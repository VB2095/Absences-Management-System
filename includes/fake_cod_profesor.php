<?php session_start(); ?>
<?php
include_once('conectar_bd.php');

//Ahora nos conectamos (creamos el objeto) a actividades extraescolares:
$dbConnection_actividades_profesores = conectarBD(actividades_extraescolares);

//Si fake_cod_profesor es 0 devolvemos un json con el cod profesor y el nombre.
if($_SESSION["tipo"] == "A" && isset($_POST["fake_cod_profesor"]) && $_POST["fake_cod_profesor"] == 0) {
	//Mostramos o no el adminsitrador con la sesión iniciada dependiendo de si hay fake o no:
	if(isset($_SESSION["fake_cod_profesor"])) {
		$profesores = $dbConnection_actividades_profesores->prepare('SELECT cod_profesor, nombre_completo FROM profesores WHERE tipo != "G"');
		$profesores -> execute();
	}else{
		$profesores = $dbConnection_actividades_profesores->prepare('SELECT cod_profesor, nombre_completo FROM profesores WHERE tipo != "G" AND cod_profesor != :cod_profesor');
		$profesores -> bindParam(":cod_profesor",$_SESSION["cod_profesor"]);
		$profesores -> execute();
		
	}
	//Para cada profesor sacamos el nombre, el grupo, etc.
	$lista_profesores = array();
	while($profesores2 = $profesores -> fetch(PDO::FETCH_OBJ)) {
		$lista_profesores [$profesores2->cod_profesor] = $profesores2->nombre_completo;
	}
	
	echo json_encode($lista_profesores);	

//Si hay fake_cod_profesor y es distinto de 0 entonces lo meto como variable de sesión:
}else if($_SESSION["tipo"] == "A" && isset($_POST["fake_cod_profesor"]) && $_POST["fake_cod_profesor"] != 0) {

	//Primero compruebo si soy yo mismo, así destruyo la variable fake, si no la creo.
	if($_POST["fake_cod_profesor"] == $_SESSION["cod_profesor"]) {
		unset($_SESSION["fake_cod_profesor"]);
		//Devuelvo en un json si se debe mostrar el aviso de que se está utilizando otro profesor o no.
		$profesor_fake["fake_cod_profesor"] = "false";  
		echo json_encode($profesor_fake);
	}else {
		$_SESSION["fake_cod_profesor"] = $_POST["fake_cod_profesor"];
		$profesor_fake["fake_cod_profesor"] = $_POST["fake_cod_profesor"];  
		echo json_encode($profesor_fake);
	}
}
?>