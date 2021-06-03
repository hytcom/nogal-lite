<?php

namespace nogal;

class nglScion extends nglBranch implements inglBranch {

	public function __init__() {
	}

	// grafts composer autoload
	final public function __vendor__() {
		if(\version_compare(PHP_VERSION, "5.4") > 0) {
			if(\file_exists(self::path("grafts").NGL_DIR_SLASH."composer".NGL_DIR_SLASH."vendor".NGL_DIR_SLASH."autoload.php")) {
				require_once(self::path("grafts").NGL_DIR_SLASH."composer".NGL_DIR_SLASH."vendor".NGL_DIR_SLASH."autoload.php");
			}
		}
	}
}

?>