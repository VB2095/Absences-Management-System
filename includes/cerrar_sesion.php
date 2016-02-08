<?php
session_start();
if(session_destroy()){
	$sesion = array("sesion" => "false");
	echo json_encode($sesion);
}else{
	$sesion = array("sesion" => "error");
	echo json_encode($sesion);

}


?>