<?php

namespace nogal;

/** CLASS {
	"name" : "nglFTP",
	"object": "ftp",
	"type" : "instanciable",
	"revision" : "20160201",
	"extends" : "nglBranch",
	"interfaces" : "inglBranch",
	"description" : "
		Gestor de conexiones FTP.

		Características Principales:
		- listar, crear, renombrar y eliminar archivos y directorios
		- descargar archivos y directorios completos al servidor local
		- subir archivos y directorios al servidor remoto
	",
	"configfile" : "ftp.conf",
	"arguments": {
		"filepath" : ["string", "Nombre del archivo o directorio activo en el servidor remoto", "null"],
		"force_create" : ["boolean", "Fuerza la creación de directorios en los métodos CD y MAKEDIR", "false"],
		"host" : ["string", "IP o dominio del servidor remoto", "127.0.0.1"],
		"local" : ["string", "Nombre del archivo o directorio en el servidor local", "null"],
		"ls_mode" : ["string", "
			Modo en el que se ejecutará el método LS:<br />
			<ul>
				<li><b>single:</b> array con los paths completos de los archivos y directorios listados</li>
				<li><b>signed:</b> idem anterior pero con un * antepuesto cuando el path corresponda a un directorio</li>
				<li><b>info:</b> información detallada de los archivos y directorios listados, sujeto a la disponibilidad del dato
					<ul>
						<li><b>basename:</b> nombre del archivo</li>
						<li><b>bytes:</b> tamaño en bytes</li>
						<li><b>chmod:</b> permisos</li>
						<li><b>date:</b> fecha en formato Y-m-d H:i:s</li>
						<li><b>extension:</b> extensión del archivo</li>
						<li><b>filename:</b> nombre del archivo sin extensión</li>
						<li><b>image:</b> true o false</li>
						<li><b>path:</b> path completo desde $sPath</li>
						<li><b>protocol:</b> protocolo del archivo</li>
						<li><b>size:</b> tamaño en la unidad de medida mas grande</li>
						<li><b>timestamp:</b> fecha UNIX</li>
						<li><b>type:</b> file o dir</li>
					</ul>
				</li>
			</ul>
		", "single"],
		"mask" : ["string", "Regex utilizada para filtrar el resultado del método LS", ""],
		"newname" : ["string", "Nombre de archivo o directorio para el método REN", "null"],
		"pass" : ["string", "Contraseña", ""],
		"passive_mode" : ["boolean", "Establese la conexión en modo pasivo", "true"],
		"port" : ["int", "Puerto del servidor remoto", "21"],
		"recursive" : ["boolean", "Ejecuta LS en modo recursivo", "false"],
		"transfer" : ["int", "Establese el modo de transferencia de archivos: FTP_BINARY ó FTP_ASCII", "FTP_BINARY"],
		"user" : ["string", "Nombre de usuario", "anonymous"]
	},
	"attributes": {
		"list" : ["string", "Contiene el último listodo de directorios generado por LS"],
		"log" : ["string", "Log de la comunicación con el servidor"],
		"system" : ["string", "Tipo de sistema operativo del servidor remoto"],
		"tree" : ["array", "Contiene el último árbol de directorios generado por TREE"],
		"windows" : ["string", "Indica si el sistema operativo es windows"]
	},
	"variables" : {
		"$ftp" : ["private", "FTP resource"],
		"$sSlash" : ["private", "Barra separadora de directorios"],
		"$aLastDir" : ["private", "Array de registro del anidamiento de directorios en el método nglFTP::delete"]
	}
} **/
class nglFTP extends nglBranch implements inglBranch {
	
	private $ftp;
	private $sSlash;
	private $aLastDir;

