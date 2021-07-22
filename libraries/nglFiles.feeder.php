<?php

namespace nogal;

/** CLASS {
	"name" : "nglFiles",
	"object" : "files",
	"type" : "main",
	"revision" : "20160201",
	"extends" : "nglBranch",
	"interfaces" : "inglFeeder",
	"description" : "Métodos frecuentes para el manejo de archivos y directorios."
} **/
class nglFiles extends nglFeeder implements inglFeeder {
	
	private $sSandBox;

	final public function __init__($mArguments=null) {
		$this->sSandBox = (\defined("NGL_SANDBOX")) ?  \realpath(NGL_SANDBOX) : \realpath(NGL_PATH_PROJECT);
	}

	/** FUNCTION {
		"name" : "absPath",
		"type" : "public",
		"description" : "formatea un path como absoluto limpiando doble barras o referencias atras (..)",
		"parameters" : { 
			"$sPath" : ["string", "Path"],
			"$sSlash" : ["string", "Separador de directorios.", "NGL_DIR_SLASH"]
		},
		"examples" : {
			"Ejemplo 1" : "
				$path = "/home/user/docs/../../user2/readme.txt";
				echo $ngl("files")->absPath($path, "/");
				
				#salida
				"/home/user2/readme.txt"
			",
			
			"Ejemplo 2" : "
				$path = "/home/user/docs/../../user2/readme.txt";
				echo $ngl("files")->absPath($path, "_");
				
				#salida
				"home_user2_readme.txt"
			"
		},
		"return" : "string"
	} **/
	public function absPath($sPath, $sSlash=NGL_DIR_SLASH) {
		$sStart = ($sPath[0]==$sSlash) ? $sSlash : "";
		if(\strtoupper(\substr(PHP_OS, 0, 3))=="WIN") {
			if(\strpos($sPath, ":")) {
				list($sStart, $sPath) = \explode(":", $sPath, 2);
				$sStart .= ":".$sSlash;
			}
		}

		$sPath = \str_replace(["/", "\\"], $sSlash, $sPath);
		$aPath = \array_filter(\explode($sSlash, $sPath), "strlen");
		
		$aAbsolute = [];
		foreach($aPath as $sDir) {
			if($sDir==".") { continue; }
			if($sDir=="..") {
				\array_pop($aAbsolute);
			} else {
				$aAbsolute[] = $sDir;
			}
		}
		
		$sAbsolutePath = $sStart.\implode($sSlash, $aAbsolute);
		return $sAbsolutePath;
	}

	/** FUNCTION {
		"name" : "basePaths",
		"type" : "public",
		"description" : "Retorna la porción común de dos paths desde el inicio. Previa a la comparación limpia los paths con <b>nglFn::clearPath</b>",
		"parameters" : { 
			"$sPath1" : ["string", "path 1"],
			"$sPath2" : ["string", "path 2"],
			"$sSlash" : ["string", "Separador de directorios.", "NGL_DIR_SLASH"]
		},
		"examples" : {
			"Ejemplo" : "
				$path1 = "/home/user/docs/readme.txt";
				$path2 = "/home/user/docs/images/picture.jpg";
				echo $ngl("files")->basePaths($path1, $path2);
				
				#salidas
				"/home/user/docs/"
			"
		},
		"return" : "string"
	} **/
	public function basePaths($sPath1, $sPath2, $sSlash=NGL_DIR_SLASH) {
		$sPath1 = self::call()->clearPath($sPath1, false, $sSlash);
		$sPath2 = self::call()->clearPath($sPath2, false, $sSlash);
		return self::call()->strCommon($sPath1, $sPath2);
	}

