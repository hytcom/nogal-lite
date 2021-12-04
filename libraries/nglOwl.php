<?php
/*
# Nogal
*the most simple PHP Framework* by hytcom.net
GitHub @hytcom
___
  
# owlm
## nglOwl *extends* nglBranch [2018-11-02]
Owl es el ORM de NOGAL y permite ejecutar operaciones sobre distintos objetos de base de datos.
Entre las funciones del objecto se encuentran:
- consulta de listados de registros directos y con referencias cruzadas
- consulta de un registro en particular
- administración de objetos depentientes, como por ejemplo los datos una empresa y sus empleados
- uso de foreignkeys a nivel objeto, sin importar el driver de base de datos
- validación de datos por medio del objeto https://github.com/hytcom/wiki/blob/master/nogal/docs/validate.md
- permite añadir, modificar, suspender y eliminar (eliminado lógico) registros
- eliminación de registros en cascada

https://github.com/hytcom/wiki/blob/master/nogal/docs/own.md
https://github.com/hytcom/wiki/blob/master/nogal/docs/owluso.md

*/
namespace nogal;

class nglOwl extends nglBranch {

	private $db;
	private $x;
	private $sObject;
	private $vObjects;
	private $sChildTable;
	private $sChildTableAlias;
	private $bChildMode;
	private $bInternalCall;
	private $aCascade;
	private $aTmpChildren;
	private $aRelationships;
	private $nRelationshipsLevel;
	private $nRelationshipsLevelLimit;
	private $bViewFields;
	private $query;

	final protected function __declareArguments__() {
		$vArguments							= [];
		$vArguments["alvin"]				= ['self::call()->istrue($mValue)', true];
		$vArguments["cascade"]				= ['self::call()->istrue($mValue)', false];
		$vArguments["child"]				= ['strtolower($mValue)', null];
		$vArguments["data"]					= ['(array)$mValue', null];
		$vArguments["db"]					= ['$mValue', null];
		$vArguments["debug"]				= ['self::call()->istrue($mValue)', false];
		$vArguments["duplicate_children"]	= ['self::call()->istrue($mValue)', false];
		$vArguments["escape"]				= ['self::call()->istrue($mValue)', true];
		$vArguments["filter"]				= ['$mValue', null];
		$vArguments["id"]					= ['$this->GetID($mValue)', null];
		$vArguments["inherit"]				= ['self::call()->istrue($mValue)', false];
		$vArguments["insert_mode"]			= ['$mValue', "INSERT"];
		$vArguments["jsql"]					= ['$mValue', null];
		$vArguments["join_level"]			= ['(int)$mValue', 2];
		$vArguments["owlog"]				= ['self::call()->istrue($mValue)', true];
		$vArguments["owlog_changelog"]		= ['self::call()->istrue($mValue)', false];
		$vArguments["object"]				= ['strtolower($mValue)', null];
		$vArguments["subobject"]			= ['strtolower($mValue)', null];
		$vArguments["use_history"]			= ['self::call()->istrue($mValue)', false];
		$vArguments["view_alias"]			= ['strtolower($mValue)', "auto"];
		$vArguments["view_children"]		= ['self::call()->istrue($mValue)', false];
		$vArguments["view_columns"]			= ['$mValue', null];
		$vArguments["view_deleted"]			= ['self::call()->istrue($mValue)', false];
		$vArguments["view_eol"]				= ['$mValue', ""];
		$vArguments["view_joins"]			= ['self::call()->istrue($mValue)', false];
		$vArguments["view_mode"]			= ['strtolower($mValue)', "sql"];
		$vArguments["view_parent"]			= ['self::call()->istrue($mValue)', false];
		
		return $vArguments;
	}

	final protected function __declareAttributes__() {
		$vAttributes						= [];
		$vAttributes["history"]				= null;
		$vAttributes["current"]				= null;
		$vAttributes["log"]					= null;
		$vAttributes["object_name"]			= null;
		$vAttributes["query"]				= null;
		$vAttributes["result"]				= null;
		$vAttributes["validate"]			= null;
		$vAttributes["last_id"]				= null;
		$vAttributes["last_imya"]			= null;
		return $vAttributes;
	}

	final protected function __declareVariables__() {
		$this->bChildMode = false;
		$this->bInternalCall = false;
		$this->aTmpChildren = [];
		
		// if(NGL_ALVIN) { $this->args(array("alvin"=>true)); }
	}

	final public function __init__() {
		$this->bViewFields = false;
	}
	
	public function child() {
		list($sChild) = $this->getarguments("child", \func_get_args());
		if(!$this->sObject) { self::errorMessage($this->object, 1001); }
		if(isset($this->vObjects[$this->sObject]["children"])) {
			if(!isset($this->vObjects[$this->sObject]["children"][$sChild])) {
				$sChild = \array_search($sChild, $this->vObjects[$this->sObject]["children"]);
			}
			if($sChild===false || !isset($this->vObjects[$this->sObject]["children"][$sChild])) {
				self::errorMessage($this->object, 1004);
			} else {
				$this->sChildTable = $this->vObjects[$this->sObject]["children"][$sChild];
				$this->sChildTableAlias = $sChild;
				$this->bChildMode = true;
			}
		} else {
			self::errorMessage($this->object, 1004);
		}

		return $this;
	}

	public function children() {
		if(!$this->sObject) { self::errorMessage($this->object, 1001); }
		$aChildren = [];
		if(isset($this->vObjects[$this->sObject]["children"])) {
			$aChildren = $this->vObjects[$this->sObject]["children"];
			if(\is_array($aChildren) && \count($aChildren)) {
				$aChildren = \array_values($aChildren);
			}
		}
		
		return $aChildren;
	}

	public function close() {
		return $this->db->close();
	}
	
	public function columns() {
		if(!$this->sObject) { return false; }
		return $this->vObjects[$this->sObject]["columns"];
	}
	
	public function connect($driver) {
		return $this->load($driver);
	}

