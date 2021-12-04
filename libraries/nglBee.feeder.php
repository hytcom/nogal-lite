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
	private $aIfs;
	private $sSeparator;
	private $bDump;
	private $sHelp;

	final public function __init__($mArguments=null) {
		if(!\defined("NGL_BEE") || NGL_BEE===null || NGL_BEE===false) {
			$this->__errorMode__("die");
			self::errorMessage("bee", null, "Bee is not available");
		}

		$this->sDelimiter = "(\r\n|\n)";
		$this->aLibs = self::Libraries();
		$this->aObjs = [];
		$this->aLoops = [];
		$this->aIfs = [];
		$this->sSeparator = "\t";
		$this->bDump = false;

		$aVars = [
			"_SERVER" => $_SERVER,
			"_ENV" => $_ENV,
			"ENV" => $GLOBALS["ENV"]
		];
		if(isset($_SESSION)) { $aVars["_SESSION"] = $_SESSION; }
		$this->aVars = $aVars;

$this->sHelp = <<<HELP

--------------------------------------------------------------------------------
	nogal bee -S:
--------------------------------------------------------------------------------
Uso:
	php bee [OPTIONS] <COMMAND>
	php bee [OPTIONS] -m"<COMMAND><SEPARATOR><COMMAND>"
	php bee [OPTIONS] (modo consola. para finalizar, usar bzzz)

Comandos:
	Las sintaxis de los comandos pueden ser:
		<OBJECT> <METHOD> [<ARGUMENT> ... <ARGUMENT>]
		<OBJECT>:<METHOD> [<ARGUMENT> ... <ARGUMENT>]

Opciones:
	-e    variable de entorno key=value
	-h    ayuda
	-m    permite ejecutar multiples comandos en línea
	-m@   multiples comandos con un separador distinto a |, en este caso, @
	-r    retorna el valor crudo de la respusts (raw)
	-f    carga el código a ejecutar, desde un archivo
	-s    modo silencioso, no tiene salida
	-v    nogal versión

Ejemplo
	# en línea
	php bee fn:imya

	# consola
	php bee
	file load https://cdn.upps.cloud/json/material-design-colors.json
	-$: read
	shift convert ["-$:", "json-ttable"]
	bzzz

--------------------------------------------------------------------------------

HELP;
	}

	public function bzzz($sSentences) {
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

	public function bzzzfile($sFilepath) {
		if($sBuffer = @\file_get_contents($sFilepath)) {
			$this->bzzz($sBuffer);
		} else {
			self::errorMessage("bee", null, "File not found: ".$sFilepath);
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

	public function terminal() {
		$this->__errorMode__("shell");
		if(!NGL_TERMINAL) { self::out("\nThis method is available only on a terminal\n", "error"); exit(); }
		if(empty($GLOBALS["argv"])) { exit("NULL"); }

		$aArguments = $GLOBALS["argv"];
		$aOptions = \getopt("h::s::r::m::f::v::e::");
		$bSilent = $bReturn = $bFromFile = false;
		$sBee = \basename(\array_shift($aArguments));

		if(isset($aOptions["h"]) && \count($aArguments)==1) { die($this->sHelp."\n"); }
		if(isset($aOptions["v"]) && \count($aArguments)==1) {
			if(\file_exists(NGL_PATH_FRAMEWORK."/version")) {
				die("v".\file_get_contents(NGL_PATH_FRAMEWORK."/version")."\n");
			} else {
				die("unknown version");
			}
		}

		if(\count($aArguments)) {
			foreach($aArguments as $k => $sArg) { if($sArg[0]=="-") { unset($aArguments[$k]); } }
		}

		$sVariables = "";
		if(isset($aOptions["e"])) {
			if(!\is_array($aOptions["e"])) { $aOptions["e"] = [$aOptions["e"]]; }
			foreach($aOptions["e"] as $sVar) {
				$aVar = \explode("=", $sVar);
				$sVariables .= "@set ".$aVar[0]." ".$aVar[1]."\n";
			}
		}

		if(isset($aOptions["s"])) { $bSilent = true; }
		if(isset($aOptions["r"])) { $bReturn = true; }
		if(isset($aOptions["f"])) {
			if(file_exists($aOptions["f"])) {
				$sCommand = \file_get_contents($aOptions["f"]);
				$bFromFile = true;
			} else {
				self::out("\nFile not found ".$aOptions["f"]."\n", "error"); exit();
			}
		}

		if(!$bFromFile) {
			if($sBee=="bee" && !\count($aArguments)) {
				$aBuffer = [];
				while(true) {
					$sInput = \readline();
					if($sInput=="bzzz") { break; }
					$aBuffer[] = $sInput;
				}
				$sCommand = \implode("\n",$aBuffer);
			} else {
				$sCommand = \implode(" ",$aArguments);
				if(isset($aOptions["m"])) {
					$sSplitter = empty($aOptions["m"]) ? "|" : $aOptions["m"];
					$sBuffer = $sCommand;
					$aBuffer = \explode($sSplitter, $sBuffer);
					$sCommand = \implode("\n",$aBuffer);
				}
			}
		}

		$sResponse = $this->dump(true)->bzzz($sVariables.$sCommand);
		if($this->error()) {
			if(!$bReturn) {
				self::out("\n".$sResponse."\n", "error"); exit();
			} else {
				exit("NULL");
			}
		} else {
			if(!$bSilent) {
				if(!$bReturn) {
					self::out($sResponse, "success"); exit();
				} else {
					exit($sResponse);
				}
			}
		}
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

			} else if($aCommand[0]=="@if" || $aCommand[0]=="@ifnot") {
				$sIfNot = $aCommand[0]=="@ifnot" ? "!" : "";
				$sConditions = $this->Argument($aCommand["source"], false, false);
				$sConditions = \str_replace([":true:", ":false:", ":null:"], ["true", "false", "null"], $sConditions);

				if(\is_bool($sConditions)) {
					$sConditions = $sConditions ? "true" : "false";
				} else if($sConditions===null) {
					$sConditions = "null";
				} else if(!\is_numeric($sConditions) && \strpos($sConditions, " ")===false) {
					$sConditions = \trim($sConditions, '"');
					$sConditions = '"'.$sConditions.'"';
				}

				$aSubCommands = \array_slice($aCommands, $x+1, $aCommand["to"]);
				if(eval("return ".$sIfNot."(".$sConditions.");")) { $this->RunCommands($aSubCommands); }
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
				$sReturn = \call_user_func_array([$this, "Func".$sCmd], $aCommand);
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
			if($sCmd!="-$:" && \strpos($sCmd,":")) {
				$aCommand = \explode(" ", $sSentence, 2);
				$aCmd = \explode(":", $sCmd);
				$sCmd = $aCmd[0];
				if(!empty($aCommand[1])) { \array_push($aCmd, $aCommand[1]); }
				$aCommand = $aCmd;
			}
			
			if($sCmd=="@loop") {
				$this->aLoops[] = $x;
				$aToRun[$x] = [$sCmd, "source"=>$aCommand[1], "from"=>$x+1, "to"=>$x+2];
			} else if($sCmd=="endloop") {
				$l = \array_pop($this->aLoops);
				$aToRun[$l]["to"] = $x-$aToRun[$l]["from"];
				$x--;
			} else if($sCmd=="@if" || $sCmd=="@ifnot") {
				$this->aIfs[] = $x;
				$aCommand = \explode(" ", $sSentence, 2);
				$aToRun[$x] = [$aCommand[0], "source"=>$aCommand[1], "from"=>$x+1, "to"=>$x+2];
			} else if($sCmd=="endif") {
				$l = \array_pop($this->aIfs);
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
		$aArguments = \func_get_args();
		$sVarname = $aArguments[0];
		$mValue = \count($aArguments) > 1 ? $aArguments[1] : "";
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
		if(func_num_args()>1) {
			@list($sVarname, $mIndex) = \func_get_args();
		} else {
			@list($sVarname) = \func_get_args();
			$mIndex = null;
		}
		
		if($sVarname==='-$:') { $sVarname = $this->output; }
		if(isset($this->aVars[$sVarname])) {
			$this->output = $this->aVars[$sVarname];
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
		} else {
			$this->output = null;
		}
	}

	private function FuncExit() {
		$mValue = \implode(" ", \func_get_args());
		$mValue = $this->Argument($mValue);
		if(\is_array($mValue)) { $mValue = \implode($this->sSeparator, $mValue); }
		$mValue = \preg_replace("/[\\\n]/is", "\n", $mValue);
		$mValue = \str_replace(["\\$","\\@",'\\"'],['$',"@",'"'], $mValue);
		die($mValue."\n\n");
	}

	private function FuncPrint() {
		$mValue = \implode(" ", \func_get_args());
		$mValue = $this->Argument($mValue);
		if(\is_array($mValue)) { $mValue = \implode($this->sSeparator, $mValue); }
		$mValue = \preg_replace("/[\\\n]/is", "\n", $mValue);
		$mValue = \str_replace(["\\$","\\@",'\\"'],['$',"@",'"'], $mValue);
		print($mValue."\n");
		$this->output = "";
		if(\ob_get_length()) { \ob_flush(); }
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

	private function Argument($mArgument, $bToRun=false, $bSpaceToJson=true) {
		if(\is_array($mArgument)) {
			foreach($mArgument as &$mArg) {
				$mArg = $this->Argument($mArg, false, $bSpaceToJson);
			}
			unset($mArg);
			return $mArgument;
		}

		$sArgument = \trim($mArgument);
		if($bSpaceToJson && !\preg_match("/\"(.*?)\"/", $sArgument) && \strpos($sArgument, " ")) { $sArgument = \json_encode(\explode(" ", $sArgument)); }
		$sArgument = self::call()->trimOnce($sArgument, '"');

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
						} else if($mValue==":true:") {
							return [true];
						} else if($mValue==":false:") {
							return [false];
						} else if($mValue==":null:") {
							return [null];
						} else if(\is_array($mValue)) {
							$aArgs[$mKey] = $this->Argument($mValue, $bToRun, false);
						} else if(!\is_array($mValue) && preg_match_all("/\{(?<!\\\\)(\\$|@)([a-z][0-9a-z_]*)\}/is", $mValue, $aMatchs, PREG_SET_ORDER)) {
							if($mValue==$aMatchs[0][0] && array_key_exists($aMatchs[0][2], $this->aVars)) {
								$aArgs[$mKey] = $this->aVars[$aMatchs[0][2]];
							} else if($mValue==$aMatchs[0][0] && \defined($aMatchs[0][2])) {
								$aArgs[$mKey] = \constant($aMatchs[0][2]);
							} else {
								foreach($aMatchs as $aMatch) {
									if(\array_key_exists($aMatch[2], $this->aVars)) {
										$mValue = \str_replace($aMatch[0], $this->aVars[$aMatch[2]], $mValue);
									} else if(\defined($aMatch[2])) {
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
						
						$aArgs[$mKey] = \str_replace(["\\$","\\@"],['$',"@"], $aArgs[$mKey]);
					}

					return $aArgs;
				}
			}

			if(\preg_match_all("/\{(?<!\\\\)(\\$|@)([a-z][0-9a-z_]*)\}/is", $sArgument, $aMatchs, PREG_SET_ORDER)) {
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
					} else if(\defined($aMatch[2])) {
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