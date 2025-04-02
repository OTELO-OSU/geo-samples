<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \geosamples\backend\controller\RequestController as RequestApi;
use \geosamples\backend\controller\MailerController as Mailer;
use \geosamples\backend\controller\UserController as User;
use \geosamples\backend\controller\FileController as File;

require '../vendor/autoload.php';

$c = new \Slim\Container(); //Initialisation de Slim
$app = new \Slim\App($c);
$container         = $app->getContainer();
$container['csrf'] = function ($c) {
	$guard = new \Slim\Csrf\Guard;
	$guard->setFailureCallable(function ($request, $response, $next) {
		$request = $request->withAttribute("csrf_status", false);
		$loader  = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig    = new Twig_Environment($loader);
		$response->write($twig->render('forbidden.html.twig'));
		return $response;
	});
	return $guard;
};

$c['notFoundHandler'] = function ($c) {
	return function ($request, $response) use ($c) {
		$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig   = new Twig_Environment($loader);
		echo $twig->render('notfound.html.twig');
	};
};

$c['notAllowedHandler'] = function ($c) {
	return function ($request, $response, $methods) use ($c) {
		$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig   = new Twig_Environment($loader);
		echo $twig->render('forbidden.html.twig');
	};
};

$mw = function ($request, $response, $next) {
	$file = file_exists($_SERVER['DOCUMENT_ROOT'] . '/../config.ini');
	if ($file == false) {
		$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig   = new Twig_Environment($loader);
		$render = $twig->render('notfound.html.twig');
	}
	if (!empty($render)) {
		$response->write($render);
		return $response;
	} else {
		$response = $next($request, $response);
		return $response;
	}
};



$check_access_right_file = function ($request, $response, $next) {
	$config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/../config.ini');
	if ((($_SESSION['mail']) and in_array($config['COLLECTION_NAME'], $_SESSION['projects_access_right_name'])) or ($_SESSION['admin'] == 1)) {
		$response = $next($request, $response);
		return $response;
	} else {
		$user = new User();
		$referents = $user->getProjectReferent($config['COLLECTION_NAME']);
		$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig   = new Twig_Environment($loader);
		$referentsA = [];
		foreach ($referents as $key => $value) {
			$referentsA[] = $value->mail;
		}
		$render = $twig->render('forbidden.html.twig', ['referents' => $referentsA]);
		$response->write($render);
		//return $responseSlim->withStatus(403);
		return $response;
	}
};






$check_current_user = function ($request, $response, $next) {
	$user      = new User();
	$checkuser = $user->check_current_user($_SESSION['mail']);
	if ($checkuser) {
		$response = $next($request, $response);
		return $response;
	} else {
		session_destroy();
		$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig   = new Twig_Environment($loader);
		$render = $twig->render('notfound.html.twig');
		$response->write($render);
		return $response;
	}
};






session_start();
//Declaration des différentes routes 

$app->get('/', function (Request $req, Response $responseSlim) {
	$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
	$twig = new Twig_Environment($loader);
	$config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/../config.ini');


	echo $twig->render('accueil.html.twig', ['project_name' => $config['COLLECTION_NAME'], 'title' => $config['COLLECTION_NAME'], 'name' => $_SESSION['name'], 'firstname' => $_SESSION['firstname'], 'mail' => $_SESSION['mail'], 'map' => 'map', 'access' => $_SESSION['access']]);
});

$app->get('/accueil', function (Request $req, Response $responseSlim) {

	return $responseSlim->withRedirect('/');
});


//Route permettant la connexion d'un utilisateur
$app->get('/login', function (Request $req, Response $responseSlim) {

	$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
	$twig   = new Twig_Environment($loader);
	echo $twig->render('login.html.twig', ['title' => "Login"]);

	session_regenerate_id();
});

$app->post('/login', function (Request $req, Response $responseSlim) {
	$loader   = new Twig_Loader_Filesystem('geosamples/frontend/templates');
	$twig     = new Twig_Environment($loader);
	$mail     = $req->getparam('email');
	$password = $req->getparam('password');
	$user     = new User();
	$error    = $user->login($mail, $password);
	if (!$error) {
		return $responseSlim->withRedirect('/');
	} else {
		echo $twig->render('login.html.twig', ['error' => $error]);
	}
});

// ! Non utilisé
//// $app->get('/validation', function (Request $req, Response $responseSlim) {
//// 		if (($_SESSION['access']==1) OR $_SESSION['admin']==1) {
//// 			$config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/../config.ini');
//// 			$request = new RequestApi();
//// 			$response=$request->Request_all_data_awaiting();
//// 			$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
//// 			$twig   = new Twig_Environment($loader);
//// 			echo $twig->render('validation.html.twig', ['title' => "Approve",'data' => $response,'mail'=>$_SESSION['mail'],'access'=>$_SESSION['access'],'project_name'=> $config['COLLECTION_NAME']]);
//// 		}else{
//// 					return $responseSlim->withRedirect('/');
//// 		}
//// });




$app->get('/loginCAS', function (Request $req, Response $responseSlim) {
	$user      = new User();
	$checkuser = $user->check_current_user($_SERVER['HTTP_MAIL']);
	if ($checkuser) {
		$_SESSION['name']      = $checkuser->name;
		$_SESSION['firstname'] = $checkuser->firstname;
		$_SESSION['mail']      = $checkuser->mail;
		$_SESSION['admin']     = $checkuser->type;
		$file = new File();
		$config = $file->ConfigFile();
		$feeder = $user->is_feeder($_SESSION['mail'], $config['COLLECTION_NAME']);
		$referent = $user->is_referent($_SESSION['mail'], $config['COLLECTION_NAME']);

		if (($referent === true) or $_SESSION['admin'] == 1) {
			$_SESSION['access'] = 1;
		} elseif ($feeder === true) {
			$_SESSION['access'] = 2;
		}

		$user->giveRight($checkuser);
		return $responseSlim->withRedirect('accueil');
	} else {
		$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig   = new Twig_Environment($loader);
		$error  = "No account linked to this email! Please register";
		echo $twig->render('login.html.twig', ['error' => $error]);
	}
});


