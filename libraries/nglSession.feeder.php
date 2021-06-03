<?php

namespace nogal;

/** CLASS {
	"name" : "nglSession",
	"object" : "sess",
	"type" : "main",
	"revision" : "20140621",
	"extends" : "nglTrunk",
	"description" : "
		Gesiona el almacenamiento y recuperación de información asociada con una sesión o varias sesiones.
		Esta clase permite gestionar las sesiones en base de datos o de manera nativa.

		nglSession construye el objeto $session dentro de NOGAL, el cual es accedido a través de: <b>$ngl("sess")->NOMBRE_DEL_METODO()</b>

		Para sessiones en bases de datos
		DROP TABLE IF EXISTS `__ngl_sessions__`;
		CREATE TABLE `__ngl_sessions__` (
			`id` VARCHAR(32) NOT NULL DEFAULT '',
			`expire` INT(11) NOT NULL DEFAULT '0',
			`persistent` ENUM('0', '1') NOT NULL DEFAULT '0',
			`data` BLOB NOT NULL,
			PRIMARY KEY (`id`) 
		);
		CREATE INDEX `expire_idx` ON `__ngl_sessions__` (`expire` DESC);
		CREATE INDEX `persistent_idx` ON `__ngl_sessions__` (`persistent`);


	",
	"variables" : {
		"$sMode" : ["private", "
			Modo en el que trabajará el objeto:
			
			<ul>
				<li><b>db:</b> configura las sesiones en base de datos</li>
				<li><b>fs:</b> modo filesystem, los archivos de sesión se almacenarán en <b>NGL_PATH_SESSIONS</b></li>
				<li><b>php:</b> modo nativo de PHP</li>
			</ul>
		"],
		"$db" : ["private", "Controlador de base de datos"],
		"$sPath" : ["private", "Ruta en la que se guardarán las sesiones cuando el modo sea <b>fs</b>. Por deeccto "],
		"$nLifeTime" : ["int", "Tiempo máximo de vida de una sesión", "session.gc_maxlifetime"]
	}
} **/
class nglSession extends nglTrunk {

	protected $class	= "nglSession";
	protected $me		= "session";
	protected $object	= "session";
	private $sMode		= "php";
	private $db			= null;
	private $sPath		= null;
	private $nLifeTime	= null;

	public function __builder__() {
	}

	public function __init__($mArguments=null) {
	}

	/** FUNCTION {
		"name" : "gc", 
		"type" : "public",
		"description" : "Elimina las sesiones, no persistentes, cuyo tiempo de vida supere el establecido por la variable PHP <b>session.gc_maxlifetime</b>",
		"return" : "boolean"
	} **/
	public function gc($nMaxLifeTime) {
		$nTime = \time();
		if($this->db!==null) {
			$this->db->exec("DELETE FROM `__ngl_sessions__` WHERE `expire` < '".$nTime."' AND `persistent` = '0'");
		} else {
			$aSessions = \glob($this->sPath."sess_*");
			foreach($aSessions as $sSession) {
				if(\file_exists($sSession) && (\filemtime($sSession) + $nMaxLifeTime) < $nTime) {
					\unlink($sSession);
				}
			}
		}
		
		return true;
	}

	/** FUNCTION {
		"name" : "close", 
		"type" : "public",
		"description" : "
			Controlador requerido por PHP para el cierre de las sesiones.
			Este método es requerido por <b>PHP</b> pero carece de utilidad dentro del objeto.
		",
		"return" : "true"
	} **/
	public function close() {
		return true;
	}	

	/** FUNCTION {
		"name" : "count", 
		"type" : "public",
		"description" : "Retorna el número de sesiones activas. Disponible cuando el modo de almacenamiento no sea <b>php</b>",
		"return" : "int"
	} **/
	public function count() {
		if($this->sMode=="db") {
			$sessions = $this->db->query("SELECT * FROM `__ngl_sessions__`");
			return $sessions->rows();
		} else if($this->sMode=="fs") {
			$aSessions = \glob($this->sPath."sess_*");
			$aPersistents = \glob($this->sPath."psess_*");
			
			return (\count($aSessions)+\count($aPersistents));
		}

		self::errorMessage($this->object, "0001");
	}

	/** FUNCTION {
		"name" : "destroy", 
		"type" : "public",
		"description" : "Llamada de retorno ejecutada cuando una sesión es destruida",
		"parameters" : { "$SID" : ["string", "ID de sesion a destruir", "sesion activa"] },
		"return" : "boolean"
	} **/
	public function destroy($SID) {
		if($SID==null) { $SID = session_id(); }
		if($this->sMode=="db") {
			$SID = $this->db->escape($SID);
			$this->db->exec("DELETE FROM `__ngl_sessions__` WHERE `id` = '".$SID."'");
		} else if($this->sMode=="fs") {
			$sFileName = $this->sPath."sess_".$SID;
			if(\file_exists($sFileName)) { \unlink($sFileName); }

			$sFileName = $this->sPath."psess_".$SID;
			if(\file_exists($sFileName)) { \unlink($sFileName); }
		}
		
		return true;
	}

