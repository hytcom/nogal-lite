<?php

namespace nogal;


/*
ACTIVAR GMAIL 


Take the step mentioned earlier. Log into your google email account and then go to this link: https://www.google.com/settings/security/lesssecureapps and set "Access for less secure apps" to ON. Test to see if your issue is resolved. If it isn't resolved, as it wasn't for me, continue to Step #2.

Go to https://support.google.com/accounts/answer/6009563 (Titled: "Password incorrect error"). This page says "There are several reasons why you might see a “Password incorrect” error (aka 534-5.7.14) when signing in to Google using third-party apps. In some cases even if you type your password correctly." This page gives 4 suggestions of things to try.

For me, the first suggestion worked:
Go to https://g.co/allowaccess from a different device you have previously used to access your Google account and follow the instructions.
Try signing in again from the blocked app.

$mail = $ngl("mail");
if($s=="imap") {
	$mail->server	= "imap";
	$mail->host		= "mail.dominio.com"; 	//"mail.dominio.com";	//"smtp.gmail.com";
	$mail->secure	= "ssl";				//"tls"; // ssl | tls
	$mail->port		= 993;					// 465 | 587
	$mail->user		= "foobar@dominio.com";
	$mail->pass		= 'asd123';
} else if($s=="pop3") {
	$mail->server	= "pop3";
	$mail->host		= "mail.dominio.com"; 	//"mail.dominio.com";	//"smtp.gmail.com";
	$mail->secure	= false;				//"tls"; // ssl | tls
	$mail->port		= 110;					// 465 | 587
	$mail->user		= "foobar@dominio.com";
	$mail->pass		= 'asd123';
} else {
	$mail->server	= "smtp";
	$mail->host		= "mail.dominio.com"; 	//"mail.dominio.com";	//"smtp.gmail.com";
	$mail->secure	= false;				//"tls"; // ssl | tls
	$mail->port		= 26;					// 465 | 587
	$mail->user		= "foobar@dominio.com";
	$mail->pass		= $ngl()->passwd('asd123');
}

print_r($mail
	->connect()
	->login()
	// ->mailbox("inbox")
	// ->headers(1,"subject")
	// ->getAll('SUBJECT "Aviso de transferencia"')
	// ->getAll()
	// ->getraw(5)
	// ->get(6)
	// ->headers(29,"X-Authority-Analysis")
	// ->headers(29,"From")
	
	->from("Ariel Bottero <".$mail->user.">")
	->name()
	->subject("prueba")
	->message("sigo probando")
	// ->message("hola!!!!!!!! <img src='cid:achu' /> probandooooo!!!")
	// ->image("tmp/achu.jpg", "achu")
	// ->attach("tmp/fantasydragon.jpg")
	->send("foobar@dominio.com")
);


// echo $mail->log;

*/


/** CLASS {
	"name" : "nglMail",
	"object" : "mail",
	"type" : "instanciable",
	"revision" : "20160201",
	"extends" : "nglBranch",
	"interfaces" : "iNglClient",
	"description" : "
		Este objeto permite gestionar operaciones sobre los servidores de correo. Recibiendo y enviando mensajes.
		Entre las funciones del objecto se encuentran:
			<ul>
				<li>Revisar correos IMAP</li>
				<li>Revisar correos POP3</li>
				<li>Enviar correos via SMTP</li>
			</ul>
	",
	"configfile" : "mail.conf",
	"variables" : {
		"$sBoundary" : ["private", "Separador de contenidos"],
		"$aAttachments" : ["private", "Array con los archivos adjuntos del correo a enviar"],
		"$socket" : ["private", "Puntero de la conexión con el servidor"],
		"$sCRLF" : ["private", "Caracteres del salto de línea"],
		"$nCRLF" : ["private", "Longuitud del salto de línea"],
		"$sTag" : ["private", "Tag antivo en las conexiones IMAP"],
		"$sServerType" : ["private", "Tipo de Servidor: smtp | imap | pop3"],
		"$sSMTPUsername" : ["private", "Nombre de usuario SMTP"],
		"$sSMTPPassword" : ["private", "Contraseña SMTP"]
	},
	"arguments": {
		"from" : ["string", "Dirección de correo saliente"],
		"name" : ["string", "Nombre asociado a la dirección de correo saliente", "null"],
		"reply" : ["string", "Dirección de correo de respuesta", "null"],
		"reply_name" : ["string", "Nombre asociado a la dirección de correo de respuesta", "null"],
		"to" : ["string", "Correos de destino, separados cualquier caracter NO válido en una dirección de correo (espacio, coma, punto y coma, etc)", "null"],
		"cc" : ["string", "Direcciones en copia, separados cualquier caracter NO válido en una dirección de correo (espacio, coma, punto y coma, etc)", "null"],
		"bcc" : ["string", "Direcciones en copia oculta, separados cualquier caracter NO válido en una dirección de correo (espacio, coma, punto y coma, etc)", "null"],
		
		"subject" : ["string", "Asunto del correo a enviar", "null"],
		"message" : ["string", "Cuerpo del correo", "null"],
		"attach" : ["string", "Ruta de un archivo adjunto", "null"],
		"attach_name" : ["string", "Nombre que llevará el archivo adjunto <b>argument::attach</b>", "null"],

		"notify" : ["string", "Dirección del acuse de recibo", "null"],
		"priority" : ["int", "Prioridad del correo a enviar, de 1 a 5", "null"],
		"charset" : ["string", "Codificación de caracteres del correo a enviar", "UTF-8"],
		"timediff" : ["string", "Diferencia horaria entre el cliente y el servidor SMTP", "0 hours"],

		"server" : ["string", "Tipo de servidor al que se realizará la conexión: smtp | imap | pop3", "smtp"],
		"host" : ["string", "Dominio o IP del servidor"],
		"secure" : ["string", "Protocolo de seguridad del servidor: SSL, TLS o NULL", "null"],
		"port" : ["int", "Puerto del servidor"],
		"user" : ["string", "Nombre de usuario en el servidor"],
		"pass" : ["string", "Contraseña del servidor"],
		"timeout" : ["int", "Tiempo de espera del servidor", 20],

		"mailbox" : ["string", "Nombre de la carpeta activa", "INBOX"],
		"mail" : ["int", "Id del correo activo", "null"],
		"fields" : ["string", "Nombres, separados por espacios, de los campos del correo activo", "INBOX"],
		"limit" : ["int", "limite de mail del metodo getall"],

		"smtp_authtype" : ["string", "Método de autenticación: CRAM-MD5 | LOGIN | PLAIN", "null"],
		"localhost" : ["string", "Nombre del servidor local", "localhost"]
	},
	"attributes": {
		"mail_headers" : ["string", "Cabeceras del correo a enviar"],
		"mail_body" : ["string", "Cuerpo del correo a enviar"],
		"mail_from" : ["string", "Dirección del remitente del correo a enviar"],
		"mail_name" : ["string", "Nombre del remitente del correo a enviar"],
		"mail_reply" : ["string", "Dirección de respuesta del correo a enviar"],
		"mail_to" : ["string", "Destinatarios del correo a enviar"],
		"mail_cc" : ["string", "Destinatarios de las copias del correo a enviar"],
		"mail_bcc" : ["string", "Destinatarios de las copias ocultas del correo a enviar"],
		"mail_subject" : ["string", "Asunto del correo a enviar"],
		"mail_text" : ["string", "Contenido en texto plano del correo a enviar"],
		"mail_text" : ["string", "Contenido HTML del correo a enviar"],
		"mail_priority" : ["int", "Prioridad del correo a enviar"],
		"mail_notify" : ["int", "Dirección de correo para la confirmación de lectura"],


		$vAttributes["state"]			= null;
		$vAttributes["log"]				= null;

		$vAttributes["exists"]			= null;
		$vAttributes["recent"]			= null;

	}
} **/
class nglMail extends nglBranch implements iNglClient {

