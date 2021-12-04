<?php

namespace nogal;

/** CLASS {
	"name" : "nglBranch",
	"revision" : "20140126",
	"extends" : "nglTrunk",
	"description" : "Contenedor de objetos",
	"variables": {
		"$class" : ["string", "Clase a la que pertenece el objeto"],
		"$aArguments" : ["array", "Argumentos del objeto seteados por el usuario"],
		"$aArgumentsSettings" : ["array", "Valores por defecto de los argumentos del objeto"],
		"$aAttributes" : ["array", "Atributos del objeto resultado de la ejecución de sus métodos"],
		"$aAttributesSettings" : ["array", "Valores por defecto de los atributos del objeto"],
		"$me" : ["string", "Nombre Real del objeto"],
		"$object" : ["string", "Nombre del objeto"]
	}
} **/
abstract class nglBranch extends nglTrunk {

	protected $class;
	protected $me;
	protected $object;

	protected $aArguments;
	protected $aArgumentsSettings;
	protected $aAttributes;
	protected $aAttributesSettings;
	
	public $CONFIG;

	final public function __builder__($vArguments) {
		$this->class				= $vArguments[0];
		$this->me					= $vArguments[1];
		$this->object				= strstr($vArguments[1],".",true);

		$this->aArguments 			= [];
		$this->aArgumentsSettings	= [];
		$this->aAttributes			= [];
		$this->aAttributesSettings	= [];
	}
	
	final public function __call($sProperty, $aArgs) {
		if(\is_array($aArgs) && \count($aArgs)) {
			$mValue = $aArgs[0];
			if(\array_key_exists($sProperty, $this->aArgumentsSettings)) {
				if(\is_array($this->aArgumentsSettings[$sProperty])) {
					$this->aArguments[$sProperty] = eval("return ".$this->aArgumentsSettings[$sProperty][0].";");
				}
			}
			return $this;
		}

		if(isset($this->aArguments[$sProperty])) {
			return eval("return ".$this->aArguments[$sProperty].";");
		}

		return $this;
	}

	/** FUNCTION {
		"name" : "__get", 
		"type" : "public",
		"description" : "
			Método mágico encargado de retornar los valores de los argumentos y atributos de los objetos.
			cuando existan un argumento y un atributo con el mismo nombre __get retornará el valor de  
			este último.

			Por ser un método mágico __get se invocará automaticamente cuando se solicita el valor 
			de algun argumento o atributo.
			
			__get no retorna el valor de atributos privados.
		",
		"parameters" : { "$sProperty" : ["string", "Nombre del agumento o atributo", "3"] },
		"examples" : {
			"llamada de un argumento" : "echo $ngl->c("foo")->bar;",
			"llamada de un atributo" : "echo $ngl->c("foo")->bar;"
		},
		"return" : "mixed"
	} **/
	final public function __get($sProperty) {
		// no atributos privados
		if(!empty($sProperty) && $sProperty[0]!="_") {
			if(\array_key_exists($sProperty, $this->aAttributes)) {
				// atributos
				return $this->aAttributes[$sProperty];
			} else {
				// argumentos
				return (isset($this->aArguments[$sProperty])) ? $this->aArguments[$sProperty] : "";
			}
		}
		
		return null;
	}

	/** FUNCTION {
		"name" : "__set", 
		"type" : "public",
		"description" : "
			Método mágico encargado de asignar los valores de los argumentos de los objetos.<br />
			Por ser un método mágico __set se invocará automaticamente cuando se asigne un valor a un argumento.
		",
		"parameters" : { 
			"$sProperty" : ["string", "Nombre del agumento"],
			"$mValue" : ["mixed", "Nuevo valor del argumento"]
		},
		"examples" : { "asignación de valor" : "echo $ngl->c("foo")->bar = \"foobar\";", },
		"return" : "mixed"
	} **/
	final public function __set($sProperty, $mValue=null) {
		if(\array_key_exists($sProperty, $this->aArgumentsSettings)) {
			if(\is_array($this->aArgumentsSettings[$sProperty])) {
				$this->aArguments[$sProperty] = eval("return ".$this->aArgumentsSettings[$sProperty][0].";");
				return $this->aArguments[$sProperty];
			}
		}
		
		return null;
	}