	public function dbStructure() {
		$aStructures = [];
		$aStructures["mysql"] = <<<SQL
-- MySQL / MariaDB --------------------------------------------------------------
-- owl index --
DROP TABLE IF EXISTS `__ngl_owl_index__`;
CREATE TABLE `__ngl_owl_index__` (
	`id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
	`imya` CHAR(32) NOT NULL DEFAULT '' COMMENT 'imya del registro en la tabla de origen',
	`role` char(32) DEFAULT NULL COMMENT 'role a partir del cual se obtiene acceso',
	PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Indice de los registros de las tablas que hagan uso de roles';
CREATE INDEX `imya_idx` ON `__ngl_owl_index__` (`imya`);
CREATE INDEX `role_idx` ON `__ngl_owl_index__` (`role`);

-- owl log --
DROP TABLE IF EXISTS `__ngl_owl_log__`;
CREATE TABLE `__ngl_owl_log__` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`imya` CHAR(32) NOT NULL DEFAULT '' COMMENT 'imya del registro en la tabla de origen',
	`user` SMALLINT UNSIGNED DEFAULT NULL COMMENT 'id del usuario que ejecutó la acción',
	`action` ENUM('insert','delete','suspend','toggle','update','unsuspend') NOT NULL DEFAULT 'insert' COMMENT 'tipo de acción',
	`date` DATETIME NOT NULL COMMENT 'fecha y hora de la ejecución',
	`ip` CHAR(45) NULL DEFAULT NULL COMMENT 'dirección de IP del usuario',
	`changelog` MEDIUMTEXT NULL DEFAULT NULL COMMENT 'cuando el argumento owlog_changelog del objeto OWL sea true, se almacenará un JSON con la versión anterior de los datos',
	PRIMARY KEY (`id`) 
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de operaciones realizadas mediante el objeto OWL';
CREATE INDEX `imya_idx` ON `__ngl_owl_log__` (`imya`);
CREATE INDEX `user_idx` ON `__ngl_owl_log__` (`user`);

-- owl structures --
DROP TABLE IF EXISTS `__ngl_owl_structure__`;
CREATE TABLE `__ngl_owl_structure__` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` CHAR(128) NOT NULL COMMENT 'nombre del objeto',
	`code` CHAR(12) NOT NULL COMMENT 'código del objeto. Que luego formará parte de el IMYA de cada registro del mismo',
	`roles` ENUM('0', '1', '2', '3') NOT NULL DEFAULT '0' COMMENT 'determina si el objeto esta sujeto a roles: 0=no, 1=si',
	`columns` TEXT NOT NULL COMMENT 'JSON con los nombres de las columnas del objeto',
	`foreignkey` TEXT NULL COMMENT 'relaciones externas',
	`relationship` TEXT NULL COMMENT 'relaciones con otros objetos en formato JSON',
	`validate_insert` TEXT NULL COMMENT 'reglas del validación para los datos para el objeto VALIDATE al momento del INSERT',
	`validate_update` TEXT NULL COMMENT 'reglas del validación para los datos para el objeto VALIDATE al momento del UPDATE',
	PRIMARY KEY (`id`) 
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Estructuras de los objetos (tablas) de el entorno OWL';
CREATE INDEX `name_idx` ON `__ngl_owl_structure__` (`name`);
CREATE UNIQUE INDEX `code_idx` ON `__ngl_owl_structure__` (`code`);
SQL;

		if($this->db && isset($aStructures[$this->db->object])) {
			return $aStructures[$this->db->object];
		}

		return "";
	}

	public function dbMysqlStructure() {

	}

	public function delete() {
		if(!$this->bInternalCall) { $this->Logger(); }
		$mDelete = $this->UpdateData(\func_get_args(), 0);
		return ($mDelete===0) ? false : $mDelete;
	}

	public function describe() {
		list($sSubObject) = $this->getarguments("subobject", \func_get_args());
		if(!$this->sObject) { return false; }
		$aReturn = $this->vObjects[$this->sObject];
		$aFields = [];

		if(!empty($sSubObject)) {
			if($sSubObject[0]=="*") {
				$sSubObject = \substr($sSubObject, 1);
				$aReturnTables = [];
				foreach($aReturn["tables"] as $aTable) {
					if(\strpos($aTable["alias"], $sSubObject)!==false) {
						$aReturnTables[$aTable["alias"]] = $aTable;
					}
				}
				return $aReturnTables;
			} else {
				return $aReturn["tables"][$sSubObject];
			}
		}

		foreach($aReturn["tables"] as $aTable) {
			if(isset($aTable["columns"])) {
				foreach($aTable["columns"] as $sColumn) {
					$aFields[] = $aTable["name"]." => ".$sColumn;
				}
			}
		}
		$aReturn["fields"] = $aFields;
		return $aReturn;
	}

	public function duplicate() {
		list($mID,$bChildren) = $this->getarguments("id,duplicate_children", \func_get_args());
		
		$aNewIDs = [];
		if(!$this->bChildMode) {
			$data = $this->get($mID, false, false, false);
			$aData = $data->get();
			if($aData!==null) {
				$nOldID = $aData["id"];
				$nNewID = $this->insert($aData);

				$aNewIDs[$this->sObject] = [$nNewID];
				if($bChildren) {
					$aChildren = $this->children();
					foreach($aChildren as $sChild) {
						$this->attribute("current", $nOldID);
						$children = $this->child($sChild)->getall(null, false, false, false);
						if($children->rows()) {
							$aChildData = $children->getall();
							$this->attribute("current", $nNewID);
							$aNewIDs[$sChild] = [];
							foreach($aChildData as $aChild) {
								$nNewChildID = $this->child($sChild)->insert($aChild);
								$aNewIDs[$sChild][] = $nNewChildID;
							}
						}
					}
				}
			}
		} else {
			if(!$this->attribute("current")) { self::errorMessage($this->object, 1002); }
			$sWhere = ($mID) ? '{"where":[["id","eq","('.$mID.')]","OR",["imya","eq","('.$mID.')"]]}' : null;
			$children = $this->child($this->sChildTable)->getall($sWhere, false, false, false);
			if($children->rows()) {
				$aNewIDs[$this->sChildTable] = [];
				$aChildData = $children->getall();
				foreach($aChildData as $aChild) {
					$nNewChildID = $this->child($this->sChildTable)->insert($aChild);
					$aNewIDs[$this->sChildTable][] = $nNewChildID;
				}
			}
		}

		return $aNewIDs;
	}

	public function get() {
		list($mID,$sAliasMode,$mJoins,$mChildren,$sColumns,$bParent,$bDeleted) = $this->getarguments("id,view_alias,view_joins,view_children,view_columns,view_parent,view_deleted", \func_get_args());
		if(!$this->bChildMode) {
			if(!$this->sObject) { self::errorMessage($this->object, 1001); }
			$nID = ($mID) ? $this->GetID($mID, $this->sObject) : $this->attribute("current");
			$sJSQL = '{"where":[["'.$this->sObject.'.id","eq","('.$nID.')"]]}';
			$sView = $this->view("jsql",$sAliasMode,$mJoins,$mChildren,$sColumns,$bParent,$bDeleted);
		} else {
			if(!$this->attribute("current")) { self::errorMessage($this->object, 1002); }
			$sTable = $this->sChildTable;
			$nID = $this->GetID($mID, $sTable);
			$nPID = $this->attribute("current");
			$sJSQL = '{"where":[
				["'.$this->sChildTableAlias.'.id","eq","('.$nID.')"],
				"AND",
				["'.$this->sChildTableAlias.'.pid","eq","('.$nPID.')"]
			]}';
			$sView = $this->viewchildren("jsql",$sAliasMode,$mJoins,$bDeleted);
		}
		
		if($sView==false) { return self::errorMessage($this->object, 1001); }
		
		if($this->bChildMode) { $this->bChildMode = false; }
		$sJSQL = $this->JsonAppener($sJSQL, $sView);

