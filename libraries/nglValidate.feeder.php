<?php

namespace nogal;

/** CLASS {
	"name" : "nglValidate",
	"object" : "validate",
	"type" : "main",
	"revision" : "20151027",
	"extends" : "nglTrunk",
	"description" : "
		Validación de variables basada en reglas.
		
		nglValidate construye el objeto $validate dentro del framework, el cual es accedido a través de: $<b>ngl("validate")->NOMBRE_DE_METODO(...)</b>
		
		Cuenta con dos valores de configuración que impactan en el procesamiento de la variable <b>$_REQUEST</b>:
		<ul>
			<li><b>proccess:</b> Determina si se procesa o no la variable <strong>$_REQUEST</strong>. Default: <strong>1</strong></li>
			<li><b>from:</b> Origen de la solicitud (HTTP_REFERER) LOCAL | ALL | HOST1,HOST2,HOSTn | IP1,IP2,IPn. Default: <strong>LOCAL</strong></li>
		</ul>
		

		Los tipos de validaciones soportadas por la clase son<
		<ul>
			<li><b>all:</b> Cualquier tipo de contenido</li>
			<li><b>alpha:</b> Cadena compuesta unicamente por letras y espacios</li>
			<li><b>base64:</b> Cadena en formato base64</li>
			<li><b>color:</b> Color en formato hexadecimal #RGB, #RRGGBB ó #RRGGBBAA</li>
			<li><b>coords:</b> par de geo coordenadas separadas por coma o punto y coma</li>
			<li><b>date:</b> Fecha en formato YYYY-mm-dd</li>
			<li><b>datetime:</b> Fecha y hora en formato YYYY-mm-dd HH:ii:ss</li>
			<li><b>email:</b> Dirección de correo</li>
			<li><b>filename:</b> Nombre de archivo</li>
			<li><b>html:</b> Cualquier tipo de contenido. El valor será tratado con HTMLENTITIES</li>
			<li><b>imya:</b> IMYA</li>
			<li><b>int:</b> Números enteros [^0-9]</li>
			<li><b>number:</b> Números formateados [0-9\.\,\-]</li>
			<li><b>ipv4:</b> Dirección IPV4</li>
			<li><b>ipv6:</b> Dirección IPV6</li>
			<li><b>regex:</b> Validación por expresiones regulares. La expresión regular es pasada por medio de la option <b>pattern</b></li>
			<li><b>text:</b> Cadena compuesta por letras, números, simbolos de uso frecuente y espacios</li>
			<li><b>time:</b> Hora en formato YYYY-mm-dd HH:ii:ss</li>
			<li><b>url:</b> URL http o ftp, segura o no</li>
			<li><b>string:</b> Cadena compuesta por letras, números y espacios</li>
			<li><b>symbols:</b> Solo símbolos y espacios</li>
		</ul>
	",
	"configfile" : "validate.conf",
	"variables" : {
		"$bCheckError" : ["private", "Control de ocurrencia de errores"],
		"$mVariables" : ["private", "Control de ocurrencia de errores"],
		"$vVariables" : ["private", "Variables utilizadas dentro de las reglas"],
		"$vTypes" : ["private", "Tipos de validaciones soportadas"]
	}
} **/
class nglValidate extends nglTrunk {

	protected $class		= "nglValidate";
	protected $me			= "validate";
	protected $object		= "validate";
	private $bCheckError 	= false;
	private $mVariables;
	private $vVariables;
	private $vRegex;

	public function __builder__() {
		$this->vRegex = self::call("sysvar")->REGEX;

		if(\file_exists(NGL_PATH_CONF.NGL_DIR_SLASH."validate.conf")) {
			$sConfig = \file_get_contents(NGL_PATH_CONF.NGL_DIR_SLASH."validate.conf");
			$vConfig = self::parseConfigString($sConfig, true);

			if(isset($vConfig["request"])) {
				if(isset($vConfig["request"]["proccess"]) && self::call()->isTrue($vConfig["request"]["proccess"])) {
					if(isset($vConfig["request"]["from"])) {
						$this->request($vConfig["request"]["from"]);
					} else {
						$this->request();
					}
				}
			}
		}
	}

