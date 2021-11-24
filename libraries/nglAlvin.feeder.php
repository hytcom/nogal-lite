<?php
/*
# Nogal
*the most simple PHP Framework* by hytcom.net
GitHub @hytcom
___
  
# alvin
## nglAlvin *extends* nglFeeder [2018-10-28]
Alvin es el sistema de seguridad de **nogal**, encargado de gestionar permisos, grupos y perfiles de usuario.  
Mas que un objeto es un concepto que atraviesa transversalmente todo el framework. 

https://github.com/hytcom/wiki/blob/master/nogal/docs/alvin.md
https://github.com/hytcom/wiki/blob/master/nogal/docs/alvinuso.md

#errors
1001 = Clave de encriptación indefinida
1002 = Token inválido o vacío
1003 = Clave grants duplicada
1004 = Clave grants indefinida
1005 = No se pudieron salvar las claves. Permiso denegado
1006 = Error en la ruta
1007 = Clave pública indefinida
1008 = Clave privada indefinida
1009 = Nombre de usuario incorrecto para el TOKEN
1010 = Passphrase indefinida
1011 = Nombre de permiso inválido
1012 = No se pudieron cargar los permisos
1013 = No se pudieron salvar las claves porque ya existen

*/
namespace nogal;

class nglAlvin extends nglFeeder implements inglFeeder {

	private $aToken;
	private $aGeneratedKeys;
	private $sAlvinPath;
	private $sCryptKey;
	private $sPrivateKey;
	private $sPassphrase;
	private $sGrantsFile;
	private $aGrants;
	private $aRAW;
	private $sDefaultGrants;
	private $crypt;
	private $roles;

	final public function __init__($mArguments=null) {
		$this->aToken = null;
		$this->aGeneratedKeys = [];
		$this->sCryptKey = $this->PrepareKey(NGL_ALVIN);
		$this->sPrivateKey = null;
		$this->sPassphrase = null;
		$this->aGrants = [];
		$this->aRAW = [];
		$this->sGrantsFile = null;
		$this->sDefaultGrants = '{"GRANTS":{"profiles":{"ADMIN":[]}},"ROLES":[],"RAW":[]}';
		$this->crypt = (self::call()->exists("crypt")) ? self::call("crypt") : null;
		$this->sAlvinPath = NGL_PATH_DATA.NGL_DIR_SLASH."alvin";
		$this->roles = self::call("tree")->loadtree([]);
		if($this->crypt!==null) { $this->crypt->type("rsa")->base64(true); }
		$this->__errorMode__("die");
	}

