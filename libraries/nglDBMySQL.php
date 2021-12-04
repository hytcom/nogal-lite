<?php
/*
# Nogal
*the most simple PHP Framework* by hytcom.net
GitHub @hytcom
___
  
# mysql
## nglDBMySQL *extends* nglBranch *implements* iNglDataBase [2018-08-21]
Gestor de conexciones con bases de datos MySQL

https://github.com/hytcom/wiki/blob/master/nogal/docs/mysql.md

*/
namespace nogal;

class nglDBMySQL extends nglBranch implements iNglDataBase {

	private $link;
	private $vModes;
	private $aQueries;

	final protected function __declareArguments__() {
		$vArguments							= [];
		$vArguments["autoconn"]				= ['self::call()->istrue($mValue)', false];
		$vArguments["base"]					= ['$mValue', "test"];
		$vArguments["charset"]				= ['$mValue', "utf8mb4"];
		$vArguments["check_colnames"]		= ['self::call()->istrue($mValue)', true];
		$vArguments["debug"]				= ['self::call()->istrue($mValue)', false];
		$vArguments["do"]					= ['self::call()->istrue($mValue)', false];
		$vArguments["engine"]				= ['$mValue', "MyISAM"];
		$vArguments["error_description"]	= ['self::call()->istrue($mValue)', false];
		$vArguments["error_query"]			= ['self::call()->istrue($mValue)', false];
		$vArguments["file"]					= ['$mValue', null];
		$vArguments["file_eol"]				= ['$mValue', "\\r\\n"];
		$vArguments["file_local"]			= ['self::call()->istrue($mValue)', true];
		$vArguments["file_separator"]		= ['$mValue', "\\t"];
		$vArguments["file_enclosed"]		= ['$mValue', '"'];
		$vArguments["host"]					= ['$mValue', "localhost"];
		$vArguments["insert_mode"]			= ['$mValue', "INSERT"];
		$vArguments["jsql"]					= ['$mValue', null];
		$vArguments["jsql_eol"]				= ['$mValue', ""];
		$vArguments["pass"]					= ['$mValue', "root"];
		$vArguments["port"]					= ['(int)$mValue', null];
		$vArguments["socket"]				= ['$mValue', null];
		$vArguments["sql"]					= ['$mValue', null];
		$vArguments["table"]				= ['(string)$mValue', null];
		$vArguments["update_mode"]			= ['strtoupper($mValue)', "UPDATE"];
		$vArguments["user"]					= ['$mValue', "root"];
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
		$vModes["UPDATE"] 		= "UPDATE";
		$vModes["REPLACE"] 		= "REPLACE";
		$vModes["IGNORE"] 		= "IGNORE";
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
		list($sHost, $sUser, $sPass, $sBase, $nPort, $sSocket) = $this->getarguments("host,user,pass,base,port,socket", \func_get_args());
		$sPass = self::passwd($sPass, true);
		$this->link = @new \mysqli($sHost, $sUser, $sPass, $sBase, $nPort, $sSocket);
		if($this->link->connect_error) {
			$this->Error();
			return false;
		}
		
		return $this;
	}

	public function chkgrants() {
		return $this->query("SHOW GRANTS FOR CURRENT_USER")->getall();
	}

