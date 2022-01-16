<?php

namespace nogal;

/** CLASS {
	"name" : "nglDBSQLite",
	"object" : "sqlite",
	"type" : "instanciable",
	"revision" : "20160201",
	"extends" : "nglBranch",
	"interfaces" : "iNglDataBase",
	"description" : "Gestiona conexciones con bases de datos SQLite.",
	"configfile" : "sqlite.conf",
	"variables" : {
		"$link" : ["private", "Puntero"],
		"$vModes" : ["private", "Modos de INSERT y UPDATE"]
	},
	"arguments": {
		"autoconn" : ["boolean", "Cuando es TRUE, ejecuta el método connect luego de crear el objeto. Sólo usar en TRUE cuando se utilicen archivos .conf", "false"],
		"base" : ["string", "Ruta del archivo de base de datos", "null"],
		"check_colnames" : ["boolean", "Activa el chequeo de los nombre de las columnas en la tabla activa", "true"],
		"debug" : ["boolean", "Cuando es TRUE él método retorna la sentencia SQL en lugar de ejecutarla", "false"],
		"do" : ["boolean", "Cuando es TRUE el método query ejecuta la sentencia pero no retorna resultado", "false"],
		"error_description" : ["boolean", "Ante un error mostrará la descripción del mismo", "false"],
		"error_query" : ["boolean", "Ante un error mostrará la consulta que le dió origen", "false"],
		"flags" : ["string", "Banderas opcionales para determinar cómo abrir la base de datos SQLite", "SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE"],
		"insert_mode" : ["string", "Tipo de modo INSERT. Valores admitidos:
			<ul>
				<li><b>INSERT:</b> inserta nuevos registros</li>
				<li><b>REPLACE:</b> si el nuevo registro duplica un valor PRIMARY KEY o UNIQUE, el antiguo registro es eliminado</li>
				<li><b>IGNORE:</b> el comando no aborta incluso si ocurren errores durante la ejecución</li>
			</ul>
		", "INSERT"],
		"jsql" : ["mixed", "
			Sentencia SQL en formato JSON o Array:
			<ul>
				<li>columns</li>
				<li>tables</li>
				<li>where</li>
				<li>group</li>
				<li>having</li>
				<li>order</li>
				<li>offset</li>
				<li>limit</li>
			</ul>
		", "null"],
		"jsql_eol" : ["string", "Salto de linea luego de cada parte de la sentencia", ""],
		"pass" : ["string", "Clave de encriptación opcional usada cuando se encripta o desencripta una base de datos", "null"],
		"sql" : ["string", "Ultima sentencia SQL ejecutada o próxima a ejecutarse", "null"],
		"table" : ["string", "Nombre de la tabla activa en los métodos INSERT y UPDATE", "null"],
		"update_mode" : ["string", "Tipo de modo UPDATE. Valores admitidos:
			<ul>
				<li><b>UPDATE:</b> actualiza los registros especificados</li>
				<li><b>REPLACE:</b> crea un nuevo registro en caso de no hallar el registro especificados</li>
				<li><b>IGNORE:</b> el comando no aborta incluso si ocurren errores durante la ejecución</li>
			</ul>
		", "UPDATE"],
		"values" : ["string", "Datos enviados a los métodos INSERT y UPDATE. Valores admitidos:
			<ul>
				<li><b>array asociativo:</b> donde cada clave es el nombre del campo en la tabla</li>
				<li><b>cadena de variables:</b> con el mismo formato que las pasadas por medio de una URL. El valor será analizado utilizando <b>parse_str</b></li>
			</ul>
		", "null"],
		"where" : ["string", "Cadena que representa una condición SQL WHERE", "null"]
	}
} **/
class nglDBSQLite extends nglBranch implements iNglDataBase {

	private $link;
	private $vModes;

