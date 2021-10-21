<?php

namespace nogal;

/** CLASS {
	"name" : "nglShift",
	"object" : "shift",
	"type" : "main",
	"revision" : "20150225",
	"extends" : "nglTrunk",
	"description" : "
		Este objeto nuclea métodos de conversión de datos entre las siguientes escructuras:
		<ul>
			<li><b>array:</b> Array de datos</li>
			<li><b>csv:</b> Texto inline o multilinea de valores separados por comas</li>
			<li><b>fixed:</b> Texto inline o multilinea de valores separados ancho fijo</li>
			<li><b>html:</b> Tablas html, soporta tablas anidadas. Si se especifican thead se utilizarán como claves</li>
			<li><b>json:</b> JSON</li>
			<li><b>object:</b> Objecto de datos o un objeto del tipo iNglDataObjet</li>
			<li><b>serialize:</b> valor serializado</li>
			<li><b>xml:</b> XML</li>
		</ul>
	",
	"variables" : {
		"$vCSV" : ["private", "Configuraciones para la lectura de cadenas CSV"]
	}
} **/
class nglShift extends nglTrunk {

	protected $class	= "nglShift";
	protected $me		= "shift";
	protected $object	= "shift"; 
	private $xpath		= null; 
	private $vCSV;

	function __builder__() {
		$this->vCSV["source"]		= null;
		$this->vCSV["colnames"]		= null;
		$this->vCSV["use_colnames"]	= false;
		$this->vCSV["chk_splitter"]	= null;
		$this->vCSV["chk_enclosed"]	= null;
		$this->vCSV["chk_escaped"]	= null;
		$this->vCSV["chk_eol"]		= null;
		$this->vCSV["splitter"]		= null;
		$this->vCSV["enclosed"]		= null;
		$this->vCSV["escaped"]		= null;
		$this->vCSV["eol"]			= null;
		$this->vCSV["length"]		= null;
		$this->vCSV["pointer"]		= null;
	}

	/** FUNCTION {
		"name" : "cast",
		"type" : "public",
		"description" : "
			Formatea el valor <b>$mValue</b> según <b>$sCastType</b>, siempre que se encuentre dentro de los tipos:
			<ul>
				<li>array</li>
				<li>boolean</li>
				<li>double</li>
				<li>integer</li>
				<li>NULL</li>
				<li>object</li>
				<li>string</li>
			</ul>
		",
		"parameters" : {
			"$mValue" : ["mixed", "Variable a formatear"],
			"$sCastType" : ["string", "
				tipo de formato:<br />
				<ul>
					<li><b>text:</b> texto plano (valor predeterminado)</li>
					<li><b>html:</b> se aplica htmlspecialchars</li>
					<li><b>htmlall:</b> se aplica htmlentities</li>
				</ul>
			", "text"]
		},
		"examples" : {
			"example" : "
				$text = <<<TXT <b>El 'río' de las "pirañas"</b> TXT;
				
				echo $ngl("shift")->cast($text);
				# Retorna: <b>El 'río' de las "pirañas"</b>
				
				echo $ngl("shift")->cast($text, "html");
				# Retorna: &lt;b&gt;El &#039;río&#039; de las &quot;pirañas&quot;&lt;/b&gt;
				
				echo $ngl("shift")->cast($text, "htmlall");
				# Retorna: &lt;b&gt;El &#039;r&iacute;o&#039; de las &quot;pira&ntilde;as&quot;&lt;/b&gt;
			"
		},
		"return" : "mixed"
	} **/
	public function cast($mValue, $sCastType="text") {
		$sType = \gettype($mValue);
		
		if(!\in_array($sType, ["array","boolean","double","integer","NULL","object","string"])) { return true; }
		if(\is_object($mValue)) { $mValue = $this->objToArray($mValue); }

		$sCastType = \strtolower($sCastType);
		if(!\is_array($mValue)) {
			return $this->CastValue($mValue, $sCastType);
		} else {
			$aCleanData = [];
			foreach($mValue as $mIndex => $mValue) {
				$aCleanData[$mIndex] = (\is_array($mValue)) ? $this->cast($mValue, $sCastType) : $this->CastValue($mValue, $sCastType);
			}
			return $aCleanData;
		}
	}

	/** FUNCTION {
		"name" : "CastValue",
		"type" : "private",
		"description" : "Auxiliar del método <b>nglShift::cast</b>",
		"parameters" : {
			"$mValue" : ["mixed", "Variable a formatear"],
			"$sCastType" : ["string", "
				tipo de formato:<br />
				<ul>
					<li><b>text:</b> texto plano (valor predeterminado)</li>
					<li><b>html:</b> se aplica htmlspecialchars</li>
					<li><b>htmlall:</b> se aplica htmlentities</li>
				</ul>
			"]
		},
		"seealso" : ["nglShift::cast"],
		"return" : "mixed"
	} **/
	private function CastValue($mValue, $sCastType) {
		switch($sCastType) {
			case "html":
				return \htmlspecialchars($mValue, ENT_QUOTES, \strtoupper(NGL_CHARSET));
				break;

			case "htmlall":
				return \htmlentities($mValue, ENT_QUOTES, \strtoupper(NGL_CHARSET));
				break;

			default:
				return $mValue;
				break;
		}
	}