	private $sBoundary;
	private $aSMTP;
	private $aAttachments;
	private $socket;
	private $sCRLF;
	private $nCRLF;
	private $sTag;
	private $sServerType;
	private $sSMTPUsername;
	private $sSMTPPassword;
	private $nSMTPMaxSize;
	private $sRegexMail;
	private $sContentKey;
	private $bLogged;

	final protected function __declareArguments__() {
		$vArguments								= [];
		$vArguments["attach"]					= ['$mValue', null];
		$vArguments["attach_content"]			= ['$mValue', null];
		$vArguments["attach_name"]				= ['$mValue', null];
		$vArguments["attachs_ignore"]			= ['self::call()->isTrue($mValue)', false];
		$vArguments["bcc"]						= ['$mValue', null];
		$vArguments["cc"]						= ['$mValue', null];
		$vArguments["charset"]					= ['(string)$mValue', "UTF-8"]; /* (UTF-8 | iso-8859-1) */
		$vArguments["fields"]					= ['$mValue', null]; /* separados por espacios */
		$vArguments["flags"]					= ['$mValue', null]; /* separadas por espacios y sin la \ */
		$vArguments["folder"]					= ['$mValue', "INBOX"];
		$vArguments["from"]						= ['$this->MailAddress((string)$mValue, "from")'];
		$vArguments["get_mode"]					= ['$mValue', "headers"]; // headers | all | keys
		$vArguments["headers"]					= ['$mValue', "DATE FROM SUBJECT"];
		$vArguments["host"]						= ['$mValue'];
		$vArguments["inreplyto"]				= ['$mValue', null]; /* id del mail al que se esta respondiendo */
		$vArguments["limit"]					= ['(int)$mValue', 25];
		$vArguments["localhost"]				= ['$mValue', "localhost"];
		$vArguments["mail"]						= ['$mValue', null]; // id o ids separados por ,
		$vArguments["message"]					= ['$this->MailMessage((string)$mValue)', null];
		$vArguments["notify"]					= ['$this->MailNotify((string)$mValue)', null];
		$vArguments["pass"]						= ['$mValue'];
		$vArguments["peek"]						= ['self::call()->isTrue($mValue)', false]; // vuelve a marcar como no leido luego de leer
		$vArguments["port"]						= ['(int)$mValue'];
		$vArguments["priority"]					= ['$this->MailPriority((string)$mValue)', null];
		$vArguments["references"]				= ['$mValue', null]; /* referencia original del mail al que se esta respondiendo, sin el id del mismo */
		$vArguments["reply"]					= ['$this->MailAddress((string)$mValue, "reply")', null];
		$vArguments["revert"]					= ['self::call()->isTrue($mValue)', true];
		$vArguments["secure"]					= ['(string)$mValue', null];
		$vArguments["server"]					= ['$mValue', "smtp"]; /* smtp | imap | pop3 */
		$vArguments["smtp_authtype"]			= ['$mValue', null]; /* "(CRAM-MD5 | LOGIN | PLAIN)" */
		$vArguments["subject"]					= ['$this->MailSubject((string)$mValue)', null];
		$vArguments["timediff"]					= ['$mValue', "0 hours"];
		$vArguments["timeout"]					= ['(int)$mValue', "20"];
		$vArguments["to"]						= ['$mValue', null];
		$vArguments["user"]						= ['$mValue'];

		return $vArguments;
	}

	final protected function __declareAttributes__() {
		$vAttributes						= [];
		$vAttributes["mail_headers"]		= null;
		$vAttributes["mail_body"]			= null;

		$vAttributes["mail_from"]			= null;
		$vAttributes["mail_from_mail"]		= null;
		$vAttributes["mail_reply"]			= null;
		$vAttributes["mail_to"]				= null;
		$vAttributes["mail_cc"]				= null;
		$vAttributes["mail_bcc"]			= null;
		
		$vAttributes["mail_subject"]		= null;
		$vAttributes["mail_text"]			= null;
		$vAttributes["mail_html"]			= null;
		$vAttributes["mail_priority"]		= null;
		$vAttributes["mail_notify"]			= null;
		
		$vAttributes["getted"]				= null;
		$vAttributes["getted_id"]			= null;
		$vAttributes["getted_keys"]			= null;
		$vAttributes["currret_mailbox"]		= null;

		$vAttributes["state"]				= null;
		$vAttributes["log"]					= null;

		$vAttributes["exists"]				= null;
		$vAttributes["recent"]				= null;
		
		return $vAttributes;
	}
	

	/** FUNCTION {
	__init__: Ejecuta las configuraciones básicas del objeto.
	**/
	final protected function __declareVariables__() {
		$this->sBoundary = "NGLBOUNDARY".self::call()->unique(16);
		$this->aAttachments = [];
		$this->socket = null;
		$this->sCRLF = "\r\n";
		$this->nCRLF = \strlen($this->sCRLF);
		$this->sSMTPUsername = null;
		$this->sSMTPPassword = null;
		$this->nSMTPMaxSize = null;
		$this->sRegexMail = self::call("sysvar")->REGEX["email"];
		$this->sContentKey = self::call()->unique();

		$this->attribute("state", "DISCONNECTED");
	}

	final public function __init__() {
		$this->bLogged = false;
	}

	public function attach() {
		list($sSource, $sName) = $this->getarguments("attach,attach_name", \func_get_args());

		if($sName==null) { $sName = $sSource; }
		$this->aAttachments[] = [$sSource, $sName];

		return $this;
	}

	public function attachContent() {
		list($sSource, $sName) = $this->getarguments("attach_content,attach_name", \func_get_args());

		if($sName==null) { $sName = self::call()->unique().".txt"; }
		$this->aAttachments[] = [$this->sContentKey, $sName, $sSource];

		return $this;
	}

	private function AuthCramMD5() {
		$vResponse = $this->request("AUTH CRAM-MD5");
		if($vResponse["code"]!=334) { return false; }

		// username - password
		$sHMAC = \hash_hmac("MD5", $this->sSMTPPassword, $vResponse["text"]);
		$vResponse = $this->request(\base64_encode($this->sSMTPUsername." ".$sHMAC));
		if($vResponse["code"]!=235) { return false; }
		
		return true;
	}