	/** FUNCTION {
		"name" : "copyr",
		"type" : "public",
		"description" : "
			Copia archivos y directorios de manera recursiva. Retorna un Array de 2 indices:
			<ul>
				<li><b>0:</b> Cantidad de archivos copiados y directorios creados.</li>
				<li><b>1:</b> Log con el detalla de cada una de las operaciones.</li>
			</ul>
		",
		"parameters" : { 
			"$sSource" : ["string", "Origen de la copia"],
			"$sDestine" : ["string", "Destino de la copia"],
			"$sMask" : ["string", "Mascara de archivos. Una expresion regular o un array de ellas que será tratado con OR"],
			"$bRecursive" : ["boolean", "Determina si se deben incluir carpetas y sub-carpetas", "true"],
			"$bIncludeHidden" : ["boolean", "Determina si se deben copiar los archivos y carpetas ocultos", "false"],
			"$bLog" : ["boolean", "Activa el log", "false"]
		},
		"examples" : {
			"Copia completa" : "
				$path1 = "/home/user/mydocs";
				$path2 = "/home/user2/mydocs";
				print_r($ngl("files")->copyr($path1, $path2));
				
				#salidas
				Array (
					[0] => 13
					[1] =>
						copy	"/home/user/mydocs/data.pdf" => "/home/user2/mydocs/data.pdf"
						mkdir	"/home/user2/mydocs/docs"
						copy	"/home/user/mydocs/docs/bar.doc" => "/home/user2/mydocs/docs/bar.doc"
						copy	"/home/user/mydocs/docs/foo.doc" => "/home/user2/mydocs/docs/foo.doc"
						mkdir 	"/home/user2/mydocs/images"
						copy	"/home/user/mydocs/images/image1.jpg" => "/home/user2/mydocs/images/image1.jpg"
						copy	"/home/user/mydocs/images/image2.jpg" => "/home/user2/mydocs/images/image2.jpg"
						copy	"/home/user/mydocs/images/image3.jpg" => "/home/user2/mydocs/images/image3.jpg"
						copy	"/home/user/mydocs/images/picture1.jpg" => "/home/user2/mydocs/images/picture1.jpg"
						copy	"/home/user/mydocs/images/picture2.jpg" => "/home/user2/mydocs/images/picture2.jpg"
						copy	"/home/user/mydocs/images/picture3.jpg" => "/home/user2/mydocs/images/picture3.jpg"
						copy	"/home/user/mydocs/images/picture4.jpg" => "/home/user2/mydocs/images/picture4.jpg"
						copy	"/home/user/mydocs/readme.txt" => "/home/user2/mydocs/readme.txt"
				)
			", 
			
			"Copia selectiva" : "
				$path1 = "/home/user/mydocs";
				$path2 = "/home/user2/mydocs";
				$out = $ngl("files")->copyr($path1, $path2, array("*.doc", "image*"));
				print_r($out);
				
				#salidas
				Array (
					[0] => 7
					[1] =>
						mkdir	"/home/user2/mydocs/docs"
						copy	"/home/user/mydocs/docs/bar.doc" => "/home/user2/mydocs/docs/bar.doc"
						copy	"/home/user/mydocs/docs/foo.doc" => "/home/user2/mydocs/docs/foo.doc"
						mkdir 	"/home/user2/mydocs/images"
						copy	"/home/user/mydocs/images/image1.jpg" => "/home/user2/mydocs/images/image1.jpg"
						copy	"/home/user/mydocs/images/image2.jpg" => "/home/user2/mydocs/images/image2.jpg"
						copy	"/home/user/mydocs/images/image3.jpg" => "/home/user2/mydocs/images/image3.jpg"
				)
			"
		},
		"seealso" : ["nglFiles::unlinkr"],
		"return" : "array"
	} **/
	public function copyr($sSource, $sDestine, $sMask="*", $bRecursive=true, $bIncludeHidden=false, $mCase=false, $bLog=false) {
		$aLog = [];
		$nCopied = 0;
		
		$sSource = self::call()->sandboxPath($sSource);
		$sDestine = self::call()->sandboxPath($sDestine);
		if(!\is_dir($sDestine)) {
			if(@\mkdir($sDestine)) {
				@\chmod($sDestine, NGL_CHMOD_FOLDER);
				$sLog = "mkdir\t".$sDestine."\n";
			} else {
				self::errorMessage($this->object, 1003, \dirname($sDestine));
			}
		}

		$sMode = ($bIncludeHidden) ? "signed-h" : "signed";		
		$aFiles = $this->ls($sSource, $sMask, "signed", $bRecursive);
		if($sMask!=="*") {
			$aDirs = $aTmpDirs = [];
			foreach($aFiles as $sFile) {
				if(!empty($sFile) && $sFile[0]!="*") {
					$sDirname = \dirname($sFile);
					$aTmpDirs[$sDirname] = true;
				}
			}
			
			$aTmpDirs =\array_keys($aTmpDirs);
			foreach($aTmpDirs as $sDirname) {
				$sPath = "*".\strtok($sDirname, NGL_DIR_SLASH);
				while($sTok = \strtok(NGL_DIR_SLASH)) {
					$sPath .= NGL_DIR_SLASH.$sTok;
					$aDirs[] = $sPath;
				}
			}
			
			$aFiles = \array_merge($aDirs, $aFiles);
		}

		if($mCase!==false) { $mCase = \strtolower($mCase); }
		$nSource = \strlen($sSource);
		foreach($aFiles as $sFile) {
			$nDir = (!empty($sFile) && $sFile[0]=="*") ? 1 : 0;
			$sFile = \substr($sFile, $nSource+$nDir);
			
			$sSourceFile = self::call()->clearPath($sSource.NGL_DIR_SLASH.$sFile);
			$sDestineFile = self::call()->clearPath($sDestine.NGL_DIR_SLASH.$sFile);
			if($mCase=="lower") {
				$sDestineFile = \strtolower($sDestineFile);
			} else if($mCase=="upper") {
				$sDestineFile = \strtoupper($sDestineFile);
			} else if($mCase=="secure") {
				$sDestineFile = self::call()->secureName($sDestineFile, NGL_DIR_SLASH.".:");
			}
			
			if(!$nDir) {
				\copy($sSourceFile, $sDestineFile);
				@\chmod($sDestineFile, NGL_CHMOD_FILE);
				$sLog = "copy\t".$sSourceFile." => ".$sDestineFile."\n";
			} else {
				if(\is_dir($sDestineFile)) { continue; }
				if(@\mkdir($sDestineFile)) {
					@\chmod($sDestineFile, NGL_CHMOD_FOLDER);
					$sLog = "mkdir\t".$sDestineFile."\n";
				} else {
					self::errorMessage($this->object, 1003, \dirname($sDestineFile));
				}
			}
			
			$nCopied++;
			if($bLog) { $aLog[] = $sLog; }
		}
		
		$aReport = [];
		$aReport[]	= $nCopied;
		if($bLog) { $aReport[] = \implode($aLog); }
		
		return $aReport;
	}

