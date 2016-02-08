<?php
include_once('conectar_bd.php');
//include_once('iniciar_sesion.php');
//echo  iniciar_sesion();


//Nos conectamos (creamos el objeto) a guardias:
$dbConnection_guardias = conectar(guardias);
//Ahora nos conectamos (creamos el objeto) a actividades extraescolares:
$dbConnection_actividades_profesores = conectar(actividades_extraescolares);



// "codHora_7__fecha_2015-04-27__sustituto_10000064__falta_10000074",
//"codHora_7__fecha_2015-04-27__sustituto_10000046__falta_10000074"





//Recuperamos la lista de sustitutos:
$modificacion = $_POST["sustitutos"];

//Comprobamos si hay que hacer algún cambio, si no, paramos el script:
if(!isset($modificacion[0])) {
	die();
}



//Sacamos profesor ausente, profsores sustitutos, cod_hora y día. Lo guardamos en $lista.

//echo "modifica: ";
foreach($modificacion as $key => $modifica){
	$modifica2 = explode("__", $modifica);
	$lista[$key]= $modifica2;
	foreach($lista[$key] as $key2 => $listado){
		$temp = explode("_",$listado);
		$lista[$key][$temp[0]] = $temp[1];
		unset($lista[$key][$key2]);
		if ($temp[0] == "sustituto"){
			$lista_sustitutos[] = $temp[1];
		}
	}
}
/*echo "listasustitutos: ";
print_r($lista_sustitutos);*/

/*echo "lista: ";
print_r($lista);*/

//Guardo en $array_sustitutos_bd una lista con todos los sustitutos previamente asignados.
$sustitutos_anteriores = $dbConnection_guardias->prepare('SELECT cod_profesor, metrica FROM historico_guardias WHERE cod_ausente = :cod_ausente AND fecha = :fecha AND cod_hora = :cod_hora');
$sustitutos_anteriores  -> bindParam(':cod_ausente',$lista[0]["falta"]);
$sustitutos_anteriores  -> bindParam(':fecha',$lista[0]["fecha"]);
$sustitutos_anteriores  -> bindParam(':cod_hora',$lista[0]["codHora"]);
$sustitutos_anteriores  -> execute();

$i = 0;
$x = 0;
while($obj_sustitutos_anteriores  = $sustitutos_anteriores  -> fetch(PDO::FETCH_OBJ)) {
	//Todos los sustitutos que hay en un principio:
	$array_sustitutos_bd[] = $obj_sustitutos_anteriores->cod_profesor;
	
	//Todos los que ya estaban anteriormente en la bd y los que tenemos que borrar:
	if(in_array($obj_sustitutos_anteriores->cod_profesor, $lista_sustitutos)){
		$repetido[$i]["cod_profesor"] = $obj_sustitutos_anteriores->cod_profesor;
		if($obj_sustitutos_anteriores->metrica == NULL) {
			$repetido[$i]["metrica"] = 0;
		}else{
			$repetido[$i]["metrica"] = $obj_sustitutos_anteriores->metrica;
		}
			
		$i++;
	}else{
		$borrar[$x]["cod_profesor"] = $obj_sustitutos_anteriores->cod_profesor;
		if($obj_sustitutos_anteriores->metrica == NULL) {			
			$borrar[$x]["metrica"] = 0;
		}else{
			$repetido[$i]["metrica"] = $obj_sustitutos_anteriores->metrica;
		}
		$x++;
	}
}

/*echo "sustitutos anteriores: <br>	";
print_r($array_sustitutos_bd);*/


//Los sustitutos que hay que insertar.
$x = 0;
foreach ($lista_sustitutos as $listado){
	
	
	if (!in_array($listado,$array_sustitutos_bd)){
		$insertar[$x]["cod_profesor"] = $listado;
		$x++;
	}
}
/*echo "<br>repetido: ";
print_r($repetido);
echo "borrar: ";
print_r($borrar);
unset($borrar);
echo "insertar: ";
print_r($insertar);*/

