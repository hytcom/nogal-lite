<?php

namespace nogal {
	/** CLASS {
		"name" : "nglRind",
		"object" : "rind",
		"type" : "instanciable",
		"revision" : "20150316",
		"extends" : "nglBranch",
		"description" : "Motor de plantillas.",
		"variables" : {
			"RIND_UID" : ["private", "ID único del objeto."],
			"RIND_ME" : ["private", "ID del objeto entrecomillado."],
			"RIND_DOLLAR_SIGN" : ["private", "cadena reservada para el signo $ (variables)."],
			"RIND_QUOTE" : ["private", "cadena reservada para las comillas dentro del pseudo código."],
			"RIND_HTML_QUOTE" : ["private", "cadena reservada para las comillas dobles utilizadas en las plantillas."],
			"RIND_RESERVED" : ["private", "cadena reservada para la marca de palabras reservadas."],
			"RIND_DC1" : ["private", "cadena reservada para el dígito control 1."],
			"RIND_DC2" : ["private", "cadena reservada para el dígito control 2."],
			"RIND_DC3" : ["private", "cadena reservada para el dígito control 3."],
			"RIND_LC_BRACKET" : ["private", "cadena reservada para la llave de apertura { ."],
			"RIND_RC_BRACKET" : ["private", "cadena reservada para la llave de cierre } ."],
			"RIND_FUN_OPEN" : ["private", "cadena reservada para la apertura de función."],
			"RIND_FUN_CLOSE" : ["private", "cadena reservada para el cierre de función."],
			"RIND_VAR_OPEN" : ["private", "cadena reservada para la apertura de variable."],
			"RIND_VAR_CLOSE" : ["private", "cadena reservada para el cierre de variable."],
			"RIND_PHP_OPEN" : ["private", "cadena reservada para la apertura PHP."],
			"RIND_PHP_CLOSE" : ["private", "cadena reservada para el cierre PHP."],
			"RIND_HDV_OPEN" : ["private", "cadena reservada para la apertura de variable en JSON."],
			"RIND_HDV_CLOSE" : ["private", "cadena reservada para el cierre de variable en JSON."],
			"RIND_NOWDOC" : ["private", "cadena reservada para inicio y apertura de NOWDOC."],
			
			"bInitPHPFunctions" : ["private", "indicador de funciones PHP configuradas.", "false"],
			"bInitVarsDenyAllow" : ["private", "indicador de variables PHP configuradas.", "false"],
			"bInitConstants" : ["private", "indicador de constantes PHP configuradas.", "false"],

			"aRindFunctions" : ["private", "nombre de las funciones RIND."],
			"SET" : ["private", "array con las variables de sistema disponibles en las plantillas."],
			"aReservedWords" : ["private", "array palabras reservadas en las plantillas."],
			"vVarsDeny" : ["private", "array de variables reservadas en las plantillas."],
			"vVarsAllow" : ["private", "array de variables permitidas en las plantillas."],
			"aLoops" : ["private", "array contenedor de LOOPS de las plantillas."],
			"aLoopsStack" : ["private", "historial de array contenedor de LOOPS de las plantillas."]
		},
		"arguments" : {
			"root" : ["string", "Path principal de la aplicación.", "getcwd()"],
			"cache" : ["string", "Path del directorio CACHE.", "getcwd()"],
			"gui" : ["string", "Path del repositorio de plantillas.", "getcwd()"],
			"curdir" : ["string", "Path emulado del archivo PHP que llamó a la plantilla, si no se especifica se utilizará el path real.", "null"],
			"scheme" : ["string", "Protocolo <b>http</b> o <b>https</b>.", "http"],

			"template" : ["string", "Nombre del archivo plantilla."],
			"cache_file" : ["string", "Nombre del archivo en el directorio <b>cache</b>."],
			"vars_deny" : ["string", "Variables PHP denegadas en las plantillas.", "NONE"],
			"vars_allow" : ["string", "Variables PHP permitidas en las plantillas.", "ALL"],
			"constants" : ["string", "Listado separado por comas, de constantes PHP permitidas en las plantillas."],
			"php_code" : ["string", "Sentencias PHP que serán incluidas al principio del archivo generado. Este código no será procesado, simplemente será incluído."],
			"php_functions" : ["string", "Listado separado por comas de las funciones PHP permitidas en las plantillas."],
			"loops_limit" : ["int", "Limite para loops numéricos.", "1000"],
			
			"var_needle" : ["string", "Base para el nombre de una variable dinámica."],
			"set_index" : ["string", "Nombre del indice a setear."],
			"set_value" : ["string", "Valor a guardar en <b>set_index</b>."],
			"set_request_index" : ["string", "Nombre del indice de la variable global <b>$_REQUEST</b> utilizado en nglRind::setSET."],

			"cache_mode" : ["string", "
				Estable el modo en el que trabajará el cache
				<ul>
					<li><b>none:</b> se leen todas las plantillas cada vez que son llamadas y procesan en caso de no existir o de haber sufrido cambios</li>
					<li><b>use:</b> se leen y procesan sólo las nuevas, las ya generadas simplemente se invocan</li>
					<li><b>cache:</b> sólo se invocan las ya generadas, no se leen ni procesan nuevas</li>
				</ul>
			", "dev"],
			"fill_urls" : ["boolean", "Activa el autocompletar del fullpath de las url en los <b>href, src y background</b>", "true"],
			"http_support" : ["boolean", "Admite urls en los paths de archivos.", "true"],
			"include_support" : ["boolean", "Habilita el uso de <b>mergefile</b>.", "true"],
			"clear_utf8_bom" : ["boolean", "Elimina la marca BOM de los archivos UTF-8.", "true"]
		}
	} **/
	class nglRind extends nglBranch {

		private $RIND_LOOPS;
		private $RIND_UID;
		private $RIND_ME;
		private $RIND_DOLLAR_SIGN;
		private $RIND_QUOTE;
		private $RIND_HTML_QUOTE;
		private $RIND_RESERVED;
		private $RIND_TEMPLATES;
		private $RIND_TEMPLATESLOG;
		private $RIND_DC1;			/* x11 */
		private $RIND_DC2;			/* x12 */
		private $RIND_DC3;			/* x13 */
		private $RIND_LC_BRACKET;	/* {{ */
		private $RIND_RC_BRACKET;	/* }} */
		private $RIND_FUN_OPEN;		/* <[FUN[ */
		private $RIND_FUN_CLOSE;	/* ]FUN]> */
		private $RIND_VAR_OPEN;		/* <[VAR[ */
		private $RIND_VAR_CLOSE;	/* ]VAR]> */
		private $RIND_PHP_OPEN;		/* <[PHP[ */
		private $RIND_PHP_CLOSE;	/* ]PHP]> */
		private $RIND_HDV_OPEN;		/* <[HDV[ */
		private $RIND_HDV_CLOSE;	/* ]HDV]> */
		private $RIND_NOWDOC;		/* x14 */
		private $EOL = "";

		// indicadores de arranque
		private $bInitPHPFunctions 	= false;
		private $bInitVarsDenyAllow = false;
		private $bInitConstants		= false;
		
		// funciones especiales
		private $aRindFunctions;

		// variable global _SET
		private $SET = [];
		
		// palabras reservadas
		private $aReservedWords;

		// variables denegadas
		private $vVarsDeny;
		private $vVarsAllow;

		private $aLoadedCollections;
		private $aLoops = [];
		private $aLoopsStack = [];
		private $sPHPFile = null;
		private $aFilePath = null;

		private $sMergeFiles = "";
		private $aMergeTail = [];

		final protected function __declareArguments__() {
			$vArguments							= [];
			$vArguments["root"] 				= ['$mValue', null];
			$vArguments["cache"] 				= ['$mValue', "cache"];
			$vArguments["gui"] 					= ['$mValue', null];
			$vArguments["curdir"] 				= ['$mValue', null];
			$vArguments["scheme"] 				= ['$mValue', "http"];
			$vArguments["template"] 			= ['(string)$mValue', null];
			$vArguments["source"] 				= ['(string)$mValue', null];
			$vArguments["cache_file"] 			= ['(string)$mValue', null];
			$vArguments["vars_deny"]			= ['$this->VarsDenyAllow("deny", $mValue)', "NONE"];
			$vArguments["vars_allow"]			= ['$this->VarsDenyAllow("allow", $mValue)', "ALL"];
			$vArguments["constants"]			= ['$this->ConstantsAllowed($mValue)', "NGL_NULL, NGL_STRING_LINEBREAK, NGL_STRING_SPLITTER, NGL_STRING_SPLITTER_NUMBERS"];
			$vArguments["php_code"]				= ['$mValue', null];
			$vArguments["php_functions"]		= ['$this->PHPFunctions($mValue)', "base64_encode, base64_decode, htmlentities, md5, nl2br, sha1, str_replace, strip_tags, strtolower, strtoupper, substr, trim, urlencode, urldecode, abs, ceil, floor, pow, round"];
			$vArguments["loops_limit"]			= ['(int)$mValue', 10000];
			$vArguments["var_needle"]			= ['(string)$mValue', null];
			$vArguments["set_index"]			= ['(string)$mValue', null];
			$vArguments["set_value"]			= ['(string)$mValue', null];
			$vArguments["set_request_index"]	= ['(string)$mValue', null];
			$vArguments["cache_mode"]			= ['(string)($mValue)', "none"];
			$vArguments["fill_urls"]			= ['self::call()->isTrue($mValue)', false];
			$vArguments["http_support"]			= ['self::call()->isTrue($mValue)', true];
			$vArguments["include_support"]		= ['self::call()->isTrue($mValue)', true];
			$vArguments["clear_utf8_bom"]		= ['self::call()->isTrue($mValue)', true];
			$vArguments["trim_stamp"]			= ['self::call()->isTrue($mValue)', true];
			$vArguments["alvin_mode"]			= ['$this->SetAlvinMode($mValue)', "all"];
			
			return $vArguments;
		}

		final protected function __declareAttributes__() {
			$vAttributes 						= [];
			$vAttributes["alvin_type"]			= null;
			$vAttributes["root_url"]			= null;
			$vAttributes["project_path"]		= null;
			$vAttributes["gui_url"]				= null;
			$vAttributes["gui_path"]			= null;
			$vAttributes["relative_path"]		= null;
			$vAttributes["template_url"]		= null;
			$vAttributes["cache_path"]			= null;
			$vAttributes["cache_file"]			= null;
			$vAttributes["word_breaker"]		= null;
			return $vAttributes;
		}