$app->get('/signup', function (Request $req, Response $responseSlim) {
	if (!@$_SESSION['name']) {
		$nameKey = $this
			->csrf
			->getTokenNameKey();
		$valueKey = $this
			->csrf
			->getTokenValueKey();
		$namecsrf  = $req->getAttribute($nameKey);
		$valuecsrf = $req->getAttribute($valueKey);
		$user = new User();
		$project = $user->getAllProject();
		$response = array();
		foreach ($project as $key => $value) {
			$array['title'] = $value;
			$response[] = $array;
		}
		$loader    = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig      = new Twig_Environment($loader);
		echo $twig->render('signup.html.twig', ['title' => "Sign up", 'name_CSRF' => $namecsrf, 'value_CSRF' => $valuecsrf, 'data' => json_encode($response)]);
	} else {
		return $responseSlim->withRedirect('/');
	}
})->add($mw)->add($container->get('csrf'));

$app->post('/signup', function (Request $req, Response $responseSlim) {
	$nameKey = $this
		->csrf
		->getTokenNameKey();
	$valueKey = $this
		->csrf
		->getTokenValueKey();
	$namecsrf        = $req->getAttribute($nameKey);
	$valuecsrf       = $req->getAttribute($valueKey);
	$loader          = new Twig_Loader_Filesystem('geosamples/frontend/templates');
	$twig            = new Twig_Environment($loader);
	$name            = $req->getparam('name');
	$firstname       = $req->getparam('firstname');
	$mail            = $req->getparam('email');
	$project_name    = $req->getparam('project_name');
	$password        = $req->getparam('password');
	$passwordconfirm = $req->getparam('password_confirm');
	$user            = new User();
	$error           = $user->signup($name, $firstname, $mail, $password, $passwordconfirm, $project_name);
	if (!$error) {
		return $responseSlim->withRedirect('accueil');
	} else {
		$user = new User();
		$project = $user->getAllProject();
		$response = array();
		foreach ($project as $key => $value) {
			$array['title'] = $value;
			$response[] = $array;
		}
		echo $twig->render('signup.html.twig', ['error' => $error, 'name_CSRF' => $nameKey, 'value_CSRF' => $valueKey, 'data' => json_encode($response)]);
	}
})->add($container->get('csrf'));

$app->get('/myaccount', function (Request $req, Response $responseSlim) {
	if (@$_SESSION['name']) {
		$config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/../config.ini');
		$loader  = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig    = new Twig_Environment($loader);
		$nameKey = $this
			->csrf
			->getTokenNameKey();
		$valueKey = $this
			->csrf
			->getTokenValueKey();
		$namecsrf  = $req->getAttribute($nameKey);
		$valuecsrf = $req->getAttribute($valueKey);
		$user      = new User();
		$user      = $user->getUserInfo($_SESSION['mail']);

		echo $twig->render('myaccount.html.twig', ['title' => "My account", 'name' => $user[0]->name, 'firstname' => $user[0]->firstname, 'name_CSRF' => $namecsrf, 'value_CSRF' => $valuecsrf, 'mail' => $_SESSION['mail'], 'admin' => $_SESSION['admin'], 'access' => $_SESSION['access'], 'project_name' => $config['COLLECTION_NAME']]);
	} else {
		return $responseSlim->withRedirect('accueil');
	}
})->add($mw)->add($container->get('csrf'))->add($check_current_user);

$app->post('/myaccount', function (Request $req, Response $responseSlim) {
	if (@$_SESSION['name']) {
		$loader  = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig    = new Twig_Environment($loader);
		$nameKey = $this
			->csrf
			->getTokenNameKey();
		$valueKey = $this
			->csrf
			->getTokenValueKey();
		$namecsrf  = $req->getAttribute($nameKey);
		$valuecsrf = $req->getAttribute($valueKey);
		$name      = $req->getparam('name');
		$firstname = $req->getparam('firstname');
		$user      = new User();
		$user->setUserInfo($_SESSION['mail'], $name, $firstname);
		$user = $user->getUserInfo($_SESSION['mail']);
		echo $twig->render('myaccount.html.twig', ['message' => "Account updated successfully", 'name' => $user[0]->name, 'firstname' => $user[0]->firstname, 'name_CSRF' => $namecsrf, 'value_CSRF' => $valuecsrf, 'mail' => $_SESSION['mail'], 'admin' => $_SESSION['admin']]);
	}
})->add($mw)->add($container->get('csrf'));


$app->get('/logout', function (Request $req, Response $responseSlim) {
	$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
	$twig   = new Twig_Environment($loader);
	session_destroy();
	$file   = new File();
	$config = $file->ConfigFile();
	return $responseSlim->withRedirect($_SERVER['HTTP_HOST'] . '/Shibboleth.sso/Logout?return=' . $_SERVER['HTTP_HOST']);
})->add($mw);

$app->get('/activate_account', function (Request $req, Response $responseSlim) {
	if (!@$_SESSION['name']) {
		$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig   = new Twig_Environment($loader);
		$token  = $req->getparam('token');
		if ($token) {
			$user  = new User();
			$error = $user->activate_account($token);
			if ($error == false) {
				return $responseSlim->withRedirect('accueil');
			} else {
				echo $twig->render('validation_mail.html.twig');
			}
		} else {
			return $responseSlim->withRedirect('accueil');
		}
	} else {
		return $responseSlim->withRedirect('accueil');
	}
})->add($mw);

