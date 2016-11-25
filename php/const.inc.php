<?php

// Parametri del POST
define('P0',"pp0");
define('P1',"pp1");
define('P2',"pp2");

// Nomi per accesso al database
define('UID',"id");

// Costanti
define('IVSIZE',16);		// 16 B = 128 b
define('IVSIZE64',24);		// 16 * 4/3 arrotondato al multiplo di 4 superiore
define('AESSIZE',32);		// 32 * 8 = 256 bit
define('AESSIZE64',44);		// 32 * 8 = 256 bit

define('TESTO_PROVA',"Testo di prova per verificare il funzionamento della crittografia");

// Valori possibili per lo stato della connessione


// Valori delle stringhe dei messaggi e degli id dell'html
define('ID_MSG',"messaggio");
define('ID_ERR',"errore");
define('ID_LOGMSG',"logged");
define('ID_LOGGED',"loggedin");
define('ID_UNLOGGED',"unlogged");
define('ID_TIMER',"timer");
define('ID_TIMERMSG',"timermsg");
define('ID_EXE',"*");
define('ID_TEST',"test");
define('ID_START',"start");
define('ID_STOP',"stop");
define('ID_COMMAND',"command");
define('ID_CONNECT',"connect");

define('CMD_LOGIN',"login");
define('CMD_CLEAR',"clear");
define('CMD_LOGOUT',"logout");
define('CMD_REFRESH',"refresh");
define('CMD_RELOAD',"reload");
define('CMD_COMMAND',"cmd");
define('CMD_RQCONNECT',"rqcnt");
define('CMD_CONNECT',"cnt");
define('CMD_AES',"aes");

