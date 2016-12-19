var refresh_timer;						// Timer globale
var crypt;								// Crittografatore a chiave RSA

function init()							// Imposta la pagina
	{									// Comandi associati ai pulsanti
	$("#<?php echo CMD_LOGIN;?>").click(function(event)
			{
			command("<?php echo CMD_LOGIN;?>");
			}
		);
	$("#<?php echo CMD_CLEAR;?>").click(function(event)
			{
			command("<?php echo CMD_CLEAR;?>");
			}
		);
	$("#<?php echo CMD_RELOAD;?>").click(function(event)
			{
			command("<?php echo CMD_RELOAD;?>");
			}
		);
	$("#<?php echo CMD_LOGOUT;?>").click(function(event)
			{
			command("<?php echo CMD_LOGOUT;?>");
			}
		);
	$("#<?php echo ID_TEST;?>").click(function(event)
			{
			command("<?php echo ID_TEST;?>");
			}
		);
	$("#<?php echo ID_COMMAND;?>").click(function(event)
			{
			p1 = "<?php echo TESTO_PROVA;?>";
			command("<?php echo CMD_COMMAND;?>");
			}
		);
	refresh_timer = null;				// Azzera oggetto timer
	ALPH = Caratteri(' ','~');
	crypt = null;
	return;
	}
function Caratteri(da,a)
	{
	var s = "";
	for(i=da.charCodeAt(0); i<=a.charCodeAt(0); i++)
		{
		s += String.fromCharCode(i);
		}
	return s;
	}
function stopTimer()					// Ferma il timer
	{
	if(refresh_timer == null)
		{
		return;
		}
	clearInterval(refresh_timer);
	refresh_timer = null;
	}
function startTimer()					// Avvia il timer di refresh
	{
	if(refresh_timer == null)
		{
		refresh_timer = setInterval(refreshRequest, "<?php echo $x->refri; ?>");
		}
	else
		{
		alert("timer already started");
		}
	}
function command(cmd)					// Esegue un comando
	{
	url = "<?php echo $_SERVER['REQUEST_URI']; ?>";
	switch(cmd)
		{
		case "<?php echo CMD_LOGIN;?>":
			p1 = $("#usr").val();
			p2 = CryptoJS.SHA1($("#pwd").val());
			sendRequest(url,cmd,p1,p2);
			sendRequest(url,'refresh',"","");
			break;
		case "<?php echo CMD_CLEAR;?>":
		case "<?php echo CMD_LOGOUT;?>":
			p1 = $("#usr").val();
			p2 = CryptoJS.SHA1($("#pwd").val());
			sendRequest(url,cmd,p1,p2);
			break;
		case "reload":
			location.reload(true);
			break;
		case "<?php echo ID_TEST;?>":				// Comando di prova
			alert("sid= "+sessionStorage.sid +"\n aes= "+CryptoJS.enc.Utf8.stringify(CryptoJS.enc.Base64.parse(sessionStorage.aes)));
			break;
		case "<?php echo ID_START;?>":				// Avvio timer + richiesta di connessione
			startTimer();
			command("<?php echo CMD_CONNECT;?>");	// Richiesta di connessione
			command
			break;
		case "<?php echo ID_STOP;?>":				// Stop timer
			stopTimer();
			break;
		case "<?php echo CMD_COMMAND;?>":			// Command
			{
			messaggio = EncryptMessage(p1,sessionStorage.aes);
			sendRequest(url,cmd,messaggio,"");
			}
			break;
		case "<?php echo CMD_CONNECT;?>":			// Connect
			GenerateRSAkeys();						// Genera coppia di chiavi RSA						
			sendRequest(url,"<?php echo CMD_CONNECT;?>",sessionStorage.puk,"");		// Richiesta di connessione	
			break;
		case "<?php echo CMD_AES;?>":				// AES
			SetPUK();
			enc = crypt.encrypt(sessionStorage.aes);	// Crittografa l'aes della sessione, e lo invia
			sendRequest(url,cmd,enc,"");
			break;
		case "<?php echo CMD_AESPK;?>":				// Richiesta chiave pubblica per verifica
			sendRequest(url,cmd,"","");
			break;
		}
	}
function processJsonData(data)			// Analizza risposta del server conseguente alla richiesta 
	{
	$msg = "";
	datiObj = JSON.parse(data);
	$.each(datiObj,function(i,o)
			{
			switch(i)
				{
				case '<?php echo CMD_COMMAND;?>':		// Risposta del server a Command
					{
					decstr = DecryptMessage(o,sessionStorage.aes);
					alert("Risposta= "+decstr);
					}
					break;
				case '<?php echo CMD_CONNECT;?>':			// Risposta del server a Connect:...
					{										// ...chiave aes + sid crittografati con chiave pubblica
					dec = crypt.decrypt(o);					// Decifra con l'oggetto crypt precedente (poi GenerateRSAkeys())
					sessionStorage.aes = dec.substr(0,'<?php echo AESSIZE64;?>');	// Salva l'aes base64 nella sessione
					if(dec.substr('<?php echo AESSIZE64;?>') != sessionStorage.sid)	// Verifica che l'sid corrisponda
						{
						alert("sid not matching");
						}
					else
						{
						command('<?php echo CMD_AESPK;?>');	// Esegue la richiesta di verifica dell'aes, come conferma
						}
					}
					break;
				case '<?php echo CMD_AES;?>':				// Riceve la risposta del server alla richista di conferma dell'aes
					{
					if(o != CryptoJS.SHA1(sessionStorage.aes))
						alert("aes errato");
					else
						{
						xx = CryptoJS.enc.Utf8.stringify(CryptoJS.enc.Base64.parse(sessionStorage.aes));
						alert(i + "(from base64): " + xx);
						}
					}
					break;
				case '<?php echo CMD_AESPK;?>':				// Riceve la chiave pubblica dal server
					{
					sessionStorage.puk = o;					// Le memorizza
					command('<?php echo CMD_AES;?>');		// Esegue la richiesta di verifica dell'aes,
					}
				break;
				case "<?php echo ID_START;?>":			// Cattura il comando start (con argomento)
					{
					sessionStorage.sid = o;				// Imposta la session id
					command(i);							// Esegue il comando start
					}
					break;
				default:
					{
					if(o == '+')				// Mostra oggetti di classe .i
						{
						$("."+i).show();
						}
					else if(o == '-')			// Nasconde oggetti di classe .i
						{
						$("."+i).hide();
						}
					else if(o == '*')			// Esegue il comando i (senza parametri)
						{
						command(i);
						}
					else
						$("#"+i).text(o);		// Imposta il testo dell'oggetto #i al valore o
					}
					break;
				}
			}
		)
	}
