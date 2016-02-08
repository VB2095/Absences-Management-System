<?php
session_start();

/*
session_start();
if(isset($_SESSION['nombre']))
{
	$_SESSION = array();
	session_destroy();
}
header("Location:index.php");
*/



//Si la sesión no estaba iniciada, la iniciamos conlos datos que nos pasan:
if(!isset($_SESSION["cod_profesor"])){
	$cod_profesor = $_POST["cod_profesor"];
	$password = $_POST["password"];
	//Hash del password:
	$password = sha1($password);
	//echo $cod_profesor." ".$password;

	include_once('conectar_bd.php');
	$dbConnection = conectarBD(actividades_extraescolares);
	$consultaLogin = $dbConnection->prepare('SELECT tipo, cod_profesor, nombre_completo FROM profesores WHERE cod_profesor =:cod_profesor AND password = :password;');	

	$consultaLogin->execute(array('cod_profesor' => $cod_profesor, 'password' => $password));
	//echo $consultaLogin->rowCount()."<br>";
	//Si la fila da un resultado significa que el usuario introducido en configuracion.php es válido.			
	if($consultaLogin->rowCount()==1){
		
		//Nos aseguramos de que el usuario tipo de usuario e iniciamos la sesión:
		$row = $consultaLogin->fetch(PDO::FETCH_ASSOC);
		echo "nombre". $row["nombre_completo"];
		$tipo = $row[tipo];
		$_SESSION['cod_profesor'] = $row['cod_profesor'];
		$_SESSION['tipo'] = $row['tipo'];
		$_SESSION['nombre_completo'] = $row['nombre_completo'];
		//echo "true";
		//$sesion = array ("sesion_iniciada"=>"true","cod_profesor"=>$cod_profesor,"tipo"=>$tipo);
		$sesion = array ();
		$sesion ["sesion_iniciada"] = "true";
		$sesion ["cod_profesor"] = $_SESSION['cod_profesor'];
		$sesion ["tipo"] = $_SESSION['tipo'];
		$sesion ["nombre_completo"] = $_SESSION['nombre_completo'];
		
		//También incluímos a fake_cod_profesor aunque no está iniciado:
		$sesion ["fake_cod_profesor"] = "falso";
		
		echo json_encode($sesion);
	}else{
		$sesion = array ("sesion_iniciada"=>"false","cod_profesor"=>"","tipo"=>"","nombre_completo"=>"", "fake_cod_profesor"=>"falso",);
		echo json_encode($sesion);
		//echo "false";
	}

}else{//Si ya estaba iniciada devolvemos un json con sus datos, dependiendo de fake_cod_profesor.
	if(isset($_SESSION['fake_cod_profesor'])) {
		$sesion = array ("sesion_iniciada"=>"true","cod_profesor"=>$_SESSION['cod_profesor'],"tipo"=>$_SESSION['tipo'],"nombre_completo"=>$_SESSION['nombre_completo'],"fake_cod_profesor"=>$_SESSION['fake_cod_profesor']);
	}else{
		$sesion = array ("sesion_iniciada"=>"true","cod_profesor"=>$_SESSION['cod_profesor'],"tipo"=>$_SESSION['tipo'],"nombre_completo"=>$_SESSION['nombre_completo'],"fake_cod_profesor"=>"falso");
	}
	echo json_encode ($sesion);
	
}

?>