	/** FUNCTION {
		"name" : "ls",
		"type" : "public",
		"description" : "lista el contenido de un directorio",
		"parameters" : { 
			"$sPath" : ["string", "Directorio", "."], 
			"$mMask" : ["string", "Mascara de archivos. Una expresión regular o un array de ellas que será tratado con OR. Este parámetro será tratado con preg_quote", "null"],
			"$sMode" : ["string", "
				Modos de salida de información.<br />
				<ul>
					<li><b>single:</b> array con los paths completos de los archivos y directorios listados</li>
					<li><b>signed:</b> idem anterior pero con un * antepuesto cuando el path corresponda a un directorio</li>
					<li><b>info:</b> información detallada de los archivos y directorios listados, sujeto a la disponibilidad del dato
						<ul>
							<li><b>type:</b> file o dir</li>
							<li><b>basename:</b> nombre del archivo</li>
							<li><b>extension:</b> extensión del archivo</li>
							<li><b>filename:</b> nombre del archivo sin extensión</li>
							<li><b>protocol:</b> protocolo del archivo</li>
							<li><b>path:</b> path completo desde $sPath</li>
							<li><b>bytes:</b> tamaño en bytes</li>
							<li><b>size:</b> tamaño en la unidad de medida mas grande</li>
							<li><b>chmod:</b> permisos</li>
							<li><b>timestamp:</b> fecha UNIX</li>
							<li><b>date:</b> fecha en formato Y-m-d H:i:s</li>
							<li><b>image:</b> true o false</li>
						</ul>
					</li>
					<li><b>single-h:</b> idem single pero incluirá los archivos y carpetas que comienzen con . (ocultos)</li>
					<li><b>signed-h:</b> idem signed pero incluirá los archivos y carpetas que comienzen con . (ocultos)</li>
					<li><b>info-h:</b> idem info pero incluirá los archivos y carpetas que comienzen con . (ocultos)</li>
				</ul>
			", "single"],
			"$bRecursive" : ["boolean", "Búsqueda en modo recursivo", "false"],
			"$sChildren" : ["string", "Nombre del nodo que se utilizará para anidar resultados cuando $bRecursive=true", "_children"],
			"$bIni" : ["boolean", "Reservada para uso interno de la función", "true"]
		},
		"examples" : {
			"Modo SINGLE (no recursivo)" : "
				$ls = $ngl("files")->ls("public_html");
				print_r($ls);
				
				# salida
				Array (
					[0] => public_html/files
					[1] => public_html/functions.js
					[2] => public_html/gallery.html
					[3] => public_html/images
					[4] => public_html/index.html
					[5] => public_html/robots.txt
					[6] => public_html/styles.css
				)
			",
			"Modo SINGLE (recursivo)" : "
				$ls = $ngl("files")->ls("public_html", "*", "single", true);
				print_r($ls);
				
				# salida
				Array (
					[0] => public_html/files
					[1] => public_html/files/document.docx
					[2] => public_html/files/info.docx
					[3] => public_html/functions.js
					[4] => public_html/gallery.html
					[5] => public_html/images
					[6] => public_html/images/image1.gif
					[7] => public_html/images/image2.gif
					[8] => public_html/images/image3.gif
					[9] => public_html/index.html
					[10] => public_html/robots.txt
					[11] => public_html/styles.css
				)
			",
			"Modo SIGNED (no recursivo)" : "
				$ls = $ngl("files")->ls("public_html", "*", "signed");
				print_r($ls);
				
				# salida
				Array (
					[0] => *public_html/files
					[1] => public_html/functions.js
					[2] => public_html/gallery.html
					[3] => *public_html/images
					[4] => public_html/index.html
					[5] => public_html/robots.txt
					[6] => public_html/styles.css
				)
			",
			"Modo INFO (no recursivo)" : "
				$ls = $ngl("files")->ls("public_html/files", "*.docx", "info");
				print_r($ls);
				
				# salida
				Array (
					[document.docx] => Array (
						[type] => file
						[basename] => document.docx
						[extension] => docx
						[filename] => document
						[protocol] => filesystem
						[path] => public_html/files/document.docx
						[bytes] => 364495
						[size] => 355.95KB
						[chmod] => 0666
						[timestamp] => 1361469382
						[date] => 2013-02-21 14:56:22
						[mime] => application/vnd.openxmlformats-officedocument.wordprocessingml.document
						[image] => 
					)

					[info.docx] => Array (
						[type] => file
						[basename] => info.docx
						[extension] => docx
						[filename] => info
						[protocol] => filesystem
						[path] => public_html/files/info.docx
						[bytes] => 87310
						[size] => 85.26KB
						[chmod] => 0666
						[timestamp] => 1425914852
						[date] => 2015-03-09 12:27:32
						[mime] => application/vnd.openxmlformats-officedocument.wordprocessingml.document
						[image] => 
					)
				)
			"
		},
		"return" : "array"
	} **/
	public function ls($sPath=".", $mMask=null, $sMode="single", $bRecursive=false, $sChildren="_children", $bIni=true) {
		if(\strpos($sMode, "-")) {
			$sMode = \strstr($sMode, "-", true);
			$bHiddenFiles = true;
		}
		$sMode = \strtolower($sMode);
	
		if($bIni) {
			$sPath = \str_replace("*", "", $sPath);
			$sPath = self::call()->clearPath($sPath);
		}

		if($mMask) {
			if(\is_array($mMask)) {
				$aMatch = [];
				foreach($mMask as $sMask) {
					$aMatch[] = \preg_quote($sMask);
				}
				$sMask = "/(".\implode("|", $aMatch).")/i";
			} else {
				$sMask = "/".\preg_quote($mMask)."/i";
			}
			
			$sMask = \str_replace("\*", ".*", $sMask);
		} else {
			$sMask = $mMask;
		}

		$sPath .= NGL_DIR_SLASH.((isset($bHiddenFiles)) ? "{,.}[!.,!..]*" : "*");
		$sPath = self::call()->sandboxPath($sPath);
		$aPath = \glob($sPath, GLOB_BRACE);

		$aDirs  = [];
		$file = self::call("file");
		foreach($aPath as $sFile) {
			$bDir = is_dir($sFile);
			if($sMode=="info") {
				$file->load($sFile);
				if(!$bDir) {
					if(!$sMask || ($sMask && \preg_match($sMask, $sFile))) {
						$vTree = $file->fileinfo();
						$aDirs[$vTree["basename"]] = $vTree;
					}
				} else {
					$vTree = $file->fileinfo();
					if($bRecursive) {
						$aDirs[$vTree["basename"]] = $vTree;
						$aDirs[$vTree["basename"]][$sChildren] = $this->ls($sFile, $mMask, $sMode, $bRecursive, $sChildren, false);
					} else if(!$sMask || ($sMask && \preg_match($sMask, $sFile))) {
						$aDirs[$vTree["basename"]] = $vTree;
					}
					
				}
			} else {
				if(!$sMask || ($sMask && \preg_match($sMask, $sFile))) {
					$sFilename = ($sMode=="signed" && $bDir) ? "*".$sFile : $sFile; 
					$aDirs = \array_merge($aDirs, [$sFilename]);
				}

				if($bDir && $bRecursive) {
					$aDirs = \array_merge($aDirs, $this->ls($sFile, $mMask, $sMode, $bRecursive, $sChildren, false));
				}
			}
		}

		return $aDirs;
	}
	
