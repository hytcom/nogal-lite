<?php
/*
# Nogal
*the most simple PHP Framework* by hytcom.net
GitHub @hytcom
___
  
# pecker
## nglPecker *extends* nglBranch [2018-08-15]
Operaciones con datos

https://github.com/hytcom/wiki/blob/master/nogal/docs/pecker.md

1001 = objeto de base de datos indefinido
1002 = 
1003 = tabla indefinida
1004 = grouper indefinido
1005 = columna indefinida
1006 = tabla inexistente
1007 = hash indefinido
1008 = campo key inexistente
1009 = la tabla para analizar esta vacía
1010 = tabla cruzada indefinida
1011 = datos de la tabla de caracteristicas indefinidos/erroneos/incompletos
1012 = falta analisis de la tabla

*/
namespace nogal;

class nglPecker extends nglBranch implements inglBranch {

	private $db;
	private $sSaveId;
	private $aSavedData;

	final protected function __declareArguments__() {
		$vArguments							= [];
		$vArguments["analyse_datatype"]		= ['self::call()->isTrue($mValue)', false];
		$vArguments["bridge"]				= ['$mValue', null];
		$vArguments["col"]					= ['(int)$mValue', null];
		$vArguments["cols"]					= ['$this->SetCols($mValue)', null];
		$vArguments["colscols"]				= ['(array)$mValue', null];
		$vArguments["datafile"]				= ['$this->SetDataFile($mValue)', "pecker"];
		$vArguments["db"]					= ['$this->SetDb($mValue)', "mysql"];
		$vArguments["exec"]					= ['$mValue', false];
		$vArguments["features"]				= ['$this->SetFeatures($mValue)', null];
		$vArguments["file"]					= ['$mValue', null];
		$vArguments["file_charset"]			= ['$mValue', null];
		$vArguments["file_eol"]				= ['$mValue', "\\r\\n"];
		$vArguments["force"]				= ['self::call()->isTrue($mValue)', false];
		$vArguments["grouper"]				= ['$this->SetGrouper($mValue)', null];
		$vArguments["hashappend"]			= ['self::call()->isTrue($mValue)', false];
		$vArguments["hittest"]				= ['$mValue', false]; // test | show | true
		$vArguments["id"]					= ['$mValue', null];
		$vArguments["key"]					= ['$this->SecureName($mValue)', null];
		$vArguments["length"]				= ['$mValue', 32];
		$vArguments["limit"]				= ['$mValue', 20];
		$vArguments["markas"]				= ['$mValue', "1"];
		$vArguments["markon"]				= ['$mValue', "pecked"];
		$vArguments["newnames"]				= ['(array)$mValue', null];
		$vArguments["output"]				= ['strtolower($mValue)', "print"]; // print | table | data
		$vArguments["overwrite"]			= ['self::call()->isTrue($mValue)', true];
		$vArguments["policy"]				= ['(array)$mValue', null];
		$vArguments["rules"]				= ['(array)$mValue', null];
		$vArguments["skip"]					= ['self::call()->isTrue($mValue)', false];
		$vArguments["splitter"]				= ['$mValue', "\\t"];
		$vArguments["table"]				= ['$this->SetTable($mValue)', null];
		$vArguments["tables"]				= ['(array)$mValue', null];
		$vArguments["truncate"]				= ['self::call()->isTrue($mValue)', false];
		$vArguments["where"]				= ['$mValue', null];
		$vArguments["xtable"]				= ['$this->SecureName($mValue)', null];
		return $vArguments;

	}

	final protected function __declareAttributes__() {
		$vAttributes						= [];
		$vAttributes["analysis"]			= null;
		$vAttributes["colstr"]				= null;
		$vAttributes["features_schema"]		= null;
		$vAttributes["grouperstr"]			= null;
		return $vAttributes;
	}

	final protected function __declareVariables__() {
	}

	final public function __init__() {
		$this->__errorMode__("die");
	}

	// analiza la tabla y sugiere el mejor tipo de dato para cada columna
	public function analyse() {
		list($sTable,$bForce) = $this->getarguments("table,force", \func_get_args());
		$this->ChkSource($sTable);
		if(isset($this->aSavedData["ANALYSIS"], $this->aSavedData["ANALYSIS"][$sTable]) && !$bForce) {
			$this->attribute("analysis", $this->aSavedData["ANALYSIS"][$sTable]);
			return $this->Output($this->attribute("analysis"));
		} else {
			if(!isset($this->aSavedData["ANALYSIS"])) { $this->aSavedData["ANALYSIS"] = []; }
			return $this->Output($this->BuildAnalysis($sTable));
		}
	}

	public function analyseAll() {
		list($aTables,$bForce) = $this->getarguments("tables,force", \func_get_args());
		if(\is_array($aTables) && \count($aTables)) {
			foreach($aTables as $sTable) {
				$this->analyse($sTable, $bForce);
			}
		}
		return $this;
	}

	public function backup() {
		list($sTable) = $this->getarguments("table", \func_get_args());
		$this->ChkSource($sTable);
		$sCreate = $this->db->query("SHOW CREATE TABLE `".$sTable."`")->get("Create Table");
		$sDate = \date("YmdHis");
		$sCreate = \str_replace("TABLE `".$sTable."` (", "TABLE `".$sTable."_".$sDate."` (", $sCreate);
		$this->db->query($sCreate);
		$insert = $this->db->query("INSERT INTO `".$sTable."_".$sDate."` SELECT * FROM `".$sTable."`");
		$this->Output(["table"=> $sTable."_".$sDate, "rows"=>$insert->rows()]);
	}

	public function backupAll() {
		list($aTables) = $this->getarguments("tables", \func_get_args());
		if(\is_array($aTables) && \count($aTables)) {
			foreach($aTables as $sTable) {
				$this->backup($sTable);
			}
		}
		return $this;
	}

	public function clear() {
		list($bRun) = $this->getarguments("exec", \func_get_args());
		$sTable = $this->ChkSource();
		$this->ChkHash();
		$nClean = 0;
		$clear = $this->db->query("SELECT `__pecker__`, (COUNT(*)-1) AS 'rows' FROM `".$sTable."` WHERE `__pecker__` IS NOT NULL GROUP BY `__pecker__` HAVING COUNT(*) > 1");
		if($clear->rows()) {
			while($aClear = $clear->get()) {
				$nClean += (int)$aClear["count"];
				if($bRun) { $this->db->query("DELETE FROM `".$sTable."` WHERE `__pecker__` = '".$aClear["__pecker__"]."' LIMIT ".$aClear["count"]); }
			}
		}

		$sTitle = ($bRun) ? "cleaned" : "to clean";
		return $this->Output([[$sTitle=>$nClean]]);
	}

	public function clearAll() {
		$this->ChkHash();
		$clear = $this->db->query("SELECT `__pecker__`, (COUNT(*)-1) AS 'rows' FROM `".$this->sTable."` WHERE `__pecker__` IS NOT NULL GROUP BY `__pecker__` HAVING COUNT(*) > 1");
		if($clear->rows()) {
			$this->db->query("DELETE FROM `".$this->sTable."` WHERE `__pecker__` IN (SELECT `__pecker__` FROM `".$this->sTable."` WHERE `__pecker__` IS NOT NULL GROUP BY `__pecker__` HAVING COUNT(*) > 1)");
		}
		return $this;
	}

