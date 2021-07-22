<?php

namespace nogal;
/**

echo $ngl("bee")
	->dump(true)
	->login("QUEENBEE")
	->bzzz(<<<BEE
file load https://cdn.bithive.cloud/json/material-design-colors.json
-$: read
shift convert ["-$:", "json-array"]
@get -$: pink
bzzz
BEE
);

*/
class nglBee extends nglFeeder implements inglFeeder {

	private $output;
	private $error;
	private $aObjs;
	private $aLibs;
	private $aVars;
	private $aLoops;
	private $sSeparator;
	private $bDump;

	final public function __init__($mArguments=null) {
		if(!\defined("NGL_BEE") || NGL_BEE===null || NGL_BEE===false) {
		if(NGL_TERMINAL) {
			$this->__errorMode__("shell");
			self::errorMode("shell");
		} else {
			$this->__errorMode__("die");
		}
		self::errorMessage("bee", null, "Bee is not available"); }

		$this->sDelimiter = "(\r\n|\n)";
		$this->aLibs = self::Libraries();
		$this->aObjs = [];
		$this->aLoops = [];
		$this->sSeparator = "\t";
		$this->bDump = false;

		$aVars = [
			"_SERVER" => $_SERVER,
			"_ENV" => $_ENV,
			"ENV" => $GLOBALS["ENV"]
		];
		if(isset($_SESSION)) { $aVars["_SESSION"] = $_SESSION; }
		$this->aVars = $aVars;
	}

	public function bzzz($sSentences, $bAutoSave=false) {
		if(!NGL_TERMINAL && NGL_BEE!==true && !isset($_SESSION[NGL_SESSION_INDEX]["FLYINGBEE"])) {
			self::errorMessage("bee", null, "You must login to use the Terminal");
		}
		if(!self::call()->isUTF8($sSentences)) { $sSentences = \utf8_encode($sSentences); }
		
		$aCommands = $this->Parse($sSentences);
		$this->error = false;

		if($this->RunCommands($aCommands)) {
			if($this->output===null) { $this->output = "NULL"; }
			if($this->bDump) {
				if(\is_object($this->output) && \is_subclass_of($this->output, "nogal\\nglTrunk")) { return "TRUE"; }
				return self::call()->dump($this->output);
			} else {
				return $this->output;
			}
		} else {
			$this->error = true;
		}
	}

	public function error() {
		return $this->error;
	}

	public function login($sPassword) {
		if($sPassword===NGL_BEE) {
			$_SESSION[NGL_SESSION_INDEX]["FLYINGBEE"] = true;
			return $this;
		}
		return false;
	}

	public function dump($bDump) {
		$this->bDump = ($bDump===false) ? false : true;
		return $this;
	}

	private function RunCommands($aCommands) {
		for($x=0; $x<\count($aCommands); $x++) {
			$aCommand = $aCommands[$x];

			if($aCommand[0]=="@loop") {
				$aSource = $this->Argument($aCommand["source"]);
				if(!\is_array($aSource)) {
					if(\preg_match("/^([0-9]+)(:)([0-9]+)$/", $aSource, $aMatchs)) {
						$aSource = \range($aMatchs[1], $aMatchs[3]);
					}
				}
				$aSubCommands = \array_slice($aCommands, $x+1, $aCommand["to"]);
				foreach($aSource as $aCurrent) {
					$this->output = $aCurrent;
					$this->RunCommands($aSubCommands);
				}
				$x += \count($aSubCommands);
			} else {
				$bReturn = $this->RunCmd($aCommand);
				if(!$bReturn) { return false; }
			}
		}

		return true;
	}

