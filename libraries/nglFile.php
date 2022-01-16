<?php
/*
# Nogal
*the most simple PHP Framework* by hytcom.net
GitHub @hytcom
___
  
# mysql
## nglFile *extends* nglBranch *implements* inglBranch [2018-09-08]
Crea un objeto sobre archivos y/o directorio

https://github.com/hytcom/wiki/blob/master/nogal/docs/file.md

*/
namespace nogal;

class nglFile extends nglBranch implements inglBranch {

	private $hugeReader;
	private $hugeWriter;
	private $sHugeWriterMode;

	final protected function __declareArguments__() {
		$vArguments							= [];
		$vArguments["content"]				= ['$mValue'];
		$vArguments["curl_options"]			= ['$mValue'];
		$vArguments["downname"]				= ['(string)$mValue', null];
		$vArguments["filepath"]				= ['(string)$mValue'];
		$vArguments["saveaspath"]			= ['(string)$mValue', null];
		$vArguments["hugefile"]				= ['self::call()->istrue($mValue)', false];
		$vArguments["length"]				= ['(int)$mValue', 0];
		$vArguments["mimetype"]				= ['(string)$mValue', null];
		$vArguments["extend_info"]			= ['self::call()->istrue($mValue)', false];
		$vArguments["outtype"]				= ['(string)$mValue', null];
		$vArguments["reload"]				= ['self::call()->istrue($mValue)', true];
		return $vArguments;
	}

	final protected function __declareAttributes__() {
		$vAttributes = [];
		$vAttributes["basename"]			= null;
		$vAttributes["bytes"]				= null;
		$vAttributes["chmod"]				= null;
		$vAttributes["date"]				= null;
		$vAttributes["extension"]			= null;
		$vAttributes["filename"]			= null;
		$vAttributes["image"]				= null;
		$vAttributes["info"]				= null;
		$vAttributes["mime"]				= null;
		$vAttributes["path"]				= null;
		$vAttributes["protocol"]			= null;
		$vAttributes["query"]				= null;
		$vAttributes["size"]				= null;
		$vAttributes["timestamp"]			= null;
		$vAttributes["type"]				= null;
		return $vAttributes;
	}

	final protected function __declareVariables__() {
	}

	final public function __init__() {
		$this->hugeReader = null;
		$this->hugeWriter = null;
		$this->sHugeWriterMode = null;
	}

	public function append() {
		list($sContent,$bReload) = $this->getarguments("content,reload", \func_get_args());
		return $this->WriteContent($sContent, $bReload, "ab");
	}

	public function prepend() {
		list($sContent,$bReload) = $this->getarguments("content,reload", \func_get_args());
		return $this->WriteContent($sContent, $bReload, "r+b");
	}

	public function download() {
		list($sDownName,$sMimeType) = $this->getarguments("downname,mimetype", \func_get_args());
		$sFilePath = $this->attribute("path");
		
		if($sFilePath===null) {
			self::errorMessage($this->object, 1002);
			return false;
		}

		if(empty($sDownName)) { $sDownName = $this->attribute("basename"); }
		if(empty($sMimeType)) { $sMimeType = $this->attribute("mime"); }

		$aLastError = self::errorGetLast();
		if(\is_array($aLastError) && \count($aLastError)) { exit(); }

		// impresion del contenido
		\header("Content-Description: File Transfer");
		\header("Content-Type: ".$sMimeType);
		\header("Content-Transfer-Encoding: binary");
		\header("Expires: 0");
		\header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		\header("Pragma: public");
		\header("Content-Disposition: attachment; filename=\"".$sDownName."\"");
		\header("Content-Length: ".$this->attribute("bytes"));

		die($this->read());
	}

	public function fileinfo() {
		return $this->attribute("info");
	}

