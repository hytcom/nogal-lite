<?php

namespace nogal;

/** CLASS {
	"name" : "nglDBSQLiteQuery",
	"object" : "sqliteq",
	"type" : "instanciable",
	"revision" : "20160201",
	"extends" : "nglBranch",
	"interfaces" : "iNglDBQuery",
	"description" : "Controla los resultados generados por consultas a la bases de datos SQLite.",
	"configfile" : "sqliteq.conf",
	"variables" : {
		"$db" : ["private", "Objeto SQLite3Result"],
		"$cursor" : ["private", "Modos de INSERT y UPDATE"]
	},
	"arguments": {
		"column" : ["string", "
			Nombre de una columna del grupo de resultados.
			En el método <b>getAll</b>, cuando el nombre este presedido de un <b>#</b>, el valor de la columna será utilizado como indice asociativo.
			Cuando el nombre este presedido de un <b>@</b>, el resultado será tratado con <b>nglFn::arrayGroup</b> y el valor de la columna será utilizado como indice asociativo.
		", "null"],
		"get_group" : ["array", "
			Estructura de agrupamiento de <b>nglFn::arrayGroup</b> utilizada en el método getAll cuando column está precedido por una <b>@</b>
		", "null"],
		"get_mode" : ["string", "tipo de modo GET. Valores admitidos:
			<ul>
				<li><b>assoc:</b> devuelve un array indexado por el nombre de columna</li>
				<li><b>num:</b> devuelve un array indexado por el número de columna, empezando por la columna 0</li>
				<li><b>both:</b> devuelve un array indexado tanto por el nombre como por el número de columna empezando por la columna 0</li>
			</ul>
		", "assoc"],
		"link" : ["resource", "Puntero del driver de base de datos", "null"],
		"query" : ["object", "Resultado de la última consulta ejecutada", "null"],
		"sentence" : ["string", "Última consulta ejecutada", "null"],
		"query_time" : ["string", "Tiempo que tomó la última consulta ejecutada", "null"]
	},
	"attributes": {
		"sql" : ["string", "Sentencia SQL ejecutada"],
		"time" : ["float", "Tiempo que tomó la ejecución"],
		"crud" : ["string", "Tipo de sentencia CRUD (SELECT, INSERT, UPDATE, REPLACE or DELETE) o false en caso de otra sentencia"]
	}
} **/
class nglDBSQLiteQuery extends nglBranch implements iNglDBQuery {

	private $db		= null;
	private $cursor = null;
	
	final protected function __declareArguments__() {
		$vArguments							= [];
		$vArguments["column"]				= ['$mValue', null];
		$vArguments["get_mode"]				= ['$this->GetMode($mValue)', \SQLITE3_ASSOC];
		$vArguments["get_group"]			= ['$mValue', null];
		$vArguments["link"]					= ['$mValue', null];
		$vArguments["query"]				= ['$mValue', null];
		$vArguments["sentence"]				= ['(string)$mValue', null];
		$vArguments["query_time"]			= ['$mValue', null];
		return $vArguments;
	}

	final protected function __declareAttributes__() {
		$vAttributes						= [];
		$vAttributes["_allrows"]			= null;
		$vAttributes["_columns"]			= null;
		$vAttributes["_rows"]				= null;
		$vAttributes["crud"]				= null;
		$vAttributes["sql"]					= null;
		$vAttributes["time"]				= null;
		
		return $vAttributes;
	}

	final protected function __declareVariables__() {
	}

	final public function __init__() {
	}
	
	/** FUNCTION {
		"name" : "allrows",
		"type" : "public",
		"description" : "retorna el número de registros del conjunto de resultados ignorando los valores LIMIT",
		"input": "_allrows,crud,sql",
		"output": "_allrows",
		"return": "int"
	} **/
	public function allrows() {
		if($this->attribute("_allrows")!==null) { return $this->attribute("_allrows"); }

		$nRows = null;
		if($this->attribute("crud")=="SELECT") {
			$sSQL = $this->attribute("sql");
			$sSQL = \trim($sSQL);
			if(\preg_match("/LIMIT *[0-9]+ *,? *[0-9]*$/i", $sSQL)) {
				$sSQL = \preg_replace("/LIMIT *[0-9]+ *,? *[0-9]*$/i", "", $sSQL);
			
				$sSQL = "SELECT COUNT(*) FROM (".$sSQL.")";
				$getrows = $this->db->query($sSQL);
				$nRows = (int)$getrows->fetchArray(\SQLITE3_NUM)[0];
				$getrows->finalize();
			} else {
				$nRows = (int)$this->rows();
			}
		}

		$this->attribute("_allrows", $nRows);
		return $nRows;
	}

