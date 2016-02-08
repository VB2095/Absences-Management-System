<?php session_start(); ?>
<?php

if (!$_SESSION['lang']){
	$_SESSION['lang'] = "es";
}
require_once('includes/lenguajes/'.$_SESSION['lang'].'_lang.php');

		
?>
<!doctype html>
<html lang="<?php echo $_SESSION[lang] ?>">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Sistema de gestión de faltas de profesores de instituto">
    <meta name="author" content="Víctor Sesma Ramón" >
	<title><?php echo title ?></title>	
	<!--Estilos Victor-->
	<link rel="stylesheet" href="estilos/estilos_victor.css" type="text/css" media="screen" />
		
	<!--Estilos calendario-->	
	<link rel="stylesheet" type="text/css" href="includes/datetimepicker-master/jquery.datetimepicker.css"/>
	
	<!--jQuery-->
	<script src='includes/jquery-2.1.3.min.js'></script>	

	<!--File upload-->
	<script src="includes/jquery.form.min.js"></script>

	<!--Bootstrap-->
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="includes/bootstrap-3.3.4-dist/css/bootstrap.min.css">
	
	<!-- Optional theme -->
	<link rel="stylesheet" href="includes/bootstrap-3.3.4-dist/css/bootstrap-theme.min.css">

	<!-- Latest compiled and minified JavaScript -->
	<script src="includes/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>
	
	<!-- Bootstrap toogle -->
	
	<link href="includes/bootstrap-toggle.min.css" rel="stylesheet">
	<script src="includes/bootstrap-toggle.min.js"></script>
	
	<!-- MomnetJS (fechas javascript) -->
	<script src="includes/moment-with-locales.js"></script>
	
	<!-- Calendario datetimepicker -->
	<script src="includes/datetimepicker-master/jquery.datetimepicker.js"></script>

	<script>
		//Cambio el idioma de la función moment a Español y la inicializo:
		moment.locale('es');
		var fecha_calendario = moment().format();

		if (moment(fecha_calendario).format("dddd") == "sábado"){
			fecha_calendario = moment(fecha_calendario).add(2, 'days');
		}else if (moment(fecha_calendario).format("dddd") == "domingo") {
			fecha_calendario = moment(fecha_calendario).add(1, 'days')
		}




		$(document).ready(function(){
			$.post("includes/iniciar_sesion.php", {})
			  .done(function(data) {
				var data = jQuery.parseJSON(data);
				
				//Cuando la sesión está cerrada...
				if(data.sesion_iniciada == "false"){
					//console.log("Elimino tabla");
					//Elimino tabla
					var parent = document.getElementById("contenido");
					var child = document.getElementById("tabla_completa");
					parent.removeChild(child);
					//Elimino la bienveida al profesor:
					var parent = document.getElementById("navbar");
					var child = document.getElementById("bienvenida_profesor");
					parent.removeChild(child);

					
				}else{
					//Cuando la sesión está iniciada...
					
					//Hago variables globales con cod_profesor, tipo_profesor y nombre_completo, fake_cod_profesor.
					cod_profesor = data.cod_profesor;
					tipo_profesor = data.tipo;
					nombre_completo = data.nombre_completo;
					session_fake_cod_profesor = data.fake_cod_profesor;
					//console.log("fake: "+session_fake_cod_profesor)
					//console.log("elimino contenido");
					var parent = document.getElementById("contenido");
					var child = document.getElementById("presentacion");
					parent.removeChild(child);
					
					//Quitar Iniciar sesion:
					var parent = document.getElementById("navbar");
					var child = document.getElementById("iniciar_sesion");
					parent.removeChild(child);

					//Quitar el toggle si el profesor es genérico:

					if (tipo_profesor == "G") {
						//console.log("Intento quitar el toggle");
						var parent = document.getElementById("botonera");
						var child = document.getElementById("horario-faltas");
						parent.removeChild(child);
						
					}else {
						

					//Preparamos la función que escuchará los cambios en el conmutador (toggle) y lanzará el inyector adecuado.
						$('#horario-faltas-input').change(function() {
							if ($(this).prop('checked') == true) {
								//console.log("Lanzo el horario personalizado");
								//console.log("Lanzo las horario");
								faltas(fecha_calendario);
							}else {
								//console.log("Lanzo las ausencias");
								faltas(fecha_calendario);
							}
    					})						
					};
					
					/*
					
					Botón opciones
					
						*/
						
					//Si el profesor es genérico quito el botón
					if (tipo_profesor == "G") {
						$("#div_opciones").empty();
					}else if (tipo_profesor == "P" || tipo_profesor == "A") {
						
						//Histórico faltas:
						var li = document.createElement("LI");
						var a = document.createElement("A");
						a.setAttribute("href","includes/historico_faltas.php?ano="+moment(fecha_calendario).format("YYYY")+"&mes="+moment(fecha_calendario).format("MM"));
						a.setAttribute("target","_blank");
						var texto = document.createTextNode("<?php echo historico_faltas; ?>");
						a.appendChild(texto);
						li.appendChild(a);
						document.getElementById("menu_opciones").appendChild(li);
						
						//Añadir falta todo el día:
						var li = document.createElement("LI");
						li.innerHTML ='<a href="#" id="anadir_falta_todo_dia" onclick="falta_multiple(1); return false;"><?php echo falta_todo_dia; ?></a>';
						document.getElementById("menu_opciones").appendChild(li);
						
						//Añadir falta varios días:
						var li = document.createElement("LI");
						li.innerHTML ='<a href="#" id="anadir_falta_varios_dia" onclick="falta_multiple(8); return false;"><?php echo falto_varios_dias; ?></a>';
						document.getElementById("menu_opciones").appendChild(li);
						
							//Si el profesor A, a parte de lo ya puesto, le ponemos el botón de "fake_cod_profesor"
							if (tipo_profesor == "A") {
								//Barra divisoria
								var li = document.createElement("LI");
								li.setAttribute("role","presentation");
								li.setAttribute("class","divider");
								document.getElementById("menu_opciones").appendChild(li);
								
								//Elemento fake...
								var li = document.createElement("LI");
								li.innerHTML ='<a href="#" id="fake_cod_profesor" onclick="fake_cod_profesor(); return false;"><?php echo fake_cod_profesor; ?></a>';
								document.getElementById("menu_opciones").appendChild(li);								
							}
					}
					
					
					//Añado el aviso al administrador de que está editando a otro profesor si procede:

					if (session_fake_cod_profesor != "falso" && tipo_profesor == "A") {
						var option = document.createElement("P");
						option.innerHTML= '<p class="text-center bg-warning"><?php echo estas_modificando;?>'+session_fake_cod_profesor+'</p>';
						document.getElementById("alerta_administrador").appendChild(option);
					}
					
					
					
					faltas(fecha_calendario);
					setInterval('faltas(fecha_calendario)',7000);
					
					
					
					
					
				}

				//Añado botón logout.

			  })
			  .fail(function(data) {
				// Dispatch errors in modal
				console.log("error");
			  });
			//faltas(fecha_calendario);
			
			
			//Subir archivos
			(function() {
			var bar = $('.bar');
			var percent = $('.percent');
			var status = $('#status');
				$('#agregar_ausencia').ajaxForm({
					url:"includes/agregar_modificar_falta_llamada.php",
				    beforeSend: function() {
				    	
				    	console.log("beforesend");
				        status.empty();
				        var percentVal = '0%';
				        bar.width(percentVal)
				        percent.html(percentVal);
				        status.html("<?php echo subiendo_archivo ?>");
				    },
				    uploadProgress: function(event, position, total, percentComplete) {
				        var percentVal = percentComplete + '%';
				        bar.width(percentVal)
				        percent.html(percentVal);
				    },
				    success: function() {
				        var percentVal = '100%';
				        bar.width(percentVal)
				        percent.html(percentVal);
				        console.log("exito");
				    },
					complete: function(xhr) {
						status.html(xhr.responseText);
						$("#agregar_falta").modal("hide")
						faltas(fecha_calendario);
					}
					
				}); 
			
			})();
			  		
		Date.parseDate = function( input, format ){
		  return moment(input,format).toDate();
		};
		Date.prototype.dateFormat = function( format ){
		  return moment(this).format(format);
		};	
	
		
		    $('#datetimepicker').datetimepicker(
		    	{formatDate:'DD.MM.YYYY',
		    	dayOfWeekStart : 1,
		    	lang:'<?php if ($_SESSION[lang] == "va"){echo "ca";}else {echo $_SESSION[lang];}; ?>',
		    	onGenerate:function( ct ){
							$(this).find('.xdsoft_date.xdsoft_weekend')
							.addClass('xdsoft_disabled');
						},
		    	timepicker:false,
				onSelectDate: function (ct, $i) {
							//console.log(ct.dateFormat('DD-MM-YYYY'))
							fecha_calendario = moment(ct.dateFormat('YYYY-MM-DD')).format("YYYY-MM-DD");
							faltas(fecha_calendario);
				},
		    	}
		    );




			//Calendario para añadir falta durante varios días:
			
			jQuery(function(){
			jQuery('#date_timepicker_start').datetimepicker({
			//formatDate:'DD.MM.YYYY',
			formatDate:'YYYY-MM-DD',
			  	dayOfWeekStart : 1,
		    	lang:'<?php if ($_SESSION[lang] == "va"){echo "ca";}else {echo $_SESSION[lang];}; ?>',
		    	onGenerate:function( ct ){
							$(this).find('.xdsoft_date.xdsoft_weekend')
							.addClass('xdsoft_disabled');
						},
			format:'YYYY-MM-DD',
			onShow:function( ct ){
				this.setOptions({
					maxDate:jQuery('#date_timepicker_end').val()?jQuery('#date_timepicker_end').val():false
				})
			},
			timepicker:false
			 });
			 jQuery('#date_timepicker_end').datetimepicker({
			  //formatDate:'DD.MM.YYYY',
			  formatDate:'YYYY-MM-DD',
			  dayOfWeekStart : 1,
		    	lang:'<?php if ($_SESSION[lang] == "va"){echo "ca";}else {echo $_SESSION[lang];}; ?>',
		    	onGenerate:function( ct ){
							$(this).find('.xdsoft_date.xdsoft_weekend')
							.addClass('xdsoft_disabled');
						},
			  format:'YYYY-MM-DD',
			  onShow:function( ct ){
			   this.setOptions({
			    minDate:jQuery('#date_timepicker_start').val()?jQuery('#date_timepicker_start').val():false
			   })
			  },
			  timepicker:false
			 });
			});



			
		});
		
		function dia_siguiente(){
			fecha_calendario = moment(fecha_calendario).add(1, 'days');
			if (moment(fecha_calendario).format("dddd") == "sábado"){
				fecha_calendario = moment(fecha_calendario).add(2, 'days');
			}else if (moment(fecha_calendario).format("dddd") == "domingo") {
				fecha_calendario = moment(fecha_calendario).add(1, 'days')
			}
			faltas(fecha_calendario);

		};
		function dia_anterior(){
			fecha_calendario = moment(fecha_calendario).subtract(1, 'days');
			if (moment(fecha_calendario).format("dddd") == "sábado"){
				fecha_calendario = moment(fecha_calendario).subtract(1, 'days');
			}else if (moment(fecha_calendario).format("dddd") == "domingo") {
				fecha_calendario = moment(fecha_calendario).subtract(2, 'days')
			}
			faltas(fecha_calendario);

		};
		function modifica_sustitucion(cod_hora,cod_profesor){

			//Elimino los <options> si los hubiera:
			var options = document.getElementsByTagName("OPTION");
			//console.log("options: "+options+" y options.length: "+options.length)
			if (options.length > 0){
				var x = options.length;
				for (i = 0; i<x; i++){
					//console.log("options en "+i+": "+options[i])
					options[0].remove();
				};
			};
			//Elimino el input con ("id","cod_profesor_ausente_hidden") si lo hubiera:
			var input1 = document.getElementById("cod_profesor_ausente_hidden");
			if(input1 != null){
				input1.remove();
			}
			//Elimino el input con ("id","cod_hora_hidden") si lo hubiera:
			var input1 = document.getElementById("cod_hora_hidden");
			if(input1 != null){
				input1.remove();
			}
			//Elimino el input con ("id","fecha_hidden") si lo hubiera:
			var input1 = document.getElementById("fecha_hidden");
			if(input1 != null){
				input1.remove();
			}
			
			//Creo tres option hidden, cod_hora,cod_profesor_ausente y dia para cuando quiera quitar la falta a todos los profesores:

			//cod_profesor_ausente
			var input = document.createElement("INPUT");
			input.setAttribute("type","hidden");
			input.setAttribute("id","cod_profesor_ausente_hidden");
			input.value = cod_profesor;
			document.getElementById("form_profesores").appendChild(input);
			
			//cod_hora:
			var cod_hora_hidden = document.createElement("INPUT");
			cod_hora_hidden.setAttribute("type","hidden");
			cod_hora_hidden.setAttribute("id","cod_hora_hidden");
			cod_hora_hidden.value = cod_hora;
			document.getElementById("form_profesores").appendChild(cod_hora_hidden);
			
			//fecha
			var fecha_hidden = document.createElement("INPUT");
			fecha_hidden.setAttribute("type","hidden");
			fecha_hidden.setAttribute("id","fecha_hidden");
			fecha_hidden.value = moment(fecha_calendario).format("YYYY-MM-DD");
			document.getElementById("form_profesores").appendChild(fecha_hidden);
			
			
			//Creo los <options>:
			$.each(lista_profesores, function(hora, element) {
				if(typeof element["falta"] != 'undefined'){
					
					$.each(element["falta"],function(cod_profesor_each,datos){
						if(hora == cod_hora && cod_profesor_each == cod_profesor){

							$.each(datos["sustituye"],function(llave,sustituto){
								var option = document.createElement("OPTION");
								//console.log(sustituto);
								if(sustituto["sustituye"] == 1){
									option.setAttribute("selected","selected");
								}
								var profesor = document.createTextNode(sustituto["nombre_completo"]+" metrica ("+sustituto["metrica"]+")");
								//option.value = "codHora_"+cod_hora+"__fecha_"+moment(fecha_calendario).format("YYYY-MM-DD")+"__sustituto_"+sustituto["cod_profesor"]+"__falta_"+cod_profesor;
								option.value = sustituto["cod_profesor"];
								option.appendChild(profesor);
								document.getElementById("select_profesores").appendChild(option);
							});
						}
					});

					
				
				}
			});
			
			$("#modifica_sustitucion").modal('show');
		}
		
		
		
		
		function modifica_sustitucion_llamada (){

			$.post("includes/modifica_sustitucion.php", {sustitutos: $("#select_profesores").val(),cod_profesor_falta_hidden: $("#cod_profesor_ausente_hidden").val(),cod_hora_hidden: $("#cod_hora_hidden").val(),fecha_hidden: $("#fecha_hidden").val(),})
			  .done(function(data) {
				// Some stuff there
				//console.log("data:");
				console.log(data);
				$("#modifica_sustitucion").modal("hide");
				faltas(fecha_calendario);
			  })
			  .fail(function(data) {
				// Dispatch errors in modal
				alert("Ha habido un error modificando la sustitución.");
			  });
		};
		
		function val() {

}
		
		
		
		Object.size = function(obj) {
		    var size = 0, key;
		    for (key in obj) {
		        if (obj.hasOwnProperty(key)) size++;
		    }
		    return size;
		};


		function faltas(fecha_calendario){
			$.ajax({
				type:'GET',
				url:"includes/calendario/json.php",
				//data:"start=2015-04-01&end=2015-04-01",
				data:"fecha_calendario="+moment(fecha_calendario).format("YYYY-MM-DD")+"&dia_semana="+moment(fecha_calendario).format("dddd"),
				dataType:'json',
				success:function(result){	
					<!--Limpiamos:-->
					var filas = document.getElementsByClassName("borrar");
					for (fila in filas) {
						//$(".borrar").empty();
						$(".borrar").remove();
						
					}

					//Muestro los días traducidos:
					
					var dias_semana = {lunes: "<?php echo lunes;?>", "martes": "<?php echo martes;?>", "miércoles": "<?php echo miercoles;?>", "jueves": "<?php echo jueves;?>", "viernes": "<?php echo viernes; ?>", "sábado": "<?php echo sabado;?>", "domingo": "<?php echo domingo;?>",};
					var dia = moment(fecha_calendario).format("dddd");
					$.each(dias_semana, function( index, value ) {
  						if (index == dia) {
  							dia = value;
  						}
					});
					
					//$('#dia').html(moment(fecha_calendario).format("dddd")+" "+moment(fecha_calendario).format("DD-MM-YYYY"));
					$('#dia').html(dia+" "+moment(fecha_calendario).format("DD-MM-YYYY"));

					lista_profesores = result;
					
					//Títulos para las faltas: 
					if(tipo_profesor == "G" || $('#horario-faltas-input').prop('checked') == false){		
						//Creo el th hora:
						var th = document.createElement("TH");
						th.setAttribute("class", "borrar");
						var texto = document.createTextNode("<?php echo hora; ?>");
						th.appendChild(texto);
						var tabla_tr = document.getElementById("tabla_thead_tr");
						tabla_tr.appendChild(th);
						
						//Creo el th falta
						var th = document.createElement("TH");
						th.setAttribute("class", "borrar");
						var texto = document.createTextNode("<?php echo falta; ?>");
						th.appendChild(texto);
						var tabla_tr = document.getElementById("tabla_thead_tr");
						tabla_tr.appendChild(th);
				
						//Creo el th grupo
						var th = document.createElement("TH");
						th.setAttribute("class", "borrar");
						var texto = document.createTextNode("<?php echo grupo; ?>");
						th.appendChild(texto);
						var tabla_tr = document.getElementById("tabla_thead_tr");
						tabla_tr.appendChild(th);
						
						//Creo el th asignatura
						var th = document.createElement("TH");
						th.setAttribute("class", "borrar");
						var texto = document.createTextNode("<?php echo asignatura; ?>");
						th.appendChild(texto);
						var tabla_tr = document.getElementById("tabla_thead_tr");
						tabla_tr.appendChild(th);

						//Creo el th aula
						var th = document.createElement("TH");
						th.setAttribute("class", "borrar");
						var texto = document.createTextNode("<?php echo aula; ?>");
						th.appendChild(texto);
						var tabla_tr = document.getElementById("tabla_thead_tr");
						tabla_tr.appendChild(th);

						//Creo el th sustituye
						var th = document.createElement("TH");
						th.setAttribute("class", "borrar");
						var texto = document.createTextNode("<?php echo sustituye; ?>");
						//th.setAttribute("colspan", "2");
						th.appendChild(texto);
						var tabla_tr = document.getElementById("tabla_thead_tr");
						tabla_tr.appendChild(th);
						
						//Creo el th observaciones
						var th = document.createElement("TH");
						th.setAttribute("class", "borrar");
						var texto = document.createTextNode("<?php echo observaciones; ?>");
						th.appendChild(texto);
						var tabla_tr = document.getElementById("tabla_thead_tr");
						tabla_tr.appendChild(th);
						
						/*//Creo el th descargas
						var th = document.createElement("TH");
						th.setAttribute("class", "borrar");
						var texto = document.createTextNode("<?php echo descargas; ?>");
						th.appendChild(texto);
						var tabla_tr = document.getElementById("tabla_thead_tr");
						tabla_tr.appendChild(th);
						
						//Creo el th modificar_sustitutos
						var th = document.createElement("TH");
						th.setAttribute("class", "borrar");
						var texto = document.createTextNode("<?php echo modificar; ?>");
						th.appendChild(texto);
						var tabla_tr = document.getElementById("tabla_thead_tr");
						tabla_tr.appendChild(th);*/
						
						//Creo el th opciones
						var th = document.createElement("TH");
						th.setAttribute("class", "borrar");
						//th.setAttribute("colspan", "2");
						var texto = document.createTextNode("<?php echo opciones; ?>");
						th.appendChild(texto);
						var tabla_tr = document.getElementById("tabla_thead_tr");
						tabla_tr.appendChild(th);
						
					}else {
						//Títulos para el horario:
						//Creo el th hora:
						var th = document.createElement("TH");
						th.setAttribute("class", "borrar");
						var texto = document.createTextNode("<?php echo hora; ?>");
						th.appendChild(texto);
						var tabla_tr = document.getElementById("tabla_thead_tr");
						tabla_tr.appendChild(th);

						//Creo el th asignatura
						var th = document.createElement("TH");
						th.setAttribute("class", "borrar");
						var texto = document.createTextNode("<?php echo asignatura; ?>");
						th.appendChild(texto);
						var tabla_tr = document.getElementById("tabla_thead_tr");
						tabla_tr.appendChild(th);
						
						//Creo el th grupo
						var th = document.createElement("TH");
						th.setAttribute("class", "borrar");
						var texto = document.createTextNode("<?php echo grupo; ?>");
						th.appendChild(texto);
						var tabla_tr = document.getElementById("tabla_thead_tr");
						tabla_tr.appendChild(th);
						
						//Creo el th aula
						var th = document.createElement("TH");
						th.setAttribute("class", "borrar");
						var texto = document.createTextNode("<?php echo aula; ?>");
						th.appendChild(texto);
						var tabla_tr = document.getElementById("tabla_thead_tr");
						tabla_tr.appendChild(th);
						
						//Creo el th Agregar falta
						var th = document.createElement("TH");
						th.setAttribute("class", "borrar");
						var texto = document.createTextNode("<?php echo agregar_falta; ?>");
						th.appendChild(texto);
						var tabla_tr = document.getElementById("tabla_thead_tr");
						tabla_tr.appendChild(th);

					}
					
					
					$.each(result, function(key, element) {
						//Para cada hora creo un su elemento:
							
						//Creo una fila por hora.
						
						var tr = document.createElement("TR");
						//console.log("Creo tr");
						tr.setAttribute("id", "hora_"+key);
						tr.setAttribute("class", "borrar");
						
						//Si soy profesor genérico o estoy mostrando Ausencias (toggle es false) muestro las ausencias:
						
						if(tipo_profesor == "G" || $('#horario-faltas-input').prop('checked') == false){						
							//Calculo el rowspan:				
							if(typeof element["falta"] != 'undefined'){
	
								var td_hora = document.createElement("TD");
								var rowspan = Object.size(element["falta"]);
								//console.log("rowspan: "+rowspan);
								td_hora.setAttribute("rowspan", rowspan);
								
								var hora = document.createTextNode(element["horario"]["hora_inicio"]+" - "+element["horario"]["hora_fin"]);
								td_hora.appendChild(hora);
								tr.appendChild(td_hora);

								//En cada hora hago su fila 
								nueva_tr = "false";
								$.each(element["falta"], function(cod_profesor, datos){
									if (nueva_tr == "true"){
										tr = document.createElement("TR");
										console.log("Creo tr en el if del rowspan");
										tr.setAttribute("class", "borrar");
										//console.log("tr nuevo");
									}else{
										//console.log("no tr nuevo");
									}
									//Nombre							
									var td_falta = document.createElement("TD");
									//console.log("nombre: "+datos["nombre_completo"]);
									var falta = document.createTextNode(datos["nombre_completo"]);
									td_falta.appendChild(falta);
									tr.appendChild(td_falta);
	
									//Grupo:
									var grupos = "";
									var td_grupo = document.createElement("TD");
									var punto = true;
									$.each(datos["cod_grupo"],function(grupo_key,grupo){
										if (punto == true) {
											grupos = grupo  + ".";
											punto = false;
										}else{							
											grupos = grupo + ", " + grupos;
										}
									});								
									//console.log("grupos: "+grupos);
									var node_grupo = document.createTextNode(grupos);
									td_grupo.appendChild(node_grupo);
									tr.appendChild(td_grupo);
									
									//Asignatura:
									var td_asignatura = document.createElement("TD");
									var asignatura = document.createTextNode(datos["asignatura"]);
									td_asignatura.appendChild(asignatura);
									tr.appendChild(td_asignatura);
									
									//Aula:
									//console.log("aula: "+datos["aula"]);
									var td_aula = document.createElement("TD");
									var aula = document.createTextNode(datos["aula"]);
									td_aula.appendChild(aula);
									tr.appendChild(td_aula);
									
									//Lo sustituye:
									var td_sustitutos = document.createElement("TD");
									lista_sustitutos = "";
									//Añadir puntos y comas si hay lista de sustituos.
									if (typeof datos["sustituye"] != 'undefined') {
										var punto = true;
										$.each(datos["sustituye"],function(sustitutoKey, sustituto){
											if (sustituto["sustituye"] == 1){
												if (punto == true) {
													lista_sustitutos = sustituto["nombre_completo"]  + ".";
													punto = false;
												}else{							
													lista_sustitutos = sustituto["nombre_completo"] + ", " + lista_sustitutos;
												}
												//lista_sustitutos = lista_sustitutos+", "+sustituto["nombre_completo"];
											}
										});
									}
									var sustituto = document.createTextNode(lista_sustitutos);
									td_sustitutos.appendChild(sustituto);
									tr.appendChild(td_sustitutos);
									
									//Observaciones:
									if(typeof datos["observaciones"] != 'undefined'){
										var td = document.createElement("TD");
										//var contenido = document.createTextNode(datos["observaciones"]);
										//td.appendChild(contenido);
										td.innerHTML = datos["observaciones"];
										tr.appendChild(td);
									}else {
										var td = document.createElement("TD");
										//var contenido = document.createTextNode(datos["observaciones"]);
										//td.appendChild(contenido);
										td.innerHTML = '<?php echo sin_observaciones?>';
										tr.appendChild(td);
									}

									/*
										
										Botónes de descarga y modificar sustitución.
									
									*/
									
									
									

									
									//Creo un objeto con la hora_inicio de "horas" de la base de datos:
									var hora_inicio = element["horario"]["hora_inicio"].split(":");
									var hora_inicio = moment({hour:hora_inicio["0"], minute:hora_inicio["1"]});
									
									//Si el profesor es genérico:
									if (tipo_profesor == "G") {
										
									
										//Si hay link de descarga...
										if(typeof datos["link"] != 'undefined'){
											
											if(moment().format('YYYY-MM-DD') == moment(fecha_calendario).format("YYYY-MM-DD")){
												//Si la clase ya ha empezado el botón está desactivado.
												if(moment(hora_inicio) <= moment()){
													var td = document.createElement("TD");
													//var contenido = document.createTextNode(datos["observaciones"]);
													//td.appendChild(contenido);
													td.innerHTML = '<a href="'+datos["link"]+'"><button type="button" class="btn btn-primary"><span class="glyphicon glyphicon glyphicon-download" aria-hidden="true"></span></button></a><button type="button" class="btn btn-primary disabled" onclick="modifica_sustitucion('+key+','+cod_profesor+');"><span class="glyphicon glyphicon glyphicon-edit" aria-hidden="true"></span></button>';
													tr.appendChild(td);
												}else {
													//Como la hora no ha empezado todavía el botón está activo.
													var td = document.createElement("TD");
													td.innerHTML = '<a href="'+datos["link"]+'"><button type="button" class="btn btn-primary"><span class="glyphicon glyphicon glyphicon-download" aria-hidden="true"></span></button></a><button type="button" class="btn btn-primary" onclick="modifica_sustitucion('+key+','+cod_profesor+');"><span class="glyphicon glyphicon glyphicon-edit" aria-hidden="true"></span></button>';
													tr.appendChild(td);
												}
											}else if (moment().format('YYYY-MM-DD') > moment(fecha_calendario).format("YYYY-MM-DD")) {
												//Si hoy es mayor que el día que miro(que está en el pasado) lo desactivo
												var td = document.createElement("TD");
												td.innerHTML = '<a href="'+datos["link"]+'"><button type="button" class="btn btn-primary"><span class="glyphicon glyphicon glyphicon-download" aria-hidden="true"></span></button></a><button type="button" class="btn btn-primary disabled" onclick="modifica_sustitucion('+key+','+cod_profesor+');"><span class="glyphicon glyphicon glyphicon-edit" aria-hidden="true"></span></button>';
												tr.appendChild(td);
											}else {
												//Si el día es mañana y otro en el futuro lo activo
												var td = document.createElement("TD");
												td.innerHTML = '<a href="'+datos["link"]+'"><button type="button" class="btn btn-primary"><span class="glyphicon glyphicon glyphicon-download" aria-hidden="true"></span></button></a><button type="button" class="btn btn-primary" onclick="modifica_sustitucion('+key+','+cod_profesor+');"><span class="glyphicon glyphicon glyphicon-edit" aria-hidden="true"></span></button>';
												tr.appendChild(td);
											}
											
										}else {
											//Si no hay link de descarga lo mismo pero con la propiedad disabled para la descargas...
	
											if(moment().format('YYYY-MM-DD') == moment(fecha_calendario).format("YYYY-MM-DD")){
												
												//Si la clase ya ha empezado el botón está desactivado.
												if(moment(hora_inicio) <= moment()){
													
													var td = document.createElement("TD");
													//var contenido = document.createTextNode(datos["observaciones"]);
													//td.appendChild(contenido);
													td.innerHTML = '<button type="button" class="btn btn-primary disabled"><span class="glyphicon glyphicon glyphicon-download" aria-hidden="true"></span></button><button type="button" class="btn btn-primary disabled" onclick="modifica_sustitucion('+key+','+cod_profesor+');"><span class="glyphicon glyphicon glyphicon-edit" aria-hidden="true"></span></button>';
													tr.appendChild(td);
												}else {
													
													//Como la hora no ha empezado todavía el botón está activo.
													var td = document.createElement("TD");
													td.innerHTML = '<button type="button" class="btn btn-primary disabled"><span class="glyphicon glyphicon glyphicon-download" aria-hidden="true"></span></button><button type="button" class="btn btn-primary" onclick="modifica_sustitucion('+key+','+cod_profesor+');"><span class="glyphicon glyphicon glyphicon-edit" aria-hidden="true"></span></button>';
													tr.appendChild(td);
												}
											}else if (moment().format('YYYY-MM-DD') > moment(fecha_calendario).format("YYYY-MM-DD")) {
												//Si hoy es mayor que el día que miro(que está en el pasado) lo desactivo
												var td = document.createElement("TD");
												td.innerHTML = '<button type="button" class="btn btn-primary disabled"><span class="glyphicon glyphicon glyphicon-download" aria-hidden="true"></span></button><button type="button" class="btn btn-primary disabled" onclick="modifica_sustitucion('+key+','+cod_profesor+');"><span class="glyphicon glyphicon glyphicon-edit" aria-hidden="true"></span></button>';
												tr.appendChild(td);
											}else {
												//Si el día es mañana y otro en el futuro lo activo
												var td = document.createElement("TD");
												td.innerHTML = '<button type="button" class="btn btn-primary disabled"><span class="glyphicon glyphicon glyphicon-download" aria-hidden="true"></span></button><button type="button" class="btn btn-primary" onclick="modifica_sustitucion('+key+','+cod_profesor+');"><span class="glyphicon glyphicon glyphicon-edit" aria-hidden="true"></span></button>';
												tr.appendChild(td);
											}
											
										}
									
									}else if (tipo_profesor == "P") {
									//Si el profesor es Profesor normal nunca puede editar:
										if(typeof datos["link"] != 'undefined'){
											var td = document.createElement("TD");
													td.innerHTML = '<a href="'+datos["link"]+'"><button type="button" class="btn btn-primary"><span class="glyphicon glyphicon glyphicon-download" aria-hidden="true"></span></button></a>';
													tr.appendChild(td);
										}else {
											var td = document.createElement("TD");
											td.innerHTML = '<button type="button" class="btn btn-primary disabled"><span class="glyphicon glyphicon glyphicon-download" aria-hidden="true"></span></button>';
											tr.appendChild(td);
										}

									}else if (tipo_profesor == "A") {
									//Si el profesor es Administrador puede editar cuando quiera:
										if(typeof datos["link"] != 'undefined'){
											var td = document.createElement("TD");
											td.innerHTML = '<a href="'+datos["link"]+'"><button type="button" class="btn btn-primary"><span class="glyphicon glyphicon glyphicon-download" aria-hidden="true"></span></button></a><button type="button" class="btn btn-primary" onclick="modifica_sustitucion('+key+','+cod_profesor+');"><span class="glyphicon glyphicon glyphicon-edit" aria-hidden="true"></span></button><button type="button" class="btn btn-danger" onclick="adminsitrador_eliminar_falta('+key+','+cod_profesor+');"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>';
											tr.appendChild(td);
										}else {
											var td = document.createElement("TD");
											td.innerHTML = '<button type="button" class="btn btn-primary disabled"><span class="glyphicon glyphicon glyphicon-download" aria-hidden="true"></span></button><button type="button" class="btn btn-primary" onclick="modifica_sustitucion('+key+','+cod_profesor+');"><span class="glyphicon glyphicon glyphicon-edit" aria-hidden="true"></span></button><button type="button" class="btn btn-danger" onclick="adminsitrador_eliminar_falta('+key+','+cod_profesor+');"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>';
											tr.appendChild(td);
										}
									}
									
									
									
									
									
									
									
									
									var tabla = document.getElementById("tabla");
									tabla.appendChild(tr);
									//console.log("appendchild caso especial");
									nueva_tr = "true";
									
								});
								
							}else{
										
								var td_hora = document.createElement("TD");							
								var hora = document.createTextNode(element["horario"]["hora_inicio"]+" - "+element["horario"]["hora_fin"]);
								//console.log("hora_sin_profesores: "+element["horario"]["hora_inicio"]+" - "+element["horario"]["hora_fin"]);
								td_hora.appendChild(hora);
								tr.appendChild(td_hora);	
								var tabla = document.getElementById("tabla");
								tabla.appendChild(tr);
								//console.log("appendchild caso generico");
							}

						}else {//Si estoy mostrando el horario del profesor ...
							
							if(typeof element["tiene_clase"] != 'undefined'){
								//Compruebo si el profesor tiene una declarada una ausencia en esa hora y le cambio el color a la fila:
								if(typeof element["falta"] != 'undefined'){
									$.each(element["falta"],function(key_cod_profesor,datos){
										if (key_cod_profesor == cod_profesor && session_fake_cod_profesor == "falso") {
											tr.setAttribute("class", "warning borrar");
										}else if (key_cod_profesor == session_fake_cod_profesor) {
											tr.setAttribute("class", "warning borrar");
										}
									});
								}
								
								
								//Celda de horas:
								var td_hora = document.createElement("TD");
								var hora = document.createTextNode(element["horario"]["hora_inicio"]+" - "+element["horario"]["hora_fin"]);
								td_hora.appendChild(hora);
								tr.appendChild(td_hora);
								tr.appendChild(td_hora);
								
								//Asignatura:
								var td_asignatura = document.createElement("TD");
								var asignatura = document.createTextNode(element["tiene_clase"]["asignatura"]);
								td_asignatura.appendChild(asignatura);
								tr.appendChild(td_asignatura);
								
								//Meto en grupo el/los grupos:
								var grupos = "";
								var td_grupo = document.createElement("TD");
								var punto = true;
								$.each(element["tiene_clase"]["cod_grupo"],function(grupo_key,grupo){
									if (punto == true) {
										grupos = grupo  + ".";
										punto = false;
									}else{							
										grupos = grupo + ", " + grupos;
									}
								});
								var node_grupo = document.createTextNode(grupos);	
								td_grupo.appendChild(node_grupo);
								tr.appendChild(td_grupo);
															
								//Aula:
								var td_aula = document.createElement("TD");
								//td_aula.setAttribute("colspan","2");
								var aula = document.createTextNode(element["tiene_clase"]["aula"]);
								td_aula.appendChild(aula);
								tr.appendChild(td_aula);
								
								//Botón añadir/modificar/borrar falta:
								var td_aula = document.createElement("TD");
								//Compruebo si la falta ya está en la base de datos para mostrar botones de modificar y borrar:
								var mostrar_borrar_modificar = "false";
								if(typeof element["falta"] != 'undefined'){
									$.each(element["falta"],function(key_cod_profesor,datos){
										if (key_cod_profesor == cod_profesor && session_fake_cod_profesor =="falso") {
											mostrar_borrar_modificar = "true";
											//console.log(cod_profesor);
											//console.log(key_cod_profesor);
										}else if (key_cod_profesor == session_fake_cod_profesor) {
											mostrar_borrar_modificar = "true";
										}
									});
								}
								
								//Si la fecha es futura para un prefsor p dejo que modifique, si es pasado lo impido.
								//Creo un objeto con la hora_inicio de "horas" de la base de datos:
								var hora_inicio = element["horario"]["hora_inicio"].split(":");
								var hora_inicio = moment({hour:hora_inicio["0"], minute:hora_inicio["1"]});
								//Si el profesor es genérico:
								if (tipo_profesor == "P") {								
								
								
									if(moment().format('YYYY-MM-DD') == moment(fecha_calendario).format("YYYY-MM-DD")){
										//Si la clase ya ha empezado el botón está desactivado.
										if(moment(hora_inicio) <= moment()){
											if(mostrar_borrar_modificar == "true"){
												var botonFalta = ('<button type="button" class="btn btn-info disabled" onclick="agregar_falta('+key+');"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></button> <button type="button" class="btn btn-danger disabled" onclick="eliminar_falta('+key+');"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>');
												td_aula.innerHTML = botonFalta;
												tr.appendChild(td_aula);
				
											}else{
												var botonFalta = ('<button type="button" class="btn btn-primary disabled" onclick="agregar_falta('+key+');"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button>');
												td_aula.innerHTML = botonFalta;
												tr.appendChild(td_aula);
											}
											
										}else {
											//Como la hora no ha empezado todavía el botón está activo.
											if(mostrar_borrar_modificar == "true"){
												var botonFalta = ('<button type="button" class="btn btn-info" onclick="agregar_falta('+key+');"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></button> <button type="button" class="btn btn-danger" onclick="eliminar_falta('+key+');"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>');
												td_aula.innerHTML = botonFalta;
												tr.appendChild(td_aula);
				
											}else{
												var botonFalta = ('<button type="button" class="btn btn-primary" onclick="agregar_falta('+key+');"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button>');
												td_aula.innerHTML = botonFalta;
												tr.appendChild(td_aula);
											}
											
										}
									}else if (moment().format('YYYY-MM-DD') > moment(fecha_calendario).format("YYYY-MM-DD")) {
										//Si hoy es mayor que el día que miro(que está en el pasado) lo desactivo
											if(mostrar_borrar_modificar == "true"){
												var botonFalta = ('<button type="button" class="btn btn-info disabled" onclick="agregar_falta('+key+');"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></button> <button type="button" class="btn btn-danger disabled" onclick="eliminar_falta('+key+');"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>');
												td_aula.innerHTML = botonFalta;
												tr.appendChild(td_aula);
				
											}else{
												var botonFalta = ('<button type="button" class="btn btn-primary disabled" onclick="agregar_falta('+key+');"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button>');
												td_aula.innerHTML = botonFalta;
												tr.appendChild(td_aula);
											}
											
									}else {
										//Si el día es mañana y otro en el futuro lo activo
										if(mostrar_borrar_modificar == "true"){
											var botonFalta = ('<button type="button" class="btn btn-info" onclick="agregar_falta('+key+');"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></button> <button type="button" class="btn btn-danger" onclick="eliminar_falta('+key+');"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>');
											td_aula.innerHTML = botonFalta;
											tr.appendChild(td_aula);
			
										}else{
											var botonFalta = ('<button type="button" class="btn btn-primary" onclick="agregar_falta('+key+');"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button>');
											td_aula.innerHTML = botonFalta;
											tr.appendChild(td_aula);
										}
									}								

								
								}else if (tipo_profesor == "A") {
									if(mostrar_borrar_modificar == "true"){
										var botonFalta = ('<button type="button" class="btn btn-info" onclick="agregar_falta('+key+');"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></button> <button type="button" class="btn btn-danger" onclick="eliminar_falta('+key+');"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>');
										td_aula.innerHTML = botonFalta;
										tr.appendChild(td_aula);
		
									}else{
										var botonFalta = ('<button type="button" class="btn btn-primary" onclick="agregar_falta('+key+');"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button>');
										td_aula.innerHTML = botonFalta;
										tr.appendChild(td_aula);
									}
									
								}		

								var tabla = document.getElementById("tabla");
								tabla.appendChild(tr);
								//console.log("clase");
							}else{
								var td_hora = document.createElement("TD");							
								var hora = document.createTextNode(element["horario"]["hora_inicio"]+" - "+element["horario"]["hora_fin"]);
								//console.log("hora_sin_profesores: "+element["horario"]["hora_inicio"]+" - "+element["horario"]["hora_fin"]);
								td_hora.appendChild(hora);
								tr.appendChild(td_hora);	
								var tabla = document.getElementById("tabla");
								tabla.appendChild(tr);
							}
							
						}

						

					});
					$();
				},
				error:function (xhr, ajaxOptions, thrownError) {
					console.log("Ha habido un problema recuperando los dtatos: "+xhr.status+thrownError);
				}				
			})//,
		//console.log("element: "+lista_profesores);
		};
		
		
		function cambiar_idioma(idioma) {
			$.post( "includes/cambiar_idioma.php", { lang: idioma})
			  .done(function( data ) {
			    window.location.reload(true);
		  });
		};


		function iniciar_sesion (usuario,contrasena){
			//var contra = $("#password").val();
			//console.log();

			$.post("includes/iniciar_sesion.php", {cod_profesor: $("#cod_profesor").val() ,password: $("#password").val()})
			  .done(function(data) {
				// Some stuff there
				//console.log("data:");
				//console.log("data: ");
				//console.log(data);
				//$("#modifica_sustitucion").modal("hide");
				//faltas(fecha_calendario);
				//Elimino el form de iniciar sesión:
				var parent = document.getElementById("navbar");
				var child = document.getElementById("iniciar_sesion");
				parent.removeChild(child);
				window.location.reload(false);
				//Añado botón logout.

			  })
			  .fail(function(data) {
				// Dispatch errors in modal
				console.log("error");
			  });
		};
		
		function cerrar_sesion(){
			$.post("includes/cerrar_sesion.php", {})
			  .done(function(data) {
				// Some stuff there
				//console.log("data:");
				//console.log("data: ");
				//console.log(data);
				//$("#modifica_sustitucion").modal("hide");
				//faltas(fecha_calendario);
				//Elimino el form de iniciar sesión:
				window.location.reload(true); 
				//Añado botón logout.

			  })
			  .fail(function(data) {
				// Dispatch errors in modal
				console.log("error");
			  });
		};



		function eliminar_falta(cod_hora){
			if (confirm("<?php echo eliminar_falta; ?>")) {
				
			
				$.post("includes/borrar_falta.php", {cod_hora:cod_hora,fecha_calendario:moment(fecha_calendario).format("YYYY-MM-DD")})
				  .done(function(data) {
					console.log(data);
					//var data = jQuery.parseJSON(data);
	
					
					
					faltas(fecha_calendario);
				  })
				  .fail(function(data) {
					// Dispatch errors in modal
					alert("Error borrando la falta.");
			  });
			}
		}
		
		
		function adminsitrador_eliminar_falta(cod_hora,cod_profesor){
			if (confirm("<?php echo eliminar_falta; ?>")) {
				
			
				$.post("includes/borrar_falta.php", {cod_hora:cod_hora,fecha_calendario:moment(fecha_calendario).format("YYYY-MM-DD"),cod_profesor_fake:cod_profesor})
				  .done(function(data) {
					console.log(data);
					//var data = jQuery.parseJSON(data);
	
					
					
					faltas(fecha_calendario);
				  })
				  .fail(function(data) {
					// Dispatch errors in modal
					alert("Error borrando la falta.");
			  });
			}
		}


		function agregar_falta(cod_hora){
			
			$.post("includes/agregar_falta.php", {cod_hora:cod_hora,fecha_calendario:moment(fecha_calendario).format("YYYY-MM-DD")})
			  .done(function(data) {
				console.log(data);
				var data = jQuery.parseJSON(data);

				//Vacio el contenido del textarea y del input:

				document.getElementById("textarea_agregar_falta").value = "";
				document.getElementById("prueba_input").value = "";
				
				//Pongo a 0 la barra de estado:
				var bar = $('.bar');
				var percent = $('.percent');
				 var percentVal = '0%';
				 bar.width(percentVal);
				 percent.html(percentVal);
				 
				 //Limpio el area del contenido:
				var status = $('#status');
				status.html("");
				
				
				//Elimino los campos hidden:
				var input1 = document.getElementById("cod_hora_agregar_ausencia");
				if(input1 != null){
					input1.remove();
				}
				var input1 = document.getElementById("fecha_calendario_agregar_ausencia");
				if(input1 != null){
					input1.remove();
				}				
				var input1 = document.getElementById("dia_semana");
				if(input1 != null){
					input1.remove();
				}
				
				//Elimino el contenido de borrar_fichero
				document.getElementById("borrar_fichero").innerHTML = "";
				
				//Agrego el campo hidden cod_profesor y fecha_calendario_agregar_ausencia:
	
				var input = document.createElement("INPUT");
				input.setAttribute("type","hidden");
				input.setAttribute("id","cod_hora_agregar_ausencia");
				input.setAttribute("name","cod_hora_agregar_ausencia");
				input.value = cod_hora;
				document.getElementById("agregar_ausencia").appendChild(input);
				
				var input = document.createElement("INPUT");
				input.setAttribute("type","hidden");
				input.setAttribute("id","fecha_calendario_agregar_ausencia");
				input.setAttribute("name","fecha_calendario_agregar_ausencia");
				input.value = moment(fecha_calendario).format("YYYY-MM-DD");
				document.getElementById("agregar_ausencia").appendChild(input);				
				
				var input = document.createElement("INPUT");
				input.setAttribute("type","hidden");
				input.setAttribute("id","dia_semana");
				input.setAttribute("name","dia_semana");
				input.value = moment(fecha_calendario).format("dddd");
				document.getElementById("agregar_ausencia").appendChild(input);	
				
				//Lo relleno con las observaciones si las hay:
				if(data.observaciones != ""){
					document.getElementById("textarea_agregar_falta").value = data.observaciones;
				}
				
				//Lo relleno con el link y doy la opción de borrarlo (si lo hay):
				console.log(data);
				if(data.link != ""){
					var status = $('#status');
					status.html("<?php echo ya_tiene_un; ?><a href='"+data.link+"' target='_blank'><?php echo fichero; ?></a>. <?php echo reemplazar; ?> ");
					
					var checkbox = document.createElement("INPUT");
					checkbox.setAttribute("type","checkbox");
					checkbox.setAttribute("id","checkbox_borrar_fichero");
					checkbox.setAttribute("name","checkbox_borrar_fichero");					
					checkbox.value = "true";
					document.getElementById("borrar_fichero").appendChild(checkbox);

				}
				
				$('#agregar_falta').modal();
			  })
			  .fail(function(data) {
				// Dispatch errors in modal
				console.log("error");
			  });
			  
		};
		
		function calendario(){
			$('#datetimepicker').datetimepicker('show')
		};
		
		function falta_multiple(uno_infinito){
			if (uno_infinito == 1) {
				//Si por fecha y permisos puedo moficicar...
				if (moment().format('YYYY-MM-DD') > moment(fecha_calendario).format("YYYY-MM-DD") && tipo_profesor == "P" ) {
					//Si hoy es mayor que el día que miro(que está en el pasado) lo desactivo
					alert("<?php echo dia_pasado; ?>");
				}else {
					//Si el día es mañana u otro en el futuro lo activo
					if (confirm("<?php echo confirmar_falto_todo_dia ?>")){
						$.post("includes/falta_multiple.php", {fecha:moment(fecha_calendario).format("YYYY-MM-DD"),dia_semana:moment(fecha_calendario).format("dddd")})
							.done(function(data) {
							console.log(data);
							faltas(fecha_calendario)
						})
							.fail(function(data) {
							// Dispatch errors in modal
							console.log("error");
						});
					}
					
				}
			}else {
						//Si uno_infinito es 8 (infinito) se faltará varios días.
						document.getElementById("date_timepicker_start").value="";
						document.getElementById("date_timepicker_end").value="";
						$('#falta_multiple').modal('show');
			}
		
		};
		
		//Cuando pulso guardar en el modal...
		
		
		function falta_multiple_llamada(){
		 	//El php se encarga de guardar sólo las fechas futuras ("P") o todas ("A").
			//dia_semana=8,fecha_inicio:jQuery('#date_timepicker_start').val(),fecha_fin:jQuery('#date_timepicker_end').val()
			$.post("includes/falta_multiple.php", {dia_semana:8,fecha_inicio:jQuery('#date_timepicker_start').val(),fecha_fin:jQuery('#date_timepicker_end').val()})
				.done(function(data) {
				console.log(data);
				fecha_calendario = moment(jQuery('#date_timepicker_start').val()).format("YYYY-MM-DD");
				$('#falta_multiple').modal('hide');
				faltas(fecha_calendario);
			})
				.fail(function(data) {
				// Dispatch errors in modal
				//alert("Error añadiendo las faltas entre los días: "+jQuery('#date_timepicker_start').val()+" y "+jQuery('#date_timepicker_fin').val());
			});
			
				
			
			
		};
		
		function fake_cod_profesor () {
			$.post("includes/fake_cod_profesor.php", {fake_cod_profesor:0})
				.done(function(data) {
				var data = jQuery.parseJSON(data);
			
				//Elimino los <options> si los hubiera:
				var options = document.getElementsByTagName("OPTION");
				if (options.length > 0){
					var x = options.length;
					for (i = 0; i<x; i++){
						options[0].remove();
					};
				};
				

				//Creo los <options>:
				$.each(data, function(cod_profesor2, nombre) {

					var option = document.createElement("OPTION");
					if (cod_profesor2 == cod_profesor) {
						var profesor = document.createTextNode("<?php echo volver_a_mi ?>"+" - "+nombre+" ("+cod_profesor2+")");
					}else {
						var profesor = document.createTextNode(nombre+" ("+cod_profesor2+")");
					}
					option.value = cod_profesor2;
					option.appendChild(profesor);
					document.getElementById("fake_cod_profesor_cod_profesor").appendChild(option);
				});

				$("#fake_cod_profesor").modal('show');
			})
				.fail(function(data) {
				alert("Ha habido algún problema");
			});
		};
		
		
		
		
		
		function fake_cod_profesor_llamada(){
			$.post("includes/fake_cod_profesor.php", {fake_cod_profesor:$("#fake_cod_profesor_cod_profesor").val()})
					.done(function(data) {
						
						var data = jQuery.parseJSON(data);
						console.log(data);
						$("#fake_cod_profesor").modal('hide');
						
						$("#alerta_administrador").empty();
						if (data.fake_cod_profesor != "false") {
							var option = document.createElement("P");
							option.innerHTML= '<p class="text-center bg-warning"><?php echo estas_modificando;?>'+data.fake_cod_profesor+'</p>';
							document.getElementById("alerta_administrador").appendChild(option);
						}


						//Actualizo las variables de sesión y llamo a faltas(fecha_calendario)

						$.post("includes/iniciar_sesion.php", {})
					  .done(function(data) {
						var data = jQuery.parseJSON(data);
					
						//Actualizo variables globales con cod_profesor, tipo_profesor y nombre_completo, fake_cod_profesor.
						cod_profesor = data.cod_profesor;
						tipo_profesor = data.tipo;
						nombre_completo = data.nombre_completo;
						session_fake_cod_profesor = data.fake_cod_profesor;
						console.log("fake: "+session_fake_cod_profesor)
						faltas(fecha_calendario);
					
					  })
					  .fail(function(data) {
						// Dispatch errors in modal
						console.log("error");
						});


					})
					.fail(function(data) {
						alert("Ha habido algún problema");
				});
				
		}
		
	</script>