	private function AuthLogin() {
		$vResponse = $this->request("AUTH LOGIN");
		if($vResponse["code"]!=334) { return false; }

		// username
		$vResponse = $this->request(\base64_encode($this->sSMTPUsername));
		if($vResponse["code"]!=334) { return false; }

		// password
		$vResponse = $this->request(\base64_encode($this->sSMTPPassword));
		if($vResponse["code"]!=235) { return false; }
		
		return true;
	}

	private function AuthPlain() {
		$vResponse = $this->request("AUTH PLAIN");
		if($vResponse["code"]!=334) { return false; }

		// username - password
		$vResponse = $this->request(\base64_encode("\0".$this->sSMTPUsername."\0".$this->sSMTPPassword));
		if($vResponse["code"]!=235) { return false; }
		
		return true;
	}

	private function BuildMail() {
		$sFrom			= $this->attribute("mail_from");
		$sFromMail		= $this->attribute("mail_from_mail");
		$sReplyTo		= $this->attribute("mail_reply");
		$sNotify		= $this->attribute("mail_notify");
		$nPriority		= $this->attribute("mail_priority");
		$aCC			= $this->attribute("mail_cc");
		$sSubject		= $this->attribute("mail_subject");
		$sEncoding 		= (\strtolower($this->charset)!="us-ascii") ? "8bit" : "7bit";
		$sTime			= \strtotime($this->timediff);

		if($sReplyTo===null) { $sReplyTo = $sFrom; }
		
		// ENCABEZADOS
		$sHeaders	 = "Date: ".\date("l j F Y, G:i", $sTime).$this->sCRLF;
		$sHeaders	.= "MIME-Version: 1.0".$this->sCRLF;
		$sHeaders	.= "From: ".$sFrom.$this->sCRLF;
		$sHeaders	.= "Reply-To: ".$sReplyTo.$this->sCRLF;
		$sHeaders	.= "To: {{MAILTO}}".$this->sCRLF;
		$sHeaders	.= "Cc: ".\implode(",", $aCC).$this->sCRLF;

		// respuesta a un mail
		if(!empty($this->inreplyto)) {
			$sReferences = $this->references;
			$sReferences = empty($sReferences) ? $this->inreplyto : $sReferences." ".$this->inreplyto;
			$sHeaders	.= "References: ".$sReferences.$this->sCRLF;
			$sHeaders	.= "In-Reply-To: ".$this->inreplyto.$this->sCRLF;
			if(\substr($sSubject,0,4)!="Re: ") { $sSubject = "Re: ".$sSubject; }
		}

		$sHeaders	.= "Subject: ".$sSubject.$this->sCRLF;
		$sHeaders	.= "Return-Path: <".$sFromMail.">".$this->sCRLF;
		$sHeaders	.= "Return-Path: <".$sFromMail.">".$this->sCRLF;
		
		// acuse de recibo
		if($sNotify!==null) {
			$sHeaders .= "Disposition-Notification-To: ".$sNotify.$this->sCRLF;
		}
		
		// prioridad
		if($nPriority!==null) {
			$sHeaders .= "X-Priority: ".$nPriority.$this->sCRLF;
		}

		// nogal id
		$sHeaders	.= "NGL-Message-ID: {{MSGID}}".$this->sCRLF;

		// tipo de contenido
		$sHeaders 	.= "Content-Type: multipart/mixed; boundary=\"".$this->sBoundary."MIX\"".$this->sCRLF;
		
		// contenido
		$sTextMessage = $this->attribute("mail_text");
		$sHTMLMessage = $this->attribute("mail_html");
		
		// MENSAJE
		$sBody	 = "--".$this->sBoundary."MIX".$this->sCRLF;
		$sBody	.= "Content-Type: multipart/alternative; boundary=\"".$this->sBoundary."ALT\"".$this->sCRLF.$this->sCRLF;

		$sBody	.= "--".$this->sBoundary."ALT".$this->sCRLF;
		$sBody	.= "Content-Type: text/plain; charset=".$this->charset.$this->sCRLF;
		$sBody	.= "Content-Transfer-Encoding: ".$sEncoding.$this->sCRLF.$this->sCRLF;
		$sBody	.= $sTextMessage;
		$sBody	.= $this->sCRLF;

		// mensaje HTML
		if($sHTMLMessage!==null) {
			$sBody	.= $this->sCRLF."--".$this->sBoundary."ALT".$this->sCRLF;
			$sBody	.= "Content-Type: text/html; charset=".$this->charset.$this->sCRLF;
			$sBody	.= "Content-Transfer-Encoding: ".$sEncoding.$this->sCRLF.$this->sCRLF;
			$sBody	.= $sHTMLMessage;
			$sBody	.= $this->sCRLF;
		}

		$sBody	.= $this->sCRLF."--".$this->sBoundary."ALT--".$this->sCRLF;

		// inclusion de adjuntos
		$sAttachs = $this->ReadAttachs();
		if(!empty($sAttachs)) {
			$sBody	.= $sAttachs;
		}

		// fin del mensaje
		$sBody	.= $this->sCRLF."--".$this->sBoundary."MIX--".$this->sCRLF.$this->sCRLF;

		$this->attribute("mail_headers", $sHeaders);
		$this->attribute("mail_body", $sBody);
	}

	public function close() {
		$this->Disconnect;
		return $this;
	}

	public function connect() {
		list($sServerType,$sHost,$sSecure,$nPort,$nTimeOut) = $this->getarguments("server,host,secure,port,timeout", \func_get_args());

		$this->sServerType = \strtolower($sServerType);
		
		// hostname
		$vHost = \parse_url($sHost);
		$sSecure = \strtolower($sSecure);
		if($sSecure=="ssl") {
			$sHost = "ssl://".((!isset($vHost["scheme"])) ? $sHost : $vHost["host"]);
		} else if($sSecure=="tls") {
			$sHost = (!isset($vHost["scheme"])) ? $sHost : $vHost["host"];
		}

		$this->Logger("<< ".$sHost.":".$nPort.$this->sCRLF);
		$this->socket = @\fsockopen($sHost, $nPort, $nError, $sError, $nTimeOut);

		if($this->socket===false) {
			$this->Logger("-- \t\tERROR #".$nError." (".$sError.")".$this->sCRLF);
			return false;
		} else {
			if($this->sServerType=="smtp") { $this->aSMTP = array($sHost, $nPort, $nTimeOut); }
			$vResponse = $this->Response("* OK");
		}
		
		$this->attribute("state", "CONNECTED");
		return $this;
	}

	public function count() {
		return $this->attribute("exists");
	}

	private function DecodingType($sContent, $sType="quoted-printable") {
		$sContent = \trim($sContent);
		switch($sType) {
			case "quoted-printable": $sContent = \quoted_printable_decode($sContent); break;
			case "base64": $sContent = \base64_decode($sContent); break;
		}

		// codificacion a UTF-8
		$sEncoding = self::call()->encoding($sContent);
		if($sEncoding===false || \strtolower($sEncoding)=="utf-8") {
			return $sContent;
		} else {
			return \iconv($sEncoding, "UTF-8//TRANSLIT", $sContent);
		}
	}

