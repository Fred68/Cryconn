var refresh_timer;						// Timer globale
var crypt;								// Crittografatore a chiave RSA
var localPuK;							// Chiave pubblica
var reader, file, txtreader, frdone;	// Per filereaded
var p1, p2;								// Comandi

$(document).ready(function()			// Dopo caricamento della pagina
		{
		init();							// Inizializza
		showHideUnLogged();				// Mostra/nasconde se (un)logged + timer restart dopo refresh
		if(!prepareFileReader())		// Verifica se il browser può leggere i file 
			{
			$(".<?php echo ID_LOGGED;?>").hide();
			$(".<?php echo ID_UNLOGGED;?>").hide();
			}
		}
	);

function init()							// Imposta la pagina
	{									// Comandi associati ai pulsanti
	$("#<?php echo CMD_LOGIN;?>").click(function(event)
			{
			command("<?php echo CMD_LOGIN;?>");
			});
	$("#<?php echo CMD_CLEAR;?>").click(function(event)
			{
			command("<?php echo CMD_CLEAR;?>");
			});
	$("#<?php echo CMD_RELOAD;?>").click(function(event)
			{
			command("<?php echo CMD_RELOAD;?>");
			});
	$("#<?php echo CMD_LOGOUT;?>").click(function(event)
			{
			command("<?php echo CMD_LOGOUT;?>");
			});
	$("#<?php echo ID_TEST;?>").click(function(event)
			{
			command("<?php echo ID_TEST;?>");
			});
	$("#<?php echo ID_COMMAND;?>").click(function(event)
			{
			p1 = "<?php echo TESTO_PROVA;?>";
			command("<?php echo CMD_COMMAND;?>");
			});
	$("#<?php echo CMD_PK;?>").click(function(event)
			{
			p1 = "<?php echo CMD_PK;?>";
			command("<?php echo CMD_COMMAND;?>");
			});
	$("#<?php echo DWN_PUK;?>").click(function(event)
			{
			$("#<?php echo DWN_PUK;?>").hide();
			});
	$("#<?php echo ID_VKEYS;?>").click(function(event)
			{
			alert("PuK=\n"+localPuK);
			});
	$("#<?php echo CMD_KOK;?>").click(function(event)
			{
			p1 = "<?php echo CMD_KOK;?>";
			command("<?php echo CMD_COMMAND;?>");
			});
	$("#<?php echo CMD_RSTK;?>").click(function(event)
			{
			p1 = "<?php echo CMD_RSTK;?>";
			command("<?php echo CMD_COMMAND;?>");
			});
	$("#<?php echo DWN_PUK;?>").hide();
	$("#<?php echo CMD_KOK;?>").hide();
	$("#<?php echo ID_FILEPUK;?>").change({typ:"<?php echo ID_FILEPUK;?>"},filechange);
	refresh_timer = null;
	ALPH = Caratteri(' ','~');
	crypt = null;
	localPuK = "";
	frdone = true;
	return;
	}
function filechange(e)
	{
	if(!frdone)
		{
		alert("Lettura dati in corso");
		return;
		}
	frdone = false;
	var typ = e.data.typ;
	file = $("#"+e.data.typ)[0].files[0];	//$("#"+e.data.typ).val() fake path
	reader = new FileReader();
	reader.onload = function()				// Funzione anonima, per non definire typ globale
		{
		txtreader = reader.result;
		switch(typ)
			{
			case "<?php echo ID_FILEPUK;?>":
				localPuK = txtreader;
				break;
			default:
				return;
				break;
			};
		frdone = true;
		}
	reader.readAsText(file);
	}
function prepareFileReader()
	{
	var ok = true;
	if ((!window.File) || (!window.FileReader) || (!window.FileList) || (!window.Blob))
		{
		ok = false;
		alert('File APIs not working in this browser');
		}
	return ok;
	}
function Caratteri(da,a)
	{
	var s = "";
	for(var i = da.charCodeAt(0); i <= a.charCodeAt(0); i++)
		{
		s += String.fromCharCode(i);
		}
	return s;
	}
