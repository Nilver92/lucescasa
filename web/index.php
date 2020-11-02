<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
date_default_timezone_set('America/Bogota');

require('../vendor/autoload.php');

$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

// Our web handlers

$app->get('/', function() use($app) {
  $app['monolog']->addDebug('logging output.');
  return $app['twig']->render('index.twig');
});


//Ruta de demostración, para validar que se recibe(n) dato(s) y se responde con este mismo
$app->post('/enviarDato', function (Request $request) use ($app) {
   return $request;
});



$app->post('/guardarlectura', function (Request $request) use ($app) {

	$Voltbat = $request->get('Voltbat');
	$Voltpanel = $request->get('Voltpanel');
	$tabla = $request->get('tabla');
	$Ipanel = $request->get('Ipanel');
	$Temp1 = $request->get('Temp1');
	$Temp2 = $request->get('Temp2');
	$mWatt = $request->get('mWatt');

	$dbconn = pg_pconnect("host=ec2-54-152-40-168.compute-1.amazonaws.com port=5432 dbname=da5l2p8fhao45b user=rvjdadbcfsozcx password=d568c86e4a84d477292656b6718984c408f607f5459bca9b6eaf550604dfcf66");

	$data = array(
		"fecha"=>date('Y-m-d H:i:s'),
		"Voltbat" => $Voltbat,
		"Voltpanel" => $Voltpanel,
		"Ipanel" => $Ipanel,
		"Temp1" => $Temp1,
		"Temp2" => $Temp2,
		"mWatt" => $mWatt
		);

	$respuesta = pg_insert($dbconn, $tabla, $data);
   	
   	return $respuesta;
});

$app->get('/consultardato', function () use ($app) {
	
$dbconn = pg_pconnect("host=ec2-54-152-40-168.compute-1.amazonaws.com port=5432 dbname=da5l2p8fhao45b user=rvjdadbcfsozcx password=d568c86e4a84d477292656b6718984c408f607f5459bca9b6eaf550604dfcf66");



$query = "SELECT * FROM lecturas ORDER BY id DESC LIMIT 15";

	$consulta = pg_query($dbconn, $query);

//echo"<br><br>";
	
//print_r(pg_fetch_array($consulta,3,PGSQL_NUM));

//echo"<br><br>";
	
//$cons_array = pg_fetch_array($consulta,5,PGSQL_ASSOC);
//print_r($cons_array);
//echo $cons_array[fecha];
	
//echo"<br><br>";
//$cons_object = pg_fetch_object($consulta);
//print_r($cons_object);	
//echo $cons_object -> fecha;
	
	$resultArray = array();
  	while ($row = pg_fetch_array($consulta, null, PGSQL_ASSOC)) {
    	$resultArray[] = $row;
  	}
	
$jsonResult = json_encode($resultArray, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);

  $response = new Response();
  $response->setContent($jsonResult);
  $response->setCharset('UTF-8');
  $response->headers->set('Content-Type', 'application/json');

  return $response;
});	


$app->get('/limpiarDatos', function () use ($app) {

$dbconn = pg_pconnect("host=ec2-54-152-40-168.compute-1.amazonaws.com port=5432 dbname=da5l2p8fhao45b user=rvjdadbcfsozcx password=d568c86e4a84d477292656b6718984c408f607f5459bca9b6eaf550604dfcf66");

	
	$query_last = "SELECT * FROM lecturas ORDER BY id DESC LIMIT 1";
	$query_first = "SELECT * FROM lecturas ORDER BY id ASC LIMIT 1";

	$consulta_last = pg_query($dbconn, $query_last);
	$consulta_first = pg_query($dbconn, $query_first);

	$id_last = pg_fetch_result($consulta_last, null, 0);
	$id_first = pg_fetch_result($consulta_first, null, 0);

	
	$registros = $id_last - $id_first + 1;

		
		if($registros >=1000){
		$id_borrar = $id_last - 500;
		$query_delete = "DELETE FROM lecturas WHERE id<=" .$id_borrar.";";
		$consulta_delete = pg_query($dbconn, $query_delete);
		return "Se borraron los registros";
	}
	else{
		return "No se borraron los registros";
	}
});

//Ruta de demostración, se recibe(n) dato(s) y se manipulan
$app->post('/postArduino', function (Request $request) use ($app) {
   	return "OK";
});

$app->run();