	private function Disconnect() {
		$this->Logger("-- Connection closed");
		$this->attribute("state", "CLOSED");
		if($this->sServerType=="smtp") { $this->request("QUIT"); }
		@fclose($this->socket);
		return false;
	}

	private function Fetch($sMailsId, $sMessagesFilter="ALL") {
		if($this->attribute("state")=="SELECTED") {
			if($this->sServerType=="imap") {
				$vResponse = $this->request("FETCH ".$sMailsId." (".$sMessagesFilter.")");
				$aMessages = false;
				if($vResponse["text"][0]=="*") {
					$aMessages = [];
					
					$sResponse = \substr($vResponse["text"], 0, \strpos($vResponse["text"], $this->Tag()));
					$aResponse = \explode($this->sCRLF, $sResponse);

					// inicio mensajes
					$sMessageData = \array_shift($aResponse);
					$sMessage = \substr($sMessageData, \strrpos($sMessageData, "}")+1);

					// mensajes
					foreach($aResponse as $sLine) {
						if(preg_match("/^\*[ 0-9]+FETCH.*\}/", $sLine)) {
							$nId = (int)\substr($sMessageData, 1, \strrpos($sMessageData, " FETCH"));
							$nIni = \strrpos($sMessageData, "{")+1;
							$nEnd = \strrpos($sMessageData, "}");
							$nLength = \substr($sMessageData, $nIni, ($nEnd-$nIni));
							$aMessages[$nId] = \trim(\substr($sMessage, 0, (int)$nLength));

							$sMessageData = $sLine;
							$sMessage = \substr($sMessageData, \strrpos($sMessageData, "}")+1);
							continue;
						}

						$sMessage .= $sLine.$this->sCRLF;
					}

					// fin de los mensajes
					$nId = (int)\substr($sMessageData, 1, \strrpos($sMessageData, " FETCH"));
					$nIni = \strrpos($sMessageData, "{")+1;
					$nEnd = \strrpos($sMessageData, "}");
					$nLength = \substr($sMessageData, $nIni, ($nEnd-$nIni));
					$aMessages[$nId] = \trim(\substr($sMessage, 0, (int)$nLength));
				}

				if($this->peek) { $this->unflag($sMailsId, "seen"); }
				return $aMessages;
			} else {
				$vResponse = $this->request("RETR ".$sMailsId);
				if($this->FindMark($vResponse["text"])) {
					$sResponse = \trim($vResponse["text"]);
					$nIni = \strpos($sResponse, $this->sCRLF) + $this->nCRLF;
					return \substr($sResponse, $nIni, -1);
				}
			}
		}
		
		return false;
	}

	private function FindMark($sResponse, $sMark=null) {
		$sMark = ($sMark!==null) ? $sMark : (($this->sServerType=="imap") ? $this->Tag() : "+OK");
		return (\strpos($sResponse, $sMark)!==false);
	}

	/*
        \Seen
           Message has been read

        \Answered
           Message has been answered

        \Flagged
           Message is "flagged" for urgent/special attention

        \Deleted
           Message is "deleted" for removal by later EXPUNGE

        \Draft
           Message has not completed composition (marked as a draft).

        \Recent
	*/
	public function flag() {
		list($sMailsId, $sFlags) = $this->getarguments("mail,flags", \func_get_args());
		$sMailsId = $this->MailsIds($sMailsId);
		$sFlag = $this->BuildFlags($sFlags);
		$this->request("STORE ".$sMailsId." +FLAGS (\\Seen)");
	}

	public function unflag() {
		list($sMailsId, $sFlags) = $this->getarguments("mail,flags", \func_get_args());
		$sMailsId = $this->MailsIds($sMailsId);
		$sFlag = $this->BuildFlags($sFlags);
		$this->request("STORE ".$sMailsId." -FLAGS (\\Seen)");
	}
	
	private function BuildFlags($sFlags) {
		$aFlags = \explode(" ", $sFlags);
		foreach($aFlags as &$sFlag) {
			$sFlag = \strtolower($sFlag);
			$sFlag = \preg_replace("/[^a-z]/", "", $sFlag);
			$sFlag = "\\".\ucfirst($sFlag);
		}
		return \implode(" ", $aFlags);
	}

	public function get() {
		if($this->bLogged) { $this->connect()->login()->mailbox($this->attribute("currret_mailbox")); }
		list($nMailId,$sGetMode) = $this->getarguments("mail,get_mode", \func_get_args());
		$nMailId = $this->MailsIds($nMailId, true);
		$sGetMode = strtolower($sGetMode);
		if($nMailId==$this->attribute("getted_id")) {
			return ($sGetMode=="keys") ? $this->attribute("getted_keys") : $this->attribute("getted");
		}

		if($sGetMode=="headers") {
			return $this->headers($nMailId);
		} else {
			if($this->sServerType=="imap") {
				$aResponse = $this->Fetch($nMailId, "BODY[]");
			} else {
				$aResponse = $this->Fetch($nMailId);
			}
		}

		if(\is_array($aResponse)) {
			$aMail = $this->MailParser(\current($aResponse));
			$aKeys = self::call()->arrayKeysR($aMail);
			$this->attribute("getted", $aMail);
			$this->attribute("getted_id", $nMailId);
			$this->attribute("getted_keys", $aKeys);
			return ($sGetMode=="keys") ? $aKeys : $aMail;
		}

		return false;
	}