	/** FUNCTION {
		"name" : "convert",
		"type" : "public",
		"description" : "Convierte una estructura de datos en otra",
		"parameters" : {
			"$mData" : ["mixed", "Estructura de datos original"],
			"$sMethod" : ["string", "
				Método de conversión, que se indica separando los tipos de estruturas de origen y destino con un - (guión medio)
				
				Estructuras soportadas:
				<ul>
					<li>array</li>
					<li>csv</li>
					<li>fixed / text</li>
					<li>html</li>
					<li>json</li>
					<li>object</li>
					<li>serialize</li>
					<li>texttable / ttable</li>
					<li>yml/yaml</li>
					<li>xml</li>
				</ul>
				
				Ejemplos:
					<ul>
						<li>array-csv</li>
						<li>json-array</li>
						<li>fixed-csv</li>
						<li>html-json</li>
					</ul>
			","object-array"],
			"$vOptions" : ["array", "
				Array con las parametrizaciones de los métodos que intervienen en la conversión

				<ul>
					<li><b>class:</b> nombre de la clase CSS aplicado en la salida como tabla HTML</li>
					<li><b>colnames:</b> array con los nombres de las columnas (null)</li>
					<li><b>convert_spaces:</b> Determina si deben convertirse los caracteres de espacio</li>
					<li><b>convert_unicode:</b> Determina si los caracteres UTF-8 deberán ser convertidos a formato UNICODE (\\uXXXX)</li>					
					<li><b>joiner:</b> caracter por el que se unirán los campos (,)</li>
					<li><b>enclose:</b> caracter que se utilizará para encerrar los valores de los campos (&quot;)</li>
					<li><b>enclosed:</b> caracter utilizado para encerrar los valores de los campos (&quot;)</li>
					<li><b>eol:</b> fin de línea (\\r\\n)</li>
					<li><b>escape:</b> caracter de escape para caracteres especiales (\) Salida de datos</li>
					<li><b>escaped:</b> caracter para escapar los caracteres especiales (\) Entrada de datos</li>
					<li><b>format:</b> formato de salida para el modo HTML (table|div|list)</li>
					<li><b>level:</b> actual nivel de anidamiento (-1)</li>
					<li><b>multiline:</b> indica que los valores de origen deben considerar multiples lineas en textTableToArray</li>
					<li><b>positions:</b> array de anchos fijos para los metodos fixed</li>
					<li><b>splitter:</b> caracter separador de campos (,)</li>
					<li><b>tag:</b> nombre del siguiente tag XML (vacio)</li>
					<li><b>tthalign:</b> alineación de los encabezados en texttable</li>
					<li><b>ttdalign:</b> alineación de los contenidos en texttable</li>
					<li><b>use_colnames:</b> en combinación con <strong>colnames</strong>, establece los índices del array. 
						Con valor true y colnames = null, se utilizarán como índices los valores de la primera fila.
						(false)
					</li>
					<li><b>xml_attributes:</b> determina si se deben procesar o no los atributos de las etiquetas XML (falso)</li>					
				</ul>
			", null]
		},
		"examples" : {
			"xml-csv" : "
				$data = "
					<months>
						<month><name>enero</name><number>01</number></month>
						<month><name>febrero</name><number>02</number></month>
						<month><name>marzo</name><number>03</number></month>
						<month><name>abril</name><number>04</number></month>
						<month><name>mayo</name><number>05</number></month>
						<month><name>junio</name><number>06</number></month>
						<month><name>julio</name><number>07</number></month>
						<month><name>agosto</name><number>08</number></month>
						<month><name>septiembre</name><number>09</number></month>
						<month><name>octubre</name><number>10</number></month>
						<month><name>noviembre</name><number>11</number></month>
						<month><name>diciembre</name><number>12</number></month>
					</months>
				";
				
				echo $ngl("shift")->convert($data, "xml-csv");
				
				# salida
				"enero","01"
				"febrero","02"
				"marzo","03"
				"abril","04"
				"mayo","05"
				"junio","06"
				"julio","07"
				"agosto","08"
				"septiembre","09"
				"octubre","10"
				"noviembre","11"
				"diciembre","12"
			", 
			
			"array-json" : "
				$data = array(
					array("firstName" => "John" , "lastName" => "Doe", "age"=>36),
					array("firstName" => "Anna" , "lastName" => "Smith", "age"=>15),
					array("firstName" => "Peter" , "lastName" => "Jones", "age"=>42)
				);
				
				echo $ngl("shift")->convert($data, "array-json");
				
				# salida
				[
					{"firstName":"John","lastName":"Doe","age":36},
					{"firstName":"Anna","lastName":"Smith","age":15},
					{"firstName":"Peter","lastName":"Jones","age":42}
				]
			"
		},
		"seealso" : [
			"nglShift::cast"
		],
		"return" : "mixed"
	} **/
	public function convert($mData, $sMethod=null, $vOptions=null) {
		if(empty($sMethod)) { $sMethod = "object-array"; }
		$sMethod = \strtolower($sMethod);
		$aMethod = \explode("-", $sMethod);

		// source
		switch($aMethod[0]) {
			case "csv":
				$aData = $this->csvToArray($mData, $vOptions);
				break;

			case "text":
			case "fixed":
				$aData = $this->fixedExplode($mData, $vOptions);
				break;

			case "ttable":
			case "texttable":
				$aData = $this->textTableToArray($mData, $vOptions);
				break;

			case "html":
				$aData = $this->htmlToArray($mData);
				break;

			case "json":
				$nBackTrack = \ini_get("pcre.backtrack_limit");
				\ini_set("pcre.backtrack_limit", ($nBackTrack+\strlen($mData)));
				$aData = $this->jsonDecode($mData);
				\ini_set("pcre.backtrack_limit", $nBackTrack);
				$aData = $this->objToArray($aData);
				break;

			case "object":
				if(\method_exists($mData, "getall")) {
					$aData = $mData->getall();
				} else {
					$aData = $this->objToArray($mData);
				}
				break;

			case "serialize":
				$aData = \unserialize($mData);
				if(!\is_array($aData)) {
					if(\is_object($aData)) {
						$aData = $this->objToArray($aData);
					}
				}
				break;

			case "xml":
				$aData = $this->xmlToArray($mData, $vOptions);
				$aData = \current($aData);
				break;

			case "yml":
			case "yaml":
				if(!\function_exists("yaml_parse")) { $this->__errorMode__("die"); self::errorMessage($this->object, 1001); }
				$mData = \preg_replace(["/^\t/is", "/\t+/"], "  ", $mData);
				$aData = \yaml_parse($mData);
				break;

			case "vector":
				$aData = [];
				foreach($mData as $mItem) {
					$aData[] = [$mItem];
				}
				break;

			case "array":
			default:
				$aData = $mData;
		}
		
		if(!\is_array($aData)) { $aData = [$aData]; }

		// destine
		switch($aMethod[1]) {
			case "csv":
				return $this->csvEncode($aData, $vOptions);

			case "gchart":
				return $this->GoogleCharts($aData);
			
			case "text":
			case "fixed":
				return $this->fixedImplode($aData, $vOptions);

			case "ttable":
			case "texttable":
				return $this->textTable($aData, $vOptions);

			case "html":
				return $this->html($aData, $vOptions);

			case "json":
				return $this->jsonEncode($aData, $vOptions);

			case "object":
				return $aData = $this->objToArray($aData);

			case "serialize":
				return \serialize($aData);

			case "xml":
				return $this->xmlEncode($aData, $vOptions);
			
			case "yml":
			case "yaml":
				if(!\function_exists("yaml_emit")) { $this->__errorMode__("die"); self::errorMessage($this->object, 1001); }
				return \yaml_emit($aData);

			case "array":
			default:
				return $aData;
		}
		
	}
	
	/** FUNCTION {
		"name" : "csvEncode",
		"type" : "public",
		"description" : "Genera una cadena formateada como CSV partiendo de un Array",
		"parameters" : {
			"$aData" : ["array", "Array de datos"],
			"$vOptions" : ["array", "
				Array de opciones del método:<br />
				<ul>
					<li><b>colnames:</b> array con los nombres de las columnas (null)</li>
					<li><b>joiner:</b> caracter por el que se unirán los campos (,)</li>
					<li><b>enclose:</b> caracter que se utilizará para encerrar los valores de los campos (&quot;)</li>
					<li><b>escape:</b> caracter de escape para caracteres especiales (\)</li>
					<li><b>eol:</b> fin de línea (\\r\\n)</li>
				</ul>
			", null]
		},
		"seealso" : ["nglShift::convert","nglShift::csvToArray"],
		"return" : "string"
	} **/
	public function csvEncode($aData, $vOptions) {
		$aColnames	 	= (isset($vOptions["colnames"])) ? $vOptions["colnames"] : null;
		$sJoiner 		= (isset($vOptions["joiner"])) ? $vOptions["joiner"] : ",";
		$sEnclosed	 	= (isset($vOptions["enclose"])) ? $vOptions["enclose"] : '"';
		$sEscaped	 	= (isset($vOptions["escape"])) ? $vOptions["escape"] : "\\";
		$sEOL			= (isset($vOptions["eol"])) ? $vOptions["eol"] : "\r\n";
	
		$sCSV = "";
		if(!\is_array($aData)) { return ""; }
		
		if($aColnames) {
			if(!\is_array($aColnames) && self::call()->isTrue($aColnames)) { $aColnames = \array_keys(\array_shift($aData)); }
			foreach($aColnames as $mColumnKey => $sColumn) {
				$sColumn = \str_replace($sEnclosed, $sEscaped.$sEnclosed, $sColumn);
				$sColumn = \str_replace($sJoiner, $sEscaped.$sJoiner, $sColumn);
				$aColnames[$mColumnKey] = $sEnclosed.$sColumn.$sEnclosed;
			}
			
			$sCSV .= \implode($sJoiner, $aColnames).$sEOL;
		}
		
		if(self::call()->isarrayarray($aData)) {
			\reset($aData);
			foreach($aData as $mLineKey => $aLine) {
				foreach($aLine as $mColumnKey => $sColumn) {
					$sColumn = \str_replace($sEnclosed, $sEscaped.$sEnclosed, $sColumn);
					$sColumn = \str_replace($sJoiner, $sEscaped.$sJoiner, $sColumn);
					$aLine[$mColumnKey] = $sEnclosed.$sColumn.$sEnclosed;
				}
				$aData[$mLineKey] = \implode($sJoiner, $aLine);
			}

			$sCSV .= \implode($sEOL, $aData);
		} else {
			foreach($aData as $mColumnKey => $sColumn) {
				$sColumn = \str_replace($sEnclosed, $sEscaped.$sEnclosed, $sColumn);
				$sColumn = \str_replace($sJoiner, $sEscaped.$sJoiner, $sColumn);
				$aData[$mColumnKey] = $sEnclosed.$sColumn.$sEnclosed;
			}
			
			$sCSV .= \implode($sJoiner, $aData);
		}
		
		return $sCSV;
	}