</head>

  <body>

		<!-- Modal de fake_cod_prof (sólo para usuario "A") -->
		<div class="modal fade" id="fake_cod_profesor" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title" id="myModalLabel"><?php echo fake_cod_profesor; ?></h4>
					</div>
					<div class="modal-body">
						<p><span id="nombre_completo"></span> <?php echo selecciona_profesor; ?></p>
						<form action="#" onsubmit="fake_cod_profesor_llamada();return false" id="form_fake_cod_profesor">
							<select class="form-control" id="fake_cod_profesor_cod_profesor">

							</select>
					</div>
					<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo cerrar; ?></button>
							<button type="submit" class="btn btn-primary"><?php echo guardar_cambios; ?></button>
						</form>
					</div>
				</div>
			</div>
		</div>

    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="http://proyecto.yalo.es/"><?php echo title ?></a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
          <form class="navbar-form navbar-right" id="iniciar_sesion" action="#" onsubmit="iniciar_sesion();return false">
            <div class="form-group">
              <input type="text" placeholder="<?php echo usuario ?>" class="form-control" id="cod_profesor">
            </div>
            <div class="form-group">
              <input type="password" placeholder="<?php echo contrasena ?>" class="form-control" id="password">
            </div>
            <button type="submit" class="btn btn-success"><?php echo acceder ?></button>
            	<!-- Mostramoes el idioma en la pantalla de acceso -->
	           <div id="idioma2">
		          <a href="#" onclick="cambiar_idioma('es'); return false;"><img src="includes/imagenes/ES.png"></a>
		          <a href="#" onclick="cambiar_idioma('va'); return false;"><img src="includes/imagenes/VA.png"></a>
		          <a href="#" onclick="cambiar_idioma('en'); return false;"><img src="includes/imagenes/EN.png"></a>
	          </div>
          </form>
          <!-- Damos la bienvenida al profesor -->
          <!--<div class="navbar-right bienvenida_profesor" id="bienvenida_profesor">-->
          <div class="navbar-right" id="bienvenida_profesor">
				<p><?php echo bienvenida_profesor." ".$_SESSION['nombre_completo']?></p>
				<a href="#" id="cerrar_sesion" onclick="cerrar_sesion(); return false;"><?php echo cerrar_sesion; ?></a>

	          <!-- Mostramoes el idioma una vez dentro-->
	           <div id="idioma">
		          <a href="#" onclick="cambiar_idioma('es'); return false;"><img src="includes/imagenes/ES.png"></a>
		          <a href="#" onclick="cambiar_idioma('va'); return false;"><img src="includes/imagenes/VA.png"></a>
		          <a href="#" onclick="cambiar_idioma('en'); return false;"><img src="includes/imagenes/EN.png"></a>
	          </div>
          </div>
        </div><!--/.navbar-collapse -->
      </div>
    </nav>

    <!-- Main jumbotron for a primary marketing message or call to action -->