	/** FUNCTION {
		"name" : "lsprint",
		"type" : "public",
		"description" : "imprime el árbol de un directorio de manera recursiva",
		"parameters" : { 
			"$sPath" : ["string", "Directorio", "."], 
			"$mMask" : ["string", "Mascara de archivos. Una expresión regular o un array de ellas que será tratado con OR", "null"],
			"$sChildren" : ["string", "Nombre del nodo que se utilizará para anidar resultados cuando $bRecursive=true", "_children"]
		},
		"examples" : {
			"Ejemplo" : "
				echo $ngl("files")->lsprint("bootstrap");
			
				# salida
				bootstrap
				├── css/
				│   ├── bootstrap.css
				│   ├── bootstrap.css.map
				│   ├── bootstrap.min.css
				│   ├── bootstrap-theme.css
				│   ├── bootstrap-theme.css.map
				│   └── bootstrap-theme.min.css
				├── js/
				│   ├── bootstrap.js
				│   └── bootstrap.min.js
				└── fonts/
					├── glyphicons-halflings-regular.eot
					├── glyphicons-halflings-regular.svg
					├── glyphicons-halflings-regular.ttf
					├── glyphicons-halflings-regular.woff
					└── glyphicons-halflings-regular.woff2
			",
		},
		"return" : "array"
	} **/
	public function lsprint($sPath=".", $mMask=null, $sChildren="_children") {
		$aLs = $this->ls($sPath, $mMask, "info", true, $sChildren);
		$aList = self::call()->treeWalk($aLs, function($aFile, $nLevel, $bFirst, $bLast) {
				$sOutput  = "";
				$sOutput .= ($nLevel) ? \str_repeat("│   ", $nLevel) : "";
				$sOutput .= ($bLast) ? "└─── " : "├─── ";
				$sOutput .= (($aFile["type"]=="dir") ? $aFile["basename"]."/" : $aFile["basename"]);
				$sOutput .= "\n";
				return $sOutput;
			}
		);

		return implode($aList);
	}