function stopTimer()					// Ferma il timer
	{
	if(refresh_timer == null)			// Controlla null e undefined
		{
		return;
		}
	clearInterval(refresh_timer);
	refresh_timer = null;
	}
function startTimer()					// Avvia il timer di refresh
	{
	if(refresh_timer == null)			// Controlla null e undefined
		{
		refresh_timer = setInterval(refreshRequest, "<?php echo $x->refri; ?>");
		}
	else
		{
		alert("Timer already started");
		}
	}
function hashPassword(pw)
	{
	var ret;
	var pwh = "";
	var pwhw = CryptoJS.SHA1(pw);		// Hash della password (wordarray)
	var pwh = pwhw.toString(CryptoJS.enc.Hex);	// Hash della password (string)
	
	if(localPuK.length > 1)				// Se c'é la chiave privata, crittografa l'hash									
		{
		crypt = new JSEncrypt();
		crypt.setKey(localPuK);			// Imposta chiave pubblica
		ret = crypt.encrypt(pwh);		// Crittografa l'hash della password con la chiave
		}
	else
		{
		ret = pwh;
		if(!confirm("Public key is missing.\nProceed ?"))
			{
			ret = "";
			}
		}
	return ret;
	}
function command(cmd)					// Esegue un comando
	{
	var url = "<?php echo $_SERVER['REQUEST_URI']; ?>";
	switch(cmd)
		{
		case "<?php echo CMD_LOGIN;?>":
			p1 = $("#usr").val();
			p2 = hashPassword($("#pwd").val());						// Hash della password (criptato con chiave privata, se non nulla)
			sendRequest(url,cmd,p1,p2);								// Richiesta di login
			sendRequest(url,"<?php echo CMD_REFRESH;?>","","");		// Subito un refresh 'refresh'
			$("#<?php echo ID_FILEPUK;?>").val("");					// Azzera subito i pulsanti di scelta file
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
			break;
		case "<?php echo ID_STOP;?>":				// Stop timer
			stopTimer();
			break;
		case "<?php echo CMD_COMMAND;?>":			// Command
			{
			var messaggio = EncryptMessage(p1,sessionStorage.aes);
			sendRequest(url,cmd,messaggio,"");
			}
			break;
		case "<?php echo CMD_CONNECT;?>":			// Connect
			GenerateRSAkeys();						// Genera coppia di chiavi RSA						
			sendRequest(url,"<?php echo CMD_CONNECT;?>",sessionStorage.puk,"");		// Richiesta di connessione	
			break;
		case "<?php echo CMD_AES;?>":				// AES
			SetPUK();
			var enc = crypt.encrypt(sessionStorage.aes);	// Crittografa l'aes della sessione, e lo invia
			sendRequest(url,cmd,enc,"");
			break;
		case "<?php echo CMD_AESPK;?>":				// Richiesta chiave pubblica per verifica
			sendRequest(url,cmd,"","");
			break;
		}
	}
