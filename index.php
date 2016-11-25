<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>Demo 01</title>
	<style>
		a.test {font-weight: bold;}
	</style>
</head>
<body>
	<a id="main" href="">main</a><br/>
	<a id="reset" href="">reset</a><br/>
	<a id="admin" href="">admin</a><br/>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
	<script>
	$(document).ready(function()
		{
		$("#main").attr('href','main.php');
		$("#reset").attr('href','reset.php');
		$("#admin").attr('href','phpliteadmin/phpliteadmin.php');
		}
	);
	</script>
</body>
</html>