<?php

namespace nogal;

/** CLASS {
	"name" : "nglRoot",
	"type" : "kernel",
	"revision" : "20160201",
	"description" : "	tutor.action
	"
} **/
class nglRoot {

	const me										= "nogal";

	private static			$fn						= null;
	private static			$session				= null;
	private static			$shift					= null;
	private static			$nut					= null;
	private static			$tutor					= null;
	private static			$unicode				= null;
	private static			$val					= null;
	private static			$var					= null;

	private static			$nStarTime				= null;
	private static			$vErrorCodes			= [];
	private static			$vLastError				= [];
	private static			$bErrorReport			= true;
	private static			$bErrorReportPrevius	= true;
	private static			$aErrorModes			= [];
	private static			$bErrorForceReturn		= false;
	private static			$vCurrentPath			= [];
	private static			$vCoreLibs				= [];
	private static			$vLibraries				= [];
	private static			$vFeederInits			= [];
	protected static		$aObjects				= [];
	protected static		$bLoadAllowed			= false;
	private static 			$aNuts					= [];
	protected static 		$aNutsLoaded			= [];
	private static 			$aTutors				= [];
	protected static 		$aTutorsLoaded			= [];
	protected static 		$sLastEval				= "";
	private static			$aObjectsByClass		= [];
	private static			$vPaths					= [];
	private static			$vLastOf				= [];


	// METODOS PUBLICOS --------------------------------------------------------
	/** FUNCTION {
		__construct: Constructor
	**/
	public function __construct($vLibraries, $vGraftsLibraries) {
		self::$nStarTime = \microtime(true);
		
		// paths
		self::setPath("libraries");
		self::setPath("grafts");
		
		// librerias
		self::$vCoreLibs = [
			"fn" => ["nglFn", true],
			"nut" => ["nglNut", true],
			"sess" => ["nglSession", true],
			"shift" => ["nglShift", true],
			"sysvar" => ["nglSystemVars", true],
			"tutor" => ["nglTutor", true],
			"unicode" => ["nglUnicode", true],
			"validate" => ["nglValidate", true]
		];
		self::$vLibraries = \array_merge(self::$vCoreLibs,  $vLibraries, $vGraftsLibraries);

		// kernel
		self::$bLoadAllowed = true;
		require_once(self::$vPaths["libraries"]."nglTrunk.php");
		require_once(self::$vPaths["libraries"]."nglBranch.php");
		require_once(self::$vPaths["libraries"]."nglFeeder.php");
		require_once(self::$vPaths["libraries"]."nglScion.php");

		require_once(self::$vPaths["libraries"]."nglFn.feeder.php");
		self::$fn = new nglFn();

		require_once(self::$vPaths["libraries"]."nglTutor.feeder.php");
		self::$tutor = new nglTutor();

		require_once(self::$vPaths["libraries"]."nglNut.feeder.php");
		self::$nut = new nglNut();

		self::$bLoadAllowed = false;
	}
	
	/** FUNCTION {
		__invoke : {
			"description" : "alias de call",
		}
	**/
	public function __invoke() {
		$aArguments = \func_get_args();
		if(!\count($aArguments)) { $aArguments = []; }
		return \call_user_func_array(["self", "call"], $aArguments);
	}

	/** FUNCTION {
		__toString : {
			"description" : "retorna el nombre del objeto",
			"return" : "nombre del objeto"
		}
	**/
	public function __toString() {
		return self::me;
	}

	/** FUNCTION {
		call : {
			"description" : "
				invoca al objeto $sObjectName con los argumentos $mArguments.
				$sObjectName puede ser un nombre de objeto o una instancia del mismo.
				cuando la instancia no exista se creará una copia del objeto.
			",
			"params" : { 
				"$sObjectName" : "
					nombre del objeto. Formatos:
						nombre_del_objeto,
						nombre_del_objeto.nombre_de_instancia
				",
				"$mArguments" : "argumentos adicionales pasados al método __init__"
			},
			"return" : "objeto o false"
		}
	**/
	public static function call($sObjectName=null, $aArguments=[]) {
		if($sObjectName===null) { return self::$fn; }
		if(!\is_array($aArguments) || !\count($aArguments)) { $aArguments = null; }

		if(\strpos($sObjectName, "|")!==false) {
			$aObjectConf	= \explode("|", $sObjectName, 2);
			$sObjectName	= $aObjectConf[0];
			$sConfFile		= $aObjectConf[1];
		}

		$sObjectName = self::objectName($sObjectName);
		$aObjectName = \explode(".", $sObjectName, 2);

		$bFeeder = true;
		if(isset(self::$vLibraries[$aObjectName[0]])) {
			$bFeeder = self::$vLibraries[$aObjectName[0]][1];
		}
		
		if(!$bFeeder && $aObjectName[0]!="nut" && $aObjectName[0]!="tutor") {
			if((\is_array($aObjectName) && \count($aObjectName)==1) || (isset($aObjectName[1]) && $aObjectName[1]==="")) {
				$tmp = self::call()->unique();
				$sObjectName .= ".".\strtolower($tmp);
			}
			$sObjectType = $aObjectName[0];
		} else {
			if($aObjectName[0]=="nut" || $aObjectName[0]=="tutor") { $sObjectName = $aObjectName[0]; }
			switch($sObjectName) {
				case "fn"		: 	return self::returnFeeder(self::$fn);
				case "tutor"	: 	return self::$tutor->load($aObjectName[1], $aArguments);
				case "nut"		: 	return self::$nut->load($aObjectName[1], $aArguments);
				default			:	$sObjectType = $sObjectName;
			}
		}

		if(!isset(self::$aObjects[$sObjectName])) {
			if(isset(self::$vLibraries[$sObjectType])) {
				$mClassName = self::$vLibraries[$sObjectType][0];
				if(!isset($sConfFile)) { $sConfFile = $sObjectType; }
				self::loadObject($mClassName, $bFeeder, $sConfFile, $sObjectName, $aArguments);
			} else {
				self::errorMessage("nogal", "1002", $sObjectType, "die");				
				return false;
			}
		}

		if(isset(self::$aObjects[$sObjectName])) {
			self::$vLastOf[$sObjectType] = self::$aObjects[$sObjectName];
			return self::$aObjects[$sObjectName];
		}
		return false;
	}