	/** FUNCTION {
		"name" : "maxUploadSize",
		"type" : "public",
		"description" : "Retorna el máximo tamaño de archivo soportado por al configuración del servidor",
		"return" : "int"
	} **/
	public function maxUploadSize() {
		$nUpload 	= \ini_get("upload_max_filesize");
		$nUpload 	= self::call()->strSizeDecode($nUpload);
		$nPost 		= \ini_get("post_max_size");
		$nPost 		= self::call()->strSizeDecode($nPost);
		$nMemory 	= \ini_get("memory_limit");
		$nMemory 	= self::call()->strSizeDecode($nMemory);
		return \min($nUpload, $nPost, $nMemory);
	}

	/** FUNCTION {
		"name" : "mkdirr",
		"type" : "public",
		"description" : "Crea un directorio. Si el directorio ya existe y $bForce es TRUE, mkdir le agregará al nombre del directorio el sufijo _N donde N es el número de directorio con el mismo nombre +1 ",
		"parameters" : { 
			"$sPath" : ["string", "Directorio", "."], 
			"$bForce" : ["boolean", "Fuerza la creación del directorio", "false"]
		},
		"return" : "boolean"
	} **/
	public function mkdirr($sPath, $bForce=false) {
		$sPath = self::call()->sandboxPath($sPath);
		if(!$bForce) {
			if(!\is_dir($sPath)) {
				if(!@\mkdir($sPath, 0777, true)) {
					return self::errorMessage($this->object, 1001, $sPath);
				}
				@\chmod($sPath, NGL_CHMOD_FOLDER);
			}
		} else {
			if(!@\mkdir($sPath)) {
				$aPath = \explode(NGL_DIR_SLASH, $sPath);
				$sDirname = \array_pop($aPath);
				$sDirPath = \implode(NGL_DIR_SLASH, $aPath);

				$sTestDir = self::call()->unique(16);
				if(!@\mkdir($sDirPath.NGL_DIR_SLASH.$sTestDir)) {
					return self::errorMessage($this->object, 1001, $sPath);
				} else {
					\rmdir($sDirPath.NGL_DIR_SLASH.$sTestDir);
				}

				$x = 1;
				while(1) {
					$sDirToCreateForced = $sDirname."_".$x;
					if(@\mkdir($sDirPath.NGL_DIR_SLASH.$sDirToCreateForced)) {
						@\chmod($sDirPath.NGL_DIR_SLASH.$sDirToCreateForced, NGL_CHMOD_FOLDER);
						break;
					}
					$x++;
				}
			}
			@\chmod($sPath, NGL_CHMOD_FOLDER);
		}
		
		return true;
	}
	