		final protected function __declareVariables__() {
			$this->RIND_LOOPS			= $this->dynVar();
			$this->RIND_QUOTE			= self::call()->unique(6); /* si se cambia el largo revisar EnquoteVars */
			$this->RIND_UID				= self::call()->unique();
			$this->RIND_ME				= $this->RIND_QUOTE.$this->RIND_UID.$this->RIND_QUOTE;
			$this->RIND_DOLLAR_SIGN		= self::call()->unique();
			$this->RIND_HTML_QUOTE		= self::call()->unique();
			$this->RIND_RESERVED		= self::call()->unique();
			$this->RIND_TEMPLATES		= '$'.self::call()->unique(8);
			$this->RIND_TEMPLATESLOG	= [];
			$this->RIND_DC1				= self::call()->unique(); /* x11 */
			$this->RIND_DC2				= self::call()->unique(); /* x12 */
			$this->RIND_DC3		 		= self::call()->unique(); /* x13 */
			$this->RIND_LC_BRACKET		= self::call()->unique(); /* {{ */
			$this->RIND_RC_BRACKET		= self::call()->unique(); /* }} */
			$this->RIND_FUN_OPEN		= self::call()->unique(); /* <[FUN[ */
			$this->RIND_FUN_CLOSE		= self::call()->unique(); /* ]FUN]> */
			$this->RIND_VAR_OPEN		= self::call()->unique(); /* <[VAR[ */
			$this->RIND_VAR_CLOSE		= self::call()->unique(); /* ]VAR]> */
			$this->RIND_PHP_OPEN		= self::call()->unique(); /* <[PHP[ */
			$this->RIND_PHP_CLOSE		= self::call()->unique(); /* ]PHP]> */
			$this->RIND_HDV_OPEN		= self::call()->unique(); /* <[HDV[ */
			$this->RIND_HDV_CLOSE		= self::call()->unique(); /* ]HDV]> */
			$this->RIND_NOWDOC			= self::call()->unique(); /* ]HDV]> */
			
			$sWordBreaker = self::call()->unique();
			$this->attribute("word_breaker", $sWordBreaker);

			// funciones especiales
			$this->aRindFunctions = [
				"alvin", "dump", "eco", "get", "halt", "heredoc", "ifcase", 
				"incfile", "join", "json", "length", "loop", "mergefile", "once", 
				"rtn", "set", "split", "unique", "unset"
			];

			// palabras reservadas
			$this->aReservedWords = [
				"\bbreak\b", "\bcontinue\b", "\bdeclare\b", "\beval\b", "\bexit\b", "\bdie\b",
				"\bfor\b", "\bforeach\b", "\bgoto\b", "\bif\b", "\binclude\b", "\brequire\b",
				"\binclude_once\b", "\bnew\b", "\brequire_once\b", "\breturn\b", "\bsleep\b", 
				"\bswitch\b", "\bwhile\b"
			];

			// variables denegadas
			if(!$this->bInitVarsDenyAllow) {
				$this->VarsDenyAllow("deny", "NONE");
				$this->VarsDenyAllow("allow", "ALL");
			}
			
			// funciones PHP permitidas
			if(!$this->bInitPHPFunctions) {
				$this->PHPFunctions("
					base64_encode, base64_decode, htmlentities, md5, nl2br, sha1, str_replace, strip_tags, 
					strtolower, strtoupper, substr, trim, urlencode, urldecode, abs, ceil, floor, pow, round
				");
			}
			
			// constantes
			if(!$this->bInitConstants) {
				$this->ConstantsAllowed("NGL_NULL, NGL_STRING_LINEBREAK, NGL_STRING_SPLITTER, NGL_STRING_SPLITTER_NUMBERS");
			}

			// variables de sistema
			$aSysVars = self::call("sysvar")->__get();
			foreach($aSysVars as $sName => $mValue) {
				$this->setSET($sName, $mValue);
			}
			
			// variable SET
			$this->aLoops = ["self"=>$this->dynVar(), "parent"=>$this->dynVar()];
			// $this->aLoops = array("self"=>"\$self", "parent"=>"\$parent");
		}
		
		final public function __init__() {
			// includes
			if(isset($this->CONFIG["includes"])) {
				if(!empty($this->CONFIG["includes"]["use"])) {
					$aIncludes = [];
					foreach($this->CONFIG["includes"] as $sKey => $sFilePath) {
						$aIncludes[$sKey] = self::call()->sandboxPath($sFilePath);
					}
					$this->setSET("INCLUDES", $aIncludes);
				}
			}
			
			// variables
			if(isset($this->CONFIG["variables"]) && \count($this->CONFIG["variables"])) {
				foreach($this->CONFIG["variables"] as $sVarname => $mValue) {
					$this->setSET($sVarname, $mValue);
				}
			}
			
			// variables request
			if(isset($this->CONFIG["request"]) && \count($this->CONFIG["request"])) {
				foreach($this->CONFIG["request"] as $sVarname => $mValue) {
					$this->setSET($sVarname, $mValue, $sVarname);
				}
			}
		}

		protected function SetAlvinMode($sMode) {
			$aConstants = [];
			if(\strtolower($sMode)!="none" && \strtolower($sMode)!="all") {
				$sMode = self::call()->explodeTrim(",", $sMode);
				$this->attribute("alvin_type", $sMode);
				$sMode = \implode(",", $sMode);
			} else {
				$this->attribute("alvin_type", \strtolower($sMode));
			}

			return $sMode;
		}

		/** FUNCTION {
			"name" : "ClearCode", 
			"type" : "private",
			"description" : "
				Realiza limpieza del código generado por la clase antes de devolverlo como plantilla.
				<ul>
					<li>variables $_SET</li>
					<li>reemplazo de RIND_HTML_QUOTE por comillas doble</li>
					<li>reemplazo de RIND_QUOTE por comillas doble</li>
					<li>limpieza de doble comillas concatenadas</li>
					<li>restitucion de caracteres RIND</li>
					<li>palabras reservadas, variables y funciones denegadas</li>
					<li>RIND_DOLLAR_SIGN por $</li>
				</ul>
			",
			"parameters" : {
				"$sSource" : ["string", "Código fuente"]
			},
			"return" : "string"
		} **/
		private function ClearCode(&$sSource) {
			// reemplazo de la variable $_GLOBALS["_SET"] por $RindObject->SET
			$sSource = \str_replace(
				["\$GLOBALS[".$this->RIND_QUOTE."_SET".$this->RIND_QUOTE."]", "\$GLOBALS['_SET']"],
				'Rind::this('.$this->RIND_ME.')->SET',
				$sSource
			);

			// reemplazo del hash $this->RIND_HTML_QUOTE por comillas doble
			$sSource = \str_replace([$this->RIND_HTML_QUOTE.'""', '""'.$this->RIND_HTML_QUOTE], "\x22\x22", $sSource);

			// reemplazo del hash $this->RIND_QUOTE por comillas doble
			$sSource = \str_replace($this->RIND_QUOTE, "\x22", $sSource);

			// limpieza de doble comillas concatenadas
			$sSource = \str_replace(["\x22\x22.", ".\x22\x22"], "", $sSource);

			// restitucion de caracteres RIND
			$sSource = $this->TagConverter($sSource, true);

			// restitucion de palabras reservadas, variables y funciones denegadas fuera del codigo PHP
			\preg_match_all("/<\?(php|php3)(.*?)\?>/is", $sSource, $aPHPCode);
			
			$x = 0;
			$aMD5 = [];
			foreach($aPHPCode[0] as $sCode) {
				$sUnique = \microtime();
				$aMD5[] = $x.\md5($sUnique);
				$x++;
			}

			$sSource = \str_replace($aPHPCode[0], $aMD5, $sSource);
			$sSource = \str_replace(["false/*".$this->RIND_RESERVED,"/*".$this->RIND_RESERVED,$this->RIND_RESERVED."*/array",$this->RIND_RESERVED."*/"], "", $sSource);
			$sSource = \str_replace($this->RIND_DOLLAR_SIGN, "\$", $sSource);
			$sSource = \preg_replace("/(\x7b)(\x7b[a-z0-9_\$@#\*%:\.]+\x7d)(\x7d)/i", "\\2", $sSource); // llaves dobles
			$sSource = \str_replace($aMD5, $aPHPCode[0], $sSource);
			
			// correccion de sintaxis JSON como argumento de metodos
			$sSource = \preg_replace("/(\"|\')[\s]*<<<RINDJSON\n/is", "<<<RINDJSON\n", $sSource);
			$sSource = \preg_replace("/RINDJSON[\s]*(\"|\')/is", "RINDJSON\n", $sSource);

			// marcas nowdoc;
			$sSource = \str_replace("\x14", "", $sSource);
		}

		/** FUNCTION {
			"name" : "ClearHyphenArguments", 
			"type" : "private",
			"description" : "Elimina de un array todos valores cuyo indice contenga un guión medio.",
			"parameters" : { "$aArguments" : ["array", "Array de valores."] },
			"return" : "array"
		} **/
		private function ClearHyphenArguments(&$aArguments) {
			foreach($aArguments as $sIndex => $mValue) {
				if(\strpos($sIndex,"-")) { unset($aArguments[$sIndex]); }
			}
		}

		/** FUNCTION {
			"name" : "CommentReservedConstants", 
			"type" : "private",
			"description" : "Auxiliar de las <b>nglRind::ReservedWords</b>, comenta las constantes reservadas en las plantillas.",
			"parameters" : { "$aMatchs" : ["array", "Constantes detectas en las plantillas."] },
			"seealso" : ["nglRind::ReservedWords"],
			"return" : "array"
		} **/
		private function CommentReservedConstants($aMatchs) {
			return "false/*".$this->RIND_RESERVED.$aMatchs[0].$this->RIND_RESERVED."*/";
		}

		/** FUNCTION {
			"name" : "CommentReservedFunctions", 
			"type" : "private",
			"description" : "Auxiliar de las <b>nglRind::ReservedWords</b>, comenta las funciones no permitidas.",
			"parameters" : { "$aMatchs" : ["array", "Funciones de no permitidas detectas en las plantillas."] },
			"seealso" : ["nglRind::ReservedWords"],
			"return" : "array"
		} **/
		private function CommentReservedFunctions($aMatchs) {
			if(\function_exists($aMatchs[1])) {
				return "/*".$this->RIND_RESERVED.$aMatchs[1].$aMatchs[2].$this->RIND_RESERVED."*/array(";
			} else {
				return $aMatchs[0];
			}
		}

		/** FUNCTION {
			"name" : "CommentReservedWords", 
			"type" : "private",
			"description" : "Auxiliar de las <b>nglRind::ReservedWords</b>, comenta las palabras reservadas en las plantillas.",
			"parameters" : { "$aMatchs" : ["array", "Palabras reservadas detectas en las plantillas."] },
			"seealso" : ["nglRind::ReservedWords"],
			"return" : "array"
		} **/
		private function CommentReservedWords($aMatchs) {
			return "/*".$this->RIND_RESERVED.$aMatchs[0].$this->RIND_RESERVED."*/";
		}

		/** FUNCTION {
			"name" : "ProcessConstants", 
			"type" : "private",
			"description" : "Retorna el código con las llamadas a las constantes citadas en <b>$_SET["CONSTANTS"]</b>.",
			"parameters" : { "$sCode" : ["string", "Código a analizar."] },
			"return" : "string"
		} **/
		private function ProcessConstants(&$sCode) {
			$sCode = \preg_replace_callback("/\{@([a-z_][a-z0-9_]*)\}/is", [$this, "ReplaceConstants"], $sCode);
		}

		/** FUNCTION {
			"name" : "ConstantsAllowed", 
			"type" : "protected",
			"description" : "Parsea la cadena <b>$sConstantsAllowed</b> y setea las constantes PHP permitidas en las plantillas en la variable <b>$_SET["CONSTANTS"]</b>.",
			"parameters" : { "$sAllowedPHPFunctions" : ["string", "Listado de constantes separado por comas."] },
			"return" : "void"
		} **/
		protected function ConstantsAllowed($sConstantsAllowed) {
			$aConstants = [];
			if(!empty($sConstantsAllowed)) {
				$aConstants = self::call("shift")->csvToArray($sConstantsAllowed);
				$aConstants = self::call()->truelize($aConstants[0]);
			}

			$aConstants["SID"] = true;
			$this->setSET("CONSTANTS", $aConstants);
			$this->bInitConstants = true;
			return \implode(",", \array_keys($aConstants));
		}

		/** FUNCTION {
			"name" : "dynVar", 
			"type" : "public",
			"description" : "Genera un nombre de variable aleatorio o basado en una semilla de 8 caracteres de longuitud.",
			"parameters" : { "$sNeedle" : ["string", "Si es distinto de null se utilizara como semilla para generar el nombre de la variable.", "argument::var_needle"] },
			"input" : "var_needle",
			"return" : "string"
		} **/
		public function dynVar() {
			list($sNeedle) = $this->getarguments("var_needle", \func_get_args());
			if(!$sNeedle) {
				$sNeedle = $this->varName(8);
			} else {
				$sNeedle = \sha1($sNeedle);
				$sNeedle = \strrev($sNeedle);
				$sNeedle = \md5($sNeedle);
				$sNeedle = "Ox".\substr($sNeedle,0,6);
			}

			return "\$".$sNeedle;
		}

		/** FUNCTION {
			"name" : "FillURL", 
			"type" : "private",
			"description" : "Reemplaza las URLs relativas por absolutas en el código de las plantilas.",
			"parameters" : { "$sSource" : ["string", "Código a fuente."] },
			"return" : "string"
		} **/
		private function FillURL($sSource) {
			$sURLSelf = $this->attribute("gui_url")."/";
			$sTemplateURL = $this->attribute("template_url")."/";

			\preg_match_all("/(<link)(.*?)(href\s*=\s*)(".$this->RIND_HTML_QUOTE."|')?(.*?)(".$this->RIND_HTML_QUOTE."|'| )(.*?)(>)/i", $sSource, $aMatchs);
			$this->FillURLParser($sSource, $aMatchs, $sURLSelf, $sTemplateURL);

			\preg_match_all("/([^\.]src|background)(\s*=\s*)(".$this->RIND_HTML_QUOTE."|')?(.*?)(".$this->RIND_HTML_QUOTE."|'| )/i", $sSource, $aMatchs);
			$this->FillURLParser($sSource, $aMatchs, $sURLSelf, $sTemplateURL);

			return $sSource;
		}

		/** FUNCTION {
			"name" : "FillURLParser", 
			"type" : "private",
			"description" : "Auxiliar del método <b>nglRind::FillURL</b> encargado de efectuar los reemplazos.",
			"parameters" : {
				"$sSource" : ["string", "Código a fuente."],
				"$aURLs" : ["array", "Array con las urls detectadas en el código original."],
				"$sURLSelf" : ["string", "URL del directorio del entorno gráfico."],
				"$sTemplateURL" : ["string", "URL de la plantilla actual."]
			},
			"seealso" : ["nglRind::FillURL"],
			"return" : "string"
		} **/
		private function FillURLParser(&$sSource, $aURLs, $sURLSelf, $sTemplateURL) {
			$nMatchs = \count($aURLs[0]);
			if($nMatchs) {
				for($x=0; $x<$nMatchs; $x++) {
					$aMatchs[$x] = $aURLs[0][$x];

					$sProtocol	= \parse_url($aURLs[4][$x], PHP_URL_SCHEME);
					$sProtocol	= \strtolower($sProtocol);
					$sProtocol2	= \parse_url($aURLs[5][$x], PHP_URL_SCHEME);
					$sProtocol2	= \strtolower($sProtocol2);
					
					$sSubURL0a11 = \strtolower(\substr($aURLs[4][$x],0,11));
					if(\strtolower($aURLs[1][$x])=="href") {
						if(!empty($aURLs[4][$x])) {
							if($sProtocol!="http" && $sProtocol!="https" && $aURLs[4][$x][0].$aURLs[4][$x][1]!="//" && $aURLs[4][$x][0].$aURLs[4][$x][1]!="<?") {
								$aReplaces[$x] = $aURLs[1][$x].$aURLs[2][$x].$aURLs[3][$x].$sURLSelf.$aURLs[4][$x].$aURLs[5][$x];
							} else {
								$aReplaces[$x] = $aURLs[0][$x];
							}
						}
					} elseif(\strtolower($aURLs[1][$x])=="<link")  {
						if(!empty($aURLs[5][$x])) {
							if($sProtocol2!="http" && $sProtocol2!="https" && $aURLs[5][$x][0].$aURLs[5][$x][1]!="//" && $aURLs[5][$x][0].$aURLs[5][$x][1]!="<?") {
								$aReplaces[$x] = $aURLs[1][$x].$aURLs[2][$x].$aURLs[3][$x].$aURLs[4][$x].$sTemplateURL.$aURLs[5][$x].$aURLs[6][$x].$aURLs[7][$x].$aURLs[8][$x];
							} else {
								$aReplaces[$x] = $aURLs[0][$x];
							}
						}
					} else {
						if($sProtocol!="http" && $sProtocol!="https" && $sProtocol!="data" && isset($aURLs[4][$x][0]) && $aURLs[4][$x][0].$aURLs[4][$x][1]!="//" && $aURLs[4][$x][0].$aURLs[4][$x][1]!="<?") {
							$aReplaces[$x] = $aURLs[1][$x].$aURLs[2][$x].$aURLs[3][$x].$sTemplateURL.$aURLs[4][$x].$aURLs[5][$x];
						} else {
							$aReplaces[$x] = $aURLs[0][$x];
						}
					}
				}
				if(isset($aMatchs, $aReplaces)) {
					$sSource = \str_replace($aMatchs, $aReplaces ,$sSource);
				}
			}
		}

		/** FUNCTION {
			"name" : "FixCode", 
			"type" : "private",
			"description" : "Analiza el código HTML/NGL y genera un código de etiquetas previo al código PHP final.",
			"parameters" : {
				"$aCode" : ["array", "Código a analizar."],
				"$sType" : ["string", "Tipo de etiqueta.", "FUN"]
			},
			"return" : "array"
		} **/
		private function FixCode($aCode, $sType="FUN") {
			$nOpened = 0;
			$bOpened = false;
			$aCleanCode = [];

			$bQuote = false;	/* comilla doble */
			$bNDoc	= false;
			$bPHP	= false;
			
			$vStringType["FUN"] = [];
			$vStringType["VAR"] = [];
			$vStringType["PHP"] = [];

			\array_push($aCode, ".", ".", ".", ".", ".", ".");
			$nCode = \count($aCode) - 6;
			for($x=0; $x<$nCode; $x++) {
				$sChar = $aCode[$x];
				$nOrd = self::call("unicode")->ord($sChar);
				$sSpcTag = $aCode[$x].$aCode[$x+1].$aCode[$x+2].$aCode[$x+3].$aCode[$x+4].$aCode[$x+5];
				
				if($nOrd==20 || ($nOrd==34 && ($aCode[$x-1]!="\x5C" || ($aCode[$x-1].$aCode[$x-2])=="\x5C\x5C"))) {
					if($nOrd==20) {
						if(!$bNDoc) {
							$bNDoc = true;
							$bQuote = true;
						} else {
							$bNDoc = false;
							$bQuote = false;
						}
					} else if(!$bNDoc) {
						$bQuote = ($bQuote) ? false : true;
					}
				}

				if(!$bQuote) {
					// fuera de una cadena
					if($sSpcTag=="<[FUN[") {
						if($bPHP) {
							$vStringType["FUN"][] = [".\x22\x22", null, null];
							$sChar = "\x22\x22.";
						} else {
							$vStringType["FUN"][] = [";?>", null, false];
							$sChar = "<?php echo ";
							$bPHP = true;
						}
						$x += 5;
					} else if($sSpcTag=="<[VAR[") {
						if($bPHP) {
							$vStringType["VAR"][] = [".\x22\x22", null, null];
							$sChar = "\x22\x22.";
						} else {
							$vStringType["VAR"][] = [";?>", null, false];
							$sChar = "<?php echo ";
							$bPHP = true;
						}
						$x += 5;
					} else if($sSpcTag=="<[PHP[") {
						if($bPHP) {
							$vStringType["PHP"][] = [".\x22\x22", null, null];
							$sChar = "\x22\x22.";
						} else {
							$vStringType["PHP"][] = ["?>", null, false];
							$sChar = "<?php ";
							$bPHP = true;
						}
						$x += 5;
					}
				} else {
					// en cadenas
					if($sSpcTag=="<[FUN[") {
						if($bPHP) {
							if($aCode[$x-2].$aCode[$x-1]!="\x22.") {
								$vStringType["FUN"][] = [".\x22", true, null];
								$sChar = "\x22.";
								$bQuote = false;
							} else {
								$vStringType["FUN"][] = ["", null, null];
								$sChar = "";
							}
						} else {
							$vStringType["FUN"][] = [";?>", true, false];
							$sChar = "<?php echo ";					
							$bQuote = false;
							$bPHP = true;
						}
						$x += 5;
					} else if($sSpcTag=="<[VAR[") {
						if($bPHP) {
							if($aCode[$x-1]=="[") {
								$vStringType["VAR"][] = ["?", null, null];
								$sChar = "";
								$bQuote = false;
							} else if($aCode[$x-2].$aCode[$x-1]!="\x22.") {
								$vStringType["VAR"][] = [".\x22", true, null];
								$sChar = "\x22.";
								$bQuote = false;
							} else {
								$vStringType["VAR"][] = ["", null, null];
								$sChar = "";
							}
						} else {
							$vStringType["VAR"][] = [";?>", null, false];
							$sChar = "<?php echo ";
							$bPHP = true;
						}
						$x += 5;
					} else if($sSpcTag=="<[PHP[") {
						$vStringType["PHP"][] = [" echo \x22", true, null];
						$sChar = "\x22; ";
						$bQuote = false;
						$x += 5;
					}
				}

				// cierres
				if($sSpcTag=="]FUN]>") {
					$aClose = \array_pop($vStringType["FUN"]);
				} else if($sSpcTag=="]VAR]>") {
					$aClose = \array_pop($vStringType["VAR"]);
				} else if($sSpcTag=="]PHP]>") {
					$aClose = \array_pop($vStringType["PHP"]);
				}

				if(!empty($aClose)) {
					if($aClose[0]=="?") {
						$sChar = ($aCode[$x]!="]") ? ".\x22" : "";
					} else {
						$sChar = $aClose[0];
					}

					if($aClose[1]!==null) { $bQuote = $aClose[1]; }
					if($aClose[2]!==null) { $bPHP = $aClose[2]; }
					unset($aClose);
					$x += 5;	
				}

				$aCleanCode[] = $sChar;
			}
		
			unset($aCode, $vStringType);
			$sCleanCode = \implode($aCleanCode);
			$sCleanCode = \str_replace(["<[HDV[", "]HDV]>"], "", $sCleanCode);

			return $sCleanCode;
		}

		/** FUNCTION {
			"name" : "flushCache", 
			"type" : "public",
			"description" : "Elimina todas las carpetas y archivos del cache",
			"return" : "array"
		} **/
		public function flushCache() {
			list($sCacheFile) = $this->getarguments("cache_file", \func_get_args());
			$this->SetPaths();
			$sCachePath = ($sCacheFile===true) ? $this->attribute("cache_path") : $this->attribute("cache_path").NGL_DIR_SLASH.$sCacheFile;
			$sCachePath = self::call()->clearPath($sCachePath, false, NGL_DIR_SLASH, true);
			if(\is_dir($sCachePath)) {
				return self::call("files")->unlinkr($sCachePath.NGL_DIR_SLASH);
			} else if(\is_file($sCachePath)) {
				return \unlink($sCachePath);
			}
		}

		public function buildcache() {
			$this->args(["curdir"=>$this->argument("gui"), "cache_mode"=>"cache"]);
			$this->SetPaths();
			$this->flushCache(true);

			$sProjectPath = $this->attribute("project_path");
			$sGuiPath = $this->attribute("gui_path");
			$sCachePath = $this->attribute("cache_path");
			$aTree = self::call("files")->ls($sGuiPath, "*.html", "single", true);
			foreach($aTree as $sFilePath) {
				$this->RIND_TEMPLATESLOG	= [];
				$this->aLoadedCollections	= [];
				$this->aLoops				= ["self"=>$this->dynVar(), "parent"=>$this->dynVar()];
				$this->sPHPFile				= null;
				$this->sMergeFiles			= "";
				$this->aMergeTail			= [];
				$this->aFilePath			= \pathinfo($sFilePath);

				$sFolder					= \str_replace($sGuiPath, "", $this->aFilePath["dirname"]);
				$sCacheDir					= self::call()->clearPath($sCachePath.NGL_DIR_SLASH.$sFolder);
				$sCacheFile					= \str_replace($sGuiPath, $sCachePath, $sFilePath);
				$this->attribute("cache_file", $sCacheFile);
				$sSource = $this->readTemplate($sFilePath);
				$sFileHash = \md5($sSource);
	
				\Rind::$Rinds[$this->RIND_UID] = $this;

				// directorio de destino
				if(!\is_dir($sCacheDir)) {
					$aFolders = \explode(NGL_DIR_SLASH, $sFolder);
					$sFolders = "";
					foreach($aFolders as $sDir) {
						if($sDir!="") {
							$sFolders .= $sDir.NGL_DIR_SLASH;
							if(!@\is_dir($sCachePath.NGL_DIR_SLASH.$sFolders)) {
								@\mkdir($sCachePath.NGL_DIR_SLASH.$sFolders);
								@\chmod($sCachePath.NGL_DIR_SLASH.$sFolders, NGL_CHMOD_FOLDER);
							}
						}
					}
				}

				$sSource		= $this->rind2php($sSource);
				$sSourceCode 	= "<?php /*rind-".$sFileHash."-".$this->RIND_UID."-cache-".\date("YmdHis")."*/ ?>\n";
				$sSourceCode 	.= "<?php ".$this->RIND_TEMPLATES."=[];\n ".$this->sMergeFiles." ?>\n";
				$sSourceCode 	.= $sSource;
				$sSource 		= null;

				self::call("file.".$this->RIND_UID)->load($sCacheFile);
				self::call("file.".$this->RIND_UID)->context(["http"=>["method"=>"GET","header"=>"Content-Type: text/xml; charset=".NGL_CHARSET]]);
				if(!self::call("file.".$this->RIND_UID)->write($sSourceCode)) {
					self::errorMessage($this->object, 1006, $sCacheFile);
				} else {
					@\chmod($sCacheFile, NGL_CHMOD_FILE);
				}

				$sSourceCode = null;
			}

			return $aTree;
		}

		/** FUNCTION {
			"name" : "GetCommand", 
			"type" : "private",
			"description" : "
				Captura los comandos <b>rind</b>:
				<ul>
					<li><b>cmd_ini:</b> posición de inicio del comando en el código fuente.</li>
					<li><b>cmd_end:</b> posición de fin del comando en el código fuente. </li>
					<li><b>function:</b> nombre del comando</li>
					<li><b>content:</b> contenido del argumento <strong>content</strong></li>
					<li><b>source:</b> código fuente</li>
					<li><b>arguments:</b> array de argumentos</li>
				</ul>
			",
			"return" : "array"
		} **/
		private function GetCommand($aCode, $nFrom) {
			// echo implode($aCode)."\n\n------------------------------------------------------------------\n\n";
			$vFunction = $this->TagReader($aCode, $nFrom, ">");
			$sFunction = self::call("unicode")->substr($vFunction["string"], 1);
			$nFunction = self::call("unicode")->strlen($sFunction);
			
			$sFunctionClose	= "\x12\x11".$sFunction;

			$nArgIni = $vFunction["char"];
			//$vArguments["string"] = "";
			$vContent = $this->TagReader($aCode, $nArgIni, $sFunctionClose, $vFunction["string"]);

			$vCommand					= [];
			$vCommand["cmd_ini"]		= $nFrom;
			$vCommand["cmd_end"]		= $vContent["char"];
			$vCommand["function"]		= $sFunction;
			$vCommand["content"]		= \implode(\array_slice($aCode, $nFrom+($nFunction+2), $vContent["char"]-($nFrom+4+$nFunction*2)));
			$vCommand["source"]			= \implode(\array_slice($aCode, $nFrom, $vContent["char"]-$nFrom+1));

			if(!\count($vContent["arguments"])) { $vContent["arguments"]["content"] = $vCommand["content"]; }
			$vCommand["arguments"] = $vContent["arguments"];

			$vArguments = [];
			foreach($vCommand["arguments"] as $sKey => $mValue) {
				$vArguments[\strtolower($sKey)] = $this->VarsEscape($mValue);
			}
			$vCommand["arguments"] = $vArguments;

			return $vCommand;
		}

		/** FUNCTION {
			"name" : "getSET", 
			"type" : "public",
			"description" : "
				Retorna el valor de un índice de la variable <b>$_SET</b>.
				Cuando el valor sea NULL, se retornarán todo el array <b>$_SET</b>.
			",
			"parameters" : { "$sIndex" : ["string", "Nombre del indice a retornar."] },
			"return" : "mixed"
		} **/
		public function getSET() {
			list($sIndex) = $this->getarguments("set_index", \func_get_args());
			return ($sIndex==null) ? $this->SET : $this->SET[$sIndex];
		}

		/** FUNCTION {
			"name" : "getRINDVariable", 
			"type" : "public",
			"description" : "Retorna el valor hash asignado a cada una de las variables RIND. Este método tiene aplicación dentro de las librerías XPS",
			"parameters" : {
				"$sVarName" : ["string", "
					Nombre de variable RIND
					<ul>
						<li><b>RIND_UID:</b> = id único</li>
						<li><b>RIND_ME:</b> = id único entrecomillado</li>
						<li><b>RIND_DOLLAR_SIGN:</b> = signo $ (variables)</li>
						<li><b>RIND_QUOTE:</b> = comillas dentro del pseudo código</li>
						<li><b>RIND_HTML_QUOTE:</b> = comillas dobles utilizadas en las plantillas</li>
						<li><b>RIND_RESERVED:</b> = marca de palabras reservadas</li>
						<li><b>RIND_DC1:</b> = dígito control 1</li>
						<li><b>RIND_DC2:</b> = dígito control 2</li>
						<li><b>RIND_DC3:</b> = dígito control 3</li>
						<li><b>RIND_LC_BRACKET:</b> = llave de apertura { </li>
						<li><b>RIND_RC_BRACKET:</b> = llave de cierre } </li>
						<li><b>RIND_FUN_OPEN:</b> = apertura de función</li>
						<li><b>RIND_FUN_CLOSE:</b> = cierre de función</li>
						<li><b>RIND_VAR_OPEN:</b> = apertura de variable</li>
						<li><b>RIND_VAR_CLOSE:</b> = cierre de variable</li>
						<li><b>RIND_PHP_OPEN:</b> = apertura PHP</li>
						<li><b>RIND_PHP_CLOSE:</b> = cierre PHP</li>
						<li><b>RIND_HDV_OPEN:</b> = apertura de variable en JSON</li>
						<li><b>RIND_HDV_CLOSE:</b> = cierre de variable en JSON</li>
						<li><b>RIND_NOWDOC:</b> = apertura/cierre de cadena NOWDOC</li>
					</ul>
				", "null"]
			},
			"return" : "mixed"
		} **/
		public function getRINDVariable($sVarName=null) {
			switch(\strtoupper($sVarName)) {
				case "RIND_UID": return $this->RIND_UID; break;
				case "RIND_ME": return $this->RIND_ME; break;
				case "RIND_DOLLAR_SIGN": return $this->RIND_DOLLAR_SIGN; break;
				case "RIND_QUOTE": return $this->RIND_QUOTE; break;
				case "RIND_HTML_QUOTE": return $this->RIND_HTML_QUOTE; break;
				case "RIND_RESERVED": return $this->RIND_RESERVED; break;
				case "RIND_DC1": return $this->RIND_DC1; break;
				case "RIND_DC2": return $this->RIND_DC2; break;
				case "RIND_DC3": return $this->RIND_DC3; break;
				case "RIND_LC_BRACKET": return $this->RIND_LC_BRACKET; break;
				case "RIND_RC_BRACKET": return $this->RIND_RC_BRACKET; break;
				case "RIND_FUN_OPEN": return $this->RIND_FUN_OPEN; break;
				case "RIND_FUN_CLOSE": return $this->RIND_FUN_CLOSE; break;
				case "RIND_VAR_OPEN": return $this->RIND_VAR_OPEN; break;
				case "RIND_VAR_CLOSE": return $this->RIND_VAR_CLOSE; break;
				case "RIND_PHP_OPEN": return $this->RIND_PHP_OPEN; break;
				case "RIND_PHP_CLOSE": return $this->RIND_PHP_CLOSE; break;
				case "RIND_HDV_OPEN": return $this->RIND_HDV_OPEN; break;
				case "RIND_HDV_CLOSE": return $this->RIND_HDV_CLOSE; break;
				case "RIND_NOWDOC": return $this->RIND_NOWDOC; break;
			}
			
			return null;
		}

		/** FUNCTION {
			"name" : "IfcaseInline", 
			"type" : "private",
			"description" : "Auxiliar del método <b>nglRind::rindIfcase</b> para los casos de if inline.",
			"parameters" : { "$sString" : ["string", "Código del IF."] },
			"seealso" : ["nglRind::rindIfcase"],
			"return" : "string"
		} **/
		private function IfcaseInline($sString) {
			$aReturn = [];
			$sString = \ltrim($sString, " \t\n\r\0\x0B");
			if($sString=="") { return "false"; }
			if($sString[0]!="(") {
				$sEmpty = \trim($sString, " \t\n\r\0\x0B");
				$sEmpty = "\x12heredoc>".$sEmpty."\x12\x11heredoc>";
				$aReturn[] = "!empty(".$sEmpty.")";
				$aReturn[] = $sString;
				return $aReturn;
			}
			
			$bIsset = false;
			$bLength = false;
			$bEmptyIsFalse = false;
			$bEmptyIsTrue = false;
			if($sString[1]=="?") { $sString[1] = " "; $bIsset = true; }
			if($sString[1]=="#") { $sString[1] = " "; $bLength = true; }
			if($sString[1]=="-") { $sString[1] = " "; $bEmptyIsFalse = true; }
			if($sString[1]=="+") { $sString[1] = " "; $bEmptyIsTrue = true; }

			$aCode 			= self::call("unicode")->split($sString);
			$nCode 			= \count($aCode);
			$bSave 			= false;
			$bQuotes		= false;
			$bCondition		= false;
			$nOpenCondition	= 0;
			$sCondition		= "";
			$sStatements	= null;

			for($x=0; $x<$nCode; $x++) {
				if(!$bCondition) {
					// 40 = (
					// 41 = )
					$nChar = \ord($aCode[$x]);
					
					if(isset($aCode[$x+5])) {
						$sQuote = $aCode[$x].$aCode[$x+1].$aCode[$x+2].$aCode[$x+3].$aCode[$x+4].$aCode[$x+5];
						if($sQuote==$this->RIND_HTML_QUOTE) {
							$bQuotes = (!$bQuotes) ? true : false;
						}
					}
					
					if($nChar==40 && !$bQuotes) { $nOpenCondition++; }

					if(!$bSave) {
						if($nChar==40) {
							$bSave = true;
							continue;
						}
					} else {
						if($nChar==41 && !$bQuotes) {
							if(--$nOpenCondition==0) {
								$bCondition = true;
								continue;
							}
						}
						$sCondition .= $aCode[$x];
					}
				} else {
					$sStatements .= $aCode[$x];
				}
			}

			$sCondition = \trim($sCondition, " \t\n\r\0\x0B");
			if($sStatements===null) { $sStatements = $sCondition; }

			if($bIsset) {
				$aReturn[] = $this->IssetArgument($sCondition);
			} else if($bLength) {
				$aReturn[] = $this->rindLength(["content"=>$sCondition]);
			} else if($bEmptyIsFalse) {
				$aReturn[] = "<[FUN[Rind::ifempty(".$this->RIND_QUOTE.$sCondition.$this->RIND_QUOTE.", 'false')]FUN]>";
			} else if($bEmptyIsTrue) {
				$aReturn[] = "<[FUN[Rind::ifempty(".$this->RIND_QUOTE.$sCondition.$this->RIND_QUOTE.", 'true')]FUN]>";
			} else {
				$aReturn[] = $sCondition;
			}

			$aReturn[] = $sStatements;
			return $aReturn;
		}

		/** FUNCTION {
			"name" : "InNotInArgument", 
			"type" : "private",
			"description" : "Auxiliar del método <b>nglRind::rindIfcase</b>, retorna el código que verifica la existencia de un argumento.",
			"parameters" : { "$sIssetArgument" : ["string", "Código a evaluar."] },
			"seealso" : ["nglRind::rindIfcase"],
			"return" : "string"
		} **/
		private function InNotInArgument($aHayStack, $sSearch, $sBreaker) {
			$aHayStack	= $this->PutSlashes($aHayStack);
			$sSearch	= $this->PutSlashes($sSearch);

			$sNeedle	= $this->dynVar();
			$sHaystack	= $this->dynVar();
			$sDelimiter = $this->dynVar();
			
			$sPreIf  = $sHaystack.' = "'.$aHayStack.'"; '.$sNeedle.' = "'.$sSearch.'"; '.$sDelimiter.' = "'.$sBreaker.'";';
			$sPreIf .= 'if(!\is_array('.$sHaystack.')) { '.$sHaystack.' = \explode('.$sDelimiter.', '.$sHaystack.'); }';

			return [$sPreIf, "\in_array(".$sNeedle.", ".$sHaystack.")"];
		}

		/** FUNCTION {
			"name" : "IssetArgument", 
			"type" : "private",
			"description" : "Auxiliar del método <b>nglRind::rindIfcase</b>, retorna el código que verifica la existencia de un argumento.",
			"parameters" : { "$sIssetArgument" : ["string", "Código a evaluar."] },
			"seealso" : ["nglRind::rindIfcase"],
			"return" : "string"
		} **/
		private function IssetArgument($sIssetArgument) {
			$nOpen = 0;
			$sIsset = "";
			$nIsset = \strlen($sIssetArgument);
			for($x=0;$x<$nIsset;$x++) {
				$sMatch = \substr($sIssetArgument, $x, 6);
				if($sMatch=="<[VAR[") {
					$nOpen++;
					if($nOpen==1) { $sIsset .= "isset("; }
				}
				
				if($sMatch=="]VAR]>") {
					$nOpen--;
					if($nOpen==0) {
						$sIsset .= "]VAR]>)";
						$x += 5;
						continue;
					}
				}

				$sIsset .= $sIssetArgument[$x];
			}

			return $sIsset;
		}

		/** FUNCTION {
			"name" : "IsTemplateFile", 
			"type" : "private",
			"description" : "Chequea si el path de la plantilla es válido.",
			"parameters" : { "$sTemplateFile" : ["string", "URL de la plantilla."] },
			"return" : "boolean"
		} **/
		private function IsTemplateFile($sTemplateFile) {
			$sFilePath = self::call("files")->absPath($sTemplateFile);

			if(\preg_match("/^http(s)?:\/\/(.*)\.(.*)$/", $sTemplateFile)) {
				return true;
			} else if(\is_file($sFilePath)) {
				return true;
			} else {
				return false;
			}
		}

		/** FUNCTION {
			"name" : "LoopVarName", 
			"type" : "private",
			"description" : "Auxiliar del método <b>nglRind::rindLoop</b>, gestiona los nombres de <b>loops</b>.",
			"parameters" : {
				"$sSource" : ["string", "Código a evaluar."],
				"$sLoopName" : ["string", "Parte dinámica del nombre."]
			},
			"seealso" : ["nglRind::rindIfcase"],
			"return" : "string"
		} **/
		private function LoopVarName($sSource, $sLoopName) {
			// atributos del loop
			$aAttribs				= [];
			$aAttribs["current"]	= true;
			$aAttribs["data"]		= true;
			$aAttribs["first"]		= true;
			$aAttribs["from"]		= true;
			$aAttribs["key"]		= true;
			$aAttribs["numrows"]	= true;
			$aAttribs["last"]		= true;
			$aAttribs["limit"]		= true;
			$aAttribs["line"]		= true;
			$aAttribs["lines"]		= true;
			$aAttribs["numrow"]		= true;
			$aAttribs["odd"]		= true;
			$aAttribs["parity"]		= true;
			$aAttribs["previous"]	= true;
			$aAttribs["sum"]		= true;
			$aAttribs["avg"]		= true;
			$aAttribs["min"]		= true;
			$aAttribs["max"]		= true;

			\preg_match_all("/(\{)(?!".$this->RIND_HTML_QUOTE.")((\(\{)?[a-zA-Z0-9_@:#\.\(\)]+(\}\))?)+(\})/i", $sSource, $aVarsSources, PREG_SET_ORDER);
			if(\is_array($aVarsSources) && \count($aVarsSources) && \is_array($aVarsSources[0]) && \count($aVarsSources[0])) {
				\usort($aVarsSources, function($a, $b) { return (\strlen($a[0]) < \strlen($b[0])); });
				foreach($aVarsSources as $aVarSource) {
					$sLoop = $sLoopName;
					$sVarSource = $sReturn = $aVarSource[0];

					$sVarDotted = \preg_replace_callback("/(\()([a-z0-9\#\.\{\}]+)(\))/", function($aMatchs) {
						return "?".\base64_encode($aMatchs[2]);
					}, $sVarSource);

					$aVarSource = \explode(".", \substr($sVarDotted, 1, -1));
					$sVarName = ($aVarSource[0][0]=="#") ? \substr($aVarSource[0], 1) : $aVarSource[0];

					if(\is_array($aVarSource) && \count($aVarSource)>1 && $aVarSource[0][0]!="?" && $aVarSource[0][0]!="#") {
						if(!isset($this->aLoops[$sVarName])) { $this->aLoops[$sVarName] = $this->dynvar(); }
					}

					if(isset($this->aLoops[$sVarName])) { $sLoop = $this->aLoops[$sVarName]; \array_shift($aVarSource); }
					// if(!isset($aVarSource[0])) { exit($sVarDotted); }
					if($aVarSource[0][0]!="?" && $aVarSource[0][0]!="#" && $aVarSource[0]!="data") { \array_unshift($aVarSource, "data"); }

					foreach($aVarSource as &$sSourcePart) {
						$sPartName = \substr($sSourcePart, 1);
						if($sSourcePart[0]=="?") {
							$sSourcePart = "[".\base64_decode($sPartName)."]";
						} else if($sSourcePart[0]=="#" && isset($aAttribs[$sPartName])) {
							$sSourcePart = "[".$this->RIND_QUOTE.$sPartName.$this->RIND_QUOTE."]";
						} else {
							$sSourcePart = "[".$this->RIND_QUOTE.$sSourcePart.$this->RIND_QUOTE."]";
						}
					}

					$sReturn = "<[VAR[".$sLoop.\implode($aVarSource)."]VAR]>";

					// echo $sLoopName." => ".$sLoop.$this->EOL;
					// echo $sVarSource." => ".$sReturn."\n\n";
					$sSource = \str_replace($sVarSource, $sReturn, $sSource);
				}
			}

			return $sSource;
		}

		/** FUNCTION {
			"name" : "MakeMatch", 
			"type" : "private",
			"description" : "Auxiliar de <b>nglRind::TagReader</b>, genera el código de una variable.",
			"parameters" : {
				"$nLength" : ["int", "Largo del nombre de la variable."],
				"$sBaseName" : ["string", "Nombre base de la variable."],
				"$sCounter" : ["int", "Base del contador de la variable."]
			},
			"seealso" : ["nglRind::TagReader"],
			"return" : "string"
		} **/
		private function MakeMatch($nLength, $sBaseName, $sCounter="\$x") {
			$aMatch[] = $sBaseName."[".$sCounter."]";
			for($x=1;$x<$nLength;$x++) {
				$aMatch[] = $sBaseName."[".$sCounter."+".$x."]";
			}
			
			return ("return ".\implode(".", $aMatch).";");
		}

		/** FUNCTION {
			"name" : "PathBuilder", 
			"type" : "protected",
			"description" : "Construye el path de la plantilla activa en base a los argumentos y atributos cargados.",
			"parameters" : { "$sFileName" : ["string", "Nombre del archivo."] },
			"return" : "string"
		} **/
		protected function PathBuilder($sFileName) {
			$sScheme = \parse_url($sFileName, PHP_URL_SCHEME);
			$sScheme = \strtolower($sScheme);

			if($sFileName[0]==NGL_DIR_SLASH) {
				$sFileName = \substr($sFileName, 1);
				$sTemplatePath = $this->attribute("project_path");
			} else {
				$sTemplatePath = self::call()->clearPath($this->attribute("gui_path").NGL_DIR_SLASH.$this->attribute("relative_path"), true);
			}

			// aborción por HTTP
			if((!\ini_get("allow_url_fopen") || !$this->argument("http_support")) && $sScheme=="http") {
				if(\ini_get("allow_url_fopen")) {
					self::errorMessage($this->object, 1001, $sFileName);
				} else {
					self::errorMessage($this->object, 1002, $sFileName);
				}
			}

			// ruta del archivo a leer
			// die($sTemplatePath." ---- ".$sFileName);
			$sBasePaths = self::call("files")->basePaths($sTemplatePath, $sFileName);
			if($sScheme=="http" || $sScheme=="https" || \strlen($sBasePaths)) {
				$sFilePath = $sFileName;
			} else {
				$sFilePath = $sTemplatePath.NGL_DIR_SLASH.$sFileName;
			}

			return self::call()->clearPath($sFilePath);
		}

		/** FUNCTION {
			"name" : "PHPFunctions", 
			"type" : "protected",
			"description" : "Parsea la cadena <b>$sAllowedPHPFunctions</b> y setea las funciones PHP permitidas en las plantillas en la variable <b>$_SET["PHP_FUNCTIONS"]</b>.",
			"parameters" : { "$sAllowedPHPFunctions" : ["string", "Listado de funciones separado por comas."] },
			"return" : "void"
		} **/
		protected function PHPFunctions($sAllowedPHPFunctions) {
			$aAllowedPHPFunctions = [];
			if(!empty($sAllowedPHPFunctions)) {
				$sAllowedPHPFunctions = \preg_replace("/\s+/is", "", $sAllowedPHPFunctions);
				$aAllowedPHPFunctions = self::call("shift")->csvToArray($sAllowedPHPFunctions);
				$aAllowedPHPFunctions = self::call()->truelize($aAllowedPHPFunctions[0]);
			}

			$this->setSET("PHP_FUNCTIONS", $aAllowedPHPFunctions);
			$this->bInitPHPFunctions = true;
			return \implode(",", \array_keys($aAllowedPHPFunctions));
		}

		/** FUNCTION {
			"name" : "process", 
			"type" : "public",
			"description" : "Procesa una plantilla, cachea el código PHP generado y retorna el path del cache.",
			"parameters" : {
				"$sFileName" : ["string", "Nombre de la plantilla activa.", "argument::template"],
				"$sCacheMode" : ["string", "Estable el modo en el que trabajará el cache.", "argument::cache_mode"]
			},
			"input" : "cache_file,cache_path*,fill_urls,gui_path*,template,cache_mode",
			"seealso" : ["nglRind::stamp"],
			"return" : "string"
		} **/
		public function process() {
			list($sFileName,$sCacheMode) = $this->getarguments("template,cache_mode", \func_get_args());
			$sCacheMode = \strtolower($sCacheMode);

			$sCacheFile = $this->argument("cache_file");
			if($this->sPHPFile===null) {
				$aBacktrace = \debug_backtrace(false);
				$sPHPFile = $aBacktrace[0]["file"];
			} else {
				$sPHPFile = $this->sPHPFile;
			}
			$vItSelf = \pathinfo($sPHPFile);

			// si no se especifica un nombre de plantilla se intentará leer el archivo HTML
			// con el mismo nombre de archivo PHP, dentro de la carpeta GUI correspondiente
			if(empty($sFileName)) { $sFileName = $vItSelf["filename"].".html"; }
			
			$sFilePath			= $this->PathBuilder($sFileName);
			$this->aFilePath	= \pathinfo($sFilePath);
			$sDirName 			= self::call()->clearPath($this->aFilePath["dirname"]);
			$sGUIPath 			= $this->attribute("gui_path");
			$sBaseDir 			= self::call("files")->basePaths($sGUIPath, $sDirName);
			$sFolder			= \str_replace($sBaseDir, "", $sDirName);
			$sCachePath			= $this->attribute("cache_path");
			$sCacheDir			= self::call()->clearPath($sCachePath.NGL_DIR_SLASH.$sFolder);

			if(\strtolower($sCacheFile)==="self") {
				$sCacheFile	= $vItSelf["basename"];
			} else if(empty($sCacheFile)) {
				$sCacheFile	= ($sFileName[0]==NGL_DIR_SLASH) ? \basename($sPHPFile) : $sFileName;
			}

			$sCacheFile	= $sCacheDir.NGL_DIR_SLASH.$sCacheFile;
			$this->attribute("cache_file", $sCacheFile);

			// chequeo de existencia de cache
			if($sCacheMode!="none" && \file_exists($sCacheFile)) {
				$sign = \fopen($sCacheFile, "r");
				$sSign = \fgets($sign);
				\fclose($sign);
				$aSign = \explode("-", \trim($sSign));
				$this->RIND_UID = $aSign[2];
				$this->RIND_ME = $this->RIND_QUOTE.$this->RIND_UID.$this->RIND_QUOTE;
				\Rind::$Rinds[$aSign[2]] = $this;
				return $sCacheFile;
			} else if($sCacheMode=="none" || ($sCacheMode=="use" && !\file_exists($sCacheFile))) {
				$sSource = $this->readTemplate($sFilePath);
				if($sSource===false) { return false; }
				$sFileHash = \md5($sSource);
			} else {
				return false;
			}

			\Rind::$Rinds[$this->RIND_UID] = $this;

			// directorio de destino
			if(!\is_dir($sCacheDir)) {
				$aFolders = \explode(NGL_DIR_SLASH, $sFolder);
				$sFolders = "";
				foreach($aFolders as $sDir) {
					if($sDir!="") {
						$sFolders .= $sDir.NGL_DIR_SLASH;
						if(!\is_dir($sCachePath.NGL_DIR_SLASH.$sFolders)) {
							@\mkdir($sCachePath.NGL_DIR_SLASH.$sFolders);
							@\chmod($sCachePath.NGL_DIR_SLASH.$sFolders, NGL_CHMOD_FOLDER);
						}
					}
				}
			}

			// convierte el codigo RIND en codigo PHP
			$sSource = $this->rind2php($sSource);
			
			// firma md5
			$sSourceCode  = "<?php /*rind-".$sFileHash."-".$this->RIND_UID."-".$sCacheMode."-".\date("YmdHis")."*/ ?>\n";
			$sSourceCode .= "<?php ".$this->RIND_TEMPLATES."=[];\n ".$this->sMergeFiles." ?>\n";
			$sSourceCode .= $sSource;
			$sSource = null;

			// graba el archivo
			self::call("file.".$this->RIND_UID)->load($sCacheFile);
			self::call("file.".$this->RIND_UID)->context(["http"=>["method"=>"GET","header"=>"Content-Type: text/xml; charset=".NGL_CHARSET]]);
			if(!self::call("file.".$this->RIND_UID)->write($sSourceCode)) {
				self::errorMessage($this->object, 1006, $sCacheFile);
			} else {
				@\chmod($sCacheFile, NGL_CHMOD_FILE);
			}

			$sSourceCode = null;
			return $sCacheFile;
		}

		/** FUNCTION {
			"name" : "quick", 
			"type" : "public",
			"description" : "
				Ejecuta la plantilla $sFileName sobre el archivo actual y retorna el código resultante.
				Este método no genere un archivo en el cache.
				
				IMPORTANTE:
					Al utilizar el método quick dentro de una clase, se debe tener en cuenta que <b>nglRind</b> invoca siempre a las 
					variables atraves de la variable $GLOBALS, por lo que las variables que se pasen a las plantillas deberán setearse 
					con la sintaxis: $GLOBALS["foo"] = "asd123";
			",
			"parameters" : {
				"$sFileName" : ["string", "Ruta de la plantilla. La misma debe ser absoluta o relativa al archivo PHP", "argument::template"],
			},
			"input" : "template",
			"seealso" : ["nglRind::stamp"],
			"return" : "string"
		} **/
		public function quick() {
			list($sFileName) = $this->getarguments("template", \func_get_args());
			\Rind::$Rinds[$this->RIND_UID] = $this;
			$sSource = $this->readTemplate($sFileName);
			$sSource = $this->rind2php($sSource);

			\ob_start();
			eval(self::EvalCode("?>".$sSource));
			return \ob_get_clean();
		}

		public function rind2php($sSource, $bDebug=false) {
			$sSource = $this->TagConverter($sSource);
			// if($bDebug) { die($sSource); }
			// file_put_contents($sCacheDir.NGL_DIR_SLASH."rindstamplog_1_".date("is").".txt", $sSource);

			$aSource = self::call("unicode")->split($sSource);
			$sSource = \implode($aSource);
			$sSource = $this->ReservedStrings($sSource);
			// if($bDebug) { die($sSource); }
			// file_put_contents($sCacheDir.NGL_DIR_SLASH."rindstamplog_2_".date("is").".txt", $sSource);

			// argumento json
			$sSource = \preg_replace(
				"/\\x13([a-z0-9\:\-]+) json>(.*?)\\x13\\x11\\1>/is",
				"\x13\\1>\x12json>\\2\x12\x11json>\x13\x11\\1>",
				$sSource
			);

			// argumento math
			$sSource = \preg_replace(
				"/\\x13([a-z0-9\:\-]+) math>(.*?)\\x13\\x11\\1>/is",
				"\x13\\1>\x12rtn>\\2\x12\x11rtn>\x13\x11\\1>",
				$sSource
			);

			// argumento notags
			$sSource = \preg_replace_callback(
				"/\\x13([a-z0-9\:\-]+) notags>(.*?)\\x13\\x11\\1>/is",
				function($aMatchs) {
					return $this->ArgumentsParser($aMatchs, "notags");
				},
				$sSource
			);

			// argumento quotes
			$sSource = \preg_replace(
				"/\\x13([a-z0-9\:\-]+) quotes>(.*?)\\x13\\x11\\1>/is",
				"\x13\\1>\x12heredoc>\\2\x12\x11heredoc>\x13\x11\\1>",
				$sSource
			);

			// argumento base64
			$sSource = \preg_replace_callback(
				"/\\x13([a-z0-9\:\-]+) base64>(.*?)\\x13\\x11\\1>/is",
				function($aMatchs) {
					return $this->ArgumentsParser($aMatchs, "base64");
				},
				$sSource
			);

			// argumento split
			$sSource = \preg_replace(
				"/\\x13([a-z0-9\:\-]+) split>(.*?)\\x13\\x11\\1>/is",
				"\x13\\1>\x12split>\\2\x12\x11split>\x13\x11\\1>",
				$sSource
			);

			// argumento join
			$sSource = \preg_replace(
				"/\\x13([a-z0-9\:\-]+) join>(.*?)\\x13\\x11\\1>/is",
				"\x13\\1>\x12join>\\2\x12\x11join>\x13\x11\\1>",
				$sSource
			);

			// argumento erroneo
			$sSource = \preg_replace(
				"/\\x13([a-z0-9\:\-]+) [a-z0-9]+>(.*?)\\x13\\x11\\1>/is",
				"\x13\\1>\\2\x13\x11\\1>",
				$sSource
			);

			// if($bDebug) { die($sSource); }
			$aSource = $this->ProcessCode($sSource, $bDebug);
			if($bDebug) { die(\implode($aSource)); }
			// file_put_contents($sCacheDir.NGL_DIR_SLASH."rindstamplog_3_".date("is").".txt", implode($aSource));

			$sSource = $this->FixCode($aSource);
			$aSource = null; 

			// die($sSource);
			// file_put_contents($sCacheDir.NGL_DIR_SLASH."rindstamplog_4_".date("is").".txt", $sSource);

			if($this->argument("fill_urls")) {
				$sSource = $this->FillURL($sSource);
				// file_put_contents($sCacheDir.NGL_DIR_SLASH."rindstamplog_5_".date("is").".txt", $sSource);
			}
			// limpieza del codigo
			$this->ClearCode($sSource);
			// die($sSource);
			// file_put_contents($sCacheDir.NGL_DIR_SLASH."rindstamplog_6_".date("is").".txt", $sSource);

			$sSource = $this->ReservedStrings($sSource, true);
			// die($sSource);
			// file_put_contents($sCacheDir.NGL_DIR_SLASH."rindstamplog_7_".date("is").".txt", $sSource);

			$sPHPCode = $this->argument("php_code");
			if(!empty($sPHPCode)) {
				$sPHPCode = \preg_replace("/^(<\?(php)?[\s]*)(.*?)([\s]*\?>)$/is", "\\3", $sPHPCode);
				$sSource = "<?php ".$sPHPCode."?>".$sSource;
			}
			
			return $sSource;
		}
		
		/*
		private function RevertToRawCode($aMatchs) {
			return "\x13".$aMatchs[1].">".base64_encode($this->ReservedStrings($this->TagConverter($aMatchs[2], true), true))."\x13\x11".$aMatchs[1].">";
		}
		*/

		private function ArgumentsParser($aMatchs, $sFunction) {
			switch($sFunction) {
				case "notags": return "\x13".$aMatchs[1].">\x12php.strip_tags>".$aMatchs[2]."\x12\x11.php.strip_tags>\x13\x11".$aMatchs[1].">";
				case "base64": return "\x13".$aMatchs[1].">".\base64_encode($this->ReservedStrings($this->TagConverter($aMatchs[2], true), true))."\x13\x11".$aMatchs[1].">";
			}
		}

		// transforma un bloque de código RIND a formato PHP durante proceso de la plantilla
		// cuando bEval es TRUE, retorna el resultado de la ejecución del código
		private function InnerRind2php($sSource, $bEval=false) {
			$aSource = $this->ProcessCode($sSource);
			$sSource = $this->FixCode($aSource);
			$aSource = null;

			// die($sSource);
			// file_put_contents($sCacheDir.NGL_DIR_SLASH."rindstamplog_4_".date("is").".txt", $sSource);

			if($this->argument("fill_urls")) {
				$sSource = $this->FillURL($sSource);
				// file_put_contents($sCacheDir.NGL_DIR_SLASH."rindstamplog_5_".date("is").".txt", $sSource);
			}

			// limpieza del codigo
			$this->ClearCode($sSource);
			// die($sSource);
			// file_put_contents($sCacheDir.NGL_DIR_SLASH."rindstamplog_6_".date("is").".txt", $sSource);

			$sSource = $this->ReservedStrings($sSource, true);
			// die($sSource);
			// file_put_contents($sCacheDir.NGL_DIR_SLASH."rindstamplog_7_".date("is").".txt", $sSource);

			$sPHPCode = $this->argument("php_code");
			if(!empty($sPHPCode)) {
				$sPHPCode = \preg_replace("/^(<\?(php)?[\s]*)(.*?)([\s]*\?>)$/is", "\\3", $sPHPCode);
				$sSource = "<?php ".$sPHPCode."?>".$sSource;
			}

			// die($sSource);
			$sQUOTE = $this->RIND_QUOTE;
			$sSource = \preg_replace_callback('/(<\?php echo )(Rind::this('.$this->RIND_ME.')\->SET[a-z0-9\_\-\"\[\]]+)(;\?>)/is',
				function($aMatchs) use ($sQUOTE) { return "{".\str_replace('"', $sQUOTE, $aMatchs[2])."}"; },
				$sSource
			);
			$sSource = "'".\str_replace(["<?php echo ", ";?>"], ["'.",".'"], $sSource)."'";

			// evalua el código
			if($bEval) { return eval(self::call()->EvalCode("return ".$sSource).";"); }

			return $sSource;
		}

		/** FUNCTION {
			"name" : "ProcessCode", 
			"type" : "private",
			"description" : "
				Procesa el código fuente aplicando:
				<ul>
					<li>Limpieza de comentarios</li>
					<li>Reemplazo de comandos simples</li>
					<li>Reemplazo de constantes</li>
					<li>Limpieza de codigo PHP</li>
					<li>Procesamiento de variables</li>
					<li>Reemplazo de comandos <b>rind</b></li>
				</ul>
			",
			"parameters" : { "$sCode" : ["string", "Código fuente."] },
			"return" : "boolean"
		} **/
		private function ProcessCode($sCode, $bDebug=false) {
			// limpieza de comentarios
			$sCode = \preg_replace("/\x12\/\*(.*?)\*\/>/is", "", $sCode);
			// if($bDebug) { die($sCode); }

			// comandos simples
			$this->SingleCommands($sCode);
			// if($bDebug) { die($sCode); }

			// constantes
			$this->ProcessConstants($sCode);
			// if($bDebug) { die($sCode); }

			// limpieza de codigo PHP
			$aCode = $this->StripPHP($sCode);
			// if($bDebug) { die(implode($aCode)); }

			// variables
			$this->VarsParser($aCode);
			$nCode = \count($aCode);
			// if($bDebug) { die(implode($aCode)); }

			for($x=0; $x<$nCode; $x++) {
				if($aCode[$x]=="\x12" && $aCode[$x+1]!="\x11") {
					// etiqueta <rind de apertura
					$vCommand = $this->GetCommand($aCode, $x);
					// if($bDebug) { print_r($vCommand); }

					$aCode = $this->ReplaceCommands($aCode, $vCommand);
					// if($bDebug) { print(implode($aCode)); }
					$nCode = \count($aCode);
				}
			}

			if($bDebug) { die(\implode($aCode)); }
			$sCode = null;
			return $aCode;
		}

		/** FUNCTION {
			"name" : "PutSlashes", 
			"type" : "private",
			"description" : "Auxiliar de <b>::InNotInArgument</b>, escapa con slashes los HTML QUOTES de los argumentos.",
			"parameters" : { "$sString" : ["string", "Cadena a escapar."] },
			"seealso" : ["nglRind::InNotInArgument"],
			"return" : "string"
		} **/
		private function PutSlashes($sString) {
			$sString = \addcslashes($sString, "'\\");
			$sString = \str_replace("\\\\".$this->RIND_HTML_QUOTE, "\\".$this->RIND_HTML_QUOTE, $sString);
			return $sString;
		}

		/** FUNCTION {
			"name" : "QuoteArguments", 
			"type" : "private",
			"description" : "Chequea y re-entrecomilla los argumentos de los comandos.",
			"parameters" : { "$aArguments" : ["array", "Código fuente."] },
			"return" : "array"
		} **/
		private function QuoteArguments($aArguments) {
			foreach($aArguments as $sName => $mValue) {
				$sValue = \trim($mValue);
				if(\strlen($sValue) && $sValue[0]==="\x12") {
					$aArguments[$sName] = $sValue;
				} else {
					$aArguments[$sName] = "\x22".$mValue."\x22";
				}
			}
			
			return $aArguments;
		}

		/** FUNCTION {
			"name" : "readTemplate", 
			"type" : "private",
			"description" : "Lee una plantilla a una variable.",
			"parameters" : {
				"$sFileName" : ["string", "Path de la plantilla."], 
				"$bRINDagsConvert" : ["boolean", "Reemplaza las etiquetas RIND por cadenas simplificadas."]
			},
			"return" : "string"
		} **/
		public function readTemplate($sFileName) {
			$sFileToInc = self::call()->clearPath($sFileName);

			// lectura del archivo
			if($this->IsTemplateFile($sFileToInc)) {
				// aborción por HTTP
				$sScheme = \parse_url($sFileName, PHP_URL_SCHEME);
				$sScheme = \strtolower($sScheme);
				if((!\ini_get("allow_url_fopen") || !$this->argument("http_support")) && ($sScheme=="http" || $sScheme=="https")) {
					if(\ini_get("allow_url_fopen")) {
						self::errorMessage($this->object, 1001, $sFileToInc);
					} else {
						self::errorMessage($this->object, 1002, $sFileToInc);
					}
				}

				$sTemplate = "";
				if(@$hFr = \fopen($sFileToInc, "rb")) {
					while(!\feof($hFr)) {
						$sTemplate .= \fread($hFr, 4096);
					}
					\fclose($hFr);
				} else {
					self::errorMessage($this->object, 1003, $sFileToInc);
				}

				if($this->argument("clear_utf8_bom")) {
					$sTemplate = \preg_replace("/^\xEF\xBB\xBF/s", "", $sTemplate);
				}

				return $sTemplate;
			}

			return false;
		}

		/** FUNCTION {
			"name" : "ReplaceCommands", 
			"type" : "private",
			"description" : "Reemplaza comandos RIND por código PHP.",
			"parameters" : {
				"$aCode" : ["array", "Código fuente."], 
				"$vCommand" : ["array", "Parámetros del comando a reemplazar."]
			},
			"return" : "array"
		} **/
		private function ReplaceCommands($aCode, $vCommand) {
			$fFunction = $vCommand["function"];
			$sReturn = "";
			if($vCommand["cmd_ini"]) {
				$sReturn = self::call("unicode")->substr($aCode, 0, $vCommand["cmd_ini"]);
			}

			if(\in_array($fFunction, $this->aRindFunctions)) {
				$fRindFunction = "rind".$fFunction;
				$sReturn .= $this->$fRindFunction($vCommand["arguments"]);
			} else if(\substr($fFunction,0,4)=="php.") {
				$fFunction = \substr($fFunction,4);
				if(\function_exists($fFunction) && isset($this->SET["PHP_FUNCTIONS"][$fFunction])) {
					$aArguments = $this->QuoteArguments($vCommand["arguments"]);
					$sFunctionCode = "<[FUN[".$fFunction."(".\implode(", ", $aArguments).")]FUN]>";
					$sFunctionCode = \preg_replace_callback("/(.{0,6})(\<\[VAR\[(.*?)\]VAR\]\>)(.{0,6})/i", [$this, "EnquoteVars"], $sFunctionCode);
					$sReturn .= $sFunctionCode;
				}
			} else if(\substr($fFunction,0,4)=="nut." && \class_exists(__NAMESPACE__."\\nglNut")) {
				$aFunction = \explode(".", $fFunction, 3);
				if(\is_array($aFunction) && \count($aFunction)==3) {
					$sObject = $aFunction[1];
					$sMethod = $aFunction[2];
				} else {
					$sObject = $aFunction[1];
					$sMethod = $aFunction[1];
				}

				$aArguments = $this->QuoteArguments($vCommand["arguments"]);
				$sFunctionCode = "<[FUN[Rind::nut(".$this->RIND_QUOTE.$sObject.$this->RIND_QUOTE.",".$this->RIND_QUOTE.$sMethod.$this->RIND_QUOTE.", \array_combine([".$this->RIND_QUOTE.\implode($this->RIND_QUOTE.", ".$this->RIND_QUOTE, \array_keys($aArguments)).$this->RIND_QUOTE."], [".\implode(", ", $aArguments)."]))]FUN]>";
				$sFunctionCode = \preg_replace_callback("/(.{0,6})(\<\[VAR\[(.*?)\]VAR\]\>)(.{0,6})/i", [$this, "EnquoteVars"], $sFunctionCode);
				$sReturn .= $sFunctionCode;
			} else {
				$sMessageError = $this->TagConverter($vCommand["source"], true);
				self::errorMessage($this->object, 1004, \htmlentities($sMessageError));
			}
			
			$sReturn .= self::call("unicode")->substr($aCode, ($vCommand["cmd_end"]+1));
			return self::call("unicode")->split($sReturn);
		}

		private function EnquoteVars($aMatchs) {
			// print_r($aMatchs);
			if($aMatchs[1]!=$this->RIND_QUOTE && $aMatchs[4]!=$this->RIND_QUOTE) {
				$sOpen = \substr($aMatchs[1], -2);
				$sClose = \substr($aMatchs[1], 0, 2);
				if($sOpen!='".' && $sClose!='."') {
					$aMatchs[2] = '".'.$aMatchs[2].'."';
					unset($aMatchs[0], $aMatchs[3]);
					return \implode($aMatchs);
				}
			}
			return $aMatchs[0];
		}
		
		/** FUNCTION {
			"name" : "ReplaceConstants", 
			"type" : "private",
			"description" : "Auxiliar del método <b>nglRind::Constants</b> para el reemplazo de constantes.",
			"parameters" : { "$aMatches" : ["array", "Array con las constantes detectadas en el código original."] },
			"seealso" : ["nglRind::Constants"],
			"return" : "string"
		} **/
		private function ReplaceConstants($aMatches) {
			if(isset($this->SET["CONSTANTS"][$aMatches[1]])) {
				return "<[VAR[".$aMatches[1]."]VAR]>";
			} else {
				return $aMatches[1];
			}
		}

		/** FUNCTION {
			"name" : "ReservedStrings", 
			"type" : "private",
			"description" : "Reemplaza las palabras reservadas por las variables <b>$RIND_...</b> y viceversa.",
			"parameters" : {
				"$sCode" : ["string", "Código fuente."], 
				"$bRevert" : ["boolean", "Indica la dirección del reemplazo, false = ida / true = vuelta."], 
			},
			"return" : "string"
		} **/
		private function ReservedStrings($sCode, $bRevert=false) {
			$aRINDKeys[] = $this->RIND_HTML_QUOTE;
			$aRINDKeys[] = $this->RIND_FUN_OPEN;
			$aRINDKeys[] = $this->RIND_FUN_CLOSE;
			$aRINDKeys[] = $this->RIND_VAR_OPEN;
			$aRINDKeys[] = $this->RIND_VAR_CLOSE;
			$aRINDKeys[] = $this->RIND_PHP_OPEN;
			$aRINDKeys[] = $this->RIND_PHP_CLOSE;
			$aRINDKeys[] = $this->RIND_HDV_OPEN;
			$aRINDKeys[] = $this->RIND_HDV_CLOSE;
			$aRINDKeys[] = $this->RIND_NOWDOC;

			$aStrings[] = '"';
			$aStrings[] = "<[FUN[";
			$aStrings[] = "]FUN]>";
			$aStrings[] = "<[VAR[";
			$aStrings[] = "]VAR]>";
			$aStrings[] = "<[PHP[";
			$aStrings[] = "]PHP]>";
			$aStrings[] = "<[HDV[";
			$aStrings[] = "\x14";

			if(!$bRevert) {
				$aRINDKeys[] = $this->RIND_LC_BRACKET;
				$aRINDKeys[] = $this->RIND_RC_BRACKET;

				$aStrings[] = "^{";
				$aStrings[] = "^}";
				$sCode = \str_replace($aStrings, $aRINDKeys, $sCode);
			} else {
				$aRINDKeys[] = $this->RIND_LC_BRACKET;
				$aRINDKeys[] = $this->RIND_RC_BRACKET;
				$aRINDKeys[] = $this->RIND_DOLLAR_SIGN;
				$aRINDKeys[] = $this->RIND_RESERVED;
				$aRINDKeys[] = $this->attribute("word_breaker");

				$aStrings[] = "{";
				$aStrings[] = "}";
				$aStrings[] = "#";
				$aStrings[] = "";
				$aStrings[] = "";

				$sCode = \str_replace($aRINDKeys, $aStrings, $sCode);
			}
			
			return $sCode;
		}

		/** FUNCTION {
			"name" : "ReservedWords", 
			"type" : "private",
			"description" : "Detecta y reemplaza las funciones, las palabras reservadas y constantes en las plantillas.",
			"parameters" : { "$sCode" : ["string", "Código fuente."] },
			"return" : "array"
		} **/
		private function ReservedWords($sCode) {

			// salvando funciones
			$sCode = \preg_replace_callback("/([a-z0-9_]+)([^a-z0-9_\.]*?)(\()/is", [&$this,"CommentReservedFunctions"], $sCode);
			
			// salvando otras palabras reservadas
			$sReservedWords = \implode("|", $this->aReservedWords);
			$sCode = \preg_replace_callback("/$sReservedWords/is", [&$this,"CommentReservedWords"], $sCode);

			// salvando constantes
			$aConstants = [];
			$vGetConstants = \get_defined_constants(true);
			$vGetConstants = \array_keys($vGetConstants["user"]);
			foreach($vGetConstants as $sConstant) {
				if(!isset($this->SET["CONSTANTS"][$sConstant])) {
					$aConstants[] = $sConstant;
				}
			}
			$sConstants = \implode("|", $aConstants);
			$sCode = \preg_replace_callback("/$sConstants/is", [&$this,"CommentReservedConstants"], $sCode);

			return $sCode;
		}

		/** FUNCTION {
			"name" : "SetPaths", 
			"type" : "private",
			"description" : "
				Arma y setea los paths utilizados por el objeto.
				Este método deberá ser ejecutado siempre que los valores de los argumentos involucrados sean alterados para los cambios surgan efecto.
			",
			"input" : "cache,gui,root,scheme",
			"output" : "project_path,cache_path,gui_path,gui_url,root_url,relative_path,template_url",
			"return" : "$this"
		} **/
		private function SetPaths() {
			$sRoot = $this->argument("root", \getcwd());
			$sGUI = $this->argument("gui", \getcwd());
			$sCache = $this->argument("cache", \getcwd());
			$sCurrentDir = $this->argument("curdir");
			$sScheme = $this->argument("scheme");

			// PATHs
			// directorio root
			$sRoot = self::call()->clearPath($sRoot, false, NGL_DIR_SLASH, true);

			// project path
			$sProjectPath 	= \realpath(NGL_PATH_PROJECT);
			$nProjectPath 	= \strlen($sProjectPath);
			$sProjectPath	= self::call()->clearPath($sProjectPath, false, NGL_DIR_SLASH, true);

			// directorio de cache
			if($sCache!==null) { $sCachePath = self::call()->clearPath($sCache, false, NGL_DIR_SLASH, true); }
			if($sCachePath===null || $sCachePath===false) { $sCachePath = NGL_PATH_CACHE; }
			if(!\is_dir($sCachePath)) {
				if(!@\mkdir($sCachePath, NGL_CHMOD_FOLDER, true)) {
					self::errorMessage($this->object, 1008, $sCachePath);
				}
			}

			// ruta de la carpeta GUI
			$sGUIPath	= ($sGUI!==null) ? self::call()->clearPath($sGUI, false, NGL_DIR_SLASH, true) : $sRoot;
			$sGUIPath 	= \realpath($sGUIPath);
			$sGUIPath 	= $sProjectPath.\substr($sGUIPath, $nProjectPath);
			$sGUIPath 	= self::call()->clearPath($sGUIPath, false, NGL_DIR_SLASH, true);

			// ruta del archivo .php que hace la peticion
			if($sCurrentDir===null) {
				$sCurrentDir = self::call()->clearPath(\dirname($this->sPHPFile), false, NGL_DIR_SLASH, true);
			}

			// rutas relativas
			$sRelativePath	= self::call()->clearPath(\str_replace($sRoot, "", $sCurrentDir));
			$sRelativeGUI	= self::call()->clearPath(\str_replace($sRoot, "", $sGUIPath));
			
			// URLs
			// protocolo
			$sScheme = ($sScheme!==null) ? \strtolower($sScheme) : "http";
			
			// url del archivo .php que hace la peticion
			$sURLSelf = self::call()->clearPath(NGL_URL.$sRelativePath);

			// url del sitio para modo de seguimiento de plantillas
			$url = self::call("url")->parse($sURLSelf);

			// root url
			$sRootURLPath = $url->path;
			$sRootURLPath = \str_replace("/", NGL_DIR_SLASH, $sRootURLPath);
			$sRootURLPath = \str_replace($sRelativePath, "", $sRootURLPath);
			$url->update("path", $sRootURLPath);
			$sRootURL = $url->unparse();
			$sRootURL = self::call()->clearPath($sRootURL);

			// gui url
			$sGUIURL = $url->path;
			$sGUIURL = \str_replace("/", NGL_DIR_SLASH, $sGUIURL);
			$sGUIURL = $sGUIURL.NGL_DIR_SLASH.$sRelativeGUI;
			$sGUIURL = \str_replace(NGL_DIR_SLASH, "/", $sGUIURL);
			$url->update("path", $sGUIURL);
			$sGUIURL = $url->unparse();
			$sGUIURL = self::call()->clearPath($sGUIURL);

			// template url
			$sTemplateURL	= $url->path."/".$sRelativePath;
			$url->update("path", $sTemplateURL);
			$sTemplateURL	= $url->unparse();
			$sTemplateURL	= self::call()->clearPath($sTemplateURL);

			// atributos
			// print("project_path: ".$sProjectPath."\n");
			// print("root_url: ".$sRootURL."\n");
			// print("gui_path: ".$sGUIPath."\n");
			// print("gui_url: ".$sGUIURL."\n");
			// print("relative_path: ".$sRelativePath."\n");
			// print("template_url: ".$sTemplateURL."\n");
			// print("cache_path: ".$sCachePath."\n");
			// exit("\nfin de setpaths");
			
			$this->attribute("project_path", 	$sProjectPath);
			$this->attribute("relative_path",	$sRelativePath);
			$this->attribute("cache_path", 		$sCachePath);
			$this->attribute("root_url", 		$sRootURL);
			$this->attribute("gui_path", 		$sGUIPath);
			$this->attribute("gui_url", 		$sGUIURL);
			$this->attribute("template_url", 	$sTemplateURL);
			
			return $this;
		}

		/** FUNCTION {
			"name" : "setSESS", 
			"type" : "public",
			"description" : "Setea el contenido de la variable $_SESSION[NGL_SESSION_INDEX]["SESS"] en el índice <b>SESS</b> de la variable <b>SET</b> y lo retorna",
			"parameters" : { "$sIndex" : ["string", "Nombre del indice a retornar."] },
			"return" : "$this"
		} **/
		public function setSESS() {
			$SESS = (isset($_SESSION[NGL_SESSION_INDEX]["SESS"])) ? $_SESSION[NGL_SESSION_INDEX]["SESS"] : [];
			$this->setSET("SESS", $SESS);
			return $this;
		}

		/** FUNCTION {
			"name" : "setSET", 
			"type" : "public",
			"description" : "
				Setea un valor en la variable <b>$_SET</b>, disponible en las plantillas.
				Si <b>$sRequested</b> es distinto de NULL, se intentará setear el valor de es índice de la variable global <b>$_REQUEST</b>.
				El método retornará el valor asignado a <b>$sIndex</b>.
				Cuando se utilice setSET en el archivo PHP habrá que asignarle un nombre al objeto <b>rind</b>, ya que el utilizar la sintaxis <b>rind.</b> causará errores.
			",
			"parameters" : {
				"$sIndex" : ["string", "Nombre del indice a setear."],
				"$mValue" : ["string", "Valor a guardar en <b>$sIndex</b>."],
				"$sRequested" : ["string", "Nombre de un indice de la variable global <b>$_REQUEST</b>."]
			},
			"input" : "set_index,set_value,set_request",
			"return" : "$this"
		} **/
		public function setSET() {
			list($sIndex, $mValue, $sRequested) = $this->getarguments("set_index,set_value,set_request_index", \func_get_args());
			$this->SET[$sIndex] = $mValue;
			if($sRequested!==null && isset($_REQUEST)) {
				if(\array_key_exists($sRequested, $_REQUEST)) {
					$this->SET[$sIndex] = $_REQUEST[$sRequested];
				} else if(isset($_REQUEST["values"]) && \array_key_exists($sRequested, $_REQUEST["values"])) {
					$this->SET[$sIndex] = $_REQUEST["values"][$sRequested];
				}
			}

			return $this;
		}

		/** FUNCTION {
			"name" : "showPaths", 
			"type" : "public",
			"description" : "Muestra los paths con los que está seteado el objeto.",
			"return" : "string"
		} **/
		public function showPaths() {
			$this->SetPaths();
			return [
				"project_path" => $this->attribute("project_path"),
				"relative_path" => $this->attribute("relative_path"),
				"gui_path" => $this->attribute("gui_path"),
				"cache_path" => $this->attribute("cache_path"),
				"root_url" => $this->attribute("root_url"),
				"gui_url" => $this->attribute("gui_url"),
				"template_url" => $this->attribute("template_url")
			];
		}

		/** FUNCTION {
			"name" : "SingleCommands", 
			"type" : "private",
			"description" : "Ejecuta los comandos simples: <b>abort</b>, <b>once</b> y <b>skip</b>.",
			"parameters" : { "$sCode" : ["string", "Código fuente."] },
			"return" : "string"
		} **/
		private function SingleCommands(&$sCode) {
			$sCode = \preg_replace("/\x12skip( )*\/>/is", "<[PHP[ c".$this->attribute("word_breaker")."ontinue; ]PHP]>", $sCode);
			$sCode = \preg_replace("/\x12abort( )*\/>/is", "<[PHP[ b".$this->attribute("word_breaker")."reak; ]PHP]>", $sCode);
			$sCode = \preg_replace("/\x12once( )*\/>/is", "<[FUN[Rind::once()]FUN]>", $sCode);
			$sCode = \preg_replace("/\x12unique( )*\/>/is", "<[FUN[Rind::unique()]FUN]>", $sCode);
		}

		/** FUNCTION {
			"name" : "stamp", 
			"type" : "public",
			"description" : "Ejecuta el método <b>nglRind::process</b> y ejecuta el archivo generado.",
			"parameters" : {
				"$sFileName" : ["string", "Nombre de la plantilla activa.", "argument::template"],
				"$sCacheMode" : ["string", "Estable el modo en el que trabajará el cache.", "argument::cache_mode"]
			},
			"input" : "cache_file,cache_path*,fill_urls,gui_path*,template,cache_mode",
			"seealso" : ["nglRind::process"],
			"return" : "string"
		} **/
		public function stamp() {
			$aBacktrace = \debug_backtrace(false);
			$this->sPHPFile = $aBacktrace[0]["file"];
			$this->SetPaths();
			$sCacheFile = \call_user_func_array(array($this, "process"), \func_get_args());
			if($sCacheFile===false) { return false; }
			$this->sPHPFile = null;
			\ob_start();
			include($sCacheFile);
			$sContent = \ob_get_clean();
			return $this->argument("trim_stamp") ? \trim($sContent) : $sContent;
		}

		public function stampstr() {
			list($sSource) = $this->getarguments("source", \func_get_args());
			$aBacktrace = \debug_backtrace(false);
			//$this->sPHPFile = $aBacktrace[0]["file"];
			$this->SetPaths();
			
			\Rind::$Rinds[$this->RIND_UID] = $this;
			$sSource = $this->rind2php($sSource);
			$sSourceCode = "<?php ".$this->RIND_TEMPLATES."=[];\n ".$this->sMergeFiles." ?>\n".$sSource;
			return eval(self::EvalCode("?>".$sSourceCode));
		}

		/** FUNCTION {
			"name" : "StripPHP", 
			"type" : "private",
			"description" : "Elimina el código PHP presente en las plantillas.",
			"parameters" : { "$sCode" : ["string", "Código fuente."] },
			"return" : "array"
		} **/
		private function StripPHP($sCode) {
			// limpieza de palabras reservadas
			$sCode = $this->ReservedWords($sCode);

			// array to string
			$aCode = self::call("unicode")->split($sCode);
			$sCode = null;
			
			$bSave = true;
			$nOpened = 0;
			$aCleanCode = [];
			$nCode = \count($aCode) - 6;

			for($x=0; $x<$nCode; $x++) {
				$sChar = $aCode[$x];
				$sString2 = $aCode[$x].$aCode[$x+1];
				
				if($sString2=="<?") {
					$nOpened++;
					$bSave = false;
				}
				
				if(!$bSave && $sString2=="?>") {
					$nOpened--;
					if(!$nOpened) {
						$x += 2;
						$sChar = $aCode[$x];
						$bSave = true;
					}
				}
				
				if($bSave) { $aCleanCode[] = $sChar; }
			}

			$aReturn = \array_merge($aCleanCode, \array_slice($aCode, -6, 6));
			unset($aCode, $aCleanCode);
			return $aReturn;
		}

		/** FUNCTION {
			"name" : "stripQuotes", 
			"type" : "protected",
			"description" : "Elimina el primer par de comillas dobles del principio y fin.",
			"parameters" : { "$sArgument" : ["string", "Cadena a limpiar."] },
			"return" : "string"
		} **/
		protected function stripQuotes($sArgument) {
			$sArgument = \str_replace($this->RIND_HTML_QUOTE, '"', $sArgument);
			$sArgument = \trim($sArgument, "\t\n\r\0\x0B");
			
			if(isset($sArgument[1])) {
				$nLength = self::call("unicode")->strlen($sArgument);
				if($sArgument[0].$sArgument[1]=="\x22\x22" && $sArgument[$nLength-2].$sArgument[$nLength-1]=="\x22\x22") {
					$sArgument[0] = "\x20";
					$sArgument[$nLength-1] = "\x20";
				} else if($sArgument[0]=="\x22" && $sArgument[$nLength-1]=="\x22") {
					$sArgument = \trim($sArgument);
					$sArgument = \trim($sArgument, "\x22");
				}
			}

			$sArgument = \str_replace('"', $this->RIND_HTML_QUOTE, $sArgument);
			return $sArgument;
		}

		/** FUNCTION {
			"name" : "TagConverter", 
			"type" : "private",
			"description" : "Reemplaza las etiquetas <b>rind</b> por cadenas simplificadas y viceversa.",
			"parameters" : {
				"$sCode" : ["string", "Código fuente."], 
				"$bRevert" : ["boolean", "Indica la dirección del reemplazo, false = ida / true = vuelta."], 
			},
			"return" : "string"
		} **/
		private function TagConverter($sCode, $bRevert=false) {
			if(!$bRevert) {
				$sCode = \str_replace("\x11", $this->RIND_DC1, $sCode);
				$sCode = \str_replace("\x12", $this->RIND_DC2, $sCode);
				$sCode = \str_replace("\x13", $this->RIND_DC3, $sCode);
				$sCode = \str_replace("<rind:", "\x12", $sCode);
				$sCode = \str_replace("</rind:", "\x12\x11", $sCode);
				$sCode = \str_replace("<@", "\x13", $sCode);
				$sCode = \str_replace("</@", "\x13\x11", $sCode);
			} else {
				$sCode = \str_replace("\x12\x11", "</rind:", $sCode);
				$sCode = \str_replace("\x12", "<rind:", $sCode);
				$sCode = \str_replace("\x13\x11", "</@", $sCode);
				$sCode = \str_replace("\x13", "<@", $sCode);
				$sCode = \str_replace($this->RIND_DC1, "\x11", $sCode);
				$sCode = \str_replace($this->RIND_DC2, "\x12", $sCode);
				$sCode = \str_replace($this->RIND_DC3, "\x13", $sCode);			
			}
			
			return $sCode;
		}

		/** FUNCTION {
			"name" : "TagReader", 
			"type" : "private",
			"description" : "Lee y parsea etiquetas <b>rind</b>.",
			"parameters" : {
				"$aCode" : ["array", "Código fuente."],
				"$nFrom" : ["int", "Inicio de lectura."],
				"$sBreaker" : ["string", "Indicador de stop de lectura."],
				"$sJumper" : ["string", "Indicador anidamientos.", "null"],
			},
			"return" : "array"
		} **/
		private function TagReader($aCode, $nFrom, $sBreaker, $sJumper=null) {
			$sNewStr		= "";	 			/* nueva cadena resultante */
			$aAttributes	= []; 			/* array con los posibles atributos */
			$nLen			= \count($aCode);	/* longuitud del codigo */
			$nJump			= 0;				/* anidamientos */

			// delimitador
			$nDelimiter = self::call("unicode")->strlen($sBreaker);
			$sDelimiter = $this->MakeMatch($nDelimiter, "\$aCode", "\$nFrom");

			// indicador de anidamiento
			if($sJumper) {
				$nJumper = self::call("unicode")->strlen($sJumper);
				$nJumperLast = $nJumper-1;
				$sJumperMatch = $this->MakeMatch($nJumper, "\$aCode", "\$nFrom");
			}
			
			// activa la lectura de atributos
			$bAttributes = true;

			$nInit = $nFrom;
			$bBreak = false;
			while(1) {
				$sChar = $aCode[$nFrom];
				$nOrd = self::call("unicode")->ord($sChar);

				// anidamiento de cadenas iguales
				if($sJumper && $sJumper[0]==$sChar) {
					if(isset($aCode[$nFrom+$nJumperLast]) && $sJumper[$nJumperLast]==$aCode[$nFrom+$nJumperLast]) {
						if($sJumper==eval($sJumperMatch)) {
							$nJump++;
						}
					}
				}

				if($sBreaker[0]==$sChar && eval($sDelimiter)==$sBreaker) {
					if($nJump>0) {
						$nJump--;
					} else {
						break;
					}
				}
				
				// sin atributos
				if($aCode[$nFrom]=="\x12" && $nInit < $nFrom && !\count($aAttributes) ) {
					$bAttributes = false;
					$sNewStr .= $sChar;
				} else if($bAttributes && $aCode[$nFrom]=="\x13" && $aCode[$nFrom+1]!="\x11" && $nJump==0) {
					$sAttribute = "";
					$y = $nFrom+1;
					while($y<$nLen) {
						if($aCode[$y]==">") { break; }
						$sAttribute .= $aCode[$y];
						$y++;
					}

					$vAttrib = $this->TagReader($aCode, $y+1, "\x13\x11".$sAttribute, "\x13".$sAttribute);
					if(!isset($aAttributes[$sAttribute])) {
						$aAttributes[$sAttribute] = $vAttrib["string"];
					} else {
						$sAttribute .= "_".\md5(\microtime());
						$aAttributes[$sAttribute] = $vAttrib["string"];
					}
					$sNewStr .= \implode(\array_slice($aCode, $nFrom, $vAttrib["char"]-$nFrom+1));
					$nFrom = $vAttrib["char"];
				} else {
					$sNewStr .= $sChar;
				}

				$nFrom++;
				if($nFrom+1 > $nLen) { break; }
			}
			$aCode = null;

			$vReturn["char"]		= $nFrom+$nDelimiter;
			$vReturn["string"]		= $sNewStr;
			$vReturn["arguments"]	= $aAttributes;

			return $vReturn;
		}

		/** FUNCTION {
			"name" : "varName", 
			"type" : "protected",
			"description" : "Genera un nombre de variable aleatorio de entre 1 y 32 caracteres de loguitud.",
			"parameters" : { "$nLength" : ["int", "Largo del nombre de la variable."] },
			"return" : "string"
		} **/
		protected function varName($nLength=6) {
			$nLength -= 2;
			if($nLength<1) { $nLength = 1; }
			if($nLength>32) { $nLength = 32; }
			$sVarName = \random_int(1000, 2000).\microtime().\random_int(1000, 2000);
			$sVarName = \md5($sVarName);
			return "Ox".\substr($sVarName,0,$nLength);
		}

		/** FUNCTION {
			"name" : "VarsDenyAllow", 
			"type" : "protected",
			"description" : "
				Establece la politica de variables PHP están permitidas en las plantillas.
				Si el valor de <b>$sVariables</b> es NULL, se aplicara <b>$sType</b> para todas las variables.
			",
			"parameters" : {
				"$sType" : ["string", "Política a aplicar, <b>deny</b> o <b>allow</b>."],
				"$sVariables" : ["string", "Listado de variables (con el signo $) separadas por comas.", "null"],
			},
			"return" : "void"
		} **/
		protected function VarsDenyAllow($sType, $sVariables=null) {
			$sType = \strtolower($sType);
			$aVarsDenyAllow = [];

			if($sVariables!==null) {
				$sAllNone = \strtoupper($sVariables);
				$sAllNone = \trim($sAllNone);
				if($sAllNone!="NONE" && $sAllNone!="ALL") {
					$aVariables = self::call("shift")->csvToArray($sVariables);
					$aVarsDenyAllow = self::call()->truelize($aVariables[0]);
					foreach($aVarsDenyAllow as $sVariable => $bTrue) {
						if($sVariable[0]=="$") {
							$sVariable = \substr($sVariable, 1);
							$aVarsDenyAllow[$sVariable] = ttue;
						}
					}
				} else {
					$aVarsDenyAllow[$sAllNone] = true;
				}
			}

			if($sType=="deny") {
				if(!\count($aVarsDenyAllow)) { $aVarsDenyAllow["ALL"] = true; }
				$this->vVarsDeny = $aVarsDenyAllow;
			} else {
				if(!\count($aVarsDenyAllow)) { $aVarsDenyAllow["NONE"] = true; }
				$this->vVarsAllow = $aVarsDenyAllow;
			}
			
			$this->bInitVarsDenyAllow = true;
		}

		/** FUNCTION {
			"name" : "VarsEscape", 
			"type" : "private",
			"description" : "Detecta y reemplaza las funciones, las palabras reservadas y constantes en las plantillas.",
			"parameters" : { "$sCode" : ["string", "Código fuente."] },
			"return" : "array"
		} **/
		private function VarsEscape($sCode) {
			$aCode		= self::call("unicode")->split($sCode);
			$nCode 		= \count($aCode);
			$bSave 		= false;
			$nIndex		= false;
			$sVarName	= "";

			for($x=0; $x<$nCode; $x++) {
				// 48 - 57	= números 
				// 65 - 90	= mayúsculas 
				// 95		= guión bajo
				// 97 - 122	= minúsculas
				$nNextChar = (isset($aCode[$x+1])) ? \ord($aCode[$x+1]) : null;
				$bSlash = (isset($aCode[$x-1]) && $aCode[$x-1]=="\\");
				
				if(!$bSave) {
					if($aCode[$x]=="\$" && !$bSlash && (!isset($aCode[$x-6]) || \implode(\array_slice($aCode, $x-6, 6))!="<[VAR[") && ($nNextChar!==null && ($nNextChar<48 || $nNextChar>57))) {
						$bSave = $x;
						continue;
					}
				}

				if($bSave!==false) {
					$sVarName .= $aCode[$x];
					$bToName = (($nNextChar>47 && $nNextChar<58) || ($nNextChar>64 && $nNextChar<91) || ($nNextChar>96 && $nNextChar<123) || $nNextChar==95);
					if(!$bToName || ($x+1)>=$nCode) {
						$sVarName	= $this->RIND_DOLLAR_SIGN.$sVarName;
						$aVariable	= self::call("unicode")->split($sVarName);
						$aBefore	= \array_slice($aCode, 0, $bSave);
						$aAfter		= \array_slice($aCode, $x+1);
						$aCode		= \array_merge($aBefore, $aVariable, $aAfter);
						$nCode		= \count($aCode);

						$x = $bSave;
						$sVarName = "";
						$bSave = false;
					}
				}
			}

			return \implode($aCode);
		}

		/** FUNCTION {
			"name" : "VarsParser", 
			"type" : "private",
			"description" : "Parser de variables.",
			"parameters" : { "$aCode" : ["array", "Código fuente."] },
			"return" : "array"
		} **/
		private function VarsParser(&$aCode) {
			$nCode 		= \count($aCode);
			$bSave 		= false;
			$sVarName 	= "";
			$nKeys 		= 0;

			for($x=0; $x<$nCode; $x++) {
				if(isset($aCode[$x+1]) && $bSave===false) {
					if($aCode[$x].$aCode[$x+1]=="\x7b\$" && (!isset($aCode[$x-1]) || $aCode[$x-1]!="\x7b")) {
						$sVarName .= $aCode[$x];
						$bSave = $x;
						$nKeys++;
						continue;
					}
				}

				if($bSave!==false) {
					$sVarName .= $aCode[$x];
					if($aCode[$x]=="{") { $nKeys++; }
					if($aCode[$x]=="}") {
						$nKeys--;
						if($nKeys==0) {
							$aVariable = $this->VarsProcessor($sVarName);

							if($aVariable) {
								$aBefore	= \array_slice($aCode, 0, $bSave);
								$aAfter		= \array_slice($aCode, $x+1);
								$aCode		= \array_merge($aBefore, $aVariable, $aAfter);
								$nCode		= \count($aCode);
							}

							$x = $bSave+1;
							$nKeys = 0;
							$sVarName = "";
							$bSave = false;
						}
					}
				}
			}
		}

		/** FUNCTION {
			"name" : "VarsProcessor", 
			"type" : "private",
			"description" : "Procesa los nombres de las variables.",
			"parameters" : { "$sVarName" : ["string", "Cadena que contiene el código de la variable."] },
			"return" : "array"
		} **/
		private function VarsProcessor($sVarName) {
			if(strpos($sVarName, ".")) {
				$sVarName = \str_replace(
					["(", ")", "."],
					["[{","}]",'"]["'],
					$sVarName
				);
				$sVarName = \preg_replace("/([a-z0-9_]+)/is", '["\\0"]', $sVarName);
				$sVarName = \str_replace(
					['[["'.$this->RIND_HTML_QUOTE, $this->RIND_HTML_QUOTE.'"]]', '["[', ']"]', '$["'],
					['["', '"]', "[", "]", '$'],
					$sVarName
				);
				$sVarName = \preg_replace("/^\[\"([a-z0-9_]+)\"\]/", "\\1", $sVarName);
				$sVarName = \preg_replace("/\->\[\"([a-z0-9_]+)\"\]/", "->\\1", $sVarName);
				$sVarName = \preg_replace("/([^a-z0-9_\"])([a-z0-9_]+)(\"\])/is", '\\1\\2', $sVarName);
			}
			
			$sVarString = \str_replace("\x5C", "", $sVarName);
			$sVarString = \str_replace("\x27", "\x22", $sVarName);
			$sVarString = \substr($sVarString, 2, -1);

			if(\strpos($sVarString, "->")) {
				// objetos
				$sName = \substr($sVarString, 0, \strpos($sVarString,"-"));
				$sVariable = "\$GLOBALS[".$this->RIND_QUOTE.$sName.$this->RIND_QUOTE."]-".\substr($sVarString, \strpos($sVarString,">"));
			} else if(\strpos($sVarString, "[")) {
				// arrays
				$sName = \substr($sVarString, 0, \strpos($sVarString,"["));
				$sVariable = "\$GLOBALS[".$this->RIND_QUOTE.$sName.$this->RIND_QUOTE."]".\substr($sVarString, \strpos($sVarString,"["));
			} else {
				$sName = $sVarString;
				$sVariable = "\$GLOBALS[".$this->RIND_QUOTE.$sVarString.$this->RIND_QUOTE."]";
			}
			
			$bAllow = ($sName=="_SET") ? true : false;
			if($sName!="GLOBALS" && $sName!="ngl" && $sName!="_SET") {
				if(isset($this->vVarsDeny["NONE"]) && !isset($this->vVarsAllow["NONE"])) {
					$bAllow = true;
				} else if(isset($this->vVarsDeny["ALL"]) && isset($this->vVarsAllow[$sName])) {
					$bAllow = true;
				} else if(isset($this->vVarsAllow["ALL"]) && !isset($this->vVarsDeny[$sName])) {
					$bAllow = true;
				}
			}

			$sVariable = ($bAllow) ? "<[VAR[$sVariable]VAR]>" : "false/*".$this->RIND_RESERVED."\$".$sVarString.$this->RIND_RESERVED."*/";
			return \str_split($sVariable);
		}

		/** FUNCTION {
			"name" : "rindDump", 
			"type" : "private",
			"description" : "Retorna el código que genera volcados en pantalla <b>rind:dump</b> en las plantillas.",
			"parameters" : { "$vArguments" : ["array", "Argumentos del comando."] },
			"return" : "string"
		} **/
		private function rindDump($vArguments) {
			$sVarname = $this->dynVar();
			$sOutput = $this->dynVar();
			$mDie = (isset($vArguments["die"])) ? $vArguments["die"] : false;
			$bReturn = ((isset($vArguments["print"]) && !self::call()->isTrue($vArguments["print"])));

			$sReturn = '<[PHP[eval(\'
				'.$sVarname.' = '.$vArguments["content"].';
				\ob_start();
				if(\is_object('.$sVarname.')) {
					if(\method_exists('.$sVarname.', '.$this->RIND_QUOTE.'get'.$this->RIND_QUOTE.') && \method_exists('.$sVarname.', '.$this->RIND_QUOTE.'reset'.$this->RIND_QUOTE.')) {
						Rind::nut('.$this->RIND_QUOTE.'owl'.$this->RIND_QUOTE.','.$this->RIND_QUOTE.'reset'.$this->RIND_QUOTE.',['.$this->RIND_QUOTE.'content'.$this->RIND_QUOTE.'=>'.$sVarname.']); 
						'.$sVarname.' = Rind::nut('.$this->RIND_QUOTE.'owl'.$this->RIND_QUOTE.','.$this->RIND_QUOTE.'get'.$this->RIND_QUOTE.',['.$this->RIND_QUOTE.'content'.$this->RIND_QUOTE.'=>'.$sVarname.']);
						Rind::dump('.$sVarname.');
					} else {
						Rind::dump('.$sVarname.');
					}
				} else {
					Rind::dump('.$sVarname.');
				}
				'.$sVarname.' = \ob_get_clean();
				
				'.$sOutput.' = "<pre>";
				'.$sOutput.' .= \htmlentities('.$sVarname.', ENT_IGNORE);
				'.$sOutput.' .= "</pre>";'
			;
			$sReturn .= ($bReturn) ? 'return '.$sOutput.';' : 'echo '.$sOutput.';';
			$sReturn .= '\')]PHP]>';
			
			if($mDie) { $sReturn .= '<[PHP[exit();]PHP]>'; }

			return $sReturn;
		}

		/** FUNCTION {
			"name" : "rindEco", 
			"type" : "private",
			"description" : "Retorna el código que imprime un valor usando <b>rind:eco</b> en las plantillas.",
			"parameters" : { "$vArguments" : ["array", "Argumentos del comando."] },
			"return" : "string"
		} **/
		private function rindEco($vArguments) {
			// $sReturn = '<[PHP[ print('.$vArguments["content"].'); ]PHP]>';
			$sReturn = '<[PHP[ Rind::eco('.$vArguments["content"].'); ]PHP]>';
			return $sReturn;
		}

		private function GetDataArguments($vArguments, $sFilePath=false, $aSubMerge=false) {
			$aDataArguments = $aGlobalDataArguments = [];
			foreach($vArguments as $sKey => $sValue) {
				if(\strpos($sKey, "data-")!==false) {
					$sEncoded = \base64_encode(\base64_decode($sValue, true));
					$sValue = ($sEncoded===$sValue) ? \base64_decode($sValue) : $sValue;
					$aGlobalDataArguments[\substr($sKey, 5)] = \base64_encode($this->rind2php($sValue));
				}
			}

			if(isset($vArguments["data"])) {
				$aGetData = \current($this->GetDataArgumentsMultiple($vArguments["data"]));
				foreach($aGetData[1] as $sKey => $sValue) {
					$aString = self::call("unicode")->split(\base64_decode($sValue));
					$aGlobalDataArguments[$sKey] = \base64_encode($this->FixCode($aString));
				}
			}
			$aTemplates = [[$sFilePath, $aGlobalDataArguments]];

			if(isset($vArguments["multiple"])) {
				$aMultiple = $this->GetDataArgumentsMultiple($vArguments["multiple"]);
				if($aSubMerge) { $aTemplates[$aSubMerge[0]] = []; }
				if($aMultiple!==false) {
					foreach($aMultiple as $nIdx => $aTemplate) {
						$aTemplateData = [];
						foreach($aTemplate[1] as $sKey => $sValue) {
							$aString = self::call("unicode")->split(\base64_decode($sValue));
							$aTemplateData[$sKey] = \base64_encode($this->FixCode($aString));
						}

						if($aSubMerge) {
							$aTemplates[$aSubMerge[0]][] = [$aSubMerge[1].$aTemplate[0], \array_merge($aGlobalDataArguments, $aTemplateData)];
						} else {
							$aTemplates[] = [$aTemplate[0], \array_merge($aGlobalDataArguments, $aTemplateData)];
						}
					}
				}
			}

			// dodo
			// print_r($aTemplates); exit();
			return $aTemplates;
		}

		private function GetDataArgumentsPrepare($sString, $bBase64Encode=true) {
			return \preg_replace_callback(
				"/".$this->RIND_HTML_QUOTE."(.*?)".$this->RIND_HTML_QUOTE."/is", 
				function($aMatchs) use ($bBase64Encode) {
					$sCode = \trim($aMatchs[1]);
					$sCode = \str_replace($this->RIND_QUOTE, '"', $sCode);
					$sCode = \preg_replace_callback(
						"/\{\*[a-z0-9\-\_\.]+\}/is", 
						function($aMatch) use ($bBase64Encode) {
							$sVar = \substr($aMatch[0], 2, -1);
							$sVar = ($bBase64Encode) ? \base64_encode($sVar) : $sVar;
							$sRindID = \str_replace($this->RIND_QUOTE, '', $this->RIND_ME);
							// return "<[VARSET[".$sVar."|".$sRindID."]VARSET]>";
							return "<?php echo Rind::getset('".$sVar."|".$sRindID."');?>";
						},
						$sCode
					);
					return ($bBase64Encode) ? '"'.\base64_encode($sCode).'"' : '"'.$sCode.'"';
				}, $sString
			);
		}

		private function GetDataArgumentsMultiple($sString) {
			$nCurly = \strpos($sString, "{");
			$nSquare = \strpos($sString, "[");
			$bNut = (\strpos($sString, "\x12json>nut:")===0);
			if(($nCurly===false && $nSquare===false) || $bNut) {
				$sString = \str_replace(["\x12json>", "\x12\x11json>"], "", $sString);
				if(\strlen($sString)) {
					if($bNut) { // via nut => nut:{"nut":"..", "method":"..", "args":{}}
						$aNut = \json_decode($this->InnerRind2php(\substr($sString,4), true), true);
						$aArgs = (empty($aNut["args"])) ? null : $aNut["args"];
						$sString = self::call("nut.".$aNut["nut"])->run($aNut["method"], $aArgs);
					} else { // via archivo json
						$sGUIPath = ($sString[0]==NGL_DIR_SLASH) ? $this->attribute("project_path") : (!empty($this->aFilePath["dirname"]) ? $this->aFilePath["dirname"].NGL_DIR_SLASH : ".".NGL_DIR_SLASH);
						$sString = $sGUIPath.$sString;
						$sString = $this->readTemplate($sString);
					}
					$sString = $this->TagConverter($sString);
					$aString = self::call("unicode")->split($sString);
					$sString = \implode($aString);
					$sString = $this->ReservedStrings($sString);
					$sString = \implode($this->ProcessCode($sString));
					$nCurly = \strpos($sString, "{");
					$nSquare = \strpos($sString, "[");
				}
			}
			
			if($nCurly!==false && $nSquare!==false) {
				$nStart = ($nCurly<$nSquare) ? $nCurly : $nSquare;
				$sClose = ($nCurly<$nSquare) ? "}" : "]";
			} else if($nCurly) {
				$nStart = $nCurly;
				$sClose = "}";
			} else {
				$nStart = $nSquare;
				$sClose = "]";
			}

			$nEnd = \strrpos($sString, $sClose);
			$sString = \substr($sString, $nStart, ($nEnd-$nStart)+1);
			$sString = $this->GetDataArgumentsPrepare($sString);
			$aData = \json_decode($sString, true);
			if($aData===null) { return false; }

			if(!\is_int(\key($aData))) { $aData = [[null, $aData]]; }
			$aDataArguments = [];
			foreach($aData as $nIndex => $aArgument) {
				$aDataArguments[$nIndex] = [];
				$aDataArguments[$nIndex][0] = \base64_decode($aArgument[0]);
				$aDataArguments[$nIndex][1] = [];
				if(isset($aArgument[1]) && \is_array($aArgument[1]) && \count($aArgument[1])) {
					foreach($aArgument[1] as $sKey => $mValue) {
						if(\is_array($mValue)) {
							$aValues = $this->GetDataArgumentsMultipleArrays($mValue);
							$mValue = \base64_encode(\json_encode($aValues));
						} else {
							$sCode = \base64_decode($mValue);
							$aCode = self::call("unicode")->split($sCode);
							$mValue = \base64_encode($this->FixCode($aCode));
						}

						$aDataArguments[$nIndex][1][\base64_decode($sKey)] = $mValue;
					}
				}
			}
			// print_r($aDataArguments);
			return $aDataArguments;
		}

		private function GetDataArgumentsMultipleArrays($aData) {
			$aValues = [];
			foreach($aData as $sValKey => $mValVal) {
				$mIndex = (\is_int($sValKey)) ? $sValKey : \base64_decode($sValKey);
				if(\is_array($mValVal)) {
					$aValues[$mIndex] = $this->GetDataArgumentsMultipleArrays($mValVal);
				} else {
					$sValVal = \base64_decode($mValVal);
					$aCode = self::call("unicode")->split($sValVal);
					$aValues[$mIndex] = $this->FixCode($aCode);
				}
			}
			return $aValues;
		}

		/** FUNCTION {
			"name" : "rindHalt", 
			"type" : "private",
			"description" : "Retorna el código que detiene un ejecución usuando <b>rind:halt</b> en las plantillas.",
			"parameters" : { "$vArguments" : ["array", "Argumentos del comando."] },
			"return" : "string"
		} **/
		private function rindHalt($vArguments) {
			$sReturn = "";
			if(isset($vArguments["url"])) { $sReturn .= '<[PHP[\header("location:'.$vArguments["url"].'");]PHP]>'; }
			if(isset($vArguments["content"])) {
				$sReturn .= '<[PHP[die("'.$vArguments["content"].'");]PHP]>';
			} else {
				$sReturn .= '<[PHP[exit();]PHP]>';
			}
			return $sReturn;
		}

		/** FUNCTION {
			"name" : "rindJson", 
			"type" : "private",
			"description" : "Retorna el código de una variable string generada con <b>rind:strvar</b> en las plantillas.",
			"parameters" : { "$vArguments" : ["array", "Argumentos del comando."] },
			"return" : "string"
		} **/
		private function rindJson($vArguments) {
			$sReturn = $vArguments["content"];
			$sReturn = \str_replace(['".<[VAR[', ']VAR]>."'],['<[VAR[', ']VAR]>'], $sReturn);
			$sReturn = \str_replace(['<[VAR[', ']VAR]>'],['<[HDV[{', '}]HDV]>'], $sReturn);
			$sReturn = "<[PHP[Rind::json(<<<RINDJSON\n".$sReturn."\nRINDJSON\n, $this->RIND_ME)]PHP]>";
			return $sReturn;
		}

		/** FUNCTION {
			"name" : "rindIfcase", 
			"type" : "private",
			"description" : "Genera el código PHP para el comando <b>rind:ifcase</b> del sistema de plantillas.",
			"parameters" : { "$vArguments" : ["array", "Argumentos del comando."] },
			"return" : "string"
		} **/
		private function rindIfcase($vArguments) {
			if(!isset($vArguments["content"])) { $vArguments["content"] = ""; }
			$sPreIf = $sSign = "";
			$aKeys = \array_keys($vArguments);
			$sKeys = \implode($aKeys);
			
			if(\strpos($sKeys, "then")==-1 && !isset($vArguments["content"])) { return false; }

			$aReturn = [];
			$aConditions = [];
			$aIfStatements = [];

			if(\strpos($sKeys, "iff:")!==false || \strpos($sKeys, "isset:")!==false || \strpos($sKeys, "noempty:")!==false || \strpos($sKeys, "in:")!==false) {
				$aIfStatements = [];
				foreach($vArguments as $sKey => $mValue) {
					$aKeys = \explode(":", $sKey);
					if(isset($aKeys[1], $vArguments["then:".$aKeys[1]])) {
						switch($aKeys[0]) {
							case "iff":
								$sCaseCondition = \trim($mValue, " \t\n\r\0\x0B");
								break;

							case "empty":
							case "noempty":
								$mValue = \trim($mValue, " \t\n\r\0\x0B");
								$mValue = "\x12heredoc>".$mValue."\x12\x11heredoc>";
								$sCaseCondition = ($aKeys[0]=="noempty") ? "!empty(".$mValue.")" : "empty(".$mValue.")";
								break;

							case "isset":
								$sIssetArgument = \trim($mValue, " \t\n\r\0\x0B");
								$sCaseCondition = $this->IssetArgument($sIssetArgument);
								break;
							
							case "in":
							case "notin":
								if(isset($vArguments["needle:".$aKeys[1]])) {
									if($aKeys[0]=="notin") { $sSign = "!"; }
									$sBreaker = (isset($vArguments["splitter:".$aKeys[1]])) ? $vArguments["splitter:".$aKeys[1]] : ";";
									$aInNotIn = $this->InNotInArgument($mValue, $vArguments["needle:".$aKeys[1]], $sBreaker);
									$sPreIf .= $aInNotIn[0];
									$sCaseCondition = $aInNotIn[1];
								}
								break;
						}

						if(isset($sCaseCondition)) {
							if(!isset($aConditions[$aKeys[1]])) {
								$aConditions[$aKeys[1]] = $sCaseCondition;
							} else {
								$aConditions[$aKeys[1]] = ($aKeys[0]=="isset") ? "(".$sCaseCondition.") && (".$aConditions[$aKeys[1]].")" : "(".$aConditions[$aKeys[1]].") && (".$sCaseCondition.")";
							}

							$aIfStatements[$aKeys[1]] = $vArguments["then:".$aKeys[1]];
							unset($sCaseCondition);
						}
					}
				}
			} else {
				if(isset($vArguments["iff"])) {
					$aConditions[] = \trim($vArguments["iff"], " \t\n\r\0\x0B");
					$aIfStatements[] = (isset($vArguments["then"])) ? $vArguments["then"] : $vArguments["content"];
				} else {
					$aIfStatements[] = (\strpos($sKeys, "then")>-1) ? $vArguments["then"] : $vArguments["content"];
				}

				if(isset($vArguments["empty"]) || isset($vArguments["noempty"])) {
					$sSign = "";
					if(isset($vArguments["noempty"])) {
						$vArguments["empty"] = $vArguments["noempty"];
						$sSign = "!";
					}
					$sEmpty = \trim($vArguments["empty"], " \t\n\r\0\x0B");
					$sEmpty = "\x12heredoc>".$sEmpty."\x12\x11heredoc>";
					$sEmpty = $sSign."empty(".$sEmpty.")";

					if(!\count($aConditions)) {
						$aConditions[] = $sEmpty;
					} else if(\is_array($aConditions) && \count($aConditions)==1) {
						$aConditions[0] = "(".$sEmpty.") && (".$aConditions[0].")";
					}
				}

				if(isset($vArguments["isset"])) {
					$sIssetArgument = \trim($vArguments["isset"], " \t\n\r\0\x0B");
					
					$sIsset = $this->IssetArgument($sIssetArgument);
					if(!\count($aConditions)) {
						$aConditions[] = $sIsset;
					} else if(\is_array($aConditions) && \count($aConditions)==1) {
						$aConditions[0] = "(".$sIsset.") && (".$aConditions[0].")";
					}
				}

				if((isset($vArguments["in"]) || isset($vArguments["notin"])) && isset($vArguments["needle"])) {
					if(isset($vArguments["notin"])) {
						$vArguments["in"] = $vArguments["notin"];
						$sSign = "!";
					}
					$sBreaker = (isset($vArguments["splitter"])) ? $vArguments["splitter"] : ";";
					$aInNotIn = $this->InNotInArgument($vArguments["in"], $vArguments["needle"], $sBreaker);
					$sPreIf = $aInNotIn[0];
					if(!\count($aConditions)) {
						$aConditions[] = $sSign.$aInNotIn[1];
					} else if(\is_array($aConditions) && \count($aConditions)==1) {
						$aConditions[0] = "(".$sSign.$aInNotIn[1].") && (".$aConditions[0].")";
					}
				}
			}

			$sReturn = $sPreIf;
			if(!\count($aConditions)) {
				$aInline = $this->IfcaseInline($aIfStatements[0]);
				$aConditions[0] = $aInline[0];
				$aIfStatements[0] = $aInline[1];
			}
			
			$bSetMode = ((isset($vArguments["setmode"]) && self::call()->isTrue($vArguments["setmode"])));
			
			$sStatementsOpen	= (!$bSetMode) ? "]PHP]>" : " return (\"";
			$sStatementsClose	= (!$bSetMode) ? "<[PHP[" : "\"); ";
			if(\is_array($aConditions) && \count($aConditions)==1) {
				$mKey = \key($aConditions);
				$sReturn .= 'if('.$aConditions[$mKey].') {'.$sStatementsOpen.$aIfStatements[$mKey].$sStatementsClose.'}';
			} else {
				$mKey = \key($aConditions);
				$sReturn .= 'if('.$aConditions[$mKey].') {'.$sStatementsOpen.$aIfStatements[$mKey].$sStatementsClose.'}';
				\next($aConditions);
				while($mCondition = \current($aConditions)) {
					$mKey = \key($aConditions);
					$sReturn .= 'else if('.$mCondition.') {'.$sStatementsOpen.$aIfStatements[$mKey].$sStatementsClose.'}';
					\next($aConditions);
				}
			}

			if(isset($vArguments["else"])) {
				$sReturn .= 'else {'.$sStatementsOpen.$vArguments["else"].$sStatementsClose.'}';
			}
			
			if($bSetMode) {
				$sReturn = '".eval(\''.\str_replace("'", "\'", $sReturn).'\')."';
			} else {
				$sReturn = '<[PHP['.$sReturn .']PHP]>';
			}

			return $sReturn;
		}

		/** FUNCTION {
			"name" : "rindIncFile", 
			"type" : "private",
			"description" : "Retorna el código que incluye un archivo válido en <b>$_SET["INCLUDES"]</b>, en las plantillas.",
			"parameters" : { "$vArguments" : ["array", "Argumentos del comando."] },
			"return" : "string"
		} **/
		private function rindIncFile($vArguments) {
			$sReturn	= "";
			$sFileName	= $vArguments["content"];
			$sFileName	= \trim($sFileName);
			if(!empty($sFileName)) {
				$sReturn  = '<[PHP[ if(isset(Rind::this('.$this->RIND_ME.')->SET["INCLUDES"]["'.$sFileName.'"]) && \file_exists(Rind::this('.$this->RIND_ME.')->SET["INCLUDES"]["'.$sFileName.'"])) { include(Rind::this('.$this->RIND_ME.')->SET["INCLUDES"]["'.$sFileName.'"]); }]PHP]>';
			}

			return $sReturn;
		}

		/** FUNCTION {
			"name" : "rindLength", 
			"type" : "private",
			"description" : "Retorna el código que devuelve el largo de un valor usando <b>rind:length</b> en las plantillas.",
			"parameters" : { "$vArguments" : ["array", "Argumentos del comando."] },
			"return" : "string"
		} **/
		private function rindLength($vArguments) {
			$sVarname = $this->dynVar();
			$sNumRows = $this->dynVar();

			$sReturn = '<[FUN[eval(\'
				'.$sVarname.' = '.$vArguments["content"].';
				if(\is_array('.$sVarname.') || \is_object('.$sVarname.')) {
					return \count('.$sVarname.');
				} else if(\is_string('.$sVarname.')) {
					return \strlen('.$sVarname.');
				} else if(\is_numeric('.$sVarname.')) {
					return '.$sVarname.';
				} else if(\is_bool('.$sVarname.')) {
					return ('.$sVarname.') ? 1 : 0;
				}
			\')]FUN]>';

			return $sReturn;
		}

		/** FUNCTION {
			"name" : "rindLoop", 
			"type" : "private",
			"description" : "Retorna los distintos códigos de bucles <b>rind:loop</b> en las plantillas.",
			"parameters" : { "$vArguments" : ["array", "Argumentos del comando."] },
			"return" : "string"
		} **/
		private function rindLoop($vArguments) {
			foreach($vArguments as $sKey => $sArgument) {
				if(empty($sKey)) {
					$vArguments[$sKey] = $this->stripQuotes($sArgument);
				} else {
					\preg_match("/^([ \t\n\r\x0B]*)(.*?)([ \t\n\r\x0B]*)$/", $sArgument, $aSpaces);
					if(!isset($aSpaces[1])) { $aSpaces[1] = ""; }
					if(!isset($aSpaces[3])) { $aSpaces[3] = ""; }
					$sArgument = \trim($sArgument);
					$vArguments[$sKey] = \trim($sArgument, "\x22");
					$vArguments[$sKey] = $aSpaces[1].$vArguments[$sKey].$aSpaces[3];
				}
			}

			// variables dinamicas
			$sDataVar		= $this->dynVar();
			$sDataBackVar	= $this->dynVar();
			$sRowVar		= $this->dynVar();
			$sRowData		= $this->dynVar();
			$sCountVar		= $this->dynVar();
			$sEndVar		= $this->dynVar();
			$sKeysVar		= $this->dynVar();
			$sIndexVar		= $this->dynVar();
			$sOrderVar		= $this->dynVar();
			$nFrom			= $this->dynVar();
			$nLimit			= $this->dynVar();
			$nLimitMax		= $this->dynVar();
			$sSign			= $this->dynVar();
			$sInvSign		= $this->dynVar();
			$sTreeIndex		= $this->dynVar();
			$sTreeTmp		= $this->dynVar();
			$sNut			= $this->dynVar();
			$sCalc			= $this->dynVar();
			$sCalcIdx		= $this->dynVar();
			$sLoops			= $this->RIND_LOOPS;
			
			// para debug ----
			// $sDataVar		= '$aData';
			// $sDataBackVar	= '$aData';
			// $sRowVar			= '$aRow';
			// $sRowData		= '$sRowData';
			// $sCountVar		= '$x';
			// $sEndVar			= '$nData';
			// $sKeysVar		= '$aKeys';
			// $sIndexVar		= '$aIndexLoop';
			// $sOrderVar		= '$sOrderVar';
			// $nFrom			= '$nFrom';
			// $nLimit			= '$nLimit';
			// $nLimitMax		= '$nLimitMax';
			// $sSign			= '$sSign';
			// $sInvSign		= '$sInvSign';
			// $sTreeIndex		= '$sTreeIndex';
			// $sTreeTmp		= '$sTreeTmp';
			// $sNut			= '$sNut';
			// $sCalc			= '$sCalc';
			// $sCalcIdx		= '$sCalcIdx';
			// $sLoops			= '$sLoops';
			
			// datos
			if(!isset($vArguments["source"])) { $vArguments["source"] = ""; }

			// nombre del loop
			if(isset($vArguments["name"])) {
				$sName = \strtolower($vArguments["name"]);
			} else {
				$sName = \strtolower(self::call()->unique(8));
			}
			
			if($sName=="self" || $sName=="parent") {
				self::errorMessage("rind", 1007, $sData);				
			}

			// contenedor de datos
			if(!isset($this->aLoops[$sName])) {
				$this->aLoops[$sName] = $sRowVar;
			} else {
				$sRowVar = $this->aLoops[$sName];
			}

			// contenido del loop
			$sContent = (isset($vArguments["content"])) ? $vArguments["content"] : "";
			$sFormat = $this->LoopVarName($sContent, $sRowVar);

			// tipo de origen de datos
			if(isset($vArguments["type"])) {
				$sType = \strtolower($vArguments["type"]);
				if(!\in_array($sType, ["array", "owl", "element", "number", "object", "vector"])) {
					$sType = "array";	
				}
			} else {
				$sType = "array";
			}

			// en caso de no hallarse resultados
			$sEmpty = (isset($vArguments["empty"])) ? $vArguments["empty"] : null;

			// limite del bucle
			$nStep	= (isset($vArguments["step"])) ? $vArguments["step"] : 1;

			// estructuras para arboles
			$bTreeMode = false;
			if(isset($vArguments["tree"])) {
				$bTreeMode		= true;
				$sChildrenNode	= ($vArguments["tree"]!=="1") ? $vArguments["tree"] : "_children";
				$sBranchOpen	= (isset($vArguments["branch_open"])) ? $this->LoopVarName($vArguments["branch_open"], $sRowVar) : "<ul>";
				$sBranchClose	= (isset($vArguments["branch_close"])) ? $this->LoopVarName($vArguments["branch_close"], $sRowVar) : "</ul>";
				$sNodeOpen		= (isset($vArguments["node_open"])) ? $this->LoopVarName($vArguments["node_open"], $sRowVar) : "<li>";
				$sNodeClose		= (isset($vArguments["node_close"])) ? $this->LoopVarName($vArguments["node_close"], $sRowVar) : "</li>";
			}

			// armado del nombre del origien de datos
			$sDataArgument = \str_replace($this->RIND_HTML_QUOTE, '"', $vArguments["source"]);
			$sDataArgument = \trim($sDataArgument);
			$sDataArgument = \trim($sDataArgument, "\x22");
			$sSourceData = '@'.$sDataVar.' = '.$sDataArgument.';'.$this->EOL;

			switch($sType) {
				case "number":
					$sChkType = true;
					$sRowSource = null;
					$sSourceData = "";
					break;

				case "owl":
				case "element":
					$sChkType = '\is_object('.$sDataVar.')';
					$sSetting = $sEndVar.' = (\method_exists('.$sDataVar.', '.$this->RIND_QUOTE.'rows'.$this->RIND_QUOTE.')) ? '.$sDataVar.'->rows() : 0;';
					$sRowSource = '(\method_exists('.$sDataVar.', '.$this->RIND_QUOTE.'get'.$this->RIND_QUOTE.')) ? '.$sDataVar.'->get() : []';
					break;

				default:
					if($sType=="object") {
						$sSourceData = $sDataVar.' = Rind::obj2array("'.$vArguments["source"].'");';
					} else if($sType=="vector") {
						$sSourceData .= $sDataVar.' = ['.$sDataVar.'];';
					}

					$sChkType = '\is_array('.$sDataVar.')';
					$sSetting = $sEndVar.' = \count('.$sDataVar.');';
					$sRowSource = $sDataVar.'['.$sKeysVar.'['.$sCountVar.']];';
					break;
			}
			
			// order de impresion
			if($sType!="owl" && $sType!="number" && $sType!="element") {
				if(isset($vArguments["index"])) {
					$sSetting .= $sIndexVar.' = "'.$vArguments["index"].'"; '.$sKeysVar.' = (\is_array('.$sIndexVar.')) ? '.$sIndexVar.' : \explode(",",'.$sIndexVar.');'.$this->EOL;
					$sSetting .= $sIndexVar.' = "'.$vArguments["index"].'";'.$this->EOL.$sKeysVar.' = (\is_array('.$sIndexVar.')) ? '.$sIndexVar.' : \explode(",",'.$sIndexVar.');'.$this->EOL;
				} else {
					$sSetting .= $sKeysVar.' = \array_keys('.$sDataVar.');'.$this->EOL;
				}
				
				if(isset($vArguments["order"])) {
					$sSetting .= $this->EOL.$sOrderVar.' = \strtolower("'.$vArguments["order"].'");'.$this->EOL;
					$sSetting .= 'if('.$sOrderVar.'=="reverse") { '.$sKeysVar.' = \array_reverse('.$sKeysVar.'); } '.$this->EOL;
					$sSetting .= 'else if('.$sOrderVar.'=="random") { \shuffle('.$sKeysVar.'); } '.$this->EOL;
					$sSetting .= 'else if('.$sOrderVar.'=="desc") { \rsort('.$sKeysVar.'); } '.$this->EOL;
					$sSetting .= 'else { \sort('.$sKeysVar.'); }'.$this->EOL;
				}
				
				$sSetting .= $sEndVar.' = \count('.$sKeysVar.');'.$this->EOL;;
			}

			//=================
			// Codigo del Loop
			//=================
			$sLoop  = '<[PHP['.$this->EOL;
			$sLoop .= $sSourceData;
			
			$sLoop .= 'if(!isset('.$sLoops.')) { '.$sLoops.' = []; }'.$this->EOL;
			$sLoop .= 'if(\is_array('.$sLoops.') && \count('.$sLoops.')) { '.$this->aLoops["parent"].' = Rind::this('.$this->RIND_ME.')->SET[\end('.$sLoops.')]; }'.$this->EOL;
			$sLoop .= '\array_push('.$sLoops.', "'.$sName.'");'.$this->EOL;

			$sLoop .= 'if(isset('.$sDataVar.')) { '.$sDataBackVar.' = '.$sDataVar.'; }'.$this->EOL;
			$sLoop .= 'if('.$sChkType.') {'.$this->EOL;

			// origen de datos
			if($sType=="owl" || $sType=="element") {
				$sLoop .= 'if(\method_exists('.$sDataVar.', '.$this->RIND_QUOTE.'reset'.$this->RIND_QUOTE.')) { '.$sDataVar.'->reset(); }'.$this->EOL;
				$sLoop .= 'if(\method_exists('.$sDataVar.', '.$this->RIND_QUOTE.'allrows'.$this->RIND_QUOTE.')) { Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["allrows"] = '.$sDataVar.'->allrows(); }'.$this->EOL;
			} else if($sType!="number") {
				$sLoop .= '\reset('.$sDataVar.');'.$this->EOL;
			} else {
				$sLoop .= "\n";
			}

			// limites del bucle
			$bFrom = $bLimit = false;
			if(isset($vArguments["from"])) { $bFrom = true; $sLoop .= $nFrom.'=\abs('.$vArguments["from"].');'; } else { $sLoop .= $nFrom.'=0;'; }
			if(isset($vArguments["limit"])) { $bLimit = true; $sLoop .= $nLimit.'=\abs('.$vArguments["limit"].');'; } else { $sLoop .= $nLimit.'=null;'; }
			if($sType=="number") {
				$sLoop .= $nLimitMax.'='.$this->argument("loops_limit").';'.$this->EOL;
				$sLoop .= 'if('.$nLimit.'===null) { '.$nLimit.' = 1; }'.$this->EOL;
				$sLoop .= 'if('.$nLimit.'>'.$nLimitMax.') { '.$nLimit.' = '.$nLimitMax.'; }'.$this->EOL;
				$sSetting = $sEndVar.' = ('.$nStep.'<0) ? '.$nFrom.'-'.$nLimit.' : '.$nFrom.'+'.$nLimit.';'.$this->EOL;
			}
			
			$sLoop .= $sSetting.$this->EOL;
			$sLoop .= 'if(!'.$sEndVar.') { ]PHP]>'.$sEmpty.'<[PHP[ }'.$this->EOL;
			$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"] = []; '.$this->EOL;

			if($sType=="owl" || $sType=="element") {
				$sLoop .= 'if(\method_exists('.$sDataVar.', '.$this->RIND_QUOTE.'allrows'.$this->RIND_QUOTE.')) { Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["allrows"] = '.$sDataVar.'->allrows(); }'.$this->EOL;
			} else {
				$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["allrows"] = '.$sEndVar.'; '.$this->EOL;
			}

			$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["numrows"] = '.$sEndVar.'; '.$this->EOL;
			$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["lines"] = '.$sEndVar.'; '.$this->EOL;
			
		
			// indice para arboles
			if($bTreeMode) { $sLoop .= $sTreeIndex.' = [];'.$this->EOL; }

			// loop
			$sLoop .= $sSign.'= ('.$nLimit.'!==null) ? ((('.$nLimit.'+'.$nFrom.')>'.$nFrom.') ? '.$this->RIND_QUOTE.'<'.$this->RIND_QUOTE.' : '.$this->RIND_QUOTE.'>'.$this->RIND_QUOTE.') : '.$this->RIND_QUOTE.'<'.$this->RIND_QUOTE.";";
			$sLoop .= $sInvSign.' = ('.$nStep.'<0) ? "!" : "";'.$this->EOL;
			
			// inicio de arboles (cuando existan)
			if($bTreeMode) { $sLoop .= ']PHP]>'.$sBranchOpen.'<[PHP['.$this->EOL;}

			// acumulados
			if(isset($vArguments["aggregate"])) {
				$sLoop .= $sCalc.'=["'.\implode('","', self::call()->explodeTrim(",",$vArguments["aggregate"])).'"];'.$this->EOL;
				$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["sum"] = '.$this->EOL;
				$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["avg"] = '.$this->EOL;
				$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["max"] = \array_fill_keys('.$sCalc.', 0);'.$this->EOL;
				$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["min"] = \array_fill_keys('.$sCalc.', PHP_INT_MAX);'.$this->EOL;
			}

			// inicio del loop
			$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["previous"] = null;'.$this->EOL;
			$sLoop .= 'for('.$sCountVar.'='.$nFrom.'; eval('.$this->RIND_QUOTE.'return '.$this->RIND_QUOTE.'.'.$sInvSign.'.'.$this->RIND_QUOTE.'('.$this->RIND_QUOTE.'.'.$sCountVar.'.'.$sSign.'.'.$sEndVar.'.'.$this->RIND_QUOTE.');'.$this->RIND_QUOTE.'); '.$sCountVar.'+='.$nStep.') {'.$this->EOL;

				// limite
				if($bLimit) {
					$sLoop .= 'if('.$sCountVar.'==('.$nLimit.'+'.$nFrom.')) { break; }'.$this->EOL;
					$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["limit"] = '.$nLimit.';'.$this->EOL;	
				}
				
				// desde
				if($bFrom) {
					$sLoop .= 'if(eval('.$this->RIND_QUOTE.'return '.$this->RIND_QUOTE.'.'.$sInvSign.'.'.$this->RIND_QUOTE.'('.$this->RIND_QUOTE.'.'.$sCountVar.'.'.$sSign.'.'.$nFrom.'.'.$this->RIND_QUOTE.');'.$this->RIND_QUOTE.')) { continue; }'.$this->EOL;
					$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["from"] = '.$nFrom.';'.$this->EOL;	
				}

				// origen de datos
				if($sRowSource) {
					$sLoop .= $sRowData.' = '.$sRowSource.';'.$this->EOL;
					if($sType!="owl" && $sType!="element") {
						$sLoop .= 'if(!\array_key_exists('.$sKeysVar.'['.$sCountVar.'], '.$sDataVar.')) { continue; }'.$this->EOL;
					}
					$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["data"] = (\is_array('.$sRowData.')) ? '.$sRowData.' : ['.$sRowData.'];'.$this->EOL;
					$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["current"] = \current(Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["data"]); \reset(Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["data"]);'.$this->EOL;
				} else {
					$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["data"] = '.$sCountVar.';'.$this->EOL;
					$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["current"] = '.$sCountVar.';'.$this->EOL;
				}

				// fila actual
				if(isset($vArguments["aggregate"])) {
					$sLoop .= "foreach(".$sCalc." as ".$sCalcIdx.") {\n";
					$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["sum"]['.$sCalcIdx.'] += '.$sRowData.'['.$sCalcIdx.'];'.$this->EOL;
					$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["avg"]['.$sCalcIdx.'] = (Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["sum"]['.$sCalcIdx.'] / ('.$sCountVar.'+1));'.$this->EOL;
					$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["min"]['.$sCalcIdx.'] = \min('.$sRowData.'['.$sCalcIdx.'], Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["min"]['.$sCalcIdx.']);'.$this->EOL;
					$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["max"]['.$sCalcIdx.'] = \max('.$sRowData.'['.$sCalcIdx.'], Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["max"]['.$sCalcIdx.']);'.$this->EOL;
					$sLoop .= "}\n";
				}

				if(isset($vArguments["zerofill"])) {
					$nZerofill = (int)$vArguments["zerofill"];
					$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["numrow"] = \str_pad('.$sCountVar.', '.$nZerofill.', "0", STR_PAD_LEFT);'.$this->EOL;
					$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["line"] = \str_pad(('.$sCountVar.'+1), '.$nZerofill.', "0", STR_PAD_LEFT);'.$this->EOL;
				} else {
					$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["numrow"] = '.$sCountVar.';'.$this->EOL;
					$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["line"] = ('.$sCountVar.'+1);'.$this->EOL;
				}
				
				
				$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["odd"] = ('.$sCountVar.'%2) ? 0 : 1;'.$this->EOL;
				$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["parity"] = ('.$sCountVar.'%2) ? "even" : "odd";'.$this->EOL;

				$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["first"] = ('.$sCountVar.'=='.$nFrom.') ? 1 : 0;'.$this->EOL;
				$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["last"] = (Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["numrows"]==Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["line"]) ? 1 : 0;'.$this->EOL;

				// nombre de la clave activa (solo en caso de arrays)
				if($sType!="owl" && $sType!="number" && $sType!="element") {
					$sLoop .= 'Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["key"] = '.$sKeysVar.'['.$sCountVar.'];'.$this->EOL;
				}
				
				$sLoop .= $sRowVar.' = '.$this->aLoops["self"].' = Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"];'.$this->EOL;
				$sLoop .= ']PHP]>';
				
				// debug
				if((isset($vArguments["debug"]) && self::call()->isTrue($vArguments["debug"]))) {
					$sLoop .= '<[PHP[';
					$sLoop .= 'echo "<pre>";';
					$sLoop .= 'echo \htmlentities(\print_r(Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"], 1), ENT_IGNORE);';
					$sLoop .= 'echo "</pre>";';
					$sLoop .= "break;";
					$sLoop .= ']PHP]>';
				}
			
				// impresion del resultado
				if($bTreeMode) { $sLoop .= $sNodeOpen; }
				$sLoop .= $sFormat;

				// recursion de arboles
				if($bTreeMode) {
					$sLoop .= '<[PHP['.$this->EOL;
					$sLoop .= '	if(isset('.$sRowVar.'["data"]["'.$sChildrenNode.'"]) && \is_array('.$sRowVar.'["data"]["'.$sChildrenNode.'"])) {';
					$sLoop .= '		'.$sTreeIndex.'[] = ['.$sDataVar.', '.$sCountVar.'];';
					$sLoop .= '		'.$sDataVar.' = '.$sRowVar.'["data"]["'.$sChildrenNode.'"];';
					$sLoop .= '		'.$sKeysVar.' = \array_keys('.$sDataVar.');';
					$sLoop .= '		'.$sEndVar.' = \count('.$sDataVar.');';
					$sLoop .= '		'.$sCountVar.' = -1;';

					$sLoop .= '		]PHP]>'.$sBranchOpen.'<[PHP[';
					$sLoop .= '		continue;';
					$sLoop .= '	}'.$this->EOL;

					$sLoop .= '		]PHP]>'.$sNodeClose.'<[PHP[';

					$sLoop .= '	if('.$sCountVar.'=='.$sEndVar.'-1) {';
					$sLoop .= '		while(!('.$sEndVar.'>'.$sCountVar.'+1) && \count('.$sTreeIndex.')) {';
					$sLoop .= '			'.$sTreeTmp.' = \array_pop('.$sTreeIndex.');';
					$sLoop .= '			'.$sDataVar.' = '.$sTreeTmp.'[0];';
					$sLoop .= '			'.$sKeysVar.' = \array_keys('.$sDataVar.');';
					$sLoop .= '			'.$sEndVar.' = \count('.$sDataVar.');';
					$sLoop .= '			'.$sCountVar.' = '.$sTreeTmp.'[1];';

					$sLoop .= '			]PHP]>'.$sBranchClose.'<[PHP[';
					$sLoop .= '			]PHP]>'.$sNodeClose.'<[PHP[';
					$sLoop .= '		}';
					$sLoop .= '		continue;';
					$sLoop .= '	}'.$this->EOL;
					$sLoop .= ']PHP]>'.$this->EOL;
				}
		
			// fin del loop
			$sLoop .= '<[PHP[ Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["previous"] = Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["data"]; ]PHP]>'.$this->EOL;

			$sLoop .= '<[PHP[ }'.$this->EOL;
			
			// fin de arboles (cuando existan)
			if($bTreeMode) { $sLoop .= ']PHP]>'.$sBranchClose.'<[PHP['.$this->EOL;}
			
			// reset de los datos del loop
			$sLoop .= 'if(isset('.$sDataBackVar.')) { Rind::this('.$this->RIND_ME.')->SET["'.$sName.'"]["data"] = '.$sDataBackVar.'; }'.$this->EOL;

			// resultado vacio
			if(!$sEmpty) {
				$sLoop .= '}]PHP]>';
			} else {
				$sLoop .= '} else { ]PHP]>';
				$sLoop .= $sEmpty;
				$sLoop .= '<[PHP[ }]PHP]>';
			}

			// vuelta al loop padre
			$sLoop .= '<[PHP[';
			$sLoop .= '\array_pop('.$sLoops.');'.$this->EOL;
			$sLoop .= 'if(\is_array('.$sLoops.') && \count('.$sLoops.')) {'.$this->EOL;
			$sLoop .= 		$this->aLoops["self"].' = Rind::this('.$this->RIND_ME.')->SET[\end('.$sLoops.')];'.$this->EOL;
			$sLoop .= 		'if(\prev('.$sLoops.')) { \end('.$sLoops.'); '.$this->aLoops["parent"].' = Rind::this('.$this->RIND_ME.')->SET[\prev('.$sLoops.')]; }'.$this->EOL;
			$sLoop .= '	}'.$this->EOL;
			$sLoop .= ']PHP]>'.$this->EOL;

			return $sLoop;
		}

		/** FUNCTION {
			"name" : "rindMergeFile", 
			"type" : "private",
			"description" : "Detecta y ejecuta las inclusiones de sub-plantillas en la plantilla principal.",
			"parameters" : { "$vArguments" : ["array", "Argumentos del comando."] },
			"return" : "string"
		} **/
		private function rindMergeFile($vArguments) { //dodo
			$sTemplate			= $this->dynVar();
			$sKey 				= $this->dynVar();
			$mValue				= $this->dynVar();
			$sFile				= $this->dynVar();
			$sGui				= $this->dynVar();
			$sGuiFile			= $this->dynVar();
			$sGuiIsDir			= $this->dynVar();
			$aStructure 		= $this->dynVar();
			$aTemplate 			= $this->dynVar();
			$sSubTemplate		= $this->dynVar();
			$sSubTarget			= $this->dynVar();
			$sSubTargetContent	= $this->dynVar();
			$sDebug				= $this->dynVar();

			$sFilePath	= (isset($vArguments["source"])) ? $vArguments["source"] : ((isset($vArguments["content"])) ? $vArguments["content"] : "");
			$nSourceDir	= (\substr($sFilePath,-1)==NGL_DIR_SLASH) ? "1" : "0";
			$sFilePath	= self::call()->clearPath($sFilePath);
			$sFilePath .= ($nSourceDir) ? NGL_DIR_SLASH : "";
			if(empty($sFilePath)) { self::errorMessage($this->object, 1003, "mergefile::source = ".$sFilePath); }
			$sGUIPath	= ($sFilePath[0]==NGL_DIR_SLASH) ? $this->attribute("project_path") : $this->attribute("gui_path");
			$sGUIPath	= self::call()->clearPath($sGUIPath, true);
			$sCacheMode = \strtolower($this->argument("cache_mode"));

			$aSubMerge = false;
			if(isset($vArguments["submerge"])) {
				$sSubMerge = \base64_decode($vArguments["submerge"]);
				$aSubMerge = \explode(":", $sSubMerge);
			}

			$aFiles = $this->GetDataArguments($vArguments, $sFilePath, $aSubMerge);
			$sTemplates = \json_encode($aFiles);
			// echo $sTemplates;
			// echo $sFilePath;
			// print_r($aFiles);
			// exit();
			\array_push($this->aMergeTail,$aFiles[0][1]);

			// dodo
			$sReturn  = '<[PHP[';
			$sReturn .= $sTemplate." = [];".$this->EOL;
			$sReturn .= $aStructure." = \json_decode('".$sTemplates."', true);".$this->EOL;
			if(\is_array($aSubMerge)) { $sReturn .= $sSubTarget.' = "'.$aSubMerge[0].'";'.$this->EOL; }

			if($aSubMerge) {
				foreach($aFiles[$aSubMerge[0]] as $aTpl) {
					$sNowDoc = self::call()->unique(6);
					$sTemplatePath = self::call()->unique(6);
					$sTemplatePath = (\substr($aTpl[0],-1)!=NGL_DIR_SLASH && \strtolower(\substr($aTpl[0],-5))!=".html") ? $aTpl[0].".html" : $aTpl[0];
					if(!isset($this->RIND_TEMPLATESLOG[$aTpl[0]])) {
						if($nSourceDir) { $sTemplatePath = $sFilePath.NGL_DIR_SLASH.$sTemplatePath; }
						$sSubTemplateContent = $this->readTemplate($sGUIPath.$sTemplatePath);
						$sSubTemplateContent = $this->rind2php($sSubTemplateContent);
						$this->RIND_TEMPLATESLOG[$aTpl[0]] = true;
						$this->sMergeFiles .= $this->RIND_TEMPLATES."['".$aTpl[0]."'] = <<<'".$sNowDoc."'\n".$sSubTemplateContent."\n".$sNowDoc.";\n";
					}
				}
			}

			foreach($aFiles as $mKey => $aTpl) {
				if($aSubMerge && $mKey===$aSubMerge[0]) { continue; }
				$sNowDoc = self::call()->unique(6);
				$sTemplatePath = self::call()->unique(6);
				$sTemplatePath = (\substr($aTpl[0],-1)!=NGL_DIR_SLASH && \strtolower(\substr($aTpl[0],-5))!=".html") ? $aTpl[0].".html" : $aTpl[0];
				if(!isset($this->RIND_TEMPLATESLOG[$aTpl[0]])) {
					if($nSourceDir) { $sTemplatePath = $sFilePath.NGL_DIR_SLASH.$sTemplatePath; }
					$sSubTemplateContent = $this->readTemplate($sGUIPath.$sTemplatePath);
					$sSubTemplateContent = $this->rind2php($sSubTemplateContent);
					// echo $sSubTemplateContent."\n\n";
					$this->RIND_TEMPLATESLOG[$aTpl[0]] = true;
					$this->sMergeFiles .= $this->RIND_TEMPLATES."['".$aTpl[0]."'] = <<<'".$sNowDoc."'\n".$sSubTemplateContent."\n".$sNowDoc.";\n";
					// dodo
				}
			}

			// -- multiple target INI
			if($aSubMerge) {
				$sReturn .= 	$sSubTargetContent.' = [];'.$this->EOL;
				$sReturn .= 	'foreach('.$aStructure.'['.$sSubTarget.'] as '.$aTemplate.') {'.$this->EOL;
				$sReturn .= 		$sSubTargetContent."[] = Rind::mergetemplate(".$aTemplate.", \"".\base64_encode(\json_encode($this->aMergeTail))."\", [".$this->RIND_TEMPLATES."]);".$this->EOL;
				$sReturn .= 	'}'.$this->EOL;
				$sReturn .= 	'unset('.$aStructure.'['.$sSubTarget.']);'.$this->EOL;
				$sReturn .= 	$aStructure.'[0][1]['.$sSubTarget.'] = \base64_encode(\implode(\chr(10), '.$sSubTargetContent.'));'.$this->EOL;
			}
			// -- multiple target END

			$sReturn .= 'foreach('.$aStructure.' as '.$aTemplate.') {'.$this->EOL;
			$sReturn .= 	$sTemplate."[] = Rind::mergetemplate(".$aTemplate.", \"".\base64_encode(\json_encode($this->aMergeTail))."\", [".$this->RIND_TEMPLATES."]);".$this->EOL;
			$sReturn .= "}";
			$sReturn .= $sTemplate.' = \implode(chr(10), '.$sTemplate.');'.$this->EOL;
			$sReturn .= $sTemplate." = \preg_replace('/\{\%mergeid\%\}/is', Rind::unique(8), ".$sTemplate.");".$this->EOL;
			$sReturn .= $sTemplate." = \preg_replace('/\{\%[a-z0-9\_\-]+\%\}/is', '', ".$sTemplate.");".$this->EOL;

			$sReturn .= "eval('?>'.".$sTemplate.");".$this->EOL;
			$sReturn .= ']PHP]>';

			\array_pop($this->aMergeTail);
			return $sReturn;
		}

		/** FUNCTION {
			"name" : "rindRtn", 
			"type" : "private",
			"description" : "Retorna el código de una expresión de <b>rind:rtn</b> en las plantillas.",
			"parameters" : { "$vArguments" : ["array", "Argumentos del comando."] },
			"return" : "string"
		} **/
		private function rindRtn($vArguments) {
			$sReturn = $vArguments["content"];
			$sReturn = \str_replace(array('".<[VAR[', ']VAR]>."'), array('"".<[VAR[', ']VAR]>.""'), $sReturn);
			$sReturn = '<[FUN['.$sReturn.']FUN]>';
			// die($sReturn);
			return $sReturn;
		}

		/** FUNCTION {
			"name" : "rindSet", 
			"type" : "private",
			"description" : "Retorna el código que setea una variable en <b>$_SET</b> usando <b>rind:set</b> en las plantillas.",
			"parameters" : { "$vArguments" : ["array", "Argumentos del comando."] },
			"return" : "string"
		} **/
		private function rindSet($vArguments, $sBaseName=null) {
			if(!isset($vArguments["name"])) { return false; }

			// nombre (las mayusculas estan reservadas para las variables SET privadas)
			$sVarName = \preg_replace("/[^a-z0-9_\{\}\%]/i", "", $vArguments["name"]);
			$sVarName = \strtolower($sVarName);
			if($sBaseName) { $sVarName = $sBaseName.".".$sVarName; }
			
			// valor
			$sDataVar = $this->dynVar();
			$mValue = (isset($vArguments["value"])) ? $vArguments["value"] : 'NGL_NULL';
			$mValue = \str_replace($this->RIND_HTML_QUOTE, '"', $mValue);
			$mValue = \trim($mValue);
			$mValue = \trim($mValue, "\x22");

			// metodo
			if(isset($vArguments["method"])) {
				if(\strpos($vArguments["method"], ",")!==false) {
					$aMethods = self::call()->explodeTrim(",", $vArguments["method"]);
					$sMethod = $aMethods[0];
				} else {
					$sMethod = $vArguments["method"];
					$aMethods = [$sMethod];
				}

				if(\preg_match("/^\x12json>(.*)\x12\x11json>$/is", $mValue)) {
					$sReturn = $sDataVar.' = '.$mValue.';';
				} else {
					$sReturn = $sDataVar.' = "'.$mValue.'";'.$this->EOL;
				}

				if(!isset($vArguments["delimiter"])) { $vArguments["delimiter"] = ","; }
				
				// metodos
				$vArguments["value"] = $mValue;
				$sReturn .= $this->rindSetMethods($aMethods, $sDataVar, $vArguments, $sVarName);
			} else if(\preg_match("/^\x12json>(.*)\x12\x11json>$/is", $mValue)) {
				$sReturn = $sDataVar.' = '.$mValue.';';
			} else {
				$sReturn = $sDataVar.' = "'.$mValue.'";'.$this->EOL;
			}

			// operador
			$sOperator = (!isset($vArguments["operator"])) ? "=" : $vArguments["operator"][0]."=";
			if(!\in_array($sOperator, ["=", ".=", "+=", "-=", "*=", "/=", "%="])) { $sOperator = "="; }
			
			$sReturn = "<[PHP[\n".$sReturn."\n".'Rind::this('.$this->RIND_ME.')->SET['.$this->RIND_QUOTE.$sVarName.$this->RIND_QUOTE.'] '.$sOperator.' '.$sDataVar.";]PHP]>\n";

			return $sReturn;
		}
		
		private function rindSetMethods($aMethods, $sDataVar, $vArguments, $sVarName) {
			$sReturn = "";
			$sEachVar = $this->dynVar();
			foreach($aMethods as $mMethod) {
				if(\strtolower($mMethod)=="each") {
					$sEachKey = $this->dynVar();
					$sReturn .= '
						'.$sEachVar.' = '.$sDataVar.";\n".'
						foreach('.$sEachVar.' as '.$sEachKey.' => '.$sDataVar.') {'."\n".'
					';
					continue;
				}
				if(\is_numeric($mMethod) || \strpos($mMethod,":") || \strpos($mMethod,"|") || $mMethod[0]=="[") {
					$nPosition = $this->dynVar();
					if(\strpos($mMethod,":")) {
						$aMethod = \explode(":", $mMethod);
						$nIndex = (int)$aMethod[0];
						$nLength = (int)$aMethod[1];
					} else if(!empty($mMethod) && $mMethod[0]=="[") {
						$sIndex = \substr($mMethod, 1, -1);
					} else if(\strpos($mMethod,"|")) {
						$aPipes = $this->dynVar();
					} else {
						$nIndex = (int)$mMethod;
						$nLength = 1;
					}
					
					if(isset($aPipes)) {
						$sReturn .= '
							'.$aPipes.' = \explode("|", "'.$mMethod.'");
							if(\end('.$aPipes.')==="") { \array_pop('.$aPipes.'); } \reset('.$aPipes.'); 
							'.$aPipes.' = \array_map(function ($p) { return (\is_numeric($p)) ? ($p-1) : $p; }, '.$aPipes.');
							if(\is_string('.$sDataVar.')) { '.$sDataVar.' = \str_split('.$sDataVar.'); }
							'.$sDataVar.' = \array_map(function ($a) use ('.$sDataVar.') { return (\array_key_exists($a, '.$sDataVar.')) ? '.$sDataVar.'[$a] : ""; }, '.$aPipes.');
						';
					} else if(isset($sIndex)) {
						$sReturn .= $sDataVar.' = isset('.$sDataVar.'["'.$sIndex.'"]) ? '.$sDataVar.'["'.$sIndex.'"] : null;';
					} else {
						$sReturn .= '
							'.$nPosition.' = ('.$nIndex.'<0) ? '.$nIndex.' : ('.$nIndex.'-1);
							if(\is_array('.$sDataVar.')) {
								'.$sDataVar.' = \array_slice('.$sDataVar.', ('.$nPosition.'), '.$nLength.');
								if('.$nLength.'===1 && !isset('.$sEachVar.')) { '.$sDataVar.' = \current('.$sDataVar.'); }
							} else if(\is_string('.$sDataVar.')) {
								'.$sDataVar.' = \substr('.$sDataVar.', ('.$nPosition.'), '.$nLength.');
							}
						';
					}
				} else {
					switch(\strtolower($mMethod)) {
						case "file":
								$sAuthorization = $this->dynVar();
								$sBodyContent = $this->dynVar();
								$sFilePath = self::call()->clearPath($vArguments["value"]);
								$sAuth = (isset($vArguments["userpwd"])) ? $vArguments["userpwd"] : "0";
								$sBody = (isset($vArguments["body"])) ? $vArguments["body"] : "0";
								$sReturn .= $sAuthorization.' = "'.$sAuth.'";'.$this->EOL;
								$sReturn .= $sBodyContent.' = '.$sBody.';'.$this->EOL;
								$sReturn .= $sDataVar.' = Rind::readFile("'.$sFilePath.'", null, '.$sAuthorization.', '.$sBodyContent.'); ';
							break;

						case "element":
							$sReturn .= '
								if(\method_exists('.$sDataVar.', '.$this->RIND_QUOTE.'get'.$this->RIND_QUOTE.')) {
									'.$sDataVar.' = '.$sDataVar.'->get();
								}
							';
							break;

						case "elements":
							$sReturn .= '
								if(\method_exists('.$sDataVar.', '.$this->RIND_QUOTE.'getall'.$this->RIND_QUOTE.')) {
									'.$sDataVar.' = '.$sDataVar.'->getall();
								}
							';
							break;
							
						case "explode":
							$sBreaker = isset($vArguments["splitter"]) ? $this->RIND_QUOTE.$vArguments["splitter"].$this->RIND_QUOTE : $this->RIND_QUOTE.",".$this->RIND_QUOTE;
							$sLineBreak = isset($vArguments["linebreak"]) ? $this->RIND_QUOTE.$vArguments["linebreak"].$this->RIND_QUOTE : "\\r\\n";
							$sReturn .= $sDataVar.' = Rind::stringToArray('.$sDataVar.', '.$sBreaker.', "'.$sLineBreak.'");';
							if(!isset($vArguments["linebreak"])) { $sReturn .= $sDataVar.' = \current('.$sDataVar.');'; }
							break;

						case "implode":
							$sGlue = isset($vArguments["splitter"]) ? $this->RIND_QUOTE.$vArguments["splitter"].$this->RIND_QUOTE : $this->RIND_QUOTE.",".$this->RIND_QUOTE;
							$sReturn .= $sDataVar.' = Rind::this('.$this->RIND_ME.')->SET['.$this->RIND_QUOTE.$sVarName.$this->RIND_QUOTE.'] = (\is_array('.$sDataVar.')) ? \implode('.$sGlue.', '.$sDataVar.') : '.$sDataVar.';';
							break;

						case "base64enc":
							$sReturn .= $sDataVar.' = \base64_encode('.$sDataVar.');';
							break;

						case "base64dec":
							$sReturn .= $sDataVar.' = \base64_decode('.$sDataVar.');';
							break;

						case "serialenc":
							$sReturn .= $sDataVar.' = \serialize('.$sDataVar.');';
							break;

						case "serialdec":
							$sReturn .= $sDataVar.' = \unserialize('.$sDataVar.');';
							break;

						case "rawurlenc":
							$sReturn .= $sDataVar.' = \rawurlencode('.$sDataVar.');';
							break;

						case "rawurldec":
							$sReturn .= $sDataVar.' = \rawurldecode('.$sDataVar.');';
							break;

						case "urlenc":
							$sReturn .= $sDataVar.' = \urlencode('.$sDataVar.');';
							break;

						case "urldec":
							$sReturn .= $sDataVar.' = \urldecode('.$sDataVar.');';
							break;

						case "queryenc":
							$sReturn .= $sDataVar.' = \http_build_query('.$sDataVar.');';
							break;

						case "querydec":
							$aParsed = $this->dynVar();
							$sReturn .= '\parse_str('.$sDataVar.', '.$aParsed.');';
							$sReturn .= $sDataVar.' = '.$aParsed.';';
							break;

						case "jsonenc":
							$sReturn .= $sDataVar.' = \json_encode('.$sDataVar.', \JSON_HEX_TAG | \JSON_NUMERIC_CHECK | \JSON_HEX_APOS | \JSON_HEX_QUOT | \JSON_HEX_AMP | \JSON_UNESCAPED_UNICODE);';
							break;

						case "jsondec":
							$sReturn .= $sDataVar.' = \json_decode('.$sDataVar.', true);';
							break;

						case "xml":
							$sReturn .= $sDataVar.' = Rind::xml2array('.$sDataVar.');';
							break;

						case "group":
							$sStructure = $this->dynVar();
							$sJson = (isset($vArguments["structure"])) ? $vArguments["structure"]: "null";
							$sReturn .= $sStructure.' = (!\is_array('.$sJson.')) ? \json_decode('.$sJson.', true) : '.$sJson.';';
							$sReturn .= $sDataVar.' = Rind::arrayGroup('.$sDataVar.', '.$sStructure.');';
							break;

						case "keys":
							$sReturn .= $sDataVar.' = \array_keys('.$sDataVar.');';
							break;

						case "vector":
							$mColumn = isset($vArguments["column"]) ? $this->RIND_QUOTE.$vArguments["column"].$this->RIND_QUOTE : "null";
							$mIndex = isset($vArguments["index"]) ? $this->RIND_QUOTE.$vArguments["index"].$this->RIND_QUOTE : "null";
							$sReturn .= $sDataVar.' = Rind::arrayColumn('.$sDataVar.','.$mColumn.','.$mIndex.');';
							break;

						case "object":
							$sReturn .= $sDataVar.' = Rind::obj2array('.$sDataVar.');';
							break;
						
						case "number":
							$sReturn .= $sDataVar.' = \preg_replace("/[^0-9\-\\'.NGL_NUMBER_SEPARATOR_DECIMAL.']/", "", '.$sDataVar.');';
							$sReturn .= 'if(!\is_numeric('.$sDataVar.')) { '.$sDataVar.' = 0; }';
						
						case "chkeys":
							if(isset($vArguments["keys"])) {
								$sKeys		= $this->dynVar();
								$sKey		= $this->dynVar();
								$aValue		= $this->dynVar();
								$sReturn .= '
								'.$sKeys.' = \explode(",", (string)("'.$vArguments["keys"].'"));
								\array_walk('.$sKeys.', function (&'.$sKey.') { '.$sKey.' = \trim('.$sKey.', " \t\n\r\0\x0B"); });
								if(\is_array('.$sDataVar.')) {
									if(\is_array(\current('.$sDataVar.'))) {
										if(\is_array('.$sKeys.') && \count('.$sKeys.')==\count(\current('.$sDataVar.'))) { 
											foreach('.$sDataVar.' as '.$sKey.' => '.$aValue.') {
												'.$sDataVar.'['.$sKey.'] = \array_combine('.$sKeys.', '.$aValue.');
											}
										}
									} else {
										if(\is_array('.$sKeys.') && \count('.$sKeys.')==\count('.$sDataVar.')) { 
											'.$sDataVar.' = \array_combine('.$sKeys.', '.$sDataVar.');
										} else {
											'.$sDataVar.' = [];
										}
									}
								} else {
									'.$sDataVar.' = [];
								}
								';
							}
							break;
						
						case "len":
						case "length":
							$sReturn .= $sDataVar.' = Rind::varLength('.$sDataVar.');';
							break;

						case "filter":
							if(isset($vArguments["filter"])) {
								$sFilter = $this->dynVar();
								$sIfCase = \trim($vArguments["filter"], " \t\n\r\0\x0B");
								$sReturn .= $sFilter." = '".$sIfCase."';"; 
								$sReturn .= $sDataVar.' = Rind::arrayFilter('.$sDataVar.', '.$sFilter.');';
							}
							break;

						case "nest":
							if(isset($vArguments["relation"])) {
								$sKeys 	= $this->dynVar();
								$sReturn .= '
									'.$sKeys.' = \explode(",", (string)("'.$vArguments["relation"].'"));
									'.$sDataVar.' = Rind::listToTree('.$sDataVar.', '.$sKeys.'[1], '.$sKeys.'[0], "nested");
								';
							}
					}
				}
				if(isset($sEachKey)) {
					$sReturn .= "\n".$sEachVar.'['.$sEachKey.'] = '.$sDataVar.'; } '.$sDataVar.' = '.$sEachVar.';';
					unset($sEachVar,$sEachKey);
				}
			}
			
			return $sReturn;
		}

		/** FUNCTION {
			"name" : "rindSplit", 
			"type" : "private",
			"description" : "Convierte un array en un string separado por comas.",
			"parameters" : { "$vArguments" : ["content", "Array de datos."] },
			"return" : "string"
		} **/
		private function rindSplit($vArguments) {
			$sReturn = $vArguments["content"];
			$sReturn = \str_replace(['".<[VAR[', ']VAR]>."'], ['<[VAR[', ']VAR]>'], $sReturn);
			$sReturn = \str_replace(['<[VAR[', ']VAR]>'], ['<[HDV[{', '}]HDV]>'], $sReturn);
			$sReturn = "<[FUN[Rind::split(".$this->RIND_QUOTE.$sReturn.$this->RIND_QUOTE.")]FUN]>";
			return $sReturn;
		}

		/** FUNCTION {
			"name" : "rindJoin", 
			"type" : "private",
			"description" : "Convierte un string separado por comas en un array.",
			"parameters" : { "$vArguments" : ["content", "Cadena de datos."] },
			"return" : "array"
		} **/
		private function rindJoin($vArguments) {
			$sReturn = $vArguments["content"];
			$sReturn = \str_replace(['".<[VAR[', ']VAR]>."'], ['<[VAR[', ']VAR]>'], $sReturn);
			$sReturn = "<[FUN[Rind::join(".$sReturn.")]FUN]>";
			return $sReturn;
		}
		
		/** FUNCTION {
			"name" : "rindHeredoc", 
			"type" : "private",
			"description" : "Retorna el código de una expresión de <b>rind:rtn</b> en las plantillas.",
			"parameters" : { "$vArguments" : ["array", "Argumentos del comando."] },
			"return" : "string"
		} **/
		private function rindHeredoc($vArguments) {
			$sReturn = $vArguments["content"];
			$sReturn = \str_replace(['".<[VAR[', ']VAR]>."'], ['<[VAR[', ']VAR]>'], $sReturn);
			$sReturn = \str_replace(['<[VAR[', ']VAR]>'], ['<[HDV[{', '}]HDV]>'], $sReturn);
			$sNowDoc = self::call()->unique(6);
			$sReturn = "<[FUN[<<<'".$sNowDoc."'\n".$sReturn."\n".$sNowDoc."\n]FUN]>";
			return $sReturn;
		}


		/** FUNCTION {
			"name" : "rindGet", 
			"type" : "private",
			"description" : "Retorna un id unico utilizando <b>ngl::unique</b> en las plantillas.",
			"parameters" : { "$vArguments" : ["array", "Argumentos del comando."] },
			"return" : "string"
		} **/
		private function rindGet($vArguments) {
			$sReturn = "<[FUN[Rind::this(".$this->RIND_ME.")->SET";
			foreach($vArguments as $sIndex) {
				$sReturn .= "[".$this->RIND_QUOTE.$sIndex.$this->RIND_QUOTE."]";
			}
			$sReturn .= "]FUN]>";
			return $sReturn;
		}

		/** FUNCTION {
			"name" : "rindUnique", 
			"type" : "private",
			"description" : "Retorna un id unico utilizando <b>ngl::unique</b> en las plantillas.",
			"parameters" : { "$vArguments" : ["array", "Argumentos del comando."] },
			"return" : "string"
		} **/
		private function rindUnique($vArguments) {
			return '<[FUN[Rind::unique('.(int)$vArguments["content"].')]FUN]>';
		}

		/** FUNCTION {
			"name" : "rindUnSet", 
			"type" : "private",
			"description" : "Retorna el código que desetea un índice de <b>$_SET</b> usando <b>rind:unset</b> en las plantillas.",
			"parameters" : { "$vArguments" : ["array", "Argumentos del comando."] },
			"return" : "string"
		} **/
		private function rindUnSet($vArguments) {
            $sVarName = \preg_replace("/[^a-z0-9_.]/i", "", $vArguments["content"]);
            $sVarName = \str_replace(".", $this->RIND_QUOTE."][".$this->RIND_QUOTE, $sVarName);
			$sReturn = '<[PHP[eval(\'unset(Rind::this('.$this->RIND_ME.')->SET['.$this->RIND_QUOTE.$sVarName.$this->RIND_QUOTE.']);\')]PHP]>';

			return $sReturn;
		}
		
		private function rindAlvin($vArguments) {
			$sString = $vArguments["content"];

			if(empty($sString)) {
				return 'true';
			} else if($sString=="true" || $sString=="1") {
				return 'true';
			} else if(\strtoupper($sString)=="RAW") {
				if(isset($vArguments["rawindex"])) {
					if(!isset($vArguments["rawdata"])) {
						return '<[FUN[Rind::alvin(null, "'.$vArguments["rawindex"].'")]FUN]>';
					} else {
						return '<[FUN[Rind::alvin(null, "'.$vArguments["rawindex"].'", '.$vArguments["rawdata"].')]FUN]>';
					}
				} else {
					return "<[FUN[Rind::alvin()]FUN]>";
				}
			} else {
				$sString = \ltrim($sString, " \t\n\r\0\x0B");
				\preg_match("/\((.*?)\)(.*)/is", $sString, $aMatchs);
				if(!\count($aMatchs)) {
					// return 'Rind::alvin("'.$sString.'")';
					return "Rind::alvin(<<<RINDALVIN\n".$sString."\nRINDALVIN\n, null, null, $this->RIND_ME)";
				} else if($aMatchs[2]==="") {
					// return 'Rind::alvin("'.$aMatchs[1].'")';
					return "Rind::alvin(<<<RINDALVIN\n".$aMatchs[1]."\nRINDALVIN\n, null, null, $this->RIND_ME)";
				} else {
					// return '<[PHP[if(Rind::alvin("'.$aMatchs[1].'")) { ]PHP]>'.$aMatchs[2].'<[PHP[}]PHP]>';
					return "<[PHP[if(Rind::alvin(<<<RINDALVIN\n".$aMatchs[1]."\nRINDALVIN\n, null, null, $this->RIND_ME)) { ]PHP]>".$aMatchs[2]."<[PHP[}]PHP]>";
				}
			}
		}

		public function viewcache() {
			list($sCacheFile) = $this->getarguments("cache_file", \func_get_args());
			if(empty($sCacheFile)) { return false; }
			$this->SetPaths();
			$sCachePath = ($sCacheFile===true) ? $this->attribute("cache_path") : $this->attribute("cache_path").NGL_DIR_SLASH.$sCacheFile;
			$sCachePath = self::call()->clearPath($sCachePath, false, NGL_DIR_SLASH, true);
			if(\is_file($sCachePath)) {
				$sCode = \highlight_file($sCachePath, true);
				$aCode = \explode("<br />", $sCode);
				$sNumLines = "";
				for($x=1;$x<\count($aCode)+1;$x++) {
					$sNumLines .= $x."<br />";
				}
				return "<table><tr><td class='rind-cache-numlines' style='text-align:right;padding:4px'>".$sNumLines."</td><td class='rind-cache-code' style='white-space:nowrap;padding:4px'>".$sCode."</td></tr></table>";
			}
		}
	}
}


namespace {

