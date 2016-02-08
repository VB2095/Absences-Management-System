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
if($_SESSION["tipo"] != "A" && $_SESSION["tipo"] != "P" ) {
	die();
}
//Si no es plural, significa que debería haber fecha y añado falta para todo el día:
if($_POST["dia_semana"] != 8 ) {
	
	
	//Primero saco todas las horas de un día:
	
	//Consulta de todas las horas de un día:
	$horas = $dbConnection_guardias->prepare('SELECT * FROM horas');
	$horas -> execute();
	
	while($obj = $horas -> fetch(PDO::FETCH_OBJ)) {


		//Comprobamos que el usuario con sesión iniciada tiene permiso para hacer los cambios deseados, si no los tiene, salimos.
		//Si el profesor es Genérico sólo tiene permiso para moficiar sustituciones futuras.
		$fecha = $_POST["fecha"];
		$cod_hora = $obj->cod_hora	;
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
		

		
		//Para cada día miro si tiene clase y aun no está insertado.
		
		$tiene_clase1 = $dbConnection_guardias->prepare('SELECT * FROM horarios_profesores WHERE cod_profesor =:cod_profesor AND dia =:dia_semana AND cod_hora = :cod_hora AND
																			cod_profesor NOT IN (SELECT cod_profesor FROM ausencias_profesores WHERE fecha = :fecha2 AND cod_hora = :cod_hora2)
																																																										');

		$tiene_clase1 -> bindParam(':cod_profesor',$session_cod_profesor);
		$tiene_clase1 -> bindParam(':dia_semana',$_POST["dia_semana"]);
		$tiene_clase1 -> bindParam(':cod_hora',$obj->cod_hora);
		$tiene_clase1 -> bindParam(':fecha2',$_POST["fecha"]);
		$tiene_clase1 -> bindParam(':cod_hora2',$obj->cod_hora);
		$tiene_clase1 -> execute();
		//echo $tiene_clase1->rowCount()."<br>";
		//Si da 1 resultado o más sí que tiene clase, lo inserto:
		if($tiene_clase1->rowCount() > 0) {
			$consulta2 = $dbConnection_guardias->prepare("INSERT INTO `guardias`.`ausencias_profesores` (`cod_profesor`, `fecha`, `cod_hora`) VALUES (:cod_profesor, :fecha, :cod_hora)");
			$consulta2  -> bindParam(':cod_profesor',$session_cod_profesor);
			$consulta2  -> bindParam(':fecha',$_POST["fecha"]);
			$consulta2  -> bindParam(':cod_hora',$obj->cod_hora);
			$consulta2  -> execute();	
			
			
					
			//Ahora agregamos al profesor con menos métrica como sustituto:
		
			
			$sustituye = $dbConnection_guardias->prepare('SELECT cod_profesor FROM horarios_profesores WHERE dia = :dia_semana and cod_hora = :cod_hora AND cod_grupo = "GUARDIA" AND aula <> "BIBLIOTECA" AND cod_profesor NOT IN 
					(SELECT cod_profesor FROM historico_guardias WHERE  fecha = :fecha AND cod_hora = :cod_hora2 AND cod_ausente != :cod_ausente) AND cod_profesor NOT IN 
								(SELECT cod_profesor FROM ausencias_profesores WHERE fecha = :fecha3 AND cod_hora = :cod_hora3)');
		
				//Select		
				$sustituye->bindParam(':dia_semana',$_POST["dia_semana"]);		
				$sustituye->bindParam(':cod_hora',$obj->cod_hora);	
				
				//Subselect
				$sustituye->bindParam(':fecha',$_POST["fecha"]);
				$sustituye->bindParam(':fecha3',$_POST["fecha"]);
				$sustituye->bindParam(':cod_ausente',$session_cod_profesor);
				$sustituye->bindParam(':cod_hora2',$obj->cod_hora);
				$sustituye->bindParam(':cod_hora3',$obj->cod_hora);
				
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
				$insert_metricaPDO  -> bindParam(':fecha',$_POST["fecha"]);
				$insert_metricaPDO  -> bindParam(':cod_hora',$obj->cod_hora);
				$insert_metricaPDO  -> bindParam(':cod_ausente',$session_cod_profesor);
				$insert_metricaPDO  -> bindParam(':metrica',$a = 1);
				$insert_metricaPDO  -> execute();			
						
			
			
			
			
		}
		
		
	}
}else if($_POST["dia_semana"] == 8 && isset($_POST["fecha_inicio"]) && isset($_POST["fecha_fin"])) {
	
	//Para cada día hago las comprobaciones:
	
	$fecha_inicio = $_POST["fecha_inicio"];
	$fecha_fin = $_POST["fecha_fin"];

	while(strtotime($fecha_inicio) <= strtotime($fecha_fin)) {
		echo "Bucle_fehcainicio: ".$fecha_inicio."<br>";
		//Primero saco todas las horas de un día:
		//Consulta de todas las horas de un día:
		$horas = $dbConnection_guardias->prepare('SELECT * FROM horas');
		$horas -> execute();
		
		while($obj = $horas -> fetch(PDO::FETCH_OBJ)) {
	
			//Comprobamos que el usuario con sesión iniciada tiene permiso para hacer los cambios deseados, si no los tiene, salimos.
			//Si el profesor es Genérico sólo tiene permiso para moficiar sustituciones futuras.
			$fecha = $fecha_inicio;
			$cod_hora = $obj->cod_hora	;
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
			
	
			
			//Para cada día miro si tiene clase y aun no está insertado.
			
			$tiene_clase1 = $dbConnection_guardias->prepare('SELECT * FROM horarios_profesores WHERE cod_profesor =:cod_profesor AND dia =:dia_semana AND cod_hora = :cod_hora AND
																				cod_profesor NOT IN (SELECT cod_profesor FROM ausencias_profesores WHERE fecha = :fecha2 AND cod_hora = :cod_hora2)
																																																											');
	
			$tiene_clase1 -> bindParam(':cod_profesor',$session_cod_profesor);
			
			//Dia de la semana en español:
			setlocale(LC_ALL,"es_ES.utf8");
			$dia_semana = strftime("%A", strtotime($fecha_inicio));
			$tiene_clase1 -> bindParam(':dia_semana',$dia_semana);
			$tiene_clase1 -> bindParam(':cod_hora',$obj->cod_hora);
			$tiene_clase1 -> bindParam(':fecha2',$fecha_inicio);
			$tiene_clase1 -> bindParam(':cod_hora2',$obj->cod_hora);
			$tiene_clase1 -> execute();
			echo $tiene_clase1->rowCount()."<br>";
			//Si da 1 resultado o más sí que tiene clase, lo inserto:
			if($tiene_clase1->rowCount() > 0) {
				$consulta2 = $dbConnection_guardias->prepare("INSERT INTO `guardias`.`ausencias_profesores` (`cod_profesor`, `fecha`, `cod_hora`) VALUES (:cod_profesor, :fecha, :cod_hora)");
				$consulta2  -> bindParam(':cod_profesor',$session_cod_profesor);
				$consulta2  -> bindParam(':fecha',$fecha_inicio);
				$consulta2  -> bindParam(':cod_hora',$obj->cod_hora);
				$consulta2  -> execute();	
				
				
						
				//Ahora agregamos al profesor con menos métrica como sustituto:
			
				
				$sustituye = $dbConnection_guardias->prepare('SELECT cod_profesor FROM horarios_profesores WHERE dia = :dia_semana and cod_hora = :cod_hora AND cod_grupo = "GUARDIA" AND aula <> "BIBLIOTECA" AND cod_profesor NOT IN 
						(SELECT cod_profesor FROM historico_guardias WHERE  fecha = :fecha AND cod_hora = :cod_hora2 AND cod_ausente != :cod_ausente) AND cod_profesor NOT IN 
								(SELECT cod_profesor FROM ausencias_profesores WHERE fecha = :fecha3 AND cod_hora = :cod_hora3)');
			
					//Select		
					$sustituye->bindParam(':dia_semana',$dia_semana);		
					$sustituye->bindParam(':cod_hora',$obj->cod_hora);	
					$sustituye->bindParam(':cod_hora3',$obj->cod_hora);	
					
					//Subselect
					$sustituye->bindParam(':fecha',$fecha_inicio);
					$sustituye->bindParam(':fecha3',$fecha_inicio);
					$sustituye->bindParam(':cod_ausente',$session_cod_profesor);
					$sustituye->bindParam(':cod_hora2',$obj->cod_hora);
					
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
					$insert_metricaPDO  -> bindParam(':fecha',$fecha_inicio);
					$insert_metricaPDO  -> bindParam(':cod_hora',$obj->cod_hora);
					$insert_metricaPDO  -> bindParam(':cod_ausente',$session_cod_profesor);
					$insert_metricaPDO  -> bindParam(':metrica',$a = 1);
					$insert_metricaPDO  -> execute();			
							
				
				
				
				
			}
		
		}

		//$fecha_inicio = strtotime(date("Y-m-d", strtotime($fecha_inicio)) . "+1 day");
		$fecha_inicio = date("Y-m-d",strtotime($fecha_inicio . "+1 day"));
	}


	
	
	
}else{
echo "Debe tener permisos para realizar esta acción.";
}

?>