	public function describe() {
		list($sTable) = $this->getarguments("table", \func_get_args());
		$sSplitter = self::call()->unique(6);
		$describe = $this->query("
			SELECT 
				`name`, `type`, `length`, `attributes`, `default`, `nullable`, `index`, `extra`, `comment` 
				FROM (
					SELECT 
						@type := REPLACE(REPLACE(`COLUMN_TYPE`, '(', '".$sSplitter."'), ')', '".$sSplitter."'),
						@len := ROUND((LENGTH(@type) - LENGTH(REPLACE(@type, '".$sSplitter."', '') )) / 6),
						`COLUMN_NAME` AS 'name', 
						TRIM(SUBSTRING_INDEX(@type, '".$sSplitter."', 1)) AS 'type',
						IF(@len>=2, TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(@type, '".$sSplitter."', 2), '".$sSplitter."', -1)), '') AS 'length',
						IF(@len>=2, TRIM(SUBSTRING_INDEX(@type, '".$sSplitter."', -1)), '') AS 'attributes',
						`COLUMN_DEFAULT` AS 'default', 
						`IS_NULLABLE` AS 'nullable', 
						CASE 
							WHEN `COLUMN_KEY`='PRI' THEN 'PRIMARY' 
							WHEN `COLUMN_KEY`='UNI' THEN 'UNIQUE' 
							WHEN `COLUMN_KEY`='MUL' THEN 'INDEX' 
							ELSE '' 
						END AS 'index', 
						`EXTRA` AS 'extra', 
						`COLUMN_COMMENT` AS 'comment'
					FROM `information_schema`.`COLUMNS` 
					WHERE 
						`TABLE_SCHEMA` = '".$this->argument("base")."' AND 
						`TABLE_NAME` = '".$sTable."'
				) info
		");