// ! Non utilisé
$app->get('/upload', function (Request $req, Response $responseSlim) {
	$nameKey = $this
		->csrf
		->getTokenNameKey();
	$valueKey = $this
		->csrf
		->getTokenValueKey();
	$namecsrf  = $req->getAttribute($nameKey);
	$valuecsrf = $req->getAttribute($valueKey);
	$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
	$twig   = new Twig_Environment($loader);
	$user      = new User();
	$file   = new File();
	$config = $file->ConfigFile();
	$feeder = $user->is_feeder($_SESSION['mail'], $config['COLLECTION_NAME']);
	$referent = $user->is_referent($_SESSION['mail'], $config['COLLECTION_NAME']);
	if ($feeder == true) {
		$access = 2;
	}
	if ($referent == true) {
		$access = 1;
	}
	if ($_SESSION['admin'] == 1) {
		$access = 1;
	}
	if (($feeder === true) or ($referent === true) or $_SESSION['admin'] == 1) {
		echo $twig->render('upload.html.twig', ['title' => "Upload", 'collection_name' => $config['COLLECTION_NAME'], 'route' => 'upload', 'name_CSRF' => $namecsrf, 'value_CSRF' => $valuecsrf, 'mail' => $_SESSION['mail'], 'admin' => $_SESSION['admin'], 'access' => $access, 'project_name' => $config['COLLECTION_NAME']]);
	} else {
		return $responseSlim->withRedirect('accueil');
	}
})->add($container->get('csrf'));

//Route permettant d'acceder a la page terms of use
$app->get('/terms', function (Request $req, Response $responseSlim) {
	$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
	$twig   = new Twig_Environment($loader);
	echo $twig->render('terms.html.twig');
});


// ! Non utilisé
//// $app->post('/upload', function (Request $req, Response $responseSlim) {
//// 	$nameKey = $this
//// 		->csrf
//// 		->getTokenNameKey();
//// 		$valueKey = $this
//// 		->csrf
//// 		->getTokenValueKey();
//// 		$namecsrf  = $req->getAttribute($nameKey);
//// 		$valuecsrf = $req->getAttribute($valueKey);
//// 		$user      = new User();
//// 		$file   = new File();
//// 		$config = $file->ConfigFile();
//// 		$feeder= $user->is_feeder($_SESSION['mail'],$config['COLLECTION_NAME']);
//// 		$referent= $user->is_referent($_SESSION['mail'],$config['COLLECTION_NAME']);
//// 		if (($feeder===true )OR ($referent===true) OR $_SESSION['admin']==1) {
//// 		$request      = new RequestApi();
//// 		$response=$request->Post_Processing($_POST,'upload');
//// 		$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
//// 		$twig   = new Twig_Environment($loader);
//// 		if ($response === true) {
//// 					echo $twig->render('display_actions.html.twig',['message'=>'Data submitted to referents','mail' => $_SESSION['mail'], 'admin' => $_SESSION['admin'],'access'=>$_SESSION['admin']]);
//// 		}else{
//// 			//var_dump($response['dataform']['METHODOLOGY'][0]['DESCRIPTION']);
//// 			echo $twig->render('upload.html.twig',[ 
//// 				'collection_name'=>$config['COLLECTION_NAME'],
//// 			'edit'=>true,'name_CSRF' => $namecsrf,
//// 			'route'=>'upload',
//// 			 'value_CSRF' => $valuecsrf, 
//// 			 'mail' => $_SESSION['mail'],
//// 			  'admin' => $_SESSION['admin'],
//// 			  'access'=>$_SESSION['admin'],
//// 			  'error'=>$response['error'],
//// 				'title'=>$response['dataform']['TITLE'],
//// 			  'description'=>$response['dataform']['DATA_DESCRIPTION'],
//// 			  'sample_name'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME'],
//// 			  'language'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['LANGUAGE'],
//// 			  'block'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['BLOCK'],
//// 			  'pulp'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['PULP'],
//// 			  'core'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['CORE_DETAILS'][0]['CORE'],
//// 			  'ANTHROPOGENIC_MATERIAL'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['ANTHROPOGENIC_MATERIAL'],
//// 			  'language'=>$response['dataform']['LANGUAGE'],
//// 			  'keywords'=>$response['dataform']['KEYWORDS'],
//// 			  'institutions'=>$response['dataform']['INSTITUTION'],
//// 			  'scientific_fields'=>$response['dataform']['SCIENTIFIC_FIELD'],
//// 			   'measurement_abbv'=>$response['dataform']['MEASUREMENT'][0]['ABBREVIATION'],
//// 			   'sampling_date'=>$response['dataform']['SAMPLING_DATE'][0],
//// 			   'lithology1'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['LITHOLOGY'],
//// 			   	'lithology2'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['LITHOLOGY_2'],
//// 			   'lithology3'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['LITHOLOGY_3'],
//// 			   'oretype1'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['ORE_TYPE_1'],
//// 			   	'oretype2'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['ORE_TYPE_2'],
//// 			   'oretype3'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['ORE_TYPE_3'],
//// 			   'texture1'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['TEXTURE_STRUCTURE_1'],
//// 			   	'texture2'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['TEXTURE_STRUCTURE_2'],
//// 			   'texture3'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['TEXTURE_STRUCTURE_3'],
//// 			    'sample_location_facility'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['SAMPLE_LOCATION_FACILITY'],
//// 			    'safety_constraints'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['SAFETY_CONSTRAINTS'],
//// 			    'storage_details'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['STORAGE_DETAILS'],
//// 			    'substance'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['SUBSTANCE'],
//// 			    'host_age'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['HOST_AGE'],
//// 			    'main_event_age'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['MAIN_EVENT_AGE'],
//// 			    'other_event_age'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['OTHER_EVENT_AGE'],
//// 			    'alteration_degree'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['ALTERATION_DEGREE'],
//// 			    'host_lithology_or_protolith'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['HOST_LITHOLOGY_OR_PROTOLITH'],
//// 			    'methodology_sampling'=>$response['dataform']['METHODOLOGY'][0]['DESCRIPTION'],
//// 			     'methodology_conditionning'=>$response['dataform']['METHODOLOGY'][1]['DESCRIPTION'],
//// 			      'methodology_sample_storage'=>$response['dataform']['METHODOLOGY'][2]['DESCRIPTION'],
//// 			       'sampling_points'=>$response['dataform']['SAMPLING_POINT'],
//// 			]);
//// 		}
//// 		}else{
//// 			return $responseSlim->withRedirect('accueil');
//// 		}
//// })->add($container->get('csrf'));