$(document).ready(function()			// Dopo caricamento della pagina
		{
		init();
		showHideUnLogged();				// Mostra/nasconde se (un)logged + timer restart dopo refresh
		}
	); 
function refreshRequest()				// Esegue refresh del timer
	{
	sendRequest("<?php echo $_SERVER['REQUEST_URI']; ?>",'refresh',"","");
	}
function sendRequest(url,p0,p1,p2)		// Invia richiesta xmlhttprequest al server
	{
	var dati = new FormData();
		dati.append('<?php echo P0; ?>',p0);
		dati.append('<?php echo P1; ?>',p1);
		dati.append('<?php echo P2; ?>',p2);
	var u = "<?php echo $_SERVER['REQUEST_URI']; ?>";
	$.ajax	({
	    		url : u,
	    		type: "POST",
	    		data: dati,
	    		contentType: false,
	    		processData: false
			})
			.done( function(data)
						{
						processJsonData(data);
						}
			)
			.fail( function()
					{
					alert("ajax fail")
					}
			)
	}

function DecryptMessage(msg,aes64)
	{
	iv64 = msg.substr(0,'<?php echo IVSIZE64;?>');
	enc = msg.substr('<?php echo IVSIZE64;?>');
	iv = CryptoJS.enc.Utf8.parse(CryptoJS.enc.Utf8.stringify(CryptoJS.enc.Base64.parse(iv64)));
	
	key= CryptoJS.enc.Utf8.parse(CryptoJS.enc.Utf8.stringify(CryptoJS.enc.Base64.parse(aes64)));
	opt = { iv: iv, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 };
	//enccfr = CryptoJS.AES.encrypt("<?php echo TESTO_PROVA;?>", key, opt);
	//deccfr = CryptoJS.AES.decrypt(enccfr, key, opt);
	//decstrcfr = deccfr.toString(CryptoJS.enc.Utf8);
	dec = CryptoJS.AES.decrypt(enc, key, opt);
	decstr = dec.toString(CryptoJS.enc.Utf8);
	return decstr;
	}
function GenerateRndSeq(len)
	{
	s = "";
	nch = ALPH.length;
	// CryptoJS.lib.WordArray.random(len) genera i numer usando Math.random, crittograficamente non sicuro
	// window.crypto.getRandomValues non è implementato su tutti i browser
	// Si genera in modo non sicuro solo l'iv, non la chiave.
	// Per ora implemento banalmente
	n = 0;
	for(i=0; i<len; i++)
		{
		n = Math.floor(Math.random()*nch);
		x = ALPH.charAt(n);
		s += x;
		}
	return s;
	}
function GenerateSeq(len)
	{
	s = "";
	nch = ALPH.length;
	n = 0;
	for(i=0; i<len; i++)
		{
		x = ALPH.charAt(n);
		s += x;
		n++;
		if(n > nch-1)
			n=0;
		}
	return s;
	}
function GenerateRandom(len)
	{
	s = "";
	s = GenerateRndSeq(len);	//Per test: s = GenerateSeq(len);
	return s;
	}
function GenerateRSAkeys()
	{
	crypt = new JSEncrypt({ default_key_size: '<?php echo RSAKEYSIZE; ?>' });
	crypt.getKey();
	sessionStorage.puk = crypt.getPublicKey();	// La chiave privata crypt.getPrivateKey() non viene salvata
	}
function SetPUK()
	{
	crypt = new JSEncrypt();
	crypt.setKey(sessionStorage.puk);
	}
function EncryptMessage(msg,aes64)
	{
	var ivstr = GenerateRandom('<?php echo IVSIZE;?>');
	var iv = CryptoJS.enc.Utf8.parse(ivstr);
	var iv64 = CryptoJS.enc.Base64.stringify(iv);
	key= CryptoJS.enc.Utf8.parse(CryptoJS.enc.Utf8.stringify(CryptoJS.enc.Base64.parse(aes64)));
	var opt = { iv: iv, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 };
	var enc = CryptoJS.AES.encrypt(msg, key, opt);
	return iv64.concat(enc);
	}
function showHideUnLogged()
	{
	$l = "<?php echo $x->IsLogged(); ?>";
	if($l)
		{
		$(".<?php echo ID_LOGGED;?>").show();
		$(".<?php echo ID_UNLOGGED;?>").hide();
		startTimer();		// Riabilita il timer, solo se è logged
		}
	else
		{
		$(".<?php echo ID_LOGGED;?>").hide();
		$(".<?php echo ID_UNLOGGED;?>").show();
		}
	
	}
