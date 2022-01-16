<?php

namespace nogal;

/** CLASS {
	"name" : "nglURL",
	"object" : "url",
	"type" : "instanciable",
	"revision" : "20150306",
	"extends" : "nglBranch",
	"description" : "Parsea URLs en sus diferentes componentes, permite actualizarlos y volver conformar la dirección",
	"configfile" : "no posee",
	"variables" : {
		"$sURL" : ["private", "URL activa"],
	},
	"arguments": {
		"url" : ["string", "URL"],
		"argument" : ["string", "Argumento que desea modificarse."],
		"value" : ["mixed", "Nuevo valor para Argument."]
	},
	"attributes": {
		"basename" : ["string", "nombre completo del archivo"],
		"url" : ["string", "URL completa"],
		"dirname" : ["string", "directorio del archivo"],
		"extension" : ["string", "extensión del archivo"],
		"filename" : ["string", "nombre sin extensión del archivo"],
		"fragment" : ["string", "Referencia interna en el documento"],
		"host" : ["string", "Dominio"],
		"path" : ["string", "Path completo del archivo apuntado"],
		"params" : ["string", "Request query parseado como array"],
		"pass" : ["string", "Contraseña (en caso de existir)"],
		"port" : ["int", "Puerto"],
		"query" : ["string", "Request query"],
		"scheme" : ["string", "Protocolo"],
		"ssl" : ["boolean", "Determina si el protocolo es un protocolo seguro"],
		"user" : ["string", "Nombre de usuario (en caso de existir)"]
	}
} **/
class nglURL extends nglBranch {

	private $sURL = null;

	final protected function __declareArguments__() {
		$vArguments					= [];
		$vArguments["url"]			= ['$mValue', null];
		$vArguments["argument"]		= ['$mValue', null];
		$vArguments["value"]		= ['$mValue', null];

		return $vArguments;
	}

	final protected function __declareAttributes__() {
		$vAttributes 				= [];
		$vAttributes["basename"]	= null;
		$vAttributes["dirname"]	 	= null;
		$vAttributes["extension"]	= null;
		$vAttributes["filename"]	= null;
		$vAttributes["fragment"]	= null;
		$vAttributes["host"]	 	= null;
		$vAttributes["params"]	 	= null;
		$vAttributes["pass"]	 	= null;
		$vAttributes["path"]	 	= null;
		$vAttributes["port"]	 	= null;
		$vAttributes["query"]	 	= null;
		$vAttributes["scheme"]	 	= null;
		$vAttributes["ssl"]	 		= null;
		$vAttributes["user"]	 	= null;

		return $vAttributes;
	}
	
	final protected function __declareVariables__() {
	}

	final public function __init__() {
	}

	/** FUNCTION {
		"name" : "get",
		"type" : "public",
		"description" : "Retorna la URL activa, ya sea la cargada por el aguamento URL o la última generada por el método <b>nglURL::unparse</b>.",
		"return" : "string"
	} **/
	public function get() {
		return $this->sURL;
	}

