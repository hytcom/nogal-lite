<?php

namespace nogal;

class nglJSQL extends nglFeeder implements inglFeeder {

	final public function __init__($mArguments=null) {
	}

	public function column($mField, $sAliasQuote="'", $sQuote="`", $sTableColumnGlue=".", $sAS="AS") {
		$aField = (\is_array($mField)) ? $mField : [$mField];
		$aFieldName = \explode($sTableColumnGlue, \trim($aField[0]));
		$sFieldName = $sQuote.\implode($sQuote.$sTableColumnGlue.$sQuote, $aFieldName).$sQuote;
		if(isset($aField[1])) {
			$sFieldName .= " ".$sAS." ".$sAliasQuote.$aField[1].$sAliasQuote." ";
		}
		return $sFieldName;
	}

	public function conditions($aSource, $bSetMode=false) {
		$aWhere = [];
		foreach($aSource as $mSource) {
			if(\is_string($mSource)) {
				if(!$bSetMode) {
					$aWhere[] = $this->operator($mSource);
					continue;
				}
			} else if(self::call()->isArrayArray($mSource)) {
				$aWhere[] = "(".$this->conditions($mSource, $bSetMode).")";
			} else {
				$aWhere[] = $this->condition($mSource);
			}
		}

		return ($bSetMode) ? \implode(", ", $aWhere) : \implode(" ", $aWhere);
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
		if(!empty($sString) && $sString[0]=="(") { $sString = \substr($sString, 1, -1); }
		if($bIsSet) {
			$sString = "'".\str_replace(",", "','", $sString)."'";
		} else {
			$sString = \str_replace("'", "\'", $sString);
		}
		return ($sString==="NULL") ? "NULL" : ($bQuoted ? "'".$sString."'" : $sString);
	}

	private function Condition($aCondition) {
		$sOperator = $this->operator($aCondition[1]);
		$sCondition  = ($aCondition[0][0]!="(") ? (is_int($aCondition[0]) ? $aCondition[0] : $this->column($aCondition[0])) : $this->value($aCondition[0]);
		
		if($sOperator=="IN" || $sOperator=="NOT IN") {
			$sCondition .= " ".$this->operator($aCondition[1])." (";
			if($aCondition[2][0]!="(") {
				// $sCondition .= is_int($aCondition[2]) ? $aCondition[2] : $this->column($aCondition[2]);
				$sCondition .= $aCondition[2];
			} else {
				// $bIsSet = preg_match("/[0-9a-z]+( ?, ?[0-9a-z]+)+/i", $aCondition[2]);
				$sCondition .= $this->value($aCondition[2], false, true);
			}
			$sCondition .= ") ";
		} else {
			$sCondition .= " ".$this->operator($aCondition[1])." ";
			$sCondition .= ($aCondition[2][0]!="(") ? (is_int($aCondition[2]) ? $aCondition[2] : $this->column($aCondition[2])) : $this->value($aCondition[2]);
		}

		return $sCondition;
	}
}

?>