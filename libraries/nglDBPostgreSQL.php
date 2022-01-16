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
		$vArguments["base"]					= ['$this->SetBase($mValue)', "postgres"];
		$vArguments["charset"]				= ['$mValue', "utf8"];
		$vArguments["collate"]				= ['$mValue', "en_US"];
		$vArguments["check_colnames"]		= ['self::call()->istrue($mValue)', true];
		$vArguments["conflict_action"]		= ['$mValue', "NOTHING"]; // NOTHING | UPDATE
		$vArguments["conflict_target"]		= ['$mValue', null]; // (column_name) | constraint_name | WHERE... 
		$vArguments["debug"]				= ['self::call()->istrue($mValue)', false];
		$vArguments["do"]					= ['self::call()->istrue($mValue)', false];
		$vArguments["coontype"]				= ['$mValue', PGSQL_CONNECT_FORCE_NEW];
		$vArguments["error_query"]			= ['self::call()->istrue($mValue)', false];
		$vArguments["field"]				= ['$mValue', null];
		$vArguments["file"]					= ['$mValue', null];
		$vArguments["file_eol"]				= ['$mValue', "\n"];
		$vArguments["file_separator"]		= ['$mValue', "\t"];
		$vArguments["file_enclosed"]		= ['$mValue', ""];
		$vArguments["host"]					= ['$mValue', "localhost"];
		$vArguments["insert_mode"]			= ['$mValue', "INSERT"]; // INSERT | CONFLICT
		$vArguments["pass"]					= ['$mValue', "root"];
		$vArguments["port"]					= ['(int)$mValue', 5432];
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
		$vAttributes["schema"]				= null;
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
		if($this->autoconn) {
			$this->connect();
		}
	}

	public function jsql() {
		return self::call("jsqlpgsql")->db($this);
	}

	public function close() {
		return $this->link->close();
	}

	public function connect() {
		list($sHost, $sUser, $sPass, $sBase, $nPort, $sOptions) = $this->getarguments("host,user,pass,base,port,options", \func_get_args());

		if(!empty($sBase)) {
			$aBaseSchema = \explode(".", $sBase, 2);
			if(\count($aBaseSchema)>1) {
				$sBase = $aBaseSchema[0];
				$this->attribute("schema", $aBaseSchema[1]);
			}
		}

		$aParams = [];
		$aParams[] = "host=".$sHost;
		$aParams[] = "user=".$sUser;
		$aParams[] = "password=".self::passwd($sPass, true);
		if(!empty($sBase)) { $aParams[] = "dbname=".$sBase; }
		if(!empty($nPort)) { $aParams[] = "port=".$nPort; }

		$this->link = @\pg_connect(\implode(" ", $aParams), $this->coontype);
		if($this->link===false) {
			$this->Error(true);
			return false;
		}

		if($this->schema!==null) {
			if(!\pg_query($this->link, "SET search_path TO ".$this->schema)) {
				$this->Error();
				return false;
			}
		}
		return $this;
	}

	public function chkgrants() {
		$grants = $this->query("
			SELECT 
				r.usename as grantor, e.usename as grantee, nspname, privilege_type, is_grantable
			FROM pg_namespace, ACLEXPLODE(nspacl) a 
				JOIN pg_user e on a.grantee = e.usesysid
				JOIN pg_user r on a.grantor = r.usesysid 
			WHERE e.usename = '".$this->user."'
		");
		return ($grants->rows()) ? $grants->getall() : null;
	}

	public function describe() {
		list($sTable) = $this->getarguments("table", \func_get_args());
		$bDebug = $this->debug;
		$this->debug = false;
		$describe = $this->query("
			SELECT 
				c.column_name AS name,
				c.data_type AS type,
				c.character_maximum_length AS length,
				COLUMN_DEFAULT AS default,
				IS_NULLABLE AS nullable,
				t.constraint_type AS index,
				(
					SELECT
						pg_catalog.col_description(cls.oid, c.ordinal_position::int)
					FROM
						pg_catalog.pg_class cls
					WHERE
						cls.oid = (SELECT ('\"' || c.table_name || '\"')::regclass::oid)
						AND cls.relname = c.table_name
				) AS comment
			FROM 
				information_schema.columns c 
				LEFT JOIN INFORMATION_SCHEMA.key_column_usage k ON (
					k.table_schema = c.table_schema AND 
					k.table_name = c.table_name AND 
					k.column_name = c.column_name 
				)
				LEFT JOIN INFORMATION_SCHEMA.table_constraints t ON (
					t.constraint_name = k.constraint_name AND 
					t.constraint_schema = k.constraint_schema AND 
					t.table_name = k.table_name
				)
			WHERE 
				(c.table_catalog || '.' || c.table_schema) = '".$this->base."' AND 
				c.table_name = '".$sTable."'
		");
		$this->debug = $bDebug;

		return ($describe->rows()) ? $describe->getall() : null;
	}

	public function describeView() {
		list($sTable) = $this->getarguments("table", \func_get_args());

		$bDebug = $this->debug;
		$this->debug = false;
		$sName = "_tmpviewfields_".self::call()->unique(8);
		$this->query("CREATE TEMPORARY TABLE `".$sName."` SELECT * FROM `".$sObject."` ORDER BY RAND() LIMIT 30");
		$aFields = $this->describe($sName);
		$this->query("DROP TEMPORARY TABLE `".$sName."`");
		$aView = [];
		foreach($aFields as $aField) {
			$sType = \substr($aField["type"], 0, \strpos($aField["type"], ")"));
			$aType = \explode("(", $sType);
			$aView[$aField["Field"]] = [
				"name" => $aField["Field"],
				"label" => \ucfirst(\str_replace("_", " ", \strtolower($aField["name"]))),
				"type" => $aType[0],
				"length" => $aType[1]
			];
		}
		$this->debug = $bDebug;
		return $aView;
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
		$sEscaped	= '\\';
	
		$bError = true;
		if($data = \pg_query($this->link, $sQuery)) {
			if($csv = @\fopen($sFilePath, "w")) {
				$bError = false;
				while($aRow = \pg_fetch_array($data, null, PGSQL_NUM)) {
					$aLine = [];
					foreach($aRow as $sColumn) {
						$sColumn = \str_replace($this->file_enclosed, $sEscaped.$this->file_enclosed, $sColumn);
						$sColumn = \str_replace($this->file_separator, $sEscaped.$this->file_separator, $sColumn);
						$aLine[] = $this->file_enclosed.$sColumn.$this->file_enclosed;
					}
					\fwrite($csv, \implode($this->file_separator, $aLine).$this->file_eol);
				}
				\fclose($csv);
			}
		}

		return (!$bError) ? $sFilePath : false;
	}

	public function file() {
		list($sFilePath) = $this->getarguments("file", \func_get_args());
		if($sFilePath===null) { return false; }
		$sFilePath = self::call()->sandboxPath($sFilePath);
		if(!\file_exists($sFilePath)) { return false; }
		$sSQL = \file_get_contents($sFilePath);
		return $this->mexec($sSQL);
	}

	public function handler() {
		return $this->link;
	}

	public function ifexists() {
		list($sTable) = $this->getarguments("table", \func_get_args());
		$nChk = \pg_fetch_result(\pg_query($this->link, "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE (table_catalog || '.' || table_schema) = '".$this->base."' AND table_name = '".$sTable."'"),0,0);
		return !$nChk ? true : false;
	}

	public function import() {
		list($sFilePath,$sTable) = $this->getarguments("file,table", \func_get_args());
		if($sFilePath===null) { return false; }
		$sFilePath = self::call()->sandboxPath($sFilePath);
		if(!\file_exists($sFilePath)) { return false; }

		$sEnclosed	= $this->file_enclosed;
		$sChk = \pg_fetch_result(\pg_query($this->link, "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE (table_catalog || '.' || table_schema) = '".$this->base."' AND table_name = '".$sTable."'"),0,0);
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
				$sSQL .= '("'.\implode('", "', array_keys($aToInsert)).'") ';
				$sSQL .= "VALUES (".\implode(",", $aToInsert).")";
				
				if(\strtoupper($sMode)=="CONFLICT") {
					$sTarget = $this->conflict_target===null ? "(".$this->pkey($sTable).")" : $this->conflict_target;
					if(!empty($sTarget) && $sTarget[0]=="(") {
						$sTarget = "ON ".$sTarget;
					} else if(\strtoupper(\substr($sTarget,0,5))!="WHERE") {
						$sTarget = "ON CONSTRAINT ".$sTarget;
					}
					$sSQL .= " ON CONFLICT ".$sTarget." DO ".$this->conflict_action;
				}

				return $this->query($sSQL, $bDO);
			}
		}
		
		return null;
	}

	public function replace() {
		list($sTable, $mValues, $bCheckColumns, $bDO) = $this->getarguments("table,values,check_colnames,do", \func_get_args());
		if(!empty($sTable)) {
			$aToInsert = $this->PrepareValues("INSERT", $sTable, $mValues, $bCheckColumns);
			if(\is_array($aToInsert) && \count($aToInsert)) {
				$sTarget = $this->conflict_target===null ? "(".$this->pkey($sTable).")" : $this->conflict_target;
				$sSQL  = "INSERT INTO ".$sTable." ";
				$sSQL .= "(".\implode(", ", array_keys($aToInsert)).") \n";
				$sSQL .= "VALUES (".\implode(",", $aToInsert).") \n";
				if(!empty($sTarget) && $sTarget!="()") {
					$sSQL .= "ON CONFLICT ".$sTarget." DO UPDATE \n";
					$aExcluded = [];
					foreach(\array_keys($aToInsert) as $sField) {
						$aExcluded[] = '"'.$sField.'" = EXCLUDED."'.$sField.'"';
					}
					$sSQL .= " SET ".\implode(",\n", $aExcluded);
				}
				return $this->query($sSQL, $bDO);
			}
		}
		
		return null;
	}

	public function mexec() {
		list($sQuery) = $this->getarguments("sql", \func_get_args());
		$sQuery = \preg_replace(array("/^--.*$/m", "/^\/\*(.*?)\*\//m"), "", $sQuery);
		if(empty($sQuery)) { return []; }
		$aQueries = self::call()->strToArray($sQuery, ";");
		if($this->debug) { return $aQueries; }
		
		$aResults = [];
		if(\count($aQueries)) {
			foreach($aQueries as $sQuery) {
				$sQuery = \trim($sQuery);
				if(!empty($sQuery)) {
					if(!$query = @\pg_query($this->link, $sQuery)) {
						$aResults[] = $this->Error();
					} else {
						$aResults[] = $query;
					}
				}
			}
		}
		
		return $aResults;
	}

	public function mquery() {
		list($sQuery) = $this->getarguments("sql", \func_get_args());
		$sQuery = \preg_replace(array("/^--.*$/m", "/^\/\*(.*?)\*\//m"), "", $sQuery);
		if(empty($sQuery)) { return []; }
		$aQueries = self::call()->strToArray($sQuery, ";");
		if($this->debug) { return \implode(PHP_EOL, $aQueries); }

		$aErrors = [];
		if(\count($aQueries)) {
			foreach($aQueries as $sQuery) {
				if(!$query = $this->query($sQuery, true)) {
					$aErrors[] = $this->Error();
				}
			}
		}

		return (\count($aErrors)) ? $aErrors : true;
	}

	public function pkey() {
		list($sTable) = $this->getarguments("table", \func_get_args());
		$bDebug = $this->debug;
		$this->debug = false;
		$pk = $this->query("
			SELECT 
				k.column_name 
			FROM 
				INFORMATION_SCHEMA.table_constraints t
				JOIN INFORMATION_SCHEMA.key_column_usage k ON (
					k.constraint_name = t.constraint_name AND 
					k.constraint_schema = t.constraint_schema AND 
					k.table_name = t.table_name
				)
			WHERE 
				(t.constraint_catalog || '.' || t.constraint_schema) = '".$this->base."' AND 
				k.table_name = '".$sTable."' AND 
				t.constraint_type = 'PRIMARY KEY'
		");
		$this->debug = $bDebug;
		return $pk->rows() ? $pk->get("column_name") : null;
	}

	public function query() {
		list($sQuery,$bDO) = $this->getarguments("sql,do", \func_get_args());
		if($this->debug) { return $sQuery; }

		// juego de caracteres
		\pg_set_client_encoding($this->link, $this->charset);

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

	public function quote() {
		list($sField) = $this->getarguments("field", \func_get_args());
		$sField = \str_replace('"','',$sField);
		return '"'.\str_replace(".",'"."',$sField).'"';
	}

	public function tables() {
		list($sTable) = $this->getarguments("where", \func_get_args());
		$bDebug = $this->debug;
		$this->debug = false;
		$tables = $this->query("
			SELECT table_name \"name\" 
			FROM INFORMATION_SCHEMA.TABLES 
			WHERE 
				(table_catalog || '.' || table_schema) = '".$this->base."' AND 
				table_name LIKE '%".$sTable."%'
			ORDER BY 1
		");
		$this->debug = $bDebug;
		return ($tables->rows()) ? $tables->getall() : [];
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

	protected function SetBase($sBaseSchema) {
		$aBaseSchema = \explode(".", $sBaseSchema, 2);
		if(\count($aBaseSchema)>1) { $this->schema = $aBaseSchema[1]; }
		return $sBaseSchema;
	}

	private function Error($bConnect=false) {
		\pg_set_error_verbosity($this->link, PGSQL_ERRORS_DEFAULT); // PGSQL_ERRORS_TERSE, PGSQL_ERRORS_DEFAULT or PGSQL_ERRORS_VERBOSE
		$sMsgError = ($bConnect) ? "Could not connect" : \pg_last_error($this->link);
		if($sMsgError && $this->error_query) {
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
			$columns = \pg_query($this->link, "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = '".$this->schema."' AND table_name = '".$sTable."'");
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