	public function fill() {
		list($sContent,$nLength) = $this->getarguments("content,length", \func_get_args());
	
		$sFilePath = $this->attribute("path");
		if($sFilePath===null) {
			self::errorMessage($this->object, 1002);
			return false;
		}
		if($nLength<1 || $nLength > PHP_INT_MAX) { $nLength = 0; }
		
		if($sContent===null) { $sContent = "\x00"; }
		$nContent = \strlen($sContent);

		if($hFw = @\fopen($sFilePath, "wb", $nLength)) {
			\fwrite($hFw, $sContent, $nLength);
			if($nLength>$nContent) {
				\fclose($hFw);
				$hFw = \fopen($sFilePath, "ab", $nLength);
				$nLength -= $nContent;
				while($nLength>0) {
					\fwrite($hFw, $sContent, $nLength);
					$nLength -= $nContent;
				}
			}
			\fclose($hFw);
			@\chmod($sFilePath, NGL_CHMOD_FILE);

			$this->load($sFilePath);
			return $this;
		}

		return false;
	}

	public function load() {
		list($sFilePath) = $this->getarguments("filepath", \func_get_args());
		if($sFilePath===true) { $sFilePath = \sys_get_temp_dir().NGL_DIR_SLASH.self::call()->unique(); }
		$sFilePath = self::call()->clearPath($sFilePath);
		$vFile = [];

		$bURL = false;
		$sProtocol = "filesystem";
		if($sScheme = self::call()->isURL($sFilePath, true)) {
			$bURL = true;
			$sProtocol = $sScheme;
		}
		
		if($bURL) {
			$aFilePath = \explode("?", $sFilePath, 2);
			$sFilePath = $aFilePath[0];

			$bIsDir = (\substr($sFilePath, -1)=="/");
			$vFile["type"] 		= $bIsDir ? "dir" : "file";
			$vFile["basename"]	= \basename($sFilePath);
			
			$aBasename = $bIsDir ? [] : \explode(".", $vFile["basename"]);
			if(\count($aBasename)>1) {
				$vFile["extension"] = \array_pop($aBasename);
				$vFile["filename"] = \implode(".", $aBasename);
			} else {
				$vFile["extension"] = "";
				$vFile["filename"] = $vFile["basename"];
			}
			
			$vFile["protocol"]	= $sProtocol ;
			$vFile["path"] 		= $sFilePath;
			if($this->extend_info) {
				$vFile["bytes"]		= null;
				$vFile["size"]		= null;
				$vFile["chmod"]		= null;
				$vFile["timestamp"]	= null;
				$vFile["date"] 		= null;
				$vFile["query"] 	= (isset($aFilePath[1])) ? $aFilePath[1] : "";
			}
		} else {
			$sFilePath = self::call("files")->absPath($sFilePath);
			$sFilePath = self::call()->sandboxPath($sFilePath);

			if(\file_exists($sFilePath)) {
				$bIsDir = (\is_dir($sFilePath));
				$vFile["type"] 		= $bIsDir ? "dir" : "file";
				$vFile["basename"]	= \basename($sFilePath);
				
				$aBasename = \explode(".", $vFile["basename"]);
				if(\count($aBasename)>1) {
					$vFile["extension"] = \array_pop($aBasename);
					$vFile["filename"] = \implode(".", $aBasename);
				} else {
					$vFile["extension"] = "";
					$vFile["filename"] = $vFile["basename"];
				}
				
				$vFile["protocol"]	= $sProtocol ;
				$vFile["path"] 		= $sFilePath;
				if($this->extend_info) {
					$vFile["bytes"]		= \filesize($sFilePath);
					$vFile["size"]		= $bIsDir ? 0 : self::call()->strSizeEncode($vFile["bytes"]);
					$vFile["chmod"]		= \substr(\sprintf("%o", \fileperms($sFilePath)), -4);
					$vFile["timestamp"]	= \filemtime($sFilePath);
					$vFile["date"] 		= \date("Y-m-d H:i:s", $vFile["timestamp"]);
					$vFile["query"] 	= null;
				}
			} else {
				$vFile["basename"]	= \basename($sFilePath);
				$aBasename = \explode(".", $vFile["basename"]);
				if(\count($aBasename)>1) {
					$vFile["extension"] = \array_pop($aBasename);
					$vFile["filename"] = \implode(".", $aBasename);
					$vFile["type"] = "file";
				} else {
					$vFile["extension"] = "";
					$vFile["filename"] = $vFile["basename"];
					$vFile["type"] = "dir";
				}
				
				$vFile["protocol"]	= $sProtocol ;
				$vFile["path"] 		= $sFilePath;
				if($this->extend_info) {
					$vFile["bytes"]		= null;
					$vFile["size"]		= null;
					$vFile["chmod"]		= null;
					$vFile["timestamp"]	= null;
					$vFile["date"] 		= null;
					$vFile["query"] 	= null;
				}
			}
		}

		if($this->extend_info) {
			$vFile["mime"] = ($vFile["type"]=="file") ? self::call()->mimeType($vFile["extension"]) : "application/x-unknown-content-type";
			$sFilePath = ($vFile["protocol"]=="url") ? "http:".$sFilePath : $sFilePath;
			$vFile["image"] = (\file_exists($sFilePath) && \function_exists("exif_imagetype")) ? (@\exif_imagetype($sFilePath)>0) : false;
		}

		// set de atributos
		$this->attribute("info", $vFile);
		foreach($vFile as $sAttribute => $mValue) {
			$this->attribute($sAttribute, $mValue);
		}

		\clearstatcache();
		return $this;
	}

