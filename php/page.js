var refresh_timer;						// Timer globale

function init()							// Imposta la pagina
	{
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
	refresh_timer = null;				// Azzera oggetto timer
	return;
	}
function stopTimer()
	{
	if(refresh_timer == null)
		{
		return;
		}
	clearInterval(refresh_timer);
	refresh_timer = null;
	alert("stop timer");
	}
function startTimer()
	{
	if(refresh_timer == null)
		{
		refresh_timer = setInterval(refreshRequest, "<?php echo $x->refri; ?>");
		alert("start timer");
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
		case "<?php echo ID_TEST;?>":				// Comandi dal sito
			alert("Test!");
			break;
		case "<?php echo ID_START;?>":
			startTimer();
			break;
		case "<?php echo ID_STOP;?>":
			stopTimer();
			break;
		}
	
	}
$(document).ready(function()			// Dopo caricamento della pagina
		{
		init();
		showHideUnLogged();				// Mostra/nasconde se (un)logged + timer restart dopo refresh
		}
	); 
function refreshRequest()					// Esegue refresh del timer
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
			if(o == '+')				// Mostra oggetti di classe .i
				{
				$("."+i).show();
				}
				
			else if(o == '-')			// Nasconde oggetti di classe .i
				{
				$("."+i).hide();
				}
			else if(o == '*')			// Esegue il comando i
				{
				command(i);
				}
			else
				$("#"+i).text(o);		// Imposta il testo dell'oggetto #i al valore o
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
