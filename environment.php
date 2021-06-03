<?php
/*
# Nogal
*the most simple PHP Framework* by hytcom.net
GitHub @hytcom
___
  
# definición de constantes
Para consultar la información acerca de las contantes, visitar

https://github.com/hytcom/wiki/blob/master/nogal/docs/constants.md

*/
namespace nogal;

#===============================================================================
# CONFIGURACIONES POR DEFECTO
#===============================================================================
// nombre del proyecto
nglRoot::defineConstant("NGL_PROJECT",											"NOGAL");

// version del proyecto
nglRoot::defineConstant("NGL_PROJECT_RELEASE",									"lasted");

// RUTAS -----------------------------------------------------------------------
// document_root
nglRoot::defineConstant("NGL_DOCUMENT_ROOT",									$_SERVER["DOCUMENT_ROOT"]);

// directorio project
nglRoot::defineConstant("NGL_PATH_PROJECT",										NGL_DOCUMENT_ROOT);

// directorio public
nglRoot::defineConstant("NGL_PATH_PUBLIC",										NGL_DOCUMENT_ROOT);

// configuraciones
nglRoot::defineConstant("NGL_PATH_CONF",										NGL_PATH_PROJECT."/conf");

// repositorio de datos
nglRoot::defineConstant("NGL_PATH_DATA",										NGL_PATH_PROJECT."/data");

// project grafts (third-party)
nglRoot::defineConstant("NGL_PATH_GRAFTS",										NGL_PATH_PROJECT."/grafts");

// nuts
nglRoot::defineConstant("NGL_PATH_NUTS",										NGL_PATH_PROJECT."/nuts");

// repositorio de sesiones para los modos fs o sqlite
nglRoot::defineConstant("NGL_PATH_SESSIONS",									NGL_PATH_PROJECT."/sessions");

// carpeta temporal
nglRoot::defineConstant("NGL_PATH_TMP",											NGL_PATH_PROJECT."/tmp");

// carpeta logs
nglRoot::defineConstant("NGL_PATH_LOGS",										NGL_PATH_PROJECT."/logs");

// contenedor de operaciones con archivos
nglRoot::defineConstant("NGL_SANDBOX",											NGL_PATH_PROJECT);

// tutores - chequeos de REFERER y ONCECODE
nglRoot::defineConstant("NGL_PATH_TUTORS",										NGL_PATH_PROJECT."/tutors");

// reglas para la validacion de variables
nglRoot::defineConstant("NGL_PATH_VALIDATE",									NGL_PATH_PROJECT."/validate");

// directorio de código fuente para el uso de prickout
nglRoot::defineConstant("NGL_PATH_PRICKOUT",									NGL_PATH_PROJECT."/prickout");

// URL del proyecto
nglRoot::defineConstant("NGL_URL",												((isset($_SERVER["HTTP_HOST"])) ? ((isset($_SERVER["HTTPS"]) && !empty($_SERVER["HTTPS"])) ? "https" : "http")."://".$_SERVER["HTTP_HOST"] : ""));

// SEGURIDAD -------------------------------------------------------------------
// id de session
nglRoot::defineConstant("NGL_SESSION_INDEX",									"NOGAL");

// clave AES para encriptar passwords (NULL para desactivar encriptación)
nglRoot::defineConstant("NGL_PASSWORD_KEY",										null);

// control de accesos (NULL para desactivar)
nglRoot::defineConstant("NGL_ALVIN",											null);

// tipo de carga de ALVIN-TOKEN (TOKEN|TOKENUSER|PROFILE)
nglRoot::defineConstant("NGL_ALVIN_MODE",										"TOKENUSER");

// array de IPs autorizadas (NULL para desactivar)
nglRoot::defineConstant("NGL_AUTHORIZED_IPS",									null);

// valida que el referer sea del mismo dominio 
nglRoot::defineConstant("NGL_REFERER",											true);

// valida la vigencia de un código ONCE
nglRoot::defineConstant("NGL_ONCECODE",											true);

// tiempo de vigencia de los códigos ONCE
nglRoot::defineConstant("NGL_ONCECODE_TIMELIFE",								900);

// ERRORES ---------------------------------------------------------------------
// manipulacion de errores (true | false)
nglRoot::defineConstant("NGL_HANDLING_ERRORS",									true);

// formato de impresion de errores cuando NGL_HANDLING_ERRORS es true (html | text)
nglRoot::defineConstant("NGL_HANDLING_ERRORS_FORMAT",							"text");

// tipo de salida de errores cuando NGL_HANDLING_ERRORS es true (boolean | code | die | print | return)
nglRoot::defineConstant("NGL_HANDLING_ERRORS_MODE",								"print");

// muestra el rastreo del error cuando NGL_HANDLING_ERRORS es true (true | false)
nglRoot::defineConstant("NGL_HANDLING_ERRORS_BACKTRACE", 						false);


// FORMATOS --------------------------------------------------------------------
// separador de filas
nglRoot::defineConstant("NGL_STRING_LINEBREAK", 								"\n");

// separador de columnas
nglRoot::defineConstant("NGL_STRING_SPLITTER", 									"\t");

// separador de números
nglRoot::defineConstant("NGL_STRING_NUMBERS_SPLITTER", 							";");

// separador decimal
nglRoot::defineConstant("NGL_NUMBER_SEPARATOR_DECIMAL", 						".");

// separador de miles
nglRoot::defineConstant("NGL_NUMBER_SEPARATOR_THOUSANDS",						",");


