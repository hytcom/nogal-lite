<?php
/*
# Nogal
*the most simple PHP Framework* by hytcom.net
GitHub @hytcom
___
  
# potsgresql
## nglDBPostgreSQL *extends* nglBranch *implements* iNglDataBase [2018-08-21]
Gestor de conexciones con bases de datos PostgreSQL

https://github.com/hytcom/wiki/blob/master/nogal/docs/potsgresql.md

*/
namespace nogal;

class nglDBPostgreSQL extends nglBranch implements iNglDataBase {

	private $link;
	private $vModes;
	private $aQueries;

	final protected function __declareArguments__() {
		$vArguments							= [];
		$vArguments["autoconn"]				= ['self::call()->istrue($mValue)', false];
		$vArguments["base"]					= ['$mValue', "postgres"];
		$vArguments["charset"]				= ['$mValue', "utf8"];
		$vArguments["check_colnames"]		= ['self::call()->istrue($mValue)', true];
		$vArguments["conflict_action"]		= ['$mValue', "NOTHING"]; // NOTHING | UPDATE
		$vArguments["conflict_target"]		= ['$mValue', "(id)"]; // (column_name) | constraint_name | WHERE... 
		$vArguments["debug"]				= ['self::call()->istrue($mValue)', false];
		$vArguments["do"]					= ['self::call()->istrue($mValue)', false];
		$vArguments["coontype"]				= ['$mValue', PGSQL_CONNECT_FORCE_NEW];
		$vArguments["error_query"]			= ['self::call()->istrue($mValue)', false];
		$vArguments["file"]					= ['$mValue', null];
		$vArguments["file_eol"]				= ['$mValue', "\n"];
		$vArguments["file_separator"]		= ['$mValue', "\t"];
		$vArguments["file_enclosed"]		= ['$mValue', ""];
		$vArguments["host"]					= ['$mValue', "localhost"];
		$vArguments["insert_mode"]			= ['$mValue', "INSERT"]; // INSERT | CONFLICT
		$vArguments["jsql"]					= ['$mValue', null];
		$vArguments["jsql_eol"]				= ['$mValue', ""];
		$vArguments["pass"]					= ['$mValue', "root"];
		$vArguments["port"]					= ['(int)$mValue', 5432];
		$vArguments["schema"]				= ['(string)$mValue', "public"];
		$vArguments["sql"]					= ['$mValue', null];
		$vArguments["table"]				= ['(string)$mValue', null];
		$vArguments["user"]					= ['$mValue', "root"];
		$vArguments["update_mode"]			= ['strtoupper($mValue)', "UPDATE"]; // UPDATE | UPDATE ONLY
		$vArguments["values"]				= ['$mValue', null];
		$vArguments["where"]				= ['$mValue', null];

		return $vArguments;
	}

	final protected function __declareAttributes__() {
		$vAttributes						= [];
		$vAttributes["last_query"]			= null;
		return $vAttributes;
	}

	final protected function __declareVariables__() {
		$vModes 				= [];
		$vModes["INSERT"] 		= "INSERT";
		$vModes["CONFLICT"] 	= "INSERT";
		$vModes["UPDATE"] 		= "UPDATE";
		$vModes["ONLY"] 		= "UPDATE ONLY";
		$this->vModes 			= $vModes;
		$this->aQueries			= [];
	}

	final public function __init__() {
		if($this->argument("autoconn")) {
			$this->connect();
		}
	}

	public function close() {
		return $this->link->close();
	}

	public function connect() {
		list($sHost, $sUser, $sPass, $sBase, $nPort, $sOptions) = $this->getarguments("host,user,pass,base,port,options", \func_get_args());

		$aParams = [];
		$aParams[] = "host=".$sHost;
		$aParams[] = "user=".$sUser;
		$aParams[] = "password=".self::passwd($sPass, true);
		if(!empty($sBase)) { $aParams[] = "dbname=".$sBase; }
		if(!empty($nPort)) { $aParams[] = "port=".$nPort; }

		$this->link = @\pg_connect(\implode(" ", $aParams), $this->argument("coontype"));
		if($this->link===false) {
			$this->Error(true);
			return false;
		}

		if(!\pg_query($this->link, "SET search_path TO ".$this->argument("schema"))) {
			$this->Error();
			return false;
		}
		return $this;
	}

