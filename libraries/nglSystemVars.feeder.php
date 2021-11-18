<?php

namespace nogal;

/** CLASS {
	"name" : "nglSystemVars",
	"object" : "sysvar",
	"type" : "main",
	"revision" : "20140202",
	"extends" : "nglTrunk",
	"description" : "
		Establece y almacena las variables por sistema de NOGAL.
		<ul>
			<li><b>NULL:</b> valor NULL</li>
			<li><b>REGEX:</b> expresiones regulares frecuentes</li>
			<li><b>SELF:</b> datos del path del archivo actual</li>
			<li><b>UID:</b> valor de la variable $_SESSION[NGL_SESSION_INDEX]["ALVIN"]["UID"] o NULL</li>
			<li><b>VERSION:</b> datos de la versión</li>
		</ul>


		Tambien son capturadas variables $_REQUEST de uso frecuente previas a la validación por Validate
		<ul>
			<li><b>ID:</b> entero o cadena imya ( $_REQUEST["id"] )</li>
			<li><b>Q:</b> usualmente utilizada en búsquedas ( $_REQUEST["q"] )</li>
		</ul>


		nglSystemVars construye el objeto $var dentro de NOGAL el cual es accedido a través de: <b>$ngl("sysvar")->NOMBRE_DE_VARIABLE</b>
	",
	"variables" : {
		"$VARS" : ["private", "Contenedor de variables"],
		"$SETTINGS" : ["private", "Valores por default de variables"]
	}
} **/
class nglSystemVars extends nglTrunk {

	protected $class		= "nglSystemVars";
	protected $me			= "sysvar";
	protected $object		= "sysvar";
	private $VARS;
	private $SETTINGS;

	public function __builder__() {
		$SETTINGS = [];

		// para que una variable sea privada no debe admitir el argumento $mValue
		// por eso se asignan mediate la ejecucion de un metodo privado
		
		// PRIVADAS (escritura privada, lectura publica)
		// nombres de meses y dias
		$SETTINGS["ACCENTED"]		= ['$this->AccentedChars()'];
		
		// IP del cliente
		$SETTINGS["IP"]				= ['$this->SetIP()'];

		// variable con contenido null
		$SETTINGS["NULL"]			= ['null'];

		// expresiones regulares de uso comun
		$SETTINGS["REGEX"]			= ['$this->SetRegexs()'];

		// PHP_SELF
		$SETTINGS["SELF"]			= ['$this->SetSelf()'];
		
		// id del usuario (en caso de existir un login)
		$SETTINGS["UID"]			= ['$this->SetUID()'];
		
		// version
		$SETTINGS["VERSION"]		= ['$this->SetVersion()'];

		// SETTINGS
		$this->SETTINGS = $SETTINGS;
		
		// VARIABLES
		$VARS = [];
		foreach($SETTINGS as $sVarname => $mValue) {
			$VARS[$sVarname] = (!\array_key_exists(1, $mValue)) ? eval("return ".$mValue[0].";") : $mValue[1];
		}

		$this->VARS = $VARS;
	}

	/** FUNCTION {
		"name" : "__get", 
		"type" : "public",
		"description" : "
			Método mágico encargado de retornar los valores de la variable $VARS cuando es invocada por medio de <b>$ngl("sysvar")</b>
			si no se especifica un nombre de variable se retornarán todas las variables de sistema.
		",
		"parameters" : { "$sVarname" : ["string", "Nombre de variable", "null"] },
		"examples" : {
			"llamada de variable" : "echo $ngl("sysvar")->foo;",
			"llamada global" : "print_r($ngl("sysvar"));"
		},
		"return" : "mixed"
	} **/
	public function __get($sVarname="ALL") {
		if($sVarname!=="ALL") {
			if(isset($this->VARS[$sVarname])) {
				return $this->VARS[$sVarname];
			}
		} else {
			return $this->VARS;
		}
	}

