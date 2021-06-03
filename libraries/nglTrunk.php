<?php

namespace nogal;

class nglTrunk extends nglRoot {

	final public function __construct() {
		if(self::$bLoadAllowed===false) {
			\trigger_error("Can't instantiate outside of the «nogal» environment", E_USER_ERROR);
			die();
		}
		
		if(\method_exists($this, "__builder__")) {
			$this->__builder__(\func_get_args());
		}

		$this->__errorMode__();
	}

	final public function __configFile__() {
		$aExplained = [];
		if(\file_exists(NGL_PATH_FRAMEWORK.NGL_DIR_SLASH."docs".NGL_DIR_SLASH.$this->object.".info")) {
			if(($sConfig = \file_get_contents(NGL_PATH_FRAMEWORK.NGL_DIR_SLASH."docs".NGL_DIR_SLASH.$this->object.".info"))) {
				$aData = self::parseConfigString($sConfig, true, true);
				if(isset($aData["documentation"])) { $aExplained["documentation"]["url"] = $aData["documentation"]["url"]; }
			}
		}

		if(\method_exists($this, "__defaults__")) { $aDefault = $this->__defaults__(); }
		foreach($aData as $sSection => $aSectionValues) {
			$aExplained[$sSection] = [];
			if($sSection=="arguments" && isset($aDefault)) {
				foreach($aDefault as $sArgument => $mValue) {
					$aExplained[$sSection][$sArgument] = [$mValue];
					if(isset($aData, $aData[$sSection][$sArgument])) { $aExplained[$sSection][$sArgument][] = $aData[$sSection][$sArgument]; }
				}
			} else {
				foreach($aSectionValues as $sArgument => $mValue) {
					$aExplained[$sSection][$sArgument] = $mValue;
				}
			}
		}

		// config file
		$sContent = "";
		foreach($aExplained as $sSection => $aValues) {
			$sContent .= "[".$sSection."]\n";
			foreach($aValues as $sKey => $mValue) {
				$sValue = (\is_array($mValue)) ? $mValue[0] : $mValue;
				if($sKey!="_help") {
					switch(true) {
						case $sValue===null: $sArgument = $sKey." = null"; break;
						case $sValue===false: $sArgument = $sKey." = false"; break;
						case $sValue===true: $sArgument = $sKey." = true"; break;
						case \is_numeric($sValue): $sArgument = $sKey." = ".$sValue; break;
						default: 
							if(\strstr($sValue, '"')!==false) {
								$sArgument = $sKey." = '".$sValue."'";
							} else {
								$sArgument = $sKey." = \"".$sValue."\"";
							}
						break;
					}

					if(\is_array($mValue) && isset($mValue[1])) { $sContent .= ";".\implode(\chr(10).";", \explode(\chr(10), $mValue[1]))."\n"; }
					$sContent .= $sArgument."\n";
					if($sSection=="arguments") { $sContent .= "\n"; }
				} else {
					$sContent .= ";".\implode(\chr(10).";", \explode(\chr(10), $sValue))."\n";
				}
			}

			$sContent .= "\n";
		}

		if(\is_writeable(NGL_PATH_CONF) && !\file_exists(NGL_PATH_CONF.NGL_DIR_SLASH.$this->object.".conf")) {
			\file_put_contents(NGL_PATH_CONF.NGL_DIR_SLASH.$this->object.".conf", $sContent);
		} else {
			return $sContent;
		}
	}

	final public function __errorMode__($sMode=NGL_HANDLING_ERRORS_MODE) {
		return self::errorMode($this->object, $sMode);
	}

	/** FUNCTION {
		"name" : "__me__", 
		"type" : "public",
		"description" : "Retorna un objeto o array con los nombre objeto y clase a la que instancia",
		"parameters" : { "$bArray" : ["boolean", "Si el valor es True se retorna un array", "false"] },
		"examples" : {
			"objeto" : "
				$object->name = nombre del objeto;
				$object->class = nombre de la clase;
			",
			"array" : "
				array(
				→ "0" => "nombre del objeto",
				→ "1" => "nombre de la clase",
				→ "name" => "nombre del objeto",
				→ "class" => "nombre de la clase",
				);
			"
		},
		"return" : "object o array"
	} **/
	final public function __me__($bArray=false) {
		if(!$bArray) {
			$me = new \stdClass();
			$me->name = $this->me;
			$me->class = $this->class;
			return $me;
		} else {
			$vMe = [];
			$vMe[0] 		= $this->me;
			$vMe[1] 		= $this->class;
			$vMe["name"]	= $this->me;
			$vMe["class"]	= $this->class;
			return $vMe;
		}
	}
}

?>