// ! Non utilisé
//// $app->get('/modify/{id}', function (Request $req, Response $responseSlim,$args) {
//// 	$nameKey = $this
//// 		->csrf
//// 		->getTokenNameKey();
//// 		$valueKey = $this
//// 		->csrf
//// 		->getTokenValueKey();
//// 		$namecsrf  = $req->getAttribute($nameKey);
//// 		$valuecsrf = $req->getAttribute($valueKey);
//// 		$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
//// 		$twig   = new Twig_Environment($loader);
//// 		$user      = new User();
//// 		$file   = new File();
////         $config = $file->ConfigFile();
//// 		$feeder= $user->is_feeder($_SESSION['mail'],$config['COLLECTION_NAME']);
//// 		$referent= $user->is_referent($_SESSION['mail'],$config['COLLECTION_NAME']);
//// 		if (($feeder===true )OR ($referent===true) OR $_SESSION['admin']==1) {
//// 		$request = new RequestApi();
//// 			$response=$request->Request_data_awaiting($args['id']);
//// 		echo $twig->render('upload.html.twig',[ 
//// 			'collection_name'=>$config['COLLECTION_NAME'],
//// 			'edit'=>true,
//// 			'route'=>'modify',
//// 			'id'=>$args['id'],
//// 			'name_CSRF' => $namecsrf,
//// 			 'value_CSRF' => $valuecsrf, 
//// 			 'mail' => $_SESSION['mail'],
//// 			  'admin' => $_SESSION['admin'],
//// 			  'access'=>$_SESSION['admin'],
//// 			  'original_sample_name'=>$args['id'],
//// 			  'title'=>$response['_source']['INTRO']['TITLE'],
//// 			  'description'=>$response['_source']['INTRO']['DATA_DESCRIPTION'],
//// 			  'sample_name'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME'],
//// 			  'language'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['LANGUAGE'],
//// 			  'block'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['BLOCK'],
//// 			  'pulp'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['PULP'],
//// 			  'core'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['CORE_DETAILS'][0]['CORE'],
//// 			  'ANTHROPOGENIC_MATERIAL'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['ANTHROPOGENIC_MATERIAL'],
//// 			  'language'=>$response['_source']['INTRO']['LANGUAGE'],
//// 			  'keywords'=>$response['_source']['INTRO']['KEYWORDS'],
//// 			  'institutions'=>$response['_source']['INTRO']['INSTITUTION'],
//// 			  'scientific_fields'=>$response['_source']['INTRO']['SCIENTIFIC_FIELD'],
//// 			   'measurement_abbv'=>$response['_source']['INTRO']['MEASUREMENT'][0]['ABBREVIATION'],
//// 			   	'measurement_unit'=>$response['_source']['INTRO']['MEASUREMENT'][0]['UNIT'],
//// 			   'sampling_date'=>$response['_source']['INTRO']['SAMPLING_DATE'][0],
//// 			   'lithology1'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['LITHOLOGY'],
//// 			   	'lithology2'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['LITHOLOGY_2'],
//// 			   'lithology3'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['LITHOLOGY_3'],
//// 			   'oretype1'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['ORE_TYPE_1'],
//// 			   	'oretype2'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['ORE_TYPE_2'],
//// 			   'oretype3'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['ORE_TYPE_3'],
//// 			   'texture1'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['TEXTURE_STRUCTURE_1'],
//// 			   	'texture2'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['TEXTURE_STRUCTURE_2'],
//// 			   'texture3'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['TEXTURE_STRUCTURE_3'],
//// 			    'sample_location_facility'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAMPLE_LOCATION_FACILITY'],
//// 			    'safety_constraints'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SAFETY_CONSTRAINTS'],
//// 			    'storage_details'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['STORAGE_DETAILS'],
//// 			    'substance'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['SUBSTANCE'],
//// 			    'host_age'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['HOST_AGE'],
//// 			    'main_event_age'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['MAIN_EVENT_AGE'],
//// 			    'other_event_age'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['OTHER_EVENT_AGE'],
//// 			    'alteration_degree'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['ALTERATION_DEGREE'],
//// 			    'host_lithology_or_protolith'=>$response['_source']['INTRO']['SUPPLEMENTARY_FIELDS']['HOST_LITHOLOGY_OR_PROTOLITH'],
//// 			    'methodology_sampling'=>$response['_source']['INTRO']['METHODOLOGY'][0]['DESCRIPTION'],
//// 			     'methodology_conditionning'=>$response['_source']['INTRO']['METHODOLOGY'][1]['DESCRIPTION'],
//// 			      'methodology_sample_storage'=>$response['_source']['INTRO']['METHODOLOGY'][2]['DESCRIPTION'],
//// 			     'methodology_custom'=>$response['_source']['INTRO']['METHODOLOGY'],
//// 			       'sampling_points'=>$response['_source']['INTRO']['SAMPLING_POINT'],
//// 			       	'files' => $response['_source']['DATA']['FILES'],
//// 			]);
//// 		}else{
//// 			return $responseSlim->withRedirect('/');
//// 		}
//// })->add($container->get('csrf'));