		return ($describe->rows()) ? $describe->getall() : null;
	}

	public function destroy() {
		foreach($this->aQueries as $query) {
			self::call($query)->destroy();
		}
		$this->link->close();
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
						$mEscapedValues[$sField] = $this->link->real_escape_string($mValue);
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
					$mEscapedValues = $this->link->real_escape_string($mEscapedValues);
				}
			}
		}

		return $mEscapedValues;
	}

	public function exec() {
		list($sQuery) = $this->getarguments("sql", \func_get_args());
		if($this->argument("debug")) { return $sQuery; }
		if(!$query = @$this->link->query($sQuery)) {
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
		$sEnclosed	= \addslashes($this->argument("file_enclosed"));
		$sCharset	= $this->argument("charset");

		$sOutput = " 
			INTO OUTFILE '".$sFilePath."' 
			CHARACTER SET ".$sCharset." 
			FIELDS TERMINATED BY '".$sSeparator."' OPTIONALLY ENCLOSED BY '".$sEnclosed."' ESCAPED BY '\\\\\\'
			LINES TERMINATED BY '".$sEOL."' 
			FROM 
		";

		$sQuery = \preg_replace("/FROM/i", $sOutput, $sQuery, 1);
		return ($this->query($sQuery)) ? $sFilePath : false;
	}

	public function handler() {
		return $this->link;
	}

	public function ifexists() {
		list($sTable) = $this->getarguments("table", \func_get_args());
		$sBase = $this->argument("base");
		$chk = $this->link->query("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '".$sBase."' AND TABLE_NAME = '".$sTable."' LIMIT 1");
		return $chk->num_rows ? true : false;
	}

	public function import() {
		list($sFilePath,$sTable) = $this->getarguments("file,table", \func_get_args());
		if($sFilePath===null) { return false; }
		$sFilePath = self::call()->sandboxPath($sFilePath);
		if(!\file_exists($sFilePath)) { return false; }

		$sEOL		= $this->argument("file_eol");
		$sEnclosed	= \addslashes($this->argument("file_enclosed"));
		$sLocal		= ($this->argument("file_local")==true) ? "LOCAL" : "";
		$sSeparator	= $this->argument("file_separator");
		$sCharset	= $this->argument("charset");
		$sEngine	= $this->argument("engine");

		$nChk = $this->query("SHOW TABLES LIKE '".$sTable."'")->rows();
		if(!$nChk) {
			if(($fp=@\fopen($sFilePath, "r"))!==FALSE) {
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
				$sCreate = "CREATE TABLE `".$sTable."` (`".implode("` TEXT NULL, `", $aColumns)."` TEXT NULL) ENGINE=".$sEngine." DEFAULT CHARSET=".$sCharset.";";

				if($this->query($sCreate)===null) { return false; }
			}
		}

		$sInput = " 
			LOAD DATA ".$sLocal." INFILE '".$sFilePath."' 
			INTO TABLE `".$sTable."`  
			CHARACTER SET ".$sCharset." 
			FIELDS TERMINATED BY '".$sSeparator."' OPTIONALLY ENCLOSED BY '".$sEnclosed."' ESCAPED BY '\\\\' 
			LINES TERMINATED BY '".$sEOL."' 
		";

		$bLoad = ($this->query($sInput)===null) ? false : true;
		if($bLoad===true && !$nChk) { $this->query("DELETE FROM `".$sTable."` LIMIT 1"); }
		return $bLoad;
	}

	public function insert() {
		list($sTable, $mValues, $sMode, $bCheckColumns, $bDO) = $this->getarguments("table,values,insert_mode,check_colnames,do", \func_get_args());
		if(!empty($sTable)) {
			$aToInsert = $this->PrepareValues("INSERT", $sTable, $mValues, $bCheckColumns);
			if(\is_array($aToInsert) && \count($aToInsert)) {
				$sMode = \strtoupper($sMode);
				$sInsertMode = (isset($this->vModes[$sMode])) ? $this->vModes[$sMode] : "INSERT";
				if($sInsertMode=="IGNORE") { $sInsertMode = "INSERT IGNORE"; }
				$sSQL  = $sInsertMode." INTO `".$sTable."` ";
				$sSQL .= "(`".\implode("`, `", \array_keys($aToInsert))."`) ";
				$sSQL .= "VALUES (".\implode(",", $aToInsert).")";
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
				if($sField==="RANDOM") { $aOrder[] = "RAND()"; break; }
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
		$sQuery = \preg_replace(["/^--.*$/m", "/^\/\*(.*?)\*\//m"], "", $sQuery);
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
		$sCharSet = $this->argument("charset");
		$this->link->query("SET NAMES ".$sCharSet);

		$sQuery = \trim($sQuery);
		if(empty($sQuery)) { return null; }

		$nTimeIni = \microtime(true);
		$this->attribute("last_query", $sQuery);
		if(!$query = $this->link->query($sQuery)) {
			return $this->Error();
		}

		if($bDO) {
			if(\method_exists($query, "free")) { $query->free(); }
			return true;
		}

		$nQueryTime = self::call("dates")->microtimer($nTimeIni);
		$sQueryName = "mysqlq".\strstr($this->me, ".")."_".self::call()->unique();
		$this->aQueries[] = $sQueryName;
		return self::call($sQueryName)->load($this->link, $query, $sQuery, $nQueryTime);
	}

	public function update() {
		list($sTable, $mValues, $sWhere, $sMode, $bCheckColumns, $bDO) = $this->getarguments("table,values,where,update_mode,check_colnames,do", \func_get_args());

		if(!empty($sTable)) {
			$aToUpdate = $this->PrepareValues("UPDATE", $sTable, $mValues, $bCheckColumns);
			if(\is_array($aToUpdate) && \count($aToUpdate)) {
				$sMode = \strtoupper($sMode);
				$sUpdateMode = (isset($this->vModes[$sMode])) ? $this->vModes[$sMode] : "";
				$sSQL = $sUpdateMode." `".$sTable."` SET ".\implode(", ", $aToUpdate)." WHERE ".$sWhere;
				return $this->query($sSQL, $bDO);
			}
		}
		
		return null;
	}

	private function Error() {
		$sMsgError = "";
		if(!$this->link->connect_error && $this->argument("error_description")) {
			$sMsgError = $this->link->error;
		} else if($this->link->connect_error && $this->argument("error_description")) {
			$sMsgError = "unknown database ".$this->argument("base");
		}

		if(!$this->link->connect_error && $this->argument("error_query")) {
			$sMsgError .= " -> ". $this->attribute("last_query");
		}

		$nError = ($this->link->connect_error) ? 1049 : $this->link->errno;
		return self::errorMessage("MySQL", $nError, $sMsgError);
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
			$columns = $this->link->query("DESCRIBE `".$sTable."`");
			$aFields = [];
			while($aGetColumn = $columns->fetch_array(MYSQLI_ASSOC)) {
				$aFields[] = $aGetColumn["Field"];
			}
			$columns->free();
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
					$aNewValues[] = "`".$sField."` = ".$mValue."";
				}
			}
		}
		
		return $aNewValues;
	}
}

?>