	/** FUNCTION {
		"name" : "CSVParseLine",
		"type" : "private",
		"description" : "auxiliar del método nglShift::csvToArray. convierte una linea CSV en un array",
		"parameters" : {
			"$sSplitter" : ["string", "caracter separador de campos", ","],
			"$sEnclosed" : ["string", "caracter utilizado para encerrar los valores de los campos", "&quot;"],
			"$sEscaped" : ["string", "caracter para escapar los caracteres especiales", "\\"],
			"$sEOL" : ["string", "fin de línea", "\\r\\n"]
		},
		"seealso" : ["nglShift::csvToArray"],
		"return" : "array"
	} **/
	private function CSVParseLine($sSplitter, $sEnclosed, $sEscaped, $sEOL) {
		$aLine			= [];
		$sData			= "";
		$bEnclosed		= false;

		$x = -1;
		$sChar = "\x0B";
		while(1) {
			$sLastChar = $sChar;
			$this->vCSV["pointer"]++;
			if($this->vCSV["length"]<=$this->vCSV["pointer"]) { break; }
			$sChar = $this->vCSV["source"][$this->vCSV["pointer"]];
			
			$this->vCSV["chk_enclosed"] = ($this->vCSV["enclosed"]) ? $sChar : self::call()->strBoxAppend($this->vCSV["chk_enclosed"], $sChar);
			if($this->vCSV["chk_enclosed"]===$sEnclosed) {
				if($bEnclosed) {
					$bEnclosed = false;
					$sChar = "";
				} else {
					$bEnclosed = true;
					continue;
				}
			}
			
			$this->vCSV["chk_splitter"] = ($this->vCSV["splitter"]) ? $sChar : self::call()->strBoxAppend($this->vCSV["chk_splitter"], $sChar);
			$this->vCSV["chk_escaped"]	= ($this->vCSV["chk_enclosed"]) ? $sLastChar : self::call()->strBoxAppend($this->vCSV["chk_escaped"], $sLastChar);
			$this->vCSV["chk_eol"]		= ($this->vCSV["eol"]) ? $sChar : self::call()->strBoxAppend($this->vCSV["chk_eol"], $sChar);

			if($this->vCSV["chk_splitter"]===$sSplitter && $this->vCSV["chk_escaped"]!==$sEscaped && !$bEnclosed) {
				if($this->vCSV["use_colnames"] && \count($this->vCSV["colnames"])) {
					if(isset($this->vCSV["colnames"][\count($aLine)])) {
						$aLine[$this->vCSV["colnames"][\count($aLine)]] = $sData;
					}
				} else {
					$aLine[] = $sData;
				}
				$sData = "";
			} else {
				$sData .= $sChar;
			}

			if($this->vCSV["chk_eol"]===$sEOL) {
				$sData = \substr($sData, 0, \strlen($sEOL)*-1);
				if($this->vCSV["use_colnames"] && \count($this->vCSV["colnames"])) {
					if(isset($this->vCSV["colnames"][\count($aLine)])) {
						$aLine[$this->vCSV["colnames"][\count($aLine)]] = $sData;
					}
				} else {
					$aLine[] = $sData;
				}
				return $aLine;
			}
		}

		if($sData!=="") {
			$aLine[] = $sData;
			return $aLine;
		}
		
		return null;
	}

	/** FUNCTION {
		"name" : "csvToArray",
		"type" : "public",
		"description" : "convierte un texto CSV (una línea o conjunto de ellas) en un array bidimensional",
		"parameters" : {
			"$aData" : ["array", "Array de datos"],
			"$vOptions" : ["array", "
				Array de opciones del método:<br />
				<ul>
					<li><b>use_colnames:</b> en combinación con <strong>colnames</strong>, establece los índices del array. 
						Con valor true y colnames = null, se utilizarán como índices los valores de la primera fila.
						(false)
					</li>
					<li><b>columns:</b> array con los nombres de las columnas (null)</li>
					<li><b>splitter:</b> caracter separador de campos (,)</li>
					<li><b>enclosed:</b> caracter utilizado para encerrar los valores de los campos (&quot;)</li>
					<li><b>escaped:</b> caracter para escapar los caracteres especiales (\)</li>
					<li><b>eol:</b> fin de línea (\\r\\n)</li>
				</ul>
			", null]
		},
		"seealso" : ["nglShift::convert","nglShift::csvEncode"],
		"return" : "string"
	} **/
	public function csvToArray($sSource, $vOptions=[]) {
		$bColnames		= (isset($vOptions["use_colnames"])) ? self::call()->isTrue($vOptions["use_colnames"]) : false;
		$aColnames		= (isset($vOptions["colnames"])) ? $vOptions["colnames"] : [];
		$sSplitter	 	= (isset($vOptions["splitter"])) ? $vOptions["splitter"] : ",";
		$sEnclosed		= (isset($vOptions["enclosed"])) ? $vOptions["enclosed"] : "\"";
		$sEscaped	 	= (isset($vOptions["escaped"])) ? $vOptions["escaped"] : "\\";
		$sEOL			= (isset($vOptions["eol"])) ? $vOptions["eol"] : "\r\n";

		if(\is_array($aColnames) && \count($aColnames)) { $bColnames = true; }
		
		$this->vCSV["use_colnames"]		= $bColnames;
		$this->vCSV["colnames"]			= $aColnames;
		$this->vCSV["chk_splitter"]		= \str_pad("", \strlen($sSplitter), "\x0B");
		$this->vCSV["chk_enclosed"]		= \str_pad("", \strlen($sEnclosed), "\x0B");
		$this->vCSV["chk_escaped"]		= \str_pad("", \strlen($sEscaped), "\x0B");
		$this->vCSV["chk_eol"]			= \str_pad("", \strlen($sEOL), "\x0B");
		$this->vCSV["splitter"]			= ($this->vCSV["chk_splitter"]==="\x0B");
		$this->vCSV["enclosed"]			= ($this->vCSV["chk_enclosed"]==="\x0B");
		$this->vCSV["escaped"]			= ($this->vCSV["chk_escaped"]==="\x0B");
		$this->vCSV["eol"]				= ($this->vCSV["chk_eol"]==="\x0B");
		$this->vCSV["source"] 			= $sSource;
		$this->vCSV["length"] 			= \strlen($sSource);
		$this->vCSV["pointer"] 			= -1;

		$aCSV = [];
		while(1) {
			$aLine = null;
			if($this->vCSV["use_colnames"] && !\count($this->vCSV["colnames"])) {
				$aLine = $this->CSVParseLine($sSplitter, $sEnclosed, $sEscaped, $sEOL);
				$this->vCSV["colnames"] = $aLine;
				continue;
			}

			$aLine = $this->CSVParseLine($sSplitter, $sEnclosed, $sEscaped, $sEOL);
			if(!$aLine) { break; }
			$aCSV[] = $aLine;
		}

		return $aCSV;
	}

	/** FUNCTION {
		"name" : "fixedExplode",
		"type" : "public",
		"description" : "Convierte una cadena en Array separando sus partes por caracter fijo",
		"parameters" : {
			"$sString" : ["string", "Cadena de datos"],
			"$vOptions" : ["array", "
				Array de opciones del método:<br />
				<ul>
					<li><b>positions:</b> posiciones de corte (null)</li>
					<li><b>trim:</b> determina si debe aplicarse el método trim a cada valor obtenido (false)</li>
					<li><b>eol:</b> determina si debe tratarse a $sString como una cadena multilinea (false)</li>
				</ul>
			", null]
		},
		"examples" : {
			"sin TRIM" : "
				$data = "John            Doe       Director    36";
				$options = array("positions" => array(16,10,12,2));
				print_r($ngl("shift")->fixedExplode($data, $options));
				
				# salida
				Array (
					[0] => "John            "
					[1] => "Doe       "
					[2] => "Director    "
					[3] => 36
				)
			",
			"con TRIM" : "
				$data = "John            Doe       Director    36";
				$options = array("positions" => array(16,10,12,2), "trim" => true);
				print_r($ngl("shift")->fixedExplode($data, $options));
				
				# salida
				Array (
					[0] => "John"
					[1] => "Doe"
					[2] => "Director"
					[3] => 36
				)
			"
		},
		"seealso" : ["nglShift::convert","nglShift::fixedImplode"],
		"return" : "array"
	} **/
	public function fixedExplode($sString, $vOptions=null) {
		if(!isset($vOptions["positions"]) || !\is_array($vOptions["positions"]) || !\count($vOptions["positions"])) {
			return [$sString];
		}
		
		$sEOL = (isset($vOptions["eol"])) ? $vOptions["eol"] : false;
		$aString = ($sEOL) ? self::call()->strToArray($sString, $sEOL) : array($sString);

		$aExplode = [];
		$bTrim = (isset($vOptions["trim"]) && $vOptions["trim"]);
		foreach($aString as $sLine) {
			$nLen = 0;
			$aLine = [];
			foreach($vOptions["positions"] as $nIndex) {
				$aLine[] = ($bTrim) ? \trim(\substr($sLine, $nLen, $nIndex)) : \substr($sLine, $nLen, $nIndex);
				$nLen += $nIndex;
			}
			
			$aExplode[] = $aLine;
		}

		return ($sEOL) ? $aExplode : $aExplode[0];
	}

