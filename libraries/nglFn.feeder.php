<?php

namespace nogal;

/** CLASS {
	"name" : "nglFn",
	"object" : "fn",
	"type" : "main",
	"revision" : "20160201",
	"extends" : "nglTrunk",
	"description" : "
		Compendio de métodos utilizados para resolver tareas rutinarias vinculadas a:
		<ul>
			<li>arrays</li>
			<li>cadenas</li>
			<li>comprovacion de tipos de datos</li>
			<li>colores</li>
			<li>fechas</li>
			<li>etc</li>
		</ul>
		
		nglFn construye el objeto $fn dentro del framework, el cual es accedido a través del método <b>$ngl("fn")->NOMBRE_DE_METODO(...)</b>
	",
	"variables" : {
		"$vMimeTypes" : ["private", "MimeTypes obtenidos con nglFn::apacheMimeTypes"]
	}
} **/
class nglFn extends nglTrunk {
	
	protected $class			= "nglFn";
	protected $me				= "fn";
	protected $object			= "fn";
	private $vMimeTypes = null;

	public function __builder__() {
	}

	/** FUNCTION {
		"name" : "apacheMimeTypes", 
		"type" : "public",
		"description" : "
			Retorna los Internet media types indexados por extención proporcionados por el sitio 
			http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
			Los datos pueden ser leídos la base interna o directamente desde le sitio oficial.
			Si la lectura local falla, el método intentará optenerlos desde el sitio oficial y guardarlos localmente.
		",
		"parameters" : { "$bOnlineData" : ["boolean", "True para leer los datos desde le sitio oficial", "false"] },
		"return" : "array"
	} **/
	public function apacheMimeTypes($bGetOnlineData=false) {
		if($this->vMimeTypes!==null) { return $this->vMimeTypes; }

		$vMimeTypes = [];
		if(!$bGetOnlineData) {
			$sConfigFile = $this->sandboxPath(NGL_PATH_DATA.NGL_DIR_SLASH."mime_types.conf");
			$sConfigFile = self::clearPath($sConfigFile);
			if(!$vMimeTypes = self::parseConfigFile($sConfigFile, true)) {
				$vMimeTypes = $this->apacheMimeTypes(true);
			}
		} else {
			$sURL = "http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types";
			if(!$sBuffer = \file_get_contents($sURL)) {
				self::errorMessage("NOGAL", 1016, "URL: ".$sURL, "die");
			} else {
				$aBuffer = \explode("\n", $sBuffer);
				$vMimeTypes = [];
				foreach($aBuffer as $sRow) {
					if(isset($sRow[0]) && $sRow[0]!=="#") {
						\preg_match_all("/([^\s]+)/", $sRow, $aType);
						if(isset($aType[1]) && ($nTypes=\count($aType[1]))>1) {
							for($x=1;$x<$nTypes;$x++) {
								$aGroup = \explode("/", $aType[1][0], 2);
								if(!isset($vMimeTypes[$aGroup[0]])) { $vMimeTypes[$aGroup[0]] = []; }
								$vMimeTypes[$aGroup[0]][$aType[1][$x]] = $aType[1][0];
							}
						}
					}
				}
				
				$sContent = "";
				\ksort($vMimeTypes);
				foreach($vMimeTypes as $sGroup => $aGroup) {
					\ksort($vMimeTypes[$sGroup]);
					$sContent .= "[".$sGroup."]\n";
					foreach($vMimeTypes[$sGroup] as $sType => $sMime) {
						$sContent .= $sType." = ".$sMime."\n";
					}
					$sContent .= "\n";
				}

				if(\is_dir(NGL_PATH_DATA) && \is_writable(NGL_PATH_DATA)) {
					\file_put_contents(NGL_PATH_DATA.NGL_DIR_SLASH."mime_types.conf", $sContent);
				}
			}
		}

		$this->vMimeTypes = $vMimeTypes;
		return $vMimeTypes;
	}

	/** FUNCTION {
		"name" : "arrange",
		"type" : "public",
		"description" : "
			Restituye el orden original de la Cadena o Array <b>$mSource</b> desordenado por <b>nglFn::disarrange</b> según las posiciones de <b>$aArrange</b>.
			Es claro que si una cadena o array es desordena usando <b>nglFn::arrange</b> el mismo podrá ser ordenado por <b>nglFn::disarrange</b>.
			Este método retornará el mismo tipo de dato que el valor de entrada <b>$mSource</b>.
		",
		"parameters" : {
			"$mDisarrange" : ["mixed", "String o Array a ordenar"],
			"$aArrange" : ["array", "Secuencia númerica que se utilizará para ordenar <b>$mSource</b>."]
		},
		"examples": {
			"ordenamiento": "
				#array original
				$input = array("segundo","cuarto","primero","tercero");

				# ordenamiento
				$orderly = $ngl()->arrange($input, array(2,9,7,3));

				#array de salida
				Array (
					[0] => primero
					[1] => segundo
					[2] => tercero
					[3] => cuarto
				)
			"
		},
		"seealso" : ["nglFn::disarrange"],
		"return" : "array"
	} **/
	public function arrange($mSource, $aArrange) {
		if(!\is_array($aArrange)) { self::errorMessage("fn", null, '$aArrange must be of the type array', "die"); }
		if(\is_string($mSource)) {
			$aDisarrange = self::call("unicode")->str_split($mSource);
		} else {
			$aDisarrange = $mSource;
		}

		$sFill 			= self::call()->unique(6);
		$nDisarrange	= \count($aDisarrange);
		$aOrdered		= \array_fill(0, $nDisarrange, $sFill);
		$aCounter		= \array_keys($aOrdered);
		$nArrange 		= \count($aArrange);

		if($nArrange<$nDisarrange) {
			$nMultiplier = \ceil($nDisarrange/$nArrange);
			$aArrange = self::call()->arrayRepeat($aArrange, $nMultiplier);
		}

		$y = -1;
		foreach($aArrange as $nIndex) {
			$nIndex *= 1;
			for($x=1;$x<$nIndex+1;$x++) {
				$nKey = \current($aCounter);
				if(\next($aCounter)===false) { \reset($aCounter); }
			}

			unset($aCounter[$nKey]);
			\reset($aCounter);

			if(isset($aDisarrange[++$y])) { $aOrdered[$nKey] = $aDisarrange[$y]; }
			if(!\count($aCounter)) { break; }
		}

		return (\is_string($mSource)) ? \implode($aOrdered) : $aOrdered;
	}

	/** FUNCTION {
		"name" : "arrayAppend", 
		"type" : "public",
		"description" : "
			Añade los indices de 1 o mas arrays al array principal, sin importar el tipo de dato y sin sobreescribir indices.
			Si los indices son del tipo alfanumericos agrupara los nuevos valores.
		",
		"parameters" : {
			"$array1" : ["array", "Array inicial"],
			"$..." : ["array", "Resto de arrays"]
		},
		"return" : "array"
	} **/
	public function arrayAppend() {
		$aArrays = \func_get_args();
		$aAppened = \array_shift($aArrays);
		$aAutoGroup = [];
		if(\is_array($aArrays) && \count($aArrays)) {
			foreach($aArrays as $aArray) {
				foreach($aArray as $mKey => $mValue) {
					if(\is_int($mKey)) {
						$aAppened[] = $mValue;
					} else {
						if(!\array_key_exists($mKey, $aAppened)) {
							$aAppened[$mKey] = $mValue;
						} else if(!\is_array($aAppened[$mKey])) {
							$aAppened[$mKey] = [$aAppened[$mKey], $mValue];
						} else if(\is_array($aAppened[$mKey]) && \is_array($mValue) && !isset($aAutoGroup[$mKey])) {
							$aAppened[$mKey] = [$aAppened[$mKey], $mValue];
							$aAutoGroup[$mKey] = true;
						} else {
							$aAppened[$mKey][] = $mValue;
						}
					}
				}
			}
		}

		return $aAppened;
	}

	/** FUNCTION {
		"name" : "arrayConcat", 
		"type" : "public",
		"description" : "
			Similar a \array_sum, solo que concatena los valores de los arrays de entrada en lugar de sumarlos algebraicamente
		",
		"parameters" : {
			"$array" : ["array", "Array de arrays de entrada"],
			"$sGule" : ["string", "Cadena con la que se unirán los valores"]
		},
		"return" : "array"
	} **/
	public function arrayConcat($aArrays, $sGlue=" ") {
		$aConcat = \array_shift($aArrays);
		if(\is_array($aArrays) && \count($aArrays)) {
			foreach($aArrays as $aArray) {
				foreach($aArray as $mKey => $mValue) {
					$aConcat[$mKey] = \trim($aConcat[$mKey].$sGlue.$mValue);
				}
			}
		}

		return $aConcat;
	}

	public function arrayArrayCombine($aKeys, $aArrayArray) {
		if(!\is_array($aKeys) || !$this->isArrayArray($aArrayArray)) { return $aArrayArray; }
		if(\count($aKeys)!=\count(\current($aArrayArray))) { return $aArrayArray; }
		foreach($aArrayArray as &$aArray) {
			$aArray = \array_combine($aKeys, $aArray);
		}

		return $aArrayArray;
	}

	/** FUNCTION {
		"name" : "arrayColumn", 
		"type" : "public",
		"description" : "
			Devuelve los valores de una sola columna de $aSource, identificado por la clave de columna $mColumnKey
			Opcionalmente, se puede proporcionar una clave de índice, $mIndexKey, para indexar los valores del array devuelto 
		",
		"parameters" : {
			"$aSource" : ["array", "array de datos"],
			"$mColumnKey" : ["mixed", "La columna de valores a devolver"]
			"$mIndexKey" : ["mixed", "La columna a usar como los índice", "null"]
		},
		"return" : "array"
	} **/
	public function arrayColumn($aSource, $mColumnKey, $mIndexKey=null) {
		if(!$this->isArrayArray($aSource)) { $aSource = array($aSource); }
		$aReturn = \array_map(function($aInput) use ($mColumnKey, $mIndexKey) {
			if(!\is_array($mColumnKey)) {
				if(!isset($aInput[$mColumnKey])) { return null; }
				$aSlice = $aInput[$mColumnKey];
			} else {
				$aSlice = [];
				foreach($mColumnKey as $sKey) {
					if(\array_key_exists($sKey, $aInput)) {
						$aSlice[$sKey] = $aInput[$sKey];
					}
				}
			}
			if($mIndexKey!==null) { return [$aInput[$mIndexKey] => $aSlice]; }

			return $aSlice;
		}, $aSource);

		if($mIndexKey!==null) {
			$aTmp = [];
			foreach($aReturn as $aValue) {
				$aTmp[\key($aValue)] = \current($aValue);
			}
			$aReturn = $aTmp;
		}
		return $aReturn;
	}
	
	/** FUNCTION {
		"name" : "arrayFlatIndex", 
		"type" : "public",
		"description" : "Retorna el subindice de un array desde una cadena separada por un caracter",
		"parameters" : {
			"$aSource" : ["array", "array de datos"],
			"$sFlatIndex" : ["string", "cadena de indices separadas por $sSeparator"],
			"$bStrict" : ["boolean", "cuando es true retorna un array solo cuando todos los subindices estan presentes", "false"]
			"$sSeparator" : ["string", "separador de subindices", "."]

			$a["users"][] = array("name"=>"bart", "age"=>10)
			$a["users"][] = array("name"=>"lisa", "age"=>8)

			$sFlatIndex = users.0.name (bart) ó users.1.age (8)

		},
		"return" : "array or false"
	} **/
	public function arrayFlatIndex($aSource, $sFlatIndex=null, $bStrict=false, $sSeparator=".") {
		if($sFlatIndex===null) { return $aSource; }
		$aIndexes = (\strpos($sFlatIndex, $sSeparator)===false) ? [$sFlatIndex] : \explode($sSeparator, $sFlatIndex);
		if(\count($aIndexes)) {
			$nFound = 0;
			foreach($aIndexes as $sIndex) {
				if(isset($aSource[$sIndex])) {
					$nFound++;
					$aSource = $aSource[$sIndex];
				} else {
					if($bStrict) { return false; }
					break;
				}
			}
		}

		return ($nFound) ? $aSource : false;
	}

	public function arrayKeysR($aArray) {
		$aKeys = [];
		foreach($aArray as $mKey => $aSubArray) {
			if(\is_array($aSubArray)) {
				if(self::call()->isArrayArray($aSubArray)) { $aSubArray = \current($aSubArray); }
				$aKeys[$mKey] = self::call()->arrayKeysR($aSubArray);
			} else {
				$aKeys[$mKey] = $mKey;
			}
		}
		return $aKeys;
	}

	/** FUNCTION {
		"name" : "arrayGoto", 
		"type" : "public",
		"description" : "Avanza el puntero del array hasta el índice indicado por $mKey y retorna los datos",
		"parameters" : {
			"$aSource" : ["array", "array de datos"],
			"$mKey" : ["mixed", "Indice hasta donde se avanzará el puntero", "0"]
		},
		"return" : "array"
	} **/
	public function arrayGoto(&$aSource, $mKey=0) {
		reset($aSource);
		while($aCurrent = \each($aSource)) {
			if($aCurrent[0]===$mKey) { return $aCurrent; }
		}
		
		return $aSource;
	}