	final protected function __declareArguments__() {
		$vArguments						= [];
		$vArguments["filepath"]			= ['(string)$mValue', null];
		$vArguments["force_create"]		= ['self::call()->isTrue($mValue)', false];
		$vArguments["host"]				= ['(string)$mValue', "127.0.0.1"];
		$vArguments["local"]			= ['(string)$mValue', null];
		$vArguments["ls_mode"]			= ['(string)$mValue', "single"];
		$vArguments["mask"]				= ['(string)$mValue'];
		$vArguments["newname"]			= ['(string)$mValue', null];
		$vArguments["pass"]				= ['(string)$mValue'];
		$vArguments["passive_mode"]		= ['self::call()->isTrue($mValue)', true];
		$vArguments["port"]				= ['(int)$mValue', 21];
		$vArguments["recursive"]		= ['self::call()->isTrue($mValue)', false];
		$vArguments["transfer"]			= ['$mValue', FTP_BINARY];
		$vArguments["user"]				= ['(string)$mValue', "anonymous"];
		
		return $vArguments;
	}

	final protected function __declareAttributes__() {
		$vAttributes					= [];
		$vAttributes["system"]			= null;
		$vAttributes["windows"]			= null;
		$vAttributes["list"]			= null;
		$vAttributes["tree"]			= null;
		$vAttributes["log"]				= null;

		return $vAttributes;
	}

	final protected function __declareVariables__() {
		$this->aLastDir = [];
	}

	final public function __init__() {
	}

	/** FUNCTION {
		"name" : "cd",
		"type" : "public",
		"description" : "Cambia de directorio en el servidor remoto",
		"parameters" : { 
			"$sPath" : ["string", "", "argument::filepath"],
			"$bForce" : ["boolean", "", "argument::force_create"]
		},
		"return" : "array"
	} **/	
	public function cd() {
		list($sPath, $bForce) = $this->getarguments("filepath,force_create", \func_get_args());

		$sPath = self::call()->clearPath($sPath, false, $this->sSlash);
		$aPath = \explode($this->sSlash, $sPath);
		for($x=0; $x<\count($aPath); $x++) {
			if($aPath[$x]!="") {
				if(!@\ftp_chdir($this->ftp, $aPath[$x])) {
					if($bForce) {
						if(!$this->makedir($aPath[$x])) { return false; }
						if(!\ftp_chdir($this->ftp, $aPath[$x])) {
							$this->Logger(self::errorMessage($this->object, 1003, $aPath[$x]));
							return false;
						}
					} else {
						$this->Logger(self::errorMessage($this->object, 1003, $aPath[$x]));
						return false;
					}
				}

				$this->Logger("CHDIR ".$aPath[$x]);
			}
		}

		return $this;
	}

	/** FUNCTION {
		"name" : "connect",
		"type" : "public",
		"description" : "Establece la conexión con el servidor remoto",
		"parameters" : { 
			"$sHost" : ["string", "", "argument::host"],
			"$nPort" : ["int", "", "argument::port"]
		},
		"examples" : {
			"Upload de archivo" : "
				$ftp = $ngl->c("ftp.")->connect("dominio.com")->login("userfoo", "passbar");
				$ftp->upload("files/demo.zip", "public_html/zipfiles/demo.zip");
			"
		},
		"return" : "boolean"
	} **/
	public function connect() {
		list($sHost, $nPort) = $this->getarguments("host,port", \func_get_args());

		if(!$ftp = \ftp_connect($sHost, (int)$nPort)) {
			$this->Logger(self::errorMessage($this->object, 1001, $sHost));
		} else {
			$this->Logger("CONNECTED TO ".$sHost.":".$nPort);
			$this->ftp = $ftp;
			return $this;
		}
		
		return false;
	}

	/** FUNCTION {
		"name" : "curdir",
		"type" : "public",
		"description" : "Retorna la ruta del directorio actual",
		"return" : "string"
	} **/
	public function curdir() {
		return \ftp_pwd($this->ftp);
	}

