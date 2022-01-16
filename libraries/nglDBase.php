<?php
/*
# Nogal
*the most simple PHP Framework* by hytcom.net
GitHub @hytcom
___
  
# mysql
## nglDBase *extends* nglBranch *implements* iNglDataBase [2020-03-30]
Gestor de conexciones con dbase

https://github.com/hytcom/wiki/blob/master/nogal/docs/dbase.md

*/
namespace nogal;

class nglDBase extends nglBranch implements iNglDataBase {

	private $link;
	private $vModes;
	private $sTable;
	private $aFields;
	private $aFieldsUnset;
	private $aResult;
	private $nResult;
	private $nRows;
	private $bUTF8;

	final protected function __declareArguments__() {
		$vArguments							= [];
		$vArguments["autoconn"]				= ['self::call()->istrue($mValue)', false];
		$vArguments["base"]					= ['(string)$mValue', null];
		$vArguments["table"]				= ['(string)$mValue', null];
		$vArguments["get_mode"]				= ['$this->GetMode($mValue)', 3];
		$vArguments["utf8"]					= ['$this->UTF8($mValue)', true];
		$vArguments["deleted"]				= ['self::call()->istrue($mValue)', false];
		
		
		$vArguments["mode"]					= ['(int)$mValue', 2];
		$vArguments["debug"]				= ['self::call()->istrue($mValue)', false];
		$vArguments["sql"]					= ['$mValue', null];

		return $vArguments;
	}

	final protected function __declareAttributes__() {
		$vAttributes						= [];
		return $vAttributes;
	}

	final protected function __declareVariables__() {
	}

	final public function __init__() {
		$this->aResult = null;
		$this->nResult = 0;
		$this->aFields = [];
		$this->aFieldsUnset = [];
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
		return $this->link->dbase_close();
	}

