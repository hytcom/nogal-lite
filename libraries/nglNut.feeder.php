<?php
/*
# Nogal
*the most simple PHP Framework* by hytcom.net
GitHub @hytcom
___
  
# nut
## nglNut *extends* nglTrunk [2018-08-22]
Gestor de los nuts del sistema

https://github.com/hytcom/wiki/blob/master/nogal/docs/nut.md
https://github.com/hytcom/wiki/blob/master/nogal/docs/nuts.md

*/
namespace nogal;

class nglNut extends nglTrunk {

	public $sNut			= null;
	private $bSafemode		= true;
	private $aAllowedMethods;
	
	protected $ID			= null;
	protected $class		= "nglNut";
	protected $me			= "nut";
	protected $object		= "nut";
	protected $aSafeMethods	= [];


	final public function __init__($sNutID=null, $aMethods=null) {
		$this->ID = ($sNutID!==null) ? $sNutID : $this->ngl()->unique();
		$this->aAllowedMethods = $aMethods;
		return $this->ID;
	}

	final public function arg($vArguments, $sIndex, $mDefault=null) {
		return (isset($vArguments[$sIndex])) ? \trim($vArguments[$sIndex], "\t\n\r\0\x0B") : $mDefault;
	}

	final public function ngl($sObjectName="fn") {
		return self::call($sObjectName);
	}

	final public function load($sNutName, $aConfig=null) {
		$sNutName = \strtolower($sNutName);
		$sNutID = (!empty($aConfig["nutid"])) ? $aConfig["nutid"] : null;
		if($sNutID!==null && isset(self::$aNutsLoaded[$sNutID])) { return self::$aNutsLoaded[$sNutID]; }

		if(!$this->nut($sNutName)) {
			$sFilePath = self::call()->clearPath(NGL_PATH_NUTS.NGL_DIR_SLASH.$sNutName.".php");
			if(\file_exists($sFilePath)) {
				require_once($sFilePath);
				if(\strpos($sNutName, "_")) {
					$sNutName = \str_replace("_", " ", $sNutName);
					$sNutName = \ucwords($sNutName);
					$sNutName = \str_replace(" ", "", $sNutName);
				}
				
				$this->sNut = $sNutName;
				$sClassName = __NAMESPACE__."\\nut".$sNutName;

				$reflection = new \ReflectionClass($sClassName);
				$aMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
				$sClass = \strtolower($sClassName);
				foreach($aMethods as $vMethod) {
					$sMethod = \strtolower($vMethod->class);
					if($sMethod!=$sClass) { break; }
					if($sMethod==$sClass) {
						self::errorMessage($this->object, 1002);
					}
				}
				
				// metodos permitidos del nut
				$aAllowedMethods = [];
				$aMethods = $reflection->getMethods(\ReflectionMethod::IS_PROTECTED);
				foreach($aMethods as $vMethod) {
					$sClassMethod = \strtolower($vMethod->class);
					if($sClassMethod==$sClass) {
						$aAllowedMethods[\strtolower($vMethod->name)] = true;
					}
				}
				unset($aAllowedMethods["init"]);
				
				$this->nut($sNutName, $sClassName, $aAllowedMethods);
			}
		}

		$aNut = $this->nut($sNutName);
		$sClassName = $aNut[0];
		if(\class_exists($sClassName)) {
			self::$bLoadAllowed = true;
			$nut = new $sClassName();
			self::$bLoadAllowed = false;
			$sNutID = $nut->__init__($sNutID, $aNut[1]);
			if(\method_exists($nut, "init")) { $nut->init(); }
			self::$aNutsLoaded[$sNutID] = $nut;
			return $nut;
		} else {
			self::errorMessage($this->object, 1001, $sClassName);
		}
	}

	final public function ifmethod($sFunction) {
		return (\method_exists($this, $sFunction));
	}

	final public function run($sMethod, $aArguments=null) {
		if(!isset($this->aAllowedMethods[\strtolower($sMethod)])) { \trigger_error("Nonexistent method", E_USER_ERROR); }

		if($this->bSafemode && !\in_array($sMethod, $this->aSafeMethods)) {
			return self::errorMessage($this->object, 1003, $this->sNut."::".$sMethod);
		} else {
			if($aArguments==null) {
				$aDataArguments = [];
			} else {
				$aDataArguments = [];
				foreach($aArguments as $sKey => $mValue) {
					$sKey = \strtolower($sKey);
					if(\strpos($sKey, "data-")===0) {
						$aDataArguments["DATA"][\substr($sKey, 5)] = $mValue;
					} else {
						$aDataArguments[$sKey] = $mValue; 
					}
				}
			}

			return \call_user_func([$this, $sMethod], $aDataArguments);
		}
	}

	final public function safemode($bMode=true) {
		$this->bSafemode = self::call()->isTrue($bMode);
		return $this;
	}

	final protected function SafeMethods($aSafeMethods=null) {
		if($aSafeMethods!==null) { $this->aSafeMethods = $aSafeMethods; }
		return $aSafeMethods;
	}
}

?>