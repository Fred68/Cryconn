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
	var $dbpath = "db";				// Percorso (relativo) del database
	//////////////////////////////////
	var $users = "users";			// Nome tabella users e nomi dei campi:...
	var $usrname = "usrname";		// nome utente del login
	var $passwd = "passwd";			// hash della password del login		
	var $keystr = "keystr";			// per aes
	var $pwddb = "pwddb";			// per accesso database
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
	
	protected $cmd = array();		// Array con i comandi SQL
	
	#fare AGGIUNGERE VARIABILE CON LO STATO DOPO L'ULTIMA OPERAZIONE; NON USARE I MESSAGGI; VALORI: IN COSTANTI
	
	// Dati principali
	public function __construct($debugmode = false)		// Costruttore
		{
		$this->refri = $this->refri * 1000;
		$this->debugMode = $debugmode;
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
		$this->cmd["utable"] = "CREATE TABLE ".$this->users." (".UID." INTEGER PRIMARY KEY NOT NULL, ".$this->usrname." TEXT NOT NULL, ".$this->passwd." TEXT NOT NULL, ".$this->keystr." TEXT DEFAULT NULL, ".$this->pwddb." TEXT);";	
		$this->cmd["insert"] = "INSERT INTO ".$this->users." (".$this->usrname.", ".$this->passwd.", ".$this->keystr.", ".$this->pwddb.") VALUES (:".$this->usrname.", :".$this->passwd.", :".$this->keystr.", :".$this->pwddb.");";
		$this->cmd["count"] = "SELECT COUNT(*) FROM ".$this->users.";";
		$this->cmd["users"] = "SELECT DISTINCT * FROM ".$this->users.";";
		$this->cmd["countusr"] = "SELECT COUNT(*) FROM ".$this->users." WHERE ".$this->usrname."= :".$this->usrname." AND ".$this->passwd."= :".$this->passwd.";";
		$this->cmd["getusr"] = "SELECT ".UID.", ".$this->keystr.", ".$this->pwddb." FROM ".$this->users." WHERE ".$this->usrname."= :".$this->usrname." AND ".$this->passwd."= :".$this->passwd.";";
		
		$this->cmd["ldroptb"] = "DROP TABLE IF EXISTS ".$this->logged.";";
		//$this->cmd["ltable"] = "CREATE TABLE ".$this->logged." (".UID." INTEGER PRIMARY KEY NOT NULL, ".$this->sid." TEXT NOT NULL, ".$this->lgt." DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ".$this->llg." DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP);";
		$this->cmd["ltable"] = "CREATE TABLE ".$this->logged." (".UID." INTEGER PRIMARY KEY NOT NULL, ".$this->sid." TEXT NOT NULL, ".$this->ssk." TEXT, ".$this->lgt." INTEGER NOT NULL, ".$this->llg." INTEGER NOT NULL);";
		$this->cmd["lcount"] = "SELECT COUNT(*) FROM ".$this->logged.";";
		$this->cmd["logged"] = "SELECT DISTINCT * FROM ".$this->logged.";";
		$this->cmd["countlgd"] = "SELECT COUNT(*) FROM ".$this->logged." WHERE ".UID."= :".UID.";";
		//$this->cmd["insertlgu"] = "INSERT INTO ".$this->logged." (".UID.", ".$this->sid.") VALUES (:".UID.", :".$this->sid.");";
		$this->cmd["insertlgu"] = "INSERT INTO ".$this->logged." (".UID.", ".$this->sid.", ".$this->lgt.", ".$this->llg.") VALUES (:".UID.", :".$this->sid.", :".$this->lgt.", :".$this->llg.");";
		$this->cmd["removelgu"] = "DELETE FROM ".$this->logged." WHERE ".UID." = :".UID.";";
		//$this->cmd["updlgu"] = "UPDATE ".$this->logged." SET ".$this->llg." = '".date(DATE_RFC3339)."' WHERE ".UID."= :".UID.";";
		//$this->cmd["updlgu"] = "UPDATE ".$this->logged." SET ".$this->llg." = '"." CURRENT TIMESTAMP "."' WHERE ".UID."= :".UID.";";
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
		$msgecho .=  "<b>Comandi</b><p>";
		foreach($this->cmd as $k => $x)
			{
			$msgecho .=  "cmd[\"".$k."\"]=".$x."<br>";	
			}
		$msgecho .=  "</p>";

		$msgecho .=  "<b>End of Parent constructor</b><br>";
		
		if($this->debugMode === true)
			echo $msgecho; 
		}
	// Operazioni su database e stato
	//
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
				$this->errore .= "<p><b>"."Errore: ".$e->getMessage()."</b></p>";
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
			error_log("Disconnect");
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
			$this->errore .= "Errore: ".$e->getMessage()."\n";
			$cnt = -1;
			}
		return $cnt;
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
				//$stmt->bindParam(":".UID,$id);
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
				$this->errore .= "Errore: ".$e->getMessage()."\n";
				}
			}
		else
			{
			if($cnt < 1)
				$this->errore .= "Errore: "."Nessun utente trovato"."\n";
			else 
				$this->errore .= "Errore: "."Trovati piu utenti"."\n";
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
			$this->errore .= "Errore: ".$e->getMessage()."\n";
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
			//$ok = true;
			}
		catch(PDOException $e)
			{
			$this->errore .= "Errore: ".$e->getMessage()."\n";
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
				//$this->errore .= "Disconnesso da altro utente\n";
				$this->messaggio .= "Disconnesso da altro utente\n";
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
						$this->timsg = "errore execute()";
						}
					#fare Aggiornare (se necessario) le variabili di sessione LLG ecc...
					}
				catch(PDOException $e)
					{
					$this->timsg = "Errore: ".$e->getMessage()."\n";
					}
				}
			else 
				{
				$this->timsg = "TEMPO SCADUTO";
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
			$this->errore .= "Errore: ".$e->getMessage()."\n";
			}
		return $ok;
		}
	protected function CmdClear($p0,$p1,$p2)			// Esegue clear logged user
		{
		if(isset($_SESSION[UID]) && isset($_SESSION[$this->usrname]))	// se login effettuato dalla sessione attiva...
			{
			$this->Disconnect();						// ... disconnette
			$this->messaggio = $p1." DISCONNESSO";
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
							$this->messaggio = $p1." RIPRISTINATO PER LOGIN";
							}
						else 
							{
							$this->messaggio = $p1." ancora connesso";
							}
						}
					
					#fare ATTENZIONE: Disconnect() solo se ok...? VERIFICARE CHE COSA FARE !
					
					}
				else
					{
					$this->messaggio = $p1." UTENTE o PASSWORD ERRATI";		// Se credenziali errate...
					}
				}
				catch(PDOException $e)
					{
					$this->errore .= "Errore: ".$e->getMessage()."\n";
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
			$this->timsg .= "Errore: ".$e->getMessage()."\n";
			$cnt = -1;
			}
		return $cnt;
		}
	protected function CmdLogin($p0,$p1,$p2)			// Esegue login
		{
		#fare SE LOGIN CON UTENTE GIA` CONNESSO, IL TIMER PARTE LO STESSO
		#fare SE REFRESH CON UTENTE DISCONNESSO DA ALTRO UTENTE, IL TIMER RESTA ATTIVO.
		#fare METTERE UN CAMPO PHP IN JS CHE VERFICA SEMPRE SE DISATTIVARE IL TIMER !
		if($this->IsLogged())
			{
			$this->Clear();
			$this->messaggio = $_SESSION[$this->usrname]." GIA` CONNESSO";
			return;
			}
		$loginOk = $this->GetUserKeys($p1, $p2, $uid, $key, $pwdb);
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
					$this->messaggio = "FALLITA REGISTRAZIONE";
					$this->Disconnect();
					}
				else
					{
					$this->Clear();			// Se tutto ok, cancella gli errori
					$this->messaggio = $p1." CONNESSO";
					}
				}
			else if($lg < 0)
				{
				$this->messaggio = "ERRORE";
				$this->Disconnect();
				}
			else
				{
				$this->messaggio = "UTENTE ".$lg." GIA` CONNESSO";
				//$this->Disconnect();
				}
			}
		else 
			{
			$this->messaggio = $p1." UTENTE o PASSWORD ERRATI";
			$this->Disconnect();
			}
		}
	protected function CmdLogout($p0,$p1,$p2)			// Esegue logout
		{
		if(isset($_SESSION[UID]) && isset($_SESSION[$this->usrname]))	// se login effettuato...
			{
			$this->messaggio = ($_SESSION[$this->usrname])." DISCONNESSO";
			$this->Disconnect();
			}
		else		// ...oppure no
			{
			$this->messaggio = "Nessun utente connesso. Logout superfluo.";
			$this->Disconnect();
			}
		return true;
		}
	protected function CmdRefresh($p0,$p1,$p2)			// Esegue refresh
		{
		if(isset($_SESSION[UID]) && isset($_SESSION[$this->usrname]))	// se login effettuato...
			{
			$ok = $this->RefreshLoggedUser();
			// $_SESSION[$this->llg] = time();
			// COMPLETARE !!!
			if(!$ok)
				{
				$this->Disconnect();
				}
			}
		else		// ...oppure no
			{
			#fare FAR CHIAMARE STOP TIMER CON JSON (O FARLO DIRETTAMENTE DA JS), QUI OPPURE AD OGNI OPERAZIONE (MEGLIO!) 
			// $this->messaggio = "Nessun utente connesso. Refresh superfluo.";
			$this->Disconnect();
			}
		return true;
		}
	protected function GenerateRandom()					// Stringa crittograficamente sicura 512 bit, in esadecimale
		{
		$s = "";										// 32 byte * 8 bit = 256 bit
		$x = openssl_random_pseudo_bytes(32);
		$s = bin2hex($x);	// PHP 7.0: random_bytes(32). Trasforma in stringa esadecimale.
		if(hex2bin($s) != $x)
			$s = "ERRORE";
		return $s;
		}
	// Scambio dati con il client
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
	public function ProcessRequest($p0,$p1,$p2)			// Analizza la richiesta e la esegue
		{
		$done = false;
		switch($p0)
			{
			case CMD_LOGIN:
				$done = $this->CmdLogin($p0,$p1,$p2);
				if($this->IsLogged())
					{
					$this->comando = ID_START;
					$this->data = session_id();
					}
				break;
			case CMD_CLEAR:
				$done = $this->CmdClear($p0,$p1,$p2);
				break;
			case CMD_LOGOUT:
				$done = $this->CmdLogout($p0,$p1,$p2);
				$this->comando = ID_STOP;
				break;
			case CMD_REFRESH:
				$done = $this->CmdRefresh($p0,$p1,$p2);
				break;
			case CMD_COMMAND:
				$this->comando = CMD_COMMAND;
				$this->data = $p1;
				break;
			case CMD_CONNECT:
				if($this->IsLogged())
					{
					$this->comando = CMD_CONNECT;		// Risposta alla richiesta di connessione
					$aestmp = $this->GenerateRandom();	// Ottiene un aes temporaneo (hex)
					$_SESSION[$this->ssk] = $aestmp;	// Lo memorizza prima in aes di sessione
					//$risposta = $aestmp.session_id();	// Aggiunge session ID o timestamp o altro
														// Lo cifra con la chiave privata + timestamp...
					$this->data = $aestmp;				// Lo invia come risposta
					}
				break;
			case CMD_AES:
				$this->comando = CMD_AES;
				if($_SESSION[$this->ssk] == $p1)
					{
					$this->data = $p1;
					}
				else
					{
					$this->data = "Errore in AES";
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
		//else 
		//	$jsn[ID_MSG] = "...";
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
			//$jsn[ID_TEST] = ID_EXE;					// Esegue il comando test [disabilitato]
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
				{
				$jsn[CMD_COMMAND] = "Risposta da command al messaggio: <".$this->data.">";
				}
				break;
			case CMD_CONNECT:
				{
				$jsn[CMD_CONNECT] = $this->data;
				}
				break;
			case CMD_AES:
				{
				$jsn[CMD_AES] = $this->data;
				}
				break;
			case ID_START:
				{
				$jsn[$this->comando] = $this->data;
				}
				break;
			default:
				$jsn[$this->comando] = ID_EXE;
				break;
			}
			
		//CONTROLLARE !!!! $THIS->DATA SEMBRA VUOTO... VEDERE PROCESSREQUEST()
#		ERRORE: COMPLETARE CODIFICA DI:
#		COMANDO GENERICO: $JSN[comando] = ID_EXE
#		COMANDO SPECIFICO: $JSN[CMD_COMMAND] = "DATI"
			
		echo json_encode($jsn);
		}
	}
?>	