	/** FUNCTION {
		"name" : "delete",
		"type" : "public",
		"description" : "Elimina un archivo o directorio",
		"parameters" : { 
			"$sPath" : ["string", "", "argument::filepath"]
		},
		"return" : "boolean"
	} **/	
	public function delete() {
		list($sPath) = $this->getarguments("filepath", \func_get_args());

		$bIsDir = ($this->cd($sPath)===false) ? false : true;
		if($bIsDir) {
			$aSource = $this->ls(null, null, "info", true);
			$vDelete = self::call()->treeWalk($aSource,
				function($aNode, $nLevel, $bFirst, $bLast) {
					if($aNode["type"]=="file") {
						if(!@\ftp_delete($this->ftp, $aNode["path"])) {
							$this->Logger(self::errorMessage($this->object, 1008, $aNode["path"]));
							return false;
						}
						return true;
					}
				}, null, [
					"nodeOpen" => function($aNode) {
						if($aNode["type"]=="dir") { $this->aLastDir[] = $aNode["path"]; }
						return true;
					},
					"branchClose" => function() {
						$sLastDir = array_pop($this->aLastDir);
						if($sLastDir!==null && !@ftp_rmdir($this->ftp, $sLastDir)) {
							$this->Logger(self::errorMessage($this->object, 1008, $sLastDir));
							return false;
						}
						return true;
					}
				]
			);
			
			foreach($vDelete as $bDelete) { if($bDelete===false) { return false; } }
			$this->cd("..");
			return @\ftp_rmdir($this->ftp, $sPath);
		} else {
			if(@\ftp_delete($this->ftp, $sPath)) {
				$this->Logger(self::errorMessage($this->object, 1008));
				return false;
			}
			return true;
		}
	}

	/** FUNCTION {
		"name" : "download",
		"type" : "public",
		"description" : "descarga un archivo o directorio del servidor a la maquina local",
		"parameters" : { 
			"$sPath" : ["string", "", "argument::filepath"],
			"$sLocalPath" : ["string", "", "argument::local"],
			"$nTransfer" : ["int", "", "argument::transfer"]
		},
		"examples" : {
			"Descarga de archivos y directorios" : "
				# conexión
				$ftp = $ngl->c("ftp.")->connect("dominio.com")->login("userfoo", "passbar");
				
				# listado de archivos
				$ftp->recursive = true;
				print_r($ftp->ls());

				# Salida
				Array (
					[0] => public_html/functions.js
					[1] => public_html/images
					[2] => public_html/images/logo.jpg
					[3] => public_html/images/header.jpg
					[4] => public_html/index.html
					[5] => public_html/style.css
				)
				
				# descarga
				$ftp->mkdir("css");
				$ftp->download("public_html", "c:/tmp");
				
				# listado local
				print_r($ngl("files")->ls("c:/tmp", null, null, true));

				# Salida
				Array (
					[0] => c:/tmp/public_html/functions.js
					[1] => c:/tmp/public_html/images
					[2] => c:/tmp/public_html/images/logo.jpg
					[3] => c:/tmp/public_html/images/header.jpg
					[4] => c:/tmp/public_html/index.html
					[5] => c:/tmp/public_html/style.css
				)
			"
		},	
		"return" : "boolean"
	} **/	
	public function download() {
		list($sPath, $sLocalPath, $nTransfer) = $this->getarguments("filepath,local,transfer", \func_get_args());

		$sSource = \basename($sPath);
		$sDestination = ($sLocalPath!==null) ? $sLocalPath : $sSource;

		$vList = $this->ls(null,null,"info");
		if($vList[$sSource]["type"]=="dir") {
			$aSource = $this->ls($sSource, null, "info", true);
			if(@!\chdir($sDestination)) {
				if(@!\mkdir($sDestination, 0755)) {
					$this->Logger(self::errorMessage($this->object, 1003));
				} else {
					\chdir($sDestination);
				}
			}

			$sMainDir = $vList[$sSource]["basename"];
			if(@!\chdir($sMainDir)) {
				if(@!\mkdir($sMainDir, 0755)) {
					$this->Logger(self::errorMessage($this->object, 1003));
				} else {
					\chdir($sMainDir);
				}
			}

			$vDownloads = self::call()->treeWalk($aSource, function($aNode, $nLevel, $bFirst, $bLast) use ($nTransfer) {
					return $this->DownloadTree($aNode, $nTransfer);
				}, null, array("branchClose"=>function() { \chdir(".."); return true; })
			);
			\chdir(\getcwd());
			
			foreach($vDownloads as $bDownload) {
				if($bDownload===false) { return false; }
			}
		} else {
			if(!@\ftp_get($this->ftp, $sDestination, $sSource, $nTransfer)) {
				$this->Logger(self::errorMessage($this->object, 1005));
				return false;
			}
			$this->Logger("DOWNLOAD ".$sSource." -> ".$sDestination);
		}

		return true;
	}

