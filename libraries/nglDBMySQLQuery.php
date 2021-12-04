<?php
/*
# Nogal
*the most simple PHP Framework* by hytcom.net
GitHub @hytcom
___
  
# mysql
## nglDBMySQLQuery *extends* nglBranch *implements* iNglDBQuery [2018-08-21]
Controla los resultados generados por consultas a la bases de datos MySQL

https://github.com/hytcom/wiki/blob/master/nogal/docs/mysqlq.md

*/
namespace nogal;

class nglDBMySQLQuery extends nglBranch implements iNglDBQuery {

	private $db		= null;
	private $cursor = null;
	
	final protected function __declareArguments__() {
		$vArguments							= [];

		$vArguments["column"]				= ['$mValue', null];
		$vArguments["get_mode"]				= ['$this->GetMode($mValue)', \MYSQLI_ASSOC];
		$vArguments["link"]					= ['$mValue', null];
		$vArguments["query"]				= ['$mValue', null];
		$vArguments["sentence"]				= ['(string)$mValue', null];
		$vArguments["query_time"]			= ['$mValue', null];

		return $vArguments;
	}

	final protected function __declareAttributes__() {
		$vAttributes						= [];
		$vAttributes["sql"]					= null;
		$vAttributes["time"]				= null;
		$vAttributes["crud"]				= null;
		$vAttributes["_allrows"]			= null;
		$vAttributes["_columns"]			= null;
		$vAttributes["_rows"]				= null;
		
		return $vAttributes;
	}

	final protected function __declareVariables__() {
	}

	final public function __init__() {
	}

	public function allrows() {
		if($this->attribute("_allrows")!==null) { return $this->attribute("_allrows"); }

		$nRows = null;
		if($this->attribute("crud")=="SELECT") {
			$sSQL = $sSQLCheck = $this->attribute("sql");
			$sSQLCheck = \preg_replace("/(\t|\n|\r)/i", "", $sSQLCheck);
			$sSQLCheck = \preg_replace("/(\"(.*?)\"|'(.*?)'|`(.*?)`)/", "", $sSQLCheck);
			if(\preg_match("/(FOUND_ROWS)/i", $sSQLCheck)) { return null; }

			$sSQL = trim($sSQL);
			if(preg_match("/LIMIT *[0-9]+ *,? *[0-9]*$/i", $sSQL)) {
				$sSQL = \preg_replace("/LIMIT *[0-9]+ *,? *[0-9]*$/i", "", $sSQL);
				$sSQL = \preg_replace("/SELECT/i", "SELECT SQL_CALC_FOUND_ROWS", $sSQL, 1);
				$foundrows = $this->db->query($sSQL);
			
				$sRowsAlias = self::call()->unique();
				$sSQL = "
					SELECT FOUND_ROWS() AS '".$sRowsAlias."'
				";
				$getrows = $this->db->query($sSQL);
				
				$aRows = $getrows->fetch_array(MYSQLI_ASSOC);
				$nRows = (int)$aRows[$sRowsAlias];

				$foundrows->free();
				$getrows->free();
			} else {
				$nRows = (int)$this->rows();
			}
		}

		$this->attribute("_allrows", $nRows);
		return $nRows;
	}
	
	public function columns() {
		if($this->attribute("_columns")!==null) { return $this->attribute("_columns"); }

		$aGetColumns = [];
		$aCols = $this->cursor->fetch_fields();
		foreach($aCols as $column) {
			$aGetColumns[] = $column->name;
		}
		
		$this->attribute("_columns", $aGetColumns);
		return $aGetColumns;
	}

	public function count() {
		if($this->attribute("_rows")!==null) { return $this->attribute("_rows"); }
		if(\in_array($this->attribute("crud"), ["INSERT", "UPDATE", "REPLACE", "DELETE"])) {
			$nRows = $this->db->affected_rows;
		} else {
			$nRows = $this->cursor->num_rows;
		}
		
		$this->attribute("_rows", $nRows);
		return $nRows;
	}