	/** FUNCTION {
		"name" : "__toString", 
		"type" : "public",
		"description" : "Método mágico que retorna el nombre del objeto y el de la clase a la que instancia, separados por dos puntos (:).",
		"return" : "string"
	} **/
	final public function __toString() {
		return $this->me.":".$this->class;
	}

	/** FUNCTION {
		"name" : "__unset", 
		"type" : "public",
		"description" : "Método mágico que restaura el valor por defecto de un argumento del objeto",
		"parameters" : { "$sProperty" : ["string", "Nombre del agumento"] },
		"examples" : {
			"reseteo de un argumento" : "unset($ngl->c("foo")->bar);"
		},
		"return" : "string o array"
	} **/
	final public function __unset($sProperty) {
		if(isset($this->aArgumentsSettings[$sProperty])) {
			$this->aArguments[$sProperty] = $this->aArgumentsSettings[$sProperty][1];
		}
	}

	/** FUNCTION {
		"name" : "__arguments__", 
		"type" : "public",
		"description" : "Equivalente a __set. Una vez seteado el argumento retorna el objeto $this",
		"parameters" : { 
			"$mArguments" : ["mixed", "Array asociativo de argumentos (argumento=>valor) o una cadena con el nombre de uno"]
			"$mValue" : ["mixed", "valor del argumento, cuando $aArguments es un string"]
		},
		"return" : "this"
	} **/
	final public function __arguments__($mArguments, $mValue=null) {
		if(\is_string($mArguments)) {
			$this->__set($mArguments, $mValue);
		} else {
			foreach($mArguments as $sArgument => $mValue) {
				$this->__set($sArgument, $mValue);
			}
		}

		return $this;
	}
	
	/** FUNCTION {
		"name" : "args", 
		"type" : "public",
		"description" : "Alias de __arguments__",
		"parameters" : { 
			"$mArguments" : ["mixed", "Array asociativo de argumentos (argumento=>valor), objeto, cadena json o una cadena con el nombre de uno"]
			"$mValue" : ["mixed", "valor del argumento, cuando $aArguments es un string"]
		},
		"return" : "this"
	} **/
	final public function args($mArguments, $mValue=null) {
		if(\is_string($mArguments)) {
			if(!empty($mArguments) && $mArguments[0]=="{") {
				$mArguments = self::call("fn")->isJSON($mArguments, "array");
				if(\is_array($mArguments)) { return $this->__arguments__($mArguments); }

				return $this;
			}
			return $this->__arguments__($mArguments, $mValue);
		} else {
			if(\is_object($mArguments)) { $mArguments = (array)$mArguments; }
			return $this->__arguments__($mArguments);
		}
	}

	/** FUNCTION {
		"name" : "Argument", 
		"type" : "protected",
		"description" : "Retorna el valor de un argumento ó $mDefault en caso de NULL",
		"parameters" : { "$sProperty" : ["string", "Nombre del agumento"] },
		"return" : "mixed"
	} **/
	final protected function Argument($sArgument, $mDefault=null) {
		if($sArgument) {
			if(\array_key_exists($sArgument, $this->aArguments)) {
				return ($this->aArguments[$sArgument]!==null) ? $this->aArguments[$sArgument] : $mDefault;
			}
		}
		return null;
	}

	/** FUNCTION {
		"name" : "Attribute", 
		"type" : "protected",
		"description" : "Setea y/o retorna el valor de un atributo del objeto",
		"parameters" : { 
			"$sAttribute" : ["string", "Nombre del atributo"],
			"$mValue" : ["mixed", "Nuevo valor del atributo"]
		},
		"return" : "mixed"
	} **/
	final protected function Attribute($sAttribute, $mValue=NGL_NULL) {
		if($sAttribute) {
			if(\array_key_exists($sAttribute, $this->aAttributes)) {
				if($mValue!==NGL_NULL) { $this->aAttributes[$sAttribute] = $mValue; }
				return $this->aAttributes[$sAttribute];
			}
		}
		return NGL_NULL;
	}