	public function chkgrants() {
		return $this->query("
			SELECT 
				r.usename as grantor, e.usename as grantee, nspname, privilege_type, is_grantable
			FROM pg_namespace, ACLEXPLODE(nspacl) a 
				JOIN pg_user e on a.grantee = e.usesysid
				JOIN pg_user r on a.grantor = r.usesysid 
			WHERE e.usename = '".$this->argument("user")."'
		")->getall();
	}

	public function destroy() {
		foreach($this->aQueries as $query) {
			self::call($query)->destroy();
		}
		\pg_close($this->link);
		return parent::__destroy__();
	}	
	
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
						$mEscapedValues[$sField] = \pg_escape_string($this->link, $mValue);
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
					$mEscapedValues = \pg_escape_string($this->link, $mEscapedValues);
				}
			}
		}

		return $mEscapedValues;
	}

	public function exec() {
		list($sQuery) = $this->getarguments("sql", \func_get_args());
		if($this->argument("debug")) { return $sQuery; }
		if(!$query = \pg_query($this->link, $sQuery)) {
			$this->Error();
			return null;
		}
		return $query;
	}

	public function export() {
		list($sQuery,$sFilePath) = $this->getarguments("sql,file", \func_get_args());
		if($sFilePath===null) { $sFilePath = NGL_PATH_TMP."/export_".\date("YmdHis").".csv"; }
		$sFilePath = self::call()->sandboxPath($sFilePath);

		$sSeparator	= $this->argument("file_separator");
		$sEOL		= $this->argument("file_eol");
		$sEnclosed	= $this->argument("file_enclosed");
		$sEscaped	= '\\';
	
		$bError = true;
		if($data = \pg_query($this->link, $sQuery)) {
			if($csv = @\fopen($sFilePath, "w")) {
				$bError = false;
				while($aRow = \pg_fetch_array($data, null, PGSQL_NUM)) {
					$aLine = [];
					foreach($aRow as $sColumn) {
						$sColumn = \str_replace($sEnclosed, $sEscaped.$sEnclosed, $sColumn);
						$sColumn = \str_replace($sSeparator, $sEscaped.$sSeparator, $sColumn);
						$aLine[] = $sEnclosed.$sColumn.$sEnclosed;
					}
					\fwrite($csv, \implode($sSeparator, $aLine).$sEOL);
				}
				\fclose($csv);
			}
		}

		return (!$bError) ? $sFilePath : false;
	}

	public function handler() {
		return $this->link;
	}

	public function ifexists() {
		list($sTable,$sBase) = $this->getarguments("table,base", \func_get_args());
		$sSchema = $this->argument("schema");
		$nChk = \pg_fetch_result(\pg_query($this->link, "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = '".$sSchema."' AND table_name = '".$sTable."'"),0,0);
		return !$nChk ? true : false;
	}

	public function import() {
		list($sFilePath,$sTable) = $this->getarguments("file,table", \func_get_args());
		if($sFilePath===null) { return false; }
		$sFilePath = self::call()->sandboxPath($sFilePath);
		if(!\file_exists($sFilePath)) { return false; }

		$sSeparator	= $this->argument("file_separator");
		$sEnclosed	= $this->argument("file_enclosed");
		$sSchema	= $this->argument("schema");
		
		$sChk = \pg_fetch_result(\pg_query($this->link, "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = '".$sSchema."' AND table_name = '".$sTable."'"),0,0);
		if($sChk==="0") {
			if(($fp=@\fopen($sFilePath, "r"))!==false) {
				if(\strlen($sSeparator)>1) { $sSeparator = self::call()->unescape($sSeparator); }
				while(($aColumns = \fgetcsv($fp, 5000, $sSeparator))!==FALSE) {
					$aColumns; break;
				}
				\fclose($fp);
				
				$aColsChecker = [];
				foreach($aColumns as &$sColumn) {
					$sColumn = self::call()->secureName($sColumn);
					if(isset($aColsChecker[$sColumn]) || empty($sColumn)) { $sColumn .= "_".self::call()->unique(); }
					$aColsChecker[$sColumn] = true;
				}
				$sCreate = "CREATE TABLE ".$sTable." (".\implode(" TEXT NULL, ", $aColumns)." TEXT NULL);";
				
				if($this->query($sCreate)===null) { return false; }
			}
		}

		$bLoad = false;
		if($csv = @\fopen($sFilePath, "r")) {
			$bLoad = true;
			\pg_query($this->link, "COPY ".$sTable." FROM STDIN");
			if($sSeparator=="\t" && $sEnclosed=="") {
				while($sRow = \fgets($csv)) {
					$sRow = \trim($sRow, "\r\n");
					\pg_put_line($this->link, $sRow."\n");
				}
			} else {
				while($aRow = \fgetcsv($csv, 0, $sSeparator, $sEnclosed)) {
					$sRow = \implode("\t", $aRow);
					\pg_put_line($this->link, $sRow."\n");
				}
			}
			\fclose($csv);
			\pg_put_line($this->link, "\\.\n");
			\pg_end_copy($this->link);
		}

		if($bLoad===true && $sChk==="0") { $this->query("DELETE FROM ".$sTable." LIMIT 1"); }
		return $bLoad;
	}

	public function insert() {
		list($sTable, $mValues, $sMode, $bCheckColumns, $bDO) = $this->getarguments("table,values,insert_mode,check_colnames,do", \func_get_args());
		if(!empty($sTable)) {
			$aToInsert = $this->PrepareValues("INSERT", $sTable, $mValues, $bCheckColumns);
			if(\is_array($aToInsert) && \count($aToInsert)) {
				$sSQL  = "INSERT INTO ".$sTable." ";
				$sSQL .= "(".\implode(", ", array_keys($aToInsert)).") ";
				$sSQL .= "VALUES (".\implode(",", $aToInsert).")";
				
				if(\strtoupper($sMode)=="CONFLICT") {
					$sTarget = $this->argument("conflict_target");
					if(!empty($sTarget) && $sTarget[0]=="(") {
						$sTarget = "ON ".$sTarget;
					} else if(\strtoupper(\substr($sTarget,0,5))!="WHERE") {
						$sTarget = "ON CONSTRAINT ".$sTarget;
					}
					$sSQL .= " ON CONFLICT ".$sTarget." DO ".$this->argument("conflict_action");
				}

				return $this->query($sSQL, $bDO);
			}
		}
		
		return null;
	}

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
			$sFirstTable = \array_shift($aJSQL["tables"]);
			$aFrom = [self::call("jsql")->column($sFirstTable, "")];
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
				$vSQL["limit"] = "LIMIT ".(int)$aJSQL["limit"]." OFFSET ".(int)$aJSQL["offset"];
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
				$sSQL = \trim($sSQL);
				break;
		}

		$this->sql($sSQL);
		return $sSQL;
	}

	public function mexec() {
		list($sQuery) = $this->getarguments("sql", \func_get_args());
		$aQueries = self::call()->strToArray($sQuery, ";");
		if($this->argument("debug")) { return $aQueries; }
		
		$aResults = [];
		foreach($aQueries as $sQuery) {
			$sQuery = \trim($sQuery);
			if(!empty($sQuery)) {
				if(!$query = @$this->link->query($sQuery)) {
					$aResults[] = $this->Error();
				} else {
					$aResults[] = $query;
				}
			}
		}
		
		return $aResults;
	}

	public function mquery() {
		list($sQuery) = $this->getarguments("sql", \func_get_args());
		$sQuery = \preg_replace(array("/^--.*$/m", "/^\/\*(.*?)\*\//m"), "", $sQuery);
		$aQueries = self::call()->strToArray($sQuery, ";");
		if($this->argument("debug")) { return \implode(PHP_EOL, $aQueries); }

		$aErrors = [];
		foreach($aQueries as $sQuery) {
			if(!$query = $this->query($sQuery, true)) {
				$aErrors[] = $this->Error();
			}
		}

		return (\count($aErrors)) ? $aErrors : true;
	}

	public function query() {
		list($sQuery,$bDO) = $this->getarguments("sql,do", \func_get_args());
		if($this->argument("debug")) { return $sQuery; }

		// juego de caracteres
		\pg_set_client_encoding($this->link, $this->argument("charset"));

		$sQuery = trim($sQuery);
		if(empty($sQuery)) { return null; }

		$nTimeIni = \microtime(true);
		$this->attribute("last_query", $sQuery);
		if(!$query = \pg_query($this->link, $sQuery)) {
			return $this->Error();
		}

		if($bDO) {
			\pg_free_result($query);
			return true;
		}

		$nQueryTime = self::call("dates")->microtimer($nTimeIni);
		$sQueryName = "pgsqlq".\strstr($this->me, ".")."_".self::call()->unique();
		$this->aQueries[] = $sQueryName;
		return self::call($sQueryName)->load($this->link, $query, $sQuery, $nQueryTime);
	}

	public function update() {
		list($sTable, $mValues, $sWhere, $sMode, $bCheckColumns, $bDO) = $this->getarguments("table,values,where,update_mode,check_colnames,do", \func_get_args());

		if(!empty($sTable)) {
			$aToUpdate = $this->PrepareValues("UPDATE", $sTable, $mValues, $bCheckColumns);
			if(\is_array($aToUpdate) && count($aToUpdate)) {
				$sMode = \strtoupper($sMode);
				$sUpdateMode = (isset($this->vModes[$sMode])) ? $this->vModes[$sMode] : "UPDATE";
				$sSQL = $sUpdateMode." ".$sTable." SET ".\implode(", ", $aToUpdate)." WHERE ".$sWhere;
				return $this->query($sSQL, $bDO);
			}
		}
		
		return null;
	}

	private function Error($bConnect=false) {
		\pg_set_error_verbosity($this->link, PGSQL_ERRORS_DEFAULT); // PGSQL_ERRORS_TERSE, PGSQL_ERRORS_DEFAULT or PGSQL_ERRORS_VERBOSE
		$sMsgError = ($bConnect) ? "Could not connect" : \pg_last_error($this->link);
		if($sMsgError && $this->argument("error_query")) {
			$sMsgError .= " -> ". $this->attribute("last_query");
		}

		return self::errorMessage("PostgreSQL", $sMsgError);
	}

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
			$sSchema = $this->argument("schema");
			$columns = \pg_query($this->link, "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = '".$sSchema."' AND table_name = '".$sTable."'");
			$aFields = [];
			while($aGetColumn = \pg_fetch_array($columns, null, PGSQL_ASSOC)) {
				$aFields[] = $aGetColumn["column_name"];
			}
			\pg_free_result($columns);
			$columns = null;
			unset($columns);
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
					$aNewValues[] = $sField." = ".$mValue."";
				}
			}
		}
		
		return $aNewValues;
	}
}

?>