	public static function requirer() {
		$aBacktrace = \debug_backtrace(false);
		foreach($aBacktrace as $aFile) {
			if(
				$aFile["function"]=="require" ||
				$aFile["function"]=="require_once" ||
				$aFile["function"]=="include" ||
				$aFile["function"]=="include_once"
			) {
				return $aFile["file"];
			}
		}
		
		return false;
	}

	public static function EvalCode($sCode) {
		self::$sLastEval = \base64_encode($sCode);
		return $sCode;
	}

	public static function returnFeeder($object) {
		$sClass = \get_class($object);
		if(\method_exists($object, "__init__") && !isset(self::$vFeederInits[$sClass])) {
			$object->__init__();
			self::$vFeederInits[$sClass] = true;
		}
		return $object;
	}

	public static function tutor($sTutorName, $sClassName=null, $aMethods=null) {
		if($sClassName!==null) { self::$aTutors[$sTutorName] = [$sClassName,$aMethods]; }
		return (isset(self::$aTutors[$sTutorName])) ? self::$aTutors[$sTutorName] : null;
	}

	public static function nut($sNutName, $sClassName=null, $aMethods=null) {
		if($sClassName!==null) { self::$aNuts[$sNutName] = [$sClassName,$aMethods]; }
		return (isset(self::$aNuts[$sNutName])) ? self::$aNuts[$sNutName] : null;
	}

    public static function absolutePath($sPath, $sDirSlash=DIRECTORY_SEPARATOR) {
        $sPath = \str_replace(['/', '\\'], $sDirSlash, $sPath);
        $aPath = \explode($sDirSlash, $sPath);
		$aPath = \array_filter($aPath, "strlen");
        $aAbsolutes = [];
        foreach($aPath as $sPart) {
            if("."==$sPart) { continue; }
            if(".."==$sPart) {
                \array_pop($aAbsolutes);
            } else {
                $aAbsolutes[] = $sPart;
            }
        }

        return \implode($sDirSlash, $aAbsolutes);
    }

	// verifica si $mPath o alguno de sus indices (si es array) es parte de NGL_PATH_CURRENT
	// $sPath debe terminar en /
	public static function inCurrentPath($mPath) {
		if(!\is_array($mPath)) { $mPath = [$mPath]; }
		\usort($mPath, function($a, $b) { return \strlen($b) - \strlen($a); });
		$nLength = \strlen(NGL_PATH_CURRENT);
		foreach($mPath as $sPath) {
			if(NGL_PATH_CURRENT===$sPath) {
				return $sPath;
			} else if(\strlen($sPath)>1 && \substr($sPath, -1, 1)=="/") {
				if(\substr(NGL_PATH_CURRENT, 0, \strlen($sPath))===$sPath) {
					return $sPath;
				}
			} else if(\substr($sPath, -1, 1)=="*") {
				if(\substr($sPath, 0, -1)===\substr(NGL_PATH_CURRENT, 0, \strlen($sPath)-1)) {
					return $sPath;
				}
			}
		}

		return false;
	}
		
	public static function constants() {
		$aConstants = [];
		$aGetConstants = \get_defined_constants(true);
		foreach($aGetConstants["user"] as $sName => $sConstant) {
			if(\substr($sName,0,4)=="NGL_") {
				$aConstants[$sName] = \addcslashes($sConstant, "\t\r\n");
			}
		}
		
		\ksort($aConstants);
		return $aConstants;
	}