	/** FUNCTION {
		"name" : "arrayGroup", 
		"type" : "public",
		"description" : "
			Agrupa un array bidimensional.
			Cuando la variable $mStructure sea NULL, los valores únicos de cada columna se agruparán en subarrays.
			Cuando la variable $mStructure sea distinto de NULL, los valores se agruparán según los grupos definidos en ella.
		",
		"parameters" : {
			"$aSource" : ["array", "array de datos"],
			"$aStructure" : ["mixed", "
				Campo principal de agrupamiento o Array con la estructura de sub-agrupamientos.
				En la estructura se determinan cuales serán los diferentes grupos y los indices estarán presentes en cada uno.
				
				Estrucutra:
					array(
						"MAIN" => array(
							"campo_principal_de_agrupamiento",
							array("campo1","campo2","campo3")
						),
						"subgrupo1" => array(
							"campo_de_agrupamiento",
							array("campo4", "campo5", "campo6")
						),
						"subgrupo2" => array(
							"campo_de_agrupamiento", 
							array("campo9", "campo10", array(
								"subgrupo2.1" => array(
									"campo_de_agrupamiento",
									array("campo11","campo11")
								)
							))
						)
					);
				
				La directiva MAIN debe estar expresada en mayusculas.
				Si es necesario determinar una estructura de sub-grupos, pero no redefinir el grupo principal, MAIN deberá ser un array
				que sólo contenga el campo_principal_de_agrupamiento
			", NULL],
		},
		"examples" : {
			"agrupamiento simple" : "
				# origen de datos
				$aSource = array(
					array("id"=>1,"date"=>"2015-11-23","name"=>"Castro Hnos SRL","cuit"=>"30-36251478-9","product"=>1,"quantity"=>15,"price"=>20),
					array("id"=>1,"date"=>"2015-11-23","name"=>"Castro Hnos SRL","cuit"=>"30-36251478-9","product"=>2,"quantity"=>10,"price"=>16),
					array("id"=>1,"date"=>"2015-11-23","name"=>"Castro Hnos SRL","cuit"=>"30-36251478-9","product"=>3,"quantity"=>20,"price"=>20),
					array("id"=>2,"date"=>"2015-11-24","name"=>"Ravelli S.A.","cuit"=>"33-58796321-8","product"=>2,"quantity"=>13,"price"=>16),
					array("id"=>2,"date"=>"2015-11-24","name"=>"Ravelli S.A.","cuit"=>"33-58796321-8","product"=>3,"quantity"=>8,"price"=>20)
				);
				
				# ejecución
				$ngl()->arrayGroup($aSource);
				
				# resultado del agrupamiento
				array(
					"1" => array(
						"id" => 1,
						"date" => "2015-11-23",
						"name" => "Castro Hnos SRL",
						"cuit" => "30-36251478-9",
						"product" => array(1, 2, 3), # <-- agrupado
						"quantity" => array(15, 10, 20), # <-- agrupado
						"price" => array(20, 16) # <-- agrupado
					),
					"2" => array (
						"id" => 2
						"date" => "2015-11-24",
						"name" => "Ravelli S.A.",
						"cuit" => "33-58796321-8",
						"product" => array(2, 3), # <-- agrupado
						"quantity" => array(13, 8), # <-- agrupado
						"price" => array(16, 20) # <-- agrupado
					)
				)
			",
			
			"sub-agrupamientos" : "
				# origen de datos
				$aSource = array(
					array("id"=>1,"date"=>"2015-11-23","name"=>"Castro Hnos SRL","cuit"=>"30-36251478-9","product"=>1,"quantity"=>15,"price"=>20),
					array("id"=>1,"date"=>"2015-11-23","name"=>"Castro Hnos SRL","cuit"=>"30-36251478-9","product"=>2,"quantity"=>10,"price"=>16),
					array("id"=>1,"date"=>"2015-11-23","name"=>"Castro Hnos SRL","cuit"=>"30-36251478-9","product"=>3,"quantity"=>20,"price"=>20),
					array("id"=>2,"date"=>"2015-11-24","name"=>"Ravelli S.A.","cuit"=>"33-58796321-8","product"=>2,"quantity"=>13,"price"=>16),
					array("id"=>2,"date"=>"2015-11-24","name"=>"Ravelli S.A.","cuit"=>"33-58796321-8","product"=>3,"quantity"=>8,"price"=>20)
				);
				
				# ejecución
				$aStructure = array(
					"MAIN" => array("id"),
					"details" => array("product", array("product", "quantity", "price"))
				);
				$ngl()->arrayGroup($aSource, $aStructure);
				
				# resultado del agrupamiento
				array(
					"1" => array(
						"id" => 1,
						"date" => "2015-11-23",
						"name" => "Castro Hnos SRL",
						"cuit" => "30-36251478-9",
						"product" => 1,
						"quantity" => 15,
						"price" => 20,
						"details" => array(
							"1" => array("product" => 1, "quantity" => 15, "price" => 20),
							"2" => array("product" => 2, "quantity" => 10, "price" => 16),
							"3" => array("product" => 3, "quantity" => 20, "price" => 20)
						)
					),
					"2" => array (
						"id" => 2,
						"date" => "2015-11-24",
						"name" => "Ravelli S.A.",
						"cuit" => "33-58796321-8",
						"product" => 2,
						"quantity" => 13,
						"price" => 16,
						"details" => array(
							"2" => array("product" => 2, "quantity" => 13, "price" => 16),
							"3" => array("product" => 3, "quantity" => 8, "price" => 20)
						)
					)
				)
			",
			
			"grupo principal" : "
				# origen de datos
				$aSource = array(
					array("id"=>1,"date"=>"2015-11-23","name"=>"Castro Hnos SRL","cuit"=>"30-36251478-9","product"=>1,"quantity"=>15,"price"=>20),
					array("id"=>1,"date"=>"2015-11-23","name"=>"Castro Hnos SRL","cuit"=>"30-36251478-9","product"=>2,"quantity"=>10,"price"=>16),
					array("id"=>1,"date"=>"2015-11-23","name"=>"Castro Hnos SRL","cuit"=>"30-36251478-9","product"=>3,"quantity"=>20,"price"=>20),
					array("id"=>2,"date"=>"2015-11-24","name"=>"Ravelli S.A.","cuit"=>"33-58796321-8","product"=>2,"quantity"=>13,"price"=>16),
					array("id"=>2,"date"=>"2015-11-24","name"=>"Ravelli S.A.","cuit"=>"33-58796321-8","product"=>3,"quantity"=>8,"price"=>20)
				);
				
				# ejecución
				$aStructure = array(
					"MAIN" => array("id", array("name", "cuit", "date")),
					"customers" => array("id", array("name", "cuit")),
					"details" => array("product", array("product", "quantity", "price"))
				);
				$ngl()->arrayGroup($aSource, $aStructure);
				
				# resultado del agrupamiento
				array(
					"1" => array(
						"name" => "Castro Hnos SRL",
						"cuit" => "30-36251478-9",
						"date" => "2015-11-23",
						"details" => array(
							"1" => array("product" => 1, "quantity" => 15, "price" => 20),
							"2" => array("product" => 2, "quantity" => 10, "price" => 16),
							"3" => array("product" => 3, "quantity" => 20, "price" => 20)
						)
					),
					"2" => array (
						"name" => "Ravelli S.A.",
						"cuit" => "33-58796321-8",
						"date" => "2015-11-24",
						"details" => array(
							"2" => array("product" => 2, "quantity" => 13, "price" => 16),
							"3" => array("product" => 3, "quantity" => 8, "price" => 20)
						)
					)
				)
			"
		},
		"return" : "array"
	} **/
	public function arrayGroup($aSource, $mStructure=null) {
		$mGroupBy = "id";
		if(\is_array($mStructure)) {
			if(isset($mStructure["MAIN"])) {
				$mGroupBy = $mStructure["MAIN"][0];
				$aMain = (isset($mStructure["MAIN"][1])) ? $mStructure["MAIN"][1] : null;
				unset($mStructure["MAIN"]);
			}
			if(\count($mStructure)) { $aStructure = $mStructure; }
		}

		$aGrouped = [];
		foreach($aSource as $mValue) {
			if(!isset($mValue[$mGroupBy])) { $mGroupBy = \key($mValue); }
			$mKey = $mValue[$mGroupBy];
			if(!isset($aGrouped[$mKey])) {
				$aGrouped[$mKey] = $mValue;
				if(isset($aMain)) {
					if($aMain===null) {
						$aMainValues = $mValue;
					} else {
						$aMainValues = [];
						foreach($aMain as $sIndex) {
							if(\array_key_exists($sIndex, $mValue)) { $aMainValues[$sIndex] = $mValue[$sIndex]; }
						}
					}
					$aGrouped[$mKey] = $aMainValues;
				}
			}

			if(\is_array($mValue)) {
				if(!isset($aStructure)) {
					// agrupa todos todos los valores diferentes de cada campo en un subarray
					foreach($mValue as $mIndex => $mData) {
						if(isset($aMain) && !\array_key_exists($mIndex, $aMain)) { continue; }
						if(!isset($aGrouped[$mKey][$mIndex])) {
							// primera aparicion
							$aGrouped[$mKey][$mIndex] = $mData;
						} else if(!\is_array($aGrouped[$mKey][$mIndex])) {
							//sengunda
							$mTmp = $aGrouped[$mKey][$mIndex];
							if($mTmp==$mData) { continue; }
							$aGrouped[$mKey][$mIndex] = [$mTmp, $mData];
						} else {
							// demas
							if(\in_array($mData, $aGrouped[$mKey][$mIndex])) { continue; }
							$aGrouped[$mKey][$mIndex][] = $mData;
						}
					}
				} else {
					// agrupa los valores de acuerdo a la escructura 
					if(!$this->ArrayGrouper($aGrouped[$mKey], $mValue, $aStructure)) {
						$aGrouped[$mKey][key($aStructure)] = null;
					}
				}
			}
		}

		return $aGrouped;
	}

	/** FUNCTION {
		"name" : "ArrayGrouper",
		"type" : "private",
		"description" : "Método axuliar de nglFn::arrayGroup",
		"parameters" : { 
			"$aGrouped" : ["string", "Patron de búsqueda"],
			"$mValue" : ["array", "Origen de datos"],
			"$aStructure" : ["boolean", "Habilita la búsqueda por expresiones regulares, donde $sNeedle es tratado como un patron regex", "false"]
		},
		"seealso" : ["nglFn::arrayGroup"], 
		"return" : "boolean"
	} **/
	private function ArrayGrouper(&$aGrouped, $mValue, $aStructure) {
		if(!\is_array($aGrouped)) { return false; }
		foreach($aStructure as $sSubGroup => $aParams) {
			if(!\array_key_exists($sSubGroup, $aGrouped)) {
				$aGrouped[$sSubGroup] = [];
			}

			$bEmptySubGroup = true;
			$nIdex = $mValue[$aParams[0]];

			if($nIdex!==null) {
				foreach($aParams[1] as $mIndex) {
					if(\is_array($mIndex)) {
						$mSubIndex = \key($mIndex);
						if(!$this->ArrayGrouper($aGrouped[$sSubGroup][$nIdex], $mValue, $mIndex)) {
							$aGrouped[$sSubGroup][$nIdex][$mSubIndex] = null;
						}
					} else {
						if(\is_array($mValue) && \array_key_exists($mIndex, $mValue) && $mValue[$mIndex]!==null) {
							$bEmptySubGroup = false;
							$aGrouped[$sSubGroup][$nIdex][$mIndex] = $mValue[$mIndex];
						} else {
							$aGrouped[$sSubGroup][$nIdex][$mIndex] = null;
						}
					}
				}
			}

			if($bEmptySubGroup===true) {
				unset($aGrouped[$sSubGroup][$nIdex]);
			}
		}
		
		return true;
	}

	/** FUNCTION {
		"name" : "arrayIn",
		"type" : "public",
		"description" : "
			Comprueba si un valor se encuentra en un array utilizando in_array o 
			chequeando valor por valor utilizando expresiones regulares. en este último caso 
			los patrones de búsqueda serán tratados con \preg_quote()
		",
		"parameters" : { 
			"$sNeedle" : ["string", "Patron de búsqueda"],
			"$aHayStack" : ["array", "Origen de datos"],
			"$bRegex" : ["boolean", "Habilita la búsqueda por expresiones regulares, donde $sNeedle es tratado como un patron regex", "false"],
			"$sRegexFlags" : ["string", "Flags utilizados en el patron de expresiones regulares", "s"],
			"$bInverseMode" : ["boolean", "Activa el modo inverso, donde cada valor del array es tratado como un patron regex y comparadon contra $sNeedle", "false"]
		},
		"seealso" : ["nglFn::tokenEncode"], 
		"return" : "boolean"
	} **/
	public function arrayIn($sNeedle, $aHayStack, $bRegex=false, $sRegexFlags="s", $bInverseMode=false) {
		if(!$bRegex) { return \in_array($sNeedle, $aHayStack); }

		if($bInverseMode) {
			foreach($aHayStack as $mValue) {
				$mValue = \preg_quote($mValue);
				if(\preg_match("/".$mValue."/".$sRegexFlags, $sNeedle)) {
					return true;
				}
			}
		} else {
			$sNeedle = \preg_quote($sNeedle);
			foreach($aHayStack as $mValue) {
				if(\preg_match("/".$sNeedle."/".$sRegexFlags, $mValue)) {
					return true;
				}
			}
		}

		return false;
	}

	/** FUNCTION {
		"name" : "arrayInsert",
		"type" : "public",
		"description" : "Añade un elemento al Array en la posición determinada",
		"parameters" : { 
			"$aArray" : ["array", "Origen de datos"],
			"$mPosition" : ["mixed", "Posición alfanúmerica de referencia en la que se insertará el nuevo valor"],
			"$aInsert" : ["mixed", "Valor a insertar"],
			"$bAfter" : ["boolean", "Determina si el nuevo valor se insertará antes o después del valor de referencia.", "true"]
		},
		"return" : "array"
	} **/
	public function arrayInsert(&$aArray, $mPosition, $aInsert, $bAfter=true) {
		$nDisplacement = ($bAfter) ? 1 : 0;
		if(\is_int($mPosition)) {
			\array_splice($aArray, ($mPosition+$nDisplacement), 0, $aInsert);
		} else {
			$nPosition = (\array_search($mPosition, \array_keys($aArray))+$nDisplacement);
			$aArray = \array_merge(
				\array_slice($aArray, 0, $nPosition),
				$aInsert,
				\array_slice($aArray, $nPosition)
			);
		}
	}

	/** FUNCTION {
		"name" : "arrayMerge",
		"type" : "public",
		"description" : "Agrega N arrays multi-dimensionales en uno",
		"parameters" : {
			"$array1" : ["array", "Array inicial"],
			"$..." : ["array", "Resto de arrays"]
		},
		"return" : "array"
	} **/
	public function arrayMerge() {
		$aArrays = \func_get_args();
		$aMerge = \array_shift($aArrays);

		foreach($aArrays as $aArray) {
			\reset($aMerge);
			while(list($mKey, $mValue) = \each($aArray)) {
				if(isset($aMerge[$mKey]) && \is_array($mValue) && \is_array($aMerge[$mKey])) {
					if(!\is_string($mKey)) {
						$aMerge[] = $this->arrayMerge($aMerge[$mKey], $mValue);
					} else {
						$aMerge[$mKey] = $this->arrayMerge($aMerge[$mKey], $mValue);
					}
				} else {
					if(!\is_string($mKey)) {
						$aMerge[] = $mValue;
					} else {
						$aMerge[$mKey] = $mValue;
					}
				}
			}
		}

		return $aMerge;
	}
	
	/** FUNCTION {
		"name" : "arrayMultiSort",
		"type" : "public",
		"description" : "Ordena un array multi-dimensional considerando multiples indices, orden y tipos de orden",
		"parameters" : {
			"$aData" : ["array", "origen de datos"],
			"$aFlags" : ["array", "
				Array de arrays con las configuraciones de los ordenes.
				formato: <b>array( array( field, [order], [type] ), ..., array( field, [order], [type] ) );</b>

				donde:
					<ul>
						<li><b>field:</b> es el indice por el cual se ordenará</li>
						<li><b>order:</b> dirección del ordenamiento:
							<ul>
								<li>asc: orden ascendente (valor predeterminado)</li>
								<li>desc: orden descendente</li>
							</ul>
						<li><b>type:</b> tipo de ordenamiento:
							<ul>
								<li>0: orden natural sencible a mayúsculas (valor predeterminado)</li>
								<li>1: orden natural insencible a mayúsculas</li>
								<li>2: numerico</li>
								<li>3: orden por cadena sencible a mayúsculas</li>
								<li>4: orden por cadena insencible a mayúsculas</li>
							</ul>
						</li>
					</ul>
			"]
		},
		"examples" : {
			"$aFlags de un ordenamiento por 2 columnas" : "
				$aFruits = array(
				→array("name"=>"lemon", "color"=>"yellow"),
				→array("name"=>"orange", "color"=>"orange"),
				→array("name"=>"apple", "color"=>"red")
				);
				
				arrayMultiSort($aFruits, array(
				→array("field"=>"name", "order"=>"desc", "type"=>2),
				→array("field"=>"color", "type"=>3)
				));
			"
		},
		"return" : "array"
	} **/
	public function arrayMultiSort($aData, $aFlags) {
		foreach($aFlags as $vArgs) {
			$mKey = $vArgs["field"];

			$bOrder = true;
			if(isset($vArgs["order"])) { $bOrder = (\strtolower($vArgs["order"])=="asc") ? true : false; }
			
			$nType = 0;
			if(isset($vArgs["type"])) { $nType = (int)$vArgs["type"]; }

			switch($nType) {
				case 1: /* Case insensitive natural. */
					\uasort($aData, function($a, $b) use ($mKey, $bOrder) { $nVal = \strcasecmp($a[$mKey], $b[$mKey]); return ($bOrder ? $nVal : ($nVal*-1)); });
					break;

				case 2: /* Numeric. */
					\uasort($aData, function($a, $b) use ($mKey, $bOrder) { $nVal = ($a[$mKey] == $b[$mKey]) ? 0 : (($a[$mKey] < $b[$mKey]) ? -1 : 1); return ($bOrder ? $nVal : ($nVal*-1)); });
					break;

				case 3: /* Case sensitive string. */
					\uasort($aData, function($a, $b) use ($mKey, $bOrder) { $nVal = \strcmp($a[$mKey], $b[$mKey]); return ($bOrder ? $nVal : ($nVal*-1)); });
					break;

				case 4: /* Case insensitive string. */
					\uasort($aData, function($a, $b) use ($mKey, $bOrder) { $nVal = \strcasecmp($a[$mKey], $b[$mKey]); return ($bOrder ? $nVal : ($nVal*-1)); });
					break;

				default: /* Case sensitive natural. */
					\uasort($aData, function($a, $b) use ($mKey, $bOrder) { $nVal = \strnatcmp($a[$mKey], $b[$mKey]); return ($bOrder ? $nVal : ($nVal*-1)); });
					break;
			}
		}

		return $aData;
	}