	public function clearWano() {
		list($sField) = $this->getarguments("field", \func_get_args());
		$sTable = $this->ChkSource();
		$this->ChkHash();
		$blank = $this->db->query("DELETE FROM `".$sTable."` WHERE `__wano__` = '1'");
		return $this->Output([["affected"=>$blank->rows()]]);
	}

	public function colsid() {
		list($sXTable) = $this->getarguments("xtable", \func_get_args());
		$aAnalysis = $this->ChkAnalysis();
		$aCols = [];
		
		if($sXTable!==null) {
			$aXAnalysis = $this->ChkAnalysis($sXTable);
			$n = (\count($aXAnalysis) > \count($aAnalysis)) ? \count($aXAnalysis) : \count($aAnalysis);
			for($x=0; $x<$n; $x++) {
				$aCol = ["col"=>$x];
				$aCol["field"] = (isset($aAnalysis[$x])) ? $this->GetCols($x) : "";
				$aCol["x_col"] = $x;
				$aCol["x_field"] = (isset($aXAnalysis[$x])) ? $this->GetCols($x, $sXTable) : "";
				$aCols[] = $aCol;
			}
		} else {
			foreach($aAnalysis as $aRow) {
				$aCols[] = ["col"=>$aRow["col"], "field"=>$aRow["field"]];
			}
		}

		return $this->Output($aCols);
	}

	// completa campos de xtable con datos de la tabla principal
	public function complete() {
		list($sDestine,$aCols,$bOverwrite) = $this->getarguments("xtable,colscols,overwrite", \func_get_args());
		$sTable = $this->ChkSource();
		$chk = $this->db->query("SELECT COUNT(*) AS 'chk' FROM `".$sTable."` WHERE `__pecked__` IS NOT NULL");

		if($chk->get("chk")) {
			$aSource = $this->GetCols(\array_keys($aCols));
			$aDestine = $this->GetCols($aCols, $sDestine);
			$aFields = \array_combine($aSource, $aDestine);

			$nCount = 0;
			foreach($aFields as $sField => $sXField) {
				$sXField = "`a`.`".$sXField."`";
				$sField = "`b`.`".$sField."`";
				$sWhere = (!$bOverwrite) ? " (".$sXField." = '' OR ".$sXField." IS NULL) AND " : "";
				$sSQL = "
					UPDATE `".$sDestine."` a, `".$sTable."` b 
						SET ".$sXField." = ".$sField." 
					WHERE 
						".$sWhere." 
						(".$sField." IS NOT NULL) AND 
						`a`.`__pecked__` = `b`.`__pecked__`
				";
				$nCount += $this->db->query($sSQL)->rows();
			}
		}

		$this->Output([["completed"=>$nCount]]);
	}

	public function concat() {
		list($aCols,$nCol,$sSeparator,$sWhere) = $this->getarguments("cols,col,splitter,where", \func_get_args());
		$sTable = $this->ChkSource();
		if($sWhere===null) { $sWhere = " 1 "; }
		$chk = $this->db->query("SELECT COUNT(*) AS 'chk' FROM `".$sTable."` WHERE ".$sWhere);
		if($chk->get("chk")) {
			$sSeparator = \addslashes($sSeparator);
			$aConcat = [];
			$aColumns = $this->GetCols($aCols);
			foreach($aColumns as $sColname) {
				$aConcat[] = " IF(`".$sColname."`!='' AND `".$sColname."` IS NOT NULL, CONCAT(`".$sColname."`,'".$sSeparator."'), '') ";
			}
			$sConcat = " CONCAT(".\implode(",", $aConcat).") ";
			$sField = $this->GetCols($nCol);
			$concat = $this->db->query("UPDATE `".$sTable."` SET `".$sField."` = ".$sConcat." WHERE ".$sWhere);
			return $this->Output([["affected"=>$concat->rows()]]);
		}

		return $this->Output([["message"=>"empty result"]]);
	}

	public function drop($mCols) {
		$sTable = $this->ChkSource();
		$this->ChkAnalysis();
		if(!\is_array($mCols)) { $mCols = [$mCols]; }

		$aDrop = [];
		foreach($mCols as $nCol) {
			$aDrop[] = "DROP COLUMN `".$this->aAnalysis[$nCol]["field"]."`";
		}
		$sDrop = "ALTER TABLE `".$this->sTable."` ".\implode(" , ", $aDrop)." ;";

		if($this->db->query($sDrop)!==null) {
			unset($this->aAnalysis[$nCol]);
		}

		return $this;
	}

	// resumen de registros duplicados
	public function duplicates() {
		$sTable = $this->ChkSource();
		$this->ChkHash();
		$all = $this->db->query("SELECT COUNT(*) 'all' FROM `".$sTable."`");
		$aDuplicates = $all->get();
		$duplicates = $this->db->query("SELECT COUNT(DISTINCT `__pecker__`) 'uniques' FROM `".$sTable."` WHERE `__pecker__` IS NOT NULL");
		$aDuplicates["uniques"] = $duplicates->get("uniques");
		$duplicate = $this->db->query("SELECT `__pecker__` FROM `".$sTable."` WHERE `__pecker__` IS NOT NULL GROUP BY `__pecker__` HAVING COUNT(*) > 1");
		$aDuplicates["duplicates"] = $duplicate->rows();
		return $this->Output($aDuplicates);
	}

