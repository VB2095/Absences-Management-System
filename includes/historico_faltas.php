<?php session_start(); ?>
<?php
include_once('conectar_bd.php');


if (!$_SESSION['lang']){
	$_SESSION['lang'] = "es";
}
require_once('lenguajes/'.$_SESSION['lang'].'_lang.php');


if(isset($_SESSION["fake_cod_profesor"])) {
	$session_cod_profesor = $_SESSION["fake_cod_profesor"];
}else {
	$session_cod_profesor = $_SESSION["cod_profesor"];
}
//A partir de este momento usaré siempre este session_cod_profesor.



		
?>
<!doctype html>
<html lang="<?php echo $_SESSION[lang] ?>">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Sistema de gestión de faltas de profesores de instituto">
   <meta name="author" content="Víctor Sesma Ramón" >
	<title><?php echo title_historico_faltas ?></title>	
	<!--Bootstrap-->
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="bootstrap-3.3.4-dist/css/bootstrap.min.css">
</head>
<body>
<?php
//Cambio los locales para que el nombre del mes salga el adecuado.
if ($_SESSION[lang] == "va"){
	setlocale(LC_ALL,"ca_ES.utf8");
}else if( $_SESSION[lang] == "es"){
	setlocale(LC_ALL,"es_ES.utf8");
} else if($_SESSION[lang] == "en") {
	setlocale(LC_ALL,"en_EN.utf8");
}else{
	setlocale(LC_ALL,$_SESSION["lang"]."_ES");
}


//echo strftime("%B", strtotime($_GET["mes"]."/1/".$_GET["ano"]));

if (isset($_SESSION["tipo"]) && isset($_GET["ano"]) && isset($_GET["mes"])) {
	//Nos conectamos (creamos el objeto) a guardias:
	$dbConnection_guardias = conectarBD(guardias);
	//Ahora nos conectamos (creamos el objeto) a actividades extraescolares:
	$dbConnection_actividades_profesores = conectarBD(actividades_extraescolares);
	
	//
	
	
	//Creo cabecera de tabla
?>


<?php
	//Consulta de todas las horas de un día:
	$horas2 = $dbConnection_guardias->prepare('SELECT * FROM horas');
	$horas2 -> execute();
	
	$horas = array();
	while($obj = $horas2 -> fetch(PDO::FETCH_OBJ)) {
		$horas[] = $obj->cod_hora;
	}

	//Días del mes:
	$dias = cal_days_in_month(CAL_GREGORIAN, $_GET["mes"], $_GET["ano"]);


	//Si el profesor es "P"
	if($_SESSION["tipo"] == "P" || $_SESSION["tipo"] == "A") {
		
		//Saco su nombre:
		$nombreProfesor = $dbConnection_actividades_profesores->prepare('SELECT nombre_completo FROM profesores WHERE cod_profesor =:cod_profesor');
		$nombreProfesor->bindParam(':cod_profesor',$session_cod_profesor);
		$nombreProfesor->execute();
		$nombreProfesor = $nombreProfesor->fetch(PDO::FETCH_ASSOC);
		
		
		
		
		
		
		//Cabecera de la tabla
	?>
		<table  class="table table-bordered table-hover table-striped table-condensed">
		<caption> <?php echo faltas.$nombreProfesor["nombre_completo"]." (".$session_cod_profesor.") - ". strftime("%B", strtotime($_GET["mes"]."/1/".$_GET["ano"]))." ".$_GET["ano"] ;?></caption>
		<thead>
			<tr>
				<th><?php echo dia; ?></th>
				<th><?php echo profesor; ?></th>
				<th><?php echo falta_hora; ?></th>
				<th><?php echo observaciones_min; ?></th>
			</tr>
		</thead>
		<tbody>				
		
		
		
	<?php
	//Para cada día del mes:
	for($dia = 1; $dia <= $dias; $dia++){
			
		//Para cada hora del día:
		foreach($horas as $hora){

			//Saco si ha faltado ese profesor:
			$falta = $dbConnection_guardias->prepare('SELECT * FROM ausencias_profesores WHERE fecha = :fecha AND cod_hora = :cod_hora AND cod_profesor = :cod_profesor');
			$fecha = $_GET["ano"]."-".$_GET[mes]."-".$dia;
			$falta -> bindParam(':fecha',$fecha);
			$falta -> bindParam(':cod_hora',$hora);
			$falta -> bindParam(':cod_profesor',$session_cod_profesor);
			$falta -> execute();
			
			//$falta2 = $falta -> rowCount();
			//Si nos ha dado resultado buscamos su nombre
			if($falta -> rowCount() > 0) {

			
				$falta2 = $falta -> fetch(PDO::FETCH_ASSOC);
				echo '<tr>';
				echo '<td>'.$dia.'</td>';
				echo '<td>'.$nombreProfesor["nombre_completo"]." (".$falta2["cod_profesor"].")".'</td>';
				echo '<td>'.$hora.'</td>';
				echo '<td>'.$falta2["observaciones"].'</td>';
				echo '</tr>';
			}
					
			}
		}

	}
echo '
	</tbody>
	</table>
';

}
?>

</body>
</html>