	/** FUNCTION {
		"name" : "arrayRebuilder", 
		"type" : "public",
		"description" : "
			Agrupa los índices <b>$aIndexes</b> combinandolos por sus claves. Con la opción de reenombrar estas últimas.
			Si $aIndexes = null y $mNewIndexes es una cadena, el método retornará un array bidimensional donde cada subarray tendrá como 
			único indice a $mNewIndexes y cuyo valor será el valor del indice actual de $aSource.
		",
		"parameters" : {
			"$aSource" : ["array", "Array de datos"],
			"$mIndexes" : ["mixed", "Lista de indices a combinar o NULL"],
			"$mNewIndexes" : ["mixed", "Lista con los nombres de los nuevos indices, que se reemplazarán uno a uno con <b>$aIndexes</b> o una cadena", "$aIndexes"]
		},
		"examples": {
			"Ejemplo" : "
				#array original
				$input = array(
					"entity" => "FooBar Inc.",
					"start_date" => "2015-11-23",
					"contact_surname" => array(
						"Smith",
						"Stewart",
						"Astley"
					),
					"contact_firstname" => array(
						"John",
						"Sara",
						"Ralph"
					),
				);

				# llamada
				$output = $ngl()->arrayRebuilder($input, array("contact_surname", "contact_firstname"));
				
				#array de salida
				Array (
					[0] => Array(
						[contact_surname] = Smith
						[contact_firstname] = John
					),
					[1] => Array(
						[contact_surname] = Smith
						[contact_firstname] = Astley
					),
					[2] => Array(
						[contact_surname] = Smith
						[contact_firstname] = Ralph
					)
				)
			",
			"Ejemplo anterior renombrando claves" : "
				# llamada
				$output = $ngl()->arrayRebuilder($input, array("contact_surname", "contact_firstname"), array("surname", "firstname"));
				
				#array de salida
				Array (
					[0] => Array(
						[surname] = Smith
						[firstname] = John
					),
					[1] => Array(
						[surname] = Smith
						[firstname] = Astley
					),
					[2] => Array(
						[surname] = Smith
						[firstname] = Ralph
					)
				)
			",
			"Ejemplo con $mNewIndexes como cadena" : "
				#array original
				$input = array(
					"0" => array("firstname"=>"John", "age"=>23),
					"1" => array("firstname"=>"Sara", "age"=>24),
					"2" => array("firstname"=>"Ralph", "age"=>25)
				);

				# llamada
				$output = $ngl()->arrayRebuilder($input, null, array("contact"));
				
				#array de salida
				Array (
					[0] => Array(
						[contact] => Array(
							[firstname] => John
							[age] => 23
						)
					),
					[1] => Array(
						[contact] => Array(
							[firstname] => Sara
							[age] => 24
						)
					),
					[2] => Array(
						[contact] => Array(
							[firstname] => Ralph
							[age] => 25
						)
					)
				)
			"
		},
		"return" : "array"
	} **/
	public function arrayRebuilder($aSource, $mIndexes, $mNewIndexes=null) {
		if($mNewIndexes===null || (\is_array($mIndexes) && \count($mIndexes)!=\count($mNewIndexes))) { $mNewIndexes = $mIndexes; }
		if(\is_array($mIndexes)) {
			$aKeys = [];
			foreach($mIndexes as $mIndex) {
				if(isset($aSource[$mIndex]) && \is_array($aSource[$mIndex])) { $aKeys = $aKeys + \array_keys($aSource[$mIndex]); }
			}
			if($aKeys===null) { return null; }
			
			$aNewData = [];
			foreach($aKeys as $mKey) {
				foreach($mIndexes as $nKey => $mIndex) {
					if(\array_key_exists($mIndex, $aSource) && \array_key_exists($mKey, $aSource[$mIndex])) {
						$mValue = (isset($aSource[$mIndex], $aSource[$mIndex][$mKey])) ? $aSource[$mIndex][$mKey] : null; 
						$aNewData[$mKey][$mNewIndexes[$nKey]] = $mValue;
					}
				}
			}
		} else if(\is_null($mIndexes) && \is_string($mNewIndexes)) {
			$aNewData = [];
			foreach($aSource as $mValue) {
				$aNewData[] = [$mNewIndexes => $mValue];
			}
		} else {
			$aNewData = $aSource;
		}

		return $aNewData;
	}

	// en un array multidimensional, cambia el valor del indice primario alguno de los valores
	public function arrayReIndex($aArrayArray, $sNewKey, $sOldKey="__old_key__") {
		if(!$this->isArrayArray($aArrayArray)) { return $aArrayArray; }
		$aReIndex = [];
		foreach($aArrayArray as $sKey => $aArray) {
			$aArray[$sOldKey] = $sKey;
			$sKey = (isset($aArray[$sNewKey])) ? $aArray[$sNewKey] : $sKey;
			$aReIndex[$sKey] = $aArray;
		}
	
		return $aReIndex;
	}

	/** FUNCTION {
		"name" : "arrayRepeat",
		"type" : "public",
		"description" : "Retorna un array con indices númericos que contiene <b>$nMultiplier</b> repeticiones del array <b>$aInput</b>.",
		"parameters" : {
			"$aInput" : ["array", "Array a ser repetido"],
			"$nMultiplier" : ["int", "Número de veces que <b>$aInput</b> debe ser repetido."]
		},
		"examples": {
			"array númerico" : "
				#array original
				$input = array("A", "B", "C");

				# repetición
				$output = $ngl()->arrayRepeat($input, 3);

				#array de salida
				Array (
					[0] => A
					[1] => B
					[2] => C
					[3] => A
					[4] => B
					[5] => C
					[6] => A
					[7] => B
					[8] => C
				)
			", 
			"array asociativo" : "
				#array original
				$input = array("A"=>"ANANA", "B"=>"BANANA", "C"=>"CIRUELA");

				# repetición
				$output = $ngl()->arrayRepeat($input, 2);

				#array de salida
				Array (
					[0] => ANANA
					[1] => BANANA
					[2] => CIRUELA
					[3] => ANANA
					[4] => BANANA
					[5] => CIRUELA
				)
			"
		},
		"return" : "array"
	} **/
	public function arrayRepeat($aInput, $nMultiplier) {
		$aInput = \array_values($aInput);
		$aOutput = [];
		for($x=0; $x<$nMultiplier ;$x++) {
			$aOutput = \array_merge($aOutput, $aInput);
		}
		
		return $aOutput;
	}

	/*
	aplica el metodo de orden, recursivamente
		sort
		rsort
		\ksort
		krsort
	*/
	public function mSort(&$aArray, $sMethod="sort", $nFlags=SORT_REGULAR) {
		if(!\in_array(\strtolower($sMethod), ["sort","ksort","rsort","krsort"])) { $sMethod = "sort"; }
		foreach($aArray as &$mValue) {
			if(\is_array($mValue)) { $this->mSort($mValue, $sMethod, $nFlags); }
		}
		return $sMethod($aArray);
	}

	/**
	 * convierte un array list en un arbol
		$arr = array(
			array('id'=>100, 'pid'=>0, 'name'=>'a'),
			array('id'=>101, 'pid'=>100, 'name'=>'a'),
			array('id'=>102, 'pid'=>101, 'name'=>'a'),
			array('id'=>103, 'pid'=>101, 'name'=>'a'),
		);
	 */
	public function listToTree($aData, $sParent="pid", $sId="id", $sChildren="_children") {
		if(!\is_array($aData) || !\is_array(\current($aData))) { return []; }
		$aPrepare = [];
		foreach($aData as $aRow){
			$mParent = (empty($aRow[$sParent])) ? 0 : $aRow[$sParent];
			$aRow[$sParent] = $mParent;
			$aPrepare[$mParent][] = $aRow;
		}
		\ksort($aPrepare);
		return $this->listToTreeCreator($aPrepare, \current($aPrepare), $sId, $sChildren);
	}

	private function listToTreeCreator(&$aList, $aParent, $sId, $sChildren) {
		$aTree = [];
		foreach($aParent as $k=>$v){
			if(isset($aList[$v[$sId]])){
				$v[$sChildren] = $this->listToTreeCreator($aList, $aList[$v[$sId]], $sId, $sChildren);
			}
			$aTree[] = $v;
		} 
		return $aTree;
	}

	/** FUNCTION {
		"name" : "base64Cleaner",
		"type" : "public",
		"description" : "Elimina de una cadena todos los caracteres que no sean válidos en una cadena base64 [a-zA-Z0-9+/=]",
		"parameters" : { "$sString" : ["string", "Cadena a limpiar"] },
		"return" : "string"
	} **/
	public function base64Cleaner($sString) {
		return \preg_replace("/[^a-zA-Z0-9\+\/\=]/", "", $sString);
	}

	/** FUNCTION {
		"name" : "between",
		"type" : "public",
		"description" : "
			Verifica si un valor en relación a un rango de valores.
			between tambien puede ser utilizado para conocer si un valor es mayor o menor a otro, ya que si no se especifica un valor para $sMaxValue distinto de null, se asume que $sMaxValue es igual a $sMinValue.
			
			Los posibles valores retornados por between son:
			<ul>
				<li><b>0:</b> cuando $mValue es menor a $sMinValue</li>
				<li><b>1:</b> cuando $mValue esta dentro del rango de valores</li>
				<li><b>2:</b> cuando $mValue es mayor a $sMaxValue</li>
			</ul>
		",
		"parameters" : {
			"$mValue" : ["string", "Valor a chequear"],
			"$sMinValue" : ["string", "Mínimo valor del rango"],
			"$sMaxValue" : ["string", "Máximo valor del rango", "null"],
			"$bCaseInsensitive" : ["boolean", "Modo insensible a mayúsculas", "false"]
		},
		"return" : "integer"
	} **/
	public function between($mValue, $sMinValue, $sMaxValue=null, $bCaseInsensitive=false) {
		$aBetween	= [];
		$aBetween[]	= $this->unaccented($sMinValue);
		$aBetween[]	= ($sMaxValue!==null) ? $this->unaccented($sMaxValue) : $aBetween[0];
		$aBetween[] = $mValue = $this->unaccented($mValue);
		
		if($bCaseInsensitive) {
			foreach($aBetween as $nKey => $sValue) {
				$aBetween[$nKey] = \strtolower($sValue);
			}
		}
		
		\sort($aBetween, SORT_NATURAL);
		
		if($aBetween[0]==$mValue) {
			return 0;
		} else if($aBetween[1]==$mValue) {
			return 1;
		} else {
			return 2;
		}
	}

	/** FUNCTION {
		"name" : "clearPath",
		"type" : "public",
		"description" : "Elimina los slashes de mas en un path o url. Todos los <b>$sSeparator</b> de cierre serán eliminados",
		"parameters" : {
			"$sPath" : ["string", "Path a limpiar"],
			"$bSlashClose" : ["boolean", "Cuando el valor es true añade un slash al final del path", "false"],
			"$sSeparator" : ["string", "Slash utilizado", "NGL_DIR_SLASH"],
			"$bRealPath" : ["boolean", "Cuando es TRUE aplica realpath() a la path", "false"]
		},
		"return" : "string"
	} **/
	public function clearPath($sPath, $bSlashClose=false, $sSeparator=NGL_DIR_SLASH, $bRealPath=false) {
		$sScheme = self::call()->isURL($sPath, true);
		$sSlash = ($sScheme!==false) ? "/" : $sSeparator;

		$sHash = $this->unique();
		if($sScheme==="url") { $sPath = ":".$sPath; }
		$sPath = \str_replace("://", $sHash, $sPath);
		$sPath = \str_replace("\/", "/", $sPath);
		$sPath = \preg_replace("/[\\\\\/]{1,}/", $sSlash, $sPath);
		if(\strpos($sPath, $sHash)===false && $sSlash=="\x5C") { $sPath = \str_replace("\x5C", "\x5C\x5C", $sPath); }

		$sPath = \rtrim($sPath, $sSlash);

		$sPath = \str_replace($sHash, "://", $sPath);
		if($sScheme==="url") { $sPath = \substr($sPath, 1); }
		
		if($bRealPath) {
			$sPath = \realpath($sPath);
			if(!$sScheme && NGL_WINDOWS) {
				$sPath = \str_replace("\\", "\\\\", $sPath);
			}
		}
		if($bSlashClose) {	$sPath .= $sSlash; }
		return $sPath;
	}

	/** FUNCTION {
		"name" : "sandboxPath",
		"type" : "public",
		"description" : "Encapsula un path dentro del Sandbox",
		"parameters" : {
			"$sPath" : ["string", "Path a encapsular"]
		},
		"return" : "string"
	} **/
	public function sandboxPath($sFilePath) {
		$sSandboxPath = (\defined("NGL_SANDBOX")) ?  \realpath(NGL_SANDBOX) : \realpath(NGL_PATH_TMP);

		if(\substr($sFilePath, 0, \strlen($sSandboxPath)) != $sSandboxPath) {
			$sFilePath = $sSandboxPath.NGL_DIR_SLASH.$sFilePath;
		}

		$sSafePath = self::call("files")->absPath($sFilePath);

		if(\substr($sSafePath, 0, \strlen($sSandboxPath)) != $sSandboxPath) {
			$sSafePath = $sSandboxPath.NGL_DIR_SLASH.self::call("files")->absPath($sFilePath);
		}

		return self::call()->clearPath($sSafePath);
	}


	/** FUNCTION {
		"name" : "colorHex",
		"type" : "public",
		"description" : "Retorna un color en valores hexadecimales basandose en RGB",
		"parameters" : {
			"$nRed" : ["int", "Valor de 0 a 255 para el color rojo", "00"],
			"$nGreen" : ["int", "Valor de 0 a 255 para el color verde", "00"],
			"$nBlue" : ["int", "Valor de 0 a 255 para el color azul", "00"]
		},
		"return" : "string"
	} **/
	public function colorHex($nRed=null, $nGreen=null, $nBlue=null) {
		$sRed 	= ($nRed>=0 && $nRed<=255) ? \sprintf("%02s", \dechex($nRed)) : "00";
		$sGreen = ($nGreen>=0 && $nGreen<=255) ? \sprintf("%02s", \dechex($nGreen)) : "00";
		$sBlue 	= ($nBlue>=0 && $nBlue<=255) ? \sprintf("%02s", \dechex($nBlue)) : "00";
		return \strtoupper("#".$sRed.$sGreen.$sBlue);
	}