	/** FUNCTION {
		"name" : "AccentedChars", 
		"type" : "private",
		"description" : "
			Retorna un array asociativo con los caracteres acentuados y su equivalente sin acento, 
			donde la clave es el caracter acentuado y el valor el caracter sin acentuar
		",
		"return" : "array"
	} **/
	private function AccentedChars() {
		$vChars = [
			"À"=>"A", "Á"=>"A", "Â"=>"A", "Ã"=>"A", "Ä"=>"A", "Å"=>"A", "Æ"=>"A",
			"È"=>"E", "É"=>"E", "Ê"=>"E", "Ë"=>"E",
			"Ì"=>"I", "Í"=>"I", "Î"=>"I", "Ï"=>"I",
			"Ò"=>"O", "Ó"=>"O", "Ô"=>"O", "Õ"=>"O", "Ö"=>"O", "Ø"=>"O",
			"Ù"=>"U", "Ú"=>"U", "Û"=>"U", "Ü"=>"U",
			"à"=>"a", "á"=>"a", "â"=>"a", "ã"=>"a", "ä"=>"a", "å"=>"a", "æ"=>"a",
			"è"=>"e", "é"=>"e", "ê"=>"e", "ë"=>"e",
			"ì"=>"i", "í"=>"i", "î"=>"i", "ï"=>"i",
			"ð"=>"o", "ò"=>"o", "ó"=>"o", "ô"=>"o", "õ"=>"o", "ö"=>"o", "ø"=>"o",
			"ù"=>"u", "ú"=>"u", "û"=>"u",
			"Š"=>"S", "š"=>"s", "Ž"=>"Z", "ž"=>"z", "Ç"=>"C", "Ñ"=>"N", "Ý"=>"Y", "Þ"=>"B",
			"ß"=>"Ss", "ç"=>"c", "ñ"=>"n", "ý"=>"y", "ý"=>"y", "þ"=>"b", "ÿ"=>"y"
		];
		
		return $vChars;
	}

	/** FUNCTION {
		"name" : "SetIP", 
		"type" : "private",
		"description" : "
			En caso de existir setea el valor de la variable $_SERVER["REMOTE_ADDR"] en la variable <b>IP</b>
			de lo contrario la setea como localhost
		",
		"return" : "string o null"
	} **/
	private function SetIP() {
		return (isset($_SERVER["REMOTE_ADDR"])) ? $_SERVER["REMOTE_ADDR"] : "127.0.0.1";
	}
	
	/** FUNCTION {
		"name" : "SetSelf", 
		"type" : "private",
		"description" : "almacena los datos de la ruta del archivo actual en la variable <b>SELF</b>",
		"examples" : {
			"/www/htdocs/foo/bar.php" : "
				[b]basename:[/b] bar.php
				[b]dirname:[/b] foo
				[b]extension:[/b] php
				[b]filename:[/b] bar
				[b]fullpath:[/b] /www/htdocs/foo/bar.php
				[b]path:[/b] /www/htdocs/foo
				
			",
			"http://localhost/foo/bar.php?val=demo" : "
				[b]basename:[/b] bar.php
				[b]dirname:[/b] foo
				[b]extension:[/b] php
				[b]filename:[/b] bar
				[b]fullpath:[/b] /www/htdocs/foo/bar.php
				[b]path:[/b] /www/htdocs/foo
				[b]query_string:[/b] val=demo
				[b]url:[/b] foo/bar.php?val=demo
				[b]fullurl:[/b] http://localhost/foo/bar.php?val=demo
				[b]urlpath:[/b] foo/bar.php
				[b]fullurlpath:[/b] http://localhost/foo/bar.php
			"
		},
		"return" : "array"
	} **/
	private function SetSelf() {
		return self::currentPath();
	}

