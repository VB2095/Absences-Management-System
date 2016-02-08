<?php session_start(); ?>
<?php
include_once('conectar_bd.php');
?>
<?php                 

if(isset($_SESSION["cod_profesor"])){

	
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
	$fecha = $_POST["fecha_calendario_agregar_ausencia"];
	$cod_hora = $_POST["cod_hora_agregar_ausencia"]	;
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
	
	
	
	
	

	
	//Compruebo si la fila ya existe para actualizarla o insertarla:
	$consulta = $dbConnection_guardias->prepare('SELECT * FROM ausencias_profesores WHERE cod_profesor = :cod_profesor AND fecha = :fecha AND cod_hora = :cod_hora');
	$consulta  -> bindParam(':cod_profesor',$session_cod_profesor);
	$consulta  -> bindParam(':fecha',$_POST["fecha_calendario_agregar_ausencia"]);
	$consulta  -> bindParam(':cod_hora',$_POST["cod_hora_agregar_ausencia"]);
	$consulta  -> execute();
	
	//Si tengo un resultado, actualizo, si no inserto.	if($consulta->rowCount() == 1){		$consulta2 = $dbConnection_guardias->prepare("UPDATE `guardias`.`ausencias_profesores` SET `observaciones`= :observaciones , `link`= :link WHERE `cod_profesor`= :cod_profesor and`fecha`=:fecha and`cod_hora`=:cod_hora");
		$consulta2  -> bindParam(':cod_profesor',$session_cod_profesor);
		$consulta2  -> bindParam(':fecha',$_POST["fecha_calendario_agregar_ausencia"]);
		$consulta2  -> bindParam(':cod_hora',$_POST["cod_hora_agregar_ausencia"]);
		$consulta2  -> bindParam(':observaciones',$_POST["textarea_agregar_falta"]);		
		

		//Primero vemos si queremos borrar el fichero o si hemos subido uno nuevo.
		if(isset($_POST["checkbox_borrar_fichero"]) && $_POST["checkbox_borrar_fichero"] == "true") {
			$link = $consulta->fetch();
			$ruta_fichero = $_SERVER['DOCUMENT_ROOT'].$link["link"];
			if(is_readable($ruta_fichero)){
				unlink($ruta_fichero);
			}
			//Borro el fichero y lo quito de la bd:
			$consulta2  -> bindParam(':link',$a = null);
			
		}else if (isset($_FILES["input_file"]["error"]) && $_FILES["input_file"]["error"] == 0){

			//Guardo el fichero y lo meto en la bd.
			$md5 = md5_file($_FILES["input_file"]["tmp_name"]);			
			echo 'md5 '.$_FILES["input_file"]["tmp_name"].' : '.$md5;
			$nombre = preg_replace('/\s+/', '_', $_FILES["input_file"]["name"]);
			move_uploaded_file($_FILES["input_file"]["tmp_name"],  
			$_SERVER['DOCUMENT_ROOT']."/descargas/". $md5."_" . $nombre);  
			  
 
			//$consulta  -> bindParam(':link',$a = $_SERVER['DOCUMENT_ROOT']."/descargas/". $md5."_" . $nombre);
			$consulta2  -> bindParam(':link',$a = "/descargas/". $md5."_" . $nombre);
			//$consulta  -> bindParam(':link',"hola2");		

			
		}else{
			//Si no, vuelvo a introducir lo que ya había antes.
			$link = $consulta->fetch();
			$consulta2 -> bindParam(":link",$link["link"]);
		}
		//Ahora actualizo

			$consulta2  -> execute();
	}else {
		
		$consulta2 = $dbConnection_guardias->prepare("INSERT INTO `guardias`.`ausencias_profesores` (`cod_profesor`, `fecha`, `cod_hora`, `observaciones`, `link`) VALUES (:cod_profesor, :fecha, :cod_hora, :observaciones, :link)");
		$consulta2  -> bindParam(':cod_profesor',$session_cod_profesor);
		$consulta2  -> bindParam(':fecha',$_POST["fecha_calendario_agregar_ausencia"]);
		$consulta2  -> bindParam(':cod_hora',$_POST["cod_hora_agregar_ausencia"]);
		$consulta2  -> bindParam(':observaciones',$_POST["textarea_agregar_falta"]);		
		

		//Primero vemos si queremos subir un archivo:
		if (isset($_FILES["input_file"]["error"]) && $_FILES["input_file"]["error"] == 0){

			//Guardo el fichero y lo meto en la bd.
			$md5 = md5_file($_FILES["input_file"]["tmp_name"]);			
			echo 'md5 '.$_FILES["input_file"]["tmp_name"].' : '.$md5;
			$nombre = preg_replace('/\s+/', '_', $_FILES["input_file"]["name"]);
			move_uploaded_file($_FILES["input_file"]["tmp_name"],  
			$_SERVER['DOCUMENT_ROOT']."/descargas/". $md5."_" . $nombre);  
 
			$consulta2  -> bindParam(':link',$a = "/descargas/". $md5."_" . $nombre);
		
		}else{
			//Si no, vuelvo a introducir null.
			$consulta2 -> bindParam(":link",$a = null);
		}
		//Ahora actualizo

			$consulta2  -> execute();
		
	

	
	
	//Ahora agregamos al profesor con menos métrica como sustituto:

	
		$sustituye = $dbConnection_guardias->prepare('SELECT cod_profesor FROM horarios_profesores WHERE dia = :dia_semana and cod_hora = :cod_hora AND cod_grupo = "GUARDIA" AND aula <> "BIBLIOTECA" AND cod_profesor NOT IN 
				(SELECT cod_profesor FROM historico_guardias WHERE  fecha = :fecha AND cod_hora = :cod_hora2 AND cod_ausente != :cod_ausente) AND cod_profesor NOT IN 
									(SELECT cod_profesor FROM ausencias_profesores WHERE fecha = :fecha3 AND cod_hora = :cod_hora3)');

		//Select		
		$sustituye->bindParam(':dia_semana',$_POST["dia_semana"]);		
		$sustituye->bindParam(':cod_hora',$_POST["cod_hora_agregar_ausencia"]);	
		$sustituye->bindParam(':cod_hora3',$_POST["cod_hora_agregar_ausencia"]);	
		
		//Subselect
		$sustituye->bindParam(':fecha',$_POST["fecha_calendario_agregar_ausencia"]);
		$sustituye->bindParam(':fecha3',$_POST["fecha_calendario_agregar_ausencia"]);
		$sustituye->bindParam(':cod_ausente',$session_cod_profesor);
		$sustituye->bindParam(':cod_hora2',$_POST["cod_hora_agregar_ausencia"]);
		
		$sustituye->execute();
		$sustituye = $sustituye->fetchAll();
		$sustituye_array = array();
		//Nos hacemos el array con cada cod_profesor que puede sustituir:
		foreach($sustituye as $fila){
			
			//Buscamos la metrica de cada profesor que puede sustituir:

						
			$metrica = $dbConnection_guardias->prepare('SELECT metrica FROM historico_guardias where cod_profesor = :cod_profesor');
			$metrica->bindParam(':cod_profesor',$fila[cod_profesor]);
			$metrica->execute();
			$metrica_final = 0;
			while($metrica2 = $metrica->fetch(PDO::FETCH_ASSOC)){				
				$metrica_final = $metrica_final + $metrica2["metrica"];
			}
			$metrica = $metrica_final;
			//Lo guardo en el array:
			$sustituye_array[$fila[cod_profesor]] = $metrica;
							
	

		}
		

		//Ordenamos el array para que los profesores con menos metrica salgan primero:
		
		asort($sustituye_array);
		
		//Insertamos el sustituto y le damos métrica 1: 

		$insert_metricaPDO = $dbConnection_guardias->prepare('INSERT INTO historico_guardias SET cod_profesor = :cod_profesor , fecha = :fecha , cod_hora = :cod_hora , cod_ausente = :cod_ausente , metrica = :metrica');
		$insert_metricaPDO  -> bindParam(':cod_profesor',key($sustituye_array));
		$insert_metricaPDO  -> bindParam(':fecha',$_POST["fecha_calendario_agregar_ausencia"]);
		$insert_metricaPDO  -> bindParam(':cod_hora',$_POST["cod_hora_agregar_ausencia"]);
		$insert_metricaPDO  -> bindParam(':cod_ausente',$session_cod_profesor);
		$insert_metricaPDO  -> bindParam(':metrica',$a = 1);
		$insert_metricaPDO  -> execute();			
	
	
	}
	
	
	$xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'; 
	
/*	if (!$xhr)  
	    echo '<textarea>'; 
	echo "<br>files<br>";
	print_r($_FILES);

	echo "<br>post<br>";
	print_r($_POST);
	//echo "debe hacer la sutitución: ".key($sustituye_array);
	//print_r($sustituye_array);
	echo "Éxito!;
	
	if (!$xhr)   
	    echo '</textarea>';*/
} 
?> 