	/** FUNCTION {
		"name" : "columns",
		"type" : "public",
		"description" : "Retorna un array con los nombre de las columnas presentes en el resultado",
		"input": "columns",
		"output": "columns",
		"return": "array"
	} **/
	public function columns() {
		if($this->attribute("columns")!==null) { return $this->attribute("columns"); }

		$aGetColumns = [];
		$nCols = $this->cursor->numColumns();
		if($nCols) {
			for($x=0; $x<$nCols; $x++) {
				$aGetColumns[] = $this->cursor->columnName($x);
			}
		}

		$this->attribute("columns", $aGetColumns);
		return $aGetColumns;
	}

	/** FUNCTION {
		"name" : "count",
		"type" : "public",
		"description" : "
			Devuelve el número de filas involucradas en la última consulta ejecutada.
			Si la consulta es de los tipos <b>INSERT, UPDATE, REPLACE o DELETE</b> devolverá la cantidad de filas afectadas.
			Si en cambio se trata de un conjunto de resultados, devolverá la cantidad de filas del mismo.
		",
		"input": "crud,rows,sql",
		"output": "rows",
		"return": "int"
	} **/
	public function count() {
		if($this->attribute("rows")!==null) { return $this->attribute("rows"); }

		if(\in_array($this->attribute("crud"), ["INSERT", "UPDATE", "REPLACE", "DELETE"])) {
			$nRows = $this->db->changes();
		} else {
			$sSQL = $this->attribute("sql");
			$rows = $this->db->query("SELECT COUNT(*) FROM (".$sSQL.")");
			$nRows = $rows->fetchArray(\SQLITE3_NUM)[0];
		}
		
		$this->attribute("rows", $nRows);
		return $nRows;
	}

	/** FUNCTION {
		"name" : "destroy",
		"type" : "public",
		"description" : "Libera la memoria asociada con el identificador del resultado y destruye el objeto",
		"return": "boolean"
	} **/
	public function destroy() {
		if(!\is_bool($this->cursor)) { $this->cursor->finalize(); }
		return parent::__destroy__();
	}	

	/** FUNCTION {
		"name" : "get",
		"type" : "public",
		"description" : "
			Obtiene una fila de resultados en forma de array y avanza el puntero.
			Cuando se especifique <b>$sColumn</b> y el valor sea el nombre una columna del grupo de resultados, se retornará unicamente el valor de dicha columna
		",
		"parameters" : { 
			"$sColumn" : ["string", "", "argument::column"],
			"$sMode" : ["string", "", "argument::get_mode"]
		},
		"examples": {
			"conexión": "
				$foo = $ngl("sqlite.foobar");
				$foo->base = "shop.sqlite";
				$foo->connect();
				$bar = $foo->query("SELECT * FROM `users`");
				print_r($bar->get());
			"
		},
		"return" : "mixed"
	} **/
	public function get() {
		list($sColumn,$sMode) = $this->getarguments("column,get_mode", \func_get_args());
		$aRow = $this->cursor->fetchArray($sMode);
		if(!empty($sColumn) && $sColumn[0]=="#") { $sColumn = \substr($sColumn, 1); }
		return ($sColumn!==null && $aRow!==false && \array_key_exists($sColumn, $aRow)) ? $aRow[$sColumn] : $aRow;
	}