	/** FUNCTION {
		"name" : "fixedImplode",
		"type" : "public",
		"description" : "Convierte un Array en una cadena respetando las logitudes de <b>positions</b>. Si la longuitud de la cadena es superior al valor de <b>positions</b>, el valor será truncado.",
		"parameters" : {
			"$aString" : ["array", "Array de datos"],
			"$vOptions" : ["array", "
				Array de opciones del método:<br />
				<ul>
					<li><b>positions:</b> posiciones de unión (null)</li>
					<li><b>fill:</b> caracter de relleno ( espacio )</li>
					<li><b>joiner:</b> caracter por el que se unirán los campos ( null ). Si es distinto de NULL su longuitud sera incluida como parte del dato y se forzara su aparcion cortanto, de ser necesario, el valor del campo</li>
					<li><b>eol:</b> fin de línea (\\r\\n)</li>
				</ul>
			"]
		},
		"examples" : {
			"ejemplo #1" : "
				$data = array("John", "Doe", "Director", "36");
				$options = array("positions" => array(16,10,12,2), "fill" => ".");
				echo $ngl("shift")->fixedImplode($data, $options);
				
				# salida
				John............Doe.......Director....36
			",
			"ejemplo #2" : "
				$data = array("ENERO", "FEBRERO", "MARZO", "ABRIL", "MAYO", "JUNIO");
				$options = array("positions" => array(5,5,5,5,5,5), "fill" => "-");
				echo $ngl("shift")->fixedImplode($data, $options);
				
				# salida
				ENEROFEBREMARZOABRILMAYO-JUNIO
			"
		},
		"seealso" : ["nglShift::convert","nglShift::fixedExplode"],
		"return" : "array"
	} **/
	public function fixedImplode($aString, $vOptions=null) {
		$sFill		= (isset($vOptions["fill"])) ? $vOptions["fill"] : " ";
		$sJoiner	= (isset($vOptions["joiner"])) ? $vOptions["joiner"] : null;
		$nJoiner	= (isset($vOptions["joiner"])) ? \strlen($sJoiner) : 0;
		$sEOL		= (isset($vOptions["eol"])) ? $vOptions["eol"] : "\n";

		$bRecursive = self::call()->isarrayarray($aString);
		
		if(!isset($vOptions["positions"]) || !\is_array($vOptions["positions"]) || !\count($vOptions["positions"])) {
			if($bRecursive) {
				$aCurrent = \current($aString);
				\reset($aString);
			} else {
				$aCurrent = $aString;
			}

			$aPositions = [];
			foreach($aCurrent as $mValue) {
				$aPositions[] = self::call("unicode")->strlen($mValue);
			}
			
			$vOptions["positions"] = $aPositions;
		}
		
		$sString = "";
		if($bRecursive) {
			foreach($aString as $aLine) {
				$sString .= $this->fixedImplode($aLine, $vOptions).$sEOL;
			}
		} else {
			$aString = \array_values($aString);
			$nPositions = \count($vOptions["positions"]);
			for($x=0;$x<$nPositions;$x++) {
				$nLen = (isset($vOptions["positions"][$x])) ? $vOptions["positions"][$x] : $nPositions;
				$sValue = \substr($aString[$x],0,$nLen);
				$sValue = \str_pad($sValue, $nLen, $sFill);
				if($sJoiner!==null) { $sValue = \substr($sValue, 0, $nJoiner*-1).$sJoiner; }
				$sString .= $sValue;
			}
		}
		
		return $sString;
	}

	public function jsObject($aData) {
		$aValues = [];
		if(\is_array($aData) && \count($aData) && self::call()->isarrayarray($aData)) {
			$aFirst = \current($aData);
			$aColnames = array_keys($aFirst);
			$aRegexs = self::call("sysvar")->REGEX;
			$aTypes = [];
			$x = 0;
			foreach($aFirst as $mVal) {
				if(\is_numeric($mVal)) {
					$aTypes[] = ["type"=>"number", "label"=>$aColnames[$x]];
				} else if(\is_bool($mVal) || \in_array(\strtolower($mVal), ["false", "true"])) {
					$aTypes[] = ["type"=>"boolean", "label"=>$aColnames[$x]];
				} else if(\preg_match("/".$aRegexs["datetime"]."/i", $mVal)) {
					$aTypes[] = ["type"=>"datetime", "label"=>$aColnames[$x]];
				} else if(\preg_match("/".$aRegexs["date"]."/i", $mVal)) {
					$aTypes[] = ["type"=>"date", "label"=>$aColnames[$x]];
				} else {
					$aTypes[] = ["type"=>"string", "label"=>$aColnames[$x]];
				}
				$x++;
			}

			foreach($aData as $aRow) {
				$x = 0;
				$aNewData = [];
				foreach(\array_values($aRow) as $mVal) {
					if($aTypes[$x]["type"]=="date") {
						$aDate = \explode("-", $mVal);
						$aNewData[] = (isset($aDate[2])) ? "new Date(".$aDate[0].",".$aDate[1].",".$aDate[2].")" : "null";
					} else if($aTypes[$x]["type"]=="datetime") {
						$aDateTime = \explode(" ", $mVal);
						$aDate = \explode("-", $aDateTime[0]);
						$aTime = \explode(":", $aDateTime[1]);
						$aNewData[] = (isset($aDate[2], $aTime[2])) ? "new Date(".$aDate[0].",".$aDate[1].",".$aDate[2].",".$aTime[0].",".$aTime[1].",".$aTime[2].")" : "null";;
					} else if($aTypes[$x]["type"]=="boolean") {
						$aNewData[] = self::call()->isTrue($mVal) ? "true" : "false";
					} else if($mVal===null || \strtolower($mVal)=="null") {
						$aNewData[] = "null";
					} else if($aTypes[$x]["type"]=="number") {
						$aNewData[] = self::call()->isInteger($mVal) ? "parseInt(".(int)$mVal.")" : "parseFloat(".$mVal.")";
					} else {
						$aNewData[] = $mVal;
					}
					$x++;
				}
				$aValues[] = $aNewData;
			}
		}
	
		foreach($aTypes as $k => $sType) {
			
		}

		$sTypes = \json_encode($aTypes); 
		$sValues = \json_encode($aValues, JSON_NUMERIC_CHECK);
		$sValues = preg_replace("/\"(new Date\([0-9,]+\)|parseInt\([0-9]+\)|parseFloat\([0-9\.]+\)|null|true|false)\"/is", "\\1", $sValues);
		return ["columns"=>$sTypes, "data"=>$sValues];
	}