	private function MailParser($sMailContent) {
		$vMailParts = \explode($this->sCRLF.$this->sCRLF, $sMailContent, 2);

		// mail
		$aMail = [];

		// headers
		$aMail["headers"] = \iconv_mime_decode_headers($vMailParts[0], 0, "UTF-8");
		$sMainContentType = (\array_key_exists("Content-Type", $aMail["headers"])) ? \strtolower($aMail["headers"]["Content-Type"]) : null;
		if($sMainContentType!==null) {
			$aMainContentType = \explode(";", $sMainContentType);
			$sMainContentType = $aMainContentType[0];
		}
		$sMainEncoding = (\array_key_exists("Content-Transfer-Encoding", $aMail["headers"])) ? \strtolower($aMail["headers"]["Content-Transfer-Encoding"]) : null;

		// from, to, subject
		$aMail["timestamp"] = \strtotime($aMail["headers"]["Date"]);
		$aFrom = $this->PrepareMails($aMail["headers"]["From"], true);
		$aMail["from"] = $aFrom[0];
		$aMail["from_name"] = $aFrom[1];
		$aMail["from_address"] = $aFrom[3];
		$aMail["to"] = $this->PrepareMails($aMail["headers"]["To"])["list"][0];
		$aMail["cc"] = \array_key_exists("Cc", $aMail["headers"]) ? $this->PrepareMails($aMail["headers"]["Cc"])["list"] : "";
		$aMail["cco"] = \array_key_exists("Cco", $aMail["headers"]) ? $this->PrepareMails($aMail["headers"]["Cco"])["list"] : "";
		$aMail["subject"] = $aMail["headers"]["Subject"];
		$aMail["hasattachments"] = false;
		$aMail["attachments"] = [];

		// body
		\preg_match_all("/boundary=\"?([a-z0-9\'\(\)\+\_\,\-\.\/\:\=\?]+)\"?/is", $sMailContent, $aBoundary);

		$aBoundaries = [];		
		foreach($aBoundary[1] as $sBoundary) {
			$sBoundary = \str_replace(["\"", '"'], "", $sBoundary);
			$aBoundaries[] = "--".$sBoundary."--";
			$aBoundaries[] = "--".$sBoundary;
		}
		// print_r($aBoundaries);

		$sHash = self::call()->unique(16);
		$sMailContent = \str_replace($aBoundaries, $sHash, $vMailParts[1]);
		$aMailContent = \explode($sHash, $sMailContent);
		// print_r($aMailContent); exit();
		
		foreach($aMailContent as $sFragment) {
			if(empty($sFragment)) { continue; }

			$aFragment = \explode($this->sCRLF, $sFragment);
			$bSave = false;
			$sContent = "";
			$sLastKey = null;
			$vFragment = [];
			while(\count($aFragment)) {
				$sLine = \array_shift($aFragment);
				$sLine = \trim($sLine);
				if($sLine!=="" && !$bSave) { $bSave = true; }
				if($bSave) {
					if($sLine==="" || !\count($aFragment)) {
						$sContentType = (\array_key_exists("Content-Type", $vFragment)) ? \strtolower($vFragment["Content-Type"]) : $sMainContentType;
						$sEncoding = (\array_key_exists("Content-Transfer-Encoding", $vFragment)) ? \strtolower($vFragment["Content-Transfer-Encoding"]) : $sMainEncoding;
						$sFragment = (\count($aFragment)) ? \implode($this->sCRLF, $aFragment) : $sFragment;

						switch(true) {
							case ($sContentType && \strpos($sContentType, "text/plain")!==false):
								if($this->sServerType=="pop3") {
									$sFragment = \preg_replace(["/\n\.\./s", "/^\.\./s"], ["\n.", "."], $sFragment);
								}
								$aMail["text"] = $this->DecodingType($sFragment, $sEncoding);
								break;
							case ($sContentType && \strpos($sContentType, "text/html")!==false):
								$aMail["html"] = $this->DecodingType($sFragment, $sEncoding);
								break;
							case (isset($vFragment["Content-Type"]) && !isset($vFragment["boundary"]) && \strpos($vFragment["Content-Type"], "boundary")==false):
								$aMail["hasattachments"] = true;
								if($this->attachs_ignore) { break; }
								$aMimeType = self::call()->parseHeaderProperty($vFragment["Content-Type"]);
								$vFragment["mimetype"] = \current($aMimeType);

								if(\array_key_exists("Content-Disposition", $vFragment)) {
									$aDisposition = self::call()->parseHeaderProperty($vFragment["Content-Disposition"]);
									if(\array_key_exists("attachment", $aDisposition)) {
										$vFragment["type"] = "attachment";
										$aFileinfo = [];
										if(\array_key_exists("filename", $vFragment)) {
											$aFileinfo = self::call()->parseHeaderProperty($vFragment["filename"]);
											$vFragment["filename"] = \current($aFileinfo);
										} else {
											$vFragment["filename"] = \array_key_exists("filename", $aMimeType) ? $aMimeType["filename"] : "unname";
										}
										$vFragment["size"] = \array_key_exists("size", $aFileinfo) ? $aFileinfo["size"] : 0;
									} else {
										$vFragment["type"] = "inline";
										$vFragment["filename"] = \array_key_exists("filename", $aDisposition) ? $aDisposition["filename"] : $vFragment["filename"];
										$vFragment["size"] = \array_key_exists("size", $aDisposition) ? $aDisposition["size"] : 0;
									}
								} else if(\array_key_exists("Content-Transfer-Encoding", $vFragment)) {
									$vFragment["type"] = "inline";
									$vFragment["filename"] = \array_key_exists("name", $vFragment) ? $vFragment["name"] : (\array_key_exists("filename", $vFragment) ? $vFragment["filename"] : "unname");
									$vFragment["size"] = 0;
								}

								// content
								$sFragment = \implode("", $aFragment);
								$vFragment["source"] = \trim($sFragment);

								$aMail["attachments"][] = $vFragment;
								break;
						}
						break;
					}

					if(\preg_match("/^([a-z\-]+)(:|=)(.*)/is", $sLine, $aLine)) {
						$sLastKey = $aLine[1];
						$aLine[3] = \trim($aLine[3]);
						$vFragment[$sLastKey] = \str_replace(["\"", '"'], "", $aLine[3]);
					} else if($sLastKey!==null) {
						$vFragment[$sLastKey] .= $this->sCRLF.$sLine;
					}
				}
			}
		}

		return $aMail;
	}

	/**
		$sSearch = SEARCH CHARSET utf-8 BODY "somestring" OR TO "someone@me.com" FROM "someone@me.com"

		ALL - return all messages matching the rest of the criteria
		ANSWERED - match messages with the \ANSWERED flag set
		BCC "string" - match messages with "string" in the Bcc: field
		BEFORE "date" - match messages with Date: before "date"
		BODY "string" - match messages with "string" in the body of the message
		CC "string" - match messages with "string" in the Cc: field
		DELETED - match deleted messages
		FLAGGED - match messages with the \FLAGGED (sometimes referred to as Important or Urgent) flag set
		FROM "string" - match messages with "string" in the From: field
		KEYWORD "string" - match messages with "string" as a keyword
		NEW - match new messages
		OLD - match old messages
		ON "date" - match messages with Date: matching "date"
		RECENT - match messages with the \RECENT flag set
		SEEN - match messages that have been read (the \SEEN flag is set)
		SINCE "date" - match messages with Date: after "date"
		SUBJECT "string" - match messages with "string" in the Subject:
		TEXT "string" - match messages with text "string"
		TO "string" - match messages with "string" in the To : UNANSWERED - match messages that have not been answered
		UNDELETED - match messages that are not deleted
		UNFLAGGED - match messages that are not flagged
		UNKEYWORD "string" - match messages that do not have the keyword "string"
		UNSEEN - match messages which have not been read yet 
	**/
	public function getall() {
		if($this->sServerType=="smtp") {
			return false;
		} else if($this->sServerType=="imap") {
			if($this->bLogged) { $this->connect()->login()->mailbox($this->attribute("currret_mailbox")); }
			list($sSearch,$nLimit,$sGetMode) = $this->getarguments("search,limit,get_mode", \func_get_args());
			if($sSearch===null) { $sSearch = "ALL"; }
			$vResponse = $this->request("SEARCH ".$sSearch, $this->Tag());
			$sResult = \str_replace("* SEARCH ", "", $vResponse["text"]);
			if($this->FindMark($vResponse["text"])) {
				$nTag = \strpos($sResult, $this->sTag);
				$sResult = \substr($sResult, 0, $nTag);
			}
			$sResult = \trim($sResult);
			$aResult = \explode(" ", $sResult);
		} else {
			$aResult = [];
			$nEnd = $this->attribute("exists")+1;
			for($x=1; $x<$nEnd; $x++) {
				$aResult[] = $x;
			}
		}

		// mas nuevos primero
		if($this->revert) { $aResult = \array_reverse($aResult); }
		$aGet = \array_slice($aResult, 0, $nLimit);
		if($sGetMode=="headers") {
			$aResponse = $this->headers($aGet);
		} else if($sGetMode=="keys") {
			$aResponse = $this->Fetch(\current($aGet), "BODY[]");
		} else {
			$sMailsId = $this->MailsIds($aGet);
			$aMails = $this->Fetch($sMailsId, "BODY[]");
			$aResponse = [];
			if(\is_array($aMails)) {
				foreach($aMails as $nMail => $sMail) {
					$aResponse[$nMail] = $this->MailParser($sMail);
				}
			}
		}

		return $aResponse;
	}