	final protected function __declareArguments__() {
		$vArguments							= [];
		$vArguments["autoconn"]				= ['self::call()->istrue($mValue)', false];
		$vArguments["base"]					= ['$mValue', null];
		$vArguments["check_colnames"]		= ['self::call()->istrue($mValue)', true];
		$vArguments["debug"]				= ['self::call()->istrue($mValue)', false];
		$vArguments["do"]					= ['self::call()->istrue($mValue)', false];
		$vArguments["error_description"]	= ['self::call()->istrue($mValue)', false];
		$vArguments["error_query"]			= ['self::call()->istrue($mValue)', false];
		$vArguments["field"]				= ['$mValue', null];
		$vArguments["flags"]				= ['(int)$mValue', (SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE)];
		$vArguments["insert_mode"]			= ['$mValue', "INSERT"];
		$vArguments["jsql"]					= ['$mValue', null];
		$vArguments["jsql_eol"]				= ['$mValue', ""];
		$vArguments["pass"]					= ['$mValue', null];
		$vArguments["sql"]					= ['$mValue', null];
		$vArguments["table"]				= ['(string)$mValue', null];
		$vArguments["update_mode"]			= ['\strtoupper($mValue)', "UPDATE"];
		$vArguments["values"]				= ['$mValue', null];
		$vArguments["where"]				= ['$mValue', null];

		return $vArguments;
	}

	final protected function __declareAttributes__() {
		$vAttributes						= [];
		return $vAttributes;
	}

	final protected function __declareVariables__() {
		$vModes 			= [];
		$vModes["INSERT"] 	= "";
		$vModes["UPDATE"] 	= "";
		$vModes["REPLACE"] 	= "OR REPLACE";
		$vModes["IGNORE"] 	= "OR IGNORE";
		$this->vModes 		= $vModes;
	}

	final public function __init__() {
		if($this->autoconn) {
			$this->connect();
		}
	}

	/** FUNCTION {
		"name" : "close",
		"type" : "public",
		"description" : "Finaliza la conexión con la base de datos",
		"return": "boolean"
	} **/
	public function close() {
		return $this->link->close();
	}

	/** FUNCTION {
		"name" : "connect",
		"type" : "public",
		"description" : "Establece la conexión con la base de datos",
		"parameters" : { 
			"$sBase" : ["string", "", "argument::base"],
			"$sPass" : ["string", "", "argument::pass"],
			"$nFlags" : ["string", "", "argument::flags"]
		},
		"return": "$this"
	} **/
	public function connect() {
		list($sBase, $sPass, $nFlags) = $this->getarguments("base,pass,flags", \func_get_args());
		$sPass = self::passwd($sPass, true);
		$sBase = self::call()->sandboxPath($sBase);
		$this->link = new \SQLite3($sBase, $nFlags, $sPass);
		return $this;
	}

	/** FUNCTION {
		"name" : "destroy",
		"type" : "public",
		"description" : "Cierra la conexión y destruye el objeto",
		"return": "boolean"
	} **/
	public function destroy() {
		$this->link->close();
		return parent::__destroy__();
	}	

	/** FUNCTION {
		"name" : "Error",
		"type" : "private",
		"description" : "Muestra el mensaje de Error generado por el fallo más reciente",
		"return": "mixed"
	} **/
	private function Error() {
		$sMsgError = "";
		if($this->error_description) {
			$sMsgError = $this->link->lastErrorMsg();
			if($this->error_query) {
				$sMsgError .= " -> ". $this->sql;
			}
		}

		return self::errorMessage("SQLite", $this->link->lastErrorCode(), $sMsgError);
	}

	/** FUNCTION {
		"name" : "escape",
		"type" : "public",
		"description" : "Escapa un valor para ser incluído de manera segura en una sentencia SQL",
		"parameters" : { 
			"$mValues" : ["mixed", "", "argument::values"]
		},
		"input": "values",
		"return": "mixed"
	} **/
	public function escape() {
		list($mValues) = $this->getarguments("values", \func_get_args());

		if(\is_array($mValues)) {
			$mEscapedValues = [];
			foreach($mValues as $sField => $mValue) {
				if($mValue===null) {
					$mEscapedValues[$sField] = null;
				} else if($mValue!==NGL_NULL) {
					if(\is_array($mValue)) {
						$mEscapedValues[$sField] = $this->escape($mValue);
					} else {
						$mEscapedValues[$sField] = $this->link->escapeString($mValue);
					}
				}
			}
		} else {
			if($mValues===null) {
				$mEscapedValues = null;
			} else if($mValues!==NGL_NULL) {
				$mEscapedValues = $mValues;
				if(\is_array($mEscapedValues)) {
					$mEscapedValues = $this->escape($mEscapedValues);
				} else {
					$mEscapedValues = $this->link->escapeString($mEscapedValues);
				}
			}
		}

		return $mEscapedValues;
	}

