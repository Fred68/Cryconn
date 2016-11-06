var refresh_timer;						// Timer globale

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
			command("<?php echo CMD_COMMAND;?>");
			}
		);
	refresh_timer = null;				// Azzera oggetto timer
	return;
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
			alert("sid= "+sessionStorage.sid);
			break;
		case "<?php echo ID_START;?>":				// Avvio timer + richiesta di connessione
			startTimer();
			sendRequest(url,"<?php echo CMD_CONNECT;?>","","");				// Richiesta di connessione				
			break;
		case "<?php echo ID_STOP;?>":				// Stop timer
			stopTimer();
			break;
		case "<?php echo CMD_COMMAND;?>":			// Command
			sendRequest(url,cmd,"messaggio","");
			break;
		case "<?php echo CMD_CONNECT;?>":			// Connect
			sendRequest(url,cmd,"","");
			break;
		case "<?php echo CMD_AES;?>":				// AES
			sendRequest(url,cmd,sessionStorage.aes,"");
			break;
		}
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
					alert(i + ": " + o);
					}
					break;
				case '<?php echo CMD_CONNECT;?>':		// Risposta del server a Connect: la chiave aes
					{									// (crittografata a doppia chiave: DA FARE)
					sessionStorage.aes = o;				// La salva nella sessione
					command('<?php echo CMD_AES;?>');	// Esegue il comando di risposta all'aes
					//alert(i + ": " + o);
					//sendRequest("<?php echo $_SERVER['REQUEST_URI']; ?>",'<?php echo CMD_AES;?>',o,"");
					}
					break;
				case '<?php echo CMD_AES;?>':
					{
					alert(i + ": " + o);
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

function hex2bin(hex)
	{
    var bytes = [], str;

    for(var i=0; i< hex.length-1; i+=2)
        bytes.push(parseInt(hex.substr(i, 2), 16));

    return String.fromCharCode.apply(String, bytes);    
	}