	class Rind {
		
		public static $Rinds;
		public static $MergeTail = [];

		public static function call($sObjectName) {
			global $ngl;
			return $ngl($sObjectName);
		}

		public static function dump($mVariable) {
			return \nogal\dump($mVariable);
		}
		
		public static function eco($mContent) {
			global $ngl;
			if($ngl()->isarrayarray($mContent)) {
				$mContent = $ngl()->imploder(";", $mContent);
			} else if(\is_object($mContent)) {
				$mContent = \implode(";", (array)$mContent);
			} else if(\is_array($mContent)) {
				$mContent = \implode(";", $mContent);
			}
			return print($mContent);
		}

		public static function clearPath($sPath, $bSlashClose=false, $sSeparator=NGL_DIR_SLASH) {
			global $ngl;
			return $ngl()->clearPath($sPath, $bSlashClose, $sSeparator);
		}

		public static function json($sString, $sRindID) {
			global $ngl;

			if(!\strlen($sString)) { return ""; }
			\preg_match_all("/\{\*[a-z0-9\-\_\.]+\}/is", $sString, $aMatchs);

			$sJson = '{"RINDJSON" : '.$sString.'}';
			$sRKOpen = $ngl()->unique();
			$sRKClose = $ngl()->unique();
			$sJson = \str_replace(["{:", ":}"], [$sRKOpen, $sRKClose], $sJson);
			$sJson = \preg_replace("/[\t\n\r]/","",$sJson);
			$sJson = \preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/','$1"$3":', $sJson);
			$sJson = \preg_replace('/(,)\s*}$/','}',$sJson);
			$sJson = \str_replace([$sRKOpen, $sRKClose], ["{:", ":}"], $sJson);
			$aJson = \json_decode($sJson, true);
			if($aJson===null) { $aJson = ["RINDJSON" => $sString]; }

			if(\is_array($aMatchs[0]) && \count($aMatchs[0])) {
				$SET = self::this($sRindID)->getSET();
				$aReplaces = [];
				foreach($aMatchs[0] as $sMatch) {
					$sVar = \substr($sMatch, 2, -1);
					$sEval = 'return @$SET["'.\str_replace('.', '"]["', $sVar).'"];';
					$aReplaces[$sMatch] = eval($ngl()->EvalCode($sEval));
				}
				$aSearch = \array_keys($aReplaces);
				
				\array_walk_recursive($aJson,
					function(&$mValue, $mKey) use ($aSearch, $aReplaces) {
						$mValue = \str_replace($aSearch, $aReplaces, $mValue);
					}
				);
			}

			return $aJson["RINDJSON"];
		}