	public function getraw() {
		list($nMailId) = $this->getarguments("mail", \func_get_args());
		$nMailId = $this->MailsIds($nMailId, true);
		if($this->sServerType=="imap") {
			if($this->bLogged) { $this->connect()->login()->mailbox($this->attribute("currret_mailbox")); }
			return $this->Fetch($nMailId, "BODY[]");
		} else {
			return $this->Fetch($nMailId);
		}
		return false;
	}

	public function getreplyto() {
		list($nMailId) = $this->getarguments("mail", \func_get_args());
		$nMailId = $this->MailsIds($nMailId, true);
		if($this->sServerType=="imap") {
			if($this->bLogged) { $this->connect()->login()->mailbox($this->attribute("currret_mailbox")); }
			$aHeaders = $this->headers($nMailId, "MESSAGE-ID REFERENCES");
			$this->unflag($nMailId, "seen");
			return $aHeaders;
		}
		return false;
	}

	public function headers() {
		list($sMailsId, $sFields) = $this->getarguments("mail,fields", \func_get_args());
		$sMailsId = $this->MailsIds($sMailsId);
		if($this->sServerType=="imap") {
			if($sFields===null) {
				$aHeaders = $this->Fetch($sMailsId, "BODY[HEADER]");
			} else {
				$aHeaders = $this->Fetch($sMailsId, "BODY[HEADER.FIELDS (".\strtoupper($sFields).")]");
			}
		} else {
			$aHeaders = $this->POP3Header($sMailsId);
		}

		if($aHeaders===false) { return false; }
		foreach($aHeaders as $nMailId => $sHeader) {
			$aHeaders[$nMailId] = \iconv_mime_decode_headers(\trim($sHeader), 0, "UTF-8");
		}

		if($this->sServerType=="pop3" && $sFields!==null) {
			$sFields = \strtolower($sFields);
			$aFields = \explode(" ", $sFields);
			$aFields = self::call()->truelize($aFields);
			$aHeaderFields = [];
			foreach($aHeaders as $nId => $aHeader) {
				foreach($aHeader as $sHeaderName => $sHeader) {
					if(isset($aFields[\strtolower($sHeaderName)])) {
						$aHeaderFields[$sHeaderName] = $sHeader;
					}
				}
				$aHeaders[$nId] = $aHeaderFields;
			}
			
		}

		return $aHeaders;
	}

	private function HELO($sHELO) {
		$aHELO = [];
		$aEHLO = \explode($this->sCRLF, $sHELO);
		foreach($aEHLO as $sRow) {
			$aRow = \preg_split("/[ \-]/is", $sRow, 3);
			if(isset($aRow[1])) {
				if(isset($aRow[2])) {
					$aValues = \explode(" ", $aRow[2]);
					$aHELO[$aRow[1]] = (\count($aValues)>1) ? self::call()->truelize($aValues) : $aValues[0];
				} else {
					$aHELO[$aRow[1]] = true;
				}
			}
		}

		return $aHELO;
	}

	private function MailsIds($mIds, $bFirst=false) {
		if(!\is_array($mIds)) { $mIds = self::call()->explodeTrim(",", $mIds); }
		if($bFirst) { return $mIds[0]; }
		return \implode(",", $mIds);
	}

	public function image() {
		list($sSource, $sCID, $sName) = $this->getarguments("embed,embed_cid,embed_name", \func_get_args());
		
		if($sCID==null) { $sCID = $sSource; }
		if($sName==null) { $sName = $sSource; }
		$this->aAttachments[] = [$sSource, $sName, $sCID];

		return $this;
	}

	private function Logger($sLog) {
		$sHistory = $this->attribute("log");
		
		$sLog = \str_replace("\r\n", "\n", $sLog);
		$sLog = \str_replace("\r", "\n", $sLog);
		$sLog = \preg_replace("/\n+/is", $this->sCRLF."   ", $sLog);
		$sLog = \trim($sLog);

		$sHistory .= $sLog.$this->sCRLF;
		$this->attribute("log", $sHistory);
	}

	public function login() {
		list($sUsername,$sPassword) = $this->getarguments("user,pass", \func_get_args());

		$sPassword = self::passwd($sPassword, true);
		if($this->attribute("state")=="CONNECTED") {
			if($this->sServerType=="smtp") {
				$this->sSMTPUsername = $sUsername;
				$this->sSMTPPassword = $sPassword;
				$this->attribute("state", "READY TO SEND");
				return $this;
			} else if($this->sServerType=="imap") {
				$vResponse = $this->request("LOGIN ".$sUsername." ".$sPassword);
			} else {
				$vResponse = $this->request("USER ".$sUsername);
				if($this->FindMark($vResponse["text"])) {
					$vResponse = $this->request("PASS ".$sPassword);
				}
			}

			if($this->FindMark($vResponse["text"])) {
				$this->attribute("state", "AUTHENTICATED");
				$this->bLogged = true;
				return $this;
			}
		}

		return false;
	}

	public function mailbox() {
		list($sMailBox) = $this->getarguments("folder", \func_get_args());

		if($this->attribute("state")=="AUTHENTICATED") {
			if($this->sServerType=="imap") {
				$vResponse = $this->request("SELECT ".$sMailBox);
				if($this->FindMark($vResponse["text"], "* OK")) {
					\preg_match("/\* ([\d]+) EXISTS/s", $vResponse["text"], $aExists);
					\preg_match("/\* ([\d]+) RECENT/s", $vResponse["text"], $aRecent);
					if(\array_key_exists(1, $aExists)) { $this->attribute("exists", (int)$aExists[1]); }
					if(\array_key_exists(1, $aRecent)) { $this->attribute("recent", (int)$aRecent[1]); }
					$this->attribute("state", "SELECTED");
				}
			} else {
				$vResponse = $this->request("STAT");
				if($this->FindMark($vResponse["text"])) {
					$aLimit = \explode(" ", $vResponse["text"]);
					$this->attribute("exists", (int)$aLimit[1]);
					$this->attribute("recent", null);
					$this->attribute("state", "SELECTED");
				}
			}
			
			$this->attribute("currret_mailbox", $sMailBox);
			return $this;
		}

		return false;
	}

