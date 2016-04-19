<?php session_start(); ?>
<?php
include_once('../conectar_bd.php');
//Funci�n para la funci�n de php uasort, que ordena los vectores.
function ordename ($a, $b) {
  	return $a['metrica'] - $b['metrica'];
}

if (!isset($_GET['fecha_calendario']) || !isset($_SESSION["cod_profesor"])) {
	die("Por favor, incluye una fecha y/o inicia sesi�n.");
}

//Ahora comprobamos si soy fake_cod_profesor para que el administrador pueda editar cualquier profesor:

if(isset($_SESSION["fake_cod_profesor"])) {
	$session_cod_profesor = $_SESSION["fake_cod_profesor"];
}else {
	$session_cod_profesor = $_SESSION["cod_profesor"];
}
//A partir de este momento usar� siempre este session_cod_profesor.

$fecha_calendario = $_GET['fecha_calendario'];
$dia_semana = $_GET['dia_semana'];
//echo "dia semana". $dia_semana;

//Creamos el array resultado:
$arr = array();

//Nos conectamos (creamos el objeto) a guardias:
$dbConnection_guardias = conectarBD('guardias');
//Ahora nos conectamos (creamos el objeto) a actividades extraescolares:
$dbConnection_actividades_profesores = conectarBD('actividades_extraescolares');

//Consulta de todas las horas de un d�a:
$horas = $dbConnection_guardias->prepare('SELECT * FROM horas');
$horas -> execute();

while($obj = $horas -> fetch(PDO::FETCH_OBJ)) {
	$arr[$obj->cod_hora]["horario"] = array("cod_hora" => $obj->cod_hora,"hora_inicio" => $obj->hora_inicio, "hora_fin" => $obj->hora_fin);

	//Consulta de profesores que faltan un d�a concreto a esa hora:
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
		//Lo a�adimos al objeto para a�adirlo al array:
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

		//Sacamos las observaciones y/o los enlaces:
		$observaciones_enlaces2 = $dbConnection_guardias->prepare('SELECT observaciones, link FROM ausencias_profesores WHERE cod_profesor =:cod_profesor AND cod_hora = :cod_hora AND fecha = :fecha_calendario');
		$observaciones_enlaces2->bindParam(':cod_profesor',$objGuardias->profesor);
		$observaciones_enlaces2 -> bindParam(':fecha_calendario',$fecha_calendario);
		$observaciones_enlaces2->bindParam(':cod_hora',$obj->cod_hora);
		$observaciones_enlaces2->execute();
		//$observaciones_enlaces = $observaciones_enlaces2->fetch();
		while ($observaciones_enlaces = $observaciones_enlaces2->fetch()){

			if($observaciones_enlaces[link] != null){
				$arr[$obj->cod_hora]["falta"][$objGuardias->profesor]["link"] = $observaciones_enlaces[link];
			}
			if($observaciones_enlaces[observaciones] != null){
				//echo "entro en observaciones: " . $observaciones_enlaces[observaciones];
				$arr[$obj->cod_hora]["falta"][$objGuardias->profesor]["observaciones"] = $observaciones_enlaces[observaciones];
			}
		}



		/*
			Sustitutos:
							*/
		//Hacemos la lista de profesores que le pueden sustituir:

		$sustituye = $dbConnection_guardias->prepare('SELECT cod_profesor FROM horarios_profesores WHERE dia = :dia_semana and cod_hora = :cod_hora AND cod_grupo = "GUARDIA" AND aula <> "BIBLIOTECA" AND cod_profesor NOT IN
			(SELECT cod_profesor FROM historico_guardias WHERE  fecha = :fecha AND cod_hora = :cod_hora2 AND cod_ausente != :cod_ausente) AND cod_profesor NOT IN
								(SELECT cod_profesor FROM ausencias_profesores WHERE fecha = :fecha3 AND cod_hora = :cod_hora3)');

		//Select
		$sustituye->bindParam(':dia_semana',$dia_semana);
		$sustituye->bindParam(':cod_hora',$obj->cod_hora);

		//Subselect
		$sustituye->bindParam(':fecha',$fecha_calendario);
		$sustituye->bindParam(':cod_ausente',$objGuardias->profesor);
		$sustituye->bindParam(':cod_hora2',$obj->cod_hora);

		//Subselect 3:
		$sustituye->bindParam(':fecha3',$fecha_calendario);
		$sustituye->bindParam(':cod_hora3',$obj->cod_hora);

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

			//Ahora buscamos su m�trica:
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

			//Ahora quien tiene la sustituci�n asignada:
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

		if(count($sustituye_array)>0) {
			foreach($sustituye_array as $key => $value){
				$ordenar[$key] = $value["metrica"];
			}

			$ordenar2 = asort($ordenar);


			foreach($ordenar as $key => $value){
				$sustituye_array2[] = $sustituye_array[$key];
			}


			$arr[$obj->cod_hora]["falta"][$objGuardias->profesor]["sustituye"] = $sustituye_array2;
			unset($ordenar);
			unset($sustituye_array2);
		}else {
			$arr[$obj->cod_hora]["falta"][$objGuardias->profesor]["sustituye"] = array();
		}

	}

	//Si el profesor es "P" o "A" tambi�n sacamos si da clase a esa hora. En caso afirmativo sacamos tambi�n el resto de informaci�n:
	if($_SESSION["tipo"] == "P" || $_SESSION["tipo"] == "A"){

		//Select para saber si tiene clase:
		$tiene_clase1 = $dbConnection_guardias->prepare('SELECT * FROM horarios_profesores WHERE cod_profesor =:cod_profesor AND dia =:dia_semana AND cod_hora = :cod_hora');
		$tiene_clase1 -> bindParam(':dia_semana',$dia_semana);
		$tiene_clase1 -> bindParam(':cod_hora',$obj->cod_hora);
		$tiene_clase1 -> bindParam(':cod_profesor',$session_cod_profesor);
		$tiene_clase1 -> execute();
		//print_r($tiene_clase1);
		//Si el resultado me da 1 o m�s filas:
		while($tiene_clase = $tiene_clase1 -> fetch(PDO::FETCH_OBJ)) {
			$arr[$obj->cod_hora]["tiene_clase"]["asignatura"] = $tiene_clase -> asignatura;
			$arr[$obj->cod_hora]["tiene_clase"]["aula"] = $tiene_clase -> aula;
			$arr[$obj->cod_hora]["tiene_clase"]["cod_grupo"][] = $tiene_clase -> cod_grupo;
		}
	}

}
/*echo $session_cod_profesor;
echo "<pre>";
print_r($arr);
echo "</pre>";*/
echo json_encode($arr);
/*$hola = exec('php ../iniciar_sesion.php');
$hola = json_decode($hola, true);
echo $hola["sesion_iniciada"];
print_r($hola);*/
?>