	public function equalizable() {
		list($sLimit) = $this->getarguments("limit", \func_get_args());
		$sTable = $this->ChkSource();
		$total = $this->db->query("
			SELECT SUM(`counter`.`total`) AS 'total' FROM (
				SELECT COUNT(DISTINCT `__pecker__`) AS 'total' 
				FROM `".$sTable."` 
				WHERE 
					`__pecker__` IN (
						SELECT 
							`__pecker__`
						FROM `".$sTable."` 
						WHERE 
							`__pecker__` IS NOT NULL 
						GROUP BY 
							`__pecker__` HAVING COUNT(*) > 1
					)
				GROUP BY `__pecker__` 
					HAVING (SUM(IF(`__pecked__` IS NULL, 0, 1)) BETWEEN 1 AND (COUNT(*)-1))
			) counter
		");
		$nTotal = $total->get("total");

		$chk = $this->db->query("
			SELECT 
				`__pecker__`, 
				COUNT(*) AS '__equalizable__', 
				'".$nTotal."' AS 'rows' 
			FROM `".$sTable."` 
			WHERE 
				`__pecker__` IN (
					SELECT 
						`__pecker__`
					FROM `".$sTable."` 
					WHERE 
						`__pecker__` IS NOT NULL 
					GROUP BY 
						`__pecker__` HAVING COUNT(*) > 1
				)
			GROUP BY `__pecker__` 
				HAVING (SUM(IF(`__pecked__` IS NULL, 0, 1)) BETWEEN 1 AND (COUNT(*)-1))
			ORDER BY 2 DESC
			LIMIT ".$sLimit."
		");
		return $this->Output($chk->getall());
	}

	public function equate() {
		$sTable = $this->ChkSource();
		$sEqTable = self::call()->unique();
		$this->db->query("
			CREATE TABLE `".$sEqTable."` 
				SELECT 
					`__pecker__`, `__pecked__` 
				FROM `".$sTable."` 
				WHERE 
					`__pecked__` IS NOT NULL AND 
					`__pecker__` IN (
						SELECT 
							`__pecker__`
						FROM `".$sTable."` 
						WHERE 
							`__pecker__` IS NOT NULL 
						GROUP BY 
							`__pecker__` HAVING COUNT(*) > 1
					)
		");
		$this->db->query("ALTER TABLE `".$sEqTable."` ADD INDEX `__pecker__` (`__pecker__`), ADD INDEX `__pecked__` (`__pecked__`)");
		$equate = $this->db->query("UPDATE `".$sTable."`, `".$sEqTable."` SET `".$sTable."`.`__pecker__` = `".$sEqTable."`.`__pecked__` WHERE `".$sTable."`.`__pecked__` IS NULL AND `".$sTable."`.`__pecker__` = `".$sEqTable."`.`__pecker__`");
		$this->db->query("DROP TABLE `".$sEqTable."`");

		return $this->Output($equate->affected());
	}

	public function fill() {
		list($sDestine,$aCols) = $this->getarguments("xtable,colscols", \func_get_args());
		$sTable = $this->ChkSource();
		$chk = $this->db->query("SELECT COUNT(*) AS 'chk' FROM `".$sTable."` WHERE `__pecked__` IS NOT NULL");

		if($chk->get("chk")) {
			$this->ChkAnalysis($sDestine);
			$aSelect = $this->GetCols(\array_keys($aCols));
			$aInsert = $this->GetCols($aCols, $sDestine);

			$sOwlFields = $sOwlValues = "";
			if($this->IsOwlTable($sFeaturesTable)) {
				$sOwlFields = " `id`, `imya`, `state`, ";
				$sOwlValues = " NULL, func.imya(), '1', ";
			}

			$sSQL = "INSERT INTO `".$sDestine."` (`__pecked__`, ".$sOwlFields." `".\implode("`,`", $aInsert)."`) ";
			$sSQL .= "SELECT `__pecked__`, ".$sOwlValues." `".\implode("`,`", $aSelect)."` FROM `".$sTable."` WHERE `__pecked__` IS NOT NULL GROUP BY `__pecked__`";
			$fill = $this->db->query($sSQL);
		}

		$this->Output([["inserted"=>$fill->rows()]]);
	}

	public function fillfeatures() {
		list($mCols,$sDestine,$sBridge) = $this->getarguments("cols,xtable,bridge", \func_get_args());
		$sTable = $this->ChkSource();
		if($mCols===null) { self::errorMessage($this->object, 1005); }
		if(\is_int($mCols)) { $mCols = [$mCols]; }
		$aColumns = $this->GetCols($mCols);

		if(!$sDestine) { self::errorMessage($this->object, 1010); }
		$aFeatures = $this->ChkFeatures();
		$sFeaturesTable = $aFeatures["table"];
		$sFeaturesId = $aFeatures["id"];
		$sFeaturesMatch = $aFeatures["match"];

		$sOwlFields = $sOwlValues = "";
		if($this->IsOwlTable($sFeaturesTable)) {
			// $sOwlFields = " `id`, `imya`, `state`, ";
			$sOwlValues = " NULL, func.imya(), '1', ";
		}

		$sSelect = " `t`.`__pecked__`, "; $sFrom = "";
		if($sBridge!==null) { list($sSelect, $sFrom) = $this->SetBridge($sBridge); }

		$nAffected = 0;
		foreach($aColumns as $sField) {
			$features = $this->db->query("
				INSERT INTO `".$sDestine."` 
					SELECT ".$sOwlValues." ".$sSelect." `f`.`".$sFeaturesId."` 
					FROM `".$sTable."` t 
						".$sFrom." 
						LEFT JOIN `".$sFeaturesTable."` f ON `f`.`".$sFeaturesMatch."` = `t`.`".$sField."`
					WHERE 
						`t`.`__pecked__` IS NOT NULL AND 
						`t`.`".$sField."` != '' AND 
						`t`.`".$sField."` IS NOT NULL 
					GROUP BY `t`.`__pecked__`
			");

			$nAffected += $features->rows();
		}

		$this->Output([["affected"=>$nAffected]]);
	}

	public function filltest() {
		$sTable = $this->ChkSource();
		$chk = $this->db->query("SELECT COUNT(DISTINCT `__pecked__`) AS 'chk' FROM `".$sTable."` WHERE `__pecked__` IS NOT NULL");
		$this->Output([["to insert"=>$chk->get("chk")]]);
	}

	public function filter($lambda=null) {
		if($lambda===null) { return \array_keys($this->aAnalysis); }
		return \array_keys(\array_filter($this->aAnalysis, $lambda));
	}

	public function getgrouper() {
		return $this->Output($this->attribute("grouperstr"));
	}

	// crea la columa __pecker__
	public function hash() {
		list($sTable,$aGrouper,$aPolicy) = $this->getarguments("table,grouper,policy", \func_get_args());

		$sGrouper = $this->attribute("grouperstr");
		if(!$sGrouper) { self::errorMessage($this->object, 1004); }

		$chk = $this->db->query("SELECT * FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA`='".$this->db->base."' AND `TABLE_NAME`='".$sTable."' AND `COLUMN_NAME`='__pecker__'");
		if(!$chk->rows()) {
			$this->db->query("ALTER TABLE `".$sTable."` ADD COLUMN `__wano__` TINYINT(1) NULL DEFAULT NULL FIRST, ADD INDEX `__wano__` (`__wano__`)");
			$this->db->query("ALTER TABLE `".$sTable."` ADD COLUMN `__pecked__` CHAR(32) NULL DEFAULT NULL FIRST, ADD INDEX `__pecked__` (`__pecked__`)");
			$this->db->query("ALTER TABLE `".$sTable."` ADD COLUMN `__pecker__` CHAR(32) NULL DEFAULT NULL FIRST, ADD INDEX `__pecker__` (`__pecker__`)");
		}

		$sToHash = (\strstr($sGrouper, ",")) ? "CONCAT(".$sGrouper.")" : $sGrouper;
		if(\is_array($aPolicy)) {
			$sToHash = $this->Sanitizer($sToHash, $aPolicy);
		}

		if($this->argument("hashappend")) {
			$hashing = $this->db->query("UPDATE `".$sTable."` SET `__pecker__` = IF(LENGTH(".$sToHash.")>0, MD5(CONCAT(`__pecker__`, ".$sToHash.")), `__pecker__`) WHERE `__pecked__` IS NOT NULL");
		} else {
			$sWhere = ($this->argument("skip")) ? "WHERE `__pecked__` IS NULL" : "";
			$hashing = $this->db->query("UPDATE `".$sTable."` SET `__pecker__` = IF(LENGTH(".$sToHash.")>0, MD5(".$sToHash."), NULL) ".$sWhere);
		}
		$this->ClearAnalysis();
		$this->BuildAnalysis($sTable);
		return $this->Output([["affected"=>$hashing->rows()]]);
	}

	// marca los registros de la tabla principal con el campo key de la tabla secundaria,
	// donde los campos __pecker__ de ambas tablas sean iguales
	public function hit() {
		list($sXTable,$sHitTest,$sLimit,$aCols) = $this->getarguments("xtable,hittest,limit,cols", \func_get_args());
		$sHitTest = \trim(\strtolower($sHitTest));
		$sTable = $this->ChkSource();
		$this->ChkHash();
		$this->ChkHash($sXTable);

		if($sHitTest==="test") {
			$nRows = $this->db->query("SELECT COUNT(*) AS 'rows' FROM `".$sTable."`")->get("count");
			$test = $this->db->query("
				SELECT COUNT(DISTINCT `".$sTable."`.`__pecker__`) AS 'hits', '".$nRows."' AS 'rows'  
				FROM `".$sTable."`, `".$sXTable."` 
				WHERE 
					`".$sTable."`.`__pecker__` IS NOT NULL AND 
					`".$sTable."`.`__pecked__` IS NULL AND 
					`".$sTable."`.`__pecker__` = `".$sXTable."`.`__pecker__`
			");
			return $this->Output($test->getall());
		} else if($sHitTest==="show") {
			if(!isset($this->aSavedData["GROUPER"][$sTable], $this->aSavedData["GROUPER"][$sXTable])) { self::errorMessage($this->object, 1004); }
			$aFields = [];
			foreach($this->aSavedData["GROUPER"][$sTable] as $sField) {
				$aFields[] = "`".$sTable."`.`".$sField."`";
			}
			foreach($this->aSavedData["GROUPER"][$sXTable] as $sField) {
				$aFields[] = "`".$sXTable."`.`".$sField."` AS 'x_".$sField."'";
			}

			if(\is_array($aCols)) {
				$this->ChkAnalysis();
				$this->ChkAnalysis($sXTable);

				$aFields[] = "'' AS '.'";
				foreach($aCols as $nCol => $nXCol) {
					$aFields[] = "`".$sTable."`.`".$this->GetCols($nCol, $sTable)."`";
					$aFields[] = "`".$sXTable."`.`".$this->GetCols($nXCol, $sXTable)."` AS 'x_".$this->GetCols($nXCol, $sXTable)."'";
				}
			}

			$test = $this->db->query("
				SELECT 
					".\implode(", ", $aFields)." 
				FROM `".$sTable."`, `".$sXTable."` 
				WHERE 
					`".$sTable."`.`__pecked__` IS NULL AND 
					`".$sTable."`.`__pecker__` IS NOT NULL AND 
					`".$sTable."`.`__pecker__` = `".$sXTable."`.`__pecker__` 
					ORDER BY RAND() LIMIT ".$sLimit
			);
			return $this->Output($test->getall());
		} else {
			$sKey = $this->argument("key");
			$pecked = $this->db->query("
				UPDATE `".$sTable."`, `".$sXTable."` 
					SET `".$sTable."`.`__pecked__` = `".$sXTable."`.`".$sKey."` 
					WHERE 
						`".$sTable."`.`__pecked__` IS NULL AND 
						`".$sTable."`.`__pecker__` IS NOT NULL AND 
						`".$sTable."`.`__pecker__` = `".$sXTable."`.`__pecker__`
			");
			return $this->Output([["affected"=>$pecked->rows()]]);
		}
	}

	// analiza mejores tipos de campos para las columnas
	public function improve() {
		list($mCols) = $this->getarguments("cols", \func_get_args());
		$sTable = $this->ChkSource();
		$this->ChkAnalysis();
		$aAnalysis = $this->attribute("analysis");
		$aCols = $this->GetCols($mCols);

		$aChange = [];
		foreach($aCols as $nCol => $sField) {
			$sChange  = "CHANGE COLUMN `".$sField."` `".$sField."` ";
			$sChange .= $aAnalysis[$nCol]["improve"];
			if($aAnalysis[$nCol]["improve"]=="char" || $aAnalysis[$nCol]["improve"]=="varchar") {
				$aLengths = [4,8,16,32,64,128,255,$aAnalysis[$nCol]["length"]];
				\sort($aLengths);
				$nIdx = \array_search($aAnalysis[$nCol]["length"], $aLengths);
				$sChange .= " (".$aLengths[$nIdx+1].") ";
			}
			$sChange .= " NULL DEFAULT NULL";
			$aChange[] = $sChange;
		}
		$sImprove = "ALTER TABLE `".$sTable."` ".implode(" ,\n ", $aChange)." ;";

		$this->db->query($sImprove);
		return $this->Output($this->BuildAnalysis($sTable));
	}

	public function loadfile() {
		list($sFileName,$bTruncate) = $this->getarguments("file,truncate", \func_get_args());
		$sFileName = self::call()->sandboxPath($sFileName);
		if(\file_exists($sFileName)) {
			$sLoadFile = $sFileName;
			$sType = \strtolower(\pathinfo($sFileName, PATHINFO_EXTENSION));
			
			$sSplitter = $this->argument("splitter");
			if($sType=="xls" || $sType=="xlsx") {
				$sLoadFile = "/tmp/".self::call()->unique(8).".csv";
				self::call("excel")->load($sFileName)->csv_splitter($sSplitter)->write($sLoadFile);
			}
			\chmod($sLoadFile, 0777);
			
			$sTable = $this->argument("table");
			if($bTruncate) {
				if($this->IfTableExist($sTable)) { $this->db->query("TRUNCATE TABLE `".$sTable."`"); }
			}
			$this->db->file_eol($this->argument("file_eol"));
			$this->db->file_separator($sSplitter);
			if($this->argument("file_charset")!=null) { $this->db->charset($this->argument("file_charset")); }
			$this->db->import($sLoadFile, $sTable);
		}

		return $this;
	}

	public function mark() {
		list($sWhere,$mMark,$sType) = $this->getarguments("where,markas,markon", \func_get_args());
		$sTable = $this->ChkSource();

		$chk = $this->db->query("SELECT COUNT(*) AS 'chk' FROM `".$sTable."` WHERE ".$sWhere);
		if($chk->get("chk")) {
			$sType = (\strtolower($sType)=="pecked") ? "__pecked__" : "__wano__";
			$sMark = ($mMark===null) ? "NULL" : 1;
			$pecked = $this->db->query("UPDATE `".$sTable."` SET `".$sType."` = ".$sMark." WHERE ".$sWhere);
			return $this->Output([["affected"=>$pecked->rows()]]);
		}
		return $this->Output([["message"=>"empty result"]]);
	}

	// normaliza los datos de una columna basandose en la tabla features
	// antes de normalizar, agrega a features los valores inexistentes
	public function normalize() {
		list($nCol) = $this->getarguments("col", \func_get_args());
		$sTable = $this->ChkSource();
		$sField = $this->GetCols($nCol);
		$aFeatures = $this->ChkFeatures();
		$sFeaturesTable = $aFeatures["table"];
		$sFeaturesId = $aFeatures["id"];
		$sFeaturesMatch = $aFeatures["match"];

		$this->uniques([$nCol], true);

		$sFieldBack = $sField."_".self::call()->unique(6);
		$this->db->query("ALTER TABLE `".$sTable."` RENAME COLUMN `".$sField."` TO `".$sFieldBack."`");
		$this->db->query("ALTER TABLE `".$sTable."` ADD COLUMN `".$sField."` INT NULL DEFAULT NULL, ADD INDEX `".$sField."` (`".$sField."`), ADD INDEX `".$sFieldBack."` (`".$sFieldBack."`)");
		$nLength = $this->db->query("SELECT `CHARACTER_MAXIMUM_LENGTH` FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA`='".$this->db->base."' AND `TABLE_NAME`='".$sFeaturesTable."' AND `COLUMN_NAME` = '".$sFeaturesMatch."'")->get("CHARACTER_MAXIMUM_LENGTH");
		$normalize = $this->db->query("
			UPDATE 
				`".$sTable."` t, `".$sFeaturesTable."` f 
				SET `t`.`".$sField."` = `f`.`".$sFeaturesId."` 
			WHERE 
				`t`.`".$sFieldBack."` != '' AND 
				`t`.`".$sFieldBack."` IS NOT NULL AND 
				LEFT(`t`.`".$sFieldBack."`, ".$nLength.") = `f`.`".$sFeaturesMatch."`
		");

		$this->BuildAnalysis($sTable);
		$this->Output([["normalized"=>$normalize->rows()]]);
	}

	public function notpecked() {
		$sTable = $this->ChkSource();
		$this->ChkHash();
		$not = $this->db->query("SELECT SUM(IF(`__pecked__` IS NULL, 1, 0)) AS 'not', COUNT(*) AS 'rows' FROM `".$sTable."`");
		if($not->rows()) {
			return $this->Output($not->getall());
		}
	}

	public function peck() {
		$sTable = $this->ChkSource();
		$this->ChkHash();
		$peck = $this->db->query("UPDATE `".$sTable."` SET `__pecked__` = `__pecker__` WHERE `__pecker__` IS NOT NULL AND `__pecked__` IS NULL");
		return $this->Output($peck->affected());
	}

	public function pecked() {
		$sTable = $this->ChkSource();
		$this->ChkHash();
		$not = $this->db->query("SELECT SUM(IF(`__pecked__` IS NOT NULL, 1, 0)) AS 'pecked', COUNT(*) AS 'rows' FROM `".$sTable."`");
		if($not->rows()) {
			return $this->Output($not->getall());
		}
	}

	public function pecknulls() {
		$sTable = $this->ChkSource();
		$this->ChkHash();
		$peck = $this->db->query("UPDATE `".$sTable."` SET `__pecked__` = func.imya() WHERE `__pecked__` IS NULL");
		return $this->Output($peck->affected());
	}

	public function rename() {
		list($aRename) = $this->getarguments("newnames", \func_get_args());
		$sTable = $this->ChkSource();
		$aAnalysis = $this->ChkAnalysis();

		$aRen = [];
		foreach($aRename as $nCol => $sNewName) {
			$aRen[] = "RENAME COLUMN `".$aAnalysis[$nCol]["field"]."` TO `".$sNewName."`";
		}
		$sRename = "ALTER TABLE `".$sTable."` ".\implode(" , ", $aRen)." ;";

		if($this->db->query($sRename)!==null) {
			foreach($aRename as $nCol => $sNewName) {
				$aAnalysis[$nCol]["field"] = $sNewName;
			}
			$this->UpdateAnalysis($sTable, $aAnalysis);
		}

		return $this->Output($this->analyse());
	}

	// resetea la columna __pecker__
	public function reset() {
		list($sTable,$sWhere) = $this->getarguments("table,where", \func_get_args());
		$this->ChkSource();
		if($sWhere===null) { $sWhere = " 1 "; } 
		if($this->ChkHash(null, false)) { $this->db->query("UPDATE `".$sTable."` SET `__pecker__` = NULL WHERE ".$sWhere); }
		return $this;
	}

	// resetea las columnas __pecker__, __pecked__ y __wano__
	public function resetAll() {
		list($sTable,$sWhere) = $this->getarguments("table,where", \func_get_args());
		$this->ChkSource();
		if($sWhere===null) { $sWhere = " 1 "; } 
		if($this->ChkHash(null, false)) { $this->db->query("UPDATE `".$sTable."` SET `__pecker__` = NULL, `__pecked__` = NULL, `__wano__` = NULL WHERE ".$sWhere); }
		return $this;
	}

	public function sanitize() {
		list($mCols,$aPolicy,$sWhere) = $this->getarguments("cols,policy,where", \func_get_args());
		if($sWhere===null) { $sWhere = 1; }
		$sTable = $this->ChkSource();
		$chk = $this->db->query("SELECT COUNT(*) AS 'chk' FROM `".$sTable."` WHERE ".$sWhere);
		if($chk->get("chk")) {
			$aToUpdate = [];
			$mFields = $this->GetCols($mCols);
			if(!\is_array($mFields)) { $mFields = [$mFields]; }
			foreach($mFields as $sField) {
				$aToUpdate[] = "`".$sField."` = ".$this->Sanitizer($sField, $aPolicy);
			}
			// die("UPDATE `".$sTable."` SET ".implode(",", $aToUpdate)." WHERE ".$sWhere);
			$sanitized = $this->db->query("UPDATE `".$sTable."` SET ".implode(",", $aToUpdate)." WHERE ".$sWhere);
			return $this->Output([["affected"=>$sanitized->rows()]]);
		}

		return $this->Output([["message"=>"empty result"]]);	
	}

	// muestra los registros donde __pecker__ = id
	public function show() {
		list($sId) = $this->getarguments("id", \func_get_args());
		$sTable = $this->ChkSource();
		$sKey = $this->ChkKey();
		$sKey = ($sKey!==null) ? "`".$sKey."`, " : "";
		$this->ChkHash();
		$sFields = ($this->attribute("colstr")!==null) ? $this->attribute("colstr") : "`".$sTable."`.* ";
		$sId = $this->db->escape($sId);
		$show = $this->db->query("
			SELECT 
				`__pecker__`, ".$sKey." ".$sFields." 
			FROM `".$sTable."` 
			WHERE `__pecker__` = '".$sId."'
		");
		return $this->Output($show->getall());
	}

	// muestra los duplicados
	public function twins() {
		list($sLimit) = $this->getarguments("limit", \func_get_args());
		$sTable = $this->ChkSource();
		$this->ChkHash();
		$sKey = $this->ChkKey();
		$sKey = ($sKey!==null) ? "`".$sKey."`, " : "";
		$sFields = ($this->attribute("colstr")!==null) ? $this->attribute("colstr") : "`".$sTable."`.* ";
		$show = $this->db->query("
			SELECT 
				`__pecker__`,
				COUNT(`__pecker__`) AS '__duplicates__', 
				".$sKey." ".$sFields." 
			FROM `".$sTable."` 
			WHERE `__pecker__` IS NOT NULL 
			GROUP BY `__pecker__` 
				HAVING COUNT(*) > 1 
			ORDER BY `__duplicates__` DESC, `__pecker__` 
			LIMIT ".$sLimit
		);
		return $this->Output($show->getall());
	}

	// muestra todos los duplicados
	public function twinsAll() {
		list($sLimit) = $this->getarguments("limit", \func_get_args());
		$sTable = $this->ChkSource();
		$this->ChkHash();
		$sKey = ($this->argument("key")!==null) ? "`".$this->argument("key")."`, " : "";
		$sFields = ($this->attribute("colstr")!==null) ? $this->attribute("colstr") : "`".$sTable."`.* ";
		$show = $this->db->query("
			SELECT 
				".$sKey." ".$sFields." 
			FROM `".$sTable."` 
				WHERE `__pecker__` IS NOT NULL AND `__pecker__` IN (SELECT `__pecker__` FROM `".$sTable."` WHERE `__pecker__` IS NOT NULL GROUP BY `__pecker__` HAVING COUNT(*) > 1) 
			ORDER BY `__pecker__` 
			LIMIT ".$sLimit
		);
		return $this->Output($show->getall());
	}

	// elimina la columna __pecker__
	public function unhash() {
		list($sTable) = $this->getarguments("table", \func_get_args());
		$this->ChkSource();
		$this->ChkHash();
		$drop = $this->db->query("ALTER TABLE `".$sTable."` DROP COLUMN `__pecker__`,  DROP COLUMN `__pecked__`,  DROP COLUMN `__wano__`");
		return $this;
	}

	public function unify() {
		list($aUnify,$sWhere) = $this->getarguments("rules,where", \func_get_args());
		$sTable = $this->ChkSource();
		$this->ChkHash();
		$aAnalysis = $this->attribute("analysis");

		$aFields = $aRules = [];
		foreach($aUnify as $nCol => $sRule) {
			$aFields[] = $aAnalysis[$nCol]["field"];
			$aRules[$aAnalysis[$nCol]["field"]] = $sRule;
		}
		$sFields = "`".\implode("`,`", $aFields)."`";

		$sWhere = ($sWhere===null) ? "" : "(".$sWhere.") AND ";
		$unify = $this->db->query("
			SELECT `__pecked__`, ".$sFields." 
			FROM `".$sTable."` 
			WHERE ".$sWhere." `__pecked__` IN (
				SELECT `__pecked__` 
				FROM `".$sTable."` 
					WHERE `__pecked__` IS NOT NULL 
				GROUP BY `__pecked__`
					HAVING COUNT(*) > 1
			) ORDER BY 1
		");

		if($unify->rows()) {
			$sCurrent = null;
			$aUnify = [];
			while($aRow = $unify->get()) {
				if($sCurrent===null) { $sCurrent = $aRow["__pecked__"]; }
				if($sCurrent!=$aRow["__pecked__"]) {
					$aMixed = $this->Mixer($aUnify, $aFields, $aRules);
					$sCurrent = $aMixed["__pecked__"];
					unset($aMixed["id"], $aMixed["__pecked__"]);
					$aMixed = $this->db->escape($aMixed);
					$this->db->update($sTable, $aMixed, "`__pecked__`='".$sCurrent."'");
					$sCurrent = $aRow["__pecked__"];
					$aUnify = [];
				}
				$aUnify[] = $aRow;
			}

			$aMixed = $this->Mixer($aUnify, $aFields, $aRules);
			$sCurrent = $aMixed["__pecked__"];
			unset($aMixed["id"], $aMixed["__pecked__"]);
			$aMixed = $this->db->escape($aMixed);
			$this->db->update($sTable, $aMixed, "`__pecked__`='".$sCurrent."'");
		}

		return $this->Output([["unified"=>$unify->rows()]]);
	}

	// muestra/inserta los valores unicos de una columna
	public function uniques() {
		list($mCols,$bInsert) = $this->getarguments("cols,exec", \func_get_args());
		$sTable = $this->ChkSource();
		if($mCols===null) { self::errorMessage($this->object, 1005); }
		if(\is_int($mCols)) { $mCols = [$mCols]; }
		$aColumns = $this->GetCols($mCols);

		if(!$bInsert) {
			$aOutput = [];
			foreach($aColumns as $sField) {
				$uniques = $this->db->query("SELECT DISTINCT `".$sField."` AS 'unique' FROM `".$sTable."` WHERE TRIM(`".$sField."`) != '' ORDER BY 1");
				$aOutput = \array_merge($aOutput, $uniques->getall("#unique"));
			}
			return $this->Output($aOutput);
		} else {
			$aFeatures = $this->ChkFeatures();
			$sFeaturesTable = $aFeatures["table"];
			$sColName = $aFeatures["match"];
			$sSelect = ($this->IsOwlTable($sFeaturesTable)) ? " NULL, func.imya(), '1', NULL, " : "";
			$nLength = $this->db->query("SELECT `CHARACTER_MAXIMUM_LENGTH` FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA`='".$this->db->base."' AND `TABLE_NAME`='".$sFeaturesTable."' AND `COLUMN_NAME` = '".$sColName."'")->get("CHARACTER_MAXIMUM_LENGTH");

			$aNew = [];
			foreach($aColumns as $sField) {
				$new = $this->db->query("
					SELECT LEFT(`".$sField."`, ".$nLength.")  AS 'unique'  
					FROM `".$sTable."` 
					WHERE 
						TRIM(`".$sField."`) != '' AND 
						LEFT(`".$sField."`, ".$nLength.") NOT IN (SELECT `".$sColName."` FROM `".$sFeaturesTable."`) 
					GROUP BY LEFT(`".$sField."`, ".$nLength.") 
					ORDER BY 1
				");
				$aNew = \array_merge($aNew, $new->getall("#unique"));
			}
			
			if(\is_array($aNew) && \count($aNew)) {
				foreach($aNew as $aNewValue) {
					$this->db->query("INSERT INTO `".$sFeaturesTable."` () VALUES (".$sSelect." '".$this->db->escape($aNewValue["unique"])."');");
				}
				return $this->Output($aNew);
			}

			return $this->Output([["message"=>"empty result"]]);
		}

		return false;
	}

	private function BuildAnalysis($sTable) {
		$aColumns = [];
		$nRows = $this->db->query("SELECT COUNT(*) AS 'rows' FROM `".$sTable."`")->get("rows");
		$structure = $this->db->query("SELECT `COLUMN_NAME`, `DATA_TYPE`, `COLUMN_TYPE` FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA`='".$this->db->base."' AND `TABLE_NAME`='".$sTable."'");
		$aStructure = $structure->getall("#COLUMN_NAME"); 

		$aAnalyse = $this->db->query("SELECT * FROM `".$sTable."` PROCEDURE ANALYSE ()")->getall();
		$bAnalyseDataType = $this->argument("analyse_datatype");
		if($bAnalyseDataType && !$nRows) { self::errorMessage($this->object, 1009, $sTable); }

		$x = 0;
		$nLength = $this->argument("length");
		foreach($aStructure as $aStructureField) {
			$sField = $aStructureField["COLUMN_NAME"];

			$sFieldComplete = $this->db->base.".".$sTable.".".$sField;
			$aField = (\array_key_exists($sFieldComplete, $aAnalyse)) ? $aAnalyse[$sFieldComplete] : null;
			
			if($sField=="__pecker__" || $sField=="__pecked__" || $sField=="__wano__") { continue; }
			$aColumns[$x]["col"] = $x;
			$aColumns[$x]["field"] = $sField;
			$aColumns[$x]["type"] = $aStructureField["COLUMN_TYPE"];
			$aColumns[$x]["improve"] = "text";
			$aColumns[$x]["length"] = $aField["Max_length"];
			if($aField!==null) {
				$aColumns[$x]["min"] = \substr($aField["Min_value"], 0, $nLength);
				$aColumns[$x]["max"] = \substr($aField["Max_value"], 0, $nLength);
				$aColumns[$x]["empties"] = $aField["Empties_or_zeros"];
				$aColumns[$x]["nulls"] = $aField["Nulls"];
			} else {
				$aColumns[$x]["min"] = "";
				$aColumns[$x]["max"] = "";
				$aColumns[$x]["empties"] = 0;
				$aColumns[$x]["nulls"] = 0;
			}
			$aColumns[$x]["normalizable"] = 0;
			$aColumns[$x]["rows"] = $nRows;
	
			if($nRows) {
				if($aField["Max_length"]>255) {
					$aColumns[$x]["improve"] = "text";
				} else if($aField["Max_length"]==-1) {
					$aColumns[$x]["improve"] = "varchar";
				} else {
					$aColumns[$x]["improve"] = "varchar";
					if($bAnalyseDataType) {
						$aTypes = $this->db->query("
							SELECT 
								COUNT(`".$sField."` REGEXP '^-?[1-9][0-9]*$' OR NULL) AS 'int',
								COUNT(REPLACE(`".$sField."`, ',', '') REGEXP '^-?[0-9]+\\\.[0-9]+$' OR NULL) AS 'decimal', 
								COUNT(REPLACE(`".$sField."`, '/', '-') REGEXP '^[0-9]{2,4}-([0-9]{2}|[a-z]{3})(-[0-9]{2,4})?$' OR NULL) AS 'date', 
								COUNT(`".$sField."` REGEXP '^[0-9]{1,2}:[0-9]{1,2}(:[0-9]{1,2})?$' OR NULL) AS 'time' 
							FROM `".$sTable."`
						")->get();
						arsort($aTypes);

						$aType = [\key($aTypes), \current($aTypes)];
						if($aType[1]>0 && ($aType[1]*100/$nRows) > 85) {
							$aColumns[$x]["improve"] = $aType[0];
							$aColumns[$x]["length"] = $aField["Max_length"];
						}
				
						if($aColumns[$x]["improve"]=="varchar") {
							$aDistincts = $this->db->query("
								SELECT 
									COUNT(DISTINCT `".$sField."`) AS 'distincts', 
									COUNT(*) AS 'total' 
								FROM `".$sTable."` 
								WHERE `".$sField."` IS NOT NULL AND `".$sField."`!=''
							")->get();
				
							if($aDistincts["distincts"]==1 && $aDistincts["total"]<=$nRows) {
								if($aField["Max_length"]>5) {
									$aColumns[$x]["improve"] = "int";
									$aColumns[$x]["normalizable"] = (int)$aDistincts["distincts"];
								} else {
									$aColumns[$x]["improve"] = "boolean";
								}
							} else if($aDistincts["total"] > 0 && ($aDistincts["distincts"]*100/$aDistincts["total"]) < 10) {
								$aColumns[$x]["improve"] = "int";
								$aColumns[$x]["normalizable"] = (int)$aDistincts["distincts"];
							}
						} else if($aColumns[$x]["improve"]=="int") {
							$nValue = $aField["Max_value"];
							switch(true) {
								case ($nValue >= -128 && $nValue <= 127): $aColumns[$x]["improve"] = "tinyint"; break;
								case ($nValue >= -32768 && $nValue <= 32767): $aColumns[$x]["improve"] = "smallint"; break;
								case ($nValue >= -8388608 && $nValue <= 8388607): $aColumns[$x]["improve"] = "mediumint"; break;
								case ($nValue > 2147483647): $aColumns[$x]["improve"] = "bigint"; break;
							}
						}
					}
				}
			}
			$x++;
		}

		$this->UpdateAnalysis($sTable, $aColumns);
		return $aColumns;
	}

	private function ChkAnalysis($sTable=null) {
		if($sTable===null) { $sTable = $this->ChkSource(); }
		if(!isset($this->aSavedData["ANALYSIS"][$sTable])) {
			$this->BuildAnalysis($sTable);
		}
		return $this->aSavedData["ANALYSIS"][$sTable];
	}

	private function ChkFeatures() {
		if($this->attribute("features_schema")===null) { self::errorMessage($this->object, 1011); }
		return $this->attribute("features_schema");
	}

	private function ChkHash($sTable=null, $bAbortOnError=true) {
		if($sTable===null) { $sTable = $this->argument("table"); }
		$chk = $this->db->query("SELECT * FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA`='".$this->db->base."' AND `TABLE_NAME`='".$sTable."' AND `COLUMN_NAME`='__pecker__'");
		if(!$chk->rows()) {
			if($bAbortOnError) {
				self::errorMessage($this->object, 1007, $sTable);
			} else {
				return false;
			}
		}
		return true;
	}

	private function ChkKey() {
		$sTable = $this->argument("table");
		$sKey = $this->argument("key");
		if($sKey!==null) {
			$chk = $this->db->query("SELECT * FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA`='".$this->db->base."' AND `TABLE_NAME`='".$sTable."' AND `COLUMN_NAME`='".$sKey."'");
			if(!$chk->rows()) { self::errorMessage($this->object, 1008, $sTable.".".$sKey); }
		}
		return $sKey;
	}

	private function ChkSource($sTable=null) {
		if($sTable===null) { $sTable = $this->argument("table"); }
		$chk = $this->db->query("SELECT * FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA`='".$this->db->base."' AND `TABLE_NAME`='".$sTable."'");
		if(!$chk->rows()) { self::errorMessage($this->object, 1006, $sTable); }
		return $sTable;
	}

	private function ClearAnalysis() {
		$sTable = $this->argument("table");
		$this->attribute("analysis", null);
		unset($this->aSavedData["ANALYSIS"][$sTable]);
		$this->SaveData();
	}

	private function GetCols($mCols, $sTable=null) {
		if($sTable===null) { $sTable = $this->ChkSource(); }
		$aAnalysis = $this->ChkAnalysis($sTable);
		if(!\is_array($mCols)) {
			if(\strpos($mCols, "-")!==false) {
				$aRange = \explode("-", $mCols, 2);
				$mCols = \range((int)$aRange[0], (int)$aRange[1]);
			} else {
				return $aAnalysis[$mCols]["field"];
			}
		}
		$aColumns = [];
		foreach($mCols as $nCol) {
			$aColumns[$nCol] = $aAnalysis[$nCol]["field"];
		}
		return $aColumns;
	}

	private function IsOwlTable($sTable) {
		$schema = $this->db->query("SELECT * FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA`='".$this->db->base."' AND `TABLE_NAME`='".$sTable."' AND `COLUMN_NAME`='imya'");
		return ($schema->rows()) ? true : false;
	}

	private function IfTableExist($sTable) {
		$exist = $this->db->query("SELECT * FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA`='".$this->db->base."' AND `TABLE_NAME`='".$sTable."'");
		return ($exist->rows()) ? true : false;
	}

	private function LoadData() {
		return self::call()->dataFileLoad("/tmp/".$this->sSaveId);
	}

	private function Mixer($aUnify, $aFields, $aRules) {
		$aUnique = \array_shift($aUnify);
		$sSplitter = $this->argument("splitter");
		foreach($aFields as $sField) {
			if($aRules[$sField]===false) { continue; }
			
			$sSaveIdx = \trim(\strtolower($aUnique[$sField]));
			$nSaveIdx = \strlen($sSaveIdx);
			if($aRules[$sField]=="join") {
				$aToSave = [\md5($sSaveIdx) => $aUnique[$sField]];
			} else if($aRules[$sField]=="longer") {
				$aToSave = [$nSaveIdx => [\md5($sSaveIdx) => $aUnique[$sField]]];
			} else {
				$aToSave = [];
			}

			foreach($aUnify as $aRow) {
				switch($aRules[$sField]) {
					case "any":
						if($aUnique[$sField]==="") { $aUnique[$sField] = $aRow[$sField]; }
						break;

					case "noempty":
						if(empty($aUnique[$sField])) { $aUnique[$sField] = $aRow[$sField]; }
						break;
					
					case "join":
						$aToSave[\md5(\trim(\strtolower($aRow[$sField])))] = $aRow[$sField];
						break;
					
					case "longer":
					case "longerjoin":
						$sVal = \trim(\strtolower($aRow[$sField]));
						$nVal = \strlen($sVal);
						if(!isset($aToSave[$nVal])) { $aToSave[$nVal] = []; }
						$aToSave[$nVal][\md5($sVal)] = $aRow[$sField];
						break;
				}
			}

			if(\is_array($aToSave) && \count($aToSave)) {
				if($aRules[$sField]=="longer") {
					\krsort($aToSave);
					$aToSave = \current($aToSave);
					$aUnique[$sField] = \current($aToSave);
				} if($aRules[$sField]=="longerjoin") {
					krsort($aToSave);
					$aToSave = \current($aToSave);
					$aUnique[$sField] = \implode($sSplitter, $aToSave);
				} else if($aRules[$sField]=="join") {
					$aUnique[$sField] = \implode($sSplitter, $aToSave);
				}
			}
		}
		
		return $aUnique;
	}

	private function Output($mData) {
		$sOutputMode = $this->argument("output");
		if(\is_array($mData) && \count($mData)) {
			if($sOutputMode=="print") {
				echo self::call("shift")->convert($mData, "array-ttable");
			} else if($sOutputMode=="table") {
				return self::call("shift")->convert($mData, "array-ttable");
			} else {
				return $mData;
			}
		}
	}

	private function Sanitizer($sField, $aPolicy) {
		foreach($aPolicy as $sPolicy) {
			if(\strstr($sPolicy, ":")) {
				$aPolParts = \explode(":", $sPolicy, 2);
				$sPolicy = $aPolParts[0];
				$sPolicyArg = $aPolParts[1];
			}
			switch($sPolicy) {
				case "trim":
					if(isset($sPolicyArg)) {
						$sField = "TRIM(BOTH '".$sPolicyArg."' FROM ".$sField.")";
					} else {
						$sField = "TRIM(".$sField.")";
					}
					break;
				case "lcase": $sField = "LCASE(".$sField.")"; break;
				case "ucase": $sField = "UCASE(".$sField.")"; break;
				case "ucfirst": $sField = "func.ucfirst(".$sField.")"; break;
				case "ucwords": $sField = "func.ucwords(".$sField.")"; break;
				case "letters": $sField = "REGEXP_REPLACE(".$sField.", '[^A-Za-z]', '')"; break;
				case "digits": $sField = "REGEXP_REPLACE(".$sField.", '[^0-9]', '')"; break;
				case "numbers": $sField = "REGEXP_REPLACE(".$sField.", '[^0-9\\,\\.\\-]', '')"; break;
				case "email": $sField = "LCASE(REGEXP_REPLACE(".$sField.", '[^0-9a-zA-Z\\@\\_\\.\\-]', ''))"; break;
				case "words": $sField = "REGEXP_REPLACE(".$sField.", '[^0-9a-zA-Z]', '')"; break;
				case "nospaces": $sField = "REGEXP_REPLACE(".$sField.", '\s', '')"; break;
				case "consonants": $sField = "REGEXP_REPLACE(".$sField.", '([^B-Zb-z]|[eiouEIOU])', '')"; break;
				case "right": $sField = "RIGHT(".$sField.", ".$sPolicyArg.")"; break;
				case "left": $sField = "LEFT(".$sField.", ".$sPolicyArg.")"; break;
			}
		}

		return $sField;
	}

	private function SaveData() {
		return self::call()->dataFileSave("/tmp/".$this->sSaveId, $this->aSavedData);
	}

	protected function SecureName($sName) {
		$sName = \preg_replace("/[^A-Za-z0-9\_\-]/is", "", $sName);
		return (empty($sName)) ? null : $sName;
	}

	protected function SetBridge($sBridge) {
		$aBridge = \explode(".", $sBridge);
		$sSelect = " `b`.`".$this->SecureName($aBridge[1])."`, ";
		$sFrom = " LEFT JOIN `".$this->SecureName($aBridge[0])."` b  ON `b`.`__pecked__` = `t`.`__pecked__` ";
		return [$sSelect, $sFrom];
	}

	protected function SetCols($aCols) {
		if($aCols!==null) {
			$aCols = $this->GetCols($aCols);
			if(!\is_array($aCols)) { $aCols = [$aCols]; }
			$this->attribute("colstr", "`".\implode("`,`", $aCols)."`");
			$sTable = $this->argument("table");
			if($sTable!==null) {
				$this->aSavedData["COLS"][$sTable] = $aCols;
				$this->SaveData();
			}
		}
		return $aCols;
	}

	protected function SetDataFile($sFileName) {
		$this->sSaveId = $sFileName.".pecker";
		$this->aSavedData = $this->LoadData();
		return $sFileName;
	}

	protected function SetDb($db) {
		die($db);
		if($db!==null) {
			if(\is_string($db)) { $db = self::call($db); }
			$this->db = $db;
			if(\method_exists($db, "connect")) {
				if(!$this->db->connect()) { self::errorMessage($this->object, 1001); }
			}
		}
		return $db;
	}

	protected function SetFeatures($aFeatures) {
		if($aFeatures!==null) {
			if(!\is_array($aFeatures) || count($aFeatures)<3) { self::errorMessage($this->object, 1011); }
			$sFeTable = $this->SecureName($aFeatures[0]);
			$sFeId = $this->SecureName($aFeatures[1]);
			$sFeMatch = $this->SecureName($aFeatures[2]);

			$schema = $this->db->query("SELECT * FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA`='".$this->db->base."' AND `TABLE_NAME`='".$sFeTable."'");
			if($schema->rows()) {
				$aGetSchema = $schema->getall("#COLUMN_NAME");
				if(!isset($aGetSchema[$sFeId]) || !isset($aGetSchema[$sFeMatch])) { self::errorMessage($this->object, 1012); }
				$aSchema = [
					"table" => $sFeTable,
					"id" => $sFeId,
					"match" => $sFeMatch,
					"imya" => (isset($aGetSchema["imya"])) ? true : false
				];
			} else {
				self::errorMessage($this->object, 1012);
			}

			$this->attribute("features_schema", $aSchema);
		}
		return $aFeatures;
	}

	protected function SetGrouper($aGrouper) {
		if($aGrouper!==null) {
			$aGrouper = $this->GetCols($aGrouper);
			if(!\is_array($aGrouper)) { $aGrouper = [$aGrouper]; }
			$this->attribute("grouperstr", "`".\implode("`,`", $aGrouper)."`");
			$sTable = $this->argument("table");
			if($sTable!==null) {
				$this->aSavedData["GROUPER"][$sTable] = $aGrouper;
				$this->SaveData();
			}
		}
		return $aGrouper;
	}

	protected function SetTable($sTableName) {
		if($sTableName!==null) {
			$sTableName = $this->SecureName($sTableName);
			if(isset($this->aSavedData["ANALYSIS"], $this->aSavedData["ANALYSIS"][$sTableName])) {
				$this->attribute("analysis", $this->aSavedData["ANALYSIS"][$sTableName]);
				if(isset($this->aSavedData["GROUPER"][$sTableName])) { $this->attribute("grouperstr", "`".\implode("`,`", $this->aSavedData["GROUPER"][$sTableName])."`"); }
				if(isset($this->aSavedData["COLS"][$sTableName])) { $this->attribute("colstr", "`".\implode("`,`", $this->aSavedData["COLS"][$sTableName])."`"); }
			}
		}
		return $sTableName;
	}

	private function UpdateAnalysis($sTable, $aAnalysis) {
		$this->attribute("analysis", $aAnalysis);
		$this->aSavedData["ANALYSIS"][$sTable] = $aAnalysis;
		$this->SaveData();
	}
}

?>