// ! Non utilisé
//// $app->post('/modify', function (Request $req, Response $responseSlim) {
//// 		$nameKey = $this
//// 		->csrf
//// 		->getTokenNameKey();
//// 		$valueKey = $this
//// 		->csrf
//// 		->getTokenValueKey();
//// 		$namecsrf  = $req->getAttribute($nameKey);
//// 		$valuecsrf = $req->getAttribute($valueKey);
//// 		$user      = new User();
//// 		$file   = new File();
//// 		$config = $file->ConfigFile();
//// 		$referent= $user->is_referent($_SESSION['mail'],$config['COLLECTION_NAME']);
//// 		if (($referent===true) OR $_SESSION['admin']==1) {
//// 		$request      = new RequestApi();
//// 		$response=$request->Post_Processing($_POST,'modify');
//// 		$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
//// 		$twig   = new Twig_Environment($loader);
//// 		if ($response === true) {
//// 					echo $twig->render('display_actions.html.twig',['message'=>'Data approved','mail' => $_SESSION['mail'], 'admin' => $_SESSION['admin'],'access'=>$_SESSION['admin']]);
//// 		}else{
//// 			echo $twig->render('upload.html.twig',[ 
//// 				'collection_name'=>$config['COLLECTION_NAME'],
//// 			'edit'=>true,'name_CSRF' => $namecsrf,
//// 			'route'=>'modify',
//// 			 'value_CSRF' => $valuecsrf, 
//// 			 'mail' => $_SESSION['mail'],
//// 			  'admin' => $_SESSION['admin'],
//// 			  'access'=>$_SESSION['admin'],
//// 			  'error'=>$response['error'],
//// 			  'title'=>$response['dataform']['TITLE'],
//// 			  'description'=>$response['dataform']['DATA_DESCRIPTION'],
//// 			  'sample_name'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['SAMPLE_NAME'],
//// 			  'language'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['LANGUAGE'],
//// 			  'block'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['BLOCK'],
//// 			  'pulp'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['PULP'],
//// 			  'core'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['CORE_DETAILS'][0]['CORE'],
//// 			  'ANTHROPOGENIC_MATERIAL'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['ANTHROPOGENIC_MATERIAL'],
//// 			  'language'=>$response['dataform']['LANGUAGE'],
//// 			  'keywords'=>$response['dataform']['KEYWORDS'],
//// 			  'institutions'=>$response['dataform']['INSTITUTION'],
//// 			  'scientific_fields'=>$response['dataform']['SCIENTIFIC_FIELD'],
//// 			   'measurement_abbv'=>$response['dataform']['MEASUREMENT'][0]['ABBREVIATION'],
//// 			    'measurement_unit'=>$response['dataform']['MEASUREMENT'][0]['UNIT'],
//// 			   'sampling_date'=>$response['dataform']['SAMPLING_DATE'][0],
//// 			   'lithology1'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['LITHOLOGY'],
//// 			   	'lithology2'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['LITHOLOGY_2'],
//// 			   'lithology3'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['LITHOLOGY_3'],
//// 			   'oretype1'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['ORE_TYPE_1'],
//// 			   	'oretype2'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['ORE_TYPE_2'],
//// 			   'oretype3'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['ORE_TYPE_3'],
//// 			   'texture1'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['TEXTURE_STRUCTURE_1'],
//// 			   	'texture2'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['TEXTURE_STRUCTURE_2'],
//// 			   'texture3'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['TEXTURE_STRUCTURE_3'],
//// 			    'sample_location_facility'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['SAMPLE_LOCATION_FACILITY'],
//// 			    'safety_constraints'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['SAFETY_CONSTRAINTS'],
//// 			    'storage_details'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['STORAGE_DETAILS'],
//// 			    'substance'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['SUBSTANCE'],
//// 			    'host_age'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['HOST_AGE'],
//// 			    'main_event_age'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['MAIN_EVENT_AGE'],
//// 			    'other_event_age'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['OTHER_EVENT_AGE'],
//// 			    'alteration_degree'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['ALTERATION_DEGREE'],
//// 			    'host_lithology_or_protolith'=>$response['dataform']['SUPPLEMENTARY_FIELDS']['HOST_LITHOLOGY_OR_PROTOLITH'],
//// 			    'methodology_sampling'=>$response['dataform']['METHODOLOGY'][0]['DESCRIPTION'],
//// 			     'methodology_conditionning'=>$response['dataform']['METHODOLOGY'][1]['DESCRIPTION'],
//// 			      'methodology_sample_storage'=>$response['dataform']['METHODOLOGY'][2]['DESCRIPTION'],
//// 			       'sampling_points'=>$response['dataform']['SAMPLING_POINT'],
//// 			]);
//// 		}
//// 		}else{
//// 			return $responseSlim->withRedirect('accueil');
//// 		}
//// })->add($container->get('csrf'));