	/** FUNCTION {
		"name" : "RebuildFILES",
		"type" : "private",
		"description" : "Toma el array $aFiles (de estructura igual a $_FILES) y lo reordena de manera recursiva, para optener una lectura mas natural",
		"parameters" : { 
			"$aFiles" : ["array", "Array de archivos"], 
			"$bTop" : ["boolean", "Indica cual es la primer iteración del método", "true"],
		},
		"return" : "array"
	} **/
	private function RebuildFILES($aFiles, $bTop=true) {
		$vFiles = [];
		foreach($aFiles as $sName => $aFile){
			$sSubName = ($bTop) ? $aFile["name"] : $sName;
			if(\is_array($sSubName)){
				foreach(\array_keys($sSubName) as $nKey){
					$vFiles[$sName][$nKey] = [
						"name"     => $aFile["name"][$nKey],
						"type"     => $aFile["type"][$nKey],
						"tmp_name" => $aFile["tmp_name"][$nKey],
						"error"    => $aFile["error"][$nKey],
						"size"     => $aFile["size"][$nKey],
					];
					$vFiles[$sName] = $this->RebuildFILES($vFiles[$sName], false);
				}
			} else {
				if($bTop) {
					$vFiles[$sName] = [$aFile];
				} else {
					$vFiles[$sName] = $aFile;
				}
			}
		}

		return $vFiles;
	}
	