	/** FUNCTION {
		"name" : "addvar",
		"type" : "public",
		"description" : "
			Almacena valores que luego pueden ser utilizados como variables dentro de las reglas. Esto es especialmente util dentro de los archivos <b>.json</b>.
			Este método retorna el propio objeto, a fin de poder añadir varias variables con una sintaxis cómoda.
		",
		"parameters" : {
			"$sVarName" : ["string", "Nombre que luego se utilizará para invocar el valor"],
			"$mValue" : ["string", "Valor"]
		},
		"examples" : {
			"Modo de empleo" : '
				[b][u]Variables PHP[/u][/b]
				$var1 = "string";
				$var2 = "milk,rice,sugar,tea";
				

				[b][u]Añadiendo valores[/u][/b]
				$ngl("validate")
					->addvar("type", $var1)
					->addvar("minlen", "3")
					->addvar("list", $var2)
				;


				[b][u]Archivo foobar.json[/u][/b]
				{
					"type" : "{$type}",
					"minlength" : "{$minlen}",
					"in" : "{$list}"
				}


				[b][u]Execución[/u][/b]
				echo $ngl("validate")->validate("rice", "foobar");
			'
		},
		"return" : "object"
	} **/
	public function addvar($sVarName, $mValue) {
		$this->vVariables[$sVarName] = $mValue;
		return $this;
	}

	/** FUNCTION {
		"name" : "CheckValue",
		"type" : "private",
		"description" : "Aplica las reglas <b>$vRules</b> sobre la variable <b>$mSource</b>. Este método es auxiliar de <b>validate</b>",
		"parameters" : {
			"$mSource" : ["mixed", "Variable o Array a validar"],
			"$vRules" : ["array", "Reglas de validación"]
		},
		"return" : "mixed"
	} **/
	private function CheckValue($mSource, $vRules) {
		if(!isset($vRules["options"])) { $vRules["options"] = []; }
		
		if(isset($vRules["options"]["multiple"])) {
			$aValues = \preg_split("/".$vRules["options"]["multiple"]."/s", $mSource);
			unset($vRules["options"]["multiple"]);
			
			$mValue = [];
			foreach($aValues as $mSource) {
				$mValue[] = $this->validate($mSource, $vRules);
			}
		} else {
			$mValue = $mSource;

			if(isset($vRules["options"]["decode"])) {
				if(\strtolower($vRules["options"]["decode"])=="urldecode") {
					$mValue = \urldecode($mValue);
				} else if(\strtolower($vRules["options"]["decode"])=="rawurldecode ") {
					$mValue = \rawurldecode($mValue);
				}
			}

			$mSource = (isset($vRules["options"]["striptags"])) ? \strip_tags($mSource, $vRules["options"]["striptags"]) : $mSource;
			$mValue = $this->ValidateByType($mSource, $vRules["type"], $vRules["options"]);

			if(empty($mValue) && !empty($mSource)) {
				$this->bCheckError = true;
				return "type";
			}

			$nLength = self::call("unicode")->strlen($mValue);
			
			// minlength
			if(isset($vRules["minlength"]) && $nLength < (int)$vRules["minlength"]) {
				$this->bCheckError = true;
				return "minlength";
			}

			// maxlength
			if(isset($vRules["maxlength"]) && $nLength > (int)$vRules["maxlength"]) {
				$this->bCheckError = true;
				return "maxlength";
			}

			// lessthan y greaterthan
			$bLess = (isset($vRules["lessthan"]));
			$bGreat = (isset($vRules["greaterthan"]));
			if($bLess || $bGreat) {
				$mLess = ($bLess) ? $vRules["lessthan"] : $vRules["greaterthan"];
				$mGreat = ($bGreat) ? $vRules["greaterthan"] : $vRules["lessthan"];
				
				$nBetween = self::call()->between($mValue, $mLess, $mGreat);

				if($bLess && !$bGreat && $nBetween>0) { $this->bCheckError = true; return "lessthan"; }
				if($bGreat && !$bLess && $nBetween<2) { $this->bCheckError = true; return "greaterthan"; }
				if($bGreat && $bLess && $nBetween!=1) { $this->bCheckError = true; return "between"; }
			}
			
			// in
			if(isset($vRules["in"])) {
				$aIn = self::call("unicode")->explode(",", $vRules["in"]);
				if(!\in_array($mValue, $aIn)) { $this->bCheckError = true; return "in"; }
			}
		}

		if(isset($vRules["options"]["addslashes"]) && self::call()->istrue($vRules["options"]["addslashes"])) {
			$mValue = \addslashes($mValue);
		}

		if(isset($vRules["options"]["quotemeta"]) && self::call()->istrue($vRules["options"]["quotemeta"])) {
			$mValue = \quotemeta($mValue);
		}
		
		return $mValue;
	}