	/** FUNCTION {
		"name" : "getall",
		"type" : "public",
		"description" : "
			Obtiene todas las filas de resultados en forma de array bidimensional.
			Cuando se especifique <b>$sColumn</b> y el valor sea el nombre una columna del grupo de resultados, se retornará unicamente el valor de dicha columna;
			excepto cuanto el nombre esté presedido de un <b>#</b>, en este caso se retornará un array multidimensional donde el valor de la columna será utilizado como indice asociativo del primer nivel. En un segundo nivel se agruparan los registros que tengan igual valor en el campo <b>$sColumn</b>.
			Este método reinicia el conjunto de resultados a la primera fila.
		",
		"parameters" : { 
			"$sColumn" : ["string", "", "argument::column"],
			"$sMode" : ["string", "", "argument::get_mode"]
		},
		"examples": {
			"conexión": "
				$foo = $ngl("sqlite.foobar");
				$foo->base = "shop.sqlite";
				$foo->connect();
				$bar = $foo->query("SELECT * FROM `users`");
				
				[b]print_r($bar->getall());[/b]
				Array (
					0 => Array(id => 1, name => Juan, age => 36),
					1 => Array(id => 2, name => Pedro, age => 28),
					2 => Array(id => 3, name => Manuel, age => 36)
				);
				
				[b]print_r($bar->getall(name));[/b]
				Array (
					0 => Juan,
					1 => Pedro,
					2 => Manuel
				);

				[b]print_r($bar->getall(#age));[/b]
				Array (
					36 => Array(
						0 => Array(id => 1, name => Juan, age => 36),
						1 => Array(id => 3, name => Manuel, age => 36)
					),
					28 => Array(id => 2, name => Pedro, age => 28)
				);
			"
		},
		"return" : "mixed"
	} **/
	public function getall() {
		list($sColumn,$sMode,$aGroup) = $this->getarguments("column,get_mode,get_group", \func_get_args());
		
		$bIndexMode = false;
		if(!empty($sColumn) && $sColumn[0]=="#") {
			$sColumn = \substr($sColumn, 1);
			$bIndexMode = true;
		}

		$bGroupByMode = false;
		if(!empty($sColumn) && $sColumn[0]=="@") {
			$sGroupBy = \substr($sColumn, 1);
			$sColumn = null;
			$bGroupByMode = true;
			$aGroup = (\is_array($aGroup)) ? $aGroup : null;
		}

		$this->reset();
		$aRow = $this->cursor->fetchArray($sMode);

		$aGetAll = [];
		if($sColumn!==null && $aRow!==false && !\array_key_exists($sColumn, $aRow)) { return $aGetAll; }
		$this->reset();

		if($sColumn!==null) {
			if($bIndexMode) {
				while($aRow = $this->cursor->fetchArray($sMode)) {
					if(isset($aGetAll[$aRow[$sColumn]])) {
						if(!isset($aMultiple[$aRow[$sColumn]])) {
							$aGetAll[$aRow[$sColumn]] = [$aGetAll[$aRow[$sColumn]]];
							$aMultiple[$aRow[$sColumn]] = true;
						}
						$aGetAll[$aRow[$sColumn]][] = $aRow;
					} else {
						$aGetAll[$aRow[$sColumn]] = $aRow;
					}
				}
			} else {
				while($aRow = $this->cursor->fetchArray($sMode)) {
					$aGetAll[] = $aRow[$sColumn];
				}			
			}
		} else {
			while($aRow = $this->cursor->fetchArray($sMode)) {
				$aGetAll[] = $aRow;
			}
		}

		if($bGroupByMode) {
			$aGetAll = self::call()->arrayGroup($aGetAll, $aGroup);
		}
		
		$this->reset();
		return $aGetAll;
	}

	/** FUNCTION {
		"name" : "GetMode",
		"type" : "protected",
		"description" : "Selecciona el modo de salida para los métodos <b>get</b> y <b>getall</b>",
		"parameters" : { "$sMode" : ["mixed", "", "argument::get_mode"]},
		"return": "int"
	} **/
	protected function GetMode($sMode) {
		$aModes 				= [];
		$aModes["both"] 		= \SQLITE3_BOTH;
		$aModes["num"] 			= \SQLITE3_NUM;
		$aModes["assoc"] 		= \SQLITE3_ASSOC;
		$aModes[3] 				= \SQLITE3_BOTH;
		$aModes[2] 				= \SQLITE3_NUM;
		$aModes[1] 				= \SQLITE3_ASSOC;

		$sMode = \strtolower($sMode);
		return (isset($aModes[$sMode])) ? $aModes[$sMode] : \SQLITE3_ASSOC;
	}
	