	public function mailboxs($sMailBox=null, $sWildCard=null) {
		list($sMailBox,$sWildCard ) = $this->getarguments("mailbox,wild_card", \func_get_args());

		$aMailboxes = [];
		if($this->attribute("state")=="AUTHENTICATED") {
			if($sMailBox=="") { $sMailBox = "\"\""; }
			if($sWildCard=="") { $sWildCard = "*"; }

			$vResponse = $this->request("LIST ".$sMailBox." ".$sWildCard);
			if($this->FindMark($vResponse["text"])) {
				$aResponse = \explode($this->sCRLF, $vResponse["text"]);
				$sTag = $this->Tag();
				foreach($aResponse as $sLine) {
					if(\strpos($sLine, $sTag)===0) { break; }
					\preg_match("/\* LIST \((.*?)\) (\"(.*?)\") (.*)/i", $sLine, $aLine);
					$vFolder = [];
					$vFolder["name"] = $aLine[4];
					$vFolder["flags"] = \explode("\\", \substr($aLine[1], 1));
					\array_push($aMailboxes, $vFolder);
				}
			}
		}

		$this->attribute("mailboxes", $aMailboxes);
		return $aMailboxes;
	}

	public function maillog() {
		return $this->attribute("log");
	}

	protected function MailMessage() {
		list($sMessage) = $this->getarguments("message", \func_get_args());

		// mensaje HTML
		$sHTML = $sMessage;

		// mensaje Texto Plano
		if($sMessage!==null) {
			$sText = \preg_replace("/(<br>|<br \/>)/is", "\n", $sMessage);
			$sText = \strip_tags($sText);
			$sText = \trim($sText);
		} else {
			$sText = "";
		}

		$sCRLF = $this->sCRLF;
		$fNormalize = function(&$sMessage) use ($sCRLF) {
			$sMessage = \str_replace("\r\n", "\n", $sMessage);
			$sMessage = \str_replace("\r", "\n", $sMessage);
			$aMessage = \explode("\n", $sMessage);
			foreach($aMessage as $nLine => $sLine) {
				if($sLine==".") { $sLine .= " "; }
				$aMessage[$nLine] = $sLine;
			}
			
			$sMessage = \implode($sCRLF, $aMessage);
			return \trim($sMessage);
		};

		$fNormalize($sText);
		$fNormalize($sHTML);
		
		$this->attribute("mail_text", $sText);
		$this->attribute("mail_html", $sHTML);
		
		return $sHTML;
	}

	protected function MailAddress($sAddress, $sType) {
		$aMails = $this->PrepareMails($sAddress, true);
		switch($sType) {
			case "from":
				$this->attribute("mail_from", $aMails[0]);
				$this->attribute("mail_from_mail", $aMails[2]);
				break;
			case "reply": $this->attribute("mail_reply", $aMails[0]); break;
		}

		return $aMails[0];
	}

	protected function MailNotify($sNotify) {
		$this->attribute("mail_notify", $sNotify);
		return $this;
	}

	protected function MailPriority($mPriority) {
		$nPriority = \intval($mPriority);
		switch($nPriority) {
			case 1: $this->attribute("mail_priority", "1 (Highest)"); break;
			case 2: $this->attribute("mail_priority", "2 (High)"); break;
			case 3: $this->attribute("mail_priority", "3 (Normal)"); break;
			case 4: $this->attribute("mail_priority", "4 (Low)"); break;
			case 5: $this->attribute("mail_priority", "5 (Lowest)"); break;
		}
		
		return $this;
	}

	protected function MailSubject($sSubject) {
		$this->attribute("mail_subject", $sSubject);
		return $sSubject;
	}

	private function POP3Header($nMailId) {
		$vResponse = $this->request("RETR ".$nMailId, $this->sCRLF.$this->sCRLF);
		return $vResponse["text"];
	}

	private function ReadAttachs() {
		$sAttachments = "";
		foreach($this->aAttachments as $aAttach) {
			$sSource = $aAttach[0];
			$sName = $aAttach[1];
			$sCID = (isset($aAttach[2])) ? $aAttach[2] : null;

			if(\file_exists($sSource)) {
				$file = self::call("file")->load($sSource);
				$sBuffer = $file->read();
				$sBuffer = \base64_encode($sBuffer);
				$sBuffer = \chunk_split($sBuffer);

				$sAttachments .= $this->sCRLF."--".$this->sBoundary."MIX".$this->sCRLF;

				if($sCID!==null && $file->image) { $sAttachments .= "Content-ID: <".$sCID.">".$this->sCRLF; }
				$sAttachments .= "Content-Type: ".$file->mime."; name=\"".$sName."\"".$this->sCRLF;
				$sAttachments .= "Content-Disposition: attachment; filename=\"".$sName."\"".$this->sCRLF;
				$sAttachments .= "Content-Transfer-Encoding: base64".$this->sCRLF.$this->sCRLF;
				$sAttachments .= $sBuffer;
			} else if($sSource==$this->sContentKey) {
				$sBasename = \basename($sName);
				$aBasename = \explode(".", $sBasename);
				$sExtension = \array_pop($aBasename);

				$sBuffer = \base64_encode($sCID);
				$sBuffer = \chunk_split($sBuffer);
				$sMime = self::call()->mimeType($sExtension);

				$sAttachments .= $this->sCRLF."--".$this->sBoundary."MIX".$this->sCRLF;
				$sAttachments .= "Content-Type: ".$sMime."; name=\"".$sName."\"".$this->sCRLF;
				$sAttachments .= "Content-Disposition: attachment; filename=\"".$sName."\"".$this->sCRLF;
				$sAttachments .= "Content-Transfer-Encoding: base64".$this->sCRLF.$this->sCRLF;
				$sAttachments .= $sBuffer;
			}
		}

		return $sAttachments;
	}

	public function request() {
		list($sMessage,$sMark) = $this->getarguments("command,mark", \func_get_args());
		$sMessage = (($this->sServerType=="imap") ? $this->Tag(true)." " : "").$sMessage.$this->sCRLF;
		@\fputs($this->socket, $sMessage);
		$this->Logger("<< ".$sMessage); 
		return $this->Response($sMark);
	}

	private function Response($sMark=null) {
		$bIMAP = $bSMTP = $bPOP3 = false;
		switch($this->sServerType) {
			case "smtp": $bSMTP = true; break;
			case "imap": $bIMAP = true; break;
			case "pop3": $bPOP3 = true; break;
			default: return false;
		}

		$vResponse = ["code"=>null, "text"=>null];
		if(!\is_resource($this->socket)) {
			$this->Logger("-- No active connections");
		} else {
			$sResponse = "";
			if($bSMTP) {
				while(true) {
					$sGet = \fgets($this->socket, 512);
					$sResponse .= (!empty($sGet)) ? $sGet : "\n";
					$vMetaData = \stream_get_meta_data($this->socket);
					if($vMetaData["unread_bytes"]==0) { break; }
					// if($vMetaData["timed_out"]) { break; }
				}
			} else {
				while(true) {
					$sGet = \fgets($this->socket, 4096);
					$sResponse .= (!empty($sGet)) ? $sGet : "\n";
					if(($sMark!==null && \strpos($sGet, $sMark)===0) ||(\strpos($sGet, $this->Tag())===0)) { 
						break;
					}
				}
			}
			
			$vResponse["text"] = $sResponse;
			if($bSMTP) { $vResponse["code"] = \substr($sResponse, 0, 3); }
			if($bPOP3) { $vResponse["code"] = \substr($sResponse, 0, 4); }
			$this->Logger(">> ".$vResponse["text"]);
		}

		return $vResponse;
	}