	/** FUNCTION {
		"name" : "DownloadTree",
		"type" : "private",
		"description" : "Método auxiliar de DOWNLOAD ejecutado por medio del método nglFn::treeWalk",
		"parameters" : { 
			"$vFile" : ["string", "Array con los datos del nombre del archivo o directorio"],
			"$nTransfer" : ["int", "Modo de transferencia de archivos, constantes FTP_BINARY ó FTP_ASCII", "FTP_BINARY"]
		},
		"return" : "null"
	} **/
	private function DownloadTree($vFile, $nTransfer=FTP_ASCII) {
		if($vFile["type"]=="dir") {
			if(@!\chdir($vFile["basename"])) {
				$this->Logger(self::errorMessage($this->object, 1003, $vFile["basename"]));
				if(@!\mkdir($vFile["basename"], 0755)) {
					$this->Logger(self::errorMessage($this->object, 1004, $vFile["basename"]));
					return false;
				} else {
					\chdir($vFile["basename"]);
				}
			}
		} else {
			$sSource = $vFile["path"];
			$sDestination = $vFile["basename"];
			if(!@\ftp_get($this->ftp, $sDestination, $sSource, $nTransfer)) {
				return false;
			}
			$this->Logger("DOWNLOAD ".$sSource." -> ".$sDestination);
		}
		
		return true;
	}

	/** FUNCTION {
		"name" : "GetChmod",
		"type" : "private",
		"description" : "Convierte una cadena de permisos RWX en un valor CHMOD",
		"parameters" : { 
			"$sCHMOD" : ["string", "Cadena de permisos RWX"]
		},
		"return" : "number"
	} **/
	private function GetChmod($sCHMOD) {
		$vTrans["-"] = "0";
		$vTrans["r"] = "4";
		$vTrans["w"] = "2";
		$vTrans["x"] = "1";
		
		$sCHMOD = \strtolower($sCHMOD);
		$sCHMOD = \substr(\strtr($sCHMOD, $vTrans), 1);
		$aCHMOD = \str_split($sCHMOD, 3);
		
		$nCHMOD = \array_sum(\str_split($aCHMOD[0])) . \array_sum(\str_split($aCHMOD[1])) . \array_sum(\str_split($aCHMOD[2]));
		return $nCHMOD;
	}

	/** FUNCTION {
		"name" : "GetTimestamp",
		"type" : "private",
		"description" : "Convierte las fechas de ftp_rawlist en un valor timestamp",
		"parameters" : { 
			"$sYear" : ["string", "Cadena que representa al año o la hora del archivo",
			"$sMonth" : ["string", "Cadena que representa al mes",
			"$sDay" : ["string", "Cadena que representa al día"
		},
		"return" : "int"
	} **/
	private function GetTimestamp($sYear, $sMonth, $sDay) {
		$nMonth = \date("n", \strtotime($sMonth));
		$nToday = \date("n");

		if(\strpos($sYear,":")===false) {
			$nTimestamp	= \strtotime($sDay." ".$sMonth." ".$sYear);
		} else {
			$sNewYear = \date("Y");
			if($nMonth > $nToday) { $sNewYear--; }
			$nTimestamp	= \strtotime($sDay." ".$sMonth." ".$sNewYear." ".$sYear);
		}

		return $nTimestamp;
	}
	
