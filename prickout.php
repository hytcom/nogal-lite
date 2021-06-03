<?php \defined("NGL_SOWED") || exit();

if(NGL_TERMINAL) { $_SERVER["REDIRECT_URL"] = $argv[1]; }
if(isset($_SERVER["REDIRECT_URL"]) || isset($_SERVER["REDIRECT_SCRIPT_URL"])) {
	
	//	chequeo del referer
	if(NGL_REFERER && isset($NGL_REFERER_CHECKS)) {
		if($ngl()->inCurrentPath($NGL_REFERER_CHECKS)!==false && $ngl()->inCurrentPath($NGL_REFERER_IGNORES)===false) {
			$ngl()->chkreferer();
		}
	}
	unset($NGL_REFERER_CHECKS,$NGL_REFERER_IGNORES);

	// redirecciones 
	$REDIRECTURL = (isset($_SERVER["REDIRECT_SCRIPT_URL"]) ? $_SERVER["REDIRECT_SCRIPT_URL"] : $_SERVER["REDIRECT_URL"]);
	if(\file_exists(NGL_PATH_PROJECT.NGL_DIR_SLASH."re-prickout.php")) {
		include_once(NGL_PATH_PROJECT.NGL_DIR_SLASH."re-prickout.php");
	}

	// chequeo por once code
	if(NGL_ONCECODE && isset($NGL_ONCECODE_CHECKS)) {
		if($ngl()->inCurrentPath($NGL_ONCECODE_CHECKS)!==false && $ngl()->inCurrentPath($NGL_ONCECODE_IGNORES)===false) {
			$NGL_ONCECODE = (isset($_REQUEST["values"]["NGL_ONCECODE"])) ? $_REQUEST["values"]["NGL_ONCECODE"] : ( isset($_REQUEST["NGL_ONCECODE"]) ? $_REQUEST["NGL_ONCECODE"] : null);
			if($NGL_ONCECODE===null || !$ngl()->once($NGL_ONCECODE)) {
				$ngl()->errorPages(403);
			}
		}
	}
	unset($NGL_ONCECODE_CHECKS,$NGL_ONCECODE_IGNORES,$NGL_ONCECODE);

	// carga del documento
	$PRICKOUT = $ngl()->prickout($REDIRECTURL, NGL_PATH_PRICKOUT);

	if($PRICKOUT[0]!==false) {
		require_once($PRICKOUT[0]);
		exit();
	} else {
		if(\substr($PRICKOUT[1], -1)==NGL_DIR_SLASH) {
			$PRICKOUT = ["dirname"=>$PRICKOUT[1]];
			$PRICKOUT["filename"] = $PRICKOUT["basename"] = "index";
			$PRICKOUT["extension"] = "html";
		} else {
			$PRICKOUT = \pathinfo($PRICKOUT[1]);
		}

		if(\file_exists($PRICKOUT["dirname"].NGL_DIR_SLASH."__container.php")) {
			require_once($PRICKOUT["dirname"].NGL_DIR_SLASH."__container.php");
			exit();
		} else if(\file_exists(NGL_PATH_PRICKOUT.NGL_DIR_SLASH."__container.php")) {
			require_once(NGL_PATH_PRICKOUT.NGL_DIR_SLASH."__container.php");
			exit();
		} else if(\file_exists($PRICKOUT["dirname"].NGL_DIR_SLASH.$PRICKOUT["basename"])) {
			require_once($PRICKOUT[1]);
			exit();
		}

		$ngl()->errorPages(404);
	}

} else {
	// acceso denegado
	$ngl()->errorPages(403);
}

?>