	/** FUNCTION {
		"name" : "SetRegexs", 
		"type" : "private",
		"description" : "Almacena expresiones regulares de uso frecuente en la variable <b>REGEX</b>",
		"examples" : {
			"Expresiones" : "
				[b]base64:[/b] caracteres permitidos en una cadena base64
				[b]color:[/b] color en formato hexadecimal #RGB, #RRGGBB ó #RRGGBBAA
				[b]date:[/b] fecha en formato yyyy-mm-dd
				[b]datetime:[/b] fecha y hora en formato yyyy-mm-dd hh-ii-ss
				[b]email:[/b] dirección de correo
				[b]filename:[/b] formato windows y Linux
				[b]fulltag:[/b] etiqueta HTML completa: <div id="foo"...>Lorem ipsum dolor sit amet...</div>
				[b]imya:[/b] cadena imya
				[b]ip:[/b] dirección IPv4
				[b]phpvar:[/b] nombre de variable PHP
				[b]tag:[/b] etiqueta HTML, sólo apertura: <div id="foo"...>
				[b]time:[/b] hora en formato hh:ii o hh:ii:ss
				[b]url:[/b] dirección URL/FTP
			"
		},
		"return" : "array"
	} **/
	private function SetRegexs() {
		$vRegexs = [];
		$vRegexs["base64"] 		= "[a-zA-Z0-9\+\/\=]*";
		$vRegexs["color"] 		= "#([0-9A-F]{6,8}|[0-9A-F]{3})";
		$vRegexs["date"] 		= "[0-9]{4}\-([012][0-9]|3[01])\-([012][0-9]|3[01])";
		$vRegexs["datetime"] 	= "[0-9]{4}\-([012][0-9]|3[01])\-([012][0-9]|3[01])\ ([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])";
		$vRegexs["email"] 		= "[a-zA-Z0-9\_\-]+(\.[a-zA-Z0-9\-\_]+)*@[a-zA-Z0-9\-]+(\.[a-zA-Z0-9]+)(\.[a-zA-Z]{2,})*";
		$vRegexs["filename"] 	= "(?(?=^([a-z]:|\\\\))(^([a-z]:|\\\\)[^\/\?\<\>\:\*\|]+)|([^\\0]+))";
		$vRegexs["fulltag"]		= "<([a-zA-Z]+)(\"[^\"]*\"|\'[^\']*\'|[^\'\">])*>(.*?)<\/\\1>";
		$vRegexs["imya"] 		= "[a-zA-Z][a-zA-Z0-9]{31}";
		$vRegexs["ipv4"]		= "((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)";
		$vRegexs["ipv6"]		= "(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]).){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]).){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))";
		$vRegexs["phpvar"] 		= "\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*";
		$vRegexs["tag"]			= "<([a-zA-Z]+)(\"[^\"]*\"|\'[^\']*\'|[^\'\">])*>";
		$vRegexs["time"] 		= "([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?";
		$vRegexs["url"] 		= "((http|ftp|HTTP|FTP)(s|S)?:\/\/)?([0-9a-zA-Z\.-]+)\.([a-zA-Z\.]{2,6})([\/a-zA-Z0-9 \.-]*)*\/?";

		return $vRegexs;
	}

	/** FUNCTION {
		"name" : "SetUID", 
		"type" : "private",
		"description" : "
			En caso de existir setea el valor de la variable $_SESSION[NGL_SESSION_INDEX]["ALVIN"]["id"] en la variable <b>UID</b>
			de lo contrario la setea como NULL
		",
		"return" : "int o null"
	} **/
	private function SetUID() {
		if(isset($_SESSION[NGL_SESSION_INDEX]["ALVIN"], $_SESSION[NGL_SESSION_INDEX]["ALVIN"]["id"])) {
			return $_SESSION[NGL_SESSION_INDEX]["ALVIN"]["id"];
		} else {
			return null;
		}
	}
	
	/** FUNCTION {
		"name" : "SetVersion", 
		"type" : "private",
		"description" : "Almacena los datos de la versión de NOGAL en la variable <b>VERSION</b>",
		"return" : "array"
	} **/
	private function SetVersion() {
		$vVersion					= [];
		$vVersion["name"]			= "nogal";
		$vVersion["description"]	= "the most simple PHP Framework";
		$vVersion["version"]		= \file_get_contents(NGL_PATH_FRAMEWORK.NGL_DIR_SLASH."version");
		$vVersion["author"]			= "hytcom";
		$vVersion["site"]			= "https://hytcom.net";
		$vVersion["documentation"]	= "https://github.com/hytcom/wiki/tree/master/nogal";
		$vVersion["github"]			= "https://github.com/hytcom/nogal-php";
		$vVersion["docker"]			= "docker pull hytcom/nogal";

		return $vVersion;
	}
	
	public function sessionVars() {
		$this->VARS["UID"] = $this->SetUID();
	}
}

?>