	/** FUNCTION {
		"name" : "unlinkr",
		"type" : "public",
		"description" : "
			Elimina archivos y directorios de manera recursiva. Retorna un Array de 2 indices:
			<ul>
				<li><b>0:</b> Cantidad de archivos y directorios eliminados</li>
				<li><b>1:</b> Log con el detalla de cada una de las operaciones</li>
			</ul>
		",
		"parameters" : { 
			"$sSource" : ["string", "Target de borrado. Si el target es un directorio y la cadena NO termina con un slash, el mismo tambien será eliminado luego de vaciarse."], 
			"$sMask" : ["string", "Mascara de archivos. Una expresion regular o un array de ellas que será tratado con OR", "*"],
			"$bRecursive" : ["boolean", "Determina si se deben incluir carpetas y sub-carpetas", "true"],
			"$bIncludeHidden" : ["boolean", "Determina si se deben eliminar los archivos y carpetas ocultos", "false"],
			"$bLog" : ["boolean", "Activa el log", "true"]
		},
		"examples" : {
			"Borrado completo" : "
				$del = $ngl("files")->unlinkr("/home/user2/mydocs");
				print_r($del);

				#salidas
				Array (
					[0] => 9
					[1] =>
						delete	"/home/user2/mydocs/docs/bar.doc"
						delete	"/home/user2/mydocs/docs/foo.doc"
						delete 	"/home/user2/mydocs/docs"
						delete	"/home/user2/mydocs/images/image1.jpg"
						delete	"/home/user2/mydocs/images/image2.jpg"
						delete	"/home/user2/mydocs/images/image3.jpg"
						delete	"/home/user2/mydocs/images/image3.jpg"
						delete 	"/home/user2/mydocs/images"
						delete 	"/home/user2/mydocs"
				)
			",
			"Borradon sólo el contenido" : "
				$del = $ngl("files")->unlinkr("/home/user2/mydocs/");
				print_r($del);

				#salidas
				Array (
					[0] => 9
					[1] =>
						delete	"/home/user2/mydocs/docs/bar.doc"
						delete	"/home/user2/mydocs/docs/foo.doc"
						delete 	"/home/user2/mydocs/docs"
						delete	"/home/user2/mydocs/images/image1.jpg"
						delete	"/home/user2/mydocs/images/image2.jpg"
						delete	"/home/user2/mydocs/images/image3.jpg"
						delete	"/home/user2/mydocs/images/image3.jpg"
						delete 	"/home/user2/mydocs/images"
				)
			"
		},
		"seealso" : ["nglFiles::copyr"],
		"return" : "array"
	} **/
	public function unlinkr($sSource, $sMask="*", $bRecursive=true, $bIncludeHidden=false, $bLog=false) {
		$sMode = ($bIncludeHidden) ? "signed-h" : "signed";
		$aFiles = $this->ls($sSource, $sMask, $sMode, $bRecursive);
		$aFiles = \array_reverse($aFiles);

		$sEnd = \substr($sSource, -1, 1);
		if($sMask=="*" && $sEnd!="/" && $sEnd!="\\") { $aFiles[] = "*".$sSource; }
		
		$aLog = [];
		$nDeleted = 0;

		foreach($aFiles as $sFile) {
			$nDir = (!empty($sFile) && $sFile[0]=="*") ? 1 : 0;
			$sFile = \substr($sFile, $nDir);
		
			if(\file_exists($sFile)) {
				if(!$nDir) {
					@\unlink($sFile);
				} else {
					@\rmdir($sFile);
				}

				$nDeleted++;
				if($bLog) { $aLog[] = "delete \t".$sFile."\n"; }
			} else {
				if($bLog) { $aLog[] = "error \t".$sFile."\n"; }
			}
		}
		
		$aReport = [];
		$aReport[]	= $nDeleted;
		if($bLog) { $aReport["log"] = \implode($aLog); }
		
		return $aReport;
	}