	public static function currentPath($sDirSlash=DIRECTORY_SEPARATOR) {
		if(\is_array(self::$vCurrentPath) && \count(self::$vCurrentPath)) { return self::$vCurrentPath; }
		
		// document_root
		$sDocumentRoot = \str_replace("\\", "/", NGL_DOCUMENT_ROOT);
		$aDocumentRoot = \explode("/", $sDocumentRoot);
		if(end($aDocumentRoot)=="") { \array_pop($aDocumentRoot); }
		$sDocumentRoot = \implode($sDirSlash, $aDocumentRoot);

		// php_self
		if(\array_key_exists("REDIRECT_SCRIPT_URL", $_SERVER)) {
			$sPHPSelf = $_SERVER["REDIRECT_SCRIPT_URL"];
		} else if(\array_key_exists("REDIRECT_URL", $_SERVER)) {
			$sPHPSelf = $_SERVER["REDIRECT_URL"];
		} else {
			$sPHPSelf = $_SERVER["PHP_SELF"];
		}

		if($sPHPSelf=="/") { $sPHPSelf = "/index"; }
		$sPHPSelf = \str_replace("\\", "/", $sPHPSelf);
		
		$aPath = \explode("/", $sPHPSelf);
		foreach($aPath as $nIndex => $sPart) {
			if($sPart==="") { unset($aPath[$nIndex]); }
		}
		$sPHPSelf = \implode($sDirSlash, $aPath);
		$vPHPSelf = \pathinfo($sPHPSelf);

		$vCurrent = [];
		$vCurrent["basename"] 	= $vPHPSelf["basename"];
		$vCurrent["path"]		= self::absolutePath($sDocumentRoot.$sDirSlash.$vPHPSelf["dirname"], $sDirSlash);
		$vCurrent["fullpath"]	= $vCurrent["path"].$sDirSlash.$vCurrent["basename"];
		$vCurrent["dirname"]	= ($vPHPSelf["dirname"]!=".") ? $vPHPSelf["dirname"] : "";

		$aBasename = \explode(".", $vCurrent["basename"]);
		if(\is_array($aBasename) && \count($aBasename)>1) {
			$vCurrent["extension"] = \array_pop($aBasename);
			$vCurrent["filename"] = \implode(".", $aBasename);
		} else {
			$vCurrent["extension"] = "";
			$vCurrent["filename"] = $vCurrent["basename"];
		}
		
		$vCurrent["query_string"] = (\array_key_exists("QUERY_STRING", $_SERVER)) ? $_SERVER["QUERY_STRING"] : "";

		$NGL_URL = \constant("NGL_URL");
		if(!empty($NGL_URL)) {
			$vURL = \parse_url(NGL_URL);
			$vCurrent["scheme"] = $vURL["scheme"];
			$vCurrent["host"] = $vURL["host"];
			$vCurrent["port"] = (isset($vURL["port"])) ? $vURL["port"] : "";
			$vCurrent["urlroot"] = $vURL["scheme"]."://".$vURL["host"];
			if(!empty($vURL["port"])) { $vCurrent["urlroot"] .= ":".$vURL["port"]; }
			$vCurrent["urldirname"] = (!empty($vCurrent["dirname"])) ? \str_replace("\\", "/", $vCurrent["dirname"])."/" : "";
			$vCurrent["url"] = $vCurrent["urldirname"].$vCurrent["basename"].(($vCurrent["query_string"]!="") ? "?".$vCurrent["query_string"] : "");
			$vCurrent["urlpath"] = $vCurrent["urldirname"].$vCurrent["basename"];
			$vCurrent["fullurl"] = $vCurrent["urlroot"]."/".$vCurrent["url"];
			$vCurrent["fullurlpath"] = $vCurrent["urlroot"]."/".$vCurrent["urlpath"];
		}

		return $vCurrent;
	}

	public static function defineConstant($sConstantName, $sConstantValue=null) {
		if(!\defined($sConstantName)) { \define($sConstantName, $sConstantValue); }
		return \constant($sConstantName);
	}

	public static function prickout($sURL=null, $sGround=null) {
		if($sURL===null) { $sURL = NGL_URL; }
		$sURL = \parse_url($sURL, PHP_URL_PATH);
		$sFile = \str_replace(\parse_url(NGL_URL, PHP_URL_PATH), "", $sURL);
		$sFile = \str_replace("\\", "/", $sFile);

		if(!empty($sFile) && $sFile[0]!="/") { $sFile = "/".$sFile; }
		$sFile = self::call()->clearPath($sFile, false, "/");
		$aParts = \explode("/", $sFile, 3);

		// casos especiales
		if(isset($aParts[1])) {
			if($aParts[1]=="tutor" && isset($aParts[2])) {
				return [NGL_PATH_PROJECT."/tutor.php", $aParts[2]];
			} else if($aParts[1]=="nut" && isset($aParts[2])) {
				return [NGL_PATH_PROJECT."/nut.php", $aParts[2]];
			}
		}

		// caso normal
		if($sGround===null) { $sGround = NGL_PATH_PRICKOUT; }
		$sFilePath = $sGround.NGL_DIR_SLASH.$sFile;
		$sFile = \realpath($sFilePath);

		if(\file_exists($sFile) && !\is_dir($sFile)) {
			return [$sFile, null];
		} else if(is_dir($sFile)) {
			if($sURL[\strlen($sURL)-1]!="/") {
				\header("location: ".$sURL."/");
				exit();
			}
			$sFile .= "/index.php";
			$sFile = \realpath($sFile);
			if(\file_exists($sFile)) { return [$sFile, null]; }
			$sFilePath .= "/";
		} else {
			$sFile = $sFilePath.".php";
			$sFile = \realpath($sFile);
			if(\file_exists($sFile)) {
				return [$sFile, null];
			}
		}

		// error
		$sFilePath = self::call()->clearPath($sFilePath, ($sURL[\strlen($sURL)-1]=="/"), NGL_DIR_SLASH);
		return [false, $sFilePath];
	}