	/** FUNCTION {
		"name" : "html",
		"type" : "public",
		"description" : "Genera una salida HTML a partir de un Array",
		"parameters" : {
			"$aData" : ["array", "Array de datos"],
			"$sFormat" : ["string", "
				Tipo de salida HTML:<br />
				<ul>
					<li><b>table:</b> tabla HTML</li>
					<li><b>div:</b> estructura de DIVs</li>
					<li><b>list:</b> estructura de UL, LI y SPAN</li>
				</ul>
			", "table"],
			"$sClassName" : ["string", "Nombre de la clase CSS que se asignara a la tabla, filas (*-head/*-row) y columnas (*-head-cell/*-cell)", "data"],
			"$sClasses" : ["string", "Otras clasess asignadas a la tabla"],
		},
		"examples" : {
			"TABLA" : "
				$data = array(
					array("firstName" => "John" , "lastName" => "Doe", "age"=>36),
					array("firstName" => "Anna" , "lastName" => "Smith", "age"=>15),
					array("firstName" => "Peter" , "lastName" => "Jones", "age"=>42)
				);
				echo $ngl("shift")->html($data, "table", "users");
				
				# salida
				<table class="users">
					<tr class="users-head">
						<th class="users-cell">firstName</th>
						<th class="users-cell">lastName</th>
						<th class="users-cell">age</th>
					</tr>
					<tr class="users-row">
						<td class="users-cell">John</td>
						<td class="users-cell">Doe</td>
						<td class="users-cell">36</td>
					</tr>
					<tr class="users-row">
						<td class="users-cell">Anna</td>
						<td class="users-cell">Smith</td>
						<td class="users-cell">15</td>
					</tr>
					<tr class="users-row">
						<td class="users-cell">Peter</td>
						<td class="users-cell">Jones</td>
						<td class="users-cell">42</td>
					</tr>
				</table>
			",
			"Estructura de DIVs" : "
				$data = array(
					array("firstName" => "John" , "lastName" => "Doe", "age"=>36),
					array("firstName" => "Anna" , "lastName" => "Smith", "age"=>15),
					array("firstName" => "Peter" , "lastName" => "Jones", "age"=>42)
				);
				echo $ngl("shift")->html($data, "div", "users");
				
				# salida
				<div class="users">
					<div class="users-head">
						<div class="users-cell">firstName</div>
						<div class="users-cell">lastName</div>
						<div class="users-cell">age</div>
					</div>
					<div class="users-row">
						<div class="users-cell">John</div>
						<div class="users-cell">Doe</div>
						<div class="users-cell">36</div>
					</div>
					<div class="users-row">
						<div class="users-cell">Anna</div>
						<div class="users-cell">Smith</div>
						<div class="users-cell">15</div>
					</div>
					<div class="users-row">
						<div class="users-cell">Peter</div>
						<div class="users-cell">Jones</div>
						<div class="users-cell">42</div>
					</div>
				</div>
			",
			"Estructura de UL, LI y SPAN" : "
				$data = array(
					array("firstName" => "John" , "lastName" => "Doe", "age"=>36),
					array("firstName" => "Anna" , "lastName" => "Smith", "age"=>15),
					array("firstName" => "Peter" , "lastName" => "Jones", "age"=>42)
				);
				echo $ngl("shift")->html($data, "list", "users");
				
				# salida
				<ul class="users">
					<li class="users-head">
						<span class="users-cell">firstName</span>
						<span class="users-cell">lastName</span>
						<span class="users-cell">age</span>
					</li>
					<li class="users-row">
						<span class="users-cell">John</span>
						<span class="users-cell">Doe</span>
						<span class="users-cell">36</span>
					</li>
					<li class="users-row">
						<span class="users-cell">Anna</span>
						<span class="users-cell">Smith</span>
						<span class="users-cell">15</span>
					</li>
					<li class="users-row">
						<span class="users-cell">Peter</span>
						<span class="users-cell">Jones</span>
						<span class="users-cell">42</span>
					</li>
				</ul>
			",
		},
		"seealso" : ["nglShift::cast", "nglShift::convert"],
		"return" : "string"
	} **/
	public function html($aData=null, $vOptions=[]) {
		$sFormat	= (isset($vOptions["format"])) ? $vOptions["format"] : "table";
		$sClassName	= (isset($vOptions["class"])) ? $vOptions["class"] : "class";
		$sClasses	= (isset($vOptions["classes"])) ? $vOptions["classes"] : "";

		$sFormat = \strtolower($sFormat);
		switch($sFormat) {
			case "div":
				$sTagTable 	= "div";
				$sTagRow 	= "div";
				$sTagHeader	= "div";
				$sTagCell 	= "div";
				break;

			case "list":
				$sTagTable 	= "ul";
				$sTagRow 	= "li";
				$sTagHeader	= "span";
				$sTagCell 	= "span";
				break;
			
			case "table":
			default:
				$sTagTable 	= "table";
				$sTagRow 	= "tr";
				$sTagHeader	= "th";
				$sTagCell	= "td";
				break;
		}

		$sHTML = "<".$sTagTable." class=\"".$sClassName." ".$sClasses."\">\n";

		// contenido del bloque
		if(self::call()->isarrayarray($aData)) {
			// cabeceras
			$aHeaders = [];
			foreach($aData as $aRow) {
				if(\is_array($aRow) && (\count($aRow) > \count($aHeaders))) { $aHeaders = $aRow; }
			}
			
			$aColumns = \array_keys($aHeaders);
			$nColumns = \count($aColumns);
			$sHTML .= "\t<".$sTagRow." class=\"".$sClassName."-head\">\n";
			foreach($aColumns as $sColumn) {
				$sHTML .= "\t\t<".$sTagHeader." class=\"".$sClassName."-head-cell\">".$sColumn."</".$sTagHeader.">\n";
			}
			$sHTML .= "\t</".$sTagRow.">\n";
			
			// datos
			foreach($aData as $mRow) {
				$sHTML .= "\t<".$sTagRow." class=\"".$sClassName."-row\">\n";
				if(is_array($mRow)) {
					foreach($mRow as $r => $sValue) {
						$sHTML .= (\strlen($sValue)) ? "\t\t<".$sTagCell." class=\"".$sClassName."-cell\">".$sValue."</".$sTagCell.">\n" : "\t\t<".$sTagCell." class=\"".$sClassName."-cell\">&nbsp;</".$sTagCell.">\n";
					}
				} else {
					$sHTML .= (\strlen($mRow)) ? "\t\t<".$sTagCell." class=\"".$sClassName."-cell\" colspan='".$nColumns."'>".$mRow."</".$sTagCell.">\n" : "\t\t<".$sTagCell." class=\"".$sClassName."-cell\" colspan='".$nColumns."'>&nbsp;</".$sTagCell.">\n";
				}
				$sHTML .= "\t</".$sTagRow.">\n";
			}
		} else {
			if(\is_array($aData) && \count($aData)) {
				foreach($aData as $sField => $mValue) {
					$sHTML .= "\t<".$sTagRow." class=\"".$sClassName."-head\">\n";
					$sHTML .= "\t\t<".$sTagHeader." class=\"".$sClassName."-head-cell\">".$sField."</".$sTagHeader.">\n";
					$sHTML .= (\strlen($mValue)) ? "\t\t<".$sTagCell." class=\"".$sClassName."-cell\">".$mValue."</".$sTagCell.">\n" : "\t\t<".$sTagCell." class=\"".$sClassName."-cell\">&nbsp;</".$sTagCell.">\n";
					$sHTML .= "\t</".$sTagRow.">\n";
				}
			} else {
				$sHTML .= "\t<".$sTagRow." class=\"".$sClassName."-row\">\n";
				$sHTML .= "\t\t<".$sTagCell." class=\"".$sClassName."-cell\">NULL</".$sTagCell.">\n";
				$sHTML .= "\t</".$sTagRow.">\n";
			}
		}

		// cierre del bloque
		$sHTML .= "</".$sTagTable.">\n";

		return $sHTML;
	}

	/** FUNCTION {
		"name" : "htmlToArray",
		"type" : "public",
		"description" : "
			Convierte una Tabla HTML en un array, utilizando el objeto DOMDocument.
			Las tablas pueden o no tener THEAD y TBODY. En el caso de tener THEAD los valores de los TH serán 
			utilizados como indices alfanuméricos en el Array de salida.
			El método soporta multiples tablas y anidamiento de tablas; en este último caso generará sub-arrays
		",
		"parameters" : {
			"$sHTML" : ["string", "Código HTML que contiene la o las tablas"]
		},
		"seealso" : ["nglShift::convert","nglShift::HTMLTableParser","nglShift::html"],
		"return" : "array"
	} **/
	public function htmlToArray($sHTML) {
		$doc = new \DOMDocument;
		$doc->loadHTML($sHTML);
		$this->xpath = new \DOMXPath($doc);
		$tables =  $this->xpath->query("body/table");
		$aTables = [];
		foreach($tables as $table) {
			$aTables[] = $this->HTMLTableParser($table);
		}
	
		return (\count($aTables)==1) ? \current($aTables) : $aTables;
	}

	/** FUNCTION {
		"name" : "HTMLTableParser",
		"type" : "private",
		"description" : "Auxiliar del método htmlToArray",
		"parameters" : {
			"$table" : ["string", "Código HTML que contiene la o las tablas"]
		},
		"seealso" : ["nglShift::htmlToArray"],
		"return" : "DOMNodeList"
	} **/
	private function HTMLTableParser($table) {
		$aHeaders = null;
		$thead = $this->xpath->query("thead", $table);
		if($thead->length) {
			$headers = $this->xpath->query("tr/th", $thead->item(0));
			if($headers->length) {
				$aHeaders = [];
				foreach($headers as $header) {
					$aHeaders[] = \trim($header->nodeValue);
				}
			}
		}
	
		$tbody = $this->xpath->query("tbody", $table);
		if($tbody->length) { $table = $tbody->item(0); }
	
		$aTable = [];
		foreach($this->xpath->query("tr", $table) as $row) {
			$aRow = [];
			foreach($this->xpath->query("td", $row) as $sColnameey => $cell) {
				$sIndex = ($aHeaders && isset($aHeaders[$sColnameey])) ? $aHeaders[$sColnameey] : $sColnameey;
				$subtables = $this->xpath->query("table", $cell);
				if($subtables->length>0) {
					$aSubtables = [];
					foreach($subtables as $subtable) {
						$aSubtables[] = $this->HTMLTableParser($subtable);
					}
					$aRow[$sIndex] = $aSubtables;
				} else {
					$aRow[$sIndex] = \trim($cell->nodeValue);
				}
			}
			$aTable[] = $aRow;
		}
		
		return $aTable;
	}
	
