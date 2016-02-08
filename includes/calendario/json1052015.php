<?php session_start(); ?>
<?php
include_once('../conectar_bd.php');
//Función para la función de php uasort, que ordena los vectores.
function ordename ($a, $b) {
  	return $a['metrica'] - $b['metrica'];
}

if (!isset($_GET['fecha_calendario'])) {
	die("Please provide a date range.");
}
$fecha_calendario = $_GET['fecha_calendario'];
$dia_semana = $_GET['dia_semana'];
//echo "dia semana". $dia_semana;

//Creamos el array resultado:
$arr = array();

//Nos conectamos (creamos el objeto) a guardias:
$dbConnection_guardias = conectar(guardias);
//Ahora nos conectamos (creamos el objeto) a actividades extraescolares:
$dbConnection_actividades_profesores = conectar(actividades_extraescolares);

//Consulta de todas las horas de un día:
$horas = $dbConnection_guardias->prepare('SELECT * FROM horas');
$horas -> execute();

while($obj = $horas -> fetch(PDO::FETCH_OBJ)) {
	$arr[$obj->cod_hora]["horario"] = array("cod_hora" => $obj->cod_hora,"hora_inicio" => $obj->hora_inicio, "hora_fin" => $obj->hora_fin);
	
	//Consulta de profesores que faltan un día concreto a esa hora:
	$guardias = $dbConnection_guardias->prepare('SELECT cod_profesor AS profesor FROM ausencias_profesores WHERE fecha = :fecha_calendario AND cod_hora = :cod_hora');
	$guardias -> bindParam(':fecha_calendario',$fecha_calendario);
	$guardias -> bindParam(':cod_hora',$obj->cod_hora);
	$guardias -> execute();
		
	//Para cada profesor sacamos el nombre, el grupo, etc.
	//$arr[$obj->cod_hora]["falta"] = array();
	while($objGuardias = $guardias -> fetch(PDO::FETCH_OBJ)) {
		$arr[$obj->cod_hora]["falta"][$objGuardias->profesor] = array();
		//Buscamos su nombre:
		$nombreProfesor = $dbConnection_actividades_profesores->prepare('SELECT nombre_completo FROM profesores WHERE cod_profesor =:cod_profesor');
		$nombreProfesor->bindParam(':cod_profesor',$objGuardias->profesor);
		$nombreProfesor->execute();
		$nombreProfesor = $nombreProfesor->fetch(PDO::FETCH_ASSOC);
		//Lo añadimos al objeto para añadirlo al array:
		$arr[$obj->cod_hora]["falta"][$objGuardias->profesor] = $nombreProfesor ;

		//Buscamos el/los grupos al que falta:
		$cod_grupo = $dbConnection_guardias->prepare('SELECT cod_grupo FROM horarios_profesores WHERE cod_profesor =:cod_profesor AND dia =:dia_semana AND cod_hora = :cod_hora');

		$cod_grupo->bindParam(':cod_profesor',$objGuardias->profesor);
		$cod_grupo->bindParam(':dia_semana',$dia_semana);
		$cod_grupo->bindParam(':cod_hora',$obj->cod_hora);
		$cod_grupo->execute();
		while($obj_cod_grupo = $cod_grupo->fetch(PDO::FETCH_ASSOC)){
			$arr[$obj->cod_hora]["falta"][$objGuardias->profesor]["cod_grupo"][] = $obj_cod_grupo["cod_grupo"];
		}
		
		//Buscamos el aula y la asignatura:
		$nombreAula = $dbConnection_guardias->prepare('SELECT aula, asignatura FROM horarios_profesores WHERE cod_profesor =:cod_profesor AND dia =:dia_semana AND cod_hora = :cod_hora AND cod_grupo = :cod_grupo');
		$nombreAula->bindParam(':cod_profesor',$objGuardias->profesor);
		$nombreAula->bindParam(':dia_semana',$dia_semana);
		$nombreAula->bindParam(':cod_hora',$obj->cod_hora);
		$nombreAula->bindParam(':cod_grupo',$arr[$obj->cod_hora]["falta"][$objGuardias->profesor]["cod_grupo"][0]);
		$nombreAula->execute();
		$nombreAula = $nombreAula->fetch();
		$arr[$obj->cod_hora]["falta"][$objGuardias->profesor]["aula"] = $nombreAula["aula"];
		$arr[$obj->cod_hora]["falta"][$objGuardias->profesor]["asignatura"] = $nombreAula["asignatura"];
		/*
			Sustitutos:
							*/
		//Hacemos la lista de profesores que le pueden sustituir:
		//$sustituye = $dbConnection_actividades_profesores->prepare('SELECT cod_profesor FROM horarios_profesores WHERE dia = :dia_semana and cod_hora = :cod_hora AND cod_grupo = "GUARDIA" AND aula <> "BIBLIOTECA"');	
		$sustituye = $dbConnection_guardias->prepare('SELECT cod_profesor FROM horarios_profesores WHERE dia = :dia_semana and cod_hora = :cod_hora AND cod_grupo = "GUARDIA" AND aula <> "BIBLIOTECA" AND cod_profesor NOT IN 
			(SELECT cod_profesor FROM historico_guardias WHERE  fecha = :fecha AND cod_hora = :cod_hora2 AND cod_ausente != :cod_ausente)');

		//Select		
		$sustituye->bindParam(':dia_semana',$dia_semana);		
		$sustituye->bindParam(':cod_hora',$obj->cod_hora);	
		
		//Subselect
		$sustituye->bindParam(':fecha',$fecha_calendario);
		$sustituye->bindParam(':cod_ausente',$objGuardias->profesor);
		$sustituye->bindParam(':cod_hora2',$obj->cod_hora);
		
		$sustituye->execute();
		$sustituye = $sustituye->fetchAll();
		$sustituye_array = array();
		//Nos hacemos el array con cada cod_profesor que puede sustituir:
		foreach($sustituye as $fila){
			
			//$sustituye_array[$fila[cod_profesor]]=array();
			$sustituye_array[]=array("cod_profesor"=>$fila[cod_profesor]);
		}
		
		//Buscamos el nombre de cada profesor que puede sustituir:
		foreach($sustituye_array as $key=> $valor){
			$nombreProfesor = $dbConnection_actividades_profesores->prepare('SELECT nombre_completo FROM profesores WHERE cod_profesor =:cod_profesor');
			$nombreProfesor->bindParam(':cod_profesor',$valor["cod_profesor"]);
			$nombreProfesor->execute();
			$nombreProfesor = $nombreProfesor->fetch(PDO::FETCH_ASSOC);
			//Lo guardo en el vector:
			$sustituye_array[$key]["nombre_completo"] = $nombreProfesor[nombre_completo];
			
			//Ahora buscamos su métrica:
			$metrica = $dbConnection_guardias->prepare('SELECT metrica FROM historico_guardias where cod_profesor = :cod_profesor');
			$metrica->bindParam(':cod_profesor',$valor["cod_profesor"]);
			$metrica->execute();
			$metrica_final = 0;
			while($metrica2 = $metrica->fetch(PDO::FETCH_ASSOC)){				
				$metrica_final = $metrica_final + $metrica2["metrica"];
			}
			$metrica = $metrica_final;
			//Lo guardo en el array:
			$sustituye_array[$key][metrica] = $metrica;
			
			//Ahora quien tiene la sustitución asignada:
			$hace_sustitucion = $dbConnection_guardias->prepare('SELECT * FROM historico_guardias WHERE cod_profesor =:cod_profesor AND fecha =:fecha AND cod_hora = :cod_hora AND cod_ausente = :cod_ausente');

			$hace_sustitucion->bindParam(':cod_profesor',$valor["cod_profesor"]);
			$hace_sustitucion->bindParam(':fecha',$fecha_calendario);
			$hace_sustitucion->bindParam(':cod_hora',$obj->cod_hora);
			$hace_sustitucion->bindParam(':cod_ausente',$objGuardias->profesor);
			$hace_sustitucion->execute();
			
			$hace_sustitucion = $hace_sustitucion->fetch();
			if ($hace_sustitucion != null){
				$sustituye_array[$key]["sustituye"] = 1;
			}else{
				$sustituye_array[$key]["sustituye"] = 0;
			}
			
			
		}
				

		//Ordenamos el array para que los profesores con menos metrica salgan primero:
		//uasort($sustituye_array, 'ordename');
		usort($sustituye_array, 'ordename');
		$arr[$obj->cod_hora]["falta"][$objGuardias->profesor]["sustituye"] = $sustituye_array;
		
	}
}
/*echo "<pre>";
print_r($arr);
echo "</pre>";*/
echo json_encode($arr);
/*$hola = exec('php ../iniciar_sesion.php');
$hola = json_decode($hola, true);
echo $hola["sesion_iniciada"];
print_r($hola);*/
?>