function processJsonData(data)			// Analizza risposta del server conseguente alla richiesta 
	{
	var datiObj = JSON.parse(data);
	$.each(datiObj,function(i,o)
			{
			switch(i)
				{
				case '<?php echo CMD_COMMAND;?>':		// Risposta del server a Command
					{
					var decstr = DecryptMessage(o,sessionStorage.aes);
					ProcessMessage(decstr);
					}
					break;
				case '<?php echo CMD_CONNECT;?>':			// Risposta del server a Connect:...
					{										// ...chiave aes + sid crittografati con chiave pubblica
					var dec = crypt.decrypt(o);				// Decifra con l'oggetto crypt precedente
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
						alert("Wrong AES");
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
	var iv64 = msg.substr(0,'<?php echo IVSIZE64;?>');
	var enc = msg.substr('<?php echo IVSIZE64;?>');
	var iv = CryptoJS.enc.Utf8.parse(CryptoJS.enc.Utf8.stringify(CryptoJS.enc.Base64.parse(iv64)));	
	var key= CryptoJS.enc.Utf8.parse(CryptoJS.enc.Utf8.stringify(CryptoJS.enc.Base64.parse(aes64)));
	var opt = { iv: iv, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 };
	var dec = CryptoJS.AES.decrypt(enc, key, opt);
	var decstr = dec.toString(CryptoJS.enc.Utf8);
	return decstr;
	}
function GenerateRndSeq(len)
	{
	var s = "";
	var nch = ALPH.length;
	// CryptoJS.lib.WordArray.random(len) genera i numer usando Math.random, crittograficamente non sicuro
	// window.crypto.getRandomValues non è implementato su tutti i browser
	// Si genera in modo non sicuro solo l'iv, non la chiave.
	// Per ora implemento banalmente
	var n = 0;
	for(var i=0; i<len; i++)
		{
		var n = Math.floor(Math.random()*nch);
		var x = ALPH.charAt(n);
		s += x;
		}
	return s;
	}
function GenerateSeq(len)
	{
	var s = "";
	var nch = ALPH.length;
	var n = 0;
	for(var i=0; i<len; i++)
		{
		var x = ALPH.charAt(n);
		s += x;
		n++;
		if(n > nch-1)
			n=0;
		}
	return s;
	}
function GenerateRandom(len)
	{
	var s = "";
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
	crypt = new JSEncrypt({ default_key_size: '<?php echo RSAKEYSIZE; ?>' });
	crypt.setKey(sessionStorage.puk);
	}
function ExtractKey(txt,ini,end)
	{
	var ret = "";
	var ixi = txt.indexOf(ini);
	var ixf = txt.indexOf(end);
	if((ixi!==-1)&&(ixf!==-1))
		{
		ixf = ixf + end.length;
		ret = txt.substring(ixi,ixf);
		}
	return ret;
	}
function EncryptMessage(msg,aes64)
	{
	var ivstr = GenerateRandom('<?php echo IVSIZE;?>');
	var iv = CryptoJS.enc.Utf8.parse(ivstr);
	var iv64 = CryptoJS.enc.Base64.stringify(iv);
	var key= CryptoJS.enc.Utf8.parse(CryptoJS.enc.Utf8.stringify(CryptoJS.enc.Base64.parse(aes64)));
	var opt = { iv: iv, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 };
	var enc = CryptoJS.AES.encrypt(msg, key, opt);
	return iv64.concat(enc);
	}
function showHideUnLogged()
	{
	var lg = "<?php echo $x->IsLogged(); ?>";
	if(lg)
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
function ProcessMessage(m)
	{
	var n = m.indexOf("=");
	if(n === -1)
		{
		alert("Bad formed message");
		return;
		}
	var cmd = m.substr(0,n);
	m = m.substr(n+1);
	switch(cmd)
		{
		case "<?php echo CMD_PK;?>":				// Risposta con la chiave pubblica generata
			var txtpu = ExtractKey(m,"-----BEGIN PUBLIC KEY-----","-----END PUBLIC KEY-----");
			$("#<?php echo DWN_PUK;?>").show();
			$("#<?php echo CMD_KOK;?>").show();
			$("#<?php echo DWN_PUK;?>").attr( {href:window.URL.createObjectURL(new Blob([txtpu], {type: 'text/plain'})),download:"<?php echo FIL_PUK;?>"});
			break;
		case "<?php echo CMD_KOK;?>":				// Conferma che le chiavi sono state memorizzate
			$("#<?php echo DWN_PUK;?>").hide();
			$("#<?php echo CMD_KOK;?>").hide();
			localPuK = "";
			alert(m);
			break;
		case "<?php echo CMD_RSTK;?>":				// Conferma che le chiavi sono state azzerate
			localPuK = "";
			alert(m);
			break;
		default:									// Comando generico
			alert("Comando "+cmd+"\n"+m);
			break;
		}
	}
