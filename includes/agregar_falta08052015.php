<?php session_start(); ?><?phpinclude_once('conectar_bd.php');//&& isset($_POST["fecha_calendario"]//print_r($_POST);//die();	if(isset($_SESSION["cod_profesor"]) && isset($_POST["cod_hora"])){
	//Nos conectamos (creamos el objeto) a guardias:
	$dbConnection_guardias = conectar(guardias);
	//Ahora nos conectamos (creamos el objeto) a actividades extraescolares:
	$dbConnection_actividades_profesores = conectar(actividades_extraescolares);
	
	//Compruebo si hay observaciones ya en la base de datos y en tal caso lo recupero y lo envío:
	$consulta = $dbConnection_guardias->prepare('SELECT observaciones FROM ausencias_profesores WHERE cod_profesor = :cod_profesor AND fecha = :fecha AND cod_hora = :cod_hora AND observaciones IS NOT NULL');
	$consulta  -> bindParam(':cod_profesor',$_SESSION["cod_profesor"]);
	$consulta  -> bindParam(':fecha',$_POST["fecha_calendario"]);
	$consulta  -> bindParam(':cod_hora',$_POST["cod_hora"]);
	$consulta  -> execute();
	
	//Si tengo un resultado, paso las observaciones, si no lo paso vacío.	if($consulta->rowCount() == 1){		$obs = $consulta->fetch();		$observaciones= array("observaciones" => $obs["observaciones"]);		echo json_encode($observaciones);	}else{		$observaciones= array("observaciones" => "");		echo json_encode($observaciones);	}		//Compruebo si hay enlace de descarga, en tal caso lo recupero y lo envío:	$consulta = $dbConnection_guardias->prepare('SELECT link FROM ausencias_profesores WHERE cod_profesor = :cod_profesor AND fecha = :fecha AND cod_hora = :cod_hora AND link IS NOT NULL');	$consulta  -> bindParam(':cod_profesor',$_SESSION["cod_profesor"]);	$consulta  -> bindParam(':fecha',$_POST["fecha_calendario"]);	$consulta  -> bindParam(':cod_hora',$_POST["cod_hora"]);	$consulta  -> execute();		/*	//Si tengo un resultado, paso las el enlace, si no lo paso vacío.	if($consulta->rowCount() == 1){		$obs = $consulta->fetch();		$observaciones= array("link" => $obs["link"]);		echo json_encode($observaciones);	}else{		$observaciones= array("link" => "");		echo json_encode($observaciones);	}*/	}else{	echo "error";}?>