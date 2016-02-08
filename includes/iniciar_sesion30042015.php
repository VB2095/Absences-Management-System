<?php
session_start(); 
function iniciar_sesion ($cod_profesor, $password) {
	//Sesión genérica:
	if (isset($_SESSION['cod_profesor']) == false && !isset($cod_profesor)){
		include_once('conectar_bd.php');
		include_once('configuracion.php');
		$dbConnection = conectar(actividades_extraescolares);
		$consultaLogin = $dbConnection->prepare('SELECT tipo, cod_profesor, nombre_completo, cod_departamento FROM profesores WHERE cod_profesor =:cod_profesor AND password = :password;');	
		//Hash del password:
		$password = sha1($password_profesor_generico);
		$consultaLogin->execute(array('cod_profesor' => $cod_profesor_generico, 'password' => $password));

		//Si la fila da un resultado significa que el usuario introducido en configuracion.php es válido.			
		if($consultaLogin->rowCount()==1){
			
			//Nos aseguramos de que el usuario el usuario es genérico e iniciamos la sesión:
			$row = $consultaLogin->fetch(PDO::FETCH_ASSOC);
			if($row[tipo] == 'G'){
				
				//Guardamos los datos de la sesión:
				$_SESSION['cod_profesor'] = $row['cod_profesor'];
				$_SESSION['tipo'] = $row['tipo'];				
				$_SESSION['nombre_completo'] = $row['nombre_completo'];				
				$_SESSION['cod_departamento'] = $row['cod_departamento'];
				$exito = true;
				return($exito);
			}else{
				echo "Debe de introducir un usaurio genérico en configuracion.php";
				$exito = false;
				return($exito);			
			}
			
		}else{
			echo "nanai.";
			$exito = false;
			return($exito);		
		}
	
	}
}

$cod_profesor = $_POST["cod_profesor"];
$password = $_POST["password"];
//Sesión genérica:

include_once('conectar_bd.php');
include_once('configuracion.php');
$dbConnection = conectar(actividades_extraescolares);
$consultaLogin = $dbConnection->prepare('SELECT tipo, cod_profesor, nombre_completo, cod_departamento FROM profesores WHERE cod_profesor =:cod_profesor AND password = :password;');	
//Hash del password:
//$password = sha1($password_profesor_generico);
$consultaLogin->execute(array('cod_profesor' => $cod_profesor, 'password' => $password));

//Si la fila da un resultado significa que el usuario introducido en configuracion.php es válido.			
if($consultaLogin->rowCount()==1){
	
	//Nos aseguramos de que el usuario el usuario es genérico e iniciamos la sesión:
	$row = $consultaLogin->fetch(PDO::FETCH_ASSOC);
	if($row[tipo] == 'G'){
		
		//Guardamos los datos de la sesión:
		$_SESSION['cod_profesor'] = $row['cod_profesor'];
		$_SESSION['tipo'] = $row['tipo'];				
		//$_SESSION['nombre_completo'] = $row['nombre_completo'];				
		//$_SESSION['cod_departamento'] = $row['cod_departamento'];
		$exito = true;
		return($exito);
	}else{
		echo "Debe de introducir un usaurio genérico en configuracion.php";
		$exito = false;
		return($exito);			
	}
	
}else{
	echo "nanai.";
	$exito = false;
	return($exito);		
}



?>