<div class="jumbotron" id="contenido">
	<div class="container" id="presentacion">
		<h3><?php echo debe_iniciar_sesion; ?></h3>
	</div>
	<div class="container" id="tabla_completa">
		<div id="botonera">
			<div id="izquierda"><button class="btn btn-primary" onclick="dia_anterior();"><span class="glyphicon glyphicon-arrow-left" aria-hidden="true"></span></button></div>
			<div id="dia"><p></p></div>
			<div id="derecha"><button class="btn btn-primary" onclick="dia_siguiente();"><span class="glyphicon glyphicon-arrow-right" aria-hidden="true"></span></button></div>
			<div id="boton_calendario"><button class="btn btn-info" onclick="calendario();"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button><input type="hidden" id="datetimepicker"/></div>
			<div id="div_opciones">
				<button class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="glyphicon glyphicon-option-vertical" aria-hidden="true"></span></button><input type="hidden" id="datetimepicker"/>
				<ul class="dropdown-menu dropdown-menu-right" role="menu" id="menu_opciones">

			  </ul>
			</div>
			<div id="horario-faltas"><input checked id="horario-faltas-input" data-toggle="toggle" data-on="<?php echo tu_horario; ?>" data-off="<?php echo ausencias; ?>" data-onstyle="primary" data-offstyle="info" type="checkbox"></div>
			
		</div>
		<div id="alerta_administrador"></div>

		<div id="tabla_css" class="table-responsive">	
			<table  class="table table-bordered table-hover table-striped">
			<thead>
				<tr id="tabla_thead_tr">

				</tr>
			</thead>
			<tbody id="tabla">
			
			</tbody>
			</table>
		</div>


		<!-- Modal de falta múltiple -->
		<div class="modal fade" id="falta_multiple" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title" id="myModalLabel"><?php echo falta_varios_dias; ?></h4>
					</div>
					<div class="modal-body">
						<form action="#" onsubmit="falta_multiple_llamada();return false" id="falta_multiple_form">
							<label><?php echo fecha_inicio; ?>
								<input id="date_timepicker_start" type="text" >
							</label>
							<label><?php echo fecha_fin; ?>
								<input id="date_timepicker_end" type="text" >
							</label>
						
					</div>
					<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo cerrar; ?></button>
					
							<button type="submit" class="btn btn-primary"><?php echo guardar_cambios; ?></button>
					</form>
					
					</div>
				</div>
			</div>
		</div>


		<!--Modal modificar_sustitución-->
		<div class="modal fade" id="modifica_sustitucion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title" id="myModalLabel"><?php echo modifica_la_sustitucion; ?></h4>
					</div>
					<div class="modal-body">
						<p><span id="nombre_completo"></span> <?php echo elije_otra_companera; ?></p>
						<form action="#" onsubmit="modifica_sustitucion_llamada();return false" id="form_profesores">
							<select multiple="multiple" class="form-control" id="select_profesores">

							</select>
					</div>
					<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo cerrar; ?></button>
							<!--<button type="button" class="btn btn-primary"><?php //echo guardar_cambios; ?></button>-->
							<button type="submit" class="btn btn-primary"><?php echo guardar_cambios; ?></button>
						</form>

					</div>
				</div>
			</div>
		</div>
				
				
		<!--Modal agregar falta-->
		<div class="modal fade" id="agregar_falta" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title" id="myModalLabel"><?php echo agregar_ausencia; ?></h4>
					</div>
					<div class="modal-body">
						<p><span id="nombre_completo"></span> <?php echo agregar_ausencia_texto; ?></p>
						<form  id="agregar_ausencia" action="#" method="post" enctype="multipart/form-data">
						
							<label for="exampleInputFile"><?php echo observaciones; ?></label>
							<textarea class="form-control" rows="3" name="textarea_agregar_falta" id="textarea_agregar_falta"></textarea>
							
							<label for="input_file"><?php echo anadir_archivo; ?></label>

							<!--Inicio seleccionar archivos -->
							<input type="file" name="input_file" id="prueba_input">
						    <div class="progress">
						        <div class="bar"></div>
						        <div class="percent">0%</div>
						    </div>
						    
						    <div id="status"></div>
						    <div id="borrar_fichero"></div>
							<!--Fin seleccionar archivos -->

					<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo cerrar; ?></button>
						
							<button type="submit" class="btn btn-primary"><?php echo guardar_cambios; ?></button>
 							
						</form>
					

					</div>
				</div>
			</div>
		</div>
			

			
	</div>
</div>

<div class="container">
	<hr>
	<footer>
		<p><a href="https://quitter.es/leviatan89">@leviatan89</a></p><a href="http://es.creativecommons.org/blog/licencias/"> <img src="includes/imagenes/by-sa_petit.png" > <a>
	</footer>
</div> <!-- /container -->


    
</body>
</html>