//Si hay que borrar o insertar primero quito la métrica que había:
if ((isset($borrar) || isset($insertar)) && count($repetido)>0){
	//Calculamos la métrica que hay que restar.
	$metrica = count($array_sustitutos_bd);
	$metrica = 1/$metrica;
	//echo "metrica: ".$metrica;
			
	//Guardamos la nueva metrica en la bd:
	foreach($repetido as $repe){
		//echo "repe: ";
		print_r($repe);
		$repePDO = $dbConnection_guardias->prepare('update historico_guardias set metrica = :metrica  WHERE cod_profesor = :cod_profesor AND fecha = :fecha AND cod_hora = :cod_hora');
		$tmp_metrica = ($repe["metrica"]-$metrica);		
		$repePDO  -> bindParam(':metrica',$tmp_metrica);
		unset($tmp_metrica);
		$repePDO  -> bindParam(':cod_profesor',$repe["cod_profesor"]);
		$repePDO  -> bindParam(':fecha',$lista[0]["fecha"]);
		$repePDO  -> bindParam(':cod_hora',$lista[0]["codHora"]);
		$repePDO  -> execute();
	}
	
	//Calculamos la métrica que hay que sumar:
	/*echo "repetido".count($repetido)."<br>";
	echo "insertar".count($insertar)."<br>";
	echo "borrar".count($borrar)."<br>";*/
	$metrica = count($repetido)+count($insertar)-count($borrar);
	$metrica = 1/$metrica;
	/*echo"metrica a sumar: ".$metrica;*/
	
	
	//Si tengo que añadir sustitutos:
	if(isset($insertar)){

		foreach($insertar as $insert){
			
			//Calculamos primero la métrica
			$insert_metricaPDO = $dbConnection_guardias->prepare('SELECT metrica FROM historico_guardias where cod_profesor = :cod_profesor');
			$insert_metricaPDO  -> bindParam(':cod_profesor',$insert["cod_profesor"]);
			$insert_metricaPDO  -> execute();
			$metrica_insert = 0;
			while($metrica2 = $insert_metricaPDO->fetch(PDO::FETCH_ASSOC)){				
				$metrica_insert = $metrica_insert + $metrica["metrica"];
			}
			$metrica_final = $metrica_insert + $metrica;


			//Insertamos los datos:
			$insertPDO = $dbConnection_guardias->prepare('INSERT INTO historico_guardias set metrica = :metrica, cod_profesor = :cod_profesor, fecha = :fecha, cod_hora = :cod_hora, cod_ausente = :cod_ausente');
			$insertPDO  -> bindParam(':metrica',$metrica_final);
			$insertPDO  -> bindParam(':cod_profesor',$insert["cod_profesor"]);
			$insertPDO  -> bindParam(':fecha',$lista[0]["fecha"]);
			$insertPDO  -> bindParam(':cod_hora',$lista[0]["codHora"]);
			$insertPDO  -> bindParam(':cod_ausente',$lista[0]["falta"]);
			$insertPDO  -> execute();
		}
	}
	
	
	//Si tengo que eliminar sustitutos:
	if(isset($borrar)) {
		foreach($borrar as $borr){
			
			//Borramos los datos:
			$borrPDO = $dbConnection_guardias->prepare('DELETE FROM historico_guardias WHERE cod_profesor = :cod_profesor AND fecha = :fecha AND cod_hora = :cod_hora LIMIT 30');
			echo "bORROpdo". $borroPDO;
			$borrPDO  -> bindParam(':cod_profesor',$insert["cod_profesor"]);
			$borrPDO  -> bindParam(':fecha',$lista[0]["fecha"]);
			$borrPDO  -> bindParam(':cod_hora',$lista[0]["codHora"]);
			$borrPDO  -> execute();
			echo "he borrado datos";
		}
	}
	
}




?>