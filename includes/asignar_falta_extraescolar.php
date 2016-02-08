<?php session_start(); ?>
<?php
include_once('conectar_bd.php');

function asignar_falta_extraescolar($cod_sesion)
{
	
	//Nos conectamos (creamos el objeto) a guardias:
	$dbConnection_guardias = conectarBD(guardias);
	//Ahora nos conectamos (creamos el objeto) a actividades extraescolares:
	$dbConnection_actividades_profesores = conectarBD(actividades_extraescolares);

	//Saco los datos de la sesión
	$sesion = $dbConnection_actividades_profesores->prepare('SELECT * FROM sesiones where cod_sesion = :cod_sesion');
	$sesion->bindParam(':cod_sesion',$cod_sesion);
	$sesion->execute();
	$sesion2 = $sesion->fetch();


	//Saco las horas afectadas:
	
	$horas = $dbConnection_guardias->prepare('SELECT * FROM horas');
	$horas->execute();
	

	echo "<br>";
	
	//Si la fecha de inicio y de fin son las mismas sólamente hay falta de un día y miro las fechas e inserto todos los dias todas las horas menos las del día de inicio y fin:
	

	$horas_ocupadas = array();
	$horas_ocupadas_inicio = array();
	$horas_ocupadas_fin = array();
	while($hora = $horas->fetch(PDO::FETCH_ASSOC)){
		//Array con todas las horas del día
		$todas_las_horas[] = $hora["cod_hora"];
		
		if(strtotime($sesion2["fecha_inicio"]) == strtotime($sesion2["fecha_fin"])){
			//El periodo es más pequeño que una hora de clase
			if(strtotime($hora["hora_inicio"]) < strtotime($sesion2["hora_inicio"]) && strtotime($hora["hora_fin"]) > strtotime($sesion2["hora_fin"]) ) {
				$horas_ocupadas[] = $hora["cod_hora"];
			}else if(strtotime($hora["hora_inicio"]) < strtotime($sesion2["hora_inicio"]) && strtotime($hora["hora_fin"]) < strtotime($sesion2["hora_fin"]) && strtotime($hora["hora_fin"]) > strtotime($sesion2["hora_inicio"])) {
				$horas_ocupadas[] = $hora["cod_hora"];
			}else if(strtotime($hora["hora_inicio"]) > strtotime($sesion2["hora_inicio"]) && strtotime($hora["hora_fin"]) > strtotime($sesion2["hora_fin"]) && strtotime($hora["hora_inicio"]) < strtotime($sesion2["hora_fin"])) {
				$horas_ocupadas[] = $hora["cod_hora"];
			//Mas grande que las horas de clase
			}else if(strtotime($hora["hora_inicio"]) > strtotime($sesion2["hora_inicio"]) && strtotime($hora["hora_fin"]) > strtotime($sesion2["hora_fin"]) && strtotime($hora["hora_inicio"]) < strtotime($sesion2["hora_fin"])){
				$horas_ocupadas[] = $hora["cod_hora"];
			}else if (strtotime($hora["hora_inicio"]) > strtotime($sesion2["hora_inicio"]) && strtotime($hora["hora_fin"]) < strtotime($sesion2["hora_fin"])){
				$horas_ocupadas[] = $hora["cod_hora"];
			}else if (strtotime($hora["hora_inicio"]) < strtotime($sesion2["hora_inicio"]) && strtotime($hora["hora_fin"]) < strtotime($sesion2["hora_fin"]) && strtotime($hora["hora_fin"])  > strtotime($sesion2["hora_inicio"])){
				$horas_ocupadas[] = $hora["cod_hora"];
			//Tamaños iguales o empieza a la misma hora o acaba a la misma hora:
			}else if (strtotime($hora["hora_inicio"]) == strtotime($sesion2["hora_inicio"]) && strtotime($hora["hora_fin"]) == strtotime($sesion2["hora_fin"])){
				$horas_ocupadas[] = $hora["cod_hora"];
			}else if (strtotime($hora["hora_inicio"]) == strtotime($sesion2["hora_inicio"]) && strtotime($hora["hora_fin"]) < strtotime($sesion2["hora_fin"])){
				$horas_ocupadas[] = $hora["cod_hora"];
			}else if (strtotime($hora["hora_inicio"]) > strtotime($sesion2["hora_inicio"]) && strtotime($hora["hora_fin"]) == strtotime($sesion2["hora_fin"])) {
				$horas_ocupadas[] = $hora["cod_hora"];
			}
		 
		}else{

			//Cuando son distintas compruebo lss horas ocupadas el primer día y el último: El día empieza a las 00:00, termina a las 23:59.
			//Cambio las horas finales por la última hora del día para tener cogidas todas las horas de ese día.
			//El periodo es más pequeño que una hora de clase.
			$ultima_hora_dia=strtotime("23:59");
			
			//El periodo es más pequeño que una hora de clase
			if(strtotime($hora["hora_inicio"]) < strtotime($sesion2["hora_inicio"]) && strtotime($hora["hora_fin"]) > $ultima_hora_dia ) {
				$horas_ocupadas_inicio[] = $hora["cod_hora"];
			}else if(strtotime($hora["hora_inicio"]) < strtotime($sesion2["hora_inicio"]) && strtotime($hora["hora_fin"]) < $ultima_hora_dia && strtotime($hora["hora_fin"]) > strtotime($sesion2["hora_inicio"])) {
				$horas_ocupadas_inicio[] = $hora["cod_hora"];
			}else if(strtotime($hora["hora_inicio"]) > strtotime($sesion2["hora_inicio"]) && strtotime($hora["hora_fin"]) > $ultima_hora_dia && strtotime($hora["hora_inicio"]) < $ultima_hora_dia) {
				$horas_ocupadas_inicio[] = $hora["cod_hora"];
			//Mas grande que las horas de clase
			}else if(strtotime($hora["hora_inicio"]) > strtotime($sesion2["hora_inicio"]) && strtotime($hora["hora_fin"]) > $ultima_hora_dia && strtotime($hora["hora_inicio"]) < $ultima_hora_dia){
				$horas_ocupadas_inicio[] = $hora["cod_hora"];
			}else if (strtotime($hora["hora_inicio"]) > strtotime($sesion2["hora_inicio"]) && strtotime($hora["hora_fin"]) < $ultima_hora_dia){
				$horas_ocupadas_inicio[] = $hora["cod_hora"];
			}else if (strtotime($hora["hora_inicio"]) < strtotime($sesion2["hora_inicio"]) && strtotime($hora["hora_fin"]) < $ultima_hora_dia && strtotime($hora["hora_fin"])  > strtotime($sesion2["hora_inicio"])){
				$horas_ocupadas_inicio[] = $hora["cod_hora"];
			//Tamaños iguales o empieza a la misma hora o acaba a la misma hora:
			}else if (strtotime($hora["hora_inicio"]) == strtotime($sesion2["hora_inicio"]) && strtotime($hora["hora_fin"]) == $ultima_hora_dia){
				$horas_ocupadas_inicio[] = $hora["cod_hora"];
			}else if (strtotime($hora["hora_inicio"]) == strtotime($sesion2["hora_inicio"]) && strtotime($hora["hora_fin"]) < $ultima_hora_dia){
				$horas_ocupadas_inicio[] = $hora["cod_hora"];
			}else if (strtotime($hora["hora_inicio"]) > strtotime($sesion2["hora_inicio"]) && strtotime($hora["hora_fin"]) == $ultima_hora_dia) {
				$horas_ocupadas_inicio[] = $hora["cod_hora"];
			}	
		
			$primera_hora_dia = strtotime("00:00");
			//El periodo es más pequeño que una hora de clase
			if(strtotime($hora["hora_inicio"]) < $primera_hora_dia && strtotime($hora["hora_fin"]) > strtotime($sesion2["hora_fin"]) ) {
				$horas_ocupadas_fin[] = $hora["cod_hora"];
			}else if(strtotime($hora["hora_inicio"]) < $primera_hora_dia && strtotime($hora["hora_fin"]) < strtotime($sesion2["hora_fin"]) && strtotime($hora["hora_fin"]) > $primera_hora_dia) {
				$horas_ocupadas_fin[] = $hora["cod_hora"];
			}else if(strtotime($hora["hora_inicio"]) > $primera_hora_dia && strtotime($hora["hora_fin"]) > strtotime($sesion2["hora_fin"]) && strtotime($hora["hora_inicio"]) < strtotime($sesion2["hora_fin"])) {
				$horas_ocupadas_fin[] = $hora["cod_hora"];
			//Mas grande que las horas de clase
			}else if(strtotime($hora["hora_inicio"]) > $primera_hora_dia && strtotime($hora["hora_fin"]) > strtotime($sesion2["hora_fin"]) && strtotime($hora["hora_inicio"]) < strtotime($sesion2["hora_fin"])){
				$horas_ocupadas_fin[] = $hora["cod_hora"];
			}else if (strtotime($hora["hora_inicio"]) > $primera_hora_dia && strtotime($hora["hora_fin"]) < strtotime($sesion2["hora_fin"])){
				$horas_ocupadas_fin[] = $hora["cod_hora"];
			}else if (strtotime($hora["hora_inicio"]) < $primera_hora_dia && strtotime($hora["hora_fin"]) < strtotime($sesion2["hora_fin"]) && strtotime($hora["hora_fin"])  > $primera_hora_dia){
				$horas_ocupadas_fin[] = $hora["cod_hora"];
			//Tamaños iguales o empieza a la misma hora o acaba a la misma hora:
			}else if (strtotime($hora["hora_inicio"]) == $primera_hora_dia && strtotime($hora["hora_fin"]) == strtotime($sesion2["hora_fin"])){
				$horas_ocupadas_fin[] = $hora["cod_hora"];
			}else if (strtotime($hora["hora_inicio"]) == $primera_hora_dia && strtotime($hora["hora_fin"]) < strtotime($sesion2["hora_fin"])){
				$horas_ocupadas_fin[] = $hora["cod_hora"];
			}else if (strtotime($hora["hora_inicio"]) > $primera_hora_dia && strtotime($hora["hora_fin"]) == strtotime($sesion2["hora_fin"])) {
				$horas_ocupadas_fin[] = $hora["cod_hora"];
			}					

		}	
	
	}
	echo "todas las horas:<pre>";
	print_r($todas_las_horas);
	echo "</pre>";
	echo "horas ocupadas inicio:<pre>";
	print_r($horas_ocupadas_inicio);
	echo "</pre>";	
	echo "horas ocupadas fin:<pre>";
	print_r($horas_ocupadas_fin);
	echo "</pre>";

	echo "<pre>";
	print_r($sesion2);
	echo"</pre>";
	
	//Saco los cod_profesor afectados:
	$cod_profesor = $dbConnection_actividades_profesores->prepare('SELECT * FROM sesiones_profesores where cod_sesion = :cod_sesion');
	$cod_profesor->bindParam(':cod_sesion',$cod_sesion);
	$cod_profesor->execute();
	
	//Por cada profesor meto las horas afectadas:
	while($cod_profesor2 = $cod_profesor->fetch(PDO::FETCH_ASSOC)){


	//Si las fechas son las mismas sólo falta un día, así que añado el profesor a cada una de las horas afectadas:
	
	if($sesion2["fecha_inicio"] == $sesion2["fecha_fin"]) {

		//Para cada hora...
		foreach( $horas_ocupadas as $cod_hora ){
			
			//Hago que me salga el día de la semana:
			setlocale(LC_ALL,"es_ES.utf8");
			$dia_semana = strftime("%A", strtotime($sesion2["fecha_inicio"]));
			
			//Primero compruebo que a esa hora, ese día el código profesor tenga clase:	
			$clase = $dbConnection_guardias->prepare('SELECT * FROM horarios_profesores where cod_profesor = :cod_profesor AND dia = :dia AND cod_hora = :cod_hora');
			$clase->bindParam(':cod_profesor',$cod_profesor2["cod_profesor"]);
			$clase->bindParam(':dia',$dia_semana);
			$clase  -> bindParam(':cod_hora',$cod_hora);
			$clase->execute();
			//Si ese profe tiene clase a esa hora hago la insercción.
			if($clase->rowCount() > 0){			
			
				try{
					$consulta2 = $dbConnection_guardias->prepare("INSERT INTO `guardias`.`ausencias_profesores` (`cod_profesor`, `fecha`, `cod_hora`, `observaciones`) VALUES (:cod_profesor, :fecha, :cod_hora, :observaciones)");
					$consulta2  -> bindParam(':cod_profesor',$cod_profesor2["cod_profesor"]);
					$consulta2  -> bindParam(':fecha',$sesion2["fecha_inicio"]);
					$consulta2  -> bindParam(':cod_hora',$cod_hora);
					$consulta2  -> bindParam(':observaciones',$a="Actividad extraescolar");		
					$consulta2  -> execute();
				}catch(PDOException $e){
					echo $e;
					//continuo...
				}
				//Ahora agregamos al profesor con menos métrica como sustituto:
		
			
				$sustituye = $dbConnection_guardias->prepare('SELECT cod_profesor FROM horarios_profesores WHERE dia = :dia_semana and cod_hora = :cod_hora AND cod_grupo = "GUARDIA" AND aula <> "BIBLIOTECA" AND cod_profesor NOT IN 
					(SELECT cod_profesor FROM historico_guardias WHERE  fecha = :fecha AND cod_hora = :cod_hora2 AND cod_ausente != :cod_ausente) AND cod_profesor NOT IN 
								(SELECT cod_profesor FROM ausencias_profesores WHERE fecha = :fecha3 AND cod_hora = :cod_hora3)');
		
	
				//Select		
				$sustituye->bindParam(':dia_semana',$dia_semana);		
				$sustituye->bindParam(':cod_hora',$cod_hora);	
				
				//Subselect
				$sustituye->bindParam(':fecha',$sesion2["fecha_inicio"]);
				$sustituye->bindParam(':fecha3',$sesion2["fecha_inicio"]);
				$sustituye->bindParam(':cod_ausente',$cod_profesor2["cod_profesor"]);
				$sustituye->bindParam(':cod_hora2',$cod_hora);
				$sustituye->bindParam(':cod_hora3',$cod_hora);
				
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
				echo "<pre>";
				print_r($sustituye_array);
				echo "</pre>";
				//Insertamos el sustituto y le damos métrica 1: 
		
				
				$insert_metricaPDO = $dbConnection_guardias->prepare('INSERT INTO historico_guardias SET cod_profesor = :cod_profesor , fecha = :fecha , cod_hora = :cod_hora , cod_ausente = :cod_ausente , metrica = :metrica');
				$insert_metricaPDO  -> bindParam(':cod_profesor',key($sustituye_array));
				$insert_metricaPDO  -> bindParam(':fecha',$sesion2["fecha_inicio"]);
				$insert_metricaPDO  -> bindParam(':cod_hora',$cod_hora);
				$insert_metricaPDO  -> bindParam(':cod_ausente',$cod_profesor2["cod_profesor"]);
				$insert_metricaPDO  -> bindParam(':metrica',$a = 1);
				$insert_metricaPDO  -> execute();			
			}	
		
		}
		
	//Si la actividad va a faltar varios días...
	}else if($sesion2["fecha_inicio"] < $sesion2["fecha_fin"]){
		//Primero creo una variable con el día que estoy insertando:

		$fecha_insert = date("Y-m-d", strtotime($sesion2["fecha_inicio"]));
					
		while(date("Y-m-d",strtotime($fecha_insert)) <= date("Y-m-d",strtotime($sesion2["fecha_fin"])) ) {
			echo "<br>while insert: ".date("Y-m-d",strtotime($fecha_insert))." while fin:".date("Y-m-d",strtotime($sesion2["fecha_fin"]));
			//Luego inserto ese día:
			if( date("Y-m-d",strtotime($fecha_insert)) == date("Y-m-d",strtotime($sesion2["fecha_inicio"])) ) {
				
	
				//Para cada hora...
				foreach( $horas_ocupadas_inicio as $cod_hora ){
					
					//Hago que me salga el día de la semana:
					setlocale(LC_ALL,"es_ES.utf8");

					$dia_semana = strftime("%A", strtotime($fecha_insert));
					
					//Primero compruebo que a esa hora, ese día el código profesor tenga clase:	
					$clase = $dbConnection_guardias->prepare('SELECT * FROM horarios_profesores where cod_profesor = :cod_profesor AND dia = :dia AND cod_hora = :cod_hora');
					$clase->bindParam(':cod_profesor',$cod_profesor2["cod_profesor"]);
					$clase->bindParam(':dia',$dia_semana);
					$clase  -> bindParam(':cod_hora',$cod_hora);
					$clase->execute();
					
					//Si ese profe tiene clase a esa hora hago la insercción.
					if($clase->rowCount() > 0){			
					echo "<br> ".$cod_profesor2["cod_profesor"]. "tiene clase en ".$dia_semana ." cod hoyra ".$cod_hora." rowcount: ".$clase->rowCount();
					
						try{
							$consulta2 = $dbConnection_guardias->prepare("INSERT INTO `guardias`.`ausencias_profesores` (`cod_profesor`, `fecha`, `cod_hora`, `observaciones`) VALUES (:cod_profesor, :fecha, :cod_hora, :observaciones)");
							$consulta2  -> bindParam(':cod_profesor',$cod_profesor2["cod_profesor"]);
							$consulta2  -> bindParam(':fecha',$fecha_insert);
							$consulta2  -> bindParam(':cod_hora',$cod_hora);
							$consulta2  -> bindParam(':observaciones',$a="Actividad extraescolar");		
							$consulta2  -> execute();
						}catch(PDOException $e){
							echo $e;
							//continuo...
						}
						//Ahora agregamos al profesor con menos métrica como sustituto:
				
					
						$sustituye = $dbConnection_guardias->prepare('SELECT cod_profesor FROM horarios_profesores WHERE dia = :dia_semana and cod_hora = :cod_hora AND cod_grupo = "GUARDIA" AND aula <> "BIBLIOTECA" AND cod_profesor NOT IN 
							(SELECT cod_profesor FROM historico_guardias WHERE  fecha = :fecha AND cod_hora = :cod_hora2 AND cod_ausente != :cod_ausente)AND cod_profesor NOT IN 
								(SELECT cod_profesor FROM ausencias_profesores WHERE fecha = :fecha3 AND cod_hora = :cod_hora3)');
				
			
						//Select		
						$sustituye->bindParam(':dia_semana',$dia_semana);		
						$sustituye->bindParam(':cod_hora',$cod_hora);	
						
						//Subselect
						$sustituye->bindParam(':fecha',$fecha_insert);
						$sustituye->bindParam(':fecha3',$fecha_insert);
						$sustituye->bindParam(':cod_ausente',$cod_profesor2["cod_profesor"]);
						$sustituye->bindParam(':cod_hora2',$cod_hora);
						$sustituye->bindParam(':cod_hora3',$cod_hora);
						
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
						echo "<pre>";
						print_r($sustituye_array);
						echo "</pre>";
						//Insertamos el sustituto y le damos métrica 1: 
				
						$insert_metricaPDO = $dbConnection_guardias->prepare('INSERT INTO historico_guardias SET cod_profesor = :cod_profesor , fecha = :fecha , cod_hora = :cod_hora , cod_ausente = :cod_ausente , metrica = :metrica');
						$insert_metricaPDO  -> bindParam(':cod_profesor',key($sustituye_array));
						$insert_metricaPDO  -> bindParam(':fecha',$fecha_insert);
						$insert_metricaPDO  -> bindParam(':cod_hora',$cod_hora);
						$insert_metricaPDO  -> bindParam(':cod_ausente',$cod_profesor2["cod_profesor"]);
						$insert_metricaPDO  -> bindParam(':metrica',$a = 1);
						$insert_metricaPDO  -> execute();			
						
						//Añado un día a mi fecha insert:
						
						
					}
				
				}
				
				//Sumo un día:
				//$dia_insert = strtotime($dia_insert . ' + 1 day');
				//$dia_insert2 = date("Y-m-d", $dia_insert);
				//echo "$dia_insert2 <br>";
				echo "<br>fecha insert vale:".$fecha_insert;
				echo "<br>fecha date(insert) vale:".date("Y-m-d",strtotime($fecha_insert));
				$fecha_insert = date("Y-m-d",strtotime($fecha_insert . " +1 day"));
				
				echo"<br>fecha_insert después de sumar un día: ". $fecha_insert;

				//Si el día fin es el mismo...
			}else if(date("Y-m-d",strtotime($fecha_insert)) == date("Y-m-d",strtotime($sesion2["fecha_fin"])) ){
			

				//Para cada hora...
				foreach( $horas_ocupadas_fin as $cod_hora ){
					
					//Hago que me salga el día de la semana:
					setlocale(LC_ALL,"es_ES.utf8");
					$dia_semana = strftime("%A", strtotime($fecha_insert));
					
					//Primero compruebo que a esa hora, ese día el código profesor tenga clase:	
					$clase = $dbConnection_guardias->prepare('SELECT * FROM horarios_profesores where cod_profesor = :cod_profesor AND dia = :dia AND cod_hora = :cod_hora');
					$clase->bindParam(':cod_profesor',$cod_profesor2["cod_profesor"]);
					$clase->bindParam(':dia',$dia_semana);
					$clase  -> bindParam(':cod_hora',$cod_hora);
					$clase->execute();
					//Si ese profe tiene clase a esa hora hago la insercción.
					if($clase->rowCount() > 0){			
					
						try{
							$consulta2 = $dbConnection_guardias->prepare("INSERT INTO `guardias`.`ausencias_profesores` (`cod_profesor`, `fecha`, `cod_hora`, `observaciones`) VALUES (:cod_profesor, :fecha, :cod_hora, :observaciones)");
							$consulta2  -> bindParam(':cod_profesor',$cod_profesor2["cod_profesor"]);
							$consulta2  -> bindParam(':fecha',$fecha_insert);
							$consulta2  -> bindParam(':cod_hora',$cod_hora);
							$consulta2  -> bindParam(':observaciones',$a="Actividad extraescolar");		
							$consulta2  -> execute();
						}catch(PDOException $e){
							echo $e;
							//continuo...
						}
						//Ahora agregamos al profesor con menos métrica como sustituto:
				
					
						$sustituye = $dbConnection_guardias->prepare('SELECT cod_profesor FROM horarios_profesores WHERE dia = :dia_semana and cod_hora = :cod_hora AND cod_grupo = "GUARDIA" AND aula <> "BIBLIOTECA" AND cod_profesor NOT IN 
							(SELECT cod_profesor FROM historico_guardias WHERE  fecha = :fecha AND cod_hora = :cod_hora2 AND cod_ausente != :cod_ausente) AND cod_profesor NOT IN 
								(SELECT cod_profesor FROM ausencias_profesores WHERE fecha = :fecha3 AND cod_hora = :cod_hora3)');
				
			
						//Select		
						$sustituye->bindParam(':dia_semana',$dia_semana);		
						$sustituye->bindParam(':cod_hora',$cod_hora);	
						$sustituye->bindParam(':cod_hora3',$cod_hora);	
						
						//Subselect
						$sustituye->bindParam(':fecha',$fecha_insert);
						$sustituye->bindParam(':fecha3',$fecha_insert);
						$sustituye->bindParam(':cod_ausente',$cod_profesor2["cod_profesor"]);
						$sustituye->bindParam(':cod_hora2',$cod_hora);
						
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
						echo "<pre>";
						print_r($sustituye_array);
						echo "</pre>";
						//Insertamos el sustituto y le damos métrica 1: 
				
						$insert_metricaPDO = $dbConnection_guardias->prepare('INSERT INTO historico_guardias SET cod_profesor = :cod_profesor , fecha = :fecha , cod_hora = :cod_hora , cod_ausente = :cod_ausente , metrica = :metrica');
						$insert_metricaPDO  -> bindParam(':cod_profesor',key($sustituye_array));
						$insert_metricaPDO  -> bindParam(':fecha',$fecha_insert);
						$insert_metricaPDO  -> bindParam(':cod_hora',$cod_hora);
						$insert_metricaPDO  -> bindParam(':cod_ausente',$cod_profesor2["cod_profesor"]);
						$insert_metricaPDO  -> bindParam(':metrica',$a = 1);
						$insert_metricaPDO  -> execute();			
						
						
						
						
					}
				
				}
				$fecha_insert = date("Y-m-d",strtotime($fecha_insert . " +1 day"));
			}else {

				//Para cada hora de los días que están completamente ocupados...
				foreach( $todas_las_horas as $cod_hora ){
					
					//Hago que me salga el día de la semana:
					setlocale(LC_ALL,"es_ES.utf8");
					$dia_semana = strftime("%A", strtotime($fecha_insert));
					
					//Primero compruebo que a esa hora, ese día el código profesor tenga clase:	
					$clase = $dbConnection_guardias->prepare('SELECT * FROM horarios_profesores where cod_profesor = :cod_profesor AND dia = :dia AND cod_hora = :cod_hora');
					$clase->bindParam(':cod_profesor',$cod_profesor2["cod_profesor"]);
					$clase->bindParam(':dia',$dia_semana);
					$clase  -> bindParam(':cod_hora',$cod_hora);
					$clase->execute();
					//Si ese profe tiene clase a esa hora hago la insercción.
					if($clase->rowCount() > 0){			
					
						//Le hago un try catch para que la ejecución continúe si el profesor ya tiene isnertada la falta y no la puede isnertar:
						try{
							$consulta2 = $dbConnection_guardias->prepare("INSERT INTO `guardias`.`ausencias_profesores` (`cod_profesor`, `fecha`, `cod_hora`, `observaciones`) VALUES (:cod_profesor, :fecha, :cod_hora, :observaciones)");
							$consulta2  -> bindParam(':cod_profesor',$cod_profesor2["cod_profesor"]);
							$consulta2  -> bindParam(':fecha',$fecha_insert);
							$consulta2  -> bindParam(':cod_hora',$cod_hora);
							$consulta2  -> bindParam(':observaciones',$a="Actividad extraescolar");		
							$consulta2  -> execute();
						}catch(PDOException $e){
							echo $e;
							//continuo...
						}
						//Ahora agregamos al profesor con menos métrica como sustituto:
				
					
						$sustituye = $dbConnection_guardias->prepare('SELECT cod_profesor FROM horarios_profesores WHERE dia = :dia_semana and cod_hora = :cod_hora AND cod_grupo = "GUARDIA" AND aula <> "BIBLIOTECA" AND cod_profesor NOT IN 
							(SELECT cod_profesor FROM historico_guardias WHERE  fecha = :fecha AND cod_hora = :cod_hora2 AND cod_ausente != :cod_ausente) AND cod_profesor NOT IN 
								(SELECT cod_profesor FROM ausencias_profesores WHERE fecha = :fecha3 AND cod_hora = :cod_hora3)');
				
			
						//Select		
						$sustituye->bindParam(':dia_semana',$dia_semana);		
						$sustituye->bindParam(':cod_hora',$cod_hora);	
						$sustituye->bindParam(':cod_hora3',$cod_hora);	
						
						//Subselect
						$sustituye->bindParam(':fecha',$fecha_insert);
						$sustituye->bindParam(':fecha3',$fecha_insert);
						$sustituye->bindParam(':cod_ausente',$cod_profesor2["cod_profesor"]);
						$sustituye->bindParam(':cod_hora2',$cod_hora);
						
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
						echo "<pre>";
						print_r($sustituye_array);
						echo "</pre>";
						//Insertamos el sustituto y le damos métrica 1: 
				
						$insert_metricaPDO = $dbConnection_guardias->prepare('INSERT INTO historico_guardias SET cod_profesor = :cod_profesor , fecha = :fecha , cod_hora = :cod_hora , cod_ausente = :cod_ausente , metrica = :metrica');
						$insert_metricaPDO  -> bindParam(':cod_profesor',key($sustituye_array));
						$insert_metricaPDO  -> bindParam(':fecha',$fecha_insert);
						$insert_metricaPDO  -> bindParam(':cod_hora',$cod_hora);
						$insert_metricaPDO  -> bindParam(':cod_ausente',$cod_profesor2["cod_profesor"]);
						$insert_metricaPDO  -> bindParam(':metrica',$a = 1);
						$insert_metricaPDO  -> execute();			
						
						
						
						
					}
				
				}
				$fecha_insert = date("Y-m-d",strtotime($fecha_insert . " +1 day"));
				echo "<br>dia_insert_dia intermedio: ".date("Y-m-d", strtotime($fecha_insert));
			}

		echo "<br>-----FIN VUELTA WHILE-----<br>";
		}
	}











		
		
	}
	
	echo "<pre>";
	print_r($cod_profesor_array );
	echo "</pre>";
	
	//Ahora busco el/los cod_hora 
	
    echo "cod_sesion: ".$cod_sesion;
}

//asignar_falta_extraescolar(20);
?>