		public static function isTrue($mValue) {
			global $ngl;
			return $ngl()->isTrue($mValue);
		}

		public static function unique($nLength=6) {
			global $ngl;
			return $ngl()->unique($nLength);
		}

		public static function this($sRindID) {
			return self::$Rinds[$sRindID];
		}
		
		public static function nut($mNut, $sMethod=null, $aArguments=null) {
			global $ngl;
			$sNutID = (!empty($aArguments["nutid"])) ? $aArguments["nutid"] : $ngl()->unique();
			$nut = (\is_string($mNut)) ? $ngl("nut.".$mNut, ["nutid"=>$sNutID]) : $mNut;
			return ($sMethod==null) ? $nut : $nut->run($sMethod, $aArguments);
		}
		
		public static function readFile($sFileName, $nLength=null, $sAuth=0, $mBody="") {
			global $ngl;
			$aOptions = ["CURLOPT_SSL_VERIFYPEER" => false];
			if($sAuth!==0) {
				$aAuth = \explode(" ", $sAuth, 2);
				$sAuthMethod = \strtolower($aAuth[0]);
				if($sAuthMethod=="basic") {
					$aOptions["CURLOPT_HTTPHEADER"] = ["Authorization: basic ".$aAuth[1]];
				} else if($sAuthMethod=="bearer" || $sAuthMethod=="alvin") {
					$aOptions["CURLOPT_HTTPHEADER"] = ["Authorization: ".$sAuth];
				} else {
					$aOptions["CURLOPT_USERPWD"] = $sAuth;
				}
			}

			if($mBody!=="") {
				if(\is_array($mBody)) { $mBody = \json_encode($mBody); }
				$aOptions["CURLOPT_POSTFIELDS"] = $mBody;
				$aOptions["CURLOPT_POST"] = true;
			}

			return $ngl("file")->load($sFileName)->read($nLength, $aOptions);
		}