	private function ResponseEnd(&$nInit=null) {
		$nInit = \microtime(true);
		return \feof($this->socket);
	}

	public function send() {
		list($mTo, $mCC, $mBCC) = $this->getarguments("to,cc,bcc", \func_get_args());
		if($this->attribute("from")==null) { return self::errorMessage($this->object, 1001); }
		
		$aSendTo = $this->PrepareMails($mTo);
		$this->attribute("mail_to", $aSendTo);
		$this->attribute("mail_cc", $this->PrepareMails($mCC)["list"]);
		$this->attribute("mail_bcc", $this->PrepareMails($mBCC)["list"]);
		
		$aSent = [];
		if(\is_array($aSendTo) && \count($aSendTo)) {
			$this->BuildMail();
			$sMessage = $this->attribute("mail_body");
			$sHeaders = $this->attribute("mail_headers");

			foreach($aSendTo["details"] as $aTo) {
				$sTo = $aTo[2];
				$sMsgId = self::call()->imya();
				$sHeader = \str_replace("{{MAILTO}}", $sTo, $sHeaders);
				$sHeader = \str_replace("{{MSGID}}", $sMsgId, $sHeader);
				if($this->sServerType=="smtp") {
					$aSent[$sMsgId] = $this->SMTPMail($sTo, $sMessage, $sHeader);
				} else if($this->sServerType=="php") {
					$aSent[$sMsgId] = \mail($sTo, $this->mail_subject, $sMessage, $sHeader);
				}
			}
		}
		
		return $aSent;
	}
	
	private function SMTPMail($sTo, $sMessage, $sHeaders) {
		// smtp
		$sAuthType	= $this->smtp_authtype;
		$sSecure 	= \strtolower($this->secure);
		$sLocalhost = "localhost";

		$aCC = $this->attribute("mail_cc");
		$aBCC = $this->attribute("mail_bcc");

		if($this->attribute("state")!="SENT") {
			// helo
			$vResponse = $this->request("EHLO ".$sLocalhost);
			if($vResponse["code"]!=250) {
				$vResponse = $this->request("HELO ".$sLocalhost);
				if($vResponse["code"]!=250) {
					$this->Logger("-- Connection closed");
					return $this->Disconnect();
				}
			}
			$vHELO = $this->HELO($vResponse["text"]);

			// tls
			if(isset($vHELO["STARTTLS"]) && $sSecure=="tls") {
				$vResponse = $this->request("STARTTLS");
				@\stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
				$this->request("EHLO ".$sLocalhost);
			}

			// login
			if($sAuthType!==null) {
				$sAuthType = \strtoupper($sAuthType);
				$vHELO["AUTH"] = [$sAuthType => true];
			}

			$bLogin = false;
			if(isset($vHELO["AUTH"])) {
				switch(true) {
					case (isset($vHELO["AUTH"]["CRAM-MD5"])): $bLogin = $this->AuthCramMD5(); break;
					case (isset($vHELO["AUTH"]["LOGIN"])): $bLogin = $this->AuthLogin(); break;
					default: case (isset($vHELO["AUTH"]["PLAIN"])): $bLogin = $this->AuthPlain(); break;
				}
			} else {
				if(!($bLogin = $this->AuthLogin())) {
					$bLogin = $this->AuthPlain();
				}
			}

			if(!$bLogin) { return $this->Disconnect(); }
			
			// email maxsize
			$this->nSMTPMaxSize = (isset($vHELO["SIZE"])) ? $vHELO["SIZE"] : null;
		}

		// mensaje
		$sMail = $sHeaders.$this->sCRLF.$this->sCRLF.$sMessage.$this->sCRLF.".";

		// max email sizes
		if($this->nSMTPMaxSize!==null && \strlen($sMail) > $this->nSMTPMaxSize) {
			$this->Logger("-- Maximum mail size exceeded: ".$nMail);
			$this->Disconnect();
		}

		// from
		$vResponse = $this->request("MAIL FROM: ".$this->attribute("mail_from_mail"));

		// to
		if($vResponse["code"]==250) {
			$aTo = \array_unique(\array_merge([$sTo], $aCC, $aBCC));

			foreach($aTo as $sMailTo) {
				$vResponse = $this->request("RCPT TO: ".$sMailTo);
				if($vResponse["code"]!=250 && $vResponse["code"]!=251) {
					$this->Disconnect();
				}
			}

			$vResponse = $this->request("DATA");
			if($vResponse["code"]==354) {
				$vResponse = $this->request($sMail);
			if($vResponse["code"]==250) { return $aTo; /*$vResponse;*/ }
			}
		}

		// goodbye
		$this->attribute("state", "SENT");

		return $sTo;
	}

	private function Tag($bNew=false) {
		if($bNew) { $this->sTag = "NGLTAG".self::call()->unique(6); }
		return $this->sTag;
	}

	// test1@domain.com
	// <test1@domain.com>
	// test1 <test1@domain.com>
	// test1@domain.com;test2@domain.com
	// test1 <test1@domain.com>;test2@domain.com
	// test1 <test1@domain.com>,test2 <test2@domain.com>
	// array("test1@domain2.com", "test1 <test1@domain.com>");
	private function PrepareMails($mEmails, $bOnce=false) {
		if(\is_string($mEmails)) {
			$mEmails = \str_replace(",", ";", $mEmails);
			$aMails = \explode(";", $mEmails);
		} else {
			$aMails = $mEmails;
		}

		$aValidMails = ["list"=>[], "details"=>[]];
		if(\is_array($aMails) && \count($aMails)) {
			foreach($aMails as $sEmail) {
				$sEmail = \str_replace(["<",">"], " ", $sEmail);
				$sEmail = \trim($sEmail);
				$aEmail = \explode(" ", $sEmail);
				if(isset($aEmail[1])) {
					$sEmail = \array_pop($aEmail);
					$sName = \implode(" ", $aEmail);
					$sName = \trim($sName);
				} else {
					$sEmail = \trim($aEmail[0]);
					$sName = $sEmail;
				}

				if($bOnce) { return [$sName."<".$sEmail.">", $sName, "<".$sEmail.">", $sEmail]; }

				if(\preg_match("/".$this->sRegexMail."/is", $sEmail)) {
					$aValidMails["list"][] = "<".$sEmail.">";
					$aValidMails["details"][] = [$sName."<".$sEmail.">", $sName, "<".$sEmail.">", $sEmail];
				}
			}
		}

		return $aValidMails;
	}
}

?>