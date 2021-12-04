<?php
namespace nogal;

/*
load:
	classname
	path/subpath/classname
	\\namespace1\\namespace2\\classname
	\\namespace1\\namespace2\\path/subpath/classname

	classname = php classname and php file
*/
class nglGraft extends nglTrunk {

	public $graft;
	private $reflect;

	public function __init__() {
	}

	final public function load($sGraftName, $mArguments=null) {
		$aGraftClass = \explode("\\", $sGraftName);
		$sGraftFile = $aGraftClass[\count($aGraftClass)-1];
		$aGraftFile = \explode("/", $sGraftFile);

		if(\is_array($aGraftClass) && \count($aGraftClass)>1) {
			$sLast = \array_pop($aGraftClass);
			$sGraftClass = (\count($aGraftFile)>1) ? \implode("\\", $aGraftClass)."\\".$aGraftFile[count($aGraftFile)-1] : \implode("\\", $aGraftClass)."\\".$sLast;
 		} else {
			$sGraftClass = $aGraftFile[\count($aGraftFile)-1];
		}
		$sGraftFile = self::call()->clearPath(NGL_PATH_GRAFTS.NGL_DIR_SLASH.$sGraftFile.".php");

		if(\file_exists($sGraftFile)) {
			require_once($sGraftFile);
			$this->reflect = new \ReflectionClass($sGraftClass);
			foreach($this->reflect->getMethods() as $method) {
				if(\in_array($method->name, ["__init__", "load", "__call", "__set", "__get", "__methods"])) {
					self::errorMessage("graft", 1004, $method->class."::".$method->name." is a reserved method name", "die");
				}
			}
			if(!\is_array($mArguments) && $mArguments!==null) { $mArguments = [$mArguments]; }
			$this->graft = ($mArguments===null) ? $this->reflect->newInstanceArgs() : $this->reflect->newInstanceArgs($mArguments);
			return $this;
		}

		return null;
	}

	final public function __vendor__() {
		if(\file_exists(NGL_PATH_GRAFTS.NGL_DIR_SLASH."vendor".NGL_DIR_SLASH."autoload.php")) {
			require_once(NGL_PATH_GRAFTS.NGL_DIR_SLASH."vendor".NGL_DIR_SLASH."autoload.php");
		}
	}

	final public function __call($sMethod, $aArguments) {
		if(\method_exists($this->graft, $sMethod)) {
			return $this->graft->$sMethod (...$aArguments);
		}
	}

	final public function __get($sProperty) {
		if(!empty($this->graft) && \property_exists($this->graft, $sProperty)) {
			return $this->graft->$sProperty;
		}
	}

	final public function __set($sProperty, $mValue) {
		if(\property_exists($this->graft, $sProperty)) {
			return $this->graft->$sProperty = $mValue;
		}
	}

	final public function __methods() {
		$aMethods = [];
		foreach($this->reflect->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
			$sMethod = $method->getName();
			$aParams = [];
			foreach($method->getParameters() as $param) {
				$sName = $param->getName();
				$aParams[$sName] = [
					"name" => $sName,
					"type" => $param->getType(),
					"optional" => $param->isOptional()
				];
				if($aParams[$sName]["optional"]) { $aParams[$sName]["default"] = $param->getDefaultValue(); }
			}
			$aMethods[$sMethod] = [
				"name" => $sMethod,
				"parameters" => $aParams
			];
		}
		
		return $aMethods;
	}
}

?>