	/** FUNCTION {
		"name" : "login",
		"type" : "public",
		"description" : "Autentica la sesion en el servidor remoto",
		"parameters" : { 
			"$sUser" : ["string", "", "argument::user"],
			"$sPass" : ["string", "", "argument::pass"],
			"$bPassive" : ["boolean", "", "argument::passive"]
		},
		"output" : "system,windows",
		"return" : "$this"
	} **/
	public function login() {
		list($sUser, $sPass, $bPassive) = $this->getarguments("user,pass,passive", \func_get_args());
		$sPass = self::passwd($sPass, true);
		if(\ftp_login($this->ftp, $sUser, $sPass)) {
			$nSystem = \ftp_systype($this->ftp);
			$this->Logger("LOGIN OK");
			$this->attribute("system", $nSystem);
			$this->attribute("windows", \preg_match("/windows/i", $nSystem));
			$this->sSlash = ($this->attribute("windows")) ? "\\" : "/";
			$this->passive($bPassive);
			return $this;
		} else {
			$this->Logger(self::errorMessage($this->object, 1002));
		}
		return false;
	}

	/** FUNCTION {
		"name" : "Logger",
		"type" : "private",
		"description" : "Registra una cadena en el atributo log",
		"parameters" : { 
			"$sLog" : ["string", "Cadena que se añadira al log"]
		},
		"input" : "log",
		"output" : "log"
	} **/
	private function Logger($sLog) {
		$sHistory = $this->attribute("log");
		$sHistory .= $sLog."\r\n";
		$this->attribute("log", $sHistory);
	}