	public static function errorsHandler($nError, $sMessage, $sFile, $nLine) {
		if(\defined("E_ERROR")) { 				$aErrors[E_ERROR]		 		= "Error"; }
		if(\defined("E_WARNING")) { 				$aErrors[E_WARNING]			= "Warning"; }
		if(\defined("E_PARSE")) { 				$aErrors[E_PARSE]				= "Parsing Error"; }
		if(\defined("E_NOTICE")) { 				$aErrors[E_NOTICE]				= "Notice"; }
		if(\defined("E_CORE_ERROR")) { 			$aErrors[E_CORE_ERROR]			= "Core Error"; }
		if(\defined("E_CORE_WARNING")) { 		$aErrors[E_CORE_WARNING]		= "Core Warning"; }
		if(\defined("E_COMPILE_ERROR")) { 		$aErrors[E_COMPILE_ERROR]		= "Compile Error"; }
		if(\defined("E_COMPILE_WARNING")) { 		$aErrors[E_COMPILE_WARNING]	= "Compile Warning"; }
		if(\defined("E_USER_ERROR")) { 			$aErrors[E_USER_ERROR]			= "User Error"; }
		if(\defined("E_USER_WARNING")) {			$aErrors[E_USER_WARNING]	= "User Warning"; }
		if(\defined("E_USER_NOTICE")) { 			$aErrors[E_USER_NOTICE]		= "User Notice"; }
		if(\defined("E_STRICT")) { 				$aErrors[E_STRICT]				= "Runtime Notice"; }
		if(\defined("E_RECOVERABLE_ERROR")) {	$aErrors[E_RECOVERABLE_ERROR]	= "Catchable Fatal Error"; }
		if(\defined("E_DEPRECATED")) { 			$aErrors[E_DEPRECATED]			= "Runtime Notice, this code not work in future versions"; }
		
		$sMessage = \str_replace("[<a href='function", "[<a target='_blank' href='http://php.net/function", $sMessage);

		$bIgnoreError = false;
		switch($nError) {
			case E_NOTICE:
				if(\strpos($sMessage, "undefined constant")) {
					$bIgnoreError = true;
				}
				break;

			case E_WARNING:
				if(\strpos($sMessage, "headers already sent")) {
					$bIgnoreError = true;
				}
			
			case E_STRICT:
				if($sMessage=="Creating default object from empty value") {
				}

			case E_PARSE:
			case E_USER_ERROR:
				break;
			
			default:
				if(isset($aErrors[$nError])) {
					$sMessage = "Internal Framework Error (".$aErrors[$nError]."), Please report to admin";
				}
		}

		$sError = "";
		if(\strtoupper(\substr($sMessage, 0, 4))!="NGL|") {
			if(\strtolower(NGL_HANDLING_ERRORS_FORMAT)=="html") {
				$sError .= $sMessage."<br />";
				$sError .= "<b>file:</b> ".$sFile." - <b>line:</b> ".$nLine;
			} else {
				$sError .= $sMessage." -> file: ".$sFile." - line: ".$nLine;
			}
		} else {
			$sError .= \substr($sMessage, 4);
		}

		self::$vLastError = [
			"object" => "PHP",
			"type" => $aErrors[$nError],
			"file" => $sFile,
			"line" => $nLine,
			"description" => $sMessage,
			"message" => $sError
		];

		if(self::$bErrorReport) {
			if(\strpos($sFile, "eval()'d")) {
				self::$vLastError["aditional"] = "\nEVAL-CODE:base64[".self::$sLastEval."]\n";
			}

			if(\error_reporting()) {
				if(!$bIgnoreError) {
					try {
						self::errorMessage();
					} catch(Exception $e){
						throw new \Exception(self::errorMessage());
					}
				}
			}
		}
	}

	public static function errorGetLast() {
		return self::$vLastError;
	}

	public static function errorClearLast() {
		self::$vLastError = [];
	}

	public static function errorReporting($bReport) {
		self::$bErrorReportPrevius = self::$bErrorReport;
		if($bReport!==null) { self::$bErrorReport = $bReport; }
		return self::$bErrorReport;
	}

	public static function errorReportingRestore() {
		self::$bErrorReport = self::$bErrorReportPrevius;
		return self::$bErrorReport;
	}

	// retorna el modo previo
	public static function errorMode($sObject, $sMode=null) {
		$sCurrent = (isset(self::$aErrorModes[$sObject])) ? self::$aErrorModes[$sObject] : NGL_HANDLING_ERRORS_MODE;
		if($sMode!==null) {
			$sMode = \strtolower($sMode);
			if(!\in_array($sMode, ["boolean","code","die","print","return"])) { $sMode = NGL_HANDLING_ERRORS_MODE; }
			self::$aErrorModes[$sObject] = $sMode;
		}
		return $sCurrent;
	}

	public static function errorForceReturn($bForce) {
		self::$bErrorForceReturn = ($bForce===true) ? true : false;
	}

	public static function errorPages($nCode) {
		switch($nCode) {
			case 403:
				\header("HTTP/1.0 403 Forbidden", true, 403);
				$sError = "403 Forbidden";
				break;

			case 404:
				\header("HTTP/1.0 404 Not Found", true, 404);
				$sError = "404 Not Found";
				break;

			case 503:
				\header("HTTP/1.0 503 Not Service Unavailable", true, 503);
				$sError = "503 Service Unavailable";
				break;
		}

		$sMessage = @\file_get_contents(NGL_PATH_CONF.NGL_DIR_SLASH."errorpage.html");
		if($sMessage===false) {
			$sMessage = @\file_get_contents(NGL_PATH_FRAMEWORK.NGL_DIR_SLASH."assets".NGL_DIR_SLASH."errorpage.html");
		}

		if($sMessage===false) {
			$sMessage = $sError;
		} else {
			$sMessage = \str_replace("{%TITLE%}", NGL_PROJECT, $sMessage);
			$sMessage = \str_replace("{%ERROR%}", $sError, $sMessage);
		}
		
		die($sMessage);
	}