	/** FUNCTION {
		"name" : "getobj",
		"type" : "public",
		"description" : "Obtiene una fila de resultados en forma de objeto stdClass y avanza el puntero.",
		"examples": {
			"conexión": "
				$foo = $ngl("sqlite.foobar");
				$foo->base = "shop.sqlite";
				$foo->connect();
				$bar = $foo->query("SELECT * FROM `users`");
				var_dump($bar->getobj());
			"
		},
		"return" : "object"
	} **/
	public function getobj() {
		return (object)$this->cursor->fetchArray(\SQLITE3_ASSOC);
	}

	/** FUNCTION {
		"name" : "free",
		"type" : "public",
		"description" : "Libera la memoria asociada con el identificador del resultado",
		"return": "boolean"
	} **/
	public function free() {
		if(!\is_bool($this->cursor)) { $this->cursor->finalize(); }
		return $this;
	}

	/** FUNCTION {
		"name" : "lastid",
		"type" : "public",
		"description" : "Retorna el ID de la fila de la sentencia INSERT más reciente realizada en la base de datos",
		"input": "crud",
		"return": "int"
	} **/
	public function lastid() {
		if($this->attribute("crud")=="INSERT") {
			return $this->db->lastInsertRowID();
		} else {
			return null;
		}
	}

	/** FUNCTION {
		"name" : "load",
		"type" : "public",
		"description" : "Carga la ultima consulta ejecutada del driver.",
		"parameters" : { 
			"$link" : ["object", "", "argument::link"],
			"$query" : ["object", "", "argument::query"]
			"$sQuery" : ["string", "", "argument::sentence"]
			"$nQueryTime" : ["int", "", "argument::query_time"]
		},
		"output": "crud,sql,time",
		"return": "boolean"
	} **/
	public function load() {
		list($link, $query, $sQuery, $nQueryTime) = $this->getarguments("link,query,sentence,query_time", \func_get_args());
		
		$this->db = $link;
		$this->cursor = $query;
		$this->attribute("sql", $sQuery);
		$this->attribute("time", $nQueryTime);
		
		$sSQL = $sQuery;
		$sSQL = \preg_replace("/^[^A-Z]*/i", "", $sSQL);
		$sSQLCommand = \strtok($sSQL, " ");
		$sSQLCommand = \strtoupper($sSQLCommand);
		
		if(\in_array($sSQLCommand, ["SELECT", "INSERT", "UPDATE", "REPLACE", "DELETE"])) {
			$this->attribute("crud", $sSQLCommand);
		} else {
			$this->attribute("crud", false);
		}
		
		return $this;
	}
	
	/** FUNCTION {
		"name" : "reset",
		"type" : "public",
		"description" : "Reinicia el conjunto de resultados a la primera fila.",
		"return": "boolean"
	} **/
	public function reset() {
		$this->cursor->reset();
		return $this;
	}

	/** FUNCTION {
		"name" : "rows",
		"type" : "public",
		"description" : "Alias de <b>nglDBSQLiteQuery::count</b>",
		"return": "int"
	} **/
	public function rows() {
		return $this->count();
	}

	/** FUNCTION {
		"name" : "toArray",
		"type" : "public",
		"description" : "
			Obtiene todas las filas de resultados en forma de array bidimensional utilizando <b>SQLite3Result::fetchArray</b> en modo asociativo.
			Este método ignora los argumentos del objeto <b>nglDBSQLiteQuery</b> y al finalizar reinicia el conjunto de resultados a la primera fila.
		",
		"return": "boolean"
	} **/
	public function toArray() {
		$this->reset();
		$aGetAll = [];
		while(($aRow = $this->cursor->fetchArray(\SQLITE3_ASSOC))!==false) {
			$aGetAll[] = $aRow;
		}
		$this->reset();
		return $aGetAll;
	}
}

?>