	/** FUNCTION {
		"name" : "parse",
		"type" : "public",
		"description" : "Descompone una URL en los atributos del objeto y retorna los datos en un array asociativo. Si <b>$sURL</b> no es una cadena, retornará FALSE",
		"parameters" : {
			"$sURL" : ["string", "Dirección URL a parsear", "argument::url"]
		},
		"input" : "url",
		"output" : "basename, dirname, extension, filename, fragment, host, params, pass, path, port, query, scheme",
		"examples" : {
			"Ejemplo" : "
				$url = "http://example:pass@hytcom.net:80/nogal/help/class.php?q=nglURL::parse#privates";
				$http = $ngl("url.foo")->parse($url)->parts();
				print_r($http);
				
				# resultado
				Array (
					[basename] => "class.php"
					[dirname] => "/nogal/help"
					[extension] => "php"
					[filename] => "class"
					[fragment] => "privates"
					[host] => "hytcom.net"
					[pass] => "pass"
					[params] => Array (
						[ q ] => "nglURL::parse"
					)
					[path] => "/nogal/help/class.php"
					[port] => "80"
					[query] => "q=nglURL::parse"
					[scheme] => "http"
					[ssl] => false
					[user] => "example"

				)
			"
		},
		"seealso" : ["nglURL::unparse"],
		"return" : "$this"
	} **/
	public function parse() {
		list($sURL) = $this->getarguments("url", \func_get_args());
		if(!is_string($sURL)) { return false; }
		$this->sURL = $sURL;
	
		$vURL = \parse_url($sURL);
		if(isset($vURL["query"])) {
			$vURL["params"] = [];
			$aPairs = \explode("&", $vURL["query"]);
			foreach($aPairs as $sPair) {
				$sPair = \trim($sPair);
				if($sPair==="") { continue; }
				list($sKey, $sValue) = \explode("=", $sPair);
				$vURL["params"][$sKey] = \urldecode($sValue);
			}
		}

		$this->attribute("ssl", false);
		if(isset($vURL["scheme"]))	{
			$this->attribute("scheme", $vURL["scheme"]);
			$this->attribute("ssl", (\strtolower($vURL["scheme"])=="https"));
		}
		
		if(isset($vURL["user"])) 	{ $this->attribute("user", 		$vURL["user"]); }
		if(isset($vURL["pass"])) 	{ $this->attribute("pass", 		$vURL["pass"]); }
		if(isset($vURL["host"])) 	{ $this->attribute("host", 		$vURL["host"]); }
		if(isset($vURL["port"])) 	{ $this->attribute("port", 		$vURL["port"]); }
		if(isset($vURL["query"])) 	{ $this->attribute("query", 	$vURL["query"]); }
		if(isset($vURL["params"])) 	{ $this->attribute("params", 	$vURL["params"]); }
		if(isset($vURL["fragment"])){ $this->attribute("fragment", 	$vURL["fragment"]); }
		if(isset($vURL["path"])) 	{
			$this->attribute("path", $vURL["path"]);
			$vPath = \pathinfo($vURL["path"]);
			if(isset($vPath["dirname"])) 	{ $this->attribute("dirname",	$vPath["dirname"]); }
			if(isset($vPath["basename"])) 	{ $this->attribute("basename",	$vPath["basename"]); }
			if(isset($vPath["extension"])) 	{ $this->attribute("extension",	$vPath["extension"]); }
			if(isset($vPath["filename"])) 	{ $this->attribute("filename",	$vPath["filename"]); }
			
			$vURL = \array_merge($vURL, $vPath);
		}

		return $this;
	}

	/** FUNCTION {
		"name" : "parts",
		"type" : "public",
		"description" : "Retorna todas las partes de la URL previamente generadas por <b>nglURL::parse</b> que estos pueden haber sufrido actualizaciones.",
		"input" : "basename*, dirname*, extension*, filename*, fragment*, host*, params*, pass*, path*, port*, query*, scheme*",
		"return" : "array"
	} **/
	public function parts() {
		return $this->__info__("attributes");
	}

