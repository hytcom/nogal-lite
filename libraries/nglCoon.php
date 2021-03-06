<?php
/*
# Nogal
*the most simple PHP Framework* by hytcom.net
GitHub @hytcom
___
  
# mysql
## nglCoon *extends* nglBranch *implements* inglBranch
Cliente/Servidor de peticiones REST

https://github.com/hytcom/wiki/blob/master/nogal/docs/coon.md

*/
namespace nogal;

class nglCoon extends nglBranch implements inglBranch {

	private $aHeaders;

	final protected function __declareArguments__() {
		$vArguments							= [];
		$vArguments["apiname"]				= ['$mValue', "nogalcoon"];
		$vArguments["auth"]					= ['$mValue', null]; // basic | bearer | alvin
		$vArguments["bodyauth"]				= ['$mValue', false]; // solo en metodos POST
		$vArguments["ctype"]				= ['(string)$mValue', "json"]; // csv | json | text | xml
		$vArguments["data"]					= ['$mValue'];
		$vArguments["header"]				= ['$mValue', null];
		$vArguments["key"]					= ['$this->SetKey($mValue)', "*sTr0N6k3Y@"];
		$vArguments["method"]				= ['(string)$mValue', "POST"];
		$vArguments["port"]					= ['$mValue', null];
		$vArguments["sslverify"]			= ['self::call()->isTrue($mValue)', false];
		$vArguments["token"]				= ['$mValue', null];
		$vArguments["url"]					= ['(string)$mValue', null];
		return $vArguments;
	}

	final protected function __declareAttributes__() {
		$vAttributes = [];
		return $vAttributes;
	}

	final protected function __declareVariables__() {
	}

	final public function __init__() {
		$this->aHeaders = [];
	}


	public function request() {
		list($mData,$sURL,$sToken,$sCType) = $this->getarguments("data,url,token,ctype", \func_get_args());
		$sMethod = \strtoupper($this->method);
		$sAuth = $this->auth." ".$sToken;
		$sCType = strtolower($sCType);
		$mContent = "";

		switch($sCType) {
			case "json":
				$sContentType = "application/json";
				if($sMethod=="POST") {
					if($this->bodyauth) {
						if(!\is_array($mData)) { $mData = \json_decode($mData); }
						$mData["NGL-REQUEST-AUTHORIZATION"] = $sAuth;
					}
					$mContent = (\is_array($mData)) ? \json_encode($mData, JSON_HEX_APOS) : $mData;
				} else {
					$mContent = (!\is_array($mData)) ? \json_decode($mData, true) : $mData;
				}
				break;

			case "xml":
				$sContentType = "application/xml";
				if($sMethod=="POST") {
					if($this->bodyauth) {
						if(!\is_array($mData)) { $mData = self::call("shift")->convert($mData, "xml-array"); }
						$mData["NGL-REQUEST-AUTHORIZATION"] = $sAuth;
					}
					$mContent = (\is_array($mData)) ? self::call("shift")->convert($mData, "array-xml") : $mData;
				} else {
					$mContent = (!\is_array($mData)) ? self::call("shift")->convert($mData, "xml-array") : $mData;
				}
				$sCType = "xml";
				break;

			case "csv":
				$sContentType = "text/csv";
				if($sMethod=="POST") {
					if($this->bodyauth) {
						if(!\is_array($mData)) { $mData = self::call("shift")->convert($mData, "csv-array"); }
						$mData["NGL-REQUEST-AUTHORIZATION"] = $sAuth;
					}
					$mContent = (\is_array($mData)) ? self::call("shift")->convert($mData, "array-csv") : $mData;
				} else {
					$mContent = (!\is_array($mData)) ? self::call("shift")->convert($mData, "csv-array") : $mData;
				}
				break;

			case "text":
				$sContentType = "text/plain";
				if($sMethod=="POST") {
					if($this->bodyauth) {
						if(!\is_array($mData)) {
							$sToParse = $mData;
							\parse_str($sToParse, $mData);
						}
						$mData["NGL-REQUEST-AUTHORIZATION"] = $sAuth;
					}
					$mContent = (\is_array($mData)) ? \http_build_query($mData) : $mData;
				} else {
					$mContent = $mData;
					if(!\is_array($mData)) { \parse_str($mData, $mContent); }
				}
				break;
		}

		$sBuffer = "REQUEST ERROR: Bad Request";
		if(self::call()->isURL($sURL) && \function_exists("curl_init")) {
			$aHeaders = ["Content-Type: ".$sContentType];
			if($sAuth!==null) { $aHeaders[] = "Authorization: ".$sAuth; }
			if($sMethod=="GET" && !empty($mContent)) {
				$url = self::call("url")->load($sURL);
				$sURL = $url->update("params", $mContent)->get();
			}

			$curl = \curl_init($sURL);
			if($this->port!==null) { \curl_setopt($curl, CURLOPT_PORT, $this->port); }
			\curl_setopt($curl, CURLOPT_HEADER, false);
			\curl_setopt($curl, CURLOPT_HTTPHEADER, $aHeaders); 
			\curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			\curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			\curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->sslverify);
			\curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $this->sslverify);