	/** FUNCTION {
		"name" : "colorRGB",
		"type" : "public",
		"description" : "Retorna los valores RGB y Transparencia de un color en formato hexadecimal",
		"parameters" : { "$sHexacolor" : ["string", "Valor del color en formato #RRGGBBAA (rojo, verde, azul, alfa)", "#00000000"] },
		"return" : "array"
	} **/
	public function colorRGB($sHexacolor="#00000000") {
		if(!empty($sHexacolor) && $sHexacolor[0]!="#") { $sHexacolor = "#".$sHexacolor; }
		\sscanf($sHexacolor, "#%2x%2x%2x%2x", $nRed, $nGreen, $nBlue, $nAlpha);
		$vRGB 			= [];
		$vRGB["red"] 	= $nRed;
		$vRGB["green"]	= $nGreen;
		$vRGB["blue"]	= $nBlue;
		$vRGB["alpha"]	= $nAlpha;
		
		return $vRGB;
	}

	public function conf($sObjectName) {
		if($obj = self::call($sObjectName)) {
			return $obj->__configFile__();
		}
	}

	/** FUNCTION {
		"name" : "coockie",
		"type" : "public",
		"description" : "
			Guarda y optiene el valor de una cookie del navegador.
			Los valores son analizados con ngl::passwd(), por lo que si NGL_PASSWORD_KEY esta activa, los valores serán enviados al navegador de manera encriptada.
		",
		"parameters" : {
			"$sKey" : ["string", "Nombre de la coockie"],
			"$sValue" : ["string", "Valor de la cookie. Si el valor es NULL o es ignorado, el método intentará retornar el valor actual de la cookie", "NULL"],
			"$mExpire" : ["mixed", "
				Indice de tiempo en el que expira la cookie:
				<ul>
					<li><b>null:</b> establece el valor de expiració en 5 años</li>
					<li><b>string:</b> el valor será tratado con strtotime</li>
					<li><b>int:</b> valor en segundos</li>
				</ul>
			", "NULL"]
		},
		"return" : "mixed"
	} **/
	public function coockie($sKey, $sValue=null, $mExpire=null) {
		if($sValue!==null) {
			if($mExpire===null) {
				$mExpire = \strtotime("+5 year");
			} else if(\is_string($mExpire)) {
				$mExpire = \strtotime($mExpire);
			}
			$sValue = self::passwd($sValue, true);
			\setcookie($sKey, $sValue, $mExpire, "/");
		} else {
			return (isset($_COOKIE[$sKey])) ? $_COOKIE[$sKey] : null;
		}
	}

	public function dataFileLoad($sFilePath) {
		$sFilePath = $this->sandboxPath($sFilePath);
		$aData = [];
		if(\file_exists($sFilePath)) {
			$sData = \file_get_contents($sFilePath);
			$aData = \unserialize($sData);
		}

		return $aData;
	}

	public function dataFileSave($sFilePath, $aData) {
		$sFilePath = $this->sandboxPath($sFilePath);
		$sDirPath = \pathinfo($sFilePath, PATHINFO_DIRNAME);
		if(\is_writable($sDirPath)) {
			\file_put_contents($sFilePath, \serialize($aData));
			return true;
		}
		return false;
	}


	/** FUNCTION {
		"name" : "dec2hex", 
		"type" : "public",
		"description" : "Transforma un decimal en hexadecimal sin límite de tamaño y con la posibilidad de rellenar con 0 por delante",
		"parameters" : {
			"$sDecimal" : ["int", "Número decimal"],
			"$nLength" : ["int", "Largo total de la cadena. Si es inferior o igual a la longitud del string de entrada, no se realiza el rellenado", "0"]
		},
		"return" : "string"
	} **/
	public function dec2hex($sDecimal, $nLength=0) {
		if(!\function_exists("bcmod")) { $this->__errorMode__("die"); self::errorMessage($this->object, 1001); }
		$sDecimal = (string)$sDecimal;
		$sHexaDecimal = "";
		$aHexValues = ["0","1","2","3","4","5","6","7","8","9","A","B","C","D","E","F"];
		while($sDecimal!="0") {
			$sHexaDecimal = $aHexValues[\bcmod($sDecimal,"16")].$sHexaDecimal;
			$sDecimal = \bcdiv($sDecimal, "16", 0);
		}

		return \str_pad($sHexaDecimal, $nLength, "0", \STR_PAD_LEFT);
	}

	/** FUNCTION {
		"name" : "disarrange",
		"type" : "public",
		"description" : "
			Desordena de manera cíclica la Cadena o Array <b>$mSource</b> según las posiciones de <b>$aArrange</b>.
			En la medida en que el desordenamiento avanza sobre <b>$aArrange</b>, las posiciones obtenidas son eliminadas de <b>$mSource</b> haciendo que este sea cada vez mas pequeño.
			Este método retornará el mismo tipo de dato que el valor de entrada <b>$mSource</b>.
		",
		"parameters" : {
			"$mSource" : ["mixed", "String o Array a desordenar"],
			"$aArrange" : ["array", "Secuencia númerica que se utilizará para desordenar <b>$mSource</b>."]
		},
		"examples" : {
			"desordenamiento" : "
				#array original
				$input = array("primero","segundo","tercero","cuarto");

				#ordenamiento
				$disorderly = $ngl()->disarrange($input, array(2,9,7,3));

				#array de salida
				Array (
					[0] => segundo
					[1] => cuarto
					[2] => primero
					[3] => tercero
				)
				
				#explicación
				Array( primero, segundo, tercero, cuarto )
					cuenta "2" posiciones y retorna "segundo"

				Array( primero, tercero, cuarto )
					cuenta "9" posiciones y retorna "cuarto"

				Array( primero, tercero )
					cuenta "7" posiciones y retorna "primero"
			
				por último retorna "tercero"
			"
		},
		"seealso" : ["nglFn::arrange"],
		"return" : "array"
	} **/
	public function disarrange($mSource, $aArrange) {
		if(!\is_array($aArrange)) { self::errorMessage("fn", null, '$aArrange must be of the type array', "die"); }
		if(\is_string($mSource)) {
			$aItems = self::call("unicode")->str_split($mSource);
		} else {
			$aItems = $mSource;
		}

		$nItems = \count($aItems);
		$nArrange = \count($aArrange);
		if($nItems>$nArrange) {
			$nMultiplier = \ceil($nItems/$nArrange);
			$aArrange = self::call()->arrayRepeat($aArrange, $nMultiplier);
		}

		$aDisarrange = [];
		foreach($aArrange as $nIndex) {
			$nIndex *= 1;
			if(!$nIndex) { $nIndex = 10; }
			$nIndex--;
			$x = 0;
			while(\count($aItems)) {
				$aChar = [key($aItems), \current($aItems)];
				if($nIndex==$x) {
					$x = 0;
					$aDisarrange[] = $aChar[1];
					unset($aItems[$aChar[0]]);
					$aItems = \array_values($aItems);
					if(\is_array($aItems) && \count($aItems)==1) {
						$aDisarrange[] = $aItems[0];
						$aItems = [];
					}
					break;
				}

				if(\next($aItems)===false) { \reset($aItems); }

				$x++;
			}

			if(!\count($aItems)) { break; }
		}

		return (\is_string($mSource)) ? \implode($aDisarrange) : $aDisarrange;
	}
	
	
	public function dump() {
		$sOutPut = "";
		if(\func_num_args()) {
			$aDump = \func_get_args();
			foreach($aDump as $mVariable) {
				if($sOutPut!=="") { $sOutPut .= "\n\n--------------------------------------------------------------------------------\n\n"; }
				$sOutPut .= (\is_string($mVariable) || \is_int($mVariable)) ? $mVariable : $this->Dumper($mVariable);
			}
		}
		return $sOutPut;
	}

	private function Dumper($mData, $nIndent=0) {
		$sDump = "";
		$sPrefix = \str_repeat(" |  ", $nIndent);
		if(\is_numeric($mData)) {
			$sDump .= "Number: $mData";
		} elseif(\is_string($mData)) {
			$sDump .= "String: '$mData'";
		} elseif (\is_null($mData)) {
			$sDump .= "NULL";
		} elseif($mData===true) {
			$sDump .= "TRUE";
		} elseif($mData===false) {
			$sDump .= "FALSE";
		} elseif(\is_array($mData)) {
			$sDump .= "Array(".\count($mData).")";
			$nIndent++;
			foreach($mData AS $k => $mValue) {
				$sDump .= "\n$sPrefix [$k] = ";
				$sDump .= $this->Dumper($mValue, $nIndent);
			}
		} elseif(\is_object($mData)) {
			$sDump .= "Object(".get_class($mData).")";
			$nIndent++;
			foreach($mData AS $k => $mValue) {
				$sDump .= "\n$sPrefix $k -> ";
				$sDump .= $this->Dumper($mValue, $nIndent);
			}
		}

		return $sDump;
	}

	public function dumphtml() {
		$sDump = $this->dump(\func_get_args());
		$sDump = \preg_replace("/((http|ftp|https):\/\/[\w-]+(\.[\w-]+)+([\w.,@?^=%&amp;:\/~+#-]*[\w@?^=%&amp;\/~+#-])?)/", '<a href="\1" target="_blank">\1</a>', $sDump);
		$sDump = "<pre style='white-space:pre'>".$sDump."</pre>";
		return $sDump;
	}

	public function dumpconsole() {
		$sConsole = "<script>\r\n//<![CDATA[\r\nif(!console){var console={log:function(){}}}";
		$sDump = $this->dump(\func_get_args());
		if($sDump!=="") {
			$aDump = \explode("\n", $sDump);
			foreach($aDump as $sLine) {
				if(\trim($sLine)) {
					$sLine = \addslashes($sLine);
					$sConsole .= "console.log(\"".$sLine."\");";
				}
			}
		}
		$sConsole .= "\r\n//]]>\r\n</script>";
		return $sConsole;
	} 

	/** FUNCTION {
		"name" : "emptyToNull", 
		"type" : "public",
		"description" : "
			Establece como NULL los valores de $aData, cuyo indice se encuentre en $aKeys, que retornen TRUE a la funcion empty.
			Si $aKeys es NULL se evaluarán todos los indices.
		",
		"parameters" : {
			"$aData" : ["array", "Array de datos"],
			"$aKeys" : ["array", "Array con los nombres de las claves del array $aData que deberán ser evaluadas","null"]
		},
		"return" : "array"
	} **/
	public function emptyToNull($aData, $aKeys=null) {
		if($aKeys===null) { $aKeys = \array_keys($aData); }
		foreach($aKeys as $mKey) {
			if(empty($aData[$mKey])) { $aData[$mKey] = null; }
		}

		return  $aData;
	}

	/** FUNCTION {
		"name" : "emptyToZero", 
		"type" : "public",
		"description" : "
			Establece como NULL los valores de $aData, cuyo indice se encuentre en $aKeys, que retornen TRUE a la funcion empty.
			Si $aKeys es NULL se evaluarán todos los indices.
		",
		"parameters" : {
			"$aData" : ["array", "Array de datos"],
			"$aKeys" : ["array", "Array con los nombres de las claves del array $aData que deberán ser evaluadas","null"]
		},
		"return" : "array"
	} **/
	public function emptyToZero($aData, $aKeys=null) {
		if($aKeys===null) { $aKeys = \array_keys($aData); }
		foreach($aKeys as $mKey) {
			if(empty($aData[$mKey])) { $aData[$mKey] = 0; }
		}

		return  $aData;
	}


	/** FUNCTION {
		"name" : "nullToEmpty", 
		"type" : "public",
		"description" : "
			Establece como vacio los valores de $aData, cuyo indice se encuentre en $aKeys y valor sea NULL o FALSE.
			Si $aKeys es NULL se evaluarán todos los indices.
		",
		"parameters" : {
			"$aData" : ["array", "Array de datos"],
			"$aKeys" : ["array", "Array con los nombres de las claves del array $aData que deberán ser evaluadas","null"]
		},
		"return" : "array"
	} **/
	public function nullToEmpty($aData, $aKeys=null) {
		if($aKeys===null) { $aKeys = \array_keys($aData); }
		foreach($aKeys as $mKey) {
			if(!isset($aData[$mKey]) || $aData[$mKey]===null || $aData[$mKey]===false) { $aData[$mKey] = ""; }
		}

		return  $aData;
	}

	/** FUNCTION {
		"name" : "encoding", 
		"type" : "public",
		"description" : "
			Verifica si la cadena <b>$sString</b> se encuentra codificada en <b>$mEncoding</b>
			<b>$mEncoding</b> debe ser el nombre de una codificación válida o un array de nombres.
			Si no se especifica un <b>$mEncoding</b> se chequerá la cadena con las codificaciones mas frecuentes.
			
			Una lista completa de las codificaciones soportadas se encuentra en:
			https://www.gnu.org/software/libiconv
		",
		"parameters" : {
			"$sString" : ["string", "Cadena a chequear"],
			"$mEncoding" : ["mixed", "Nombre de una codificación válida o un array de nombres", "null"],
			"$bStrict" : ["boolean", "Determina si, en caso afirmativo, el método debe retornar el nombre de la codificación o TRUE", "false"]
		},
		"return" : "string o booleano"
	} **/
	public function encoding($sString, $mEncoding=null, $bStrict=false) {
		$aEncodingCommons = ["UTF-8", "ASCII", "ISO-8859-1", "CP1252", "UTF-7"];
		$aEncodingEuropeans = [
			"ISO-8859-2", "ISO-8859-3", "ISO-8859-4", "ISO-8859-5", "ISO-8859-7",
			"ISO-8859-9", "ISO-8859-10", "ISO-8859-13", "ISO-8859-14", "ISO-8859-15",
			"ISO-8859-16", "KOI8-R", "KOI8-U", "KOI8-RU", "CP1250", "CP1251",
			"CP1253", "CP1254", "CP1257", "CP850", "CP866", "CP1131", "MacRoman",
			"MacCentralEurope", "MacIceland", "MacCroatian", "MacRomania",
			"MacCyrillic", "MacUkraine", "MacGreek", "MacTurkish", "Macintosh"
		];

		if($mEncoding!==null) {
			$aEncoding = (\is_array($mEncoding)) ? $mEncoding : [$mEncoding];
		} else {
			$aEncoding = $aEncodingCommons;
		}

		$sMD5 = \md5($sString);

		// chequeo de codificaciones comunes o pasadas por el usuario
		foreach($aEncoding as $sEncoding) {
			if(!\strstr($sEncoding, "//")) { $sEncoding .= "//IGNORE"; }
			$sSample = \iconv($sEncoding, $sEncoding, $sString);
			if(\md5($sSample)==$sMD5) {
				return ($bStrict) ? true : $sEncoding;
			}
		}

		if($mEncoding!==null) { return false; }

		// chequedo de codificaciones occidentales
		$aEncoding = $aEncodingEuropeans;
		foreach($aEncoding as $sEncoding) {
			if(!\strstr($sEncoding, "//")) { $sEncoding .= "//IGNORE"; }
			$sSample = @\iconv($sEncoding, $sEncoding, $sString);
			if(\md5($sSample)==$sMD5) {
				return ($bStrict) ? true : $sEncoding;
			}
		}

		return false;
	}