	/** FUNCTION {
		"name" : "exec",
		"type" : "public",
		"description" : "Ejecuta una sentencia SQL y retorna un objecto <b>SQLite3Result</b>",
		"parameters" : { 
			"$sQuery" : ["string", "", "argument::sql"]
		},
		"return": "SQLite3Result object"
	} **/
	public function exec() {
		list($sQuery) = $this->getarguments("sql", \func_get_args());
		if($this->debug) { return $sQuery; }
		if(!$query = @$this->link->query($sQuery)) {
			return $this->Error();
		}
		return $query;
	}

	public function handler() {
		return $this->link;
	}

	/** FUNCTION {
		"name" : "jsqlParser",
		"type" : "public",
		"description" : "Convierte una sentencia JSQL y retorna una sentencia SQL",
		"parameters" : { 
			"$mJSQL" : ["mixed", "", "argument::jsql"],
			"$sEOL" : ["string", "Salto de linea que se insertará luego de cada parte de la sentencia", "argument::jsql_eol"] 
		},
		"seealso" : ["nglJSQL"],
		"return" : "string"
	} **/
	public function jsqlParser() {
		list($mJSQL, $sEOL) = $this->getarguments("jsql,jsql_eol", \func_get_args());
		$aJSQL = (\is_string($mJSQL)) ? self::call("jsql")->decode($mJSQL) : $mJSQL;
		$sType = (isset($aJSQL["type"])) ? \strtolower($aJSQL["type"]) : "select";

		$vSQL = [];
		$vSQL["columns"]	= "";
		$vSQL["tables"]		= "";
		$vSQL["where"]		= "";
		$vSQL["group"]		= "";
		$vSQL["having"]		= "";
		$vSQL["order"]		= "";
		$vSQL["limit"] 		= "";

		// select
		switch($sType) {
			case "select":
				$aSelect = [];
				if(isset($aJSQL["columns"])) {
					foreach($aJSQL["columns"] as $sField) {
						$aSelect[] = self::call("jsql")->column($sField);
					}
				} else {
					$aSelect[] = "*";
				}
				$vSQL["columns"] = "SELECT ".$sEOL.\implode(", ".$sEOL, $aSelect).$sEOL;
				break;

			case "insert":
			case "update":
				$aSelect = [];
				if(isset($aJSQL["columns"])) {
					$sSelect = self::call("jsql")->conditions($aJSQL["columns"], true);
				}
				$vSQL["columns"] = "SET ".$sSelect.$sEOL;
				break;
			
			case "where":
				return self::call("jsql")->conditions($aJSQL["where"]);
				break;
		}
		
		// tables
		if(isset($aJSQL["tables"])) {
			$sFirstTable = array_shift($aJSQL["tables"]);
			$aFrom = [self::call("jsql")->column($sFirstTable,"")];
			foreach($aJSQL["tables"] as $aTable) {
				if(!\is_array($aTable) || (\is_array($aTable) && !isset($aTable[2]))) {
					$aFrom[] = ", ".$sEOL.self::call("jsql")->column($aTable, "");
				} else {
					$aFrom[] = "LEFT JOIN ".self::call("jsql")->column($aTable, "")." ON (".self::call("jsql")->conditions($aTable[2]).")".$sEOL;
				}
			}
			
			switch($sType) {
				case "select":
					$vSQL["tables"] = "FROM ".$sEOL.\implode(" ", $aFrom);
					break;

				case "insert":
					$vSQL["tables"] = "INSERT INTO ".$sEOL.\implode(" ", $aFrom);
					break;

				case "update":
					$vSQL["tables"] = "UPDATE ".$sEOL.\implode(" ", $aFrom);
					break;
			}
		}

		// where
		$vSQL["where"] = (isset($aJSQL["where"])) ? "WHERE ".$sEOL.self::call("jsql")->conditions($aJSQL["where"]) : "";
		
		// group by
		if(isset($aJSQL["group"])) {
			$aGroup = [];
			foreach($aJSQL["group"] as $sField) {
				$aGroup[] = self::call("jsql")->column($sField);
			}
			$vSQL["group"] = "GROUP BY ".$sEOL.\implode(", ", $aGroup);
		}
		
		// having
		if(isset($aJSQL["having"])) { $vSQL["having"] = "HAVING ".$sEOL.self::call("jsql")->conditions($aJSQL["having"]); }
		
		// order by
		if(isset($aJSQL["order"])) {
			$aOrder = [];
			foreach($aJSQL["order"] as $sField) {
				if($sField==="RANDOM") { $aOrder[] = "RANDOM()"; break; }
				$aField = \explode(":", $sField);
				$sOrder = (\is_numeric($aField[0])) ? $aField[0] : self::call("jsql")->column($aField[0]);
				if(isset($aField[1])) { $sOrder .= " ".$aField[1]; }
				$aOrder[] = $sOrder;
			}
			$vSQL["order"] = "ORDER BY ".$sEOL.\implode(", ".$sEOL, $aOrder);
		}
		
		if(isset($aJSQL["limit"])) {
			if(isset($aJSQL["offset"])) {
				$vSQL["limit"] = "LIMIT ".(int)$aJSQL["offset"].", ".(int)$aJSQL["limit"];
			} else {
				$vSQL["limit"] = "LIMIT ".(int)$aJSQL["limit"];
			}
		}
		
		// sentencia SQL
		$sSQL = "";
		switch($sType) {
			case "select":
				$sSQL = \implode(" ", $vSQL);
				break;

			case "insert":
			case "update":
				$sSQL = $vSQL["tables"]." ".$vSQL["columns"]." ".$vSQL["where"]." ".$vSQL["order"]." ".$vSQL["limit"];
				$sSQL = trim($sSQL);
				break;
		}

		$this->sql($sSQL);
		return $sSQL;
	}

