<?php session_start(); ?>
<?php
	$idioma = $_POST['lang'];	
	$_SESSION['lang'] = $idioma;
	//print_r($_SESSION);
?>