	public static function exists($sObjectType) {
		$sObjectType = \strtok($sObjectType, ".");
		return isset(self::$vLibraries[$sObjectType]);
	}

	public static function kill($sObjectName) {
		if(isset(self::$aObjects[$sObjectName])) {
			$sClassName = self::$aObjects[$sObjectName]->class;
			if(isset(self::$aObjectsByClass[$sClassName])) {
				foreach(self::$aObjectsByClass[$sClassName] as $nIndex => $sName) {
					if($sName==$sObjectName) {
						self::$aObjectsByClass[$sClassName][$nIndex] = null;
						unset(self::$aObjectsByClass[$sClassName][$nIndex]);
						break;
					}
				}
			}

			self::$aObjects[$sObjectName] = null;
			unset(self::$aObjects[$sObjectName]);
			\gc_collect_cycles();
			return true;
		}
		return false;
	}

	public static function errorSetCodes($sObject, $aCodes) {
		self::$vErrorCodes[$sObject] = $aCodes;
	}

	public static function errorCodes($sObject, $nCode) {
		if(!isset(self::$vErrorCodes[$sObject])) {
			$sErrorFile = null;
			if(\file_exists(NGL_PATH_CONF.NGL_DIR_SLASH.$sObject.".conf")) {
				$sErrorFile = NGL_PATH_CONF.NGL_DIR_SLASH.$sObject.".conf";
			} else if(\file_exists(NGL_PATH_FRAMEWORK.NGL_DIR_SLASH."docs".NGL_DIR_SLASH.$sObject.".info")) {
				$sErrorFile = NGL_PATH_FRAMEWORK.NGL_DIR_SLASH."docs".NGL_DIR_SLASH.$sObject.".info";
			}

			if($sErrorFile!==null) {
				$aConfig = self::parseConfigString(\file_get_contents($sErrorFile), true);
				if(isset($aConfig["errors"])) { self::errorSetCodes($sObject, $aConfig["errors"]); }
			}
		}

		return (isset(self::$vErrorCodes[$sObject], self::$vErrorCodes[$sObject][$nCode])) ? self::$vErrorCodes[$sObject][$nCode] : $nCode;
	}

	public static function errorMessage($sObject=null, $sCode=null, $sAditionalText=null, $sMode=null) {
		$sMsgText = "NOGAL ERROR ";
		$sDescription = $sDescriptionPure = "";

		$bLast = false;
		if($sObject===null) {
			$bLast = true;
			$aLast = self::errorGetLast();
			if(\is_array($aLast) && \count($aLast)) {
				$sObject = $aLast["object"];
				$sCode = $aLast["type"];
				$sDescription = $sDescriptionPure = $aLast["description"];
				// if(isset($aLast["message"]) && !empty($aLast["message"])) { $sDescription = $aLast["message"]; }
				if(!empty($aLast["message"])) { $sDescription = $aLast["message"]; }
				if(isset($aLast["aditional"])) { $sAditionalText = $aLast["aditional"]; }
			}
		}

		$sTitle = ($sCode!==null) ? "@".$sObject."#".$sCode : $sObject;
		$sObject = \strtolower($sObject);
		if($sMode===null) { $sMode = self::errorMode($sObject); }

		if($sMode=="boolean") { return false; }
		if($sMode=="code") { return $sCode; }

		$aBacktrace = \debug_backtrace();
		$sCurrentFile = $aBacktrace[0]["file"];
		$nCurrentLine = $aBacktrace[0]["line"];

		$EOL = (\strtolower(NGL_HANDLING_ERRORS_FORMAT)=="html") ? "<br />" : "\n";
		$SOL = (\strtolower(NGL_HANDLING_ERRORS_FORMAT)=="html") ? "&nbsp;&nbsp;&nbsp;&nbsp;" : "\t";

		if(!$bLast) { $sDescription = $sDescriptionPure = self::errorCodes($sObject, $sCode); }

		if($sAditionalText!==null) {
			if(!empty($sDescription)) { $sDescription .= " -> "; }
			$sDescription .= $sAditionalText;
		}
		
		if(!empty($sDescription) && $sDescription!==$sCode) {
			$sMsgText .= $sTitle." - ";
			$sMsgText .= $sDescription;
		} else {
			$sMsgText .= $sTitle;
		}

		if(!$bLast && NGL_HANDLING_ERRORS_BACKTRACE) {
			$x = 1;
			$sBacktrace = $EOL;
			$sFile = $nLine = "";
			foreach($aBacktrace as $vNode) {
				if(isset($vNode["file"])) { $sFile = $vNode["file"]; }
				if(isset($vNode["line"])) { $nLine = $vNode["line"]; }
				$sBacktrace .= "#".$x." - ".\basename($sFile)." (" .$nLine.")".$EOL;
				$sBacktrace .= $vNode["function"]."(".$EOL;
				if(isset($vNode["args"])) {
					$sBacktrace .= $SOL.self::call()->imploder([", ", $EOL.$SOL], $vNode["args"]).$EOL;
				}
				$sBacktrace .= ")".$EOL.$EOL;
				$x++;
			}

			$sMsgText .= $sBacktrace;
		}

		$sMsgText = "[ ".\strip_tags($sMsgText)." ]";
		
		// log
		$vCurrentPath = self::currentPath();
		$sErrRow  = \date("Y-m-d H:i:s");
		$sErrRow .= "\t".(isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "localhost");
		$sErrRow .= "\t".$vCurrentPath["fullpath"];
		$sErrRow .= "\t".$sCurrentFile." (".$nCurrentLine.")";
		$sErrRow .= "\t".$sMsgText;
		self::log("errors.log", $sErrRow."\n");
		
		$sMsg = $sMsgText;
		if(self::$bErrorForceReturn) { return $sMsg; }

		// html format
		if($EOL=="<br />") {
			$sCSSBox 			= " style='display:block !important;width:auto !important;padding:10px !important;background-color:#FFFF88 !important;border:solid 5px #DD2211 !important'";
			$sCSSTitle 			= " style='font-family:sans-serif !important;font-size:12pt !important;font-weight:bold !important;color:#DD2211 !important;'";
			$sCSSSubtitle 		= " style='font-family:sans-serif !important;font-size:10pt !important;font-weight:normal !important;color:#FF5500 !important;'";
			$sCSSCode 			= " style='font-family:sans-serif !important;font-size:12pt !important;font-weight:bold !important;color:#FF5500 !important;'";
			$sCSSDescription 	= " style='font-family:sans-serif !important;font-size:11pt !important;font-weight:normal !important;color:#FF5500 !important;'";
			$sCSSAditionalText	= " style='font-family:sans-serif !important;font-size:10pt !important;font-weight:normal !important;color:#FF5500 !important;'";

			$sMsg = "<code".$sCSSBox."><span".$sCSSTitle.">NOGAL ERROR</span>".$EOL.$EOL;

			$sDescription = "";
			if(isset($vErrors[$sObject])) {
				if(\array_key_exists($sCode, $vErrors[$sObject])) { $sDescription = $vErrors[$sObject][$sCode]; }
			}

			if($sAditionalText!==null) {
				if(!empty($sDescription)) { $sDescription .= "<br />"; }
				$sDescription .= "<span".$sCSSAditionalText.">".$sAditionalText."</span>";
			}
		
			if(!empty($sDescription)) {
				$sMsg .= "<span".$sCSSCode.">".$sTitle." - </span>";
				$sMsg .= "<span".$sCSSDescription.">".$sDescription."</span>".$EOL;
			} else {
				$sMsg .= "<span".$sCSSDescription.">".$sCode."</span>".$EOL;
			}

			if(NGL_HANDLING_ERRORS_BACKTRACE) { $sMsg .= $sBacktrace; }

			$sMsg .= "</code>\n";
		}

		self::$vLastError = [
			"object" => $sObject,
			"type" => $sCode,
			"file" => $vCurrentPath["path"],
			"line" => $nCurrentLine,
			"description" => $sDescriptionPure,
			"message" => $sMsgText
		];

		if(PHP_SAPI=="cli") { $sMsg = self::out("\n".$sMsg, "error"); }
		switch($sMode) {
			case "die":
				die($sMsg);
				break;

			case "return":
				return $sMsg;

			case "print":
				print($sMsg);
				break;
		}
	}
	
