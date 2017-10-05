<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \petrophysics\backend\controller\RequestController as RequestApi;
require '../vendor/autoload.php';

$c = new \Slim\Container();//Initialisation de Slim
$app = new \Slim\App($c);

//Declaration des différentes routes 



$app->get('/get_all_poi', function (Request $req,Response $responseSlim) {
	$request = new RequestApi();
	$response = $request->Request_all_poi();
	$responseSlim->withHeader('Content-Type', 'application/json');
    return $response;
});

$app->post('/get_poi_sort', function (Request $req,Response $responseSlim) {
	$request = new RequestApi();
	$json  = $req->getparam('json');
	$response = $request->Request_poi_with_sort($json);
	$responseSlim->withHeader('Content-Type', 'application/json');
    return $response;
});

$app->post('/get_poi_type_data', function (Request $req,Response $responseSlim) {
	$json  = $req->getparam('json');
	$request = new RequestApi();
	$response = $request->Request_data_with_sort($json);
	$responseSlim->withHeader('Content-Type', 'application/json');
    //return $response;
});

$app->get('/download_poi_data/{name}', function (Request $req,Response $responseSlim,$args) {
	$name = $args['name'];
	$name = str_replace(' ', '', $name);
	$request = new RequestApi();
	$response = $request->Request_poi_data($name);
	$response=json_decode($response,TRUE);
	$path=$response['FILES'][0]['ORIGINAL_DATA_URL'];
	$download = $request->download($path);
	 if ($download == NULL or $download == false) {
       return $responseSlim->withStatus(403);
    }
});


$app->get('/preview_poi_data/{name}', function (Request $req,Response $responseSlim,$args) {
	$name = $args['name'];
	$name = str_replace(' ', '', $name);
	$request = new RequestApi();
	$response = $request->Request_poi_data($name);
	$response=json_decode($response,TRUE);
	$path=$response['FILES'][0]['ORIGINAL_DATA_URL'];
	$download = $request->preview($path,$name,$response['FILES'][0]['DATA_URL']);
	
       return $responseSlim->withStatus(200);
});

$app->run();
