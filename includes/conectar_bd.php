<?php

//Utilizamos PDO para evitar el SQLInjection.
function conectarBD($bd) {
	try{
		$dbConnection = new PDO('mysql:dbname='.$bd.';host=127.0.0.1;charset=utf8', 'user', 'password');
		$dbConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}catch(PDOException $e){
		echo"Conexión fallida ".$e->getMessage();
		die();
	}	
	return $dbConnection;
}

?>

