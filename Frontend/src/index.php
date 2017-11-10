<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


require '../vendor/autoload.php';


$c = new \Slim\Container();
$app = new \Slim\App($c);

$app->get('/', function (Request $req,Response $responseSlim) {
$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
$twig = new Twig_Environment($loader);
$config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/Backend/config.ini');

echo $twig->render('accueil.html.twig',['project_name' => $config['PROJECT_NAME']]);

});

$app->run();