	private function RunCmd($aCommand) {
		$sCmd = $aCommand[0];

		if($sCmd[0]==="@") {
			if($sCmd=="@php") {
				\array_shift($aCommand);
				$this->output = \call_user_func_array([$this, "FuncPhp"], $aCommand);
				return true;
			} else {
				$sCmd = \substr($sCmd, 1);
				\array_shift($aCommand);
				\call_user_func_array([$this, "Func".$sCmd], $aCommand);
				return true;
			}
		} else if($sCmd==='-$:') {
			$obj = $this->output;
		} else if(isset($this->aLibs[$sCmd])) {
			if(!isset($this->aObjs[$sCmd])) {
				if(!($this->aObjs[$sCmd] = self::call($sCmd))) { die(self::errorMessage(null)); }
				$this->aObjs[$sCmd]->errorMode("return");
			}
			$obj = $this->aObjs[$sCmd];
		}
		$aLastError = self::errorGetLast();
		if(\is_array($aLastError) && \count($aLastError)) { die(self::errorMessage(null)); }

		$aArgs = null;
		if(\array_key_exists(2, $aCommand)) {
			$aArgs = $this->Argument($aCommand[2], true);
		}

		if(\array_key_exists(1, $aCommand)) {
			if(\method_exists($obj, $aCommand[1]) || (\is_object($obj) && \method_exists($obj, "isArgument") && $obj->isArgument($aCommand[1]))) {
				if($aArgs!==null) {
					$mOutput = \call_user_func_array([$obj,$aCommand[1]], $aArgs);
					if(\strtolower($sCmd)=="graft") { $mOutput = $mOutput->graft; }
				} else {
					$mOutput = \call_user_func([$obj,$aCommand[1]]);
				}
			} else {
				if(NGL_TERMINAL) {
					self::errorMessage("bee", null, "The required method '".$aCommand[1]."' does not exist for '".$sCmd."'", "shell");
				} else {
					self::errorMessage("bee", null, "The required method '".$aCommand[1]."' does not exist for '".$sCmd."'", "die");
				}
			}

			$this->output = $mOutput;
		}

		return true;
	}

	private function Parse($sSentences) {
		$aToRun = [];
		$sSentences = \preg_replace("/( )*\\\\".$this->sDelimiter."/", "", $sSentences);
		$aSentences = \preg_split("/".$this->sDelimiter."/", $sSentences."\n");

		$x = -1;
		foreach($aSentences as $sSentence) {
			$sSentence = \trim($sSentence);
			if($sSentence==="" || $sSentence[0]=="#" || $sSentence[0].$sSentence[1]=="//") { continue; }
			$x++;
			$aCommand = \explode(" ", $sSentence, 3);
			$sCmd = \strtolower($aCommand[0]);
			
			if($sCmd=="@loop") {
				$this->aLoops[] = $x;
				$aToRun[$x] = [$sCmd, "source"=>$aCommand[1], "from"=>$x+1, "to"=>$x+2];
			} else if($sCmd=="endloop") {
				$l = \array_pop($this->aLoops);
				$aToRun[$l]["to"] = $x-$aToRun[$l]["from"];
				$x--;
			} else {
				$aToRun[$x] = $aCommand;
			}
		}

		return $aToRun;
	}

	private function FuncLogout() {
		unset($_SESSION[NGL_SESSION_INDEX]["FLYINGBEE"]);
		$this->output = "bye";
	}

	private function FuncLogin() {
		list($sPassword) = \func_get_args();
		$this->login($sPassword);
	}

	private function FuncClear() {
		$this->output = "";
	}

	private function FuncSet() {
		list($sVarname, $mValue) = \func_get_args();
		if($mValue==='-$:') {
			$this->aVars[$sVarname] = $this->output;
		} else {
			$this->aVars[$sVarname] = $mValue;
		}
	}

	private function FuncSeparator() {
		list($sSeparator) = \func_get_args();
		$this->sSeparator = $sSeparator;
	}

	// @get ENV now
	// @get ENV [now, date]
	private function FuncGet() {
		@list($sVarname, $mIndex) = \func_get_args();
		$mVar = ($sVarname==='-$:') ? $this->output : $this->aVars[$sVarname];
		$this->output = $mVar;
		if($mIndex!==null) {
			$mIndex = $this->Argument($mIndex);
			if(\is_array($mIndex)) {
				foreach($mIndex as $sIndex) {
					if(\array_key_exists($sIndex, $this->output)) {
						$this->output = $this->output[$sIndex];
					}
				}
			} else {
				$this->output = $this->output[$mIndex];
			}
		}
	}

	private function FuncPrint() {
		$mValue = \implode(" ", \func_get_args());
		$mValue = $this->Argument($mValue);
		if(\is_array($mValue)) { $mValue = \implode($this->sSeparator, $mValue); }
		$mValue = \preg_replace("/[\\\n]/is", "\n", $mValue);
		print($mValue);
	}

	private function FuncLaunch() {
		$mValue = \implode(" ", \func_get_args());
		$sURL = $this->Argument($mValue);
		\header("location:".$sURL);
		exit();
	}

	private function FuncPhp() {
		$aArguments = \func_get_args();
		$sFunction = $aArguments[0];
		if(isset($aArguments[1])) {
			return \call_user_func_array($sFunction, $this->Argument($aArguments[1], true));
		} else {
			return \call_user_func($sFunction);
		}
	}

