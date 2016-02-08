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

//Consulta de profesores que faltan un día concreto:
$guardias = $dbConnection_guardias->prepare('SELECT cod_profesor AS profesor, cod_hora FROM ausencias_profesores WHERE fecha = :fecha_calendario');
$guardias -> bindParam(':fecha_calendario',$fecha_calendario);
$guardias -> execute();

//Para cada profesor sacamos el nombre, el grupo, etc.
while($obj = $guardias -> fetch(PDO::FETCH_OBJ)) {

	//Cuando falte un profesor buscamos su nombre:
	$nombreProfesor = $dbConnection_actividades_profesores->prepare('SELECT nombre_completo FROM profesores WHERE cod_profesor =:cod_profesor');
	$nombreProfesor->bindParam(':cod_profesor',$obj->profesor);
	$nombreProfesor->execute();
	$nombreProfesor = $nombreProfesor->fetch(PDO::FETCH_ASSOC);
	//Lo añadimos al objeto para añadirlo al array:
	$obj-> nombre_completo = $nombreProfesor[nombre_completo];

	//Buscamos el aula al que falta:
	$nombreAula = $dbConnection_guardias->prepare('SELECT aula FROM horarios_profesores WHERE cod_profesor =:cod_profesor AND dia =:dia_semana AND cod_hora = :cod_hora');
	$nombreAula->bindParam(':cod_profesor',$obj->profesor);
	$nombreAula->bindParam(':dia_semana',$dia_semana);
	$nombreAula->bindParam(':cod_hora',$obj->cod_hora);
	$nombreAula->execute();
	//$nombreAula = $nombreAula->fetch(PDO::FETCH_ASSOC);
	$nombreAula = $nombreAula->fetchAll();
	$nombreAula_array = array();
	foreach($nombreAula as $key => $valor){
		$nombreAula_array[$key]["aula"] = $valor["aula"];
	}


	//Lo añadimos al objeto para añadirlo al array:
	//$obj-> nombre_aula = $nombreAula["aula"];
	
	//Buscamos el grupo al que falta:
	/*$nombreGrupo = $dbConnection_actividades_profesores->prepare('SELECT nombre FROM actividades_extraescolares.grupos where cod_grupo = 
																						(select cod_grupo from guardias.horarios_profesores where cod_profesor = :cod_profesor and dia = :dia and cod_hora = :cod_hora )');	
	$nombreGrupo->bindParam(':cod_profesor', $obj->profesor);	
	$nombreGrupo->bindParam(':cod_hora', $obj->cod_hora);	
	$nombreGrupo->bindParam(':dia', $dia_semana);	
	$nombreGrupo->execute();
	$nombreGrupo = $nombreGrupo ->fetch(PDO::FETCH_ASSOC);
	$obj-> nombre_grupo = $nombreGrupo[nombre];*/
	
	//Hacemos la lista de profesores que le pueden sustituir:
	$sustituye = $dbConnection_actividades_profesores->prepare('SELECT cod_profesor FROM horarios_profesores WHERE dia = :dia_semana and cod_hora = :cod_hora AND cod_grupo = "GUARDIA" AND aula <> "BIBLIOTECA"');	
	$sustituye->bindParam(':dia_semana',$dia_semana);		
	$sustituye->bindParam(':cod_hora',$obj->cod_hora);		
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
		$metrica = $metrica->fetch(PDO::FETCH_ASSOC);
		//Lo guardo en el array:
		$sustituye_array[$key][metrica] = $metrica[metrica];
	}

	//Ordenamos el array para que los profesores con menos metrica salgan primero:
	//uasort($sustituye_array, 'ordename');
	usort($sustituye_array, 'ordename');


	$obj -> sustituye = $sustituye_array;
	
	$arr[] = $obj;

	
}
	echo "<pre>";		
		print_r ($nombreAula_array);
	echo "</pre>";
	/*echo "<pre>";
	print_r($arr);
	echo "</pre>";*/
echo json_encode($arr);
//echo json_encode($obj);


?>