	/** FUNCTION {
		"name" : "ensureVar", 
		"type" : "public",
		"description" : "Retorna el valor de $mSure cuanto $mVar no esta seteada o es NULL",
		"parameters" : {
			"$mVar" : ["mixed", "Variable a evaluar"],
			"$mSure" : ["mixed", "Valor que se aplicará cuando $mVar no exista o sea NULL"]
		},
		"return" : "mixed"
	} **/
	public function ensureVar(&$mVar, $mSure) {
		return (!isset($f) || $f===null) ? $r : $f;
	}

	/** FUNCTION {
		"name" : "\exploder", 
		"type" : "public",
		"description" : "Ejecuta la función <b>\explode</b> de PHP de manera recursiva, utilizando los delimitadores para armar un array multi-dimensional",
		"parameters" : {
			"$aDelimiters" : ["array", "Delimitadores"],
			"$sSource" : ["string", "Cadena de origen"],
			"$nLimit" : ["int", "
				Si es positivo, el array devuelto contendrá el máximo de $nLimit elementos, y el último elemento contendrá el resto de la cadena de origen.
				Si es negativo, se devolverán todos los componentes a excepción del último -$nLimit.
				Si es cero, se tratará como 1.
			"]
		},
		"return" : "array"
	} **/
	public function exploder($aDelimiters, $sSource) {
		$aReturn = \explode($aDelimiters[0],$sSource);
		\array_shift($aDelimiters);
		if($aDelimiters!=null) {
			foreach($aReturn as $mKey => $mValue) {
				$aReturn[$mKey] = $this->exploder($aDelimiters, $mValue);
			}
		}

		return  $aReturn;
	}
	
	/** FUNCTION {
		"name" : "\explodeTrim", 
		"type" : "public",
		"description" : "Ejecuta la función <b>\explode</b> de PHP y a continuación trata a cada uno de los valores con la función <b>trim</b>",
		"parameters" : {
			"$sDelimiter" : ["string", "Delimitador"],
			"$sSource" : ["string", "Cadena de origen"],
			"$nLimit" : ["int", "
				Si es positivo, el array devuelto contendrá el máximo de $nLimit elementos, y el último elemento contendrá el resto de la cadena de origen.
				Si es negativo, se devolverán todos los componentes a excepción del último -$nLimit.
				Si es cero, se tratará como 1.
			"]
		},
		"return" : "array"
	} **/
	public function explodeTrim($sDelimiter, $sSource, $nLimit=null) {
		$aReturn = ($nLimit!==null) ? \explode($sDelimiter, $sSource, $nLimit) : \explode($sDelimiter, $sSource);
		return \array_map("trim", $aReturn);
	}

	/** FUNCTION {
		"name" : "headers", 
		"type" : "public",
		"description" : "Retorna un array con todas las cabeceras recibidas en la ultima peticion",
		"parameters" : { "$sCase" : ["string", "Determina si los nombres de los headers deben ser retornados en mayúsculas, minúsculas"] },
		"return" : "mixed"
	} **/
	public function getheaders($sCase="lowercase") {
		$sCase = (\strtolower($sCase)=="lowercase") ? "strtolower" : "strtoupper";
		$aHeaders = \getallheaders();
		$aHeadersKeys = \array_map($sCase, \array_keys($aHeaders));
		return \array_combine($aHeadersKeys, $aHeaders);
	}

	/** FUNCTION {
		"name" : "headers", 
		"type" : "public",
		"description" : "Retorna un array con todas las cabeceras enviadas hasta el momento, un una cadena o false para cuando se especifique $sHeader",
		"parameters" : { "$sHeader" : ["string", "Chequea que esta cabecera haya sido enviada, retornando su valor o FALSE"] },
		"return" : "mixed"
	} **/
	public function headers($sHeader=null) {
		$aHeaders = $aHeadersLcase = [];
		$aList = \headers_list();
		if(\is_array($aList) && \count($aList)) {
			foreach($aList as $sHeader) {
				$aHeader = \explode(": ", $sHeader);
				$aHeaders[$aHeader[0]] = $aHeadersLcase[\strtolower($aHeader[0])] = $aHeader[1];
			}
		}
		
		if($sHeader!==null) {
			$sHeader = strtolower($sHeader);
			return (\array_key_exists($aHeadersLcase, $sHeader)) ? $aHeadersLcase[$sHeader] : false;
		}
		
		return $aHeaders;
	}

	public function parseHeaderProperty($sRawHeaders) {
		$aGetProperties = explode(";", $sRawHeaders);
		$aProperties = [];
		foreach($aGetProperties as $sProperty) {
			$sProperty = \trim($sProperty);
			if($sProperty!="") {
				$x = \strpos($sProperty, "=");
				if($x!==false)  {
					$sKey = \trim(\substr($sProperty, 0, $x));
					$sValue = \trim(\substr($sProperty, $x+1));
					if(\strlen($sValue) > 0 && $sValue[0]=='"') { $sValue = \substr($sValue, 1, -1); }
					$aProperties[$sKey] = $sValue;
				} else {
					$aProperties[\trim($sProperty)] = \trim($sProperty);
				}
			}
		}

		return $aProperties;
	}


	/** FUNCTION {
		"name" : "hex2dec", 
		"type" : "public",
		"description" : "Transforma un hexadecimal en decimal sin límite de tamaño",
		"parameters" : { "$sDecimal" : ["string", "Número hexadecimal"] },
		"return" : "string"
	} **/
	public function hex2dec($sHexaDecimal) {
		if(!\function_exists("bcmod")) { $this->__errorMode__("die"); self::errorMessage($this->object, 1001); }
		$aDecValues = [
			"0" => "0", "1" => "1", "2" => "2",
			"3" => "3", "4" => "4", "5" => "5",
			"6" => "6", "7" => "7", "8" => "8",
			"9" => "9", "A" => "10", "B" => "11",
			"C" => "12", "D" => "13", "E" => "14",
			"F" => "15"
		];
		$sDecimal = "0";

		$sHexaDecimal = (string)$sHexaDecimal;
		$sHexaDecimal = \strtoupper($sHexaDecimal);
		$sHexaDecimal = \strrev($sHexaDecimal);
		$nHexaDecimal = \strlen($sHexaDecimal);
		for($x=0; $x<$nHexaDecimal; $x++) {
			$sDecimal = \bcadd(\bcmul(\bcpow("16", $x, 0),$aDecValues[$sHexaDecimal[$x]]), $sDecimal);
		}

		return $sDecimal;
	}

	/** FUNCTION {
		"name" : "imploder", 
		"type" : "public",
		"description" : "
			Une elementos de un array multi dimensional en una cadena.
			Cuando <b>$mGlue</b> sea declarado como un array, el primer índice será utilizado para unir los valores 
			y el segundo para unir los distintos niveles del array.
			Para mantener una relación con <b>\implode</b>, si <b>$mGlue</b> no es especificado se asumirá que el único valor pasado es <b>$aSource</b>.
		",
		"parameters" : {
			"$mGlue" : ["mixed", "Cadena o array de dos de ellas con las que se unirán los valores", ""]
			"$aSource" : ["array", "array de datos"],
		},
		"return" : "string"
	} **/
	public function imploder($mGlue, $aSource=null) {
		if($aSource===null) {
			$aSource = $mGlue;
			$mGlue = "";
		}
		
		$sGlue1 = $sGlue2 = $mGlue;
		if(\is_array($mGlue)) {
			$sGlue1 = $mGlue[0];
			$sGlue2 = $mGlue[1];
		}
		
		$sImplode = "";
		foreach($aSource as $mValue) {
			if(\is_array($mValue)) {
				if(!empty($sImplode)) { $sImplode .= $sGlue2; }
				$sImplode .= $this->imploder([$sGlue1, $sGlue2], $mValue);
			} else {
				$sImplode .= $mValue.$sGlue1;
			}
		}
		
		$sImplode = \trim($sImplode, $sGlue1);
		return $sImplode;
	}

	/** FUNCTION {
		"name" : "imya",
		"type" : "public",
		"description" : "Retorna o valida un <b>imya</b>",
		"parameters" : { "$sImya" : ["string", "
			Cuando el valor es NULL se genera un nuevo imya, equivalente a un valor unique(32)
			Cuando el valor es distinto de NULL limpia la cadena basandose en el patron [^a-zA-Z0-9]
			Si la cadena resultante cuenta con menos de 32 carecteres el método retornará NULL
		", "null"] },
		"return" : "string o null"
	} **/
	public function imya($sImya=null) {
		if($sImya===null) {
			return $this->unique(32);
		} else {
			$sImya = \preg_replace("/[^a-zA-Z0-9]/", "", $sImya);
			return (\strlen($sImya)==32) ? $sImya : null;
		}
	}

	/** FUNCTION {
		"name" : "stroimya",
		"type" : "public",
		"description" : "Retorna una cadena con formato <b>imya</b> basado en una cadena de entrada",
		"parameters" : { "$sString" : ["string", "Cadena", "null"] },
		"return" : "string"
	} **/
	public function strimya($sString) {
		$sMD5		= \md5($sString);
		$sLetters	= \preg_replace("/[^a-z]/i", "", $sMD5);
		$sLetters	= $sLetters.$sLetters.$sLetters;
		$sRounds	= \preg_replace("/[^0-9]/i", "", $sMD5);
		$nRounds 	= \array_sum(\str_split($sRounds))*20;
		$sHash		= $sMD5;
		$sSalt		= \md5(\strrev($sHash));
		$sHash		= \crypt($sHash, '$6$rounds='.$nRounds.'$'.$sSalt.'$');
		$sHash		= \preg_replace("/[^a-z0-9\$]/i", "", $sHash);
		$nDollar	= \strrpos($sHash, '$');
		$sImya		= \substr($sHash, $nDollar+1, 32);
		$sImya[0]	= $sLetters[\substr($nRounds, 0, 1)];

		return $sImya;
	}

	/** FUNCTION {
		"name" : "intPart",
		"type" : "public",
		"description" : "Retorna la parte entera de un número",
		"parameters" : { "$mNumber" : ["mixed", "Valor númerico"] },
		"return" : "integer o NULL"
	} **/
	public function intPart($mNumber=null) {
		if($mNumber===null || $mNumber==="" || \is_array($mNumber)) { return null; }
		$mNumber = (string)$mNumber;
		$sSing = ($mNumber[0]=="-") ? $mNumber[0] : "";
		$sNumber = \preg_replace("/[^\.\,\d]/", "", $mNumber);
		$sNumber = \str_replace(",", ".", $sNumber);
		$aNumber = \explode(".", $sSing.$sNumber);
		if(isset($aNumber[1])) { \array_pop($aNumber); }
		return \implode($aNumber);
	}

	/** FUNCTION {
		"name" : "isArrayArray",
		"type" : "public",
		"description" : "
			Comprueba si <b>$aArray</b> es un Array de Arrays. 
			Con <b>$bStrict</b> FALSE sólo chequeará que el primer y ultimo valor de <b>$aArray</b> sean un arrays y tengan las mismas claves.
			Si es TRUE verificará que todos los valores sean del tipo array y tengan las mismas claves
		",
		"parameters" : { 
			"$aArray" : ["array", "Array a comprobar"], 
			"$bStrict" : ["boolean", "Activa o desactiva el modo estricto", "false"]
		},
		"return" : "boolean"
	} **/
	public function isArrayArray($aArray, $bStrict=false) {
		if(\is_array($aArray)) {
			\reset($aArray);
			if(!$bStrict) {
				$aFirst = \current($aArray);
				$aLast = \end($aArray);
				return (\is_array($aFirst) && \is_array($aLast) && (\array_keys($aFirst)==\array_keys($aLast)));
			}

			$aFirst = \current($aArray);
			$aFirstKeys = \array_keys($aFirst);
			foreach($aArray as $mValue) {
				if(!\is_array($mValue) || $aFirstKeys!=\array_keys($mValue)) { return false; }
			}

			return true;
		}

		return false;
	}

	/** FUNCTION {
		"name" : "isBase64",
		"type" : "public",
		"description" : "
			Comprueba si <b>$sValue</b> es una cadena codificada en base64 validad
		",
		"parameters" : { "$sValue" : ["string", "Valor a comprobar"] },
		"return" : "boolean"
	} **/
	public function isBase64($sValue) {
		return (\base64_encode(\base64_decode($sValue, true))===$sValue);
	}

	/** FUNCTION {
		"name" : "isEmpty",
		"type" : "public",
		"description" : "
			Comprueba si <b>$mValue</b> esta vacío. en el caso de que 
			$mValue sea del tipo Array, isEmpty devolverá FALSE si al menos
			uno de sus índices está vacío. Los arrays son examinados de manera recursiva
		",
		"parameters" : { "$mValue" : ["mixed", "Valor a comprobar"] },
		"return" : "boolean"
	} **/
	public function isEmpty($mValue) {
		if(\is_array($mValue)) {
			foreach($mValue as $mVar) {
				if(!$this->isEmpty($mVar)) {
					return false;
				}
			}
		} else if(!empty($mValue)) {
			return false;
		}

		return true;
	}

	/** FUNCTION {
		"name" : "isImage",
		"type" : "public",
		"description" : "Comprueba archivo es una imagen",
		"parameters" : { "$sFilePath" : ["string", "Filepath"] },
		"return" : "boolean"
	} **/
	public function isImage($sFilePath) {
		if(\file_exists($sFilePath)) {
			$finfo = \finfo_open(FILEINFO_MIME_TYPE);
			$sMimeType = \finfo_file($finfo, $sFilePath);
			$aMimeType = \explode("/", $sMimeType);
			return (\strtolower($aMimeType[0])=="image") ? true : false;
		}
		return false;
	}

	/** FUNCTION {
		"name" : "isInteger",
		"type" : "public",
		"description" : "Comprueba si un valor es un número entero",
		"parameters" : { "$mNumber" : ["mixed", "Valor númerico"] },
		"return" : "boolean"
	} **/
	public function isInteger($mNumber) {
		if($mNumber===null) { return false; }
		$nInteger = $this->intPart($mNumber);
		return ($nInteger==$mNumber);
	}

	/** FUNCTION {
		"name" : "isJSON",
		"type" : "public",
		"description" : "Comprueba si un valor es una cadena JSON válida",
		"parameters" : {
			"$sString" : ["string", "Cadena a chequear"],
			"$mType" : ["string", "
				Determina el tipo de respuesta (Boolean, Array u Object)
				<ul>
					<li><b>NULL:</b> se retornará TRUE o FALSE</li>
					<li><b>array:</b> se retornarán los datos como un array asociativo cuando el valor sea un JSON, o FALSE</li>
					<li><b>object:</b> se retornarán los datos como un objeto cuando el valor sea un JSON, o FALSE/li>
				</ul>
			", "null"]
		},
		"return" : "mixed"
	} **/
	public function isJSON($sString, $mType=null) {
		if(!\is_string($sString)) { return false; }
		if(!\strlen($sString)) { return false; }
		$bType = null;
		$sString = \ltrim($sString);
		if(!empty($sString) && $sString[0]!="\x5B" && $sString[0]!="\x7B") { return false; }
		if($mType!==null) {
			$bType = (\strtolower($mType)=="array");
			$json = @\json_decode($sString, $bType);
		} else {
			$json = @\json_decode($sString);
		}
		
		if(\json_last_error()==JSON_ERROR_NONE) {
			return ($bType!==null) ? $json : true;
		}
		
		return false;
	}