	/** FUNCTION {
		"name" : "ClearCharacters",
		"type" : "private",
		"description" : "Retorna una cadena despues de compararla contra <b>$aToClean</b>",
		"parameters" : {
			"$sString" : ["string", "Valor"],
			"$aToClean" : ["array", "Array con los caracteres a conservar/eliminar"],
			"$bInvert" : ["boolean", "Indica si deben retornarse los valores que se encuentran en <b>$aToClean</b> o los que no se encuentran", "false"]
		},
		"return" : "string"
	} **/
	private function ClearCharacters1($sString, $aToClean, $bInvert=false) {
		$sNewString = $sInvertString = $sChar = "";
		$nString = \strlen($sString);
		for($x=0; $x<$nString; $x++) {
			if((\ord($sString[$x])&0xC0)!=0x80) {
				if(\strlen($sChar)) {
					$nOrd = self::call("unicode")->ord($sChar);
					if(!isset($aToClean[$nOrd])) {
						$sNewString .= $sChar;
					} else {
						$sInvertString .= $sChar;
					}
					$sChar = "";
				}
				$sChar .= $sString[$x];
			} else {
				$sChar .= $sString[$x];
			}
		}

		$nOrd = self::call("unicode")->ord($sChar);
		if(!isset($aToClean[$nOrd])) {
			$sNewString .= $sChar;
		} else {
			$sInvertString .= $sChar;
		}
		
		return ($bInvert) ? $sInvertString : $sNewString;
	}

	/** FUNCTION {
		"name" : "GetRulesFile",
		"type" : "private",
		"description" : "Obtiene la configuración de un archivo <b>.json</b> y la retorna en como un Array",
		"parameters" : {
			"$sRulesFile" : ["string", "Nombre del archivo <b>.json</b> ubicado en la carpeta <b>NGL_PATH_VALIDATE</b>"]
		},
		"return" : "array"
	} **/
	private function GetRulesFile($sRulesFile) {
		if(\file_exists(NGL_PATH_VALIDATE.NGL_DIR_SLASH.$sRulesFile.".json")) {
			$sRules = \file_get_contents(NGL_PATH_VALIDATE.NGL_DIR_SLASH.$sRulesFile.".json");
			$sRules = \trim($sRules);
			return \json_decode($sRules, true);
		}
		
		return null;
	}

	/** FUNCTION {
		"name" : "request",
		"type" : "public",
		"description" : "
			Valida y reemplaza los valores de la variable global <b>$_REQUEST</b> en base al archivo <b>REQUEST.json</b>
			Este método sobreescribe los valores de <b>$_REQUEST</b>. Para obtener los valores originales se deberán consultar <b>$_GET y $_POST</b>.
			La parametrización del arranque de esta propiedad se efectua desde el archivo <b>NGL_PATH_CONF/validate.conf</b>
		",
		"parameters" : {
			"$sFrom" : ["string", "
				Especifica el origen válido de la solicitud, a fin de impedir consultas no autorizadas.
				En caso de que la solicitud provenga de un origen inválido se vaciará <b>$_REQUEST</b>
				
				<b>Origenes válidos</b>
				<ul>
					<li><b>ALL:</b> Cualquier origen</li>
					<li><b>LOCAL:</b> Solicitudes provenientes del propio servidor. Este es el comportamiento predeterminado</li>
					<li><b>Especificas:</b> <b>Hostnames</b> y/o direcciones <b>IP</b> separados por comas (,)</li>
				</ul>
			", "LOCAL"]
		},
		"return" : "array"
	} **/
	public function request($sFrom="LOCAL") {
		$bProccess = false;
		if(isset($_SERVER["HTTP_REFERER"])) {
			$aURL = \parse_url($_SERVER["HTTP_REFERER"]);
			$sRequestHost = $aURL["host"];
			$sIP = \gethostbyname($sRequestHost);
			
			$aHost = \parse_url($_SERVER["HTTP_HOST"]);
			$sHost = (isset($aHost["host"])) ? $aHost["host"] : $aHost["path"];
			if($sHost==$sRequestHost) {
				$bProccess = true;
			} else {
				$aRequestsFroms = $this->RequestFrom($sFrom);
				switch(1) {
					case (!isset($aRequestsFroms["LOCAL"])):
					case (isset($aRequestsFroms["ALL"])):
					case (isset($aRequestsFroms[$sRequestHost])):
					case (isset($aRequestsFroms[$sIP])):
						$bProccess = true;
					break;
				}
			}
		}

		if($bProccess || !isset($_SERVER["HTTP_REFERER"])) {
			$_REQUEST = $this->validate($_REQUEST, "REQUEST");
		} else {
			$_REQUEST = [];
		}
	}

