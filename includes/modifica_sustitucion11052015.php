<?php session_start(); ?>
<?php
include_once('conectar_bd.php');
//include_once('iniciar_sesion.php');


//Comprobamos si el usuario tiene permisos para editar:
if(isset($_SESSION["tipo"])){
	//Nos conectamos (creamos el objeto) a guardias:
	$dbConnection_guardias = conectarBD(guardias);
	//Ahora nos conectamos (creamos el objeto) a actividades extraescolares:
	$dbConnection_actividades_profesores = conectarBD(actividades_extraescolares);
	
	
	//Recuperamos el array de sustitutos:
	$sustitutos_usuario = $_POST["sustitutos"];

	
	//Cod_profesor que falta:
	$cod_ausente = $_POST["cod_profesor_falta_hidden"];
	
	//Cod_hora:
	$cod_hora = $_POST["cod_hora_hidden"];
	
	//Fecha:
	$fecha = $_POST["fecha_hidden"];
	
	//Comprobamos que el usuario con sesión iniciada tiene permiso para hacer los cambios deseados, si no los tiene, salimos.
	//Si el profesor es Genérico sólo tiene permiso para moficiar sustituciones futuras.
	if($_SESSION["tipo"] == "G"){
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
	}else if($_SESSION["tipo"] == "P"){	//Si el usuario es un profesor normal sólo puede editar las asignaciones a sus faltas (no tiene sentido, simplemente no puede):


		$resultado = array("exito"=>"false");
		echo json_encode($resultado);			
		die();
	}
	
	
	//Vemos si hay que quitar a todos los profesores de la lista:
	//Si no hay (llega variable vacía pero existe) los borro todos:
	if($sustitutos_usuario==""){

		$borrPDO = $dbConnection_guardias->prepare('DELETE FROM historico_guardias WHERE fecha = :fecha AND cod_hora = :cod_hora AND cod_ausente = :cod_ausente ');
		$borrPDO  -> bindParam(':cod_ausente',$cod_ausente);
		$borrPDO  -> bindParam(':fecha',$fecha);
		$borrPDO  -> bindParam(':cod_hora',$cod_hora);
		$borrPDO  -> execute();
	}else{
		
		//Guardo en $sustitutos_bd una lista con todos los sustitutos previamente asignados.
		$sustitutos_bd1 = $dbConnection_guardias->prepare('SELECT cod_profesor FROM historico_guardias WHERE cod_ausente = :cod_ausente AND fecha = :fecha AND cod_hora = :cod_hora');
		$sustitutos_bd1  -> bindParam(':cod_ausente',$cod_ausente);
		$sustitutos_bd1  -> bindParam(':fecha',$fecha);
		$sustitutos_bd1  -> bindParam(':cod_hora',$cod_hora);
		$sustitutos_bd1  -> execute();
	
	
		//Array con los profesores que tengo que mantener ($mantener) (están en la bd y en la $sustitutos_usuario) -En este mismo while los que hay que borrar-.
		while($sustitutos_bd2  = $sustitutos_bd1  -> fetch(PDO::FETCH_OBJ)) {
	
			//Todos los sustitutos que hay en un principio en la bd:
			$sustitutos_bd[] = $sustitutos_bd2->cod_profesor;
			
			//Todos los que ya estaban anteriormente en la bd:
			if(in_array($sustitutos_bd2->cod_profesor, $sustitutos_usuario)){
				$repetido[] = $sustitutos_bd2->cod_profesor;
				
			}else{	//Los que tenemos que borrar (etán en la bd pero no en $sustitutos_usuario):
				$borrar[] = $sustitutos_bd2->cod_profesor;
				
			}
		}
	
	
		//Lista de sustitutos que hay que insertar en la bd (están en sustitutos_usuarios pero no en la bd):
		
		foreach ($sustitutos_usuario as $key => $sustituto_u){
			if(isset($sustitutos_bd)) {
				if(!in_array($sustituto_u, $sustitutos_bd)){
					$insertar[] = $sustituto_u;
				}
			}else{
				$insertar[] = $sustituto_u;
			}
		}
		
	
	}
	

	
	
	//Si hay que borrar o insertar sustitutos primero pongo a 0 la métrica de los profesores que habían (si habían, que semanticamente, habrá mínimo 1) y luego ya veremos (:
	if (isset($borrar) || isset($insertar)){
	
		//Inyectamos métrica 0 a los sustitutos de esta sustitución:
		if (isset($repetido)){
			foreach($repetido as $repe){
				//echo "repe: ";
				//print_r($repe);
				$repePDO = $dbConnection_guardias->prepare('update historico_guardias set metrica = :metrica  WHERE cod_profesor = :cod_profesor AND fecha = :fecha AND cod_hora = :cod_hora AND cod_ausente = :cod_ausente');		
				$cero = 0;
				$repePDO  -> bindParam(':metrica',$cero);
				$repePDO  -> bindParam(':cod_profesor', $repe);
				$repePDO  -> bindParam(':cod_ausente',$cod_ausente);
				$repePDO  -> bindParam(':fecha',$fecha);
				$repePDO  -> bindParam(':cod_hora',$cod_hora);
				$repePDO  -> execute();
			}
		}
		
		//Calculamos la métrica que hay que sumar a cada profesor (en los repetidos no cuento alguno que tenga que borrar):
		$metrica = count($repetido)+count($insertar);
		if($metrica == 0) {
			$metrica = 1;
		}else {		
			$metrica = 1/$metrica;
		}
			
		//Añadimos la métrica a los prepetidos (si procede):
		if (isset($repetido)){
			foreach($repetido as $repe){
	
				$repePDO = $dbConnection_guardias->prepare('update historico_guardias set metrica = :metrica  WHERE cod_profesor = :cod_profesor AND fecha = :fecha AND cod_hora = :cod_hora AND cod_ausente = :cod_ausente');		
				$repePDO  -> bindParam(':metrica',$metrica);
				$repePDO  -> bindParam(':cod_profesor',$repe);
				$repePDO  -> bindParam(':cod_ausente',$cod_ausente);
				$repePDO  -> bindParam(':fecha',$fecha);
				$repePDO  -> bindParam(':cod_hora',$cod_hora);
				$repePDO  -> execute();
			}
		}
		
	
		//Añado los nuevos sustitutos y su metrica (si procede):
		if(isset($insertar)){
	
			foreach($insertar as $insert){
	
				$insert_metricaPDO = $dbConnection_guardias->prepare('INSERT INTO historico_guardias SET cod_profesor = :cod_profesor , fecha = :fecha , cod_hora = :cod_hora , cod_ausente = :cod_ausente , metrica = :metrica');
				$insert_metricaPDO  -> bindParam(':cod_profesor',$insert);
				$insert_metricaPDO  -> bindParam(':fecha',$fecha);
				$insert_metricaPDO  -> bindParam(':cod_hora',$cod_hora);
				$insert_metricaPDO  -> bindParam(':cod_ausente',$cod_ausente);
				$insert_metricaPDO  -> bindParam(':metrica',$metrica);
				$insert_metricaPDO  -> execute();			
			}
		}
		
		
		//Si tengo que eliminar sustitutos:
		if(isset($borrar)) {
			foreach($borrar as $borr){
				
				//Borramos los datos:
				$borrPDO = $dbConnection_guardias->prepare('DELETE FROM historico_guardias WHERE cod_profesor = :cod_profesor AND fecha = :fecha AND cod_hora = :cod_hora AND cod_ausente = :cod_ausente');
				$borrPDO   -> bindParam(':cod_profesor',$borr);
				$borrPDO   -> bindParam(':fecha',$fecha);
				$borrPDO   -> bindParam(':cod_hora',$cod_hora);
				$borrPDO   -> bindParam(':cod_ausente',$cod_ausente);
				$borrPDO  -> execute();
				
			}
		}
		
	}
	$resultado = array("exito"=>"true");
	echo json_encode($resultado);
}else{
	$resultado = array("exito"=>"false");
	echo json_encode($resultado);
}
?>