		public static function stringToArray($sString, $sSplitter=",", $sEOL='\\r\\n', $sEnclosure='"') {
			global $ngl;
			$aElements = $ngl("shift")->csvToArray($sString, ["splitter"=>$sSplitter, "enclosed"=>$sEnclosure, "eol"=>$sEOL]);
			return $aElements;
		}

		public static function obj2array($mObject) {
			global $ngl;
			return $ngl("shift")->objToArray($mObject);
		}

		public static function varLength($mVar) {
			if(\is_array($mVar) || \is_object($mVar)) {
				return \count($mVar);
			} else if(\is_string($mVar)) {
				return \strlen($mVar);
			} else if(\is_numeric($mVar)) {
				return $mVar;
			} else if(\is_bool($mVar)) {
				return ($mVar) ? 1 : 0;
			}
		}

		public static function arrayFilter($aData, $sFilter) {
			global $ngl;
			return \array_filter($aData, function($v, $k) use ($sFilter, $ngl) {
				$sFilter = "return (".\str_replace(['GLOBALS["v"]', 'GLOBALS["k"]'], ["v", "k"], $sFilter).");";
				return eval($ngl()->EvalCode($sFilter));
			}, \ARRAY_FILTER_USE_BOTH);
		}

		public static function listToTree($aData, $sParent, $sId, $sNest) {
			global $ngl;
			return $ngl()->listToTree($aData, $sParent, $sId, $sNest);
		}