	public static function lastOf($sObject=null) {
		if($sObject===null) {
			return $vLastOf;
		} else if(isset(self::$vLastOf[$sObject])) {
			return self::$vLastOf[$sObject];
		}
		return null;
	}

	/** FUNCTION {
		load : {
			"description" : "agrega un nuevo objeto al objeto principal y lo retorna",
			"params" : {
				"$sClassName" : "nombre de la clase del nuevo objeto",
				"$sObjectName" : "nombre del nuevo objeto",
				"$aArguments" : "argumentos para el nuevo objeto"
			},
			"return" : "instancia $sObjectName de la clase $sClassName"
		}
	**/
	public static function loadObject($sClassName, $bFeeder=true, $sConfFile=null, $sObjectName=null, $aArguments=null) {
		if($bFeeder) {
			$sClassFile = $sClassName.".feeder.php";
		} else {
			$sClassFile = $sClassName.".php";
		}

		if(!isset(self::$aObjectsByClass[$sClassName])) {
			if(\file_exists(self::$vPaths["libraries"].$sClassFile)) {
				require_once(self::$vPaths["libraries"].$sClassFile);
				self::$aObjectsByClass[$sClassName] = [];
			} else if(\file_exists(self::$vPaths["grafts"].$sClassFile)) {
				require_once(self::$vPaths["grafts"].$sClassFile);
				self::$aObjectsByClass[$sClassName] = [];
			} else {
				self::errorMessage("nogal", "1001", self::$vPaths["libraries"].$sClassFile." (".$sClassName.")", "die");
			}
		}

		if($sObjectName!==null) {
			$sObjectName = self::objectName($sObjectName);
			if(!\in_array($sObjectName, self::$aObjectsByClass[$sClassName])) {
				$sCallClass = __NAMESPACE__."\\".$sClassName;

				self::$bLoadAllowed = true;
				self::$aObjects[$sObjectName] = new $sCallClass ($sClassName, $sObjectName);
				self::$bLoadAllowed = false;
				if(\method_exists(self::call($sObjectName), "__vendor__")) { self::call($sObjectName)->__vendor__(); }
				if(\method_exists(self::call($sObjectName), "__declareAttributes__")) { self::call($sObjectName)->__SetupAttributes__(self::call($sObjectName)->__declareAttributes__()); }
				if(\method_exists(self::call($sObjectName), "__declareArguments__")) { self::call($sObjectName)->__SetupArguments__(self::call($sObjectName)->__declareArguments__()); }
				if(\method_exists(self::call($sObjectName), "__declareVariables__")) { self::call($sObjectName)->__declareVariables__(); }
				if(!$bFeeder && \file_exists($sConfigFile = NGL_PATH_CONF.NGL_DIR_SLASH.$sConfFile.".conf")) {
					self::call($sObjectName)->__config__($sConfigFile);
				}
				if(\method_exists(self::call($sObjectName), "__arguments__") && $aArguments!==null) { self::call($sObjectName)->args($aArguments); }
				if(\method_exists(self::call($sObjectName), "__init__")) {
					if($bFeeder) {
						self::call($sObjectName)->__init__($aArguments);
					} else {
						self::call($sObjectName)->__init__();
					}
				}

				self::$aObjectsByClass[$sClassName][] = $sObjectName;
			}
		
			if(isset(self::$aObjects[$sObjectName])) {
				return self::$aObjects[$sObjectName];
			}
		}

		return false;
	}

