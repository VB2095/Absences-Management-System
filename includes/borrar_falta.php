<?php session_start(); ?>
<?php
include_once('conectar_bd.php');



//Ahora comprobamos si soy fake_cod_profesor para que el administrador pueda editar cualquier profesor:

if(isset($_SESSION["fake_cod_profesor"])) {
	$session_cod_profesor = $_SESSION["fake_cod_profesor"];
}else {
	$session_cod_profesor = $_SESSION["cod_profesor"];
}
//A partir de este momento usaré siempre este session_cod_profesor.









//Nos conectamos (creamos el objeto) a guardias:
$dbConnection_guardias = conectarBD(guardias);
//Ahora nos conectamos (creamos el objeto) a actividades extraescolares:
$dbConnection_actividades_profesores = conectarBD(actividades_extraescolares);


//Comprobamos que el usuario con sesión iniciada tiene permiso para hacer los cambios deseados, si no los tiene, salimos.
//Si el profesor es Genérico sólo tiene permiso para moficiar sustituciones futuras.
$fecha = $_POST["fecha_calendario"];
$cod_hora = $_POST["cod_hora"];
if($_SESSION["tipo"] == "G"){
		$resultado = array("exito"=>"false");
	echo json_encode($resultado);			
	die();
}else if($_SESSION["tipo"] == "P"){	//Si el usuario es un profesor normal sólo puede editar las asignaciones a sus faltas (no tiene sentido, simplemente no puede):

//Si estamos intentando cambiar hoy compruebo la hora.
	if ($fecha == date("o-m-d")){
		 
		//Vemos cual es la hora de inicio:
		$hora_inicio2 = $dbConnection_guardias->prepare('SELECT hora_inicio FROM horas WHERE cod_hora = :cod_hora ');
		$hora_inicio2  -> bindParam(':cod_hora',$cod_hora);
		$hora_inicio2  -> execute();
		$hora_inicio = $hora_inicio2->fetch(PDO::FETCH_ASSOC);

		//Si la hora de inicio ya ha empezado, no se puede editar:
		if($hora_inicio["hora_inicio"] < date("H:i")){
			$resultado = array("exito"=>"false");
			echo json_encode($resultado);			
			die();
		}
	}else if($fecha < date("o-m-d")){
		//Si intento cambiar un dia anterior a hoy directamente salgo.
		$resultado = array("exito"=>"false");
		echo json_encode($resultado);			
		die();
	}

}



//Comprobamos si el usuario es "P", "A" que se borra una falta a si mismo (no nos envía el cod_profesor_fake):
if($_SESSION["tipo"] == "P" || $_SESSION["tipo"] == "A" && !isset($_POST["cod_profesor_fake"])){

	//Borro mis sustitutos:
	$borrPDO = $dbConnection_guardias->prepare('DELETE FROM historico_guardias WHERE fecha = :fecha AND cod_hora = :cod_hora AND cod_ausente = :cod_ausente ');
	$borrPDO  -> bindParam(':cod_ausente',$session_cod_profesor);
	$borrPDO  -> bindParam(':fecha',$_POST["fecha_calendario"]);
	$borrPDO  -> bindParam(':cod_hora',$_POST["cod_hora"]);
	$borrPDO  -> execute();
	
	//Compruebo que no tuviese link de descarga (fichero):
	$consulta = $dbConnection_guardias->prepare('SELECT * FROM ausencias_profesores WHERE cod_profesor = :cod_profesor AND fecha = :fecha AND cod_hora = :cod_hora');
	$consulta  -> bindParam(':cod_profesor',$session_cod_profesor);
	$consulta  -> bindParam(':fecha',$_POST["fecha_calendario"]);
	$consulta  -> bindParam(':cod_hora',$_POST["cod_hora"]);
	$consulta  -> execute();
	
	if($consulta->rowCount() == 1){
		$link = $consulta->fetch();
		if($link["link"] != null && $link["link"] != "") {
			$ruta_fichero = $_SERVER['DOCUMENT_ROOT'].$link["link"];
			if(is_readable($ruta_fichero)){
				unlink($ruta_fichero);
			}	
		}
	}
	
	

	//Borro mi ausencia:	
	$borrPDO = $dbConnection_guardias->prepare('DELETE FROM ausencias_profesores WHERE cod_profesor = :cod_profesor AND fecha = :fecha AND cod_hora = :cod_hora ');
	$borrPDO  -> bindParam(':cod_profesor',$session_cod_profesor);
	$borrPDO  -> bindParam(':fecha',$_POST["fecha_calendario"]);
	$borrPDO  -> bindParam(':cod_hora',$_POST["cod_hora"]);
	$borrPDO  -> execute();		
	
	
//Si el usuario es administrador ("A"), comprobamos que nos mande un cod_profesor_fake. Eliminaremos el cod_profesor_fake:
}else if($_SESSION["tipo"] == "A" && isset($_POST["cod_profesor_fake"])){






echo "hola";

	//Borro mis sustitutos:
	$borrPDO = $dbConnection_guardias->prepare('DELETE FROM historico_guardias WHERE fecha = :fecha AND cod_hora = :cod_hora AND cod_ausente = :cod_ausente ');
	$borrPDO  -> bindParam(':cod_ausente',$_POST["cod_profesor_fake"]);
	$borrPDO  -> bindParam(':fecha',$_POST["fecha_calendario"]);
	$borrPDO  -> bindParam(':cod_hora',$_POST["cod_hora"]);
	$borrPDO  -> execute();
	
	//Compruebo que no tuviese link de descarga (fichero):
	$consulta = $dbConnection_guardias->prepare('SELECT * FROM ausencias_profesores WHERE cod_profesor = :cod_profesor AND fecha = :fecha AND cod_hora = :cod_hora');
	$consulta  -> bindParam(':cod_profesor',$_POST["cod_profesor_fake"]);
	$consulta  -> bindParam(':fecha',$_POST["fecha_calendario"]);
	$consulta  -> bindParam(':cod_hora',$_POST["cod_hora"]);
	$consulta  -> execute();
	
	if($consulta->rowCount() == 1){
		$link = $consulta->fetch();
		if($link["link"] != null && $link["link"] != "") {
			$ruta_fichero = $_SERVER['DOCUMENT_ROOT'].$link["link"];
			if(is_readable($ruta_fichero)){
				unlink($ruta_fichero);
			}	
		}
	}
	
	

	//Borro mi ausencia:	
	$borrPDO = $dbConnection_guardias->prepare('DELETE FROM ausencias_profesores WHERE cod_profesor = :cod_profesor AND fecha = :fecha AND cod_hora = :cod_hora ');
	$borrPDO  -> bindParam(':cod_profesor',$_POST["cod_profesor_fake"]);
	$borrPDO  -> bindParam(':fecha',$_POST["fecha_calendario"]);
	$borrPDO  -> bindParam(':cod_hora',$_POST["cod_hora"]);
	$borrPDO  -> execute();	















}