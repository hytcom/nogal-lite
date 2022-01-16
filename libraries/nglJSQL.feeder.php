<?php

namespace nogal;

class nglJSQL extends nglFeeder implements inglFeeder {

	protected $db;
	protected $aTypes;

	final public function __init__($mArguments=null) {
		if($mArguments!==null) { $this->db($this->parser($mArguments[0])); }
		
		$aTypes = [
			"TINYINT",
			"SMALLINT",
			"MEDIUMINT",
			"INT",
			"BIGINT",
			"DECIMAL",
			"FLOAT",
			"DOUBLE",
			"CHAR",
			"VARCHAR",
			"TINYTEXT",
			"TEXT",
			"MEDIUMTEXT",
			"BIGTEXT",
			"JSON",
			"TINYBLOB",
			"BLOB",
			"MEDIUMBLOB",
			"BIGBLOB",
			"ENUM",
			"DATE",
			"TIME",
			"DATETIME",
			"TIMESTAMP",
			"YEAR"
		];
		$aDataTypes = $this->datatypes();
		foreach($aTypes as $sType) {
			if(!\array_key_exists($sType, $aDataTypes)) {
				self::errorMessage("jsql", 1002, $sType);
			}
		}
		$this->aTypes = $aDataTypes;
	}

	public function db($db) {
		$this->db = $db;
		return $this;
	}

	public function query($mJSQL, $bRun=false) {
		$aJSQL = (\is_string($mJSQL)) ? $this->decode($mJSQL) : $mJSQL;
		$sQuery = (isset($aJSQL["query"])) ? \strtolower($aJSQL["query"]) : "select";

		$sSQL = "";
		switch($sQuery) {
			case "coladd": $sSQL = $this->colAdd($aJSQL); break;
			case "coldrop": $sSQL = $this->colDrop($aJSQL); break;
			case "colmodify": $sSQL = $this->colModify($aJSQL); break;
			case "colrename": $sSQL = $this->colRename($aJSQL); break;

			case "comment": $sSQL = $this->comment($aJSQL); break;
			case "create": $sSQL = $this->create($aJSQL); break;
			case "drop": $sSQL = $this->drop($aJSQL); break;
			case "index": $sSQL = $this->index($aJSQL); break;
			case "indexdrop": $sSQL = $this->indexDrop($aJSQL); break;
			
			case "delete":
			case "select":
				$sSQL = ($sQuery=="select") ? $this->select($aJSQL) : $this->delete($aJSQL); break;

			case "insert":
			case "update":
				$sSQL = ($sQuery=="insert") ? $this->insert($aJSQL) : $this->update($aJSQL); break;
			
			case "where":
				$sSQL = $this->where($aJSQL["where"]); break;
			
			case "rename": $sSQL = $this->rename($aJSQL); break;
		}

		$sSQL = \preg_replace("/ +/is", " ", $sSQL);
		return $bRun===false ? $sSQL : $this->db->query($sSQL);
	}

	public function where($aConditions, $bArrayMode=false) {
		$aWhere = [];
		foreach($aConditions as $mCondition) {
			if(\is_string($mCondition)) {
				$aWhere[] = $this->operator($mCondition);
			} else if(self::call()->isArrayArray($mCondition)) {
				$aWhere[] = !$bArrayMode ? "(".$this->where($mCondition).")" : [$this->where($mCondition)];
			} else {
				$aWhere[] = $this->condition($mCondition);
			}
		}
		return !$bArrayMode ? \implode(" ", $aWhere) : $aWhere;
	}

	public function operator($sString) {
		return self::call()->strOperator($sString, true);
	}

	public function decode($sString) {
		if(empty($sString)) { return null; }
		$aJSON = \json_decode($sString, true);
		if($aJSON===null) {
			self::errorMessage("jsql", 1001, $sString);
			return false;
		}

		return $aJSON;
	}

	public function encode($aArray) {
		return \json_encode($aArray);
	}

	public function value($sString, $bQuoted=true, $bIsSet=false) {
		$sString = \trim($sString);
		if($sString[0]!="[") {
			if($bIsSet) {
				$sString = "'".\str_replace(",", "','", $sString)."'";
			} else {
				$sString = \str_replace("'", "\'", $sString);
			}
			return ($sString==="NULL") ? "NULL" : ($bQuoted ? "'".$sString."'" : $sString);
		}
	}

	public function appener($sJSQL, $sExtra) {
		$aJSQL = $this->decode($sJSQL);
		$aExtra = $this->decode($sExtra);
		$aJSQL = self::call()->arrayAppend($aJSQL, $aExtra);
		return $this->encode($aJSQL);
	}

	public function condition($aCondition) {
		$sOperator = $this->operator($aCondition[1]);
		$sCondition  = $aCondition[0][0]=="[" ? $this->column(\substr($aCondition[0],1,-1)) : (is_int($aCondition[0]) ? $aCondition[0] : $this->value($aCondition[0]));
		
		if($sOperator=="IN" || $sOperator=="NOT IN") {
			$sCondition .= " ".$this->operator($aCondition[1])." (";
			$aSet = self::call()->explodeTrim(",", $aCondition[2]);
			foreach($aSet as &$sSet) {
				$sSet = \trim($sSet, "'");
				$sSet = $sSet[0]=="[" ? $this->column(\substr($sSet,1,-1)) : $this->value($sSet);
			}
			$sCondition .= \implode(", ", $aSet);
			$sCondition .= ") ";
		} else {
			$sCondition .= " ".$this->operator($aCondition[1])." ";
			$sCondition .= $aCondition[2][0]=="[" ? $this->column(\substr($aCondition[2],1,-1)) : (is_int($aCondition[2]) ? $aCondition[2] : $this->value($aCondition[2]));
		}

		return $sCondition;
	}
}

?>