		$this->attribute("result", $this->query($sJSQL));
		return $this->attribute("result");
	}

	public function getall() {
		list($sFilter,$sAliasMode,$mJoins,$mChildren,$sColumns,$bParent,$bDeleted) = $this->getarguments("filter,view_alias,view_joins,view_children,view_columns,view_parent,view_deleted", \func_get_args());

		// filtro
		$sFilter = ($sFilter!==null) ? \ltrim($sFilter) : "";
		
		if(!$this->bChildMode) {
			$sJSQL = $this->view("jsql",$sAliasMode,$mJoins,$mChildren,$sColumns,$bParent,$bDeleted);
			if($sJSQL==false) { return self::errorMessage($this->object, 1001); }
		} else {
			$sJSQL = $this->viewchildren("jsql",$sAliasMode,$mJoins,$bDeleted);
			if($sJSQL==false) { return self::errorMessage($this->object, 1001); }

			if($this->attribute("current")) {
				if(!empty($sFilter)) { 
					$sChildrenWhere = '{"where": [["'.$this->sChildTableAlias.'.pid", "eq", "("'.$this->attribute("current").'")"], "AND"]}';
				} else {
					$sChildrenWhere = '{"where": [["'.$this->sChildTableAlias.'.pid", "eq", "("'.$this->attribute("current").'")"]]}';
				}

				$sJSQL = $this->JsonAppener($sJSQL, $sChildrenWhere);
			}
		}

		// aplicamos filtros
		if(!empty($sFilter)) {
			$sJSQL = $this->JsonAppener($sJSQL, $sFilter);
		}

		if($this->bChildMode) { $this->bChildMode = false; }

		$this->attribute("result", $this->query($sJSQL));
		return $this->attribute("result");
	}

	public function getByImya() {
		list($sImya) = $this->getarguments("id", \func_get_args());

		$sSQL = $this->JsqlParser('{
			"columns":["name"],
			"tables":["__ngl_owl_structure__"],
			"where":[["code","eq","('.\substr($sImya, 0, 12).')"]]
		}');
		$table = $this->db->query($sSQL);

		if($table->rows()) {
			$sTable = $table->get("name");
			$sSQL = $this->JsqlParser('{
				"tables":["'.$sTable.'"],
				"where":[["imya","eq","('.$sImya.')"]]
			}');
			$data = $this->db->query($sSQL);
			if($data->rows()) {
				return [$sTable, $data->get()];
			}
		}
		return false;
	}

	public function imyaOf() {
		list($sImya) = $this->getarguments("id", \func_get_args());

		$sSQL = $this->JsqlParser('{
			"columns":["name"],
			"tables":["__ngl_owl_structure__"],
			"where":[["code","eq","('.\substr($sImya, 0, 12).')"]]
		}');
		$table = $this->db->query($sSQL);

		if($table->rows()) { return $table->get("name"); }
		return false;
	}

	public function insert() {
		if(!$this->bInternalCall) { $this->Logger(); }

		$aArguments = \func_get_args();

		// actualizacion multiple con array multiple
		if(\is_array($aArguments) && \count($aArguments)==1 && \is_array(\current($aArguments)) && \is_array(\current(\current($aArguments)))) {
			$aArguments = \current($aArguments);
		}

		// insercion multiple
		if(\is_array($aArguments) && \count($aArguments)>1) {
			$bChildMode = $this->bChildMode;
			$aIDs = [];
			foreach($aArguments as $aInput) {
				// evita la desactivacion del modo child
				$this->bInternalCall = true;
				$this->bChildMode = $bChildMode;
				$aIDs[] = $this->insert($aInput);
				$this->bInternalCall = false;
			}
			
			return $aIDs;
		}

		// insersion simple
		list($vData,$sMode) = $this->getarguments("data,insert_mode", $aArguments);
		$vData = (array)$vData;

		if(!$this->bChildMode) {
			if(!$this->sObject) { self::errorMessage($this->object, 1001); }
			$sTable = $this->sObject;
		} else {
			if(!$this->attribute("current")) { self::errorMessage($this->object, 1002); }
			$sTable = $this->sChildTable;
			$vData["pid"] = $this->attribute("current");
		}

		unset($vData["id"]);
		$vData["imya"] = $this->Imya($sTable);
		$vData["state"] = 1;

		// validacion
		if(isset($this->vObjects[$sTable]["validate_insert"])) {
			$sRules = $this->vObjects[$sTable]["validate_insert"];
			if(!empty($sRules)) {
				$vData = $this->Validate($vData, $sRules);
				if($vData===null) { self::errorMessage($this->object, 1003); return false; }
			}
		}

		// insercion
		if($this->argument("escape")) { $vData = $this->db->escape($vData); }
		$insert = $this->db->insert($sTable, $vData, $sMode);
		$nRowID = null;
		if($insert) {
			$nRowID = $insert->lastid();
			$this->attribute("last_id", $nRowID);
			$this->attribute("last_imya", $vData["imya"]);
			$nRows = $insert->rows();
			
			if($nRows) {
				// log
				$this->OwLog($vData["imya"], "insert");
				
				// roles
				if($bAlvin = $this->AlvinInit()) {
					$this->db->insert("__ngl_owl_index__", [
						"imya"	=> $vData["imya"],
						"role"	=> self::call("alvin")->role()
					], "INSERT", false, true);
				}
			}
			
			if(!$this->bChildMode) {
				$this->attribute("current", $nRowID);
			} else {
				$this->bChildMode = false;
			}
		
			$insert->destroy();
			$insert = null;
		}
		unset($insert);

		return ($nRowID) ? $nRowID : false;
	}

	public function load() {
		list($driver) = $this->getarguments("db", \func_get_args());
		$this->db = $driver;
		if(\method_exists($this->db, "connect")) { $this->db->connect(); }
		return $this;
	}

	public function query() {
		list($sJSQL) = $this->getarguments("jsql", \func_get_args());
		$sSQL = $this->AlvinSQL($sJSQL);
		$this->attribute("query", $sSQL);
		if($this->argument("debug")) { echo self::call()->dump($sSQL); }
		$this->query = $this->db->query($sSQL);
		return $this->query;
	}
	
	public function relationship() {
		if(!$this->sObject) { self::errorMessage($this->object, 1001); }
		list($bJoins,$bChildren,$bParent,$nJoinLevel) = $this->getarguments("view_joins,view_children,view_parent,join_level", \func_get_args());

		$aReturn = [];
		if(!$this->sObject) { self::errorMessage($this->object, 1001); }
		
		$sErrors = "";
		$vObject = $this->vObjects[$this->sObject];
		if(!isset($vObject["tables"])) { return false; }
		$vTables = $vObject["tables"];

		$nLoopingOut = 0;
		$aUsed = $aUnUsed = [];
		while(\count($vTables)) {
			if($nLoopingOut++>200) { echo "Error looping in relationship method\n";
				// print_r($vTables);
				// print_r($aUsed);
				// print_r($aUnUsed);
				exit();
			}

			if(isset($sJump)) {
				$sTable = $sJump;
				unset($sJump);
				if(!isset($vTables[$sTable]) && !isset($aUsed[$sTable])) { continue; }
				$vTable = $vTables[$sTable];
			} else {
				$sTable = \key($vTables);
				$vTable = \current($vTables);
				\next($vTables);
			}

			if($vTable["level"]>$nJoinLevel) {
				$aUnUsed[$sTable] = true;
				unset($vTables[$sTable]);
				continue;
			}

			// FROM (va antes por el continue en los joins)
			if($vTable["type"]=="main" || !count($aReturn)) {
				$aUsed[$this->sObject] = true;
				$aReturn[] = "MAIN TABLE `".$this->sObject."`";
			} else if($vTable["type"]=="children" && $bChildren) {
				if(isset($aUnUsed[$vTable["join"]])) {
					$aUnUsed[$sTable] = true;
					unset($vTables[$sTable]);
					continue;
				}
				if(!isset($aUsed[$vTable["join"]])) {
					$sJump = $vTable["join"];
					continue;
				}

				$aUsed[$sTable] = true;
				$aReturn[] = \str_repeat("--", $vTable["level"])." CHILD `".$vTable["name"]."` AS '".$sTable."' USING `".$sTable."_pid`";
			} else if($vTable["type"]=="parent" && $bParent) {
				$aUsed[$sTable] = true;
				$aReturn[] = \str_repeat("--", $vTable["level"])." PARENT OF `".$vTable["name"]."` AS '".$sTable."' USING `id` = `".$vTable["join"]."`.`".$vTable["using"]."`";
			} else if(!empty($vTable["join"]) && $vTable["type"]=="join" && $bJoins) {
				if(isset($aUnUsed[$vTable["join"]])) {
					$aUnUsed[$sTable] = true;
					unset($vTables[$sTable]);
					continue;
				}
				if(!isset($aUsed[$vTable["join"]]) && $sTable!=$vTable["join"]) {
					$sJump = $vTable["join"];
					continue;
				}

				$aUsed[$sTable] = true;
				$aReturn[] = \str_repeat("--", $vTable["level"])." JOIN `".$vTable["name"]."` AS '".$sTable."' USING `".$vTable["join"]."_".$vTable["using"]."`";
			} else {
				$aUnUsed[$sTable] = true;
				unset($vTables[$sTable]);
			}

			if(isset($aUsed[$sTable])) { unset($vTables[$sTable]); }
		}
		
		if(!empty($sErrors)) {
			$this->Logger("warning", $sErrors);
		}

		return \implode("\n", $aReturn);
	}

	public function select() {
		list($sObjectName) = $this->getarguments("object", \func_get_args());

		$sObjectName = \preg_replace("/[^a-zA-Z0-9_\-]/s", "", $sObjectName);
		$this->args(["id"=>null]);
		$this->attribute("current", null);

		if(isset($this->vObjects[$sObjectName])) {
			$this->attribute("object_name", $sObjectName);
			$this->sObject = $sObjectName;
			return $this;
		}

		$sSQL = $this->JsqlParser('{
			"columns":["name","columns","foreignkey","relationship","validate_insert","validate_update"],
			"tables":["__ngl_owl_structure__"],
			"where":[["name","eq","('.$sObjectName.')"]]
		}');

		$this->attribute("query", $sSQL);
		$table = $this->db->query($sSQL);

		if(!$table || !$table->rows()) {
			self::errorMessage($this->object, 1001);
			return null;
		}

		$aObject					= $table->get();
		$vObject					= [];
		$vObject["name"]			= $aObject["name"];
		$vObject["columns"]			= ($aObject["columns"]) ? self::call("jsql")->decode($aObject["columns"]) : [];
		$vObject["foreignkey"]		= ($aObject["foreignkey"]) ? self::call("jsql")->decode($aObject["foreignkey"]) : [];
		$vObject["relationship"]	= ($aObject["relationship"]) ? self::call("jsql")->decode($aObject["relationship"]) : [];
		$vObject["validate_insert"] = $aObject["validate_insert"];
		$vObject["validate_update"] = $aObject["validate_update"];
		$table->destroy();
		$table = null;

		// tabla 
		$vMain = [
			"name"		=>	$vObject["name"], 
			"alias"		=>	$vObject["name"], 
			"type"		=>	"main", 
			"parent"	=>	((isset($vObject["relationship"]["parent"])) ? "__parent" : ""), 
			"level"		=>	0, 
			"columns"	=>	$vObject["columns"]
		];
		$aTables = array($vObject["name"] => $vMain);

		$this->aRelationships = [];
		if(\is_array($vObject["relationship"]) && \count($vObject["relationship"])) {
			// parent
			// if(isset($vObject["relationship"]["parent"]) && !empty($vObject["relationship"]["parent"])) {
			if(!empty($vObject["relationship"]["parent"])) {
				$sSQL = $this->JsqlParser('{
					"columns":["columns","relationship"],
					"tables":["__ngl_owl_structure__"],
					"where":[["name","eq","('.$vObject["relationship"]["parent"].')"]]
				}');
		
				$this->attribute("query", $sSQL);
				$parent = $this->db->query($sSQL);
				if($parent->rows()) {
					$aTable						= [];
					$aTable["name"] 			= $vObject["relationship"]["parent"];
					$aTable["level"] 			= 1;
					$aTable["alias"] 			= "__parent";
					$aTable["type"]				= "parent";
					$aTable["join"]				= $vObject["name"];
					$aTable["using"]			= "pid";
					$aTable["columns"]			= self::call("jsql")->decode($parent->get("columns"));
					$aTables[$aTable["alias"]]	= $aTable;

					$aParentJoins = self::call("jsql")->decode($parent->reset()->get("relationship"));
					if(isset($aParentJoins["joins"]) && \count($aParentJoins["joins"])) {
						$aParentJoins = $aParentJoins["joins"];
						foreach($aParentJoins as $aPJoin) {
							$sSQL = $this->JsqlParser('{
								"columns":["columns"],
								"tables":["__ngl_owl_structure__"],
								"where":[["name","eq","('.$aPJoin["name"].')"]]
							}');
					
							$this->attribute("query", $sSQL);
							$parentjoin = $this->db->query($sSQL);

							$aParentJoin = [];
							$aTable["name"] 			= $aPJoin["name"];
							$aTable["level"] 			= 2;
							$aTable["alias"] 			= "__parent_".$aPJoin["name"];
							$aTable["type"]				= "join";
							$aTable["join"]				= "__parent";
							$aTable["using"]			= $aPJoin["using"];
							$aTable["columns"]			= self::call("jsql")->decode($parentjoin->get("columns"));
							$aTables[$aTable["alias"]]	= $aTable;
						}
					}
				}
			}

			// children
			$this->aTmpChildren = [];
			$this->nRelationshipsLevel = 0;
			$this->GetRelationship($aTables, $vObject, $sObjectName, ["**CONTROL**"=>[], $sObjectName=>true]);
			$vObject["children"] = $this->aTmpChildren;
		}

		$vObject["tables"] = self::call()->arrayMultiSort($aTables, [["field"=>"level", "type"=>2]]);

		// recarga de hijos con dependencias
		if(isset($vObject["relationship"]) && isset($vObject["relationship"]["children"])) {
			$vObject["relationship"]["children"] = $this->aTmpChildren;
		}

		$this->attribute("object_name", $sObjectName);
		$this->sObject = $sObjectName;
		$this->vObjects[$sObjectName] = $vObject;

		// print_r($vObject["tables"]);
		return $this;
	}

	public function showtables() {
		$aTables = [];
		$sSQL = $this->JsqlParser('{"columns":["name","columns","foreignkey","relationship","validate_insert","validate_update"], "tables":["__ngl_owl_structure__"], "order":["name"]}');
		$this->attribute("query", $sSQL);
		$tables = $this->db->query($sSQL);

		if($tables->rows()) {
			$aTables = $tables->getall("#name");
			foreach($aTables as $nKey => $aTable) {
				foreach($aTable as $sField => $mValue) {
					if($sField=="columns") {
						$aTables[$nKey][$sField] = self::call("jsql")->decode($mValue);
					} else if($sField=="foreignkey" || $sField=="relationship" || $sField=="validate_insert" || $sField=="validate_update") {
						$aTables[$nKey][$sField] = (empty($mValue)) ? "NO" : "YES";
					}
				}
			}
		}

		return $aTables;
	}

	public function suspend() {
		if(!$this->bInternalCall) { $this->Logger(); }
		$mUpdate = $this->UpdateData(\func_get_args(), 2);
		return ($mUpdate===0) ? false : $mUpdate;
	}

	public function toggle() {
		if(!$this->bInternalCall) { $this->Logger(); }
		$mUpdate = $this->UpdateData(\func_get_args(), 4);
		return ($mUpdate===0) ? false : $mUpdate;
	}

	public function unsuspend() {
		if(!$this->bInternalCall) { $this->Logger(); }
		$mUpdate = $this->UpdateData(\func_get_args(), 3);
		return ($mUpdate===0) ? false : $mUpdate;
	}
	
	public function update() {
		if(!$this->bInternalCall) { $this->Logger(); }
		$mUpdate = $this->UpdateData(\func_get_args());
		return ($mUpdate===false) ? false : $mUpdate;
	}
	
	public function upsert() {
		if(!$this->bInternalCall) { $this->Logger(); }

		$aArguments = \func_get_args();
		if(\is_array($aArguments) && \count($aArguments)==1 && \is_array(\current($aArguments)) && \is_array(\current(\current($aArguments)))) {
			$aArguments = \current($aArguments);
		}

		$aToInsert = $aToUpdate = [];
		foreach($aArguments as $aRow) {
			// if((isset($aRow["id"]) && !empty($aRow["id"])) || (isset($aRow["imya"]) && !empty($aRow["imya"]))) {
			if(!empty($aRow["id"]) || !empty($aRow["imya"])) {
				$aToUpdate[] = $aRow;
			} else {
				$aToInsert[] = $aRow;
			}
		}

		$mInsert = (\count($aToInsert)) ? $this->insert($aToInsert) : null;
		$mUpdate = (\count($aToUpdate)) ? $this->UpdateData($aToUpdate) : null;
		return ["insert"=>$mInsert, "update"=>$mUpdate];
	}

	public function view() {
		if(!$this->sObject) { self::errorMessage($this->object, 1001); }
		list($sOutputMode,$sAliasMode,$mJoins,$mChildren,$mColumns,$bParent,$bDeleted) = $this->getarguments("view_mode,view_alias,view_joins,view_children,view_columns,view_parent,view_deleted", \func_get_args());

		$aSelect = $aFrom = [];
		$sErrors = "";
		$vObject = $this->vObjects[$this->sObject];
		if(!isset($vObject["tables"])) { return false; }
		$vTables = $vObject["tables"];

		// SELECT customizado
		if($mColumns) {
			$aRequires = (\is_array($mColumns)) ? $mColumns : self::call("shift")->convert($mColumns, "json-array");
			$aSelected = [];
			foreach($aRequires as $mColumn) {
				$aColumn = (\is_array($mColumn)) ? $mColumn : [$mColumn];
				$aTable = \explode(".", $aColumn[0]);
				$aSelected[$aTable[0]] = true;
				// $sAlias = ((isset($aColumn[1])) ? $aColumn[1] : $aTable[1]);
				$sAlias = ((isset($aColumn[1])) ? $aColumn[1] : str_replace(".", "_", $aColumn[0]));
				$aSelect[$sAlias] = '["'.$aTable[0].'.'.$aTable[1].'","'.$sAlias.'"]';
				$aSelect[$sAlias] = '["'.$aTable[0].'.'.$aTable[1].'","'.$sAlias.'"]';
			}
		}

		$nJoinLevel = $this->argument("join_level");
		if(\is_array($mJoins)) {
			$aJoins = $mJoins;
		} else if(!empty($mJoins) && \is_string($mJoins) && $mJoins[0]==":") {
			$aJoins = \array_keys($vTables);
			$nJoinLevel = \substr($mJoins, 1);
		} else {
			$mJoins = self::call("fn")->istrue($mJoins);
			$aJoins = ($mJoins) ? \array_keys($vTables) : [];
		}

		$aChildren = (\is_array($mChildren)) ? $mChildren : (($mChildren==true) ? \array_keys($vTables) : []);

		$nLoopingOut = 0;
		$aUsed = $aUnUsed = [];

		// print_r($vTables);var_dump($mChildren);exit();
		$nTables = \count($vTables);
		while(\count($vTables)) {
			if($nLoopingOut++>$nTables) {
				echo "Error looping in view method\n";
				// print_r($vTables);
				// print_r($aUsed);
				// print_r($aUnUsed);
				exit();
			}

			if(isset($sJump)) {
				$sTable = $sJump;
				unset($sJump);
				if(!isset($vTables[$sTable]) && !isset($aUsed[$sTable])) { continue; }
				$vTable = $vTables[$sTable];
			} else {
				$sTable = \key($vTables);
				$vTable = \current($vTables);
				\next($vTables);
			}

			if($vTable["level"]>$nJoinLevel) {
				$aUnUsed[$sTable] = true;
				unset($vTables[$sTable]);
				continue;
			}

			// FROM (va antes por el continue en los joins)
			if($vTable["type"]=="main" || !\count($aFrom)) {
				$aUsed[$this->sObject] = true;
				$aFrom[] = '"'.$this->sObject.'"';
			} else if($vTable["type"]=="children") {
				if($mChildren===true || \in_array($sTable, $aChildren) || (isset($vTable["parent"]) && $vTable["parent"]!=$this->sObject && \count($aChildren))) {
					if(isset($aUnUsed[$vTable["join"]])) {
						$aUnUsed[$sTable] = true;
						unset($vTables[$sTable]);
						continue;
					}
					if(!isset($aUsed[$vTable["join"]])) {
						$sJump = $vTable["join"];
						continue;
					}

					$aUsed[$sTable] = true;
					$sFrom = '[
						"'.$vTable["name"].'",
						"'.$sTable.'", 
						[
							["'.$sTable.'.pid","eq","'.$vTable["join"].'.'.$vTable["using"].'"]
					';
					
					if(!$bDeleted && in_array("state", $vTables[$sTable]["columns"])) { $sFrom .= ',"AND",["'.$sTable.'.state","isnot","(NULL)"]'; }
					$sFrom .= ']]';
					$aFrom[] = $sFrom;
				} else {
					$aUnUsed[$sTable] = true;
					unset($vTables[$sTable]);
				}
			} else if($bParent && $sTable=="__parent") {
				$aUsed[$sTable] = true;
				$aFrom[] = '[
					"'.$vTable["name"].'",
					"'.$sTable.'",
					[
						["'.$sTable.'.id","eq","'.$this->sObject.'.pid"]
					]
				]';
			} else if(($mJoins===true || \in_array($sTable, $aJoins)) && !empty($vTable["join"]) && $vTable["type"]=="join") {
				if(isset($aUnUsed[$vTable["join"]])) {
					$aUnUsed[$sTable] = true;
					unset($vTables[$sTable]);
					continue;
				}
				if(!isset($aUsed[$vTable["join"]]) && $sTable!=$vTable["join"]) {
					$sJump = $vTable["join"];
					continue;
				}

				$aUsed[$sTable] = true;
				$sJoinField = (isset($vTable["field"])) ? $vTable["field"] : "id";
				$sFrom = '[
					"'.$vTable["name"].'",
					"'.$sTable.'",
					[
						["'.$sTable.'.'.$sJoinField.'","eq","'.$vTable["join"].'.'.$vTable["using"].'"]
				';

				if(!$bDeleted && \in_array("state", $vTables[$sTable]["columns"])) { $sFrom .= ',"AND",["'.$sTable.'.state","isnot","(NULL)"]'; }
				$sFrom .= ']]';
				$aFrom[] = $sFrom;
			} else {
				$aUnUsed[$sTable] = true;
				unset($vTables[$sTable]);
			}

			if(isset($aUsed[$sTable])) { unset($vTables[$sTable]); }

			// SELECT
			if(!isset($aSelected) && $vTable) {
				foreach($vTable["columns"] as $sField) {
					if($sTable!=$this->sObject) {
						if($vTable["type"]=="children" && ($mChildren==false || !\in_array($sTable, $aChildren))) { break; }
						if($vTable["type"]=="parent" && !$bParent) { break; }
						if($vTable["type"]=="join" && ($mJoins==false || !\in_array($sTable, $aJoins))) { break; }
					}

					switch($sAliasMode) {
						case "all":
							$sColumnAlias = $sTable."_".$sField;
							break;

						case "joins":
						$sColumnAlias = ($vTable["type"]=="main") ? $sField : $sTable."_".$sField;
						break;

						case "none":
							$sColumnAlias = $sField;
							break;

						default:
							$sColumnAlias = (isset($aSelect[$sField])) ? $sTable."_".$sField : $sField;
							break;
					}
					
					if(isset($aSelect[$sColumnAlias])) {
						$sErrors .= "duplicate alias '".$sColumnAlias."' for `".$sTable."`.`".$sField."`\n";
						continue;
					}
					
					$aSelect[$sColumnAlias] = '["'.$sTable.'.'.$sField.'","'.$sColumnAlias.'"]';
				}
			}
		}

		if(!empty($sErrors)) {
			$this->Logger("warning", $sErrors);
		}

		if(!$mColumns) {
			$aSelect = \array_merge([
				"__id__" => '["'.$this->sObject.'.id","__id__"]',
				"__imya__" => '["'.$this->sObject.'.imya","__imya__"]'
			], $aSelect);
		}

		if($this->bViewFields) { return $aSelect; }

		$sView = '{"columns" : ['.\implode(',', $aSelect).'], "tables" : ['.\implode(', ', $aFrom).']}';
		$sEOL = $this->argument("view_eol");

		return (\strtolower($sOutputMode)=="jsql") ? $sView : $this->JsqlParser($sView, $sEOL);
	}

	public function viewFields() {
		if(!$this->sObject) { self::errorMessage($this->object, 1001); }
		list($sOutputMode,$sAliasMode,$mJoins,$mChildren,$mColumns,$bParent) = $this->getarguments("view_mode,view_alias,view_joins,view_children,view_columns,view_parent", \func_get_args());

		$this->bViewFields = true;
		$aFields = $this->view($sOutputMode,$sAliasMode,$mJoins,$mChildren,$mColumns,$bParent);
		$aFields = \array_map("json_decode", $aFields);
		$this->bViewFields = false;
		return $aFields;
	}

	public function viewchildren() {
		if(!$this->sObject) { self::errorMessage($this->object, 1001); }
		list($sOutputMode,$sAliasMode,$bJoins,$bDeleted) = $this->getarguments("view_mode,view_alias,view_joins,view_deleted", \func_get_args());

		$aSelect = $aFrom = [];
		$sErrors = "";
		$vObject = $this->vObjects[$this->sObject];
		if(!isset($vObject["tables"])) { return false; }
		$vTables = $vObject["tables"];

		\array_shift($vTables);
		\array_unshift($vTables, $vTables[$this->sChildTableAlias]);
		$vTables[$this->sChildTableAlias]["type"] = "main";

		foreach($vTables as $sTable => $vTable) {
			if($vTable["type"]=="children") { continue; }
			if($vTable["type"]=="join" && $vTable["join"]!=$this->sChildTableAlias) { continue; }

			foreach($vTable["columns"] as $sField) {
				if($vTable["type"]=="join") {
					if(!$bJoins || ($vTables[$vTable["join"]]["type"]=="children" && !$bChildren)) {
						continue;
					}
				}

				switch($sAliasMode) {
					case "all":
						$sColumnAlias = $sTable."_".$sField;
						break;

					case "joins":
						$sColumnAlias = ($vTable["type"]=="main") ? $sField : $sTable."_".$sField;
						break;

					case "none":
						$sColumnAlias = $sField;
						break;

					default:
						$sColumnAlias = (isset($aSelect[$sField])) ? $sTable."_".$sField : $sField;
						break;
				}
				
				
				if(isset($aSelect[$sColumnAlias])) {
					$sErrors .= "duplicate alias '".$sColumnAlias."' for `".$sTable."`.`".$sField."`\n";
					continue;
				}
				
				$aSelect[$sColumnAlias] = '["'.$sTable.'.'.$sField.'","'.$sColumnAlias.'"]';
			}

			if($vTable["type"]=="main") {
				$aFrom[] = '["'.$this->sChildTable.'", "'.$this->sChildTableAlias.'"]';
			} else if($vTable["type"]=="related" && $bJoins) {
				$sJoinField = (isset($vTable["field"])) ? $vTable["field"] : "id";
				if(!$bDeleted) {
					$aFrom[] = '["'.$vTable["name"].'","'.$sTable.'", [["'.$sTable.'.'.$sJoinField.'","eq","'.$vTable["join"].'.'.$vTable["using"].'"],"AND",["'.$sTable.'.state","isnot","(NULL)"]]]';
				} else {
					$aFrom[] = '["'.$vTable["name"].'","'.$sTable.'", [["'.$sTable.'.'.$sJoinField.'","eq","'.$vTable["join"].'.'.$vTable["using"].'"]]]';
				}
			}
		}
		
		if(!empty($sErrors)) {
			$this->Logger("warning", $sErrors);
		}

		$sView = '{"columns" : ['.\implode(', ', $aSelect).'], "tables" : ['.\implode(', ', $aFrom).']}';
		$sEOL = $this->argument("view_eol");
		return (strtolower($sOutputMode)=="jsql") ? $sView : $this->JsqlParser($sView, $sEOL);
	}

	final private function AlvinInit() {
		if(NGL_ALVIN===null || !$this->argument("alvin")) { return false; }
		if(!self::call("alvin")->loaded()) { return (self::call("alvin")->autoload()===false) ? false : true; }
		return true;
	}

	final private function AlvinSQL($sJSQL, $bOnlyWhere=false) {
		if($this->AlvinInit()) {
			$sTableName = ($this->bChildMode) ? $this->sChildTable : $this->sObject;
			$sRole = self::call("alvin")->role();
			$role = $this->db->query("SELECT id FROM __ngl_owl_structure__ WHERE name = '".$sTableName."' AND roles = '1'");
			if(!empty($sRole) && \strtoupper($sRole)!="ADMIN" && $role->rows()) {
				$sChain = self::call("alvin")->rolechain();
				$sRoles = (!empty($sChain)) ? "OR role IN ('".\str_replace(",", "','", $sChain)."')" : "";

				$sHash = self::call()->unique(16);
				$sHashNot = self::call()->unique(16);
				if(!$bOnlyWhere) {
					$aJSQL = self::call("jsql")->decode($sJSQL);
					if(isset($aJSQL["where"])) {
						$aJSQL["where"] = [$aJSQL["where"], "AND", [[$sTableName.".imya", "in", $sHash], "OR", [$sTableName.".imya", "notin", $sHashNot]]];
					} else {
						$aJSQL["where"] = [[$sTableName.".imya", "in", $sHash], "OR", [$sTableName.".imya", "notin", $sHashNot]];
					}
					$sSQL = $this->JsqlParser($aJSQL);
				} else {
					$sSQL = "((".$sJSQL." IN (".$sHash.")) OR (".$sJSQL." NOT IN (".$sHashNot.")))";
				}
				
				// consulta final
				$sSQL = \str_replace($sHash, "SELECT imya FROM __ngl_owl_index__ WHERE (role IS NULL OR role = '".$sRole."' ".$sRoles.")", $sSQL);
				$sSQL = \str_replace($sHashNot, "SELECT imya FROM __ngl_owl_index__ WHERE 1", $sSQL);
			} else {
				$sSQL = (!$bOnlyWhere) ? $this->JsqlParser($sJSQL) : "";
			}
		} else {
			$sSQL = (!$bOnlyWhere) ? $this->JsqlParser($sJSQL) : "";
		}

		return $sSQL;
	}

	final public function alvinWhere() {
		list($sJSQL) = $this->getarguments("jsql", \func_get_args());
		return $this->AlvinSQL($sJSQL, true);
	}

	private function CrossRows($sTable, $sWhere=false, $aConditions=null) {
		if(isset($this->vObjects[$sTable])) {
			$vForeignKey = $this->vObjects[$this->sObject]["foreignkey"];
		} else {
			$sSQL = $this->JsqlParser('{"columns":["foreignkey"], "tables":["__ngl_owl_structure__"], "where":[["name","eq","('.$sTable.')"]]}');
			$this->attribute("query", $sSQL);
			$foreignkey = $this->db->query($sSQL);
			
			$sForeignKey = $foreignkey->get("foreignkey");
			$foreignkey->destroy();
			$foreignkey = null;
			
			$vForeignKey = self::call("jsql")->decode($sForeignKey);
		}

		$vCrossRows = [];
		if(\is_array($vForeignKey) && \count($vForeignKey)) {
			foreach($vForeignKey["tables"] as $sCrossTable => $aFields) {
				if($sWhere!==false) {
					$aConditions = [];
					$aConditions["where"] = [$sWhere];
					$aConditions["from"] = [];
				}
			
				foreach($aFields as $nKey => $sField) {
					$sIndex = \md5($sTable.$vForeignKey["fields"][$nKey].$sCrossTable.$sField);
					$aConditions["where"][$sIndex] = '
						["'.$sTable.'.'.$vForeignKey["fields"][$nKey].'","eq","'.$sCrossTable.'.'.$sField.'"],
						"AND",
						["'.$sCrossTable.'.state","isnot","(NULL)"]
					';
				}

				$aConditions["from"][$sTable]		= $sTable;
				$aConditions["from"][$sCrossTable]	= $sCrossTable;

				// query
				$sSQL = $this->AlvinSQL('{
					"type":"select",
					"columns":["'.$sCrossTable.'.id"],
					"tables":["'.\implode('","', $aConditions["from"]).'"],
					"where":['.\implode(',"AND",', $aConditions["where"]).']
				}');

				$aRows = [];
				$this->attribute("query", $sSQL);
				$rows = $this->db->query($sSQL);
				if($rows) {
					$aRows	= ($rows->rows()) ? $rows->getall("id") : [];
					$nRows	= \count($aRows);
					$sRows	= \implode(",", $aRows);
					if($nRows) {
						$sSQLUpdate = '{
							"type":"update",
							"columns":[["'.$sCrossTable.'.state","eq","(NULL)"]],
							"tables":["'.\implode('","', $aConditions["from"]).'"],
							"where":['.\implode(',"AND",', $aConditions["where"]).']
						}';

						$aCascade 					= $this->aCascade;
						$sCascadeIndex 				= \count($aConditions["from"])."-".\implode("-", $aConditions["from"]);
						$aCascade[$sCascadeIndex] 	= [$sCrossTable, $sRows, $sSQLUpdate];
						$this->aCascade 			= $aCascade;

						$vCrossRows[] = $nRows." ROWS IN `".$sCrossTable."` LINKED WITH `".$sTable."`";
						$vCrossRows = \array_merge($vCrossRows, $this->CrossRows($sCrossTable, false, $aConditions));
					}
				}
				
				if($sWhere===false) { $aConditions = null; }
			}
		}

		return (!empty($vCrossRows)) ? ["info"=>$vCrossRows, "cascade"=>$this->aCascade] : [];
	}

	private function DeleteInCascade($aCascade) {
		\krsort($aCascade);
		$nRows = 0;
		foreach($aCascade as $aTable) {
			$this->attribute("query", $aTable[2]);
			if($this->db->query($aTable[2])) {
				$aIDs = \explode(",", $aTable[1]);
				foreach($aIDs as $nID) {
					if($sImya = $this->ImyaFromID($aTable[0], $nID)) {
						$this->OwLog($sImya, "delete");
					}
					$nRows++;
				}
			}
		}
		
		return $nRows;
	}

	protected function GetID($mID, $sTable=null) {
		if($mID===null) { return null; }
		$sTableName = $sTable;

		if($this->db===null) {
			$this->attribute("current", (int)$mID);
			return $this->attribute("current");
		}

		if($sTable===null) {
			// uso interno
			if(!$this->sObject) { self::errorMessage($this->object, 1001); }
			$sTableName = $this->sObject;
		}

		if(self::call()->isInteger($mID)) {
			$sJSQL = $this->JsqlParser('{
				"columns":["id"], 
				"tables":["'.$sTableName.'"], 
				"where":[
					["'.$sTableName.'.id","eq","('.$mID.')"],
					"AND",
					["'.$sTableName.'.state","isnot","(NULL)"]
				]
			}');
			$this->attribute("query", $sJSQL);
			$id = $this->db->query($sJSQL);
			if($id->rows()) {
				if($sTable!==null) { return $id->get("id"); } // uso interno
				$this->attribute("current", $id->get("id"));
				return $this->attribute("current");
			} else {
				return false;
			}
		}

		if(\is_array($mID)) {
			$aWhere = $this->db->escape($mID);
			$sJSQL = $this->JsqlParser('{
				"columns":["id"],
				"tables":["'.$sTableName.'"],
				"where":[["'.$sTableName.'.'.$aWhere[0].'","'.$aWhere[1].'","('.$aWhere[2].')"]]
			}');
	
			$this->attribute("query", $sJSQL);
			$id = $this->db->query($sJSQL);
			if($id->rows()) {
				if($sTable!==null) { return $id->get("id"); } // uso interno
				$this->attribute("current", $id->get("id"));
				return $this->attribute("current");
			} else {
				return false;
			}
		}

		$sImya = self::call()->imya($mID);
		$sJSQL = $this->JsqlParser('{
			"columns":["id"],
			"tables":["'.$sTableName.'"],
			"where":[["'.$sTableName.'.imya","eq","('.$sImya.')"]]
		}');

		$this->attribute("query", $sJSQL);
		$id = $this->db->query($sJSQL);
		if($id->rows()) {
			if($sTable!==null) { return $id->get("id"); } // uso interno
			$this->attribute("current", $id->get("id"));
			return $this->attribute("current");
		} else {
			return false;
		}
	}
	
	private function GetRelationship(&$aTables, $vObject, $sAlias, $aParents) {
		$this->x = 0;
		$this->GetRelationshipChildren($aTables, $vObject, $sAlias, $aParents);
		$aTablesNames = \array_keys($aTables);
		$aWhere = [];
		foreach($aTablesNames as $sTbName) {
			$aWhere[] = '["name","eq","('.$sTbName.')"]';
		}
		$sWhere = \implode(',"OR",', $aWhere);
		$sSQL = $this->JsqlParser('{"tables":["__ngl_owl_structure__"], "where":['.$sWhere.']}');
		$relationship = $this->db->query($sSQL);
		$vRelationship = $relationship->getall("#name");
		$relationship->destroy();
		$relationship = null;
	}

	private function GetRelationshipChildren(&$aTables, $mObject, $sAlias, $aParents) {
		if($this->x++>500) { echo "Error GetRelationshipChildren method\n"; \print_r($aTables); exit(); }

		$aRelations = $aColumns = [];

		if(\is_array($mObject)) {
			$sObjectName = $sAlias = $mObject["name"];
			$aColumns = $mObject["columns"];
			$aRelations = $mObject["relationship"];
		} else {
			$sObjectName = $mObject;
			if(!$sAlias) { $sAlias = $sObjectName; }

			$sSQL = $this->JsqlParser('{
				"columns":["name","columns","relationship"], 
				"tables":["__ngl_owl_structure__"], 
				"where":[["name","eq","('.$mObject.')"]]
			}');

			$relations = $this->db->query($sSQL);
			if($relations->rows()) {
				$aGetRelations = $relations->get();
				$relations->destroy();
				$relations = null;
				$aColumns = ($aGetRelations["columns"]) ? self::call("jsql")->decode($aGetRelations["columns"]) : [];
				$aRelations = ($aGetRelations["relationship"]) ? self::call("jsql")->decode($aGetRelations["relationship"]) : [];

				if(!isset($aRelations["joins"])) { $aRelations["joins"] = []; }
				if(!isset($aRelations["children"])) { $aRelations["children"] = []; }
			}

			if(\is_array($aRelations) && \count($aRelations) && isset($aTables[$sAlias])) {
				$aTables[$sAlias]["parent"] = (isset($aRelations["parent"])) ? $aRelations["parent"] : null;
			}
		}

		$this->aRelationships[$sObjectName] = $aColumns;
		$aTables[$sAlias]["columns"] = $aColumns;
		if(\is_array($aRelations)) {
			$this->nRelationshipsLevel++;
			if($this->nRelationshipsLevel <= $this->argument("join_level")) {
				// joins
				if(isset($aRelations["joins"]) && \count($aRelations["joins"])) {
					$this->GetRelationshipStructure($aTables, $aParents, $sObjectName, $sAlias, $aRelations["joins"], false);
				}

				// children
				if(isset($aRelations["children"]) && \count($aRelations["children"])) {
					$this->GetRelationshipStructure($aTables, $aParents, $sObjectName, $sAlias, $aRelations["children"], true);
				}
			}
		}

		$this->nRelationshipsLevel--;
		return true;
	}

	private function GetRelationshipStructure(&$aTables, &$aParents, $sObjectName, $sAlias, $aRelations, $bChildren) {
		// resolucion de multiples joins
		$aTablesToJoin = $aToJoinsUnique = [];
		foreach($aRelations as $aTableToJoin) {
			if(!isset($aToJoinsUnique[$aTableToJoin["name"]])) { $aToJoinsUnique[$aTableToJoin["name"]] = []; }
			$aTableToJoin["alias"] = $aTableToJoin["name"];
			$aToJoinsUnique[$aTableToJoin["name"]][] = $aTableToJoin;
		}
		foreach($aToJoinsUnique as $aTablesJoinsGroup) {
			if(\is_array($aTablesJoinsGroup) && \count($aTablesJoinsGroup)>1) {
				foreach($aTablesJoinsGroup as $aTableGroup) {
					$aTableGroup["alias"] = $aTableGroup["name"]."_".$aTableGroup["using"];
					$aTablesToJoin[] = $aTableGroup;
				}
			} else {
				$aTablesToJoin[] = $aTablesJoinsGroup[0];
			}
		}
		
		// joins
		foreach($aTablesToJoin as $vTable) {
			$sUsingField				= ($bChildren) ? "id" : (\array_key_exists("using", $vTable) ? $vTable["using"] : null);
			$sJoinAlias					= $sAlias."_".$vTable["alias"];

			$aTable						= [];
			$aTable["name"] 			= $vTable["name"];
			$aTable["level"] 			= $this->nRelationshipsLevel;
			$aTable["alias"] 			= $sJoinAlias;
			$aTable["type"]				= ($bChildren) ? "children" : "join";
			$aTable["join"]				= $sAlias;
			$aTable["using"]			= $sUsingField;
			$aTable["field"]			= (\array_key_exists("field", $vTable)) ? $vTable["field"] : "id";
			$aTables[$sJoinAlias] 		= $aTable;

			// joins
			$sJoinUniqueID = \md5($sObjectName."@".$vTable["name"]."@".$sUsingField);
			if(!\key_exists($sJoinAlias, $aParents) && !isset($aParents["**CONTROL**"][$sJoinUniqueID])) {
				$aParents[$sJoinAlias] = true;
				$aParents["**CONTROL**"][$sJoinUniqueID] = $sObjectName."@".$vTable["name"]."@".$sUsingField;
				$this->GetRelationshipChildren($aTables, $aTable["name"], $sJoinAlias, $aParents);
			} else if(isset($this->aRelationships[$aTable["name"]])) {
				$aTables[$sJoinAlias]["columns"] = $this->aRelationships[$aTable["name"]];
				unset($aParents[$aTable["name"]]);
			} else {
				$aTables[$sJoinAlias]["columns"] = $aColumns;
				unset($aParents[$aTable["name"]]);
			}

			// registro de hijos
			if($bChildren) { $this->aTmpChildren[$aTable["alias"]] = $aTable["name"]; }
		}
	}

	private function Imya($sObject=null) {
		if($sObject===null) { $sObject = $this->sObject; }
		$sGroup = \substr(self::call()->strimya($sObject), 0, 12);
		return $sGroup.self::call()->unique(20);
	}

	private function ImyaFromID($sTableName, $nID) {
		// ansi query no requiere jsql
		$imya = $this->db->query("SELECT imya FROM ".$sTableName." WHERE id='".$nID."'");
		if($imya->rows()) { return $imya->get("imya"); }
		return false;
	}

	private function JsonAppener($sJSQL, $sExtra) {
		$aJSQL = self::call("jsql")->decode($sJSQL);
		$aExtra = self::call("jsql")->decode($sExtra);
		$aJSQL = self::call()->arrayAppend($aJSQL, $aExtra);
		return self::call("jsql")->encode($aJSQL);
	}

	private function JsqlParser($mJSQL, $EOL=null) {
		return $this->db->jsqlParser($mJSQL, $EOL);
	}

	private function Logger($sStatus=null, $aDetails=[]) {
		if($sStatus===null) {
			$this->attribute("log", []);
		} else {
			$this->attribute("log", ["status"=>$sStatus, "details"=>$aDetails]);

			if($this->argument("use_history")) {
				$aAttrHistory = $this->attribute("history");
				$aAttrHistory[] = $this->attribute("log");
				$this->attribute("history", $aAttrHistory);
			}
		}
	}

	private function OwLog($sImya, $sAction, $aChangeLog=null) {
		if($this->argument("owlog")) {
			$aLog				= [];
			$aLog["imya"]		= $sImya;
			$aLog["user"]		= self::call("sysvar")->UID;
			$aLog["action"]		= $sAction;
			$aLog["date"]		= \date("Y-m-d H:i:s");
			$aLog["ip"]			= self::call("sysvar")->IP;
			$aLog["changelog"]	= ($aChangeLog!=null) ? $this->db->escape(\json_encode($aChangeLog)) : null;

			$this->db->insert("__ngl_owl_log__", $aLog, "INSERT", false, true);
			$this->Logger("success", $aLog);
		}
	}

	private function UpdateData($aArguments, $nState=1) {
		$nRows = 0;

		// actualizacion multiple con array multiple
		$mCurrent = \current($aArguments);
		if(\is_array($aArguments) && \count($aArguments)==1 && \is_array($mCurrent) && \is_array(current($mCurrent))) {
			$aArguments = $mCurrent;
		}
		
		// update multiple
		if(\is_array($aArguments) && \count($aArguments)>1 && \is_array($mCurrent)) {
			$bChildMode = $this->bChildMode;
			foreach($aArguments as $aInput) {
				// evita la desactivacion del modo child
				$this->bChildMode = $bChildMode;
				$this->bInternalCall = true;
				if(!\is_array(\current($aInput))) { $aInput = [$aInput]; }
				$nReturn = $this->UpdateData($aInput, $nState);
				if($nReturn!==false) { $nRows += $nReturn; }
				$this->bInternalCall = false;
			}

			return $nRows;
		}

		// update simple
		list($vData) = $this->getarguments("data", $aArguments);
		if(!$this->bChildMode) {
			// padres
			if(!$this->sObject) { self::errorMessage($this->object, 1001); }
			$sTable = $this->sObject;
			if(\is_array($vData) && \count($vData)) {
				if(isset($vData["id"])) {
					$this->attribute("current", $vData["id"]);
				} else if(isset($vData["imya"])) {
					$nID = $this->GetID($vData["imya"]);
					$this->attribute("current", $nID);
				}
			}

			if(!$this->attribute("current")) { self::errorMessage($this->object, 1002); }
			$sWhere = '[["'.$sTable.'.id","eq","('.(int)$this->attribute('current').')"],"AND",["'.$sTable.'.state","isnot","(NULL)"]]';
			$nRowID = $this->attribute("current");
		} else {
			// hijos
			$sTable = $this->sChildTable;
			if(!$this->attribute("current")) { self::errorMessage($this->object, 1002); }
			
			$aWhere = [];
			$aWhere[] = '["'.$sTable.'.pid","eq","('.$this->attribute('current').')"],"AND",["'.$sTable.'.state","isnot","(NULL)"]';
			if(isset($vData["id"])) {
				$aWhere[] = '"AND",["'.$sTable.'.id","eq","('.(int)$vData["id"].')"]';
				$nRowID = $vData["id"];
			} else if(isset($vData["imya"])) {
				$vData["id"] = $this->GetID($vData["imya"], $sTable);
				$aWhere[] = '"AND",["'.$sTable.'.id","eq","('.(int)$vData["id"].')"]';
				$nRowID = $vData["id"];
			} else {
				$sSQLChildren = '{"columns":["id"], "tables":["'.$sTable.'"], "where":['.\implode($aWhere).']}';
				$childrens = $this->query($sSQLChildren);
				$aRowID = $childrens->getall("id");
			}

			$sWhere = "[".\implode(",", $aWhere)."]";
		}

		// chequeo de permisos
		$chkgrant = $this->query('{"columns":["id"], "tables":["'.$sTable.'"], "where":'.$sWhere.'}');
		if($chkgrant->rows()) {
			unset($vData["id"], $vData["imya"]);
			if(!$nState) {
				// borrado (state = NULL)
				$this->sCrossTable = $sTable;
				$vCrossRows = $this->CrossRows($sTable, $sWhere);
				$this->aCascade = [];

				if(!empty($vCrossRows)) {
					if($this->argument("cascade")) {
						$nRows += $this->DeleteInCascade($vCrossRows["cascade"]);
					} else {
						$this->Logger("foreignkeys", $vCrossRows["info"]);
						return false;
					}
				}

				$vData = ["state"=>null, "code"=>null];
			} else if($nState==2) {
				// suspencion (state = 0)
				$vData = ["state"=>0];
				if(!$this->bChildMode && $this->argument("inherit")) {
					if($sTable==$this->sObject && isset($this->vObjects[$sTable]["children"])) {
						foreach($this->vObjects[$sTable]["children"] as $sChildTable) {
							$sChildWhere = $this->JsqlParser('{"type":"where", "where":[["pid","eq","('.$nRowID.')"],"AND",["state","eq","(1)"]]}');
							$update = $this->db->update($sChildTable, $vData, $sChildWhere);
						}
					}
				}
			} else if($nState==3) {
				// reactivación (state = 1)
				$vData = ["state"=>1];
				if(!$this->bChildMode && $this->argument("inherit")) {
					if($sTable==$this->sObject && isset($this->vObjects[$sTable]["children"])) {
						foreach($this->vObjects[$sTable]["children"] as $sChildTable) {
							$sChildWhere = $this->JsqlParser('{"type":"where", "where":[["pid","eq","('.$nRowID.')"],"AND",["state","eq","(0)"]]}');
							$update = $this->db->update($sChildTable, $vData, $sChildWhere);
						}
					}
				}
			} else if($nState==4) {
				// toggle: suspencion | reactivación
				if(!$this->bChildMode && $this->argument("inherit")) {
					if($sTable==$this->sObject && isset($this->vObjects[$sTable]["children"])) {
						foreach($this->vObjects[$sTable]["children"] as $sChildTable) {
							$vData = ["state"=>0];
							$sChildWhere = $this->JsqlParser('{"type":"where", "where":[["pid","eq","('.$nRowID.')"],"AND",["state","eq","(1)"]]}');
							$update = $this->db->update($sChildTable, $vData, $sChildWhere);
							if(!$update->rows()) {
								$vData = ["state"=>1];
								$sChildWhere = $this->JsqlParser('{"type":"where", "where":[["pid","eq","('.$nRowID.')"],"AND",["state","eq","(0)"]]}');							
								$update = $this->db->update($sChildTable, $vData, $sChildWhere);
							}
						}
					}
				}
			} else {
				//  actualizacion de datos, no modifica state
				// validacion
				unset($vData["state"]);
				if(isset($this->vObjects[$sTable]["validate_update"])) {
					$sRules = $this->vObjects[$this->sObject]["validate_update"];
					if(!empty($sRules)) {
						$vData = $this->Validate($vData, $sRules, true);
						if($vData===null) { self::errorMessage($this->object, 1003); return false; }
					}
				}
			}

			// escape
			if($this->argument("escape")) { $vData = $this->db->escape($vData); }
			$sSQLWhere = $this->JsqlParser('{"type":"where", "where":'.$sWhere.'}');
			if($nState==4) {
				$vData = ["state"=>0];
				$update = $this->db->update($sTable, $vData, $sSQLWhere);
				if(!$update->rows()) {
					$vData = ["state"=>1];
					$update = $this->db->update($sTable, $vData, $sSQLWhere);
				}
			} else {
				if($this->argument("owlog_changelog")) {
					$changes = $this->db->query("SELECT * FROM ".$sTable." WHERE ".$sSQLWhere);
					$aChangeLog = $changes->getall("#id");
				}
				$update = $this->db->update($sTable, $vData, $sSQLWhere);
			}
			
			if($update) {
				$nRows += $update->rows();
				if($nRows) {
					$aActions = ["delete", "update", "suspend", "unsuspend", "toggle"];
					$sLogAction = $aActions[$nState];
					if(!isset($aRowID)) { $aRowID = [$nRowID]; }
					foreach($aRowID as $nID) {
						$aChanges = (isset($aChangeLog, $aChangeLog[$nID])) ? $aChangeLog[$nID] : null;

						// log
						if($sImya = $this->ImyaFromID($sTable, $nID)) {
							$this->OwLog($sImya, $sLogAction, $aChanges);
						}
					}
				} else {
					$this->Logger("empty", "0 affected rows");
				}

				$update->destroy();
				$update = null;
			}

			if($this->bChildMode) {
				$this->bChildMode = false;
			}
		}

		return $nRows;
	}

	private function Validate($vData, $sRules, $bIgnoreDefault=false) {
		$vValidate = self::call("validate")->validate($vData, $sRules, $bIgnoreDefault); 
		$this->attribute("validate", $vValidate);
		if(isset($vValidate["errors"]) && $vValidate["errors"]===0) {
			$vData = \array_merge($vData, $vValidate["values"]);
			return $vData;
		}

		return false;
	}
}

?>