$app->get('/recover', function (Request $req, Response $responseSlim) {
	if (!@$_SESSION['name']) {
		$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig   = new Twig_Environment($loader);
		$token  = $req->getparam('token');
		if ($token) {
			$user   = new User();
			$result = $user->check_token($token);
			if ($result == false) {
				return $responseSlim->withRedirect('accueil');
			} else {
				echo $twig->render('change_password.html.twig', ['token' => $token]);
			}
		} else {
			echo $twig->render('recover.html.twig');
		}
	} else {
		return $responseSlim->withRedirect('accueil');
	}
})->add($mw);

$app->post('/recover', function (Request $req, Response $responseSlim) {
	$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
	$twig   = new Twig_Environment($loader);
	$mail   = $req->getparam('email');
	$user   = new User();
	$error  = $user->recover_send_mail($mail);
	echo $twig->render('recover.html.twig', ['error' => $error, 'post' => 'true']);
})->add($mw);



$app->post('/contact', function (Request $req, Response $responseSlim) {
	if ($_SERVER['HTTP_REFERER'] != null) {
		$loader     = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig       = new Twig_Environment($loader);
		$sendermail = $req->getparam('User-email');
		$message    = $req->getparam('User-message');
		$object     = $req->getparam('User-object');
		$request    = new RequestApi();
		$Mail       = new Mailer();
		$error      = $Mail->Send_Contact_Mail($object, $message, $sendermail);
		echo $twig->render('contact_request.html.twig', ['error' => $error]);
	} else {
		return $responseSlim->withStatus(403);
	}
});

// ! Non utilisé
//// $app->post('/delete_data', function (Request $req, Response $responseSlim) {
//// 		$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
//// 		$twig   = new Twig_Environment($loader);
//// 		$id  = $req->getparam('id');
//// 		$req   = new RequestApi();
//// 		$error  = $req->delete_data($id);
//// 		return $responseSlim->withRedirect('validation');
//// })->add($container->get('csrf'));



$app->post('/resetpassword', function (Request $req, Response $responseSlim) {
	$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
	$twig   = new Twig_Environment($loader);
	$mail   = $_SESSION['mail'];
	$user   = new User();
	$error  = $user->recover_send_mail($mail);
	session_destroy();
	echo $twig->render('recover.html.twig', ['error' => $error, 'post' => 'true']);
})->add($mw)->add($container->get('csrf'));

$app->post('/change_password', function (Request $req, Response $responseSlim) {
	$loader          = new Twig_Loader_Filesystem('geosamples/frontend/templates');
	$twig            = new Twig_Environment($loader);
	$token           = $req->getparam('token');
	$password        = $req->getparam('password');
	$passwordconfirm = $req->getparam('password_confirm');
	$user            = new User();
	$error           = $user->change_password($token, $password, $passwordconfirm);
	echo $twig->render('change_password.html.twig', ['error' => $error, 'token' => $token, 'post' => 'true']);
})->add($mw);


$app->get('/listusers', function (Request $req, Response $responseSlim) {
	if (@$_SESSION['admin'] == 1) {
		$config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/../config.ini');
		$loader  = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig    = new Twig_Environment($loader);
		$nameKey = $this
			->csrf
			->getTokenNameKey();
		$valueKey = $this
			->csrf
			->getTokenValueKey();
		$namecsrf      = $req->getAttribute($nameKey);
		$valuecsrf     = $req->getAttribute($valueKey);
		$user          = new User();
		$usersreferents = $user->getReferentProjectsUSERS();
		$usersapproved = $user->getAllUsersApproved();
		$userswaiting  = $user->getAllUsersWaiting();
		$Allprojects  = $user->getAllProject();
		$usersawaitingvalidation = $user->getUserAwaitingValidationFromReferent($Allprojects);
		$allusers = json_encode($user->getAllUsersApprovedAutocomplete());
		echo $twig->render('listusers.html.twig', ['title' => "Administration panel", 'usersapproved' => $usersapproved, 'userswaiting' => $userswaiting, 'name_CSRF' => $namecsrf, 'value_CSRF' => $valuecsrf, 'mail' => $_SESSION['mail'], 'allprojects' => $Allprojects, 'UsersAwaitingValidation' => $usersawaitingvalidation, 'usersreferentsadmin' => $usersreferents, 'admin' => '1', 'alluser' => $allusers, 'access' => $_SESSION['admin'], 'project_name' => $config['COLLECTION_NAME']]);
	} else {
		$loader  = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig    = new Twig_Environment($loader);
		$nameKey = $this
			->csrf
			->getTokenNameKey();
		$valueKey = $this
			->csrf
			->getTokenValueKey();
		$namecsrf      = $req->getAttribute($nameKey);
		$valuecsrf     = $req->getAttribute($valueKey);
		$user          = new User();

		//null
		$usersreferents = $user->getReferentProjectsUSERS();

		//null
		$Allprojects  = $user->getReferentProject();

		//Projets
		$readonlyproject = $user->getNotReferentProject();

		//false
		$usersawaitingvalidation = $user->getUserAwaitingValidationFromReferent($Allprojects);

		//"null" mdrr
		$allusers = json_encode($user->getAllUsersApprovedAutocomplete());

		$file   = new File();
		$config = $file->ConfigFile();
		$feeder = $user->is_feeder($_SESSION['mail'], $config['COLLECTION_NAME']);
		$referent = $user->is_referent($_SESSION['mail'], $config['COLLECTION_NAME']);
		echo $twig->render('listusers.html.twig', ['usersreferents' => $usersreferents, 'name_CSRF' => $namecsrf, 'value_CSRF' => $valuecsrf, 'mail' => $_SESSION['mail'], 'allprojects' => $Allprojects, 'readonlyproject' => $readonlyproject, 'UsersAwaitingValidation' => $usersawaitingvalidation, 'alluser' => $allusers]);
	}
})->add($mw)->add($container->get('csrf'))->add($check_current_user)->setName('listusers');