		public static function once() {
			global $ngl;
			return $ngl()->once();
		}

		public static function arrayGroup($aData, $aStructure=null) {
			global $ngl;
			if(!\is_array($aData)) { return []; }
			return $ngl()->arrayGroup($aData, $aStructure);
		}

		public static function arrayColumn($aData, $mColumnKey, $mIndexKey) {
			global $ngl;
			if(!\is_array($aData)) { return []; }
			if($mColumnKey===null) {
				if(\is_array($aData) && \count($aData)) {
					$aKeys = \array_keys(\current($aData));
					$mColumnKey = $aKeys[0];
				} else {
					return [];
				}
			}
			return $ngl()->arrayColumn($aData, $mColumnKey, $mIndexKey);
		}

		public static function alvin($sGrants=null, $sRawIndex=null, $aData=null, $sRindID=null) {
			global $ngl;

			$sUsername = (isset($_SESSION[NGL_SESSION_INDEX]["ALVIN"]["username"])) ? $_SESSION[NGL_SESSION_INDEX]["ALVIN"]["username"] : null;
			if($sRindID!==null) {
				$rind = self::this($sRindID);

				$mAlvinType = $rind->alvin_type;
				if($mAlvinType=="none") { return true; }

				$SET = $rind->getSET();
				$sGrants = \preg_replace_callback(
					"/<\?php echo Rind::this\(\"[0-9a-z]+\"\)->SET(.*?);\?>/is",
					function($aMatch) use ($ngl, $SET) {
						\ob_start();
						$sToEval = '<?php echo $SET'.$aMatch[1].";?>";
						eval($ngl()->EvalCode("?>".$sToEval));
						$sEvaluated = \ob_get_clean();
						return (\substr($sEvaluated, 0, 15)!="[ NOGAL ERROR @") ? $sEvaluated : false;
					},
					$sGrants
				);
				if($sGrants===false) { return false; }
				if(!empty($sGrants)) {
					if($sUsername=="admin") { return true; }
					if(\is_array($mAlvinType) && \in_array($sUsername, $mAlvinType)) { return true; }
				}
			}

			if($sGrants==="true") { return true; }

			if(\strlen($sGrants) && $sGrants[0]=="@") {
				if($sGrants==="@") { return true; }
				$sGrants = \substr($sGrants, 1);
			}

			if(NGL_ALVIN!==null) {
				if(!$ngl("alvin")->loaded()) {
					if(!$ngl("alvin")->autoload()) { return false; }
				}

				if($sGrants===null) {
					$mRaw = $ngl("alvin")->raw($sRawIndex);
					if($aData!==null){
						foreach($aData as $sKey => $sValue) {
							$mRaw = \str_replace("{:".$sKey.":}", $sValue, $mRaw);
						}
					}
					return $mRaw;
				} else {
					if($ngl()->isTrue($sGrants, true)) { return true; }
					return ($ngl("alvin")->check($sGrants));
				}
			} else {
				if($ngl()->isTrue($sGrants, true)) { return true; }
				return false;
			}
		}
		