	/** FUNCTION {
		"name" : "mexec",
		"type" : "public",
		"description" : "Ejecuta varias sentencias SQL separadas por ; y retorna un array de objectos <b>SQLite3Result</b>",
		"parameters" : { 
			"$sQuery" : ["string", "", "argument::sql"]
		},
		"return": "array"
	} **/
	public function mexec() {
		list($sQuery) = $this->getarguments("sql", \func_get_args());
		$aQueries = self::call()->strtoArray($sQuery, ";");
		if($this->debug) { return $aQueries; }
		
		$aResults = [];
		foreach($aQueries as $sQuery) {
			$sQuery = trim($sQuery);
			if(!empty($sQuery)) {
				if(!$query = @$this->link->query($sQuery)) {
					$aResults[] = $this->Error(true);
				} else {
					$aResults[] = $query;
				}
			}
		}
		
		return $aResults;
	}

	/** FUNCTION {
		"name" : "mquery",
		"type" : "public",
		"description" : "Ejecuta varias sentencias SQL separadas por ; y retorna un array de objectos <b>nglDBSQLiteQuery</b>, o TRUE cuando DO esta activo",
		"parameters" : { 
			"$sQuery" : ["string", "", "argument::sql"],
			"$bDO" : ["boolean", "", "argument::do"]
		},
		"input": "sql, debug",
		"return": "array"
	} **/
	public function mquery() {
		list($sQuery,$bDO) = $this->getarguments("sql,do", \func_get_args());
		$sQuery = \preg_replace("/^--(.*?)$/m", "", $sQuery);
		$aQueries = self::call()->strtoArray($sQuery, ";");
		if($this->debug) { return $aQueries; }

		$aResults = [];
		foreach($aQueries as $sQuery) {
			$sQuery = trim($sQuery);
			if(!empty($sQuery)) {
				$nTimeIni = \microtime(true);
				if(!$query = @$this->link->query($sQuery)) {
					if(!$bDO) { $aResults[] = $this->Error(true); }
				} else {
					if($bDO) {
						if(\method_exists($query, "free")) { $query->free(); }
						$aResults = true;
					} else {
						$nQueryTime		= self::call("dates")->microtimer($nTimeIni);
						$sQueryName 	= "sqliteq".\strstr($this->me, ".")."_".self::call()->unique();
						$aResults[] 	= self::call($sQueryName)->load($this->link, $query, $sQuery, $nQueryTime);
					}
				}
			}
		}

		return $aResults;
	}
	