	private function Argument($mArgument, $bToRun=false) {
		if(\is_array($mArgument)) {
			foreach($mArgument as &$mArg) {
				$mArg = $this->Argument($mArg);
			}
			unset($mArg);
			return $mArgument;
		}

		$sArgument = \trim($mArgument);
		$sArgument = \trim($sArgument, '"');

		if($sArgument==='-$:') {
			return ($bToRun) ? [$this->output] : $this->output;
		} else if($sArgument==":true:") {
			return [true];
		} else if($sArgument==":false:") {
			return [false];
		} else if($sArgument==":null:") {
			return [null];
		} else {
			$sArgument = \preg_replace(["/(?<!\\\\)\\\\n/is", "/(?<!\\\\)\\\\r/is", "/(?<!\\\\)\\\\t/is"], ["\n","\r","\t"], $sArgument);
			if(isset($sArgument[0]) && ($sArgument[0]=="{" || $sArgument[0]=="[")) {
				$aArgs = \json_decode($sArgument, true, 512, JSON_UNESCAPED_UNICODE);
				if(\is_array($aArgs)) {
					foreach($aArgs as $mKey => $mValue) {
						if($mValue==='-$:') {
							$aArgs[$mKey] = $this->output;
						} else if($sArgument==":true:") {
							return [true];
						} else if($sArgument==":false:") {
							return [false];
						} else if($sArgument==":null:") {
							return [null];
						} else if(\is_array($mValue)) {
							$aArgs[$mKey] = $this->Argument($mValue, $bToRun);
						} else if(!\is_array($mValue) && preg_match_all("/\{(\\$|@)([a-z][0-9a-z_]*)\}/is", $mValue, $aMatchs, PREG_SET_ORDER)) {
							if($mValue==$aMatchs[0][0] && array_key_exists($aMatchs[0][2], $this->aVars)) {
								$aArgs[$mKey] = $this->aVars[$aMatchs[0][2]];
							} else if($mValue==$aMatchs[0][0] && \defined($aMatchs[0][2])) {
								$aArgs[$mKey] = \constant($aMatchs[0][2]);
							} else {
								foreach($aMatchs as $aMatch) {
									if(\array_key_exists($aMatch[2], $this->aVars)) {
										$mValue = \str_replace($aMatch[0], $this->aVars[$aMatch[2]], $mValue);
									} else if(\defined($aMatchs[2])) {
										$mValue = \str_replace($aMatch[0], \constant($aMatchs[0][2]), $mValue);
									}
								}
								$mValue = \str_replace('-$:', $this->output, $mValue);
								$aArgs[$mKey] =  $mValue;
							}
						} else {
							$sReplace = "";
							if(\strpos($mValue, '-$:')!==false) {
								if(\is_array($this->output)) {
									foreach($this->output as $mValue) {
										if(\is_array($mValue)) { 
											$bArrayArray = true;
											break;
										}
									}
									if(!isset($bArrayArray)) { $sReplace = \implode($this->sSeparator, $this->output); }
								} else {
									$sReplace = $this->output;
								}
								$aArgs[$mKey] = \str_replace('-$:', $sReplace, $mValue);
							}
						}
					}

					return $aArgs;
				}
			}

			if(\preg_match_all("/\{(\\$|@)([a-z][0-9a-z_]*)\}/is", $sArgument, $aMatchs, PREG_SET_ORDER)) {
				// variables
				if($sArgument==$aMatchs[0][0] && \array_key_exists($aMatchs[0][2], $this->aVars)) {
					return ($bToRun) ? [$this->aVars[$aMatchs[0][2]]] : $this->aVars[$aMatchs[0][2]];
				}

				// constantes
				if($sArgument==$aMatchs[0][0] && \defined($aMatchs[0][2])) {
					return ($bToRun) ? [\constant($aMatchs[0][2])] : \constant($aMatchs[0][2]);
				}
	
				foreach($aMatchs as $aMatch) {
					if(\array_key_exists($aMatch[2], $this->aVars)) {
						$sArgument = \str_replace($aMatch[0], $this->aVars[$aMatch[2]], $sArgument);
					} else if(\defined($aMatchs[2])) {
						$sArgument = \str_replace($aMatch[0], \constant($aMatchs[0][2]), $sArgument);
					}
				}
				$sArgument = \str_replace('-$:', $this->output, $sArgument);
				return ($bToRun) ? [$sArgument] : $sArgument;
			}
		}

		if(strpos($sArgument, '-$:')!==false) {
			$sReplace = (\is_array($this->output)) ? self::call()->imploder($this->sSeparator, $this->output) : $this->output;
			$sArgument = \str_replace('-$:', $sReplace, $sArgument);
		}

		return ($bToRun) ? [$sArgument] : $sArgument;
	}
}

?>