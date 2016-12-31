<?php 
session_start();
include_once 'const.inc.php';
include 'php/crycon.inc.php';
$x = new Crycon(false);
$po = $p1 = $p2 = "";
$ret = $x->ReadRequest($p0, $p1, $p2);
if(!$ret)
	{
	?>
	<!DOCTYPE html>
	<html>
	<head>
		<meta charset="ISO-8859-1">
		<title>Page</title>
		<?php include 'script.php'; ?>
		<script type="text/javascript"> <?php include 'page.js'; ?> </script>
		<style>
			td {border: 1px solid black;}
		</style>
	</head>
	<body>
		<h1>Page</h1>
		<div>
			<table class="logstat">
				<col width="200">
				<col width="300">
				<col width="200">
				<col width="200">
				<tr>
				<td id="<?php echo ID_LOGMSG;?>"><?php echo $x->LoggedMessage(); ?></td>
				<td id="<?php echo ID_MSG;?>"></td>
				<td id="<?php echo ID_ERR;?>"></td>
				<td id="<?php echo ID_TIMER;?>"><?php echo $x->TimerValue();?></td>
				<td id="<?php echo ID_TIMERMSG;?>"><?php echo $x->TimerMsg();?></td>
				</tr>
			</table>
			<form class="<?php echo ID_UNLOGGED;?>" id="loginform"> 
			User: <input type='text' id='usr' value=''><br>
			Password: <input type='password' id='pwd'><br>
			</form>
			<button class="<?php echo ID_UNLOGGED;?>" id="<?php echo CMD_LOGIN;?>">Login</button>
			<button class="<?php echo ID_UNLOGGED;?>" id="<?php echo CMD_CLEAR;?>">Clear</button>			
			<button class="<?php echo ID_UNLOGGED;?>" id="<?php echo CMD_RELOAD;?>">Reload</button>
			<button id="<?php echo ID_VKEYS;?>">See puK</button>
			<button class="<?php echo ID_LOGGED;?>" id="<?php echo CMD_LOGOUT;?>">Logout</button>
			<p class="<?php echo ID_UNLOGGED;?>">Public key: <input type="file" id="<?php echo ID_FILEPUK;?>" name="<?php echo ID_FILEPUK;?>[]" /></p>
		</div>
		<div class="<?php echo ID_LOGGED;?>">
		<p>User functions:</p>
		<button id="<?php echo ID_TEST;?>">Test</button>
		<button id="<?php echo ID_COMMAND;?>">Command</button>
		<button id="<?php echo CMD_RSTK;?>">Reset keys</button>
		<button id="<?php echo CMD_PK;?>">New keys</button>
		<button id="<?php echo CMD_KOK;?>">Confirm keys</button>
		<a id="<?php echo DWN_PUK;?>">PublicKey</a>
		</div>
	</body>
	</html>
	<?php 
	}
else
	{
	$x->ProcessRequest($p0,$p1,$p2);	
	$x->SendResponse();
	}
?>