	/** FUNCTION {
		"name" : "unparse",
		"type" : "public",
		"description" : "Compone una URL uniendo los atributos del objeto. Si el parámetro <b>query</b> es ignorado si existe <b>params</b>, ya que estos pueden haber sufrido actualizaciones.",
		"input" : "basename*, dirname*, extension*, filename*, fragment*, host*, params*, pass*, path*, port*, query*, scheme*",
		"seealso" : ["nglURL::parse","nglURL::update"],
		"return" : "string"
	} **/
	public function unparse() {
		if($this->attribute("host")===null) {
			if($this->url!==null) {
				$this->parse();
			} else {
				return false;
			}
		}

		// parts
		$vURL = $this->__info__("attributes");

		// armado
		$sScheme	= (isset($vURL["scheme"])) ? \strtolower($vURL["scheme"]) : "http";
		$sUser		= (isset($vURL["user"])) ? $vURL["user"] : null;
		$sPass		= (isset($vURL["pass"])) ? $vURL["pass"] : null;
		$sHost		= (isset($vURL["host"])) ? $vURL["host"] : null;
		$sFragment	= (isset($vURL["fragment"])) ? $vURL["fragment"] : null;

		// path
		if(isset($vURL["filename"], $vURL["extension"])) {
			$sBasename = $vURL["filename"].".".$vURL["extension"];
		} else {
			$sBasename = (isset($vURL["basename"])) ? $vURL["basename"] : null;
		}

		if($sBasename!==null) {
			$sPath = (isset($vURL["dirname"])) ? $vURL["dirname"] : "";
			$sPath .= "/".$sBasename;
		} else {
			$sPath = (isset($vURL["path"])) ? $vURL["path"] : "";
		}

		// query
		if(isset($vURL["params"])) {
			$sQuery = $this->QueryString($vURL["params"]);   
		} else {
			$sQuery = (isset($vURL["query"])) ? $vURL["query"] : null;
		}
		
		$sURL  = $sScheme."://";
		$sURL .= ($sUser!==null && $sUser != "" && $sPass!==null) ? $sUser.":".$sPass."@" : "";
		$sURL .= \str_replace("//", "/", $sHost."/".$sPath);
		$sURL .= ($sQuery!==null) ? "?".$sQuery : "";
		$sURL .= ($sFragment!==null) ? "#".$sFragment : "";

		$this->sURL = $sURL;
		$this->parse($sURL);

		return $sURL;
	}

	/** FUNCTION {
		"name" : "update",
		"type" : "public",
		"description" : "
			Permite actualizar las distintas partes de la URL basandose en las partes generadas <b>nglURL::parse</b>.
			<b>nglURL::parse</b> se autoejecutará si aún no ha sido ejecutado y está seteado el argumento <b>url</b>.
		",
		"parameters" : {
			"$sPart" : ["string", "Parte de la URL", "argument::argument"],
			"$mValue" : ["mixed", "Nuevo valor", "argument::value"]
		},
		"seealso" : ["nglURL::parse","nglURL::unparse"],
		"examples" : {
			"Ejemplo" : "
				$ngl("url.foo")->url = "http://www.hytcom.net/nogal/help/class.php?q=nglURL::parse#privates";
				$ngl("url.foo")->update("host", "hytcom.net");
				$ngl("url.foo")->update("fragment", "publics");
				echo $ngl("url.foo")->unparse();

				# salida
				"http://hytcom.net/nogal/help/class.php?q=nglURL::parse#publics"
			"
		},
		"return": "$this"
	} **/
	public function update() {
		list($sPart, $mValue) = $this->getarguments("argument,value", \func_get_args());

		if(!$this->isAttribute($sPart)) { return false; }

		if($this->attribute("host")===null && $this->url!==null) {
			$this->parse();
		}

		$sPart = \strtolower($sPart);
		if($sPart=="params") {
			$aOldParams = ($this->attribute("params")) ? $this->attribute("params") : [];
			$mValue = \array_merge($aOldParams, $mValue);
		}
		$this->attribute($sPart, $mValue);

		return $this;
	}

	/** FUNCTION {
		"name" : "QueryString",
		"type" : "private",
		"description" : "Convierte un Array en una cadena de variables válidad para ser enviada vía GET o POST",
		"seealso" : ["nglURL::get","nglURL::post"],
		"return" : "string"
	} **/
	private function QueryString($aParts) {
		$aPairs = [];
		foreach($aParts as $sKey=>$sValue) {
			$aPairs[] = $sKey."=".\urlencode(\stripslashes($sValue));
		}
		$sQuery = \implode("&", $aPairs);
		
		return $sQuery;
	}
}

?>