	public function destroy() {
		if(!\is_bool($this->cursor)) { $this->free(); }
		$this->db = null;
		$this->cursor = null;
		return parent::__destroy__();
	}

	public function free() {
		$this->cursor->free_result();
		return $this;
	}

	public function get() {
		list($sColumn,$nMode) = $this->getarguments("column,get_mode", \func_get_args());
		$aRow = $this->cursor->fetch_array($nMode);
		if(!empty($sColumn) && $sColumn[0]=="#") { $sColumn = \substr($sColumn, 1); }
		return ($sColumn!==null && $aRow!==false && $aRow!==null && \array_key_exists($sColumn, $aRow)) ? $aRow[$sColumn] : $aRow;
	}

	public function getall() {
		list($sColumn,$nMode) = $this->getarguments("column,get_mode", \func_get_args());
		$bGroupByMode = $bIndexMode = $bKeyValue = false;

		if(\is_array($sColumn)) {
			$aGroup = $sColumn;
			$sColumn = null;
			$bGroupByMode = true;
		} else {
			if(!empty($sColumn) && $sColumn[0]=="#") {
				$sColumn = \substr($sColumn, 1);
				$bIndexMode = true;
			}

			if(!empty($sColumn) && $sColumn[0]=="@") {
				$aColumn = \explode(";", \substr($sColumn, 1));
				$sColumn = $aColumn[0];
				$sValue = (\count($aColumn)>1) ? $aColumn[1] : $aColumn[0];
				$bKeyValue = true;
			}			
		}

		$this->reset();
		$aRow = $this->cursor->fetch_array($nMode);

		$aGetAll = [];
		if($sColumn!==null && $aRow!==false && $aRow!==null && !\array_key_exists($sColumn, $aRow)) { return $aGetAll; }
		$this->reset();

		if($sColumn!==null) {
			if($bIndexMode) {
				$aMultiple = [];
				while($aRow = $this->cursor->fetch_array($nMode)) {
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
			} else if($bKeyValue) {
				while($aRow = $this->cursor->fetch_array($nMode)) {
					$aGetAll[$aRow[$sColumn]] = $aRow[$sValue];
				}			
			} else {
				while($aRow = $this->cursor->fetch_array($nMode)) {
					$aGetAll[] = $aRow[$sColumn];
				}			
			}
		} else {
			while($aRow = $this->cursor->fetch_array($nMode)) {
				$aGetAll[] = $aRow;
			}
		}

		if($bGroupByMode) {
			$aGetAll = self::call()->arrayGroup($aGetAll, $aGroup);
		}

		$this->reset();
		return $aGetAll;
	}

	public function getobj() {
		return $this->cursor->fetch_object();
	}

	public function lastid() {
		if($this->attribute("crud")=="INSERT" || $this->attribute("crud")=="REPLACE") {
			return $this->db->insert_id;
		} else {
			return null;
		}
	}

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

	public function reset() {
		$this->cursor->data_seek(0);
		return $this;
	}

	public function rows() {
		return $this->count();
	}

	public function toArray() {
		$this->reset();
		$aGetAll = [];
		while($aRow = $this->cursor->fetch_array(\MYSQLI_ASSOC)) {
			$aGetAll[] = $aRow;
		}
		$this->reset();
		return $aGetAll;
	}

	protected function GetMode($nMode) {
		$aModes 				= [];
		$aModes["both"] 		= \MYSQLI_BOTH;
		$aModes["num"] 			= \MYSQLI_NUM;
		$aModes["assoc"] 		= \MYSQLI_ASSOC;
		$aModes[3] 				= \MYSQLI_BOTH;
		$aModes[2] 				= \MYSQLI_NUM;
		$aModes[1] 				= \MYSQLI_ASSOC;

		$nMode = \strtolower($nMode);
		return (isset($aModes[$nMode])) ? $aModes[$nMode] : \MYSQLI_ASSOC;
	}
}

?>