	/** FUNCTION {
		"name" : "JSONChar",
		"type" : "private",
		"description" : "Auxiliar del método nglShift::jsonEncode encargado de codificar un caracter para que sea válido dentro de una cadena UTF-8",
		"parameters" : {
			"$sChar" : ["string", "Caracter a codificar", "&quot;"],
			"$bUTF8" : ["boolean", "Determina si los caracteres UTF-8 deberán ser convertidos a formato UNICODE (\\uXXXX)", "false"],
			"$bConverSpaces" : ["boolean", "Determina si deben convertirse los caracteres de espacio", "false"],
			"$bAddSlashes" : ["boolean", "Determina si deben escaparse los caracteres (",\\,/)", "false"] 
		},
		"seealso" : ["nglShift::jsonEncode"],
		"return" : "string"
	} **/
	private function JSONChar($sChar, $bUTF8=false, $bConverSpaces=false, $bAddSlashes=false) {
		if($bConverSpaces) {
			$nOrd = \ord($sChar);
			switch(true) {
				case $nOrd == 0x08:
					return "\\b";
				case $nOrd == 0x09:
					return "\\t";
				case $nOrd == 0x0A:
					return "\\n";
				case $nOrd == 0x0C:
					return "\\f";
				case $nOrd == 0x0D:
					return "\\r";
			}
		}

		if($bAddSlashes) {
			$nOrd = \ord($sChar);
			switch(true) {
				case $nOrd == 0x22:
				//case $nOrd == 0x27:
				case $nOrd == 0x2F:
				case $nOrd == 0x5C: /* double quote, slash, slosh */
					return "\\".$sChar;
			}
		}

		if(!$bUTF8) {
			return $sChar;
		} else {
			return "\\u".\str_pad(\dechex(self::call("unicode")->ord($sChar)), 4, "0", STR_PAD_LEFT);
		}
	}

	/** FUNCTION {
		"name" : "jsonDecode",
		"type" : "public",
		"description" : "Decodifica una cadena JSON de un Array",
		"parameters" : {
			"$sString" : ["string", "Cadena JSON"],
		},
		"seealso" : ["nglShift::jsonEncode"],
		"return" : "array"
	} **/
	public function jsonDecode($sString) {
		$sString = $this->JSONReduceString($sString);

		switch(\strtolower($sString)) {
			case "true":
				return true;

			case "false":
				return false;

			case "null":
				return null;

			default:
				$sString = $this->JSONReduceString($sString);

				switch(\strtolower($sString)) {
					case "true":
						return true;
		
					case "false":
						return false;
		
					case "null":
						return null;
		
					default:
						$aDecoded = [];
						if(\is_numeric($sString)) {
							return ((float)$sString==(integer)$sString) ? (integer)$sString : (float)$sString;
						} else if(\preg_match("/^(\"|').*(\\1)$/s", $sString, $aDecoded) && $aDecoded[1]==$aDecoded[2]) {
							$sQuote = \substr($sString, 0, 1);
							$sString = \substr($sString, 1, -1);
							$sString = self::call("unicode")->unescape($sString);
							$nString = \strlen($sString);
		
							$sUTF8 = "";
							for($x=0; $x<$nString; $x++) {
								$sSub2Chars = \substr($sString, $x, 2);
								switch(true) {
									case $sSub2Chars == "\\b":
										$sUTF8 .= \chr(0x08);
										$x++;
										break;
									case $sSub2Chars == "\\t":
										$sUTF8 .= \chr(0x09);
										$x++;
										break;
									case $sSub2Chars == "\\n":
										$sUTF8 .= \chr(0x0A);
										$x++;
										break;
									case $sSub2Chars == "\\f":
										$sUTF8 .= \chr(0x0C);
										$x++;
										break;
									case $sSub2Chars == "\\r":
										$sUTF8 .= \chr(0x0D);
										$x++;
										break;
		
									case $sSub2Chars == "\\\"":
									case $sSub2Chars == "\\'":
									case $sSub2Chars == "\\\\":
									case $sSub2Chars == "\\/":
										if(($sQuote=='"' && $sSub2Chars!="\\'") || ($sQuote=="'" && $sSub2Chars!='\\"')) {
											$sUTF8 .= $sString[++$x];
										}
										break;
		
									default:
										$sUTF8 .= $sString[$x];
										break;
								}
							}
		
							return $sUTF8;
		
						} else if(\preg_match("/^\[.*\]$/s", $sString) || \preg_match("/^\{.*\}$/s", $sString)) {
							if(!empty($sString) && $sString[0]=="[") {
								$aStakeState = [3];
								$aDecoded = [];
							} else {
								$aStakeState = [4];
								$aDecoded = [];
							}
		
							$aStakeState[] = [0=>1, 1=>0, 2=>false];
		
							$sString = \substr($sString, 1, -1);
							$sString = $this->JSONReduceString($sString);
		
							if($sString=="") {
								return $aDecoded;
							}
		
							$nString = \strlen($sString);
							for($x=0; $x <= $nString; ++$x) {
								$aStakeTop = \end($aStakeState);
								$sSub2Chars = \substr($sString, $x, 2);
								if(($x==$nString) || (($sString[$x]==",") && ($aStakeTop[0]==1))) {
									$sSlice = \substr($sString, $aStakeTop[1], ($x - $aStakeTop[1]));
									$aStakeState[] = [0=>1, 1=>($x + 1), 2=>false];
		
									if(\reset($aStakeState)==3) {
										$aDecoded[] = $this->jsonDecode($sSlice);
									} else if(\reset($aStakeState)==4) {
										$aParts = [];
										if(\preg_match('/^\s*(["\'].*[^\\\]["\'])\s*:\s*(\S.*),?$/Uis', $sSlice, $aParts)) {
											$mKey = $this->jsonDecode($aParts[1]);
											$mValue = $this->jsonDecode($aParts[2]);
											$aDecoded[$mKey] = $mValue;
										} else if(\preg_match('/^\s*(\w+)\s*:\s*(\S.*),?$/Uis', $sSlice, $aParts)) {
											$mKey = $aParts[1];
											$mValue = $this->jsonDecode($aParts[2]);
											$aDecoded[$mKey] = $mValue;
										}
									}
								} else if((($sString[$x]=='"') || ($sString[$x]=="'")) && ($aStakeTop[0]!=2)) {
									$aStakeState[] = [0=>2, 1=>$x, 2=>$sString[$x]];
								} else if(($sString[$x] == $aStakeTop[2]) && ($aStakeTop[0] == 2) && ((\strlen(\substr($sString, 0, $x)) - \strlen(\rtrim(\substr($sString, 0, $x), "\\"))) % 2 != 1)) {
									\array_pop($aStakeState);
								} else if(($sString[$x]=="[") && \in_array($aStakeTop[0], [1, 3, 4])) {
									$aStakeState[] = [0=>3, 1=>$x, 2=>false];
								} else if(($sString[$x]=="]") && ($aStakeTop[0]==3)) {
									\array_pop($aStakeState);
								} else if(($sString[$x]=="{") && \in_array($aStakeTop[0], [1, 3, 4])) {
									$aStakeState[] = [0=>4, 1=>$x, 2=>false];
								} else if(($sString[$x]=="}") && ($aStakeTop[0]==4)) {
									\array_pop($aStakeState);
								} else if(($sSub2Chars=="/*") && \in_array($aStakeTop[0], [1, 3, 4])) {
									$aStakeState[] = [0=>5, 1=>$x, 2=>false];
									$x++;
								} else if(($sSub2Chars=="*/") && ($aStakeTop[0]==5)) {
									\array_pop($aStakeState);
									$x++;
		
									for($y=$aStakeTop[1]; $y<=$x; ++$y) {
										$sString = \substr_replace($sString, " ", $y, 1);
									}
								}
							}
		
							if(\reset($aStakeState)==3 || \reset($aStakeState)==4) {
								return $aDecoded;
							}
						}
				}	
		}
	}