	/** FUNCTION {
		"name" : "ls",
		"type" : "public",
		"description" : "Lista el contenido de un directorio. Sino se especifica un directorio listará el directorio actual",
		"parameters" : { 
			"$sDirname" : ["string", "", "argument::filepath"],
			"$sMask" : ["string", "", "argument::mask"],
			"$sMode" : ["string", "", "argument::ls_mode"],
			"$bRecursive" : ["boolean", "", "argument::recursive"],
		},
		"input" : "system",
		"return" : "array"
	} **/
	public function ls() {
		list($sDirname, $sMask, $sMode, $bRecursive) = $this->getarguments("filepath,mask,ls_mode,recursive", \func_get_args());

		$sCurrentDir = $this->curdir();
		if($sDirname==null) {
			$sDirname = self::call()->clearPath($sCurrentDir, false, $this->sSlash);
		} else {
			if(\strpos($sDirname, $this->sSlash)===false) {
				$sDirname = self::call()->clearPath($sCurrentDir.$this->sSlash.$sDirname, false, $this->sSlash);
			}
		}
		$aList = \ftp_rawlist($this->ftp, $sDirname);
		$this->Logger("LIST (".$sMode.") ".$sDirname);

		$vTree = [];
		if($aList && \count($aList)) {
			$sMode = \strtolower($sMode);
			if($this->attribute("system")=="UNIX") {
				foreach($aList as $sFile) {
					if($sMode=="raw") { $vTree[] = $sFile; continue; }

					// $sFile = trim($sFile);
					$aFileInfo = \preg_split("/[\s]+/", $sFile, 9, PREG_SPLIT_NO_EMPTY);
					if(\is_array($aFileInfo)) {
						$sName = $aFileInfo[8];
						$sFileType = ($aFileInfo[0][0]=="d") ? "dir" : (($aFileInfo[0][0]=="l") ? "link" : "file");
						$sPath = $sDirname.$this->sSlash.$sName;

						$sLink = null;
						if($sFileType=="link") {
							unset($vTree[$sName]);
							$sLink = \substr($sName, \strrpos($sName, " -> ")+1);
							$sName = \substr($sName, 0, \strpos($sName, " -> "));
						}

						if($sMode=="single") {
							$vTree[] = $sPath;
						} else if($sMode=="signed") {
							$sSing = ($sFileType=="dir") ? "*" : "";
							$vTree[] = $sSing.$sPath;
						} else {
							$vTree[$sName]["raw"]		= $sFile;
							$vTree[$sName]["type"]		= $sFileType;
							$vTree[$sName]["path"]		= $sPath;
							$vTree[$sName]["basename"]	= $sName;
							$vTree[$sName]["link"]		= $sLink;
							
							$aBasename = \explode(".", $vTree[$sName]["basename"]);
							if(\is_array($aBasename) && \count($aBasename)>1 && $sFileType!="dir") {
								$vTree[$sName]["extension"] = \array_pop($aBasename);
								$vTree[$sName]["filename"] = \implode(".", $aBasename);
							} else {
								$vTree[$sName]["extension"] = "";
								$vTree[$sName]["filename"] = $vTree[$sName]["basename"];
							}
							
							$vTree[$sName]["bytes"]			= $aFileInfo[4];
							$vTree[$sName]["size"]			= self::call()->strSizeEncode($aFileInfo[4]);
							$vTree[$sName]["chmod"]			= $this->GetChmod($aFileInfo[0]);
							$vTree[$sName]["timestamp"]		= $this->GetTimestamp($aFileInfo[7], $aFileInfo[5], $aFileInfo[6]);
							$vTree[$sName]["date"]			= \date("Y-m-d H:i:s", $vTree[$sName]["timestamp"]);
							$vTree[$sName]["mime"] 			= ($vTree[$sName]["type"]=="file") ? self::call()->mimeType($vTree[$sName]["extension"]) : "application/x-unknown-content-type";
							$vTree[$sName]["image"] 		= (\strpos($vTree[$sName]["mime"], "image")===0);
						}

						if($bRecursive && $sFileType=="dir") {
							if(isset($vTree[$sName])) {
								$vTree[$sName]["_children"] = $this->ls($sPath, $sMask, $sMode, true);
							} else {
								$vTree = \array_merge($vTree, $this->ls($sPath, $sMask, $sMode, true));
							}
						}
					}
				}
			} else if($this->attribute("system")=="Windows_NT") {
				foreach($aList as $sFile) {
					if($sMode=="raw") { $vTree[] = $sFile; continue; }

					\preg_match("/([0-9]{2})-([0-9]{2})-([0-9]{2}) +([0-9]{2}):([0-9]{2})(AM|PM) +([0-9]+|<DIR>) +(.+)/is", $sFile, $aFileInfo);
					if(\is_array($aFileInfo)) {
						if($aFileInfo[3]<70) { $aFileInfo[3]+=2000; } else { $aFileInfo[3]+=1900; }
					
						$sName = $aFileInfo[8];
						$sFileType = $aFileInfo[7];
						$sPath = $sDirname.$this->sSlash.$sName;

						if($sMode=="single") {
							$vTree[] = $sPath;
						} else if($sMode=="signed") {
							$sSing = ($sFileType=="dir") ? "*" : "";
							$vTree[] = $sSing.$sPath;
						} else {
							$vTree[$sName]["raw"]		= $sFile;
							$vTree[$sName]["type"] 		= ($sFileType=="<DIR>") ? "dir" : "file";
							$vTree[$sName]["path"] 		= $sPath;
							$vTree[$sName]["basename"] 	= $sName;
							$vTree[$sName]["link"]		= null;
							
							$aBasename = \explode(".", $sName);
							if(\is_array($aBasename) && \count($aBasename)>1 && $sFileType!="<DIR>") {
								$vTree[$sName]["extension"] = \array_pop($aBasename);
								$vTree[$sName]["filename"] = \implode(".", $aBasename);
							} else {
								$vTree[$sName]["extension"] = "";
								$vTree[$sName]["filename"] = $vTree[$sName]["basename"];
							}
							
							$vTree[$sName]["bytes"] 	= ($sFileType=="<DIR>") ? 0 : $aFileInfo[7];
							$vTree[$sName]["size"] 		= self::call()->strSizeEncode($vTree[$sName]["bytes"]);
							$vTree[$sName]["chmod"]		= null;
							$vTree[$sName]["timestamp"]	= $this->GetTimestamp($aFileInfo[3], $aFileInfo[1], $aFileInfo[2]);
							$vTree[$sName]["date"]		= \date("Y-m-d H:i:s", $vTree[$sName]["timestamp"]);
							$vTree[$sName]["mime"] 		= ($vTree[$sName]["type"]=="file") ? self::call()->mimeType($vTree[$sName]["extension"]) : "application/x-unknown-content-type";
							$vTree[$sName]["image"] 	= (\strpos($vTree[$sName]["mime"], "image")===0);
						}

						if($bRecursive && $sFileType=="dir") {
							if(isset($vTree[$sName])) {
								$vTree[$sName]["_children"] = $this->ls($sPath, $sMask, $sMode, true);
							} else {
								$vTree = \array_merge($vTree, $this->ls($sPath, $sMask, $sMode, true));
							}
						}
					}
				}
			}
		}
		return $vTree;
	}