	/** FUNCTION {
		"name" : "__config__", 
		"type" : "public",
		"description" : "
			Procesa la información contenida en un archivo de configuración y la aplica al objeto.
			Al final retorna un array con la configuración aplicada y la almacena en la variable <b>nglBranch::CONFIG</b>
		",
		"parameters" : { "$sConfigFile" : ["string", "Ruta del archivo de configuración"] },
		"return" : "array"
	} **/
	final public function __config__($sConfigFile=null) {
		if($sConfigFile===null && !\count($this->CONFIG)) { return null; }

		if($sConfigFile!==null) {
			if(!($sConfig = \file_get_contents($sConfigFile))) { return false; }
			$vConfig = self::parseConfigString($sConfig, true);
		} else {
			$vConfig = $this->CONFIG;
		}
		
		if(isset($vConfig["arguments"])) { $this->args($vConfig["arguments"]); }
		if(isset($vConfig["errors"])) { self::errorSetCodes($this->object, $vConfig["errors"]); }
		
		$this->CONFIG = $vConfig;
		return $vConfig;
	}

	final public function __defaults__() {
		$aArgs = $this->__declareArguments__();
		$aDefault = [];
		foreach($aArgs as $sArg => $mValue) {
			$aDefault[$sArg] = (\array_key_exists(1, $mValue)) ? $mValue[1] : null;
		}

		\ksort($aDefault);
		return $aDefault;
	}

	/** FUNCTION {
		"name" : "__destroy", 
		"type" : "public",
		"description" : "Elimina el objeto utilizando el método kill del framework",
		"return" : "boolean"
	} **/
	final public function __destroy__() {
		return self::kill($this->me);
	}

	/** FUNCTION {
		"name" : "GetArguments", 
		"type" : "protected",
		"description" : "
			Captura los valores pasados por <b>$aPassedArgs()</b> utilizando como guía los nombres de argumentos <b>$sArgs</b>
			Durante la captura pueden darse 2 tipos de situaciones:
			<ul>
				<li>el argumento existe en <strong>$aPassedArgs()</strong> y es distinto de la constante <strong>NGL_NULL</strong>, en este caso se utiliza el valor pasado</li>
				<li>el argumento no existe en <strong>$aPassedArgs()</strong> o existe pero es igual de la constante <strong>NGL_NULL</strong>, en este caso se utiliza el valor del argumento <b>$sArgs</b> alamacenado en el objeto</li>
			</ul>
		",
		"parameters" : {
			"$aArgs" : ["string", "Nombres de los argumentos del objeto, separados por comas"],
			"$aPassedArgs" : ["array", "Argumentos pasados al método invocado"]
		},
		"return" : "array"
	} **/
	final protected function GetArguments($sArgs, $aPassedArgs) {
		$aArgs = \explode(",", $sArgs);
		$aArguments = [];
		foreach($aArgs as $nIndex => $sArgument) {
			$sArgument = trim($sArgument);
			$aArguments[] = (\array_key_exists($nIndex, $aPassedArgs) && $aPassedArgs[$nIndex]!==NGL_NULL) ? $aPassedArgs[$nIndex] : $this->Argument($sArgument);
		}

		return $aArguments;
	}
	
	/** FUNCTION {
		"name" : "info", 
		"type" : "public",
		"description" : "Devuelve todos los argumentos y/o atributos del objeto",
		"parameters" : {
			"$sMode" : ["string", "Determina el tipo de salida:
				<ul>
					<li><b>arguments:</b> = sólo se devolverán los argumentos</li>
					<li><b>attributes:</b> = sólo se devolverán los atributos</li>
					<li><b>both:</b> = se devolverán tanto los argumentos como los atributos</li>
				</ul>
			", "both"]
		},
		"return" : "array"
	} **/
	final public function __info__($sMode="both") {
		$sMode = \strtolower($sMode);
		$vInfo = [];

		if($sMode=="arguments" || $sMode=="both") {
			foreach($this->aArguments as $sArgument => $mValue) {
				$vInfo["arguments"][$sArgument] = $mValue;
			}
		}

		if($sMode=="attributes" || $sMode=="both") {
			foreach($this->aAttributes as $sAttribute => $mValue) {
				$vInfo["attributes"][$sAttribute] = $mValue;
			}
		}
		
		if($sMode=="arguments") { return $vInfo["arguments"]; }
		if($sMode=="attributes") { return $vInfo["attributes"]; }
		return $vInfo;
	}