			if($sMethod=="POST") {
				\curl_setopt($curl, CURLOPT_POSTFIELDS, $mContent);
				\curl_setopt($curl, CURLOPT_POST, 1);
			}
			
			$sBuffer = \curl_exec($curl); 
			if(\curl_errno($curl)) { $sBuffer = "REQUEST ERROR curl: ".\curl_error($curl); }
			\curl_close($curl);
		}

		return $sBuffer;
	}

	public function getrequest() {
		$aHeaders		= self::call()->getheaders();
		$aRequest		= $_REQUEST["source"];
		$mBody			= $sInput = \file_get_contents("php://input");
		$aSelf			= self::call("sysvar")->SELF;

		$sCType = $this->ctype;
		$sContentType = (isset($aHeaders["content-type"])) ? $aHeaders["content-type"] : $sCType;
		switch($sContentType) {
			case "json":
			case "application/json":
				$mBody = \json_decode($sInput, true);
				$sCType = "json";
				break;

			case "xml":
			case "application/xhtml+xml":
			case "application/xml":
			case "text/xml":
				$mBody = self::call("shift")->convert($sInput, "xml-array");
				$sCType = "xml";
				break;

			case "csv":
			case "text/csv":
				$mBody = self::call("shift")->convert($sInput, "csv-array");
				$sCType = "csv";
				break;

			case "text":
			case "text/plain":
			case "text/html":
				$mBody = $sInput;
				$sCType = "text";
				break;
		}

		if($this->ctype===null) { $this->args("ctype", $sCType); }

		if($this->bodyauth) { $aHeaders["authorization"] = $mBody["NGL-REQUEST-AUTHORIZATION"]; }
		if(isset($aHeaders["authorization"])) {
			$aAuth = \explode(" ", $aHeaders["authorization"], 2);
			$sAuthMethod = \strtolower($aAuth[0]);
			if($sAuthMethod=="basic") {
				$sAuth = \base64_decode($aAuth[1]);
				$mAuth = (\strpos($sAuth, ":")) ? \explode(":", $sAuth, 2) : $sAuth;
			} else if($sAuthMethod=="bearer") {
				$mAuth = $aAuth[1];
			} else if($sAuthMethod=="alvin") {
				$sToken = $this->tokenDecode($aAuth[1]);
				if($sToken!==false) {
					$mAuth = $sToken;
					$this->args("token", $sToken);
				} else {
					$mAuth = false;
				}
			}
		}
	
		$aReturn = [];
		if(isset($mAuth)) { $aReturn["auth"] = $mAuth; }
		$aReturn["path"]	= $aSelf;
		$aReturn["headers"]	= $aHeaders;
		$aReturn["request"]	= $aRequest;
		$aReturn["body"]	= $mBody;

		return $aReturn;
	}

	public function setheader() {
		list($mHeader = $this->getarguments("header", func_get_args());
		$aHeader = \is_array($mHeader) ? $mHeader : sefl::call()->explodeTrim(":", $mHeader, 2);
		$this->aHeaders[$aHeader[0]] = (\count($aHeader)>1) ? $aHeader[1] : "";
		return $this;
	}

	public function response() {
		list($aData,$sToken,$sCType) = $this->getarguments("data,token,ctype", func_get_args());
		if(!in_array($sCType, ["csv","json","text","xml"])) { $sCType = "json"; }

		foreach($this->aHeaders as $sHeader => $mValue) {
			\header($sHeader":".$mValue, true);
		}

		if($sCType=="json" || $sCType=="xml") {
			$aResponse = [];
			$aResponse["api"]			= $this->apiname;
			$aResponse["timestamp"]		= \time();
			$aResponse["datetime"]		= \date("Y-m-d H:i:s", $aResponse["timestamp"]);
			if($sToken!==null) {
				$aResponse["token"] 	= self::call()->tokenEncode($sToken, $this->key, false);
			}
			$aResponse["count"]			= (\is_array($aData)) ? \count($aData) : 0;
			$aResponse["data"]			= $aData;

			\header("Content-Type: application/".$sCType, true);
			return self::call("shift")->convert($aResponse, "array-".$sCType);
		} else if($sCType=="csv") {
			\header("Content-Type: text/csv", true);
			return self::call("shift")->convert($aData, "array-csv");
		} else {
			\header("Content-Type: text/plain", true);
			return (\is_array($aData)) ? self::call()->imploder(["\t", "\n"], $aData) : $aData;
		}
	}

	public function tokenEncode() {
		list($sToken) = $this->getarguments("token", func_get_args());
		return self::call()->tokenEncode($sToken, $this->key, "ALVIN TOKEN");
	}

	public function tokenDecode() {
		list($sToken) = $this->getarguments("token", func_get_args());
		return self::call()->tokenDecode($sToken, $this->key);
	}

	protected function SetKey($sKey) {
		if(!empty($sKey) {
			return $sKey;
		} else {
			if(NGL_ALVIN!==true) {
				return NGL_ALVIN;
			} else if(\file_exists(NGL_PATH_DATA."/alvin/public.key") && NGL_ALVIN!==null) {
				return \file_get_contents(NGL_PATH_DATA."/alvin/public.key");
			} else {
				return "*sTr0N6k3Y@";
			}
		}
		return $sKey;
	}
}

?>