	/** FUNCTION {
		"name" : "jsonEncode",
		"type" : "public",
		"description" : "Codifica un valor en una cadena JSON",
		"parameters" : {
			"$mValue" : ["mixed", "Valor a codificar. Puede ser de cualquier tipo menos un resource"],
			"$vOptions" : ["array", "
				Array de opciones del método:<br />
				<ul>
					<li><b>convert_unicode:</b> Determina si los caracteres UTF-8 deberán ser convertidos a formato UNICODE (\\uXXXX), default TRUE</li>
					<li><b>convert_spaces:</b> Determina si deben convertirse los caracteres de espacio, default TRUE</li>
					<li><b>add_slashes:</b> Determina si deben escaparse los caracteres (',",\\,/), default TRUE</li>
				</ul>
			", null]
		},
		"seealso" : ["nglShift::jsonDecode"],
		"return" : "string"
	} **/
	public function jsonEncode($mValue, $vOptions=null) {
		$bConverUTF8 = (isset($vOptions["convert_unicode"])) ? self::call()->isTrue($vOptions["convert_unicode"]) : true;
		$bConverSpaces = (isset($vOptions["convert_spaces"])) ? self::call()->isTrue($vOptions["convert_spaces"]) : true;
		$bAddSlashes = (isset($vOptions["add_slashes"])) ? self::call()->isTrue($vOptions["add_slashes"]) : true;

		switch(\gettype($mValue)) {
			case "boolean":
				return ($mValue) ? "true" : "false";

			case "NULL":
				return "null";

			case "integer":
				return (int)$mValue;

			case "double":
			case "float":
				return (float)$mValue;

			case "string":
				$sASCII = "";
				$sChar = "";
				$bUTF8 = false;
				$nValue = \strlen($mValue);
				for($x=0; $x<$nValue; $x++) {
					if((\ord($mValue[$x])&0xC0)!=0x80) {
						if(\strlen($sChar)) {
							if(!$bConverUTF8) { $bUTF8 = false; }
							$sASCII .= $this->JSONChar($sChar, $bUTF8, $bConverSpaces, $bAddSlashes);
							$sChar = "";
							$bUTF8 = false;
						}
					} else {
						$bUTF8 = true;
					}

					$sChar .= $mValue[$x];
				}

				if(!$bConverUTF8) { $bUTF8 = false; }
				$sASCII .= $this->JSONChar($sChar, $bUTF8, $bConverSpaces, $bAddSlashes);
				return '"'.$sASCII.'"';

			case "array":
				if(\is_array($mValue) && \count($mValue) && (\array_keys($mValue)!==\range(0, \count($mValue)-1))) {
					$aProperties = \array_map([$this, "JSONNameValuePair"], \array_keys($mValue), \array_values($mValue));
					$sProperties = "{".\implode(",", $aProperties)."}";
					return $sProperties;
				}

				if(!\is_array($vOptions)) { $vOptions = []; }
				$aElements = \array_map([$this, "jsonEncode"], $mValue, $vOptions);
				return "[".\implode(",", $aElements)."]";

			case "object":
				$mValues = \get_object_vars($mValue);

				$aProperties = \array_map([$this, "JSONNameValuePair"], \array_keys($mValues), \array_values($mValues));
				return "{".\implode(",", $aProperties)."}";
		}
	}

	/** FUNCTION {
		"name" : "jsonFormat",
		"type" : "public",
		"description" : "Auxiliar del método nglShift::jsonEncode encargado generar un par ordenado NOMBRE:VALOR válido",
		"parameters" : {
			"$sJson" : ["string", "Cadena JSON"],
			"$bCompress" : ["boolean", "Cuando es TRUE, se retorna la cadena original sin saltos de líneas ni tabulaciones", false],
			"$bHTML" : ["boolean", "Determina si el resultado debe ser tratado con htmlentities", false],
			"$sTab" : ["string", "Tabulador", "\\t"],
			"$sEOL" : ["string", "Salto de línea", "\\n"]
		},
		"seealso" : ["nglShift::jsonEncode"],
		"return" : "string"
	} **/
	public function jsonFormat($sJson, $bCompress=false, $bHTML=false, $sTab="\t", $sEOL="\n") {
		$sJson = \preg_replace("/[\n\r\t]/is", "", $sJson);
		if($bCompress) { return $sJson; }
		
		$sResult = "";
		for($x=$y=$z=0;isset($sJson[$x]);$x++) {
			if($sJson[$x]=='"' && ($x>0 ? $sJson[$x-1] : "")!="\\" && $y=!$y) {
				if(!$y && \strchr(" \t\n\r", $sJson[$x])){ continue; }
			}

			if(\strchr("}]", $sJson[$x]) && !$y && $z--) {
				if(!\strchr("{[", $sJson[$x-1])) { $sResult .= $sEOL.\str_repeat($sTab, $z); }
			}
			
			$sResult .= ($bHTML) ? \htmlentities($sJson[$x]) : $sJson[$x];
			if(\strchr(",{[", $sJson[$x]) && !$y) {
				$z += (\strchr("{[", $sJson[$x])===false) ? 0 : 1;
				if(!\strchr("}]", $sJson[$x+1])) { $sResult .= $sEOL.\str_repeat($sTab, $z); }
			}
		}

		return $sResult;
	}
	
	/** FUNCTION {
		"name" : "JSONNameValuePair",
		"type" : "private",
		"description" : "Auxiliar del método nglShift::jsonEncode encargado generar un par ordenado NOMBRE:VALOR válido",
		"parameters" : {
			"$sName" : ["string", "Nombre del índice"],
			"$mValue" : ["mixed", "Valor"]
		},
		"seealso" : ["nglShift::jsonEncode"],
		"return" : "string"
	} **/
	private function JSONNameValuePair($sName, $mValue) {
		$sName = $this->jsonEncode(\strval($sName));
		$sEncoded = $this->jsonEncode($mValue);
		return $sName.":".$sEncoded;
	}	

	/** FUNCTION {
		"name" : "JSONReduceString",
		"type" : "private",
		"description" : "Auxiliar del método nglShift::jsonDecode encargado limpiar el código antes de ser parseado",
		"parameters" : {
			"$sString" : ["string", "Cadena JSON"] 
		},
		"seealso" : ["nglShift::jsonDecode"],
		"return" : "string"
	} **/
	private function JSONReduceString($sString) {
		$sString = \preg_replace(["/^\s*\/\/(.+)$/m", "/^\s*\/\*(.+)\*\//Us", "/\/\*(.+)\*\/\s*$/Us"], "", $sString);
		return \trim($sString);
	}
	
	/** FUNCTION {
		"name" : "objToArray", 
		"type" : "public",
		"description" : "Convierte un objeto en un array asosiativo de manera recursiva",
		"parameters" : { "$mObject" : ["object", "Objeto a convertir"] },
		"seealso" : ["nglShift::convert", "nglShift::objFromArray"],
		"return" : "array"
	} **/
	public function objToArray($mObject) {
		if(\is_object($mObject)) { $mObject = \get_object_vars($mObject); }

		$aArray = [];
		if(\is_array($mObject) && \count($mObject)) {
			foreach($mObject as $mKey => $mValue) {
				$mValue = (\is_array($mValue) || \is_object($mValue)) ? $this->objToArray($mValue) : $mValue;
				$aArray[$mKey] = $mValue;
			}
		}
	
		return $aArray;
	}

	/** FUNCTION {
		"name" : "objFromArray", 
		"type" : "public",
		"description" : "Convierte un Array en un Objeto de manera recursiva",
		"parameters" : { "$mObject" : ["array", "Array a convertir"] },
		"seealso" : ["nglShift::convert", "nglShift::objToArray"],
		"return" : "object"
	} **/
	public function objFromArray($aArray) {
		$oObject = new \stdClass();
		if(\is_array($aArray) && \count($aArray)) {
			foreach($aArray as $mKey => $mValue) {
				$oObject->{$mKey} = (\is_array($mValue) || \is_object($mValue)) ? $this->objFromArray($mValue) : $mValue;
			}
		}
	
		return $oObject;
	}

