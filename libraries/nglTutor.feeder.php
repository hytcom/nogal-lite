<?php

namespace nogal;

class nglTutor extends nglTrunk {

	private $aAllowedMethods;
	private $bLockable			= false;
	private $bLocked			= false;
	private $sTutorName;
	private $sMethodName;
	protected $class			= "nglTutor";
	protected $me				= "tutor";
	protected $object			= "tutor";
	protected $bDebug			= false;
	protected $master;
	protected $aNulls;
	protected $aEmpty;
	protected $aZeros;

	final public function __init__($sTutorID=null, $aMethods=null) {
		$this->ID = ($sTutorID!==null) ? $sTutorID : self::call()->unique();
		$this->aNulls = [];
		$this->aEmpty = [];
		$this->aZeros = [];
		$this->aAllowedMethods = $aMethods;
		return $this->ID;
	}

	final public function debugging() {
		return $this->bDebug;
	}

	final public function load($sTutorName, $mArguments=null, $sTutorID=null) {
		if($sTutorID!==null && isset(self::$aTutorsLoaded[$sTutorID])) { return self::$aTutorsLoaded[$sTutorID]; }

		if(!$this->tutor($sTutorName)) {
			$sTutorFile = self::call()->clearPath(NGL_PATH_TUTORS.NGL_DIR_SLASH.$sTutorName.".php");
			if(\file_exists($sTutorFile)) {
				require_once($sTutorFile);
				
				$this->TutorName = $sTutorName;
				$sClassName = __NAMESPACE__."\\tutor".$sTutorName;

				// aborta si encuentra metodos publicos en el tutor
				$reflection = new \ReflectionClass($sClassName);
				$aMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
				$sClass = \strtolower($sClassName);
				foreach($aMethods as $vMethod) {
					$sClassMethod = \strtolower($vMethod->class);
					if($sClassMethod!=$sClass) { break; }
					if($sClassMethod==$sClass) {
						self::errorMessage($this->object, 1001);
					}
				}
				
				// metodos permitidos del tutor
				$aAllowedMethods = ["debug"=>true];
				$aMethods = $reflection->getMethods(\ReflectionMethod::IS_PROTECTED);
				foreach($aMethods as $vMethod) {
					$sClassMethod = \strtolower($vMethod->class);
					if($sClassMethod==$sClass) {
						$aAllowedMethods[\strtolower($vMethod->name)] = true;
					}
				}
				unset($aAllowedMethods["init"]);

				$this->tutor($sTutorName, $sClassName, $aAllowedMethods);
			}
		}


		$aTutor = $this->tutor($sTutorName);
		$sClassName = $aTutor[0];
		if(\class_exists($sClassName)) {
			self::$bLoadAllowed = true;
			$tutor = new $sClassName();
			$tutor->TutorName($sTutorName);
			self::$bLoadAllowed = false;
			$sTutorID = $tutor->__init__($sTutorID, $aTutor[1]);
			if(\method_exists($tutor, "init")) { $tutor->init($mArguments); }
			self::$aTutorsLoaded[$sTutorID] = $tutor;
			return $tutor;
		} else {
			self::errorMessage($this->object, 1002, $sClassName);
		}
	}

	final public function run($sMethod, $aArguments=[]) {
		if(!isset($this->aAllowedMethods[\strtolower($sMethod)])) { \trigger_error("Nonexistent method", E_USER_ERROR); }
		if($this->bLocked) { \trigger_error("Can't run methods from a locked tutor", E_USER_ERROR); }
		if($this->bLockable) { $this->lock(); }
		
		if(\method_exists($this, $sMethod)) {
			$this->MethodName($sMethod);
			return \call_user_func([$this, $sMethod], $aArguments);
		}
		
		return null;
	}

	final protected function Alvin($sGrants=null) {
		if(NGL_ALVIN!==null) {
			if(!isset($_SESSION[NGL_SESSION_INDEX]) || !isset($_SESSION[NGL_SESSION_INDEX]["ALVIN"])) {
				\trigger_error("Only logged users can run this method", E_USER_ERROR);
			}

			$alvin = self::call("alvin");
			if(!empty($sGrants)) {
				if(!$alvin->loaded()) {
					if(!$alvin->autoload()) { \trigger_error("Alvin is not loaded", E_USER_ERROR); exit(); }
				}
				
				if($alvin->profile()=="ADMIN") { return $this; }

				if(!$alvin->check($sGrants)) { \trigger_error("You do not have grants to execute this action", E_USER_ERROR); exit(); }
				return $this;
			}
		}
		return $this;
	}

	final protected function Debug() {
		$this->bDebug = true;
		if(!$this->debuggable) { return "[[TUTOR-DEBUG]]<br /><br />Debug is not allowed"; }
		$fn = self::call("fn");
		$aArguments = ($this->sMethodName=="debug" && \count($_FILES)) ? \array_merge(\func_get_args(), $_FILES) : \func_get_args();
		return 
			"[[TUTOR-DEBUG]]".
			"<br /><br />".
			"<pre>\n".
			"METHOD: ".$_SERVER["REQUEST_METHOD"]."\n".
			"TUTOR: ".$this->sTutorName." / ".$this->sMethodName."\n".
			"</pre>\n".
			\call_user_func_array([$fn, "dumphtml"], $aArguments)
		;
	}

	final protected function Alert($sMessage="error") {
		$this->bDebug = true;
		return "[[TUTOR-ALERT]]".$sMessage;
	}

	final protected function Lock() {
		$this->bLocked = true;
		return $this;
	}

	final protected function Lockable() {
		$this->bLockable = true;
		$this->bLocked = true;
		return $this;
	}

	final protected function MethodName($sName) {
		$this->sMethodName = $sName;
		return $this;
	}

	final protected function Nulls($aData, $aNulls=null) {
		if($aNulls===null) { $aNulls = $this->aNulls; }
		if(\current($aNulls)===true) { $aNulls = \array_keys($aData); }
		if(!\is_array($aNulls) || !\count($aNulls)) { return $aData; }
		return self::call()->emptyToNull($aData, $aNulls);
	}

	final protected function Empty($aData, $aEmpty=null) {
		if($aEmpty===null) { $aEmpty = $this->aEmpty; }
		if(!\is_array($aEmpty) || !\count($aEmpty)) { return $aData; }
		return self::call()->nullToEmpty($aData, $aEmpty);
	}

	final protected function Zeros($aData, $aZeros=null) {
		if($aZeros===null) { $aZeros = $this->aZeros; }
		if(!\is_array($aZeros) || !\count($aZeros)) { return $aData; }
		return self::call()->emptyToZero($aData, $aZeros);
	}

	final protected function Sanitize($aData) {
		$aData = $this->Nulls($aData);
		$aData = $this->Empty($aData);
		return $this->Zeros($aData);
	}

	final private function TutorCaller($sCaller) {
		if($sCaller!==__FILE__) {
			\trigger_error("Can't instantiate outside of the «tutor» class", E_USER_ERROR);
		}
	}

	final protected function TutorName($sName) {
		$this->sTutorName = $sName;
		return $this;
	}

	final protected function Unlock() {
		$this->bLocked = false;
		return $this;
	}
}

?>