	/** FUNCTION {
		"name" : "isLowerCase",
		"type" : "public",
		"description" : "
			Comprueba si <b>$sString</b> son sólo letras minúsculas. En el caso de que 
			$mValue sea del tipo Array, isLowerCase devolverá FALSE si al menos en 
			uno de sus índices existen catacteres que no esten en minúsculas.
			Los arrays son examinados de manera recursiva
		",
		"parameters" : { "$sString" : ["string", "Cadena a comprobar"] },
		"return" : "boolean"
	} **/
	public function isLowerCase($mValue) {
		if(\is_array($mValue)) {
			foreach($mValue as $mVar) {
				if(!$this->isLowerCase($mVar)) {
					return false;
				}
			}
		} else if(!\ctype_lower($mValue)) {
			return false;
		}

		return true;
	}

	/** FUNCTION {
		"name" : "isNull",
		"type" : "public",
		"description" : "
			Comprueba si un valor es NULL.
			Esto sucederá cuando el método nativo <b>\is_null($mValue)</b> retorne true o cuando el valor <b>strtolower($mValue)</b> sea igual a <b>null</b>
		",
		"parameters" : { "$mValue" : ["mixed", "Cadena a comprobar"] },
		"return" : "boolean"
	} **/
	public function isNull($mValue) {
		if(\is_null($mValue) || \strtolower($mValue)=="null") { return true; }
		return false;
	}

	/** FUNCTION {
		"name" : "isNumber",
		"type" : "public",
		"description" : "
			Comprueba si <b>$mNumber</b> es un valor númerico y retorna su valor en el formato correcto (float o int).
			Seran considerados números los siguientes formatos:<br />
			<ul>
				<li>123.456 (float)</li>
				<li>123,456	(float)</li>
				<li>123,456.78 (float)</li>
				<li>123.456,78 (float)</li>
				<li>123.456.789 (int)</li>
				<li>123,456,789 (int)</li>
				<li>123456789 (int)</li>
			</ul>
		",
		"parameters" : { "$mNumber" : ["mixed", "Valor a comprobar"] },
		"return" : "integer, float o null"
	} **/
	public function isNumber($mNumber) {
		$nDot = \strpos($mNumber, ".");
		$nComma = \strpos($mNumber, ",");
		
		if($nDot!==false && $nComma!==false) {
			if($nDot<$nComma) {
				$mNumber = \str_replace(".", "", $mNumber);
				$mNumber = \str_replace(",", ".", $mNumber);
			} else {
				$mNumber = \str_replace(",", "", $mNumber);
			}
		} else if($nComma!==false) {
			$mNumber = \str_replace(",", ".", $mNumber);
		}
		
		if(\substr_count($mNumber, ".")>1) {
			$mNumber = \str_replace(".", "", $mNumber);
		}
		
		$nNumber = null;
		if(\is_numeric($mNumber)) {
			$nNumber = $mNumber * 1;
			if(\is_float($nNumber) && !\is_int($nNumber)) {
				$nNumber = (float)$nNumber;
			} else if(\is_int($nNumber)) {
				$nNumber = (int)$nNumber;
			}
		}

		return $nNumber;
	}

	/** FUNCTION {
		"name" : "isSerialized",
		"type" : "public",
		"description" : "Comprueba si <b>$sString</b> es un array serializado. Si <b>$bResult</b> es igual a TRUE el método retornará un array en caso de TRUE",
		"parameters" : {
			"$sString" : ["string", "Cadena a chequear"],
			"$bResult" : ["boolean", "Determina el tipo de respuesta", "false"],
		},
		"return" : "mixed"
	} **/
	public function isSerialized($sString, $bResult=false) {
		if(!\is_string($sString)) { return false; }
		$sString = \trim($sString);

		if($sString==="b:0;" || $sString==="b:1;" || $sString==="N;") { return true; }
		if($sString[1]!==":") { return false; }

		$nLen = \strlen($sString);
		switch($sString[0]) {
			case "s":
				if($sString[$nLen-2]!=='"') { return false; }
				break;
			case "i":
			case "d":
				if($sString[$nLen-1]!==';') { return false; }
				break;
			case "a":
			case "O":
				if($sString[$nLen-1]!=='}') { return false; }
				break;
			default:
				return false;
		}

		$aNums = [true,true,true,true,true,true,true,true,true,true];
		if(!isset($aNums[$sString[2]])) { return false; }

		$aResult = @\unserialize($sString);
		if($aResult===false) { return false; }

		return ($bResult) ? $aResult : true;
	}	
	
	/** FUNCTION {
		"name" : "isTrue",
		"type" : "public",
		"description" : "
			Comprueba si <b>$mValue</b> es TRUE o FALSE. Si $mValue es String y su valor es '0', 'false', 'null', 'no' u 'off', el valor de retorno será FALSE
		",
		"parameters" : { "$mValue" : ["mixed", "Valor a comprobar"] },
		"return" : "boolean"
	} **/
	public function isTrue($mValue, $bStrict=false) {
		if($bStrict===true) {
			$bValue = false;
			if($mValue===true || $mValue===1 || \in_array(\strtolower($mValue), ["1", "true", "yes", "on"])) {
				$bValue = true;
			}
		} else {
			$bValue = true;
			if(\in_array(\strtolower($mValue), ["", "0", "false", "null", "no", "off"])) {
				$bValue = false;
			}
		}
		
		return $bValue;
	}
	
	/** FUNCTION {
		"name" : "isUpperCase",
		"type" : "public",
		"description" : "
			Comprueba si <b>$sString</b> son sólo letras mayúsculas. En el caso de que $mValue sea del tipo Array, isUpperCase devolverá FALSE si al menos en uno de sus índices existen catacteres que no esten en mayúsculas.
			Los arrays son examinados de manera recursiva
		",
		"parameters" : { "$sString" : ["string", "Cadena a comprobar"] },
		"return" : "boolean"
	} **/
	public function isUpperCase($mValue) {
		if(\is_array($mValue)) {
			foreach($mValue as $mVar) {
				if(!$this->isUpperCase($mVar)) {
					return false;
				}
			}
		} else if(!\ctype_upper($mValue)) {
			return false;
		}

		return true;
	}
	
	/** FUNCTION {
		"name" : "isURL",
		"type" : "public",
		"description" : "
			Retorna TRUE (o el protocolo) si <b>$sFilePath</b> es una URL http, ftp o comienza con //
			Para este último caso, cuando se solicite el protocolo, se retornará "url"
		",
		"parameters" : {
			"$sFilePath" : ["string", "URL a comprobar"],
			"$bScheme" : ["boolean", "Determina si en caso de TRUE se debe o no retornar el protocolo", "false"]
		},
		"return" : "mixed"
	} **/
	public function isURL($sFilePath, $bScheme=false) {
		if(empty($sFilePath)) { return false; }
		$sScheme = \parse_url($sFilePath, PHP_URL_SCHEME);
		$sProtocol = ($sScheme!==null) ? \strtolower($sScheme) : ((isset($sFilePath[1]) && $sFilePath[0].$sFilePath[1]==="//") ? "url": "filesystem");
		if(\in_array($sProtocol, ["http", "https", "ftp", "ftps", "url"])) {
			return ($bScheme) ? $sProtocol : true;
		} else {
			return false;
		}
		
	}
	
	/** FUNCTION {
		"name" : "isUTF8",
		"type" : "public",
		"description" : "Comprueba si <b>$sString</b> es una cadena UTF-8",
		"parameters" : { "$sString" : ["string", "Cadena a comprobar"] },
		"return" : "boolean"
	} **/
	public function isUTF8($sString) {
		return (\preg_match("//u", $sString));
	}

	/** FUNCTION {
		"name" : "memory", 
		"type" : "public",
		"description" : "Devuelve el valor de la cantidad de memoria asignada a PHP, formateado con strSizeEncode()",
		"parameters" : {
			"$bRealUsage" : ["boolean", "True para obtener el tamaño real de memoria asignada por el sistema.", "false"],
			"$nDecimals" : ["int", "Cantidad de decimales despues de la coma", 5]
		},
		"return" : "string"
	} **/
	public function memory($bRealUsage=false, $nDecimal=5) {
		return $this->strSizeEncode(\memory_get_usage($bRealUsage), $nDecimal);
	}

	/**
	transforma una cadena multilinea en un array, utilizando $sDelimiter como separador de entrada
	ej: multilples queries SQL terminadas en ;
	si no se especifica $sDelimiter, se utilizará PHP_EOL
	*/
	public function strToArray($sSource, $sDelimiter=null) {
		$aSource = \explode(PHP_EOL, $sSource);
		if($sDelimiter===null) { return $aSource; }

		$nDelimiter = \strlen($sDelimiter);
		$sLine = "";
		$aString = [];
		foreach($aSource as $sBuffer) {
			$sBuffer = \rtrim($sBuffer);
			$sLast = \substr($sBuffer, ($nDelimiter*-1));
			$sLine .= $sBuffer;
			if($sLast==$sDelimiter) {
				if($sLine!=$sDelimiter) { $aString[] = $sLine; }
				$sLine = "";
			}
		}

		return $aString;
	}

	/** FUNCTION {
		"name" : "mimeType", 
		"type" : "public",
		"description" : "Retorna el Mime Type de la extensión proporcionada.",
		"parameters" : { "$sExtension" : ["string", "Extensión."] },
		"return" : "string"
	} **/
	public function mimeType($sExtension) {
		foreach($this->apacheMimeTypes() as $aGroup) {
			if(isset($aGroup[$sExtension])) { return $aGroup[$sExtension]; }
		}
		return "application/octet-stream";
	}

	/** FUNCTION {
		"name" : "secureName", 
		"type" : "public",
		"description" : "Limpia una cadena para que pueda ser utilizada como nombre de archivo, carpeta, tabla o campo de una base de datos",
		"parameters" : {
			"$sName" : ["string", "Nombre original."],
			"$sLeave" : ["string", "Conserva estos caracteres", ""]
		},
		"return" : "string"
	} **/
	public function secureName($sName, $sLeave="") {
		if(!empty($sLeave)) { $sLeave = \preg_quote($sLeave); }
		$sName = $this->unaccented($sName);
		$sName = \preg_replace("/[^0-9a-z_".$sLeave."]/", "_", \strtolower($sName));
		$sName = \trim($sName);
		return $sName;
	}

	/** FUNCTION {
		"name" : "once",
		"type" : "public",
		"description" : "
			Genera o chequea un código único guardado en la session activa.
			Cuando se ejecuta el método sin el argumento $sCode, este generará un <b>ONCECODE</b>, lo guardará en la session y lo retornará.
			Cuando se pase un $sCode al método, este chequeará si el mismo existe en la session activa. Si existe lo deseteará y devolverá TRUE; en caso contrario retornará FALSE.
			La vigencia de los códigos en la session es de NGL_ONCECODE_TIMELIFE, en segundos.
		",
		"parameters" : { "$sCode" : ["string", "ONCECODE generado por el método en una ejecución previa", "null"] },
		"return" : "mixed"
	} **/
	public function once($sCode=null) {
		if(!isset($_SESSION[NGL_SESSION_INDEX]["ONCECODES"])) { $_SESSION[NGL_SESSION_INDEX]["ONCECODES"] = []; }
		
		$nNow = \time();
		foreach($_SESSION[NGL_SESSION_INDEX]["ONCECODES"] as $sOnceCode => $nTime) {
			if($nNow > ($nTime + NGL_ONCECODE_TIMELIFE)) {
				unset($_SESSION[NGL_SESSION_INDEX]["ONCECODES"][$sOnceCode]);
			}
		}
		
		if($sCode===null) {
			$sCode = $this->unique(64);
			$_SESSION[NGL_SESSION_INDEX]["ONCECODES"][$sCode] = $nNow;
			return $sCode;
		} else {
			if(isset($_SESSION[NGL_SESSION_INDEX]["ONCECODES"][$sCode])) {
				unset($_SESSION[NGL_SESSION_INDEX]["ONCECODES"][$sCode]);
				return true;
			} else {
				return false;
			}
		}
	}

	/** FUNCTION {
		"name" : "round05",
		"type" : "public",
		"description" : "
			Redondea un número al entero o punto medio mas cercano.
			El parámetro $nPrecition permite controlar la distancia del redondeo al punto medio
			Según la presición el redondeo dará con .5 cuando: 
			<ul>
				<li><b>0:</b> cuando sea x.5</li>
				<li><b>1:</b> cuando se encuentre entre x.4 y x.6</li>
				<li><b>2:</b> cuando se encuentre entre x.3 y x.7</li>
				<li><b>3:</b> cuando se encuentre entre x.2 y x.8</li>
				<li><b>4:</b> cuando se encuentre entre x.1 y x.9</li>
				<li><b>&gt5:</b> siempre</li>
			</ul>
		",
		"parameters" : {
			"$nNumber" : ["int", "Numero a redondear", "null"]
			"$nPrecition" : ["int", "Presición del redondeo desde el .5", "1"]
		},
		"examples" : {
			"Redondeos" : "
				round05(3.2, 1) => 3
				round05(3.2, 2) => 3
				round05(3.2, 3) => 3.5
				round05(3.2, 4) => 3.5
				round05(3.2, 5) => 3.5
			"
		},
		"return" : "float"
	} **/
	public function round05($nNumber, $nPrecition=1) {
		$nFloor = \floor($nNumber*1);
		$nPrecition = \abs((int)$nPrecition/10);
		return ($nNumber<($nFloor+(.5-$nPrecition))) ? $nFloor : (($nNumber>($nFloor+(.5+$nPrecition))) ? \ceil($nNumber) : $nFloor+.5);
	}
	
	public function settings() {
		global $ngl;
		if(file_exists(NGL_PATH_PROJECT."/settings.php")) {
			include(NGL_PATH_PROJECT."/settings.php");
		}

		$aSettings = \get_defined_vars();
		unset($aSettings["ngl"]);
		\ksort($aSettings);

		$aConstants = \get_defined_constants(true);
		foreach($aConstants["user"] as $sConstant => $mValue) {
			if(\substr($sConstant, 0, 4)=="NGL_") { unset($aConstants["user"][$sConstant]); }
		}
		\ksort($aConstants["user"]);

		return ["constants"=>$aConstants["user"], "variables"=>$aSettings];
	}