	/** FUNCTION {
		"name" : "mkdir",
		"type" : "public",
		"description" : "
			Crea un directorio.
			Si el directorio ya existe y el argumento force_create es TRUE, mkdir le agregará al nombre del directorio el sufijo _N donde N es el número de directorio con el mismo nombre +1
		",
		"parameters" : { 
			"$sPath" : ["string", "", "argument::filepath"],
			"$bForce" : ["boolean", "", "argument::force_create"]
		},
		"examples" : {
			"Crea un directorio" : "
				# conexión
				$ftp = $ngl->c("ftp.")->connect("dominio.com")->login("userfoo", "passbar");
				
				# listado de archivos
				$ftp->ls_mode = "signed";
				print_r($ftp->ls());

				# Salida
				Array (
					[0] => functions.js
					[1] => *images
					[2] => index.html
					[3] => style.css
				)
				
				# nuevos directorios
				$ftp->mkdir("css");
				$ftp->mkdir("images", true);
				
				# listado
				print_r($ftp->ls());

				# Salida
				Array (
					[0] => *css
					[1] => functions.js
					[2] => *images
					[3] => *images_1
					[4] => index.html
					[5] => style.css
				)
			"
		},		
		"return" : "$this"
	} **/	
	public function mkdir() {
		list($sPath, $bForce) = $this->getarguments("filepath,force_create", \func_get_args());

		if(!$bForce) {
			if(!@\ftp_mkdir($this->ftp, $sPath)) {
				$this->Logger(self::errorMessage($this->object, 1004, $sPath));
				return false;
			}
		} else {
			$sTestDir = self::call()->unique(16);
			if(!@\ftp_mkdir($this->ftp, $sTestDir)) {
				$this->Logger(self::errorMessage($this->object, 1004, $sPath));
				return false;
			} else {
				\ftp_rmdir($this->ftp, $sTestDir);
			}

			$x = 1;
			$sDirToCreateForced = $sPath;
			while(1) {
				if(@\ftp_mkdir($this->ftp, $sDirToCreateForced)) {
					$sPath = $sDirToCreateForced;
					break;
				}
				$sDirToCreateForced = $sPath."_".$x;
				$x++;
			}
		}

		$this->Logger("MKDIR ".$sPath);
		return $this;
	}

	/** FUNCTION {
		"name" : "passive",
		"type" : "public",
		"description" : "Activa/desactiva el modo pasivo. Por defecto todas las conexiones se inician en modo pasivo",
		"parameters" : { 
			"$bPassive" : ["boolean", "", "argument::passive_mode"]
		},
		"return" : "$this"
	} **/	
	public function passive() {
		list($bPassive) = $this->getarguments("passive_mode", \func_get_args());
		\ftp_pasv($this->ftp, $bPassive);
		$this->Logger("PASIVE MODE ".($bPassive ? "ON" : "OFF"));
		return $this;
	}
	