$app->post('/approveuser', function (Request $req, Response $responseSlim) {
	if (@$_SESSION['admin'] == 1) {
		$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig   = new Twig_Environment($loader);
		$email  = $req->getparam('email');
		$user   = new User();
		$error  = $user->approveUser($email);
		return $responseSlim->withRedirect('listusers');
	}
})->add($mw)->add($container->get('csrf'));

$app->post('/disableuser', function (Request $req, Response $responseSlim) {
	if (@$_SESSION['admin'] == 1) {
		$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig   = new Twig_Environment($loader);
		$email  = $req->getparam('email');
		$user   = new User();
		$error  = $user->disableUser($email);
		return $responseSlim->withRedirect('listusers');
	}
})->add($mw)->add($container->get('csrf'));

$app->post('/removeuser', function (Request $req, Response $responseSlim) {
	if (@$_SESSION['admin'] == 1) {
		$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig   = new Twig_Environment($loader);
		$email  = $req->getparam('email');
		$user   = new User();
		$error  = $user->deleteUser($email);
		return $responseSlim->withRedirect('listusers');
	}
})->add($mw)->add($container->get('csrf'));

$app->post('/modifyuser', function (Request $req, Response $responseSlim) {
	if (@$_SESSION['admin'] == 1) {
		$loader    = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig      = new Twig_Environment($loader);
		$email     = $req->getparam('email');
		$name      = $req->getparam('name');
		$firstname = $req->getparam('firstname');

		$type      = $req->getparam('type');

		$user  = new User();

		// ! Plus utilisé
		////if (empty($name) && empty($firstname)) {
		// $error = $user->SetRightUser($email, $type,$project_name);
		////}else{
		$error = $user->modifyUser($email, $name, $firstname, $type);
		////}
		return $responseSlim->withRedirect('listusers');
	}

	// ! Plus utilisé
	//// else {
	//// 	$loader    = new Twig_Loader_Filesystem('geosamples/frontend/templates');
	//// 	$twig      = new Twig_Environment($loader);
	//// 	$email     = $req->getparam('email');
	//// 	$type      = $req->getparam('type');
	//// 	$project_name      = $req->getparam('project_name_modify');
	//// 	if ($type == 1) {
	//// 		return $responseSlim->withRedirect('listusers');
	//// 	} 
	//// 	elseif ($type == 2) {
	//// 		return $responseSlim->withRedirect('listusers');
	//// 	}
	//// 	elseif ($type == 3) {
	//// 		$type = 3;
	//// 	}
	//// 	else {
	//// 		$type = 0;
	//// 	}
	//// 	$user  = new User();
	//// 	$error = $user->SetRightUser($email, $type,$project_name);
	//// 	return $responseSlim->withRedirect('listusers');
	//// }

})->add($mw)->add($container->get('csrf'));


$app->post('/create_project', function (Request $req, Response $responseSlim) {
	if (@$_SESSION['admin'] == 1) {
		$loader    = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig      = new Twig_Environment($loader);
		$name      = $req->getparam('project_name');
		$user  = new User();
		$error = $user->Create_project($name);
		if ($error !== true) {
			return $responseSlim->withStatus(403);
		} else {
			return $responseSlim->withRedirect('listusers');
		}
	}
})->add($mw)->add($container->get('csrf'));


// ! N'est plus appelé
////$app->post('/get_user_projects', function (Request $req, Response $responseSlim) {
////	if (@$_SESSION['admin'] == 1) {
////		$loader    = new Twig_Loader_Filesystem('geosamples/frontend/templates');
////		$twig      = new Twig_Environment($loader);
////		$user  = new User();
////		$response = $user->getReferentProject();
////		return json_encode($response);
////	}
////
////});


// ! N'est plus appelé
////$app->post('/get_valid_user', function (Request $req, Response $responseSlim) {
////		$loader    = new Twig_Loader_Filesystem('geosamples/frontend/templates');
////		$twig      = new Twig_Environment($loader);
////		$user  = new User();
////		$response = $user->getAllUsersApprovedAutocomplete();
////		return json_encode($response);
////
////});



// ! N'est plus appelé
////$app->post('/get_user_in_projects', function (Request $req, Response $responseSlim) {
////	
////		$loader    = new Twig_Loader_Filesystem('geosamples/frontend/templates');
////		$twig      = new Twig_Environment($loader);
////		$project_name      = $req->getparam('project_name');
////		$user  = new User();
////		$response = $user->getUserInProject($project_name);
////		return json_encode($response);
////	
////
////});

$app->post('/add_user_projects', function (Request $req, Response $responseSlim) {
	$project_name      = $req->getparam('project_name');
	$loader    = new Twig_Loader_Filesystem('geosamples/frontend/templates');
	$twig      = new Twig_Environment($loader);
	$mail      = $req->getparam('mail_user');
	if ($_SESSION['admin'] == 1) {
		$user  = new User();
		$error = $user->AddUserToProject($mail, $project_name);
		return $responseSlim->withRedirect('listusers');
	}
	// ! Ne pas utiliser
	////else {
	////	foreach ($_SESSION['projects_access_right'] as $key => $value) {
	////		if (($project_name == $value->name) and (($value->user_type == 2))) {
	////			$user  = new User();
	////			$error = $user->AddUserToProject($mail, $project_name);
	////			return $responseSlim->withRedirect('listusers');
	////		}
	////	}
	////}
});

