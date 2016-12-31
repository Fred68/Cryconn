<?php
include_once 'const.inc.php';
class Crycon						// Classe: connessione criptata
	{
	var $debugMode = true;			// Messaggi per debug
	protected $dbnm = "";			// Nome del database
	protected $errore = "";			// Messaggi di errore
	protected $messaggio = "";		// Messaggio
	protected $comando = "";		// Comando da eseguire
	protected $data = "";			// Dati legati al messaggio
	protected $timsg = "";			// Messaggio del timer (refresh)
	var $stddbname = "crycon";		// Nome del file .db
	var $dbpath = "db";				// Percorso (relativo) del database e del file rsa
	//////////////////////////////////
	var $users = "users";			// Nome tabella users e nomi dei campi:...
	var $usrname = "usrname";		// nome utente del login
	var $passwd = "passwd";			// hash della password del login		
	var $keystr = "keystr";			// per aes
	var $pwddb = "pwddb";			// per accesso database
	var $privKey = "prK";			// Chiave privata
	var $pubKey = "puK";			// Chiave pubblica
	//////////////////////////////////
	var $logged = "logged";			// Nome tabella logged e nomi dei campi:...
	var $sid = "sid";		 		// session_id
	var $lgt = "lgt";				// log time
	var $llg = "llg";				// last log time
	var $ssk = "ssk";				// session symmetric key
	//////////////////////////////////
	var $cmdfrm = "cmd";			// per memorizzazione comando
	var $refri = 20;				// refresh in secondi
	var $rtmt = 35;					// timeout sessione in secondi
	//////////////////////////////////
	var $db = null;					// Database
	//////////////////////////////////
	var $caratteri;					// Array con i caratteri ammessi
	//////////////////////////////////
	protected $cmd = array();		// Array con i comandi SQL
	#fare AGGIUNGERE VARIABILE CON LO STATO DOPO L'ULTIMA OPERAZIONE; NON USARE I MESSAGGI; VALORI: IN COSTANTI
	#fare SANITIZE dei dati del POST, prima di usarli per un comando su database o altro. 
	// Dati principali
	public function __construct($debugmode = false)		// Costruttore
		{
		$this->refri = $this->refri * 1000;
		$this->debugMode = $debugmode;
		$this->caratteri = array_merge(range('A','Z'), range('a','z'), range('0','9'));
		$this->Clear();									// Cancella i messaggi di errore
		$this->ConstructName($this->stddbname);			// Chiama l'inizializzatore
		}
	protected function Clear()							// Cancella errori e messaggi
		{
		$this->errore = "";
		$this->messaggio = "";
		$this->comando = "";
		$this->data = "";
		$this->timsg = "...";
		
		}
	public function GetError()							// Restituisce gli errori
		{
		return $this->errore;
		}
	public function GetMessage()						// Restituisce il messaggio
		{
		return $this->messaggio;
		}
	protected function ConstructName($dbname)			// Inizializzatore per il costruttore, con nome del database
		{
		$msgecho = "";
		$msgecho .= "<b>Parent constructor</b><br>";

		$this->cmd["udroptb"] = "DROP TABLE IF EXISTS ".$this->users.";";
		$this->cmd["utable"] = "CREATE TABLE ".$this->users." (".UID." INTEGER PRIMARY KEY NOT NULL, ".$this->usrname." TEXT NOT NULL, ".$this->passwd." TEXT NOT NULL, ".$this->keystr." TEXT DEFAULT NULL, ".$this->pwddb." TEXT, ".$this->privKey." TEXT DEFAULT NULL, ".$this->pubKey." TEXT DEFAULT NULL );";	
		$this->cmd["insert"] = "INSERT INTO ".$this->users." (".$this->usrname.", ".$this->passwd.", ".$this->keystr.", ".$this->pwddb.") VALUES (:".$this->usrname.", :".$this->passwd.", :".$this->keystr.", :".$this->pwddb.");";
		$this->cmd["count"] = "SELECT COUNT(*) FROM ".$this->users.";";
		$this->cmd["users"] = "SELECT DISTINCT * FROM ".$this->users.";";
		$this->cmd["countusr"] = "SELECT COUNT(*) FROM ".$this->users." WHERE ".$this->usrname."=:".$this->usrname." AND ".$this->passwd."=:".$this->passwd.";";
		$this->cmd["getusr"] = "SELECT ".UID.", ".$this->keystr.", ".$this->pwddb." FROM ".$this->users." WHERE ".$this->usrname."=:".$this->usrname." AND ".$this->passwd."= :".$this->passwd.";";
		$this->cmd["setkeys"] = "UPDATE ".$this->users." SET ".$this->privKey."=:".$this->privKey.", ".$this->pubKey."=:".$this->pubKey." WHERE ".UID."=:".UID.";";
		$this->cmd["getusrpk"] = "SELECT ".$this->privKey.", ".$this->pubKey." FROM ".$this->users." WHERE ".$this->usrname."=:".$this->usrname.";";
		
		$this->cmd["ldroptb"] = "DROP TABLE IF EXISTS ".$this->logged.";";
		$this->cmd["ltable"] = "CREATE TABLE ".$this->logged." (".UID." INTEGER PRIMARY KEY NOT NULL, ".$this->sid." TEXT NOT NULL, ".$this->ssk." TEXT, ".$this->lgt." INTEGER NOT NULL, ".$this->llg." INTEGER NOT NULL);";
		$this->cmd["lcount"] = "SELECT COUNT(*) FROM ".$this->logged.";";
		$this->cmd["logged"] = "SELECT DISTINCT * FROM ".$this->logged.";";
		$this->cmd["countlgd"] = "SELECT COUNT(*) FROM ".$this->logged." WHERE ".UID."= :".UID.";";
		$this->cmd["insertlgu"] = "INSERT INTO ".$this->logged." (".UID.", ".$this->sid.", ".$this->lgt.", ".$this->llg.") VALUES (:".UID.", :".$this->sid.", :".$this->lgt.", :".$this->llg.");";
		$this->cmd["removelgu"] = "DELETE FROM ".$this->logged." WHERE ".UID." = :".UID.";";
		$this->cmd["updlgu"] = "UPDATE ".$this->logged." SET ".$this->llg."=:".$this->llg." WHERE ".UID."=:".UID.";";
		$this->cmd["getlgu"] = "SELECT ".$this->sid.", ".$this->lgt.", ".$this->llg." FROM ".$this->logged." WHERE ".UID."=:".UID.";";
		if(strlen($dbname)<1)
			{
			$dbname = $this->stddbname;	
			}
		$this->dbnm = "sqlite:".$this->dbpath."/".$dbname.".db";
		$msgecho .=  "<b>Db</b><p>";
		$msgecho .=  "Parent db name: ".$this->dbnm."<br>";
		$msgecho .=  "</p>";
		$msgecho .=  "<b>Commands</b><p>";
		foreach($this->cmd as $k => $x)
			{
			$msgecho .=  "cmd[\"".$k."\"]=".$x."<br>";	
			}
		$msgecho .=  "</p>";
		$msgecho .=  "<b>End of Parent constructor</b><br>";
		if($this->debugMode === true)
			echo $msgecho; 
		}
	protected function Connect()						// Apre la connessione (crea il database, se non esiste)
		{
		$ok = false;
		if(($this->db)===null)
			{
			try
				{
				$this->db = new PDO($this->dbnm);			
				$ok = true;
				}
			catch(PDOException $e)
				{	
				$this->errore .= "<p><b>"."Error: ".$e->getMessage()."</b></p>";
				}	
			}
		return $ok;
		}
	public function Disconnect()						// Rimuove l'utente logged, cancella sessione e connessione al database
		{
		if(isset($_SESSION[UID]))
			{
			$this->RemoveLoggedUser($_SESSION[UID]);
			session_unset();
			session_destroy();
			$this->db = null;
			}
		return;
		}
	protected function CountUsers($uname, $upwd)		// Conta gli utenti con nome e hash della password. -1 se errore.
		{
		$cnt = 0;
		try
			{
			$this->Connect();						// Connette, se necessario, ma non disconnette
			$stmt = $this->db->prepare($this->cmd["countusr"]);
			$stmt->bindParam(":".$this->usrname,$uname);
			$stmt->bindParam(":".$this->passwd,$upwd);
			$stmt->execute();
			$cnt = $stmt->fetchColumn();
			}
		catch(PDOException $e)
			{
			$this->errore .= "Error: ".$e->getMessage()."\n";
			$cnt = -1;
			}
		return $cnt;
		}
	protected function GetUserPKs($uname, &$puk, &$prk)			// Ottiene chiavi RSA dal database
		{
		$ok = false;
		$puk = "";
		$prk = "";
		try
			{
			$this->Connect();									// Connette, se necessario, ma non disconnette
			$stmt = $this->db->prepare($this->cmd["getusrpk"]);
			$stmt->bindParam(":".$this->usrname,$uname);
			$stmt->execute();
			$cnt = $stmt->fetchAll(PDO::FETCH_NUM);
			$nrows = count($cnt);
			if($nrows > 0)
				{
				$row = $cnt[0]; 	// Sceglie la prima (ed unica)
				$prk = $row[0]; 	// Legge le chiavi
				$puk = $row[1];
				$ok = true;
				}
			}
		catch(PDOException $e)
			{
			$this->errore .= "Error: ".$e->getMessage()."\n";
			}
		return $ok;
		}	
	protected function GetUserKeys($uname, $upwd, &$id, &$key, &$pwdb)	// Ottiene chiave e password del database
		{
		$ok = false;
		$id = "";
		$key = "";
		$pwdb = "";
		$cnt = $this->CountUsers($uname, $upwd);
		if($cnt == 1)		// Converte. Non usare ===.
			{
			try
				{
				$this->Connect();						// Connette, se necessario, ma non disconnette
				$stmt = $this->db->prepare($this->cmd["getusr"]);
				$stmt->bindParam(":".$this->usrname,$uname);
				$stmt->bindParam(":".$this->passwd,$upwd);
				$stmt->execute();
				$cnt = $stmt->fetchAll(PDO::FETCH_NUM);
				$row = $cnt[0]; 	// Sceglie la prima (ed unica)
				$id = $row[0]; 		// Legge l'id
				$key = $row[1]; 	// Legge la chiave
				$pwdb = $row[2];	// Legge la password del db
				$ok = true;
				}
			catch(PDOException $e)
				{
				$this->errore .= "Error: ".$e->getMessage()."\n";
				}
			}
		else
			{
			if($cnt < 1)
				$this->errore .= "Error: "."No user found"."\n";
			else 
				$this->errore .= "Error: "."Multiple users found"."\n";
			}
		return $ok;
		}
	protected function SetKeys($id,$prk,$puk)		// Memorizza le chiavi privata e pubblica
		{
		$ok = false;
		try
			{
			$this->Connect();
			$stmt = $this->db->prepare($this->cmd["setkeys"]);
			$stmt->bindParam(':'.UID,$id);
			$stmt->bindParam(':'.$this->privKey,$prk);
			$stmt->bindParam(':'.$this->pubKey,$puk);
			$ok = $stmt -> execute();
			$ok = true;
			}
		catch(PDOException $e)
			{
			$this->errore .= "Error: ".$e->getMessage()."\n";
			}
		return $ok;
		}
	protected function CountLoggedUsers($uid)			// Conta gli utenti connessi con $userID. -1 se errore
		{
		$cnt = 0;
		try
			{
			$this->Connect();
			$stmt = $this->db->prepare($this->cmd["countlgd"]);
			$stmt->bindParam(":".UID,$uid);
			$stmt->execute();
			$cnt = $stmt->fetchColumn();
			}
		catch(PDOException $e)
			{
			$this->errore .= "Error: ".$e->getMessage()."\n";
			$cnt = -1;
			}
		return $cnt;
		}
	protected function InsertLoggedUser($id,$sid)		// Inserisce logged user
		{
		$ok = false;
		try
			{
			$this->Connect();
			$stmt = $this->db->prepare($this->cmd["insertlgu"]);
			$stmt->bindParam(':'.UID,$id);
			$stmt->bindParam(':'.$this->sid,$sid);
			
			$tm = time();
			$stmt->bindParam(':'.$this->lgt,$tm);
			$stmt->bindParam(':'.$this->llg,$tm);
			
			$ok = $stmt -> execute();
			}
		catch(PDOException $e)
			{
			$this->errore .= "Error: ".$e->getMessage()."\n";
			}
		return $ok;
		}
	public function IsLogged()							// Verifica se è l'utente della sessione attiva è connesso
		{
		$ok = false;
		if(isset($_SESSION[UID]) &&						// Prima verifica le variabili di sessione, se è connesso
				isset($_SESSION[$this->usrname]) &&
				isset($_SESSION[$this->llg]) &&
				isset($_SESSION[$this->lgt])
				)
			{
			if($this->CountLoggedUsers($_SESSION[UID])>0)	// Poi dal database
				{
				$ok = true;
				}
			else 
				{
				$this->messaggio .= "Disconnected by another user\n";
				}
			}
		return $ok;
		}
	protected function RefreshLoggedUser()				// Refresh con aggiornamento dell'utente logged
		{
		$ok = false;
		if($this->IsLogged())							// Se è connesso...
			{
			$id = $_SESSION[UID];							// Legge l'uid dell'utente !
			$ttr = time() - $_SESSION[$this->llg];			// Tempo trascorso dal refresh precedente 
			if($ttr < $this->rtmt)							// Se rimane nel timeout
				{
				$_SESSION[$this->llg] = time();				// Àggiorna all'ultimo refresh la variabile di sessione...
				try											// ...e l'utente
					{										// Nota: in sqlite non c'è ON UPDATE CURRENT TIMESTAMP, solo in MySql
					$this->Connect();						// Usa INTEGER e time() di php, più semplice da confrontare
					$stmt = $this->db->prepare($this->cmd["updlgu"]);
					$tm = time();
					$stmt->bindParam(':'.UID, $id);
					$stmt->bindParam(':'.$this->llg, $tm);
					$ex = $stmt->execute();
					if($ex == true)
						{
						$ok = true;							// Imposta il flag a true
						$this->timsg = "ok";
						}
					else 
						{
						$this->timsg = "Error in execute()";
						}
					#fare Aggiornare (se necessario) le variabili di sessione LLG ecc...
					}
				catch(PDOException $e)
					{
					$this->timsg = "Error: ".$e->getMessage()."\n";
					}
				}
			else 
				{
				$this->timsg = "Timeout expired";
				}
			}
		return $ok;
		}
	protected function RemoveLoggedUser($id)			// Rimuove l'utente con $id dagli utenti collegati
		{
		$ok = false;
		try
			{
			$this->Connect();
			$stmt = $this->db->prepare($this->cmd["removelgu"]);
			$stmt->bindParam(':'.UID,$id);
			$stmt -> execute();
			$ok = true;
			}
		catch(PDOException $e)
			{
			$this->errore .= "Error: ".$e->getMessage()."\n";
			}
		return $ok;
		}
	protected function CmdClear($p0,$p1,$p2)			// Esegue clear logged user
		{
		if(isset($_SESSION[UID]) && isset($_SESSION[$this->usrname]))	// se login effettuato dalla sessione attiva...
			{
			$this->Disconnect();						// ... disconnette
			$this->messaggio = $p1." disconnected";
			}
		else											// Se non connesso nella sessione...
			{
			try
				{
				$uid = $key = $pwdb = "";
				$loginOk = $this->GetUserKeys($p1, $p2, $uid, $key, $pwdb);		// Controlla...
				if($loginOk == true)											// ...se credenziali corrette
					{
					$_SESSION[UID] = $uid;
					$ret = false;
					$ttot = $trfr = 0;
					$ret = $this->CheckUser($uid, $trfr, $ttot);				// Verifica se ancora connesso
					if($ret == true)
						{
						if($trfr > $this->rtmt)
							{
							$this->Disconnect();
							$this->messaggio = $p1." reset for login";
							}
						else 
							{
							$this->messaggio = $p1." still connected";
							}
						}
					#fare ATTENZIONE: Disconnect() solo se ok...? VERIFICARE CHE COSA FARE !
					}
				else
					{
					$this->messaggio = $p1." wrong user or password";		// Se credenziali errate...
					}
				}
				catch(PDOException $e)
					{
					$this->errore .= "Error: ".$e->getMessage()."\n";
					}
			}
		return true;
		}
	protected function CheckUser($uid, &$dtrefr, &$dttot) 	// true se l'utente è connesso e nel timeout
		{
		$cnt = 0;										
		try
			{
			$this->Connect();
			$stmt = $this->db->prepare($this->cmd["getlgu"]);
			$stmt->bindParam(":".UID,$uid);
			$stmt->execute();
			$cnt = $stmt->fetchAll(PDO::FETCH_NUM);
			$r = count($cnt);
			if($r > 0)
				{
				$row = $cnt[0];			// Sceglie la prima riga, se c'è
				if(count($row) == 3)	// Legge le celle delle colonne, se corrette
					{
					$sid = $row[0];		// Sessione		
					$lgt = $row[1];		// log time
					$llg = $row[2];		// last log
					
					$dtrefr = time() - $llg;
					$dttot = time() - $lgt; 
					
					$ok = true;
					#fare DA COMPLETARE Verifica se l'utente con user id è ancora connesso e nel timeout
					}
				}
			}
		catch(PDOException $e)
			{
			$this->timsg .= "Error: ".$e->getMessage()."\n";
			$cnt = -1;
			}
		return $cnt;
		}
	protected function CmdLogin($p0,$p1,$p2)			// Esegue login$p1 utente, $p2 hash password (criptato o no)
		{
		#fare SE LOGIN CON UTENTE GIA` CONNESSO, IL TIMER PARTE LO STESSO? Correggere
		#fare SE REFRESH CON UTENTE DISCONNESSO DA ALTRO UTENTE, IL TIMER RESTA ATTIVO.
		#fare METTERE UN CAMPO PHP IN JS CHE VERFICA SEMPRE SE DISATTIVARE IL TIMER !
		if($this->IsLogged())
			{
			$this->Clear();
			$this->messaggio = $_SESSION[$this->usrname]." already connected";
			return;
			}
		$prK = "";
		$puK = "";
		$kOk = $this->GetUserPKs($p1, $puK, $prK);		// Legge le chiavi
		if(!$kOk)
			{
			$this->messaggio = "User not found";
			$this->Disconnect();
			}
		if(strlen($prK) > 1)				// Se la chiave privata non è nulla
			{
			try
				{
				$p2no64 = base64_decode($p2);					// Decodifica da base64
				openssl_private_decrypt($p2no64, $dec, $prK);	// Decodifica con chiave privata
				$p2 = $dec;
				}
			catch(Exception $e)
				{
				$this->messaggio = "Error in openssl_public_decrypt(): ".$e->getMessage()."\n";
				$this->Disconnect();
				}
			}
		$loginOk = $this->GetUserKeys($p1, $p2, $uid, $key, $pwdb);		// Legge i dati utente
		if($loginOk == true)
			{
			$lg = $this->CountLoggedUsers($uid);
			if($lg == 0)
				{
				$_SESSION[$this->cmdfrm] = $p0;
				$_SESSION[UID] = $uid;
				$_SESSION[$this->usrname] = $p1;
				$_SESSION[$this->keystr] = $key;
				$_SESSION[$this->pwddb] = $pwdb;
				$_SESSION[$this->llg] = time();
				$_SESSION[$this->lgt] = time();
				$ok = false;
				$ok = $this->InsertLoggedUser($uid,session_id());
				if(!$ok)
					{
					$this->messaggio = "Storage failed";
					$this->Disconnect();
					}
				else
					{
					$this->Clear();			// Se tutto ok, cancella gli errori
					$this->messaggio = $p1." connected";
					}
				}
			else if($lg < 0)
				{
				$this->messaggio = "ERROR";
				$this->Disconnect();
				}
			else
				{
				$this->messaggio = "User ".$lg." already connected";
				}
			}
		else 
			{
			$this->messaggio = $p1."  wrong user or password";
			$this->Disconnect();
			}
		}
	protected function CmdLogout($p0,$p1,$p2)			// Esegue logout
		{
		if(isset($_SESSION[UID]) && isset($_SESSION[$this->usrname]))	// se login effettuato...
			{
			$this->messaggio = ($_SESSION[$this->usrname])." disconnected";
			$this->Disconnect();
			}
		else		// ...oppure no
			{
			$this->messaggio = "No user connected. Logout unnecessary";
			$this->Disconnect();
			}
		return true;
		}
	protected function CmdRefresh($p0,$p1,$p2)			// Esegue refresh
		{
		if(isset($_SESSION[UID]) && isset($_SESSION[$this->usrname]))	// se login effettuato...
			{
			$ok = $this->RefreshLoggedUser();
			if(!$ok)
				{
				$this->Disconnect();
				}
			}
		else		// ...oppure no
			{
			#fare FAR CHIAMARE STOP TIMER CON JSON (O FARLO DIRETTAMENTE DA JS), QUI OPPURE AD OGNI OPERAZIONE (MEGLIO!) 
			$this->Disconnect();
			}
		return true;
		}
	protected function GenerateSeq($nbyte)
		{
		$s = "";
		$n = 1;
		for($i=0; $i<$nbyte; $i++)
			{
			$s = $s.(string)$n;
			$n++;
			if($n>9)
				$n = 0;
			}
		return $s;
		}
	protected function GenerateRndSeq($nbyte)
		{
		$alph = implode('',range(' ','~'));
		$s = "";
		$n = 1;
		for($i=0; $i<$nbyte; $i++)
			{
			$s = $s.$alph{$this->crypto_rand_secure(0,strlen($alph)-1)};
			}
		return $s;
		}
	protected function crypto_rand_secure($min, $max)	// From php.net
		{
		$range = $max - $min;
		if ($range == 0) return $min;
		$log = log($range, 2);
		$bytes = (int) ($log / 8) + 1;		// length in bytes
		$bits = (int) $log + 1; 			// length in bits
		$filter = (int) (1 << $bits) - 1;	// set all lower bits to 1
		do
			{
			$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes, $s)));
			$rnd = $rnd & $filter;			// discard irrelevant bits
			}
		while($rnd >= $range);
		return $min + $rnd;
		}
	protected function GenerateRandom($nbyte)			// Stringa crittograficamente sicura, in base64
		{
		$s = "";
		$x = $this->GenerateRndSeq($nbyte);				// Casuale [Usare $x = $this->GenerateSeq($nbyte) per test]
		$s = base64_encode($x);
		return $s;
		}
	protected function GenerateIV()						// IV in base64
		{
		return $this->GenerateRandom(IVSIZE);
		}
	protected function GenerateAES()
		{
		return $this->GenerateRandom(AESSIZE);			// AES in base64
		}
	public function ReadRequest(&$p0,&$p1,&$p2)			// Legge la richiesta del POST e la mette negli argomenti. False se errata.
		{
		$ok = false;
		if( isset($_POST[P0])&&isset($_POST[P1])&&isset($_POST[P2])&&!empty($_POST[P0]))
			{
			$p0 = $_POST[P0];
			$p1 = $_POST[P1];
			$p2 = $_POST[P2];
			$ok = true;
			}
		return $ok;
		}
	protected function EncryptMessage($x)
		{
		$iv64 = $this->GenerateIV();
		$iv = base64_decode($iv64);
		$method = "aes-256-cbc";
		$aes = base64_decode($_SESSION[$this->ssk]);
		$enc = openssl_encrypt($x, $method, $aes, false, $iv);
		return $iv64.$enc;
		}
	protected function DecryptMessage($x)
		{
		$iv64 = substr($x,0,IVSIZE64);
		$enc = substr($x, IVSIZE64);
		$iv = base64_decode($iv64);
		$method = "aes-256-cbc";
		$aes = base64_decode($_SESSION[$this->ssk]);
		$dec  = openssl_decrypt($enc, $method, $aes, false, $iv);
		return $dec;
		}
	public function ProcessRequest($p0,$p1,$p2)			// Analizza la richiesta e la esegue
		{
		$done = false;
		switch($p0)										// Prepara il comando di risposta ed i dati
			{
			case CMD_LOGIN:								// Login
				$done = $this->CmdLogin($p0,$p1,$p2);
				if($this->IsLogged())					// Se connesso,
					{
					$this->comando = ID_START;			// Richiede start timer in js
					$this->data = session_id();			// invia anche la sid
					}
				break;
			case CMD_CLEAR:
				$done = $this->CmdClear($p0,$p1,$p2);
				break;
			case CMD_LOGOUT:
				$done = $this->CmdLogout($p0,$p1,$p2);	// Esegue logout
				$this->comando = ID_STOP;				// e richiede stop timer
				break;
			case CMD_REFRESH:
				$done = $this->CmdRefresh($p0,$p1,$p2);
				break;
			case CMD_COMMAND:							// Elabora una stringa di comando generico (criptata)
				$this->comando = CMD_COMMAND;
				$msg = $this->DecryptMessage($p1);
				$res = $this->ProcessMessage($msg);
				$this->data = $this->EncryptMessage($res);
				break;
			case CMD_CONNECT:							// Connessione criptata
				if($this->IsLogged())
					{
					$this->comando = CMD_CONNECT;
					$aestmp = $this->GenerateAES();		// Ottiene un aes temporaneo in base64
					$_SESSION[$this->ssk] = $aestmp;	// Memorizza l'aes di sessione (single symm key), in base64
					$risposta = $aestmp.session_id();	// Aggiunge session ID
					openssl_public_encrypt($risposta, $encrsa, $p1);	// Crittografa la risposta ($p1 contiene la puk)
					$encrsa64 = base64_encode($encrsa);	// Codifica in base64
					$this->data = $encrsa64;			// Lo invia come risposta
					}
				break;
			case CMD_AESPK:								// Richiesta chiave pubblica per verifica aes
				$this->comando = CMD_AESPK;				// Imposta la risposta da inviare
				$this->GenerateRsaKeys($prK, $puK);
				$_SESSION[$this->privKey] = $prK;
				$this->data = $puK;
				break;
			case CMD_AES:
				$this->comando = CMD_AES;
				$dec = null;
				$privateKey = openssl_pkey_get_private($_SESSION[$this->privKey]);	// Ottiene la chiave privata dalla stringa
				$p2no64 = base64_decode($p1);								// Traduce la chiave aes criptata da base64
				openssl_private_decrypt($p2no64, $dec, $privateKey);		// Decodifica la chiave aes con chiave privata 
				if($_SESSION[$this->ssk] == $dec)							// Verifica
					{
					$this->data = sha1($dec);
					}
				else
					{
					$this->data = "Error in AES";
					$this->Disconnect();				// Completare !!!!
					}
				break;
			}
		return $done;
		}
	public function LoggedMessage()						// Messaggio con utente connesso
		{
		$msg = "";
		if($this->IsLogged())
			$msg = "Logged: ".$_SESSION[$this->usrname];
		else 
			$msg = "User not logged.";
		return $msg;
		}
	public function TimerValue()
		{
		$tm = "";
		if($this->IsLogged())
			{
			$tm = $_SESSION[$this->llg]-$_SESSION[$this->lgt];
			}
		return $tm;
		}
	public function TimerMsg()
		{
		return $this->timsg;
		}
	public function SendResponse()						// Invia risposta codificata in json
		{
		$jsn = [];
		$tmp = $this->GetMessage();						// Messaggio
		if(!!$tmp)
			$jsn[ID_MSG] = $tmp;
		$tmp = $this->GetError();						// Errore
		if(!!$tmp)
			$jsn[ID_ERR] = $tmp;
		else 
			$jsn[ID_ERR] = "...";
		$jsn[ID_LOGMSG] = $this->LoggedMessage();		// (Un)Logged
		$jsn[ID_TIMERMSG] = $this->TimerMsg();			// Timer
		if($this->IsLogged())
			{
			$jsn[ID_LOGGED] = "+";						// Mostra class loggedin
			$jsn[ID_UNLOGGED] = "-";					// Nasconde class unlogged
			$jsn[ID_TIMER] = $this->TimerValue();		// Risponde al timer
			}
		else 
			{
			$jsn[ID_LOGGED] = "-";
			$jsn[ID_UNLOGGED] = "+";
			$jsn[ID_TIMER] = "";
			}
		switch($this->comando)
			{
			case CMD_COMMAND:
			case CMD_CONNECT:
			case CMD_AES:
			case CMD_AESPK:
			case ID_START:
				{
				$jsn[$this->comando] = $this->data;
				}
				break;
			default:
				$jsn[$this->comando] = ID_EXE;
				break;
			}
#		ERRORE: COMPLETARE CODIFICA DI:
#		COMANDO GENERICO: $JSN[comando] = ID_EXE
#		COMANDO SPECIFICO: $JSN[CMD_COMMAND] = "DATI"
		echo json_encode($jsn);
		}
	function GenerateRsaKeys(&$prK, &$puK)				// Genera coppia di chiavi (argomenti passati per reference)
		{
		$prK = $puK = '';
		$privateKey = openssl_pkey_new(array('private_key_bits' => RSAKEYSIZE,'private_key_type' => OPENSSL_KEYTYPE_RSA,));
		openssl_pkey_export ($privateKey, $prK);			// Chiave privata in string
		$a_key = openssl_pkey_get_details($privateKey);		// Estrae i dettagli della chiave...
		$puK = $a_key['key'];								// ...tra cui la chiave pubblica
		}
	function ProcessMessage($m)							// Elabora il messaggio e prepara risposta (inizia con 'comando=') 
		{
		$r = '';
		switch($m)
			{
			case CMD_PK:								// Genera nuova coppia di chiavi ed invia la chiave pubblica
				$this->GenerateRsaKeys($prK, $puK);		// Per reference
 				$r = CMD_PK.'='.$puK;
 				$_SESSION[$this->privKey] = $prK;		// Le memorizza in sessione
 				$_SESSION[$this->pubKey] = $puK;
				break;
			case CMD_KOK:								// Memorizza le chiavi nel database
				if(!isset($_SESSION[$this->privKey]) || !isset($_SESSION[$this->pubKey]))
					{
					$r = CMD_KOK.'='."New key not generated yet";
					break;
					}
				$done = $this->SetKeys($_SESSION[UID],$_SESSION[$this->privKey],$_SESSION[$this->pubKey]);
				if(!$done)
					{
					$r = CMD_KOK.'='."Error storing keys";
					}
				else 
					{
					$r = CMD_KOK.'='."Keys correctly stored";
					//$_SESSION[$this->privKey] = "";		// Le azzera
					//$_SESSION[$this->pubKey] = "";
					unset($_SESSION[$this->privKey]);		// Elimina le chiavi dalla sessione
					unset($_SESSION[$this->pubKey]);
					}
				break;
			case CMD_RSTK:								// Genera nuova coppia di chiavi ed invia la chiave pubblica
				$done = $this->SetKeys($_SESSION[UID],"","");
				if(!$done)
					{
					$r = CMD_RSTK.'='."Error resetting keys";
					}
				else
					{
					$r = CMD_RSTK.'='."Keys correctly reset";
					unset($_SESSION[$this->privKey]);		// Elimina le chiavi dalla sessione
					unset($_SESSION[$this->pubKey]);
					}
				break;
			default:									// Ripsonde con un messaggio generico
				$r = '000'.'='."Message processed:\n".$m."\nOra: ".date("Y-m-d H:i:s",time());
				break;
			}
		return $r;
		}

		
	}
?>	