	/** FUNCTION {
		"name" : "insert",
		"type" : "public",
		"description" : "Inserta un nuevo registro en una tabla",
		"parameters" : { 
			"$sTable" : ["string", "", "argument::table"],
			"$mValues" : ["mixed", "", "argument::values"],
			"$sMode" : ["string", "", "argument::insert_mode"]
		},
		"examples" : {
			"datos en array": "
				$foo = $ngl("sqlite.foobar");
				$foo->base = "shop.sqlite";
				$foo->connect();
				
				$data = array("foo"=>"foovalue", "bar"=>"barvalue");
				$foo->insert("tablename", $data);
			",
			"datos como cadena de variables": "
				$foo = $ngl("sqlite.foobar");
				$foo->base = "shop.sqlite";
				$foo->connect();
				
				$data="foobar&bar=barvalue"
				$foo->insert("tablename", $data, "replace");
			"
		},
		"return": "SQLite3Result object"
	} **/
	public function insert() {
		list($sTable, $mValues, $sMode, $bCheckColumns) = $this->getarguments("table,values,insert_mode,check_colnames", \func_get_args());

		if(!empty($sTable)) {
			$aToInsert = $this->PrepareValues("INSERT", $sTable, $mValues, $bCheckColumns);

			if(\is_array($aToInsert) && \count($aToInsert)) {
				$sMode = \strtoupper($sMode);
				$sInsertMode = (isset($this->vModes[$sMode])) ? $this->vModes[$sMode] : "";
				$sSQL  = "INSERT ".$sInsertMode." INTO `".$sTable."` ";
				$sSQL .= "(`".\implode("`, `", \array_keys($aToInsert))."`) ";
				$sSQL .= "VALUES (".\implode(",", $aToInsert).")";
				return $this->query($sSQL);
			}
		}
		
		return false;
	}

	/** FUNCTION {
		"name" : "PrepareValues",
		"type" : "private",
		"description" : "
			Auxiliar de los métodos <b>insert</b> y <b>update</b>.
			Prepara el <b>array asociativo</b> o la <b>cadena de variables</b> para ser utilizados en las sentencias.
			Cuando los valores sean pasados como una <b>cadena de variables</b> estos serán tratados con <b>escape</b> para garantizar la seguridad del comando SQL.
		",
		"parameters" : { 
			"$sType" : ["string", "Tipo de operación, INSERT o UPDATE"],
			"$sTable" : ["string", "Nombre de la tabla"],
			"$mValues" : ["mixed", "Datos en forma de array asociativo o cadena de variables"],
			"$bCheckColumns" : ["boolean", "Activa el chequeo de columnas en la tabla", "true"]
		},
		"return": "SQLite3Result object"
	} **/
	private function PrepareValues($sType, $sTable, $mValues, $bCheckColumns) {
		if(\is_array($mValues)) {
			$aValues = $mValues;
		} else if(\is_string($mValues)){
			\parse_str($mValues, $aValues);
			$aValues = $this->escape($aValues);
		} else {
			return false;
		}
		
		// campos validos
		$aFields = \array_keys($aValues);
		if($bCheckColumns) {
			$columns = $this->link->query("PRAGMA table_info(".$sTable.")");
			$aFields = [];
			while($aGetColumn = $columns->fetchArray(SQLITE3_ASSOC)) {
				$aFields[] = $aGetColumn["name"];
			}
			$columns->finalize();
			$columns = null;
		}
		
		// limpieza de campos inexistentes
		$aNewValues = [];
		if($bCheckColumns && !\count($aFields)) { return $aNewValues; }

		if(\is_array($aFields) && \count($aFields)) {
			if($sType=="INSERT") {
				foreach($aValues as $sField => $mValue) {
					if($bCheckColumns && !\in_array($sField, $aFields)) { unset($aValues[$sField]); continue; }
					$mValue = ($mValue===null) ? "NULL" : "'".$mValue."'";
					$aNewValues[$sField] = $mValue;
				}
			} else {
				foreach($aValues as $sField => $mValue) {
					if($bCheckColumns && !\in_array($sField, $aFields)) { unset($aValues[$sField]); continue; }
					$mValue = ($mValue===null) ? "NULL" : "'".$mValue."'";
					$aNewValues[] = "`".$sField."` = ".$mValue."";
				}		
			}
		}
		
		return $aNewValues;
	}