	public static function loadedClass($sClassName=null) {
		if($sClassName) {
			return (
				isset(self::$aObjectsByClass[$sClassName]) || (
					isset(self::$vLibraries[$sClassName]) && 
					isset(self::$aObjectsByClass[self::$vLibraries[$sClassName][0]])
				)
			);
		} else {
			return self::$aObjectsByClass;
		}
	}

	public static function availables() {
		$aComponents = \array_merge(self::$vCoreLibs, self::$vLibraries);
		\ksort($aComponents);
		$aAvailables = [];
		foreach($aComponents as $sComponent => $aComponent) {
			$aAvailables[$sComponent] = ["object"=>$sComponent, "class"=>$aComponent[0], "documentation"=>"https://github.com/hytcom/wiki/blob/master/nogal/docs/".$sComponent.".md"];
		}
		return $aAvailables;
	}

	public static function is($obj, $sType=null) {
		if(\method_exists($obj, "__me__")) {
			$aAvailables = \array_merge(self::$vCoreLibs, self::$vLibraries);
			$object = $obj->__me__();
			if(isset($aAvailables[$object->name]) && $aAvailables[$object->name][0]==$object->class) {
				if($sType!==null) {
					return ($aAvailables[$object->name][0]==$aAvailables[\strtolower($sType)][0]) ? true : false;
				}
				return true;
			}
			return false;
		} else {
			return false;
		}
	}

	protected static function Libraries() {
		$aAvailables = [];
		foreach(self::$vLibraries as $sLib => $aLib) {
			$aAvailables[$sLib] = $aLib[1];
		}
		//sort($aAvailables);
		return $aAvailables;
	}

	public static function log($sFileName, $sContent) {
		if(\is_dir(NGL_PATH_LOGS) && \is_writable(NGL_PATH_LOGS)) {
			$sFileName = self::call()->sandboxPath(NGL_PATH_LOGS.NGL_DIR_SLASH.$sFileName);
			$sFileName = self::call()->clearPath($sFileName);
			if(\file_exists($sFileName) && !\is_writable($sFileName)) { return false; }
			\file_put_contents($sFileName, $sContent, FILE_APPEND);
		}
	}

	public static function chkreferer($bReturnMode=false) {
		if(!isset($_SERVER["HTTP_REFERER"])) {
			if($bReturnMode) { return false; }
			self::call()->errorPages(403);
		} else {
			if(\strpos($_SERVER["HTTP_REFERER"], NGL_URL)===false) {
				if($bReturnMode) { return false; }
				self::call()->errorPages(403);
			}
		}
		
		if($bReturnMode) { return true; }
	}

	public static function passwd($sPassword, $bDecrypt=false) {
		if(NGL_PASSWORD_KEY!==null && self::call()->exists("crypt")) {
			if($bDecrypt) {
				$sPassword = \base64_decode($sPassword);
				$sPassword = self::call("crypt")->type("aes")->key(NGL_PASSWORD_KEY)->decrypt($sPassword);
			} else {
				$sPassword = self::call("crypt")->type("aes")->key(NGL_PASSWORD_KEY)->encrypt($sPassword);
				$sPassword = \base64_encode($sPassword);
			}
		}
		
		return $sPassword;
	}

	public static function out($sMessage, $sStyle=null, $bNewLine=true) {
		$aStyles = [
			"success" => "\033[0;92m%s\033[0m",
			"error" => "\033[1;37;41m%s\033[0m",
			"info" => "\033[96;96m%s\033[0m",
			"light" => "\033[93;93m%s\033[0m",
			"bold" => "\033[1m%s\033[0m"
		];
	
		$sFormat = '%s';
	
		if(isset($aStyles[$sStyle])) { $sFormat = $aStyles[$sStyle]; }
		if($bNewLine) { $sFormat .= PHP_EOL; }
	
		\printf($sFormat, $sMessage);
	}

	public static function objects() {
		return self::$aObjects;
	}

	public static function objectName($sObjectName) {
		$sObjectName = \preg_replace("/[^a-zA-Z0-9_\.]/is", "", $sObjectName);
		return \strtolower($sObjectName);
	}

	/** FUNCTION {
		"name" : "parseConfigFile",
		"type" : "public",
		"description" : "Parsea un archivo de configuración basado en una estructura enriquecida de los archivos .ini",
		"parameters" : {
			"$sFilePath" : ["string", "Ruta del archivo de configuración"],
			"$bUseSections" : ["boolean", "Indica si el archivo está divido en secciones", "false"]
		},
		"return" : "array o null"
	} **/
	public static function parseConfigFile($sFilePath, $bUseSections=false) {
		$sFilePath = \preg_replace("/[\\\\\/]{1,}/", NGL_DIR_SLASH, $sFilePath);
		$sFilePath = \rtrim($sFilePath, NGL_DIR_SLASH);
		if(\file_exists($sFilePath)) {
			$sContent = \file_get_contents($sFilePath);
			return self::parseConfigString($sContent, $bUseSections);
		}
		return null;
	}

