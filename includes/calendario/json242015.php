<?php
if (!isset($_GET['start']) || !isset($_GET['end'])) {
	die("Please provide a date range.");
}
$start = $_GET['start'];
$end = $_GET['end'];
mysql_pconnect("localhost", "balmis_asir", "12345qwert") or die("Could not connect");
mysql_select_db("guardias") or die("Could not select database");

//$dbConnection = new PDO('mysql:dbname=actividades_extraescolares;host=127.0.0.1;charset=utf8', 'balmis_asir', '12345qwert');
//$rs = mysql_query("SELECT * FROM events ORDER BY start ASC");
//$rs = mysql_query("SELECT cod_profesor AS title, hora_inicio AS start, hora_fin AS end, fecha FROM ausencias_profesores AS a_p JOIN horas AS h ON a_p.cod_hora = h.cod_hora");
$rs = mysql_query("SELECT cod_profesor AS title, hora_inicio AS start, hora_fin AS end, fecha FROM ausencias_profesores AS a_p JOIN horas AS h ON a_p.cod_hora = h.cod_hora WHERE a_p.fecha >= '".$start."' AND a_p.fecha <= '".$end."'");
//$rs = mysql_query("SELECT cod_profesor AS title, hora_inicio AS start, hora_fin AS end, fecha FROM ausencias_profesores AS a_p JOIN horas AS h ON a_p.cod_hora = h.cod_hora WHERE a_p.fecha >= '2015-01-26' AND a_p.fecha <= '2015-03-09'");
$arr = array();
while($obj = mysql_fetch_object($rs)) {
	$obj->start = $obj->fecha."T".$obj->start;
	$obj->end = $obj->fecha."T".$obj->end;
	if(strtotime($obj->start) >= time()){
		$obj->editable = true;
	}else{
		$obj->editable = false;
	}

	unset($obj->fecha);
	$arr[] = $obj;
}

echo json_encode($arr);



?>