	/** FUNCTION {
		"name" : "XMLChildren", 
		"type" : "private",
		"description" : "Auxiliar del método ngl:Babel::xmlToArray utilizado para recorrer el objeto XML de manera recursiva",
		"parameters" : {
			"$vXML" : ["object", "Objecto XML"],
			"$bAttributes" : ["boolean", "Determina si se deben procesar o no los atributos de las etiquetas XML"],
			"$x" : ["int", "Contador interno"]
		},
		"seealso" : ["nglShift::xmlToArray"],
		"return" : "array"
	} **/
	private function XMLChildren($vXML, $bAttributes=false, &$x) {
		$aWithChildren = [];
		$vChildren = [];

		while($x++ < \count($vXML)-1) {
			$vNode = [];
			$sTagName = \strtolower($vXML[$x]["tag"]);

			switch($vXML[$x]["type"]) {
				case "complete":
					if($bAttributes) {
						$vNode["node"] = (isset($vXML[$x]["value"])) ? $vXML[$x]["value"] : [];
						$vNode["attributes"] = (isset($vXML[$x]["attributes"])) ? $vXML[$x]["attributes"] : [];
					} else {
						$vNode = (isset($vXML[$x]["value"])) ? $vXML[$x]["value"] : [];
					}

					// siempre usa el indice 0
					if(isset($vChildren[$sTagName])) {
						if(!\is_array($vChildren[$sTagName])) {
							$vChildren[$sTagName] = [$vChildren[$sTagName]];
						} else {
							if(\is_array($vChildren[$sTagName]) && \count($vChildren[$sTagName])==2 && isset($vChildren[$sTagName]["attributes"])) {
								$vChildren[$sTagName] = [$vChildren[$sTagName]];
							}
						}

						$vChildren[$sTagName][] = $vNode;
					} else {
						$vChildren[$sTagName] = $vNode;
					}

					
					break;

				case "open":
					if(isset($vXML[$x]["attributes"])) {
						$vNode["attributes"] = $vXML[$x]["attributes"];
						$vNode["node"] = $this->XMLChildren($vXML, $bAttributes, $x);
					} else {
						$vNode = $this->XMLChildren($vXML, $bAttributes, $x);
					}

					$vChildren[$sTagName][] = $vNode;
	
					break;

				case "close":
					return $vChildren;
			}
		}

		return $vChildren;
	}

	/** FUNCTION {
		"name" : "xmlEncode", 
		"type" : "public",
		"description" : "Convierte un array en una estructura XML",
			"$aData" : ["array", "Array de datos"],
			"$vOptions" : ["array", "
				Array de opciones del método:<br />
				<ul>
					<li><b>tag:</b> nombre del siguiente tag XML (vacio)</li>
					<li><b>level:</b> actual nivel de anidamiento (-1)</li>
				</ul>
			", null]
		},
		},
		"seealso" : ["nglShift::convert", "nglShift::xmlToArray"],
		"return" : "string"
	} **/
	public function xmlEncode($aData, $vOptions=null) {
		$sTagName = (isset($vOptions["tag"])) ? $vOptions["tag"] : "";
		$nLevel = (isset($vOptions["level"])) ? $vOptions["level"] : -1;
	
		if(!empty($sTagName)) { $nLevel++; }

		$sTab = ($nLevel>=0) ? \str_repeat("\t", $nLevel) : "";
		
		$sXML = ($sTagName) ? $sTab."<".$sTagName." level=\"".$nLevel."\">\n" : "";
		foreach($aData as $mKey => $mValue) {
			if(\is_array($mValue)) {
				$sTag = (!\is_numeric($mKey)) ? $mKey : "row";
				$sXML .= $this->xmlEncode($mValue, ["tag"=>$sTag, "level"=>$nLevel]);
			} else {
				$sTag = (!\is_numeric($mKey)) ? $mKey : $sTagName.":".$mKey;
				$sXML .= $sTab."\t<".$sTag.">".((\is_numeric($mValue) || $mValue==="") ? $mValue : "<![CDATA[".$mValue."]]>")."</".$sTag.">\n";
			}
		}
		$sXML .= ($sTagName) ? $sTab."</".$sTagName.">\n" : "";

		if($nLevel>0) { $nLevel--; }
		unset($aData);

		return $sXML;
	}
	
	/** FUNCTION {
		"name" : "xmlToArray", 
		"type" : "public",
		"description" : "Vuelca el contenido de un texto XML en un array asosiativo de manera recursiva",
		"parameters" : {
			"$sXML" : ["string", "Estructura XML"],
			"$vOptions" : ["array", "
				Array de opciones del método:<br />
				<ul>
					<li><b>xml_attributes:</b> determina si se deben procesar o no los atributos de las etiquetas XML (falso)</li>
				</ul>
			", null]
		},
		"seealso" : ["nglShift::convert", "nglShift::objFromArray"],
		"return" : "array"
	} **/
	public function xmlToArray($sXML, $vOptions=null) {
		$bAttributes = (isset($vOptions["xml_attributes"])) ? self::call()->isTrue($vOptions["xml_attributes"]) : false;
	
		$hXML = \xml_parser_create();
		\xml_parser_set_option($hXML, XML_OPTION_CASE_FOLDING, 0);
		\xml_parser_set_option($hXML, XML_OPTION_SKIP_WHITE, 0);
		\xml_parse_into_struct($hXML, $sXML, $aValues, $aTags);
		\xml_parser_free($hXML);

		$x = -1;
		$aArray = $this->XMLChildren($aValues, $bAttributes, $x);

		return $aArray;
	}

	public function textTable($aData, $vOptions=[]) {
		$sHeaderAlign	= (isset($vOptions["tthalign"])) ? \strtolower($vOptions["tthalign"]) : "center";
		$sBodyAlign		= (isset($vOptions["ttdalign"])) ? \strtolower($vOptions["ttdalign"]) : "left";
		$nHeaderAlign	= ($sHeaderAlign=="left") ? STR_PAD_RIGHT : ($sHeaderAlign=="right" ? STR_PAD_LEFT : STR_PAD_BOTH);
		$nBodyAlign		= ($sBodyAlign=="left") ? STR_PAD_RIGHT : ($sBodyAlign=="right" ? STR_PAD_LEFT : STR_PAD_BOTH);

		$aCells = \array_fill_keys(\array_keys(\current($aData)), 0);
		$nCells = \count($aCells);
		foreach($aCells AS $sColname => $nLength) {
			$aCells[$sColname] = self::call("unicode")->strlen(\trim($sColname, "\t"));
		}
		foreach($aData as $i => $aRow) {
			foreach($aRow as $sColname=>$sCell) {
				$sCell = \str_replace("\t", "\\t", $sCell);
				$aCells[$sColname] = \max($aCells[$sColname], self::call("unicode")->strlen($sCell));
				$aData[$i][$sColname] = $sCell;
			}
		}
		
		$sBar = "+";
		$sHeader = "|";
		foreach($aCells as $sColname => $nLength) {
			$sBar .= \str_pad("", $nLength + 2, "-")."+";
			$sHeader .= " ".self::call("unicode")->strpad($sColname, $nLength, " ", $nHeaderAlign) . " |";
		}

		$aCells = \array_values($aCells);
		$sTable = "";
		$sTable .= $sBar."\n";
		$sTable .= $sHeader."\n";
		$sTable .= $sBar."\n";
		foreach($aData as $aRow) {
			$sTable .= "|";
			if(\is_array($aRow) && \count($aRow) < $nCells) { $aRow = \array_pad($aRow, $nCells, "NULL"); }
			$x = 0;
			foreach($aRow as $sCell) {
				$sCell = \trim($sCell, "\t");
				$sCell = \preg_replace('/[\x00-\x1F\x80-\xFF]/', "?", $sCell);
				if($sCell===null) {
					$sCell = "NULL";
				} else if($sCell===false) {
					$sCell = "FALSE";
				} else if($sCell===true) {
					$sCell = "TRUE";
				}
				$sTable .= " ".self::call("unicode")->strpad($sCell, $aCells[$x], " ", $nBodyAlign) . " |";
				$x++;
			}
			$sTable .= "\n";
		}
		$sTable .= $sBar."\n";
		
		return $sTable;
	}

	public function textTableToArray($sTable, $vOptions=[]) {
		$bMultiline = (isset($vOptions["multiline"])) ? $vOptions["multiline"] : false;
		$aTable = \explode("\n", $sTable);
		\array_shift($aTable);
		$aHeaders = self::call()->explodeTrim("|", \array_shift($aTable));
		$aHeaders = \array_slice($aHeaders, 1, -1);

		if($bMultiline) {
			\array_shift($aTable);
			$aMultilineEmpty = $aMultiline = \array_fill_keys(\array_keys($aHeaders), "");
		} else {
			$aTable = \array_slice($aTable, 1, -1);
		}

		$aReturn = [];
		if(\is_array($aTable) && \count($aTable)) {
			foreach($aTable as $sRow) {
				$sRow = \trim($sRow);
				if($bMultiline) {
					if(!empty($sRow) && $sRow[0]=="+") {
						$aReturn[] = \array_combine($aHeaders, $aMultiline);
						$aMultiline = $aMultilineEmpty;
						continue;
					}
					$aMultiline = self::call()->arrayConcat([$aMultiline, \array_slice(self::call()->explodeTrim("|", $sRow), 1, -1)], " ");
				} else {
					if(!empty($sRow) && $sRow[0]=="+") { continue; }
					$aReturn[] = \array_combine($aHeaders, \array_slice(self::call()->explodeTrim("|", $sRow), 1, -1));
				}
			}
		}

		return $aReturn;
	}
}

?>