		public static function split($sString) {
			if(!\is_string($sString)) { return "is not an string"; }
			return \explode(NGL_STRING_SPLITTER, $sString);
		}

		public static function join($aData) {
			if(!\is_array($aData)) { return ""; }
			return \implode(NGL_STRING_SPLITTER, $aData);
		}

		public static function ifempty($sCondition, $bReturn) {
			global $ngl;
			return (!\strlen($sCondition)) ? $bReturn : eval($ngl()->EvalCode("?><?php return (".$sCondition.") ? true : false; ?>"));
		}

		public static function getset($sReference) {
			global $ngl;
			$aReference = \explode("|", $sReference);
			$SET = self::this($aReference[1])->getSET();
			$sIndex = \base64_decode($aReference[0]);
			$sEval = 'return $SET["'.\str_replace('.', '"]["', $sIndex).'"];';
			return eval($ngl()->EvalCode($sEval));
		}

		public static function mergetemplate($aTemplate, $aParentData, $aSource) {
			global $ngl;

			if(\is_array($aSource[0])) {
				$sSubTemplate = $aSource[0][$aTemplate[0]];
			} else {
				$sFile = (\substr($aTemplate[0],-1)!=NGL_DIR_SLASH && \strtolower(\substr($aTemplate[0],-5))!=".html") ? $aTemplate[0].".html" : $aTemplate[0];
				if($aSource[1]) { $sFile = $aSource[2].NGL_DIR_SLASH.$sFile; }
				$sSubTemplate = self::this($aSource[0])->readTemplate($aSource[3].$sFile);
				$sSubTemplate = self::this($aSource[0])->rind2php($sSubTemplate);
			}

			$aParentData = \json_decode(\base64_decode($aParentData),true);
			if(isset($aTemplate[1]) && \count($aTemplate[1])) {
				if($aParentData[\count($aParentData)-1]!=$aTemplate[1]) {
					$aParentData[] = $aTemplate[1];
				}
			}
			$aParentData = \array_reverse($aParentData);

			$ngl()->errorForceReturn(true);
			foreach($aParentData as $aTemplateKeys) {
				foreach($aTemplateKeys as $sKey => $sValue) {
					$sValue = \base64_decode($sValue);
					$sValue = \stripslashes($sValue);
					$sValue = \preg_replace_callback(
						"/<\?php echo (\\\$GLOBALS|Rind::getset\()(.*?);?>/is",
						function($aMatch) use ($ngl) {
							\ob_start();
							eval($ngl()->EvalCode("?>".$aMatch[0]));
							$sEvaluated = \ob_get_clean();
							return (\substr($sEvaluated, 0, 15)!="[ NOGAL ERROR @") ? $sEvaluated : "";
						},
						$sValue
					);
					$sSubTemplate = \str_replace("{%".$sKey."%}",  $sValue,  $sSubTemplate);
					$sSubTemplate = \preg_replace('/\{\%mergeid\%\}/is', Rind::unique(8), $sSubTemplate);
					
					// echo "\r\n\n\n\n".$aTemplate[0]."\n";
					// echo $sSubTemplate."\n\n\n\n\n\n";
				}
			}
			$ngl()->errorForceReturn(false);

			return $sSubTemplate;
		}
	}
}

?>