	public static function sow($sName, $sNewName) {
		$sName = \strtolower(\preg_replace("/[^a-z0-9\_\-]+/", "", $sName));
		$sNewName = \strtolower(\preg_replace("/[^a-zA-Z0-9\_\-\/]+/", "", $sNewName));
		$sFolder = "components";
		switch($sName) {
			case "nut": $sName = "nut.php"; $sDestine = NGL_PATH_NUTS.NGL_DIR_SLASH.$sNewName.".php"; break;
			case "tutor": $sName = "tutor.php"; $sDestine = NGL_PATH_TUTORS.NGL_DIR_SLASH.$sNewName.".php"; break;
			case "owltutor": $sName = "owltutor.php"; $sDestine = NGL_PATH_TUTORS.NGL_DIR_SLASH.$sNewName.".php"; break;
			default:
				$sFolder = "structures";
				$sDestine = $sNewName;
		}

		$sSource = NGL_PATH_FRAMEWORK.NGL_DIR_SLASH."assets".NGL_DIR_SLASH."templates".NGL_DIR_SLASH.$sFolder.NGL_DIR_SLASH.$sName;
		if(\file_exists($sSource)) {
			if(\is_dir($sSource)) { $sNewName = null; }
			if($sNewName===null) {
				$source = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sSource, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
				foreach($source as $item) {
					$sDestinePath = $sDestine.NGL_DIR_SLASH.$source->getSubPathname();
					if(!\file_exists($sDestinePath)) {
						if($item->isDir()) {
							\mkdir($sDestinePath, NGL_CHMOD_FOLDER);
						} else {
							\copy($item, $sDestinePath);
							\chmod($sDestinePath, NGL_CHMOD_FILE);
						}
					}
				}
				if(\file_exists($sDestine.NGL_DIR_SLASH."aftersow")) {
					include_once($sDestine.NGL_DIR_SLASH."aftersow");
				}
			} else {
				if(!\file_exists($sDestine) && NGL_PATH_PROJECT!=NGL_PATH_FRAMEWORK) {
					if(!\is_dir(NGL_PATH_NUTS)) { \mkdir(NGL_PATH_NUTS, NGL_CHMOD_FOLDER); }
					if(!\is_dir(NGL_PATH_TUTORS)) { \mkdir(NGL_PATH_TUTORS, NGL_CHMOD_FOLDER); }
					$sCamelName = \ucwords($sNewName);
					$sBuffer = \file_get_contents($sSource);
					$sBuffer = \str_replace(
						["<{=LOWERNAME=}>", "<{=UPPERNAME=}>", "<{=CAMELNAME=}>", "<{=LOWERCAMELNAME=}>"],
						[$sNewName, \strtoupper($sNewName), $sCamelName, \lcfirst($sCamelName)],
						$sBuffer
					);
					\file_put_contents($sDestine, $sBuffer);
					\chmod($sDestine, NGL_CHMOD_FILE);
					return true;
				}
				return false;
			}
			return true;
		}
		return false;
	}

	/** FUNCTION {
		"name" : "strBoxAppend",
		"type" : "public",
		"description" : "
			Añade $sAppend a $sString desde el final y hasta el largo de $sString.
			Si $sPrepend es mas corta que $sString se conservarán los caracteres de esta última que no lleguen a ser desplazados
		",
		"parameters" : {
			"$sString" : ["string", "Cadena contenedora"],
			"$sAppend" : ["string", "Cadena de reemplazo"]
		},
		"examples" : {
			"$sString > $sAppend" : "
				$sString = "lorem";
				$sAppend = "sit";
				# emsit
				
			",

			"$sString = $sAppend" : "
				$sString = "lorem";
				$sAppend = "ipsum";
				# ipsum
				
			",

			"$sString < $sAppend" : "
				$sString = "lorem";
				$sAppend = "consectetuer";
				# etuer
			"
		},
		"return" : "string"
	} **/
	public function strBoxAppend($sString, $sAppend) {
		return \substr($sString.$sAppend, (\strlen($sString)*-1));
	}

	/** FUNCTION {
		"name" : "strBoxPrepend",
		"type" : "public",
		"description" : "
			Añade $sPrepend a $sString desde el inicio y hasta el largo de $sString.
			Si $sPrepend es mas corta que $sString se conservarán los caracteres de esta última que no lleguen a ser desplazados
		",
		"parameters" : {
			"$sString" : ["string", "Cadena contenedora"],
			"$sPrepend" : ["string", "Cadena de reemplazo"]
		},
		"examples" : {
			"$sString > $sPrepend" : "
				$sString = "lorem";
				$sPrepend = "sit";
				sitlo
				
			",

			"$sString = $sPrepend" : "
				$sString = "lorem";
				$sPrepend = "ipsum";
				ipsum
				
			",

			"$sString < $sPrepend" : "
				$sString = "lorem";
				$sPrepend = "consectetuer";
				conse
			"
		},
		"return" : "string"
	} **/
	public function strBoxPrepend($sString, $sPrepend) {
		return \substr($sPrepend.$sString, 0, \strlen($sString));
	}

	/** FUNCTION {
		"name" : "strCommon", 
		"type" : "public",
		"description" : "Compara dos cadenas desde el inicio y retorna la subcadena en común",
		"parameters" : { 
			"$sString1" : ["string", "Primer cadena para la comparación"],
			"$sString2" : ["string", "Segunda cadena para la comparación"]
		},
		"return" : "string"
	} **/
	public function strCommon($sString1, $sString2) {
		$aString1 = \str_split($sString1);
		$aString2 = \str_split($sString2);
	
		$aCommon	= [];
		$nString1	= \count($aString1);
		$nString2	= \count($aString2);
		$nLimit		= \min($nString1, $nString2);
		for($x=0; $x<$nLimit; $x++) {
			if($aString1[$x]!=$aString2[$x]) { break; }
			$aCommon[] = $aString1[$x];
		}
		
		return \implode($aCommon);
	}

	/** FUNCTION {
		"name" : "strOperator", 
		"type" : "public",
		"description" : "
			Retorna un operador válido en función su codificación:
			<ul>
				<li><b>eq:</b><em>=</em> (Equal)</li>
				<li><b>noteq:</b><em>!=</em> (Not equal)</li>
				<li><b>lt:</b><em><</em> (Less than)</li>
				<li><b>gt:</b><em>></em> (Greater than)</li>
				<li><b>lteq:</b><em><=</em> (Less than or equal to)</li>
				<li><b>gteq:</b><em>>=</em> (Greater than or equal to)</li>
				<li><b>like:</b><em>LIKE</em></li>
				<li><b>rlike:</b><em>RLIKE</em></li>
				<li><b>and:</b><em>AND</em></li>
				<li><b>or:</b><em>OR</em></li>
				<li><b>xor:</b><em>XOR</em> (Exclusive OR)</li>
				<li><b>in:</b><em>IN</em></li>
				<li><b>notin:</b><em>NOT IN</em></li>
				<li><b>is:</b><em>IS</em></li>
				<li><b>isnot:</b><em>IS NOT</em></li>
			</ul>
			Si <b>$sSign</b> no se encuentra entre las opciones, se retornará el signo =
			Si <b>$sSign</b> no es especificado, se retornará un array asosiativo con todos los operadores
		",
		"parameters" : {
			"$sSign" : ["string", "Código del signo que se quiere obtener"],
			"$bEmpty" : ["boolean", "Deterina si debe retornarse vacio en caso de no encontrar coincidencia", "false"]
		},
		"return" : "mixed"
	} **/
	public function strOperator($sSign=null, $bEmpty=false) {
		$vSigns				= [];
		$vSigns["eq"]		= "=";		/* EQUAL */
		$vSigns["noteq"]	= "!=";		/* NOT EQUAL */
		$vSigns["lt"]		= "<";		/* LESS THAN */
		$vSigns["gt"]		= ">";		/* GREATER THAN */
		$vSigns["lteq"]		= "<=";		/* LESS THAN OR EQUAL TO */
		$vSigns["gteq"]		= ">=";		/* GREATER THAN OR EQUAL TO */
		$vSigns["like"]		= "LIKE";	/* LIKE */
		$vSigns["rlike"]	= "RLIKE";	/* RLIKE */
		$vSigns["and"]		= "AND";	/* AND */
		$vSigns["or"]		= "OR";		/* OR */
		$vSigns["xor"]		= "XOR";	/* EXCLUSIVE OR */
		$vSigns["in"]		= "IN";		/* IN */
		$vSigns["notin"]	= "NOT IN";	/* NOT IN */
		$vSigns["is"]		= "IS";		/* IS */
		$vSigns["isnot"]	= "IS NOT";	/* IS NOT */

		if($sSign!==null) {
			$sSign = \trim(\strtolower($sSign));
			if(isset($vSigns[$sSign])) {
				return $vSigns[$sSign];
			} else {
				return (!$bEmpty) ? "=" : "";
			}
		}
		
		return $vSigns;
	}

	/** FUNCTION {
		"name" : "strSizeDecode", 
		"type" : "public",
		"description" : "Retorna el valor $sSize en bytes. Cuando existan decimales se redondeará el resultado",
		"parameters" : {
			"$sSize" : ["string", "Tamaño a convertir"]
		},
		"return" : "string"
	} **/
	public function strSizeDecode($sSize) {
		$aUnits = ["B"=>0, "K"=>1, "M"=>2, "G"=>3, "T"=>4, "P"=>5, "E"=>6, "Z"=>7, "Y"=>8];
		$sUnit = \preg_replace("/[^bkmgtpezy]/i", "", $sSize);
		$sUnit = \strtoupper($sUnit[0]);
		$nSize = \preg_replace("/[^0-9\.]/", "", $sSize);

		if(!empty($sUnit) && isset($aUnits[$sUnit])) {
			return \round($nSize * \pow(1024, $aUnits[$sUnit]));
		} else {
			return \round($nSize);
		}
	}

	/** FUNCTION {
		"name" : "strSizeEncode", 
		"type" : "public",
		"description" : "Retorna el valor $nBytes con el formato KB o MB o GB etc",
		"parameters" : {
			"$nBytes" : ["int", "Número de bytes"],
			"$nDecimals" : ["int", "Cantidad de decimales despues de la coma", 2]
		},
		"return" : "string"
	} **/
	public function strSizeEncode($nBytes, $nDecimals=2) {
		$aUnits = ["B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"];
		$nExp = ($nBytes>0) ? \floor(\log($nBytes) / \log(1024)) : 0;
		return \sprintf("%.".$nDecimals."f".$aUnits[$nExp], ($nBytes / \pow(1024, \floor($nExp))));
	}

	/** FUNCTION {
		"name" : "strToFloat", 
		"type" : "public",
		"description" : "Intenta retornar un número decimal a partir de una cadena",
		"parameters" : {
			"$sNumber" : ["string", "Número en formato cadena"],
			"$sDecimal" : ["string", "Separador de decimales", "."]
		},
		"return" : "float"
	} **/
	public function strToFloat($sNumber, $sDecimal=",") {
		$sDecimal = \preg_quote($sDecimal);
		$sNumber = \preg_replace("/[^0-9".$sDecimal."]/", "", $sNumber);
	
		$aNumber = \preg_split("/".$sDecimal."(?=[^".$sDecimal."]*$)/", $sNumber);
		if(isset($aNumber[1])) {
			$aNumber[0] = \preg_replace("/[\.,]/", "", $aNumber[0]);
			$sNumber = \implode(".", $aNumber);
		}
	
		return floatval($sNumber);
	}

	/** FUNCTION {
		"name" : "strToVars", 
		"type" : "public",
		"description" : "Busca cadenas del tipo {:cadena:} e intenta reemplazarlas por los valores de $aVars",
		"parameters" : {
			"$sString" : ["string", "Cadena"],
			"$aVars" : ["array", "Array asociativo con los posibles valores para $sString"]
		},
		"examples" : {
			"Reemplazo" : "
				$sString = "Hola {:login.username:}";
				$aVars = array("login"=>array("username"=>"John"));
				sitlo
				
			"
		},
		"return" : "string"
	} **/
	function strToVars($sString, $aVars) {
		\preg_match_all("/\{:([a-z][a-z0-9_\.]*):\}/is", $sString, $aMatchs, PREG_SET_ORDER);
		foreach($aMatchs as $aColon) {
			$mValue = $aVars;
			$aVar = \explode(".", $aColon[1]);
			foreach($aVar as $sIndex) {
				if(\key_exists($sIndex, $mValue)) {
					$mValue = $mValue[$sIndex];
				} else {
					break;
				}
			}
	
			if(!\is_array($mValue) && !\is_object($mValue)) {
				$sString = \str_replace($aColon[0], $mValue, $sString);
			}
		}
	
		return $sString;
	}

	/** FUNCTION {
		"name" : "tokenDecode", 
		"type" : "public",
		"description" : "Decodifica una cadena codificada con <b>tokenEncode</b>. Si el token esta en una sola linea se lo tratara como un token corto",
		"parameters" : {
			"$sToken" : ["string", "Cadena codificada"],
			"$sKey" : ["string", "Código se seguridad"]
		},
		"seealso" : ["nglFn::tokenEncode"], 
		"return" : "string"
	} **/
	public function tokenDecode($sToken, $sKey) {
		if(strlen($sToken)<64) { return false; }

		$aToken = self::call()->explodeTrim("\n", $sToken);
		if(\count($aToken)>1) {
			\array_shift($aToken);
			\array_pop($aToken);
			$sToken = \implode($aToken);
		} else {
			$sToken = \preg_replace("/\/(.*?)\//is", "", $sToken);
		}

		// firma
		$sSign = "";
		$sLastLine = \substr($sToken, -64);
		for($x=2; $x<64; $x+=4) {
			$sSign .= $sLastLine[$x];
			$sSign .= $sLastLine[$x+1];
		}
		$aSign = \preg_split("/[G-Z]/is", $sSign, 3);
		$sSign = $aSign[0];
		$nLength = self::call()->hex2dec($aSign[1]);

		// token
		$sToken = \substr($sToken, 0, -64);
		
		// secure arrange
		$sSignKey = self::call()->hex2dec($sSign);
		$sToken	= self::call()->arrange($sToken, self::call("unicode")->str_split($sSignKey, 2));

		// chequeo firma
		if(\substr(\md5($sToken),0,-(\strlen($aSign[1])+2)) != $sSign) {
			return false;
		}

		// key
		$sKey 		= \sha1($sKey);
		$sKey		= self::call()->hex2dec($sKey);
		$aKey 		= self::call("unicode")->str_split($sKey, 2);
		$aKeyRev 	= \array_reverse($aKey);

		// token
		$sToken = \preg_replace("/\s/", "", $sToken);
		$aToken = self::call("unicode")->str_split($sToken, 2);

		$x = 0;
		$aClear = [];
		while(\count($aClear)<\count($aToken)) {
			$nKey = \next($aKeyRev);
			if($nKey===false) { \reset($aKeyRev); $nKey = \next($aKeyRev); };
			$x += \ceil($nKey/2);
			$aClear[] = $aToken[$x];
			if(\count($aClear)==$nLength) { break; }
			$x++;
		}

		// arrange
		$aSource = self::call()->arrange($aClear, $aKey);
		
		// source to dec
		foreach($aSource as &$sChar) {
			$sChar = \chr(self::call()->hex2dec($sChar, 2));
		}
		
		return \implode($aSource);
	}