	public function read() {
		if(\is_resource($this->hugeReader)) {
			if(!\feof($this->hugeReader)) {
				$sBuffer = \fgets($this->hugeReader);
			} else {
				\fclose($this->hugeReader);
				$this->hugeReader = null;
				return false;
			}
		} else {
			list($nLength,$aCurlOptions) = $this->getarguments("length,curl_options", \func_get_args());

			$sFilePath = $this->attribute("path");
			if($this->attribute("protocol")=="url") { $sFilePath = "http:".$sFilePath; }

			if($sFilePath===null) {
				self::errorMessage($this->object, 1002);
				return false;
			}
			if($nLength<1 || $nLength > PHP_INT_MAX) { $nLength = PHP_INT_MAX; }

			$bURL = self::call()->isURL($sFilePath);

			// extensión
			$sExtension = ($this->attribute("extension")) ? $this->attribute("extension") : "";
			
			// lectura del archivo
			$nBuffer = 0;
			$sBuffer = "";
			if((!$bURL || !(bool)\ini_get("allow_url_fopen")) && $handler = @\fopen($sFilePath, "rb")) {
				if(!$this->hugefile) {
					while(!\feof($handler)) {
						$nBuffer += 4096;
						$sBuffer .= \fread($handler, 4096);
						if($nBuffer > $nLength) {
							\fclose($handler);
							return \substr($sBuffer, 0, $nLength);
						}
					}
					\fclose($handler);
				} else {
					$this->hugeReader = $handler;
					$sBuffer = \fgets($this->hugeReader);
				}
			} else if($bURL && \function_exists("curl_init")) {
				if($this->attribute("query")) { $sFilePath .= "?".$this->attribute("query"); }

				$curl = \curl_init($sFilePath);
				\curl_setopt($curl, CURLOPT_HEADER, 0);
				if(\is_array($aCurlOptions) && \count($aCurlOptions)) {
					foreach($aCurlOptions as $sOption => $mValue) {
						$mCurlOption = \constant($sOption);
						if($mCurlOption!==null) {
							\curl_setopt($curl, $mCurlOption, $mValue);
						}
					}
				}
				\curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
				\curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

				$sBuffer = \curl_exec($curl); 
				if(\curl_errno($curl)) { $sBuffer = "CURL ERROR: ".\curl_error($curl); }
				\curl_close($curl);

				if($nLength && \strlen($sBuffer) > $nLength) {
					return \substr($sBuffer, 0, $nLength);
				}
			} else {
				self::errorMessage($this->object, 1001, $sFilePath);
				return false;
			}
		}

		return $sBuffer;
	}

	public function buffer() {
		$sBuffer = $this->read(...\func_get_args());
		$this->args("content", $sBuffer);
		return $this;
	}

	public function reload() {
		$sFilePath = $this->attribute("path");
		if($sFilePath===null) {
			self::errorMessage($this->object, 1002);
			return false;
		}	
		$this->load($sFilePath);
		return $this;
	}

	public function view() {
		list($sMimeType) = $this->getarguments("outtype", \func_get_args());
		$sFilePath = $this->attribute("path");

		if($sFilePath===null) {
			self::errorMessage($this->object, 1002);
			return false;
		}
		$sMimeType = ($sMimeType===null) ? $this->attribute("mime") : self::call()->mimeType($sMimeType);

		\header("Content-Type: ".$sMimeType);
		die($this->read());
	}
	