// FECHA, HORA E IDIOMA --------------------------------------------------------
// zona horaria: http://php.net/manual/es/timezones.php
nglRoot::defineConstant("NGL_TIMEZONE",											"America/Argentina/Buenos_Aires");

// nombre de los meses del año
nglRoot::defineConstant("NGL_DATE_MONTHS",										"Enero,Febrero,Marzo,Abril,Mayo,Junio,Julio,Agosto,Septiembre,Octubre,Noviembre,Diciembre");

// nombre de los días de la semana
nglRoot::defineConstant("NGL_DATE_DAYS",										"Domingo,Lunes,Martes,Miércoles,Jueves,Viernes,Sábado");

// idiomas aceptados
nglRoot::defineConstant("NGL_ACCEPTED_LANGUAGES",								"es");


// OTRAS -----------------------------------------------------------------------
// inicia el framework ignorando los chequeos de compatibilidad
nglRoot::defineConstant("NGL_RUN_ANYWAY", 										false);

// fuera de linea
nglRoot::defineConstant("NGL_FALLEN",											NULL);

// activa/desactiva la pantalla de configuración
nglRoot::defineConstant("NGL_GARDENER", 										false);

// activa/desactiva a bee (NULL para desactivar)
nglRoot::defineConstant("NGL_BEE", 												null);

// separador de directorios
nglRoot::defineConstant("NGL_DIR_SLASH",										DIRECTORY_SEPARATOR);

// desarrollo: E_ALL | produccion: E_ERROR | E_WARNING | E_PARSE | E_NOTICE
nglRoot::defineConstant("NGL_ERROR_REPORTING",									E_ALL);

// indica valor nulo pudiendo ser o no NULL
nglRoot::defineConstant("NGL_NULL", 											"__NGL_NULL_VALUE__");

// tipografia por defecto
nglRoot::defineConstant("NGL_FONT", 											NGL_PATH_FRAMEWORK."/assets/roboto.ttf");

// permisos aplicados a las nuevas carpetas
nglRoot::defineConstant("NGL_CHMOD_FOLDER",										0775);

// permisos aplicados a los nuevos archivos
nglRoot::defineConstant("NGL_CHMOD_FILE",										0664);


// SISTEMA ---------------------------------------------------------------------
$NGL_URL = \constant("NGL_URL");
if(!empty($NGL_URL)) {
	$NGL_URLPARTS = \parse_url(NGL_URL);
	if(isset($NGL_URLPARTS["port"])) {
		$NGL_URLPARTS["urlport"] =  ":".$NGL_URLPARTS["port"];
	} else {
		$NGL_URLPARTS["port"] = $NGL_URLPARTS["urlport"] = "";
	}

	nglRoot::defineConstant("NGL_URL_PROTOCOL", 			$NGL_URLPARTS["scheme"]);
	nglRoot::defineConstant("NGL_URL_HOST", 				$NGL_URLPARTS["host"]);
	nglRoot::defineConstant("NGL_URL_PORT", 				$NGL_URLPARTS["port"]);
	nglRoot::defineConstant("NGL_URL_ROOT", 				$NGL_URLPARTS["scheme"]."://".$NGL_URLPARTS["host"].$NGL_URLPARTS["urlport"]);
	unset($NGL_URLPARTS);
}
unset($NGL_URL);

// path del archivo actual desde NGL_URL y REQUEST_URI
if(PHP_SAPI!="cli") {
	$NGL_PATH_CURRENT = \explode("?", NGL_URL_ROOT.$_SERVER["REQUEST_URI"], 2);
	$NGL_PATH_CURRENT_QUERY = isset($NGL_PATH_CURRENT[1]) ? $NGL_PATH_CURRENT[1] : "";
	$NGL_PATH_CURRENT = \str_replace(NGL_URL, "", $NGL_PATH_CURRENT[0]);
	\define("NGL_TERMINAL", false);
	\define("NGL_PATH_CURRENT", \preg_replace("#/+$#", "/", $NGL_PATH_CURRENT));
	\define("NGL_PATH_CURRENT_QUERY", $NGL_PATH_CURRENT_QUERY);
	unset($NGL_PATH_CURRENT, $NGL_PATH_CURRENT_QUERY);
} else {
	\define("NGL_TERMINAL", true);
	\define("NGL_PATH_CURRENT", \getcwd()."/".$_SERVER["PHP_SELF"]);
}

#===============================================================================
# VARIABLES RESERVADAS
#===============================================================================
$_SET	= [];	// variables seteadas en las plantillas nglXtroitel
$ENV 	= []; 	// variables de entorno tambien disponibles en las plantillas nglXtroitel
$env	= &$ENV; 	// EVN alias

#===============================================================================
# CONFIGURACION PHP
#===============================================================================
// errores || errors
if(@\ini_set("display_errors", true)===false) {
	echo "<b>NOGAL ERROR</b> Can't modify PHP display_errors.<br />\n";
} else {
	@\ini_set("track_errors", 0);
	@\ini_set("html_errors", 0);
	\error_reporting(NGL_ERROR_REPORTING);
}

// manejador de errores || errors handler
if(NGL_HANDLING_ERRORS) {
	\set_error_handler(__NAMESPACE__."\\nglRoot::errorsHandler", E_ALL | E_STRICT);
}

// timezone
if(\function_exists("date_default_timezone_set")) {
	\date_default_timezone_set(NGL_TIMEZONE);
}


?>