	/** FUNCTION {
		"name" : "destroyAll", 
		"type" : "public",
		"description" : "Destruye todas las sesiones, persistentes o no. Disponible cuando el modo de almacenamiento no sea <b>php</b>",
		"return" : "void"
	} **/
	public function destroyAll() {
		if($this->sMode==="db") {
			$this->db->exec("DELETE FROM `__ngl_sessions__`");
			\session_destroy();
		} else if($this->sMode==="fs") {
			$aSessions = \glob($this->sPath."*sess_*");
			foreach($aSessions as $sSession) {
				if(\file_exists($sSession)) { \unlink($sSession); }
			}
		}

		self::errorMessage($this->object, "0002");
	}

	/** FUNCTION {
		"name" : "GetPersistent", 
		"type" : "private",
		"description" : "Chequea si la sesion <b>$SID</b> es o no una sesion persistente",
		"parameters" : { "$SID" : ["string", "ID de sesion a chequear"] },
		"return" : "boolean"
	} **/
	private function GetPersistent($SID) {
		if($this->sMode==="db") {
			$session = $this->db->query("
				SELECT 
					`persistent` 
				FROM `__ngl_sessions__` 
				WHERE `id` = '".$SID."'
			");		

			if($session->rows()) {
				return self::call()->isTrue($session->get("persistent"));
			}
		} else if($this->sMode==="fs") {
			return \file_exists($this->sPath."psess_".$SID);
		}

		return false;
	}

	/** FUNCTION {
		"name" : "id", 
		"type" : "public",
		"description" : "Retorna el ID de la sesion activa",
		"return" : "string"
	} **/
	public function id($sSessId=null) {
		return ($sSessId!==null) ? \session_id($sSessId) : \session_id();
	}

	/** FUNCTION {
		"name" : "open", 
		"type" : "public",
		"description" : "
			Llamada de retorno que se ejecutada cuando la sesión está siendo abierta.
			Este método es requerido por <b>PHP</b> pero carece de utilidad dentro del objeto.
		",
		"return" : "boolean"
	} **/
	public function open() {
		return true;
	}

	/** FUNCTION {
		"name" : "persistent", 
		"type" : "public",
		"description" : "Chequea si la sesion <b>$SID</b> es o no una sesion persistente",
		"parameters" : {
			"$bPersistent" : ["boolean", "Indica si la sesion <b>$SID</b> es una sesion persistente", "sesion activa"],
			"$SID" : ["string", "ID de sesion a chequear", "sesion activa"]
		},
		"return" : "boolean"
	} **/
	public function persistent($bPersistent=true, $SID=null) {
		if($SID==null) { $SID = \session_id(); }
		if($this->GetPersistent($SID)===false) { $this->write($SID, array()); }
		$nPersistent = (self::call()->istrue($bPersistent)) ? "1" : "0";

		if($this->sMode==="db") {
			$SID = $this->db->escape($SID);
			return $this->db->exec("UPDATE `__ngl_sessions__` SET `persistent` = '".$nPersistent."' WHERE `id` = '".$SID."'");
		} else if($this->sMode==="fs") {
			if($nPersistent==="1" && \file_exists($this->sPath."sess_".$SID)) {
				return \rename($this->sPath."sess_".$SID, $this->sPath."psess_".$SID);
			} else if($nPersistent==="0" && \file_exists($this->sPath."psess_".$SID)) {
				return \rename($this->sPath."psess_".$SID, $this->sPath."sess_".$SID);
			}
			
			return true;
		}

		self::errorMessage($this->object, "0003");
	}

	/** FUNCTION {
		"name" : "read", 
		"type" : "public",
		"description" : "Retorna el contenidos de la sesion en forma de cadena serializada",
		"parameters" : { "$SID" : ["string", "ID de sesion a leer", "sesion activa"] },
		"return" : "boolean"
	} **/
	public function read($SID) {
		if($SID==null) { $SID = \session_id(); }
		$nTime	= \time();
		
		if($this->sMode==="db") {
			$session = $this->db->query("
				SELECT 
					`data` 
				FROM `__ngl_sessions__` 
				WHERE 
					`id` = '".$SID."' AND 
					`expire` > '".$nTime."'
			");

			if($session->rows()) {
				return $session->get("data");
			} 
		} else if($this->sMode==="fs") {
			if(\file_exists($this->sPath."psess_".$SID)) {
				return \file_get_contents($this->sPath."psess_".$SID);
			} else if(\file_exists($this->sPath."sess_".$SID)) {
				return \file_get_contents($this->sPath."sess_".$SID);
			}
		}

		return "";
	}

	/** FUNCTION {
		"name" : "showSessions", 
		"type" : "public",
		"description" : "
			Retorna listado completo de las sesiones activas. 
			Cuando el objeto este configurado en modo <b>fs</b> retornara el listado de archivos de sesion.
		",
		"return" : "array"
	} **/
	public function showSessions() {
		if($this->sMode==="db") {
			$query = $this->db->query("SELECT * FROM `__ngl_sessions__`");
			return $query->toArray();
		} else if($this->sMode==="fs") {
			return \glob($this->sPath."*sess_*");
		}

		self::errorMessage($this->object, "0004");
	}

	/** FUNCTION {
		"name" : "start", 
		"type" : "public",
		"description" : "Da inicio al objeto. Configura el modo de sesión y el tiempo máximo de vida de las mismas",
		"parameters" : {
			"$handler" : ["mixed", "
				Determina el modo en el cual trabajarán las sesiones:
				<ul>
					<li><b>objecto de DB:</b> configura las sesiones en base de datos</li>
					<li><b>string fs:</b> modo filesystem, los archivos de sesión se almacenarán en <b>NGL_PATH_SESSIONS</b></li>
					<li><b>null:</b> modo nativo de PHP</li>
				</ul>
			", "null"],
			"$nLifeTime" : ["int", "Tiempo máximo de vida de una sesión", "session.gc_maxlifetime"]
		},
		"return" : "void"
	} **/
	public function start($handler=null, $nLifeTime=null) {
		if(!$nLifeTime) { $nLifeTime = \get_cfg_var("session.gc_maxlifetime"); }
		$this->lifeTime = $nLifeTime;
		
		if($handler!==null) {
			if(\is_object($handler)) {
				$this->sMode = "db";
				$this->db = $handler;
			} else if(\is_string($handler)) {
				$this->sMode = "fs";
				$this->sPath = self::call()->clearPath(NGL_PATH_SESSIONS, true);
				if(!\is_dir($this->sPath)) {
					self::errorMessage("nogal", "1010", $this->sPath);
				}
			} else {
				$this->sMode = "php";
			}

			\session_set_save_handler(
				[$this, "open"],
				[$this, "close"],
				[$this, "read"],
				[$this, "write"],
				[$this, "destroy"],
				[$this, "gc"]
			);

			\register_shutdown_function("session_write_close");
			
			// recolector de residuos
			$this->gc($nLifeTime);
		}
		
		// inicio de sesion
		\session_start();
	}

	/** FUNCTION {
		"name" : "write", 
		"type" : "public",
		"description" : "Guarda los datos de la variable superglobal <b>$_SESSION</b> como contenido de la sesión <b>$SID</b>",
		"parameters" : {
			"$SID" : ["string", "ID de sesion a chequear", "sesion activa"]
			"$vSessionData" : ["mixed", "Datos de la sesión activa", "sesion activa"],
		},
		"return" : "boolean"
	} **/
	public function write($SID, $vSessionData) {
		$nTime	= (\time() + $this->nLifeTime);
		$bPersistent = $this->GetPersistent($SID);
		$sData = (\is_array($vSessionData)) ? \serialize($vSessionData) : $vSessionData;

		if($this->sMode==="db") {
			$vSession = [];
			$vSession["id"] 			= $SID;
			$vSession["expire"] 		= $nTime;
			$vSession["persistent"] 	= ($bPersistent) ? "1" : "0";
			$vSession["data"] 			= $sData;

			$this->db->table = "__ngl_sessions__";
			$vSession = $this->db->escape($vSession);
			$this->db->insert_mode = "REPLACE";
			$query = $this->db->insert("__ngl_sessions__", $vSession);

			return ($query->rows()) ? true : false;
		} else if($this->sMode==="fs") {
			if($bPersistent) {
				return \file_put_contents($this->sPath."psess_".$SID, $sData);
			} else {
				return \file_put_contents($this->sPath."sess_".$SID, $sData);
			}
		}
		
		return true;
	}

	/** FUNCTION {
		"name" : "sqlcreate", 
		"type" : "public",
		"description" : "Retorna la sentencia SQL para crear la tabla de sessiones",
		"return" : "string"
	} **/
	public function sqlcreate() {
		return <<<SQL
DROP TABLE IF EXISTS `__ngl_sessions__`;
CREATE TABLE `__ngl_sessions__` (
	`id` VARCHAR(32) NOT NULL DEFAULT '',
	`expire` INT(11) NOT NULL DEFAULT '0',
	`persistent` ENUM('0', '1') NOT NULL DEFAULT '0',
	`data` BLOB NOT NULL,
	PRIMARY KEY (`id`) 
);
CREATE INDEX `expire_idx` ON `__ngl_sessions__` (`expire` DESC);
CREATE INDEX `persistent_idx` ON `__ngl_sessions__` (`persistent`);
SQL;
	}
}

?>