	// DB --------------------------------------------------------------------
	public function dbStructure() {
		return <<<SQL
-- MySQL / MariaDB -------------------------------------------------------------
-- users
CREATE TABLE IF NOT EXISTS `users` (
	`id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
	`imya` CHAR(32) NOT NULL DEFAULT '',
	`state` ENUM('0','1') DEFAULT '1' COMMENT 'NULL=eliminado, 0=inactivo, 1=activo',
	`wrong` INT UNSIGNED NOT NULL COMMENT 'cantidad de fallos en intentos de login',
	`fullname` VARCHAR(128) DEFAULT NULL,
	`username` VARCHAR(128) DEFAULT NULL,
	`password` VARCHAR(255) DEFAULT NULL,
	`email` CHAR(96) DEFAULT NULL,
	`profile` CHAR(32) DEFAULT NULL,
	`roles` CHAR(255) DEFAULT NULL,
	`alvin` MEDIUMEXT DEFAULT NULL COMMENT 'alvin token',
	PRIMARY KEY (`id`)
) ENGINE=MyISAM, DEFAULT CHARSET=utf8mb4, COLLATE=utf8mb4_unicode_ci COMMENT='Tabla de usuarios del sistema';
CREATE UNIQUE INDEX `imya` ON `users` (`imya`);
CREATE UNIQUE INDEX `username` ON `users` (`username`);
CREATE INDEX `state` ON `users` (`state`);

DROP TABLE IF EXISTS `users_entities`;
CREATE TABLE `users_entities` (
	`id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT NOT NULL,
	`imya` CHAR(32) NOT NULL DEFAULT '',
	`state` ENUM('0', '1') NULL DEFAULT '1',
	`pid` INT UNSIGNED NOT NULL COMMENT 'id del usuario',
	`entity` CHAR(32)  NOT NULL COMMENT 'imya del registro en la entidad asociada'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Identifica a los usuarios del sistema con los registros de otras entidades. Por ejemplo, cual es el usuario del vendedor JUAN';
CREATE UNIQUE INDEX `imya` ON `users_entities` (`imya`);
CREATE INDEX `state` ON `users_entities` (`state`);
CREATE INDEX `pid` ON `users_entities` (`pid`);
SQL;
	}

	// KEYS --------------------------------------------------------------------
	public function keys($bSet=false, $bReturnKeys=false) {
		if(!$this->crypt) { self::errorMessage($this->object, 1001); }
		$this->aGeneratedKeys = self::call("crypt")->type("rsa")->keys();
		if($bSet) {
			$this->setkey(true, $this->aGeneratedKeys["private"]);
			$this->setkey(false, $this->aGeneratedKeys["public"]);
		}
		return ($bReturnKeys) ? $this->aGeneratedKeys : $this;
	}

	public function saveKeys() {
		if(!$this->crypt) { self::errorMessage($this->object, 1001); }
		if(!\is_dir($this->sAlvinPath)) {
			if(!@\mkdir($this->sAlvinPath, 0777, true)) {
				self::errorMessage($this->object, 1005, $this->sAlvinPath);
			}
		}
		if(!\file_exists($this->sAlvinPath.NGL_DIR_SLASH."private.key") && !\file_exists($this->sAlvinPath.NGL_DIR_SLASH."public.key")) {
			@\file_put_contents($this->sAlvinPath.NGL_DIR_SLASH."private.key", $this->aGeneratedKeys["private"]);
			@\file_put_contents($this->sAlvinPath.NGL_DIR_SLASH."public.key", $this->aGeneratedKeys["public"]);
		} else {
			self::errorMessage($this->object, 1013, $this->sAlvinPath);
		}

		return $this;
	}

	public function setkey($bPrivate=false, $sKey=null) {
		if(!$this->crypt) { self::errorMessage($this->object, 1001); }
		if($bPrivate) {
			if($sKey===null) {
				if(\file_exists($this->sAlvinPath.NGL_DIR_SLASH."private.key")) {
					$sKey = \file_get_contents($this->sAlvinPath.NGL_DIR_SLASH."private.key");
				} else {
					return self::errorMessage($this->object, 1008);
				}
			}
			$this->sPrivateKey = $this->PrepareKey($sKey);
		} else {
			if($sKey===null) {
				if(\file_exists($this->sAlvinPath.NGL_DIR_SLASH."public.key")) {
					$sKey = \file_get_contents($this->sAlvinPath.NGL_DIR_SLASH."public.key");
				} else {
					return self::errorMessage($this->object, 1007);
				}
			}
			$this->sCryptKey = $this->PrepareKey($sKey);
		}
		return $this;
	}

    private function PrepareKey($sKey) {
        return \preg_replace([
            "/-----BEGIN PUBLIC KEY-----/is",
            "/-----END PUBLIC KEY-----/is",
            "/-----BEGIN RSA PRIVATE KEY-----/is",
            "/-----END RSA PRIVATE KEY-----/is",
            "/[\s]*/is"
        ], [""], $sKey);
    }

	// ADMIN GRANTS ------------------------------------------------------------
	// carga o crea los permisos
	public function loadGrants($sPassphrase=null) {
		if(!$this->crypt) { self::errorMessage($this->object, 1001); }
		if($sPassphrase===null) { return self::errorMessage($this->object, 1010); }
		$grants = self::call("file")->load($this->sAlvinPath.NGL_DIR_SLASH."grants");
		if($grants->size) {
			$sGrants = $grants->read();
			$sGrants = \preg_replace("/(\n|\r)/is", "", $sGrants);
			$grants->close();
			$sGrants = $sGrants = self::call("crypt")->type("aes")->key($sPassphrase)->base64(true)->decrypt($sGrants);
		} else {
			$sGrants = $this->sDefaultGrants;
		}

		return $this->jsonGrants($sGrants);
	}

	// escribe el archivo con los permisos
	public function save($sPassphrase=null) {
		if(!$this->crypt) { self::errorMessage($this->object, 1001); }
		if($sPassphrase===null) { return self::errorMessage($this->object, 1010); }

		$this->AdminGrants();
		$aRoles = $this->roles();
		self::call()->msort($this->aGrants, "ksort");
		$sGrants = \json_encode(["GRANTS"=>$this->aGrants, "ROLES"=>$aRoles, "RAW"=>$this->aRAW]);
		$sGrants = self::call("shift")->jsonformat($sGrants, true);
		$sGrants = self::call("crypt")->type("aes")->key($sPassphrase)->base64(true)->encrypt($sGrants);

		$this->BackupGrants();
		$save = self::call("file")->load($this->sAlvinPath.NGL_DIR_SLASH."grants");
		if($save->write(\chunk_split($sGrants, 80))!==false) {
			$save->close();

			if($this->crypt) {
				$sJsonRoles = self::call("shift")->jsonformat(\json_encode($aRoles), true);
				if(!$this->sPrivateKey) { return self::errorMessage($this->object, 1008); }
				$sJsonRoles = $this->crypt->type("rsa")->key($this->sPrivateKey)->encrypt($sJsonRoles);
				$saveroles = self::call("file")->load($this->sAlvinPath.NGL_DIR_SLASH."roles");
				if($saveroles->write(\chunk_split($sJsonRoles, 80))!==false) {
					$saveroles->close();
				}
			}

			return true;
		}

		return false;
	}

	private function BackupGrants() {
		$aBackups = self::call("files")->ls($this->sAlvinPath.NGL_DIR_SLASH, "*.bak", "info");
		$aBackups = self::call()->arrayMultiSort($aBackups, [["field"=>"timestamp", "order"=>"desc", "type"=>2]]);
		$aBackups = \array_slice($aBackups, 6);
		if(\count($aBackups)) {
			foreach($aBackups as $aBack) {
				\unlink($aBack["path"]);
			}
		}
		@\copy($this->sAlvinPath.NGL_DIR_SLASH."grants", $this->sAlvinPath.NGL_DIR_SLASH."grants_".\date("YmdHis").".bak");
	}

	// importa los permisos desde una cadena plana o un json
	public function import($sGrants) {
		if(!empty($sGrants) && $sGrants[0]=="{") {
			return $this->jsonGrants($sGrants);
		} else {
			$aGrants = self::call()->strToArray($sGrants);
			$this->jsonGrants($this->sDefaultGrants);
			foreach($aGrants as $sRow) {
				$aRow = \preg_split("/(\t|;|,)/is", $sRow);
				$sGroup = \trim(\array_shift($aRow));
				$this->setGrant("groups", $sGroup, []);
				foreach($aRow as $sGrant) {
					$sGrant = trim($sGrant);
					if(empty($sGrant)) { break; }
					$this->setGrant("grants", $sGrant, $sGrant);
					$this->setGrant("groups", $sGroup, [$sGrant=>$sGrant], 1);
				}
			}
		}

		return $this;
	}

	public function export($bPretty=false) {
		$sGrants = \json_encode(["GRANTS"=>$this->aGrants, "ROLES"=>$this->roles(), "RAW"=>$this->aRAW]);
		return ($bPretty) ? self::call("shift")->jsonFormat($sGrants) : $sGrants;
	}

	// retorna todos los permisos del tipo raw
	public function getraw($sProfile=null) {
		if($sProfile!==null) {
			if(isset($this->aRAW[$sProfile])) {
				return $this->aRAW[$sProfile];
			} else {
				return false;
			}
		}

		return $this->aRAW;
	}

	// agrega o sobreescribe los permisos raw de un perfil
	public function setraw($sProfile, $aValue, $bAppend=false) {
		if($bAppend) { $aValue = self::call()->arrayMerge($this->aRAW[$sProfile], $aValue); }
		$this->aRAW[$sProfile] = $aValue;
		return $this;
	}

	// elimina un perfil de los permisos raw
	public function unsetraw($sProfile) {
		unset($this->aRAW[$sProfile]);
		return $this;
	}

	// ROLES -------------------------------------------------------------------
	// valida un nombre de rol o una cadena de ellos separados por ,
	// si el nombre es nulo y hay un token cargado, intenta retornar el role del mismo
	public function role($sRoles=null) {
		if($sRoles===null && isset($_SESSION[NGL_SESSION_INDEX]["ALVIN"]["role"])) { return $_SESSION[NGL_SESSION_INDEX]["ALVIN"]["role"]; }
		$aRoles = \explode(",", $sRoles);
		foreach($aRoles as $x => $sRole) {
			$aRoles[$x] = $this->GrantName($sRole, false);
		}
		return \implode(",", $aRoles);
	}

	public function rolechain($sRoles=null) {
		if($sRoles===null && isset($_SESSION[NGL_SESSION_INDEX]["ALVIN"]["roleschain"])) { return $_SESSION[NGL_SESSION_INDEX]["ALVIN"]["roleschain"]; }
		if(!empty($sRoles) && \file_exists($this->sAlvinPath.NGL_DIR_SLASH."roles")) {
			$sRolesTree = \file_get_contents($this->sAlvinPath.NGL_DIR_SLASH."roles");
			if($sRolesTree = @self::call("crypt")->type("rsa")->base64(true)->key(NGL_ALVIN)->decrypt($sRolesTree)) {
				$aRoles = \json_decode($sRolesTree, true);
				if(\is_array($aRoles)) {
					$tree = self::call("tree")->loadtree($aRoles);
					$aUserRoles = \explode(",", $sRoles);
					$aChain = [$aUserRoles[0]];
					foreach($aUserRoles as $sRole) {
						$aChain[] = $sRole;
						$aChain = \array_merge($aChain, $tree->childrenChain($sRole, null));
					}
					return \implode(",", \array_unique($aChain));
				}
			}
		}
		return "";
	}

	public function roles() {
		return $this->roles->tree();
	}

	// resetea los roles
	public function resetRoles() {
		$this->roles = self::call("tree")->loadtree([]);
	}

	// agrega un role
	public function setRole($sRole, $sParent=null) {
		$aRole = ["id"=>$this->GrantName($sRole, false)];
		if($sParent!=null) { $aRole["parent"] = $this->GrantName($sParent, false); }
		$this->roles->node($aRole);
		return $this;
	}

	// obtiene la ruta de un role en formato cadena
	public function rolePath($sRole) {
		$sRole = $this->GrantName($sRole, false);
		return $this->roles->parentsChain($sRole, "id", ",");
	}

	// obtiene los hijos de un role
	public function roleChildren($sRole) {
		$sRole = $this->GrantName($sRole, false);
		return $this->roles->children($sRole);
	}

	// GRANTS ------------------------------------------------------------------
	// retorna todos los permisos del tipo grant
	public function getall() {
		return $this->aGrants;
	}

	// listado de permisos segun el tipo (grants|groups|profiles)
	public function get($sType=null) {
		if($sType!==null && isset($this->aGrants[$sType])) { return $this->aGrants[$sType]; }
		return [];
	}

	// retorna un permiso con su composicion
	public function grant($sName=null, $sType="grants") {
		$this->chkType($sType);
		$sName = $this->GrantName($sName);
		$sType = \strtolower($sType);
		if($sType!==false && $sName!==null && isset($this->aGrants[$sType][$sName])) {
			if($sType=="profiles" && $sName=="ADMIN") {
				return ["ADMIN"=>[]];
			} else {
				return $this->aGrants[$sType][$sName];
			}
		}
		return false;
	}

	// agrega un permiso a la estructura
	public function setGrant($sType, $sName, $mGrant=null) {
		$this->chkType($sType);
		$sName = $this->GrantName($sName);
		if($sName===false) { self::errorMessage($this->object, 1011, null, "die"); }
		if($sType=="grants" && $mGrant===null) { $mGrant = $sName; }
		
		$sIndex = $this->FindGrant($sName, false, $sType);
		
		// valor a array
		if(\is_array($mGrant)) {
			$mGrant = \array_unique($mGrant);
		} else {
			if($sType!="grants") { $mGrant = [$mGrant]; }
		}

		// nuevo registro
		if($sIndex===false) {
			if($sType=="grants") {
				$this->aGrants["grants"][$sName] = [];
			} else if($sType=="groups") {
				$this->MakeGroup($sName, $mGrant, true);
				\ksort($this->aGrants["groups"][$sName]);
			} else {
				if($sName=="ADMIN") { return $this; }
				$this->MakeProfile($sName, $mGrant, true);
			}
		} else { // edicion
			if($sType=="groups") {
				$this->MakeGroup($sIndex, $mGrant);
				\ksort($this->aGrants["groups"][$sIndex]);
			} else if($sType=="profiles") {
				if($sIndex=="ADMIN") { return $this; }
				$this->MakeProfile($sIndex, $mGrant);
			}
		}

		return $this;
	}

	private function MakeGroup($sName, $aGrants, $bNew=false) {
		if($bNew) { $this->aGrants["groups"][$sName] = []; }
		foreach($aGrants as $sGrant) {
			$sGrant = $this->GrantName($sGrant);
			if(\array_key_exists($sGrant, $this->aGrants["grants"])) {
				$this->aGrants["groups"][$sName][$sGrant] = [];
				$this->aGrants["grants"][$sGrant][$sName] = true;
			}
		}
		
		ksort($this->aGrants["groups"]);
		ksort($this->aGrants["groups"][$sName]);
	}

	private function MakeProfile($sName, $aGrants, $bNew=false) {
		if($bNew) { $this->aGrants["profiles"][$sName] = []; }
		foreach($aGrants as $sGrant) {
			if(!empty($sGrant) && $sGrant[0]=="-") { $sGrant = \substr($sGrant, 1); $bRemove = true; }
			$sGrant = $this->GrantName($sGrant, true);
			$aGrant = \explode(".", $sGrant);
			if(isset($this->aGrants["groups"][$aGrant[0]])) {
				if(isset($aGrant[1])) {
					if(!isset($this->aGrants["groups"][$aGrant[0]][$aGrant[1]])) { continue; }
					if(isset($bRemove)) {
						unset($this->aGrants["groups"][$aGrant[0]][$aGrant[1]][$sName]);
						unset($this->aGrants["profiles"][$sName][$aGrant[0]][$aGrant[1]]);
					} else {
						$this->aGrants["groups"][$aGrant[0]][$aGrant[1]][$sName] = true;
						$this->aGrants["profiles"][$sName][$aGrant[0]][$aGrant[1]] = true;
					}
				} else {
					if(!isset($bRemove)) {
						foreach($this->aGrants["groups"][$aGrant[0]] as $sGrant => $sTrue) {
							$this->aGrants["groups"][$aGrant[0]][$sGrant][$sName] = true;
						}
						$this->aGrants["profiles"][$sName][$aGrant[0]] = self::call()->truelize(\array_keys($this->aGrants["groups"][$aGrant[0]]));
					} else {
						foreach($this->aGrants["profiles"][$sName][$aGrant[0]] as $sGrant => $sTrue) {
							unset($this->aGrants["groups"][$aGrant[0]][$sGrant][$sName]);
							unset($this->aGrants["profiles"][$sName][$aGrant[0]][$sGrant]);
						}
						unset($this->aGrants["profiles"][$sName][$aGrant[0]]);
					}
				}
			}
		}

		ksort($this->aGrants["profiles"]);
		ksort($this->aGrants["profiles"][$sName]);
	}

	// elimina un permiso, grupo o perfil
	public function unsetGrant($sType, $sName) {
		$this->chkType($sType);
		$sName = $this->GrantName($sName);

		if($sName!==false) { 
			if($sType=="grants") {
				foreach($this->aGrants["grants"][$sName] as $sGroup => $bVal) {
					foreach($this->aGrants["groups"][$sGroup][$sName] as $sProfile => $bVal) {
						unset($this->aGrants["profiles"][$sProfile][$sGroup][$sName]);
					}					
					unset($this->aGrants["groups"][$sGroup][$sName]);
				}
			} else if($sType=="groups") {
				foreach($this->aGrants["groups"][$sName] as $sGrant => $aProfiles) {
					if(is_array($aProfiles) && count($aProfiles)) {
						foreach($aProfiles as $sProfile => $bVal) {
							unset($this->aGrants["profiles"][$sProfile][$sName]);
						}
					}
				}
			} else {
				if($sName=="ADMIN") { return $this; }
				foreach($this->aGrants["profiles"][$sName] as $sGroup => $aGrants) {
					if(\is_array($aGrants) && count($aGrants)) {
						foreach($aGrants as $sGrant => $bVal) {
							unset($this->aGrants["groups"][$sGroup][$sGrant][$sName]);
						}
					}
				}
			}

			unset($this->aGrants[$sType][$sName]);
			\ksort($this->aGrants[$sType]);
		}
		return $this;
	}

	// genera el token del usuario
	public function token($sProfileName, $sRoleName=null, $aGrants=[], $aRaw=[], $sUsername=null) {
		if($sUsername!==null) { $sUsername = $this->username($sUsername); }
		if(!$this->crypt) { return self::errorMessage($this->object, 1001); }

		$sProfileName = $this->profile($sProfileName);
		$sRoleName = $this->role($sRoleName);
		$aToken = ["profile"=>$sProfileName, "role"=>$sRoleName, "grants"=>null, "raw"=>null, "username"=>$sUsername];

		// permisos
		if(\is_array($aGrants) && \count($aGrants)) {
			$aToken["grants"] = $this->PrepareGrants($aGrants); 
		}

		// permisos crudos
		if(\is_array($aRaw) && \count($aRaw)) { $aToken["raw"] = $aRaw; }

		$sTokenContent = \serialize($aToken);
		if($this->crypt) {
			if(!$this->sPrivateKey) { return self::errorMessage($this->object, 1008); }
			$sTokenContent = $this->crypt->type("rsa")->key($this->sPrivateKey)->encrypt($sTokenContent);
		}
		$sTokenContent = \base64_encode($sTokenContent);
		$sTokenContent = \base64_encode($this->password($sUsername))."@".$sTokenContent;

		$sToken	 = "/-- NGL ALVIN TOKEN -------------------------------------------------------/\n";
		$sToken	.= \chunk_split($sTokenContent);
		$sToken	.= "/------------------------------------------------------- NGL ALVIN TOKEN --/";

		return $sToken;
	}

	//
	private function GrantName($sGrant, $bDot=false) {
		$sGrant = self::call()->unaccented($sGrant);
		$sRegex = (!$bDot) ? "/[^a-zA-Z0-9]+/" : "/[^a-zA-Z0-9\-\_\.]+/";
		return \strtoupper(\preg_replace($sRegex, "", $sGrant));
	}

	private function PrepareGrants($aProfile) {
		if(\array_key_exists("ADMIN", $aProfile)) { $aProfile = $this->aGrants["groups"]; }
		$aToken = [];
		foreach($aProfile as $sGroup => $aGrants) {
			$aToken[$sGroup] = true;
			if(!\is_array($aGrants) || !\count($aGrants)) { continue; }
			foreach($aGrants as $sGrant => $bVal) {
				$aToken[$sGroup.".".$sGrant] = true;
			}
		}
		return $aToken;
	}
	
	// decodifica los permisos json
	private function jsonGrants($sGrants) {
		$aGrants = json_decode($sGrants, true);
		if($aGrants!==null) {
			if(\array_key_exists("GRANTS", $aGrants)) { $this->aGrants = $aGrants["GRANTS"]; }
			if(\array_key_exists("ROLES", $aGrants)) { $this->roles = self::call("tree")->loadtree($aGrants["ROLES"]); }
			if(\array_key_exists("RAW", $aGrants)) { $this->aRAW = $aGrants["RAW"]; }
			if(!\array_key_exists("GRANTS", $aGrants) && !\array_key_exists("ROLES", $aGrants) && !\array_key_exists("RAW", $aGrants)) { $this->aGrants = $aGrants; }
		} else {
			return self::errorMessage($this->object, 1012);
		}

		// perfil y rol admin
		$this->AdminGrants();

		return $this;
	}

	// busca permisos
	private function FindGrant($sName, $bRecursive=false, $sType="grants") {
		$sType = \strtolower($sType);
		if(isset($this->aGrants[$sType], $this->aGrants[$sType][$sName])) { return $sName; }
		return false;
	}

	private function chkType(&$sType) {
		$sType = \strtolower($sType);
		return (\in_array($sType, ["grants", "groups", "profiles"])) ? $sType : false;
	}

	// USE GRANTS --------------------------------------------------------------
	// carga un token
	// tipo de carga de ALVIN-TOKEN (TOKEN|TOKENUSER|PROFILE)
	public function load($sToken=null, $sUsername=null, $sProfile=null) {
		if(!$this->crypt) { self::errorMessage($this->object, 1001); }

		// datos insuficientes
		if($sToken===null && $sProfile===null) { return false; }

		// modo de carga
		$sMode = \strtoupper(NGL_ALVIN_MODE);

		if($sToken!==null && $sMode!=="PROFILE") {
			$sToken = \preg_replace("/\s/is", "", $sToken);
			$sToken = \str_replace(["NGLALVINTOKEN","/---------------------------------------------------------/"], "", $sToken);

			$aToken = \explode("@", $sToken);
			if(isset($aToken[1])) {
				if($sUsername!==null) {
					$sUsername = $this->username($sUsername);
					if(\base64_decode($aToken[0])!==$this->password($sUsername)) {
						self::errorMessage($this->object, 1009);
						return false;				
					}
				}
				$sToken = $aToken[1];
			} else {
				if($sMode==="TOKENUSER") { return false; }
				$sToken = $aToken[0];
			}
		
			if($this->crypt) {
				$sDecrypt = $this->crypt->type("rsa")->key($this->sCryptKey)->decrypt(\base64_decode($sToken));
			} else {
				$sDecrypt = \base64_decode($sToken);
			}

			$this->aToken = \unserialize($sDecrypt);
		} else if($sProfile!==null) {
			$sProfileName = \trim($sProfile);
			$sProfileName = \strtoupper($sProfileName);
			$this->aToken = ["profile"=>$sProfileName];
		}

		// \nogal\dump($this->aToken);
		if(!\is_array($this->aToken)) {
			self::errorMessage($this->object, 1002);
			return false;
		}
		
		return $this;
	}

	// verifica que haya un token cargado
	public function loaded() {
		return ($this->aToken!==null);
	}

	public function autoload() {
		if(empty($_SESSION[NGL_SESSION_INDEX]["ALVIN"])) { return false; }
		$sUsername	= isset($_SESSION[NGL_SESSION_INDEX]["ALVIN"]["username"]) ? $_SESSION[NGL_SESSION_INDEX]["ALVIN"]["username"] : null;
		$sToken		= isset($_SESSION[NGL_SESSION_INDEX]["ALVIN"]["alvin"]) ? $_SESSION[NGL_SESSION_INDEX]["ALVIN"]["alvin"] : null;
		$sProfile	= isset($_SESSION[NGL_SESSION_INDEX]["ALVIN"]["profile"]) ? $_SESSION[NGL_SESSION_INDEX]["ALVIN"]["profile"] : null;
		if(!$this->load($sToken, $sUsername, $sProfile)) { return false; }
		return $this;
	}

	// valida un nombre de perfil
	// si el nombre es nulo y hay un token cargado, intenta retornar el perfil del mismo
	public function profile($sProfile=null) {
		if($sProfile===null && $this->aToken!==null) { return $this->aToken["profile"]; }
		return $this->GrantName($sProfile, false);
	}

	// valida un nombre de usuario
	// si el nombre es nulo y hay un token cargado, intenta retornar el nombre del mismo
	public function username($sUsername=null) {
		if($sUsername===null && $this->aToken!==null) { return $this->aToken["username"]; }
		$sUsername = self::call()->unaccented($sUsername);
		return \preg_replace("/[^a-zA-Z0-9\_\-\.\@]+/", "", $sUsername);
	}

	// encripta un password
	public function password($sPassword) {
		$sCryptPassword = \crypt($sPassword, '$6$rounds=5000$'.\md5($this->sCryptKey).'$');
		$aCryptPassword = \explode('$', $sCryptPassword, 5);
		return $aCryptPassword[4];
	}

	// ver token
	public function viewtoken() {
		return ($this->aToken!==null) ? $this->aToken : false;
	}

	public function analize($sGrant, $sToken=null) {
		$sGrant = \trim($sGrant);
		if(empty($sGrant)) { return false; }
		if($sGrant[0].$sGrant[1]=="?|") {
			$sGrant = \substr($sGrant, 2);
		}
		return $this->CheckGrant($sGrant, $sToken, "analize");
	}

	/// chequear si es parte de otro proesupestos.add tiene que matchear con presupuestos(algo)
	public function check($sGrant, $sToken=null) {
		$sGrant = \trim($sGrant);
		$sGrant = \strtoupper($sGrant);
		if(empty($sGrant)) { return false; }
		if($sGrant[0].$sGrant[1]=="!|") {
			$sGrant = \substr($sGrant, 2);
			return $this->CheckGrant($sGrant, $sToken, "none");
		} else if($sGrant[0].$sGrant[1]=="?|") {
			$sGrant = \substr($sGrant, 2);
			return $this->CheckGrant($sGrant, $sToken, "any");
		} else {
			return $this->CheckGrant($sGrant, $sToken, "all");
		}
	}

	public function raw($sIndex=null, $aKeyVals=false) {
		$mRaw = null;
		if(!\is_array($this->aToken)) { self::errorMessage($this->object, 1002); return false; }
		if(\array_key_exists("raw", $this->aToken)) {
			$mRaw = self::call()->arrayFlatIndex($this->aToken["raw"], $sIndex, true);
		}

		if(\is_array($aKeyVals)) {
			$mRaw = $this->RawKeywords($mRaw, $aKeyVals);
		}

		return $mRaw;
	}

	private function RawKeywords($mRaw, $aKeyVals) {
		if(\is_array($mRaw)) {
			foreach($mRaw as $mKey => $mValue) {
				$mRaw[$mKey] = $this->RawKeywords($mValue, $aKeyVals);
			}
		} else {
			\preg_match_all("/\{:([a-z0-9_\.]+):\}/is", $mRaw, $aMatchs);
			if(\is_array($aMatchs[0]) && \count($aMatchs[0])) {
				foreach($aMatchs[0] as $x => $sFind) {
					$sReplace = self::call()->arrayFlatIndex($aKeyVals, $aMatchs[1][$x], true);
					$mRaw = \str_replace($sFind, $sReplace, $mRaw);
				}
			}
		}

		return $mRaw;
	}

	private function FlatGrants($sName, $aGrants) {
		$aFlat = [$sName=>$sName];
		foreach($aGrants as $sName => $aGrant) {
			if(isset($aGrant["type"]) && $aGrant["type"]=="grant") {
				$aFlat[$sName] = $aGrant["grant"];
			} else {
				$aFlat = \array_merge($aFlat, $this->FlatGrants($sName, $aGrant["grant"]));
			}
		}
		return $aFlat;
	}

	public function unload($aUser) {
		$this->aToken = null;
		return $this;
	}

	private function AdminGrants() {
		if(!\array_key_exists("ADMIN", $this->aGrants["profiles"])) { $this->aGrants["profiles"]["ADMIN"] = []; }
		$aRoles = $this->roles();
		if(empty($aRoles) || !\array_key_exists("ADMIN", $aRoles)) { $this->setRole("ADMIN"); }
	}

	// primero intenta matchear el nombre del perfil
	// luego busca pertenencias de grupos xxx.
	// finalmente, permisos
	private function CheckGrant($sGrant, $sToken=null, $sMode="analize") {
		if($sToken!=null) { $this->load($sToken); }
		$aToCheck = (\strpos($sGrant, ",")===false) ? [$sGrant] : self::call()->explodeTrim(",", $sGrant);

		// nombre del perfil
		if(!empty($this->aToken["profile"]) && \in_array($this->aToken["profile"], $aToCheck)) { return ($sMode=="none") ? false : true; }

		if(\is_array($aToCheck) && \count($aToCheck)==1) {
			$sGrant = $aToCheck[0];
			if(isset($this->aToken["grants"][$sGrant])) {
				return ($sMode=="none") ? false : true;
			}
			return ($sMode=="none") ? true : false;
		} else {
			
			$aReturn = [];
			$bNone = true;
			foreach($aToCheck as $sGrant) {
				if(isset($this->aToken["grants"][$sGrant])) {
					if($sMode=="any") { return true; }
					if($sMode=="none") { return false; }
					$aReturn[$sGrant] = true;
					$bNone = false;
				} else {
					if($sMode=="all") { return false; }
					$aReturn[$sGrant] = false;
				}

			}

			if($sMode=="none") { return $bNone; }
			if($sMode=="all") { return true; }
			if($sMode=="any") { return false; }

			return $aReturn;
		}
	}
}

?>