	/** FUNCTION {
		"name" : "upload",
		"type" : "public",
		"description" : "
			Aplica move_uploaded_file a los multiples archivos encontrados en $_FILES y retorna un reporte
		",
		"parameters" : { 
			"$mDestine" : ["mixed", "Ruta de destino o array asociativo de las mismas, donde cada índice será el mismo que en el array $_FILES"], 
			"$bOriginalName" : ["boolean", "copia el archivo con su nombre original"], 
			"$aExtensions" : ["array", "de extensiones soportadas"], 
			"$nLimit" : ["int", "Tamaño máximo soportado para los archivos. Si no se especifica se aplicará el valor de <b>nglFiles::maxUploadSize</b>", "null"],
		},
		"return" : "array"
	} **/
	public function upload($mDestine, $bOriginalName=false, $aExtensions=null, $nLimit=null) {
		$vUploads = ["errors"=>0, "report"=>[], "files"=>[]];

		if(\count($_FILES)) {
			if($nLimit===null) { $nLimit = $this->maxUploadSize(); }

			$_FILES = $this->RebuildFILES($_FILES);
			foreach($_FILES as $mIndex => $vFiles) {
				foreach($vFiles as $nIndex => $vFile) {
					$sIndex = $mIndex."_".$nIndex;
					switch($vFile["error"]) {
						case UPLOAD_ERR_OK:
							break;

						case UPLOAD_ERR_INI_SIZE:
							$vUploads["errors"]++;
							$vUploads["report"][$sIndex] = self::errorMessage($this->object, 1010);
							break;

						case UPLOAD_ERR_FORM_SIZE:
							$vUploads["errors"]++;
							$vUploads["report"][$sIndex] = self::errorMessage($this->object, 1011);
							break;

						case UPLOAD_ERR_PARTIAL:
							$vUploads["errors"]++;
							$vUploads["report"][$sIndex] = self::errorMessage($this->object, 1012);

						case UPLOAD_ERR_NO_FILE:
							$vUploads["errors"]++;
							$vUploads["report"][$sIndex] = self::errorMessage($this->object, 1013); 
							break;

						case UPLOAD_ERR_NO_TMP_DIR:
							$vUploads["errors"]++;
							$vUploads["report"][$sIndex] = self::errorMessage($this->object, 1014); 
							break;

						case UPLOAD_ERR_CANT_WRITE:
							$vUploads["errors"]++;
							$vUploads["report"][$sIndex] = self::errorMessage($this->object, 1015); 
							break;

						default:
							$vUploads["errors"]++;
							$vUploads["report"][$sIndex] = self::errorMessage($this->object, 1016);
					}

					if($vFile["size"] > $nLimit) {
						$vUploads["report"][$sIndex] = self::errorMessage($this->object, 1017);
					}

					$vInfo = \pathinfo($vFile["name"]);
					$vInfo = \pathinfo($vFile["name"]);
					if($aExtensions!==null && !\in_array($vInfo["extension"], $aExtensions)) {
						$vUploads["report"][$sIndex] = self::errorMessage($this->object, 1019);
						continue;
					}

					$sFilename = ($bOriginalName) ? $vFile["name"] : self::call()->unique(32).".".$vInfo["extension"];
					$sDestine = (\is_array($mDestine)) ? $mDestine[$mIndex] : $mDestine;
					$sDestineFilePath = self::call()->sandboxPath($sDestine.NGL_DIR_SLASH.$sFilename);
					$bIsImage = self::call()->isImage($vFile["tmp_name"]);
					if(\move_uploaded_file($vFile["tmp_name"], $sDestineFilePath)) {
						@\chmod($sDestineFilePath, NGL_CHMOD_FILE);
						unset($vFile["error"], $vFile["tmp_name"]);
						@\chmod($sDestineFilePath, NGL_CHMOD_FILE);
						$vFile["path"]		= $sDestineFilePath;
						$vFile["filename"]	= $vFile["name"];
						$vFile["realname"]	= $sFilename;
						$vFile["extension"]	= $vInfo["extension"];
						$vFile["mimetype"]	= $vFile["type"];
						$vFile["image"]		= $bIsImage;
						$vFile["field"]		= $mIndex;
						
						unset($vFile["error"], $vFile["tmp_name"], $vFile["name"], $vFile["type"]);
						
						$vUploads["files"][$sIndex] 	= $vFile;
						$vUploads["report"][$sIndex]	= "OK";
					} else {
						$vUploads["errors"]++;
						$vUploads["report"][$sIndex] = self::errorMessage($this->object, 1018, $vFile["tmp_name"]." => ".$sDestineFilePath);
					}
				}
			}
		}
		
		return $vUploads;
	}
}

?>