	/** FUNCTION {
		"name" : "tokenEncode", 
		"type" : "public",
		"description" : "Codifica el valor de <b>$sSource</b> en un token aplicando el código de seguridad <b>$sKey</b>",
		"parameters" : {
			"$sSource" : ["string", "Cadena que se desea tokenizar"],
			"$sKey" : ["string", "Código se seguridad"],
			"$sTokenTitle" : ["string", "Título del token, este aparecera en la línea de encabezado", "NGL TOKEN"]
		},
		"seealso" : ["nglFn::tokenDecode"],
		"return" : "string"
	} **/
	public function tokenEncode($sSource, $sKey, $sTokenTitle="NGL TOKEN") {
		$sChars = "0123456789aAbBcCdDeEfFgGhHiIjJkKlLmMnNoOpPqQrRsStTuUvVwWxXyYzZ";

		// source to hex
		$aSource = self::call("unicode")->str_split($sSource);
		foreach($aSource as &$sChar) {
			$sChar = self::call()->dec2hex(ord($sChar), 2);
		}

		// disarrange
		$sKey 		= \sha1($sKey);
		$sKey		= self::call()->hex2dec($sKey);
		$aKey 		= \str_split($sKey, 2);
		$aSource	= self::call()->disarrange($aSource, $aKey);

		// echo implode("", $aSource)."\n";

		// fill token
		$aKey = \array_reverse($aKey);
		$aToken = [];
		foreach($aSource as $sChar) {
			$nKey = \next($aKey);
			if($nKey===false) { \reset($aKey); $nKey = \next($aKey); };

			$nFill = ($sTokenTitle!==false) ? \ceil($nKey/2) : 1;
			for($x=0;$x<$nFill;$x++) {
				$aToken[] = $sChars[\rand(0,61)].$sChars[\rand(0,61)];
			}
			$aToken[] = $sChar;
		}

		if((\count($aToken)*2)%64) {
			$nFill = 64 - ((\count($aToken)*2)%64);
			for($x=0;$x<$nFill;$x++) {
				$aToken[] = $sChars[\rand(0,61)];
			}
		}
		
		// token data
		$sTokenData	= \implode("", $aToken);
		
		// length
		$sLength = $sChars[\rand(22,61)].self::call()->dec2hex(\count($aSource)).$sChars[\rand(22,61)];

		// sign
		$y = 2;
		$sSign = \md5($sTokenData);
		$sSign = $sSignKey = \substr($sSign,0,-strlen($sLength));
		$sSign .= $sLength;

		$sLastLine = self::call()->unique(32).self::call()->unique(32);
		for($x=0; $x<32; $x+=2) {
			$sLastLine[$y] = $sSign[$x];
			$sLastLine[$y+1] = $sSign[$x+1];
			$y+=4;
		}

		// secure disarrange
		$sSignKey2 = $sSignKey;
		$sSignKey = self::call()->hex2dec($sSignKey);
		$aKey = self::call("unicode")->str_split($sSignKey, 2);
		$sTokenData	= self::call()->disarrange($sTokenData, $aKey);

		// token
		if($sTokenTitle===false) {
			$sToken = $sTokenData.$sLastLine;
		} else {
			$sTokenData	= \implode("\n", self::call("unicode")->str_split($sTokenData, 64));
			$sTokenTitle = \substr($sTokenTitle, 0, 58); 
			$sToken	 = "/-- ".\str_pad($sTokenTitle." ", 58, "-")."-/\n";
			$sToken	.= $sTokenData."\n";
			$sToken	.= $sLastLine."\n";
			$sToken	.= "/------------------------------------------------- NGL TOKEN --/";
		}

		return $sToken;
	}

	/** FUNCTION {
		"name" : "treeWalk", 
		"type" : "public",
		"description" : "
			Aplica una función de usuario recursivamente a cada miembro del arbol,
			entrando en cada uno de los nodos $sChildrenNode. En cada interacción se ejecutará los métodos:
			<ul>
				<li>$fFunction</li>
				<li>$vEvents[branchOpen]</li>
				<li>$vEvents[nodeOpen]</li>
				<li>$vEvents[nodeClose]</li>
				<li>$vEvents[branchClose]</li>
			</ul>	
		",
		"parameters" : {
			"$aData" : ["array", "Array de datos"],
			"$fFunction" : ["function", "
				Función del usuario que se ejecutará para cada nodo. En cada ejecución se pasarán los siguientes argumentos:<br />
				<ul>
					<li>datos del nodo</li>
					<li>nivel de profundidad</li>
					<li>booleano que define si el nodo actual es el primer nodo de la rama</li>
					<li>booleano que define si el nodo actual es el último nodo de la rama</li>
				</ul>
			"],
			"$sChildrenNode" : ["string", "Nombre de nodo que contiene a los hijos", "_children"]
			"$vEvents" : ["array", "
				Array de funciones que se ejecutaran ante cada evento:
				A estas funciones se les pasarán 2 argumentos, el nodo activo y el nivel de profundidad
				<ul>
					<li><b>branchOpen:</b> se ejecutara cada vez que una nueva rama sea abierta</li>
					<li><b>branchClose:</b> se ejecutara cada vez que una rama se cierre</li>
					<li><b>nodeOpen:</b> se ejecutara justo antes de ejecutar <strong>$fFunction</strong></li>
					<li><b>nodeClose:</b> se ejecutara cada vez que un nodo se cierre</li>
				</ul>
			", "null"],
		},
		
		"examples" : {
			"Formato de árbol #1" : "
				$aFamily = array(
				→ array(
				→→ "name" => "Emily Summer",
				→→ "age" => 78,
				→→ "_children" => array(
				→→→→ array(
				→→→→→ "name"=>"Marge Charles",
				→→→→→ "age" => 50,
				→→→→→ "_children" => array(
				→→→→→→ array("name"=>"Sara Smith", "age"=>20),
				→→→→→→ array("name"=>"Max Smith", "age"=>17)
				→→→→ )
				→→→ )
				→→ )
				→ ),
				→
				→ array(
				→→ "name" => "Rod Smith",
				→→ "age" => 80,
				→→ "_children" => array(
				→→→→ array(
				→→→→→ "name"=>"John Smith",
				→→→→→ "age" => 54,
				→→→→→ "_children" => array(
				→→→→→→ array("name"=>"Sara Smith", "age"=>20),
				→→→→→→ array("name"=>"Max Smith", "age"=>17)
				→→→→ )
				→→→ ),

				→→→ array(
				→→→→ "name"=>"Susan Smith",
				→→→→ "age" => 49,
				→→→→ "_children" => array(
				→→→→→ array("name"=>"Ralph Astley")
				→→→→ )
				→→→ )
				→→ )
				→ )
				);
			",

			"Formato de árbol #2" : "
				$aFamily = array(
				→ "name" => "Emily Summer",
				→ "age" => 78,
				→ "_children" => array(
				→→→ array(
				→→→→ "name"=>"Marge Charles",
				→→→→ "age" => 50,
				→→→→ "_children" => array(
				→→→→→ array("name"=>"Sara Smith", "age"=>20),
				→→→→→ array("name"=>"Max Smith", "age"=>17)
				→→→ )
				→→ )
				→ )
				);
			",
			
			"Ejemplo de función del usuario" : "
				$aLs = $ngl("files")->ls("mydocuments", "*", "info", true);

				echo "<pre>";
				$sColumn = "basename";
				$aList = $ngl()->treeWalk($aLs, function($aNode, $nLevel, $bFirst, $bLast) use ($sColumn) {
						$sOutput  = ($nLevel) ? \str_repeat("│   ", $nLevel) : "";
						$sOutput .= ($bLast) ? "└── " : "├── ";
						$sOutput .= (($aFile["type"]=="dir") ? $aFile[$sColumn]."/" : $aFile[$sColumn]);
						$sOutput .= "\\n";
						return $sOutput;
					}
				);
				echo \implode($aList);
				echo "</pre>";
				
				# salida
				mydocuments/
				├── excel/
				├── mp3/
				│   ├── rock/
				│   └── pop/
				└── word/
					└── personal/
			"
		},
		"return" : "void"
	} **/
	public function treeWalk($aData, $fFunction=null, $sChildrenNode="_children", $vEvents=[]) {
		if(!\is_array($aData)) { return false; }

		$mOutput = [];
		if($fFunction===null) { $fFunction = (function() { $aArgs = \func_get_args(); if(isset($aArgs[0])) { return self::dump($aArgs[0]); }}); }
		$fBranchOpen	= (isset($vEvents["branchOpen"])) ? $vEvents["branchOpen"] : null;
		$fBranchClose 	= (isset($vEvents["branchClose"])) ? $vEvents["branchClose"] : null;
		$fNodeOpen		= (isset($vEvents["nodeOpen"])) ? $vEvents["nodeOpen"] : null;
		$fNodeClose		= (isset($vEvents["nodeClose"])) ? $vEvents["nodeClose"] : null;
		
		if(empty($sChildrenNode)) { $sChildrenNode = "_children"; }

		$nData = \count($aData);
		$aKeys = \array_keys($aData);
		$nData = \count($aKeys);
		$aTreeIndex = [];
		
		if($fBranchOpen!==null) { $mOutput[] = $fBranchOpen($aData[$aKeys[0]], 0); }
		for($x=0; $x<$nData; $x+=1) {
			if(!isset($aData[$aKeys[$x]])) { continue; }
			$aRow = $aData[$aKeys[$x]];

			$nDeep = \count($aTreeIndex);
			if($fNodeOpen!==null) { $mOutput[] = $fNodeOpen($aRow, $nDeep); }
			$mOutput[] = $fFunction($aRow, $nDeep, ($x==0), ($x==$nData-1));
			
			if(isset($aRow[$sChildrenNode]) && \is_array($aRow[$sChildrenNode])) {
				if($fBranchOpen!==null) { $mOutput[] = $fBranchOpen($aRow, \count($aTreeIndex)); }
				if(\count($aRow[$sChildrenNode])) {
					$aTreeIndex[] = [$aData, $x];
					$aData = $aRow[$sChildrenNode];
					$aKeys = \array_keys($aData);
					$nData = \count($aData);
					$x = -1;
					continue;
				}
				
				// rama vacia
				if($fBranchClose!==null) { $mOutput[] = $fBranchClose($aRow, \count($aTreeIndex)); }
			}

			if($fNodeClose!==null) { $mOutput[] = $fNodeClose($aRow, $nDeep); }
			
			if($x==$nData-1) {
				while(!($nData>$x+1) && \count($aTreeIndex)) {
					$sTreeTmp = \array_pop($aTreeIndex);
					$aData = $sTreeTmp[0];
					$aKeys = \array_keys($aData);
					$nData = \count($aData);
					$x = $sTreeTmp[1];
					
					$nDeep = \count($aTreeIndex);
					if($fBranchClose!==null) { $mOutput[] = $fBranchClose($aRow, $nDeep); }
					if($fNodeClose!==null) { $mOutput[] = $fNodeClose($aRow, $nDeep); }
				}
				continue;
			}
		}
		if($fBranchClose!==null) { $mOutput[] = $fBranchClose($aRow, 0); }
		
		return $mOutput;
	}

	/** FUNCTION {
		"name" : "truelize", 
		"type" : "public",
		"description" : "Crea un nuevo Array combinando los valores de $aSource como claves y el booleano TRUE como valor de cada uno.",
		"parameters" : {
			"$aSource" : ["array", "array de datos"]
			"$bTrim" : ["boolean", "Determina si se debe aplicar TRIM sobre los valores de <b>$aSource</b> antes de ser usados como claves."]
		},
		"examples": {
			"ejemplo #1" : "
				#array original
				$input = array("A", "B", "C", "D");

				# transformación
				$output = $ngl()->truelize($input);

				#array de salida
				Array (
					["A"] => true
					["B"] => true
					["C"] => true
					["D"] => true
				)
			"
		},
		"return" : "array"
	} **/
	public function truelize($aSource, $bTrim=true) {
		\reset($aSource);
		$aReturn = [];
		foreach($aSource as $mValue) {
			$mValue = ($bTrim) ? \trim($mValue) : $mValue;
			$aReturn[$mValue] = true; 
		}
		
		return $aReturn;
	}

	/** FUNCTION {
		"name" : "unaccented",
		"type" : "public",
		"description" : "Reemplaza los caracteres acentuados por su equivalente sin acento",
		"parameters" : { "$sAccented" : ["string", "Cadena acentuada"] },
		"return" : "string"
	} **/
	public function unaccented($sAccented) {
		$vAccented = self::call("sysvar")->ACCENTED;
		return \str_replace(\array_keys($vAccented), $vAccented, $sAccented);
	}

	/** FUNCTION {
		"name" : "unescape",
		"type" : "public",
		"description" : "
			Reemplaza caracteres escapados por su equivalente real
				\' – comilla simple
				\" – comilla doble
				\\ – Backslash.
				\n – nueva linea
				\t – tabulacion
				\r – retorno de carro
		",
		"parameters" : { "$sEscaped" : ["string", "Cadena escapada"] },
		"return" : "string"
	} **/
	public function unescape($sEscaped) {
		$aEscaped = ["\\'", '\\"', "\\\\", "\\n", "\\r", "\\t"];
		$aUnescaped = ["'", '"', "\\", "\n", "\r", "\t"];
		return \str_replace($aEscaped, $aUnescaped, $sEscaped);
	}
	
	/** FUNCTION {
		"name" : "unique",
		"type" : "public",
		"description" : "Genera una cadena aleatoria de 4 a 4096 caracteres que matchea con el patrón: [a-zA-Z][a-zA-Z0-9]{4,4096}",
		"parameters" : { "$nLength" : ["int", "Loguitud de la cadena", "6"] },
		"return" : "string"
	} **/
	public function unique($nLength=6) {
		if($nLength<4) { $nLength = 4; }
		if($nLength>4096) { $nLength = 4096; }

		$sUnique = "";
		$nLoops = \ceil($nLength/86);
		for($x=0; $x<$nLoops; $x++) {
			$sHash		= \microtime();
			$sSalt		= \md5($sHash);
			$nRounds	= \rand(1000, 2000);
			$sHash		= \crypt($sHash, '$6$rounds='.$nRounds.'$'.$sSalt.'$');
			$nDollar	= \strrpos($sHash, '$');
			$sHash		= \substr($sHash, $nDollar+1);
			
			$sUnique.= $sHash;
		}

		$sUnique = \substr($sUnique, 0, $nLength);
		$sUnique = \preg_replace_callback(
			"/[^0-9a-zA-Z]/",
			function($aMatchs) {
				return (!\ord($aMatchs[0])%2) ? \chr(\rand(65, 90)) : \chr(\rand(97, 122));
			},
			$sUnique
		);

		// primer caracter NO numerico
		$sUnique[0] = (\ord($sUnique[0])%2) ? \chr(\rand(65, 90)) : \chr(\rand(97, 122));

		return $sUnique;
	}

	/** FUNCTION {
		"name" : "uriDecode", 
		"type" : "public",
		"description" : "
			Decodifica una cadena codificada con <b>uriEncode</b>.
			El valor retornado podrá ser un string o un array, dependiendo del valor original de <b>$sString</b>
		",
		"parameters" : { "$sString" : ["string", "Cadena codificada"] },
		"return" : "string o array"
	} **/
	public function uriDecode($sString) {
		if($sString = \strtr($sString, "-._", "+/=")) {
			if($sString = \base64_decode($sString)) {
				$sString = \stripslashes($sString);
				if($sString = \gzuncompress($sString)) {
					$mValue = \unserialize($sString);
					return $mValue;
				}
			}
		}
		
		return false;
	}

	/** FUNCTION {
		"name" : "uriEncode", 
		"type" : "public",
		"description" : "Codifica una cadena o array para que pueda ser enviado de manera segura por GET o POST",
		"parameters" : { "$mValue" : ["mixed", "Cadena o array que se quiere codificar"] },
		"return" : "string"
	} **/
	public function uriEncode($mValue) {
		$sString = \serialize($mValue);
		$sString = \gzcompress($sString, 9);
		$sString = \addslashes($sString);
		$sString = \base64_encode($sString);
		$sString = \strtr($sString, "+/=", "-._");
		
		return $sString;
	}

	/** FUNCTION {
		"name" : "urlExists", 
		"type" : "public",
		"description" : "
			Comprueba si existe una URL. El chequeo se intenta hacer mediante <b>get_headers</b> o <b>curl_init</b>, 
			si no pueden llevarse a cabo retorna <b>NULL</b>
		",
		"parameters" : { "$sURL" : ["string", "URL a chequear"] },
		"return" : "boolean"
	} **/
	public function urlExists($sURL) {
		if($vHeaders = @\get_headers($sURL)) {
			$bExists = ($vHeaders[0]=="HTTP/1.1 404 Not Found") ? false : true;
		} else if(\function_exists("curl_version")) {
			$bExists = \is_resource(\curl_init($sURL)) ? false : true;
		} else {
			$bExists = null;
		}

		return $bExists;
	}
}

?>