	/** FUNCTION {
		"name" : "parseConfigString",
		"type" : "public",
		"description" : "Parsea una cadena de configuración basada en una estructura enriquecida de los archivos .ini",
		"parameters" : {
			"$sString" : ["string", "Origen de datos"],
			"$bUseSections" : ["boolean", "Indica si el archivo está divido en secciones", "false"]
		},
		"return" : "array"
	} **/
	public static function parseConfigString($sString, $bUseSections=false, $bPreserveNL=false) {
		if($bPreserveNL) {
			$NL = self::call()->unique(6);
			$sString = \preg_replace("/(\\\(\\r\\n|\\n))/", $NL, $sString);
		}
		$sString = \preg_replace("/\\\(\\r\\n|\\n)/s", "", $sString);

		$aData = [];
		$aLines = \explode(chr(10), $sString);

		$sSection = null;
		foreach($aLines as $sLine) {
			if($bUseSections) {
				$bSection = \preg_match("/^(\[)([a-zA-Z0-9\_\.\-]+)(\])/is", $sLine, $aMatchs);
				if($bSection) {
					$sSection = $aMatchs[2];
					continue;
				}
			}

			$bStatement = \preg_match("/^(?!;)([\w+\.\-\/]+)(\[[\w+\.\-\/]*\])?\s*=\s*(.*)\s*$/s", $sLine, $aMatchs);
			if($bStatement) {
				$sKey	= $aMatchs[1];
				$sIndex	= (!empty($aMatchs[2])) ? \substr($aMatchs[2], 1, -1) : null;
				$mValue	= $aMatchs[3];
				if(\preg_match("/^(((\"|\')(.*)(\"|\'))?.*)(;.*)?$/is", $mValue, $aValue)) {
					if(!\array_key_exists(4, $aValue)) {
						$aValue = \explode(";", $aValue[1]);
						$mValue	= $aValue[0];
					} else {
						$mValue	= \strlen($aValue[4]) ? $aValue[4] : "";
					}
				}
				
				$mValue = \trim($mValue);

				// constantes
				$mValue = \preg_replace_callback(
					"/\{@([a-z_][a-z0-9_]*)\}/i", 
					function($aMatches) {
						return (\defined($aMatches[1])) ? \constant($aMatches[1]) : $aMatches[1];
					},
					$mValue
				);

				// booleans
				switch(\strtolower($mValue)) {
					case "null": $mValue = null; break;
					case "false": $mValue = false; break;
					case "true": $mValue = true; break;
				}

				// multilinea
				if($bPreserveNL) { $mValue = \str_replace($NL, chr(10), $mValue); }

				if($sSection!==null) {
					if($sIndex!==null) {
						if($sIndex!="") {
							$aData[$sSection][$sKey][$sIndex] = $mValue;
						} else {
							$aData[$sSection][$sKey][] = $mValue;
						}
					} else {
						$aData[$sSection][$sKey] = $mValue;
					}
				} else {
					if($sIndex!==null) {
						if($sIndex!="") {
							$aData[$sKey][] = $mValue;
						} else {
							$aData[$sKey][$sIndex] = $mValue;
						}
					} else {
						$aData[$sKey] = $mValue;
					}
				}
			}
		}

		return $aData;
	}

	public static function path($sPath) {
		$sPath = \strtolower($sPath);
		if(isset(self::$vPaths[$sPath])) { return self::$vPaths[$sPath]; }
		return null;
	}
	
	public static function setPath($sPath) {
		$sBaseDir = \preg_replace("/[\\\\\/]{1,}/", NGL_DIR_SLASH, NGL_PATH_FRAMEWORK);
		self::$vPaths[$sPath] = \realpath($sBaseDir.NGL_DIR_SLASH.$sPath).NGL_DIR_SLASH;
	}

	public static function starTime() {
		return self::$nStarTime;
	}

	public static function tempDir() {
		if(!function_exists("sys_get_temp_dir") ) {
			if(!empty($_ENV["TMP"])) { return \realpath($_ENV["TMP"]); }
			if(!empty($_ENV["TMPDIR"])) { return \realpath($_ENV["TMPDIR"]); }
			if(!empty($_ENV["TEMP"])) { return \realpath($_ENV["TEMP"]); }

			$sTempFile = \tempnam(self::call()->unique(16), "");
			if($sTempFile) {
				$sTempDir = \realpath(\dirname($sTempFile));
				\unlink($sTempFile);
				return $sTempDir;
			} else {
				return false;
			}
		}
		
		return \sys_get_temp_dir();
	}

	public static function whois($sElement=null) {
		if($sElement===null) {
			$aLibraries = self::$vLibraries;
			$aMethods = \get_class_methods(__CLASS__);
			foreach($aMethods as $nKey => $sMethod) {
				if($sMethod[0]=="_") { unset($aMethods[$nKey]); }
			}
			
			$vInfo = [];
			$vInfo["objects"] = \array_keys($aLibraries);
			$vInfo["methods"] = $aMethods;
			
		} else {
			$sElement = \strtolower($sElement);
			$vInfo = self::call($sElement)->Whoami();
		}
		return $vInfo;
	}
}

?>