	/** FUNCTION {
		"name" : "isArgument", 
		"type" : "public",
		"description" : "Verifica la existencia del argumento <b>$sArgument</b>",
		"parameters" : { "$sArgument" : ["string", "Nombre del argumento"] },
		"return" : "mixed"
	} **/
	final public function isArgument($sArgument) {
		return (\array_key_exists($sArgument, $this->aArgumentsSettings));
	}

	/** FUNCTION {
		"name" : "isAttribute", 
		"type" : "public",
		"description" : "Verifica la existencia del atributo <b>$sAttribute</b>",
		"parameters" : { "$sAttribute" : ["string", "Nombre del atributo"] },
		"return" : "mixed"
	} **/
	final public function isAttribute($sAttribute) {
		return (\array_key_exists($sAttribute, $this->aAttributes));
	}

	/** FUNCTION {
		"name" : "__reset__", 
		"type" : "public",
		"description" : "Restaura los valores de todos los atributos y argumentos del objeto",
		"return" : "void"
	} **/
	final public function __reset__() {
		if(\method_exists($this, "__declareAttributes__")) { $this->__SetupAttributes__($this->__declareAttributes__()); }
		if(\method_exists($this, "__declareArguments__")) { $this->__SetupArguments__($this->__declareArguments__()); }
		if(\method_exists($this, "__declareVariables__")) { $this->__declareVariables__(); }
		$this->config();
	}

	/** FUNCTION {
		"name" : "__SetupArguments__", 
		"type" : "protected",
		"description" : "Establece e inicializa los argumentos con los valores por defecto",
		"parameters" : { 
			"$aArgumentsSettings" : ["array", "
				Lista de argumentos permitidos y sus valores por defecto.
				Todos los nombres de argumentos serán tratados con <b>strtolower<b>
			"]
		},
		"return" : "void"
	} **/
	final protected function __SetupArguments__($aArgumentsSettings) {
		$aArguments = [];
		foreach($aArgumentsSettings as $sProperty => $mArgument) {
			$sProperty = \strtolower($sProperty);
			if(\is_array($mArgument)) {
				if(isset($mArgument[1])) {
					$aArguments[$sProperty] = $mArgument[1];
				}
			} else {
				if($mAttribute!==null) {
					$aArguments[$sProperty] = $mArgument;
				}
			}
		}

		$this->aArguments = $aArguments;
		$this->aArgumentsSettings = $aArgumentsSettings;
	}

	/** FUNCTION {
		"name" : "__SetupAttributes__", 
		"type" : "protected",
		"description" : "Establece e inicializa los argumentos con los valores por defecto",
		"parameters" : { 
			"$aArgumentsSettings" : ["array", "
				Lista de argumentos permitidos y sus valores por defecto
				Todos los nombres de argumentos serán tratados con <b>strtolower<b>
				Los argumentos en mayúsculas son reservados y comunes a todos los objetos
			"]
		},
		"return" : "void"
	} **/
	final protected function __SetupAttributes__($aAttributesSettings=[]) {
		// atributos
		$aAttributes = [];
		foreach($aAttributesSettings as $sAttribute => $mValue) {
			$aAttributes[\strtolower($sAttribute)] = $mValue;
		}
		$this->aAttributes = $aAttributes;
	}

	/** FUNCTION {
		"name" : "__whoami__", 
		"type" : "public",
		"description" : "
			Retorna los argumentos, atributos y metodos del objeto.
			Este método tambien es llamado por el método whois del framework
		",
		"return" : "array"
	} **/
	final public function __whoami__() {
		$vDescribe = $this->__info__();
		$aMethods = ["__destroy__","__info__", "__me__", "__reset__", "__whoami__"];
		$reflection = new \ReflectionClass(__NAMESPACE__."\\".$this->class);
		$aThisMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
		foreach($aThisMethods as $method) {
			if($method->class==__NAMESPACE__."\\".$this->class && $method->name[0]!="_") {
				$aMethods[] = $method->name;
			}
		}
		\sort($aMethods);
		$vDescribe["methods"] = $aMethods;
		
		return $vDescribe;
	}
}

?>