	/** FUNCTION {
		"name" : "write", 
		"type" : "public",
		"description" : "Escribe/reemplaza el contenido del archivo con <b>content</b>. Si el archivo no existe lo crea",
		"parameters" : {
			"$sContent" : ["string", "", "argument::content"] 
		},
		"input" : "path",
		"seealso" : ["nglFile::append","nglFile::read"],
		"examples" : {
			"Escritura" : "
				$ngl("file.foo")->load("readme.txt");
				$ngl("file.foo")->write("hola mundo!");
			"
		},
		"return": "$this"
	} **/
	public function write() {
		list($sContent,$bReload) = $this->getarguments("content,reload", \func_get_args());
		return $this->WriteContent($sContent, $bReload, "wb");
	}

	public function saveas() {
		list($sFilePath) = $this->getarguments("saveaspath", \func_get_args());
		$sFilePath = self::call("files")->absPath($sFilePath);
		$sFilePath = self::call()->sandboxPath($sFilePath);
		$this->attribute("path", $sFilePath);
		$this->attribute("protocol", "filesystem");
		return $this;
	}

	public function close() {
		if(\is_resource($this->hugeReader)) { \fclose($this->hugeReader); }
		if(\is_resource($this->hugeWriter)) { \fclose($this->hugeWriter); }
		$this->hugeReader = null;
		$this->hugeWriter = null;
		return $this;
	}

	/** FUNCTION {
		"name" : "WriteContent", 
		"type" : "protected",
		"description" : "Escribe contenido en un archivo. Este método es utilizado por <b>append</b> y <b>write</b>",
		"parameters" : {
			"$sFilePath" : ["string", "Path del archivo de destino"],
			"$sContent" : ["string", "Contenido a escribir"],
			"$bReload" : ["boolean", "
				Determina si se aplicará el método nglFile::reload sobre el archivo para actualizar la información.
				Se recomienda usar <b>false</b> cuando se realicen sucesivos nglFile::append
			", "true"],
			"$sMode" : ["string", "Modo de escritura (según fopen)", "wb"]
		},
		"output" : "bytes,chmod,date,info,size,timestamp",
		"return": "$this"
	} **/
	protected function WriteContent($sContent, $bReload=true, $sMode="wb") {
		$sFilePath = $this->attribute("path");
		if($sFilePath===null) {
			self::errorMessage($this->object, 1002);
			return false;
		}

		if($sMode==="r+b") {
			if($handler = @\fopen($sFilePath, $sMode)) {
				$nLen = \strlen($sContent);
				$nLength = \filesize($sFilePath) + $nLen;
				$sFileContent = \fread($handler, $nLen);
				\rewind($handler);
				$x = 1;
				while (\ftell($handler) < $nLength) {
					\fwrite($handler, $sContent);
					$sContent = $sFileContent;
					$sFileContent = \fread($handler, $nLen);
					\fseek($handler, $x * $nLen);
					$x++;
				}

				if(!$this->hugefile) {
					\fclose($handler);
					if($bReload) { $this->reload($sFilePath); }
				}

				return $this;
			}
		} else {
			if(\is_resource($this->hugeWriter)) {
				if($this->sHugeWriterMode==$sMode) {
					\fwrite($this->hugeWriter, $sContent);
					return $this;
				} else {
					\fclose($this->hugeWriter);
					$this->hugeWriter = null;
				}
			}

			if($this->attribute("protocol")=="filesystem") {
				if($handler = @\fopen($sFilePath, $sMode)) {
					if(!\file_exists($sFilePath)) { @\chmod($sFilePath, NGL_CHMOD_FILE); }
					\fwrite($handler, $sContent);
					
					if(!$this->hugefile) {
						\fclose($handler);
						if($bReload) { $this->reload($sFilePath); }
					} else {
						$this->hugeWriter = $handler;
						$this->sHugeWriterMode = $sMode;
					}
					return $this;
				} else {
					self::errorMessage($this->object, 1003, $sFilePath);
				}
			}
		}

		return false;
	}
}

?>