	/** FUNCTION {
		"name" : "connect",
		"type" : "public",
		"description" : "Establece la conexión con la base de datos",
		"parameters" : { 
			"$sBase" : ["string", "", "argument::base"]
		},
		"return": "$this"
	} **/
	public function connect() {
		list($sBase,$nMode) = $this->getarguments("base,mode", \func_get_args());
		$nMode = ((int)$nMode===0) ? 0 : 2;
		$sBase = self::call()->sandboxPath($sBase);
		$this->link = \dbase_open($sBase, $nMode);
		$this->sTable = \basename(\strtolower($sBase), ".dbf");
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

	public function handler() {
		return $this->link;
	}

	public function query() {
		list($sQuery) = $this->getarguments("sql", \func_get_args());
		$aQuery = self::call("qparser")->query($sQuery);
		if($aQuery!==false) {
			switch($aQuery[0]) {
				case "SELECT":
					$this->Select($aQuery[1]);
					$this->nRows = \count($this->aResult);
					break;

				case "DESCRIBE":
					return $this->Describe($aQuery[1]);
					break;
			}
		}
		return $this;
	}

	public function get() {
		list($sColumn,$nMode) = $this->getarguments("column,get_mode", \func_get_args());
		if(@$aRow = $this->Fetch($nMode)) {
			if(!empty($sColumn) && $sColumn[0]=="#") { $sColumn = \substr($sColumn, 1); }
			return ($sColumn!==null && \array_key_exists($sColumn, $aRow)) ? $aRow[$sColumn] : $aRow;
		} else {
			return false;
		}
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

		$this->nResult = 0;
		$aRow = $this->Fetch($nMode);

		$aGetAll = [];
		if($sColumn!==null && $aRow!==false && $aRow!==null && !\array_key_exists($sColumn, $aRow)) { return $aGetAll; }
		$this->nResult = 0;

		if($sColumn!==null) {
			if($bIndexMode) {
				$aMultiple = [];
				while(@$aRow = $this->Fetch($nMode)) {
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
				while(@$aRow = $this->Fetch($nMode)) {
					$aGetAll[$aRow[$sColumn]] = $aRow[$sValue];
				}			
			} else {
				while(@$aRow = $this->Fetch($nMode)) {
					$aGetAll[] = $aRow[$sColumn];
					
				}			
			}
		} else {
			while(@$aRow = $this->Fetch($nMode)) {
				$aGetAll[] = $aRow;
			}
		}

		if($bGroupByMode) {
			$aGetAll = self::call()->arrayGroup($aGetAll, $aGroup);
		}

		$this->reset();
		return $aGetAll;
	}

	public function allrows() {
		return \dbase_numrecords($this->link);
	}

	public function rows() {
		return $this->nRows;
	}

	public function reset() {
		$this->nResult = 0;
	}

	public function columns() {
		return $this->aFields;
	}

	public function escape() {
	}

	public function exec() {
	}

	public function mexec() {
	}

	public function mquery() {
	}
	
	public function insert() {
	}

	public function update() {
	}

	private function PrepareValues($sType, $sTable, $mValues, $bCheckColumns) {
	}

	private function Fetch($nMode) {
		if(@$aRow = \dbase_get_record_with_names($this->link, $this->aResult[$this->nResult])) {
			foreach($this->aFieldsUnset as $sUnset) { unset($aRow[$sUnset]); }
			if($nMode===2) { $aRow = \array_values($aRow); }
			if($nMode===1) { $aRow = \array_merge($aRow, \array_values($aRow)); }
			$this->nResult++;
			return ($this->bUTF8) ? \array_map("utf8_encode", $aRow) : $aRow;
		} else {
			$this->nResult = 0;
			return false;
		}
	}

	private function Select($aQuery) {
		$aReturn = [];

		$aNames = @\dbase_get_record_with_names($this->link, 1);
		$aFields = $aColumns = ($aNames!==false) ? \array_keys($aNames) : false;
		if($aFields===false) {
			$this->aResult = $aReturn;
			$this->nRows = 0;
			return false;
		}
		if($aQuery["FIELDS"][0]!="*") {
			$aFields = self::call()->arrayColumn($aQuery["FIELDS"], 1);
		}
		if(!$this->deleted) {
			if($nDeleted = \array_search("deleted", $aColumns)) { unset($aColumns[$nDeleted]); }
		}
		$this->aFields = $aFields;
		$this->aFieldsUnset = \array_diff($aFields, $aColumns);

		$nFrom = $n = 1;
		$this->nRows = $y = \dbase_numrecords($this->link);
		if(isset($aQuery["LIMIT"])) {
			if(isset($aQuery["LIMIT"][1])) {
				$nFrom = (int)$aQuery["LIMIT"][0] + 1;
				$this->nRows = (int)$aQuery["LIMIT"][1];
			} else {
				$this->nRows = (int)$aQuery["LIMIT"][0] + 1;
			}
		}

		if(isset($aQuery["WHERE"])) {
			$sTable = $aQuery["FROM"][0];
			if($sTable===1) { $sTable = $this->sTable; }
			for($x=$nFrom; $x<=$y; $x++) {
				$$sTable = \dbase_get_record_with_names($this->link, $x);
				if($$sTable===false) { break; }

				$n++;
				eval(self::call()->EvalCode("if(".$aQuery["WHERE"].") { \$bEval = true; } else { \$bEval = false; }"));
				if(!$bEval) { $n--; continue; }

				$aReturn[] = $x;
				if($n==$this->nRows) { break; }
			}
		} else {
			$aReturn = \range(1, $this->nRows);
		}

		$this->aResult = $aReturn;
		return true;
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
		$aModes["both"] 		= 1;
		$aModes["num"] 			= 2;
		$aModes["assoc"] 		= 3;
		$aModes[3] 				= 1;
		$aModes[2] 				= 2;
		$aModes[1]	 			= 3;

		$sMode = \strtolower($sMode);
		return (isset($aModes[$sMode])) ? $aModes[$sMode] : 3;
	}

	protected function UTF8($bVal) {
		$this->bUTF8 = self::call()->istrue($bVal);
		return $bVal;
	}

	private function Describe($aQuery) {
		return \dbase_get_header_info($this->link);
	}
}

?>