	/** FUNCTION {
		"name" : "rename",
		"type" : "public",
		"description" : "Cambia el nombre de un archivo o directorio",
		"parameters" : { 
			"$sPath" : ["string", "", "argument::filepath"],
			"$sNewName" : ["boolean", "", "argument::newname"]
		},
		"examples" : {
			"Cambiar nombre de un archivo" : "
				# conexión
				$ftp = $ngl->c("ftp.")->connect("dominio.com")->login("userfoo", "passbar");
				
				# listado de archivos
				$ftp->ls_mode = "signed";
				print_r($ftp->ls());

				# Salida
				Array (
					[0] => functions.js
					[1] => *images
					[2] => index.html
					[3] => style.css
				)
				
				# nuevos directorios
				$ftp->rename("index.html", "home.html");
				
				# listado
				print_r($ftp->ls());

				# Salida
				Array (
					[0] => functions.js
					[1] => *images
					[2] => home.html
					[3] => style.css
				)
			"
		},	
		"return" : "boolean"
	} **/	
	public function rename() {
		list($sPath, $sNewName) = $this->getarguments("filepath,newname", \func_get_args());

		$sPath = \basename($sPath);
		$sNewName = \basename($sNewName);

		if($sPath=="" || $sNewName=="") { return false; }
		if(!@\ftp_rename($this->ftp, $sPath, $sNewName)) {
			$this->Logger(self::errorMessage($this->object, 1007));
			return false;
		}
		
		$this->Logger("REN ".$sPath." => ".$sNewName);
		return $this;
	}

	/** FUNCTION {
		"name" : "upload",
		"type" : "public",
		"description" : "Sube un archivo o directorio al servidor remoto",
		"parameters" : {
			"$sLocalPath" : ["string", "", "argument::local"],
			"$sPath" : ["string", "", "argument::filepath"],
			"$nTransfer" : ["boolean", "", "argument::transfer"]
		},
		"examples" : {
			"Upload de archivo" : "
				$ftp = $ngl->c("ftp.")->connect("dominio.com")->login("userfoo", "passbar");
				$ftp->upload("files/demo.zip", "public_html/zipfiles/demo.zip");
			"
		},
		"return" : "boolean"
	} **/	
	public function upload() {
		list($sLocalPath, $sPath, $nTransfer) = $this->getarguments("local,filepath,transfer", \func_get_args());

		if(\is_dir($sLocalPath)) {
			$this->cd($sPath);
			$aSource = self::call("files")->ls($sLocalPath, null, "info", true);
			$vUploads = self::call()->treeWalk($aSource, function($aNode, $nLevel, $bFirst, $bLast) use ($nTransfer) {
					return $this->UploadTree($aNode, $nTransfer);
				}, null, ["branchClose"=>function() { $this->cd(".."); return true; }]
			);
			$this->cd("..");
			foreach($vUploads as $bUpload) {
				if($bUpload===false) { return false; }
			}
		} else {
			if(!@\ftp_put($this->ftp, $sPath, $sLocalPath, $nTransfer)) {
				$this->Logger(self::errorMessage($this->object, 1006));
				return false;
			}
			$this->Logger("UPLOAD ".$sLocalPath." -> ".$sPath);
		}

		return true;
	}

	/** FUNCTION {
		"name" : "UploadTree",
		"type" : "private",
		"description" : "método auxiliar de UPLOAD ejecutado por medio del método treeWalk",
		"parameters" : { 
			"$vFile" : ["array", "Array con los datos del nombre del archivo o directorio"],
			"$nTransfer" : ["int", "Modo de transferencia de archivos FTP_BINARY ó FTP_ASCII", "FTP_ASCII"]
		},
		"return" : "boolean"
	} **/
	private function UploadTree($vFile, $nTransfer) {
		if($vFile["type"]=="dir") {
			if(!$this->cd($vFile["basename"], true)) { return false; }
		} else {
			$sSource = $vFile["path"];
			$sDestination = $vFile["basename"];
			if(!@\ftp_put($this->ftp, $sDestination, $sSource, $nTransfer)) {
				return false;
			}
			$this->Logger("UPLOAD ".$sSource." -> ".$sDestination);
		}
		
		return true;
	}
}

?>