	/** FUNCTION {
		"name" : "query",
		"type" : "public",
		"description" : "Ejecuta una sentencia SQL y retorna un objecto <b>nglDBSQLiteQuery</b>",
		"parameters" : { 
			"$sQuery" : ["string", "", "argument::sql"],
			"$bDO" : ["boolean", "", "argument::do"]
		},
		"examples": {
			"conexión": "
				$foo = $ngl("sqlite.foobar");
				$foo->base = "shop.sqlite";
				$foo->connect();
				$bar = $foo->query("SELECT * FROM `users`");
			"
		},
		"return": "nglDBSQLiteQuery object"
	} **/
	public function query() {
		list($sQuery,$bDO) = $this->getarguments("sql,do", \func_get_args());

		if($this->debug) { return $sQuery; }

		$nTimeIni = \microtime(true);
		if(!$query = @$this->link->query($sQuery)) {
			return $this->Error();
		}
		
		if($bDO) {
			if(method_exists($query, "finalize")) { $query->finalize(); }
			return true;
		}

		$nQueryTime = self::call("dates")->microtimer($nTimeIni);
		$sQueryName = "sqliteq".\strstr($this->me, ".")."_".self::call()->unique();
		return self::call($sQueryName)->load($this->link, $query, $sQuery, $nQueryTime);
	}

	public function quote() {
		list($sField) = $this->getarguments("field", \func_get_args());
		$sField = \str_replace('"','',$sField);
		return '"'.\str_replace(".",'"."',$sField).'"';
	}

	/** FUNCTION {
		"name" : "update",
		"type" : "public",
		"description" : "Actualiza todos los registros que cumplan con la condición <b>$sWhere</b>",
		"parameters" : { 
			"$sTable" : ["string", "", "argument::table"],
			"$mValues" : ["mixed", "", "argument::values"],
			"$sWhere" : ["string", "", "argument::where"],
			"$sMode" : ["string", "", "argument::update_mode"]
		},
		"examples" : {
			"datos en array": "
				$foo = $ngl("sqlite.foobar");
				$foo->base = "shop.sqlite";
				$foo->connect();
				
				$data = array("foo"=>"foovalue", "bar"=>"barvalue");
				$foo->update("tablename", $data, "`id`='7'");
			",
			"datos como cadena de variables": "
				$foo = $ngl("sqlite.foobar");
				$foo->base = "shop.sqlite";
				$foo->connect();
				
				$data="foobar&bar=barvalue"
				$foo->update("tablename", $data, "`id`='7'", "ignore");
			"
		},
		"return": "SQLite3Result object"
	} **/
	public function update() {
		list($sTable, $mValues, $sWhere, $sMode, $bCheckColumns, $bDO) = $this->getarguments("table,values,where,update_mode,check_colnames,do", \func_get_args());

		if(!empty($sTable)) {
			$aToUpdate = $this->PrepareValues("UPDATE", $sTable, $mValues, $bCheckColumns);
			if(\is_array($aToUpdate) && count($aToUpdate)) {
				$sMode = \strtoupper($sMode);
				$sUpdateMode = (isset($this->vModes[$sMode])) ? $this->vModes[$sMode] : "";
				$sSQL = "UPDATE ".$sUpdateMode." `".$sTable."` SET ".\implode(", ", $aToUpdate)." WHERE ".$sWhere;
				return $this->query($sSQL, $bDO);
			}
		}
		
		return false;
	}
}

?>