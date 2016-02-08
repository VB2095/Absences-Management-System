<?php session_start(); ?>
<?php
include_once('conectar_bd.php');

function asignar_falta_extraescolar($cod_sesion)
{
	
	//Nos conectamos (creamos el objeto) a guardias:
	$dbConnection_guardias = conectar(guardias);
	//Ahora nos conectamos (creamos el objeto) a actividades extraescolares:
	$dbConnection_actividades_profesores = conectar(actividades_extraescolares);

	//Saco los datos de la sesión
	$sesion = $dbConnection_actividades_profesores->prepare('SELECT * FROM sesiones where cod_sesion = :cod_sesion');
	$sesion->bindParam(':cod_sesion',$cod_sesion);
	$sesion->execute();
	$sesion2 = $sesion->fetch();


	//Saco las horas afectadas:
	
	$horas = $dbConnection_guardias->prepare('SELECT * FROM horas');
	$horas->execute();
	

	echo "<br>";
	
	//Si la fecha de inicio y de fin son las mismas sólamente hay falta de un día y miro las fechas:
	

	$horas_ocupadas = array();
	$horas_ocupadas_inicio = array();
	$horas_ocupadas_fin = array();
	while($hora = $horas->fetch(PDO::FETCH_ASSOC)){
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
	echo "horas ocupadas:<pre>";
	print_r($horas_ocupadas);
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





		$consulta2 = $dbConnection_guardias->prepare("INSERT INTO `guardias`.`ausencias_profesores` (`cod_profesor`, `fecha`, `cod_hora`, `observaciones`, `link`) VALUES (:cod_profesor, :fecha, :cod_hora, :observaciones, :link)");
		$consulta2  -> bindParam(':cod_profesor',$cod_profesor2["cod_profesor"]);
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
		
	}

	
	
	//Ahora agregamos al profesor con menos métrica como sustituto:

	
	$sustituye = $dbConnection_guardias->prepare('SELECT cod_profesor FROM horarios_profesores WHERE dia = :dia_semana and cod_hora = :cod_hora AND cod_grupo = "GUARDIA" AND aula <> "BIBLIOTECA" AND cod_profesor NOT IN 
			(SELECT cod_profesor FROM historico_guardias WHERE  fecha = :fecha AND cod_hora = :cod_hora2 AND cod_ausente != :cod_ausente)');

		//Select		
		$sustituye->bindParam(':dia_semana',$_POST["dia_semana"]);		
		$sustituye->bindParam(':cod_hora',$_POST["cod_hora_agregar_ausencia"]);	
		
		//Subselect
		$sustituye->bindParam(':fecha',$_POST["fecha_calendario_agregar_ausencia"]);
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
	
	echo "<pre>";
	print_r($cod_profesor_array );
	echo "</pre>";
	
	//Ahora busco el/los cod_hora 
	
    echo "cod_sesion: ".$cod_sesion;
}

asignar_falta_extraescolar(20);
?>