	/** FUNCTION {
		"name" : "RequestFrom",
		"type" : "private",
		"description" : "Analiza la cadena <b>$sFrom</b> y retorna un array de origines para ser utilizados en <b>request</b>",
		"parameters" : { "$sFrom" : ["string", "Cadena de posibles origenes"] },
		"return" : "array"
	} **/
	private function RequestFrom($sFrom) {
		$aRequestsFroms = ["LOCAL" => true];
		if(!empty($sFrom)) {
			$sFrom = \strtoupper($sFrom);
			$sFrom = \trim($sFrom);
			if($sFrom!="ALL" && $sFrom!="LOCAL") {
				$aRequestsFroms = self::call()->explodeTrim(",", $sFrom);
			} else {
				$aRequestsFroms = [$sFrom => true];
			}
		}

		return $aRequestsFroms;
	}

	/** FUNCTION {
		"name" : "resetvars",
		"type" : "public",
		"description" : "Desetea las variables seteadas con <b>ngl:Validate::addvar</b>",
		"return" : "void"
	} **/
	public function resetvars() {
		$this->vVariables = [];
	}

	/** FUNCTION {
		"name" : "validate", 
		"type" : "public",
		"description" : "Valida la variable <b>$mVariables</b> aplicando las reglas <b>$mRules</b>. Si <b>$mRules</b> no está definido, retornará <b>NULL</b>",
		"parameters" : {
			"$mVariables" : ["mixed", "Variable o Array a validar"],
			"$mRules" : ["mixed", "
				Conjunto de reglas que se aplicarán para la validación.
				$mRules acepta texto JSON, el nombre de un archivo .json o un Array de datos
				
				<b>Sintaxsis:</b><br />
				{
					<blockquote>
						<b>nombre_campo</b>: {
							<blockquote>
								<b>type</b>: Es el único valor obligatorio. Ver tipos
									<b>required</b> : 1 o 0
									<b>default</b> : Valor utilizado cuando el valor no esté seteado o no cumpla con la validación
									<b>minlength</b>: Longuitud mínima del valor
									<b>maxlength</b>: Longuitud máxima del valor
									<b>lessthan</b>: El valor debe ser menor a ... valor alfanumerico
									<b>greaterthan</b>: El valor debe ser mayor a ... valor alfanumerico
									<b>in</b>: Lista de valores separados por coma

									<b>options</b>: [
										<blockquote>
											<b>addslashes</b> : Especifica si el valor debe ser tratado con <b>addslashes</b> después de ser validado
											<b>allow</b> : Tipos o grupos de caracteres UTF-8 (nglUnicode::groups) mas el grupo especial <b>SPACES</b>, separados por coma, que aceptará el campo (tipo ALL)
											<b>decode</b> : Especifica si el valor debe ser tratado con <b>urldecode</b> o <b>rawurldecode</b> antes de ser validado
											<b>deny</b> : Tipos o grupos de caracteres UTF-8 (nglUnicode::groups) mas el grupo especial <b>SPACES</b>, separados por coma, que aceptará el campo (tipo ALL)
											<b>encoding</b> : Juego de caracteres empleado durante la conversión htmlentities (tipo HTML)
											<b>htmlentities</b> : Flags utilizados durante la conversión htmlentities (tipo HTML)
											<b>multiple</b> : Caracter utilizado para separar el valor de la variable y convertirla en un array de valores. Todas la variables pueden ser multiples
											<b>pattern</b> : Expresión regular (tipo REGEX)
											<b>quotemeta</b> : Especifica si el valor debe ser tratado con <b>quotemeta</b> después de ser validado
											<b>striptags</b> : Determina cuales son las etiquetas permitidas para aplicar <b>strip_tags</b> sobre el valor antes de ser validado (tipos: ALL, ALPHA, HTML, TEXT, STRING)
										</blockquote>
									]
							</blockquote>
						}
					</blockquote>
				}

				Cuando las reglas son del tipo JSON, archivo o texto, se pueden invocar valores variables agregados por medio de <b>addvar</b>, utilizando la sintaxsis {$nombre_variable}
				
				Nota: el grupo especial <b>SPACES</b> contiene a los caracteres:
				<ul>
					<li>tabulación (ord:9)</li>
					<li>salto de línea (ord:10)</li>
					<li>retorno de carro (ord:13)</li>
					<li>espacio (ord:32)</li>
				</ul>
			"],
			"$bIgnoreDefault" : ["boolean", "Desactiva el uso de valores <b>default</b>", "false"],
		},
		"seealso" : ["nglUnicode::groups"],
		"examples" : {
			"Reglas JSON en archivo .json" : "
				El archivo debe estar alojado en la carpeta <b>NGL_PATH_VALIDATE</b> del proyecto
			
				$age = 22; 
				$age = $ngl("validate")->validate($age, "rules_age");
			",

			"Reglas JSON en línea" : "
				$rules = "{
					"type" : "regex",
					"options" : { "pattern" : "\\\$[0-9]+" },
					"minlength" : 5
				}";
				
				$var = "$522"; 
				$var = $ngl("validate")->validate($var, $rules);
			",

			"Reglas en Array" : "
				$rules = [];
				$rules["type"] = "html";
				$rules["options"] = [
					\"htmlentities" => \"ENT_COMPAT,ENT_HTML401,ENT_QUOTES\",
					\"striptags\" => \"<i>\"
				);

				$var = "<b>prueba de 'validación' de datos <i>HTML</i></b>";
				$var = $ngl("validate")->validate($var, $rules);
			",

			"Validación multiple" : "
				$rules = "{
					"type" : "email",
					"options" : { "multiple" : ";" }
				}";

				$emails = "mail1@foobar.com;mail2@foobar.com;mail3@foobar.com";
				print_r($ngl("validate")->validate($emails, $rules));
			",

			"Validación de Arrays" : "
				$rules = "{
					"product" : {
						"type" : "string",
						"default" : "milk",
						"in" : "milk,rice,sugar,tea"
					},
					
					"quantity" : {
						"type" : "int",
						"greaterthan" : "1",
						"lessthan" : "20",
					},
					
					"price" : {
						"type" : "regex",
						"options" : { "pattern" : "\\\$[0-9]+" },
						"minlength" : 5
					}
				}";

				print_r($ngl("validate")->validate($_POST, $rules));
			",
			
			"Tipo all customizado" : "
				$rules = [];
				$rules["type"] = "all";
				$rules["options"] = [
					\"allow\" => \"LATIN_BASIC_LOWERCASE,LATIN_BASIC_NUMBERS\",
					\"striptags\" => true
				);

				$var = "<b>ESTA ES 'la prueba' <i>1234</i></b>";
				$var = $ngl("validate")->validate($var, $rules);
				
				# retornara: laprueba1234
			"
		},

		"return" : "mixed"
	} **/
	public function validate($mVariables, $mRules, $bIgnoreDefault=false) {
		if(\is_array($mRules)) {
			$aRules = $mRules;
		} else {
			$sRules = \trim($mRules);

			$aRules = self::call()->isJson($sRules, "array");
			if($aRules===false) {
				$aRules = $this->GetRulesFile($sRules);	
			}
		}

		if($aRules===null) { return null; }
		
		if(!\is_array($mVariables)) {
			$this->bCheckError = false;
			$mCheck = $this->CheckValue($mVariables, $aRules);

			if($this->bCheckError && !\is_array($mCheck)) {
				$mCheck = "error => ".$mCheck;
			}

			return $mCheck;
		}

		$vReport = $vValidated = [];
		foreach($aRules as $sField => $vRules) {
			unset($mValue);
			foreach($vRules as $sRule => $mRule) {
				if(\is_string($mRule) && \preg_match("/^\{".self::call("sysvar")->REGEX["phpvar"]."\}$/s", $mRule)) {
					$sVarname = \substr($mRule, 2, -1);
					$vRules[$sRule] = (isset($this->vVariables[$sVarname])) ? $this->vVariables[$sVarname] : $mRule;
				}
			}

			if(!isset($vRules["type"])) {
				$vReport[$sField] = "type";
				continue;
			}

			if(isset($mVariables[$sField])) {
				$mValue = $mVariables[$sField];
				if(\is_array($mValue)) {
					$bError = false;
					foreach($mValue as $mKey => $mSubValue) {
						$this->bCheckError = false;
						$mSubValue = $this->CheckValue($mSubValue, $vRules);
						if($this->bCheckError) {
							$bError = true;
							$vReport[$sField." => ".$mKey] = $mSubValue;

							// valor por defecto
							if(!$bIgnoreDefault && isset($vRules["default"])) {
								$mValue[$mKey] = $vRules["default"];
							} else {
								unset($mValue[$mKey]);
							}
						} else {
							$mValue[$mKey] = $mSubValue;
						}
					}
				} else {
					$this->bCheckError = false;
					$mValue = $this->CheckValue($mValue, $vRules);
					if($this->bCheckError) {
						// valor por defecto
						if(!$bIgnoreDefault && isset($vRules["default"])) {
							$mValue = $vRules["default"];
						} else {
							$vReport[$sField] = $mValue;
							continue;
						}
					}
				}
			} else {
				if(!$bIgnoreDefault && isset($vRules["default"])) {
					$mValue = $vRules["default"];
				} else if(isset($vRules["required"]) && self::call()->istrue($vRules["required"])) {
					$vReport[$sField] = "required";
					continue;
				}
			}

			if(isset($mValue)) { $vValidated[$sField] = $mValue; }
		}

		$vCheck 			= [];
		$vCheck["source"]	= $mVariables;
		$vCheck["rules"]	= $aRules;
		$vCheck["values"]	= $vValidated;
		$vCheck["errors"]	= \count($vReport);
		$vCheck["report"]	= $vReport;

		return $vCheck;
	}

	/** FUNCTION {
		"name" : "ValidateByType",
		"type" : "private",
		"description" : "Validador de variables por tipo",
		"parameters" : {
			"$mValue" : ["string", "Valor"],
			"$sType" : ["string", "Tipo de validación"],
			"$vOptions" : ["array", "Parámetros auxiliares de la validación", "array()"]
		},
		"return" : "string"
	} **/
	private function ValidateByType($mValue, $sType, $vOptions=[]) {
		if(\ini_get("magic_quotes_sybase") && (\strtolower(\ini_get("magic_quotes_sybase"))!="off")) {
			$mValue = \stripslashes($mValue);
		}
	
		$aParams = [];
		$mNewValue = "";
		$sType = \strtolower($sType);
		switch($sType) {
			case "all":
				if(isset($vOptions["allow"])) {
					$aToClean = self::call()->explodeTrim(",", $vOptions["allow"]);
					$bDeny = false;
				} else if(isset($vOptions["deny"])) {
					$bDeny = true;
					$aToClean = self::call()->explodeTrim(",", $vOptions["deny"]);
				}
				
				if(isset($bDeny)) {
					$bSpaces = false;
					foreach($aToClean as $sValue) {
						$sValue = \strtoupper($sValue);

						if($sValue=="SPACES") { $bSpaces = true; }
						if(\strlen($sValue)==3) {
							$aParams["types"][$sValue] = true;
						} else {
							$aParams["groups"][$sValue] = true;
						}
					}

					if($bSpaces) { $aParams["chars"] = [9=>true,10=>true,13=>true,32=>true]; }
					$mNewValue = $this->ClearCharacters($mValue, $aParams, $bDeny);
				} else {
					$mNewValue = $mValue;
				}
				break;

			case "html":
				$vFlags 					= [];
				$vFlags["ENT_COMPAT"]		= ENT_COMPAT;
				$vFlags["ENT_QUOTES"]		= ENT_QUOTES;
				$vFlags["ENT_NOQUOTES"]		= ENT_NOQUOTES;
				$vFlags["ENT_IGNORE"]		= ENT_IGNORE;
				$vFlags["ENT_SUBSTITUTE"]	= ENT_SUBSTITUTE;
				$vFlags["ENT_DISALLOWED"]	= ENT_DISALLOWED;
				$vFlags["ENT_HTML401"]		= ENT_HTML401;
				$vFlags["ENT_XML1"]			= ENT_XML1;
				$vFlags["ENT_XHTML"]		= ENT_XHTML;
				$vFlags["ENT_HTML5"]		= ENT_HTML5;
				
				$sFlags = (isset($vOptions["htmlentities"])) ? $vOptions["htmlentities"] : "ENT_COMPAT,ENT_HTML401";
				$aFlags = \explode(",", $sFlags);

				$nFlag = 0;
				foreach($aFlags as $sFlag) {
					$sFlag = \trim($sFlag);
					$nFlag |= $vFlags[\strtoupper($sFlag)];
				}
				
				$sEncoding = (isset($vOptions["encoding"])) ? $vOptions["encoding"] : "UTF-8";
				$mNewValue = \htmlentities($mValue, $nFlag, $sEncoding);
				break;

			case "regex":
				if(isset($vOptions["pattern"])) {
					if(\preg_match("/".$vOptions["pattern"]."/s", $mValue)) {
						$mNewValue = $mValue;
					}
				}
				break;

			case "base64file":
				$sType = "base64";
				$mValue = \substr($mValue, \strpos($mValue, ",")+1);
			case "base64":
			case "color":
			case "date":
			case "datetime":
			case "email":
			case "filename":
			case "imya":
			case "ipv4":
			case "ipv6":
			case "time":
			case "url":
				if(\preg_match("/^".$this->vRegex[$sType]."$/s", $mValue)) {
					$mNewValue = $mValue;
				}
				break;
				
			case "int":
				$mNewValue = \preg_replace("/^[^0-9]+$/s", "", $mValue);
				$mNewValue = (int)$mNewValue;
				break;

			case "coords":
				$mNewValue = \preg_replace("/^[^0-9\.\-](,|;)[^0-9\.\-]+$/s", "", $mValue);
				break;

			case "number":
				$mNewValue = \preg_replace("/^[^0-9\.\,]+$/s", "", $mValue);
				if(!\is_numeric($mNewValue)) { $mNewValue = 0; }
				$mNewValue *= 1;
				break;

			case "alpha":
				$aParams["types"] = ["ABC"=>true, "ABU"=>true];
				$aParams["groups"] = [];
				$aParams["chars"] = [9=>true,10=>true,13=>true,32=>true];
				$mNewValue = $this->ClearCharacters($mValue, $aParams);
				break;

			case "string":
				$aParams["types"] = ["ABC"=>true, "ABU"=>true, "NUM"=>true];
				$aParams["groups"] = [];
				$aParams["chars"] = [9=>true,10=>true,13=>true,32=>true];
				$mNewValue = $this->ClearCharacters($mValue, $aParams);
				break;

			case "text":
				$aParams["types"] = ["ABC"=>true, "ABU"=>true, "SYL"=>true, "NUM"=>true, "SYM"=>true];
				$aParams["groups"] = ["BASIC_LATIN_SYMBOLS"=>true];
				$aParams["chars"] = [9=>true,10=>true,13=>true,32=>true];
				$mNewValue = $this->ClearCharacters($mValue, $aParams);
				break;

			case "symbols":
				$aParams["types"] = ["SYM"=>true];
				$aParams["groups"] = [];
				$aParams["chars"] = [9=>true,10=>true,13=>true,32=>true];
				$mNewValue = $this->ClearCharacters($mValue, $aParams, true);
				break;
		}

		return $mNewValue;
	}
		
	private function ClearCharacters($sString, $aParams=null, $bInvert=false) {
		$sNewString = $sInvertString = $sChar = "";
		$nString = \strlen($sString);
		for($x=0; $x<$nString; $x++) {
			if((\ord($sString[$x])&0xC0)!=0x80) {
				if(\strlen($sChar)) {
					$aIs = self::call("unicode")->ischr($sChar);
					if(isset($aParams["types"][$aIs[0]]) || isset($aParams["groups"][$aIs[1]]) || isset($aParams["chars"][$aIs[2]])) {
						$sNewString .= $sChar;
					} else {
						$sInvertString .= $sChar;
					}
					$sChar = "";
				}
				$sChar .= $sString[$x];
			} else {
				$sChar .= $sString[$x];
			}
		}

		$aIs = self::call("unicode")->ischr($sChar);
		
		if(isset($aParams["types"][$aIs[0]]) || isset($aParams["groups"][$aIs[1]]) || isset($aParams["chars"][$aIs[2]])) {
			$sNewString .= $sChar;
		} else {
			$sInvertString .= $sChar;
		}
		
		return ($bInvert) ? $sInvertString : $sNewString;
	}
}

?>