$app->post('/delete_user_projects', function (Request $req, Response $responseSlim) {
	$project_name      = $req->getparam('project_name');
	$loader    = new Twig_Loader_Filesystem('geosamples/frontend/templates');
	$twig      = new Twig_Environment($loader);
	$mail      = $req->getparam('mail_user');
	if ($_SESSION['admin'] == 1) {
		$user  = new User();
		$error = $user->DeleteUserFromProject($mail, $project_name);
		return $responseSlim->withRedirect('listusers');
	}
	// ! Plus utilisé
	////else {
	////	foreach ($_SESSION['projects_access_right'] as $key => $value) {
	////		if (($project_name == $value->name) and (($value->user_type == 2) || ($_SESSION['admin'] == 1))) {
	////			$user  = new User();
	////			if ($user->is_referent($mail, $project_name) == false) {
	////				$error = $user->DeleteUserFromProject($mail, $project_name);
	////			}
	////			return $responseSlim->withRedirect('listusers');
	////		}
	////	}
	////}
});

$app->get('/get_all_poi', function (Request $req, Response $responseSlim) {
	$request = new RequestApi();
	$response = $request->Request_all_poi();
	$responseSlim->withHeader('Content-Type', 'application/json');
	return $response;
});

// ! Surtout pas en prod
//// $app->get('/pass_es', function (Request $req, Response $responseSlim) {
//// 	$request = new RequestApi();
//// 	$response = $request->pass_es();
//// 	$responseSlim->withHeader('Content-Type', 'application/json');
//// 	return $response;
//// });

$app->post('/get_poi_sort', function (Request $req, Response $responseSlim) {
	$request = new RequestApi();
	$json  = $req->getparam('json');
	$response = $request->Request_poi_with_sort($json);
	$responseSlim->withHeader('Content-Type', 'application/json');
	return $response;
});

$app->post('/get_poi_type_data', function (Request $req, Response $responseSlim) {
	$json  = $req->getparam('json');
	$request = new RequestApi();
	$response = $request->Request_data_with_sort($json);
	$responseSlim->withHeader('Content-Type', 'application/json');
	//return $response;
})->add($check_access_right_file);
$app->post('/download_poi_type_data', function (Request $req, Response $responseSlim) {
	$json  = $req->getparam('json');
	$request = new RequestApi();
	$response = $request->Download_data_with_sort($json);
	$responseSlim->withHeader('Content-Type', 'application/json');
	//return $response;
})->add($check_access_right_file);


$app->get('/download_poi_data/{name}', function (Request $req, Response $responseSlim, $args) {
	$name = $args['name'];
	$name = str_replace(' ', '', $name);
	if (strrpos($name, '_RAW') != false) {
		return $responseSlim->withStatus(403);
	}
	$request = new RequestApi();
	$response = $request->Request_poi_data($name);
	$response = json_decode($response, TRUE);
	$path = $response['FILES'][0]['ORIGINAL_DATA_URL'];
	$download = $request->download($path);
	if ($download == NULL or $download == false) {
		return $responseSlim->withStatus(403);
	}
})->add($check_access_right_file);


$app->get('/download_poi_raw_data/{name}', function (Request $req, Response $responseSlim, $args) {
	$name = $args['name'];
	$name = str_replace(' ', '', $name);
	$request = new RequestApi();
	$response = $request->Request_poi_raw_data($name);
	$response = json_decode($response, TRUE);
	$path = $response['FILES'][0]['ORIGINAL_DATA_URL'];
	$download = $request->download($path);
	if ($download == NULL or $download == false) {
		//return $responseSlim->withStatus(403);
	}
})->add($check_access_right_file);


$app->get('/download_poi_data_awaiting/{name}/{picturename}', function (Request $req, Response $responseSlim, $args) {
	$user = new User();
	$referent = $user->is_referent($_SESSION['mail'], $config['COLLECTION_NAME']);
	if (($referent === true) or $_SESSION['admin'] == 1) {
		$name = $args['name'];
		$picture = $args['picturename'];
		$request = new RequestApi();
		$path = $request->Request_poi_data_Awaiting($name, $picture);
		$download = $request->download_img($path);
		if ($download == NULL or $download == false) {
			return $responseSlim->withStatus(403);
		}
	} else {
		return $responseSlim->withRedirect('/');
	}
})->add($check_access_right_file);

$app->get('/preview_img/{name}/{picturename}', function (Request $req, Response $responseSlim, $args) {
	$name = $args['name'];
	$picture = $args['picturename'];
	$request = new RequestApi();
	$path = $request->Request_poi_img($name, $picture);
	$download = $request->preview_img($path);
	if ($download == NULL or $download == false) {
		return $responseSlim->withStatus(403);
	}
	return $responseSlim->withHeader('Content-type', $download);
});

$app->get('/download_img/{name}/{picturename}', function (Request $req, Response $responseSlim, $args) {
	$name = $args['name'];
	$picture = $args['picturename'];
	$request = new RequestApi();
	$path = $request->Request_poi_img($name, $picture);
	$download = $request->download_img($path);
	if ($download == NULL or $download == false) {
		return $responseSlim->withStatus(403);
	}
});


$app->get('/preview_poi_data/{name}', function (Request $req, Response $responseSlim, $args) {
	$name = $args['name'];
	$name = str_replace(' ', '', $name);
	$request = new RequestApi();
	$response = $request->Request_poi_data($name);
	$response = json_decode($response, TRUE);
	$path = $response['FILES'][0]['ORIGINAL_DATA_URL'];
	$download = $request->preview($path, $name, $response['FILES'][0]['DATA_URL']);

	return $responseSlim->withStatus(200);
})->add($check_access_right_file);



$app->run();
