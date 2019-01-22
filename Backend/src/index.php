<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \geosamples\backend\controller\RequestController as RequestApi;
use \geosamples\backend\controller\UserController as User;
use \geosamples\backend\controller\FileController as File;
require '../vendor/autoload.php';

$c = new \Slim\Container();//Initialisation de Slim
$app = new \Slim\App($c);
$container         = $app->getContainer();
$container['csrf'] = function ($c) {
	$guard = new \Slim\Csrf\Guard;
	$guard->setFailureCallable(function ($request, $response, $next) {
		$request = $request->withAttribute("csrf_status", false);
		$loader  = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig    = new Twig_Environment($loader);
		echo $twig->render('forbidden.html.twig');
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
	} else {
       /* $file   = new File();
        $config = $file->ConfigFile();
        if (strlen($config['REPOSITORY_NAME']) == 0 or strlen($config['host']) == 0 or strlen($config['authSource']) == 0 or strlen($config['DOI_PREFIX']) == 0) {
            $loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
            $twig   = new Twig_Environment($loader);
            $render = $twig->render('notfound.html.twig');
        }*/
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
	if (($_SESSION['mail']) or ($_SESSION['admin']==1) )  {
		$response = $next($request, $response);
		return $response;
	}
	else{
		$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig   = new Twig_Environment($loader);
		$render = $twig->render('notfound.html.twig');
		$response->write($render);
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

$app->get('/', function (Request $req,Response $responseSlim) {
	$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
	$twig = new Twig_Environment($loader);
	$config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/../config.ini');

	echo $twig->render('accueil.html.twig',['project_name' => $config['PROJECT_NAME'],'name' => $_SESSION['name'], 'firstname' => $_SESSION['firstname'], 'mail' => $_SESSION['mail'],'name'=>'map','access'=>$_SESSION['admin']]);

});

$app->get('/accueil', function (Request $req,Response $responseSlim) {

	return $responseSlim->withRedirect('/');

});


//Route permettant la connexion d'un utilisateur
$app->get('/login', function (Request $req, Response $responseSlim) {

	$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
	$twig   = new Twig_Environment($loader);
	echo $twig->render('login.html.twig');

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



$app->get('/loginCAS', function (Request $req, Response $responseSlim) {
	$user      = new User();
	$checkuser = $user->check_current_user($_SERVER['HTTP_MAIL']);
	if ($checkuser) {
		$_SESSION['name']      = $checkuser->name;
		$_SESSION['firstname'] = $checkuser->firstname;
		$_SESSION['mail']      = $checkuser->mail;
		$_SESSION['admin']     = $checkuser->type;
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

		$loader    = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig      = new Twig_Environment($loader);
		echo $twig->render('signup.html.twig', ['name_CSRF' => $namecsrf, 'value_CSRF' => $valuecsrf]);
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
	$password        = $req->getparam('password');
	$passwordconfirm = $req->getparam('password_confirm');
	$user            = new User();
	$error           = $user->signup($name, $firstname, $mail, $password, $passwordconfirm);
	if (!$error) {
		return $responseSlim->withRedirect('accueil');
	} else {
		echo $twig->render('signup.html.twig', ['error' => $error, 'name_CSRF' => $nameKey, 'value_CSRF' => $valueKey]);
	}

})->add($container->get('csrf'));

$app->get('/myaccount', function (Request $req, Response $responseSlim) {
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
		$user      = new User();
		$user      = $user->getUserInfo($_SESSION['mail']);

		echo $twig->render('myaccount.html.twig', ['name' => $user[0]->name, 'firstname' => $user[0]->firstname, 'name_CSRF' => $namecsrf, 'value_CSRF' => $valuecsrf, 'mail' => $_SESSION['mail'], 'admin' => $_SESSION['admin'],'access'=>$_SESSION['admin']]);
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
		echo $twig->render('myaccount.html.twig', ['message'=> "Account updated successfully",'name' => $user[0]->name, 'firstname' => $user[0]->firstname, 'name_CSRF' => $namecsrf, 'value_CSRF' => $valuecsrf, 'mail' => $_SESSION['mail'], 'admin' => $_SESSION['admin']]);

	}
})->add($mw)->add($container->get('csrf'));


$app->get('/logout', function (Request $req, Response $responseSlim) {
	$loader = new Twig_Loader_Filesystem('geosamples/frontend/templates');
	$twig   = new Twig_Environment($loader);
	session_destroy();
	$file   = new File();
	$config = $file->ConfigFile();
   // return $responseSlim->withRedirect($config['REPOSITORY_URL'] . '/Shibboleth.sso/Logout?return=' . $config['REPOSITORY_URL']);

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
		$usersapproved = $user->getAllUsersApproved();
		$userswaiting  = $user->getAllUsersWaiting();
		$Allprojects  = $user->getAllProject();
		echo $twig->render('listusers.html.twig', ['usersapproved' => $usersapproved, 'userswaiting' => $userswaiting, 'name_CSRF' => $namecsrf, 'value_CSRF' => $valuecsrf, 'mail' => $_SESSION['mail'],'allprojects'=>$Allprojects]);
	} else {
		return $responseSlim->withRedirect('accueil');
	}
})->add($mw)->add($container->get('csrf'))->add($check_current_user);

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
		if ($type == 1) {
			$type = 1;
		} 
		elseif ($type == 2) {
			$type = 2;
		}
		else {
			$type = 0;
		}
		$user  = new User();
		$error = $user->modifyUser($email, $name, $firstname, $type);
		return $responseSlim->withRedirect('listusers');
	}

})->add($mw)->add($container->get('csrf'));

$app->post('/create_project', function (Request $req, Response $responseSlim) {
	if (@$_SESSION['admin'] == 1) {
		$loader    = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig      = new Twig_Environment($loader);
		$name      = $req->getparam('project_name');
		$user  = new User();
		$error = $user->Create_project($name);
		return $responseSlim->withRedirect('listusers');
	}

})->add($mw)->add($container->get('csrf'));


$app->post('/get_user_projects', function (Request $req, Response $responseSlim) {
	if (@$_SESSION['admin'] == 1) {
		$loader    = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig      = new Twig_Environment($loader);
		$user  = new User();
		$response = $user->getReferentProject();
		return json_encode($response);
	}

});

$app->post('/get_valid_user', function (Request $req, Response $responseSlim) {
	if (@$_SESSION['admin'] == 1) {
		$loader    = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig      = new Twig_Environment($loader);
		$user  = new User();
		$response = $user->getAllUsersApprovedAutocomplete();
		return json_encode($response);
	}

});

$app->post('/get_user_in_projects', function (Request $req, Response $responseSlim) {
	if (@$_SESSION['admin'] == 1) {
		$loader    = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig      = new Twig_Environment($loader);
		$project_name      = $req->getparam('project_name');
		$user  = new User();
		$response = $user->getUserInProject($project_name);
		return json_encode($response);
	}

});

$app->post('/add_user_projects', function (Request $req, Response $responseSlim) {
	if (@$_SESSION['admin'] == 1) {
		$loader    = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig      = new Twig_Environment($loader);
		$mail      = $req->getparam('mail_user');
		$project_name      = $req->getparam('project_name');
		$user  = new User();
		$error = $user->AddUserToProject($mail,$project_name);
		return$error;
	}

});

$app->post('/delete_user_projects', function (Request $req, Response $responseSlim) {
	if (@$_SESSION['admin'] == 1) {
		$loader    = new Twig_Loader_Filesystem('geosamples/frontend/templates');
		$twig      = new Twig_Environment($loader);
		$mail      = $req->getparam('mail_user');
		$project_name      = $req->getparam('project_name');
		$user  = new User();
		$error = $user->DeleteUserFromProject($mail,$project_name);
		echo $error;
	}

});



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
})->add($check_access_right_file);
$app->post('/download_poi_type_data', function (Request $req,Response $responseSlim) {
	$json  = $req->getparam('json');
	$request = new RequestApi();
	$response = $request->Download_data_with_sort($json);
	$responseSlim->withHeader('Content-Type', 'application/json');
    //return $response;
})->add($check_access_right_file);


$app->get('/download_poi_data/{name}', function (Request $req,Response $responseSlim,$args) {
	$name = $args['name'];
	$name = str_replace(' ', '', $name);
	if (strrpos($name, '_RAW')!=false) {
		return $responseSlim->withStatus(403);
	}
	$request = new RequestApi();
	$response = $request->Request_poi_data($name);
	$response=json_decode($response,TRUE);
	$path=$response['FILES'][0]['ORIGINAL_DATA_URL'];
	$download = $request->download($path);
	if ($download == NULL or $download == false) {
		return $responseSlim->withStatus(403);
	}
})->add($check_access_right_file);

$app->get('/download_poi_raw_data/{name}', function (Request $req,Response $responseSlim,$args) {
	$name = $args['name'];
	$name = str_replace(' ', '', $name);
	$request = new RequestApi();
	$response = $request->Request_poi_raw_data($name);
	$response=json_decode($response,TRUE);
	$path=$response['FILES'][0]['ORIGINAL_DATA_URL'];
	var_dump($path);
	$download = $request->download($path);
	if ($download == NULL or $download == false) {
		//return $responseSlim->withStatus(403);
	}
})->add($check_access_right_file);


$app->get('/download_img/{name}/{picturename}', function (Request $req,Response $responseSlim,$args) {
	$name = $args['name'];
	$picture = $args['picturename'];
	$request = new RequestApi();
	$path = $request->Request_poi_img($name,$picture);
	$download = $request->download($path);
	if ($download == NULL or $download == false) {
		return $responseSlim->withStatus(403);
	}
});

$app->get('/preview_img/{name}/{picturename}', function (Request $req,Response $responseSlim,$args) {
	$name = $args['name'];
	$picture = $args['picturename'];
	$request = new RequestApi();
	$path = $request->Request_poi_img($name,$picture);
	$download = $request->preview_img($path);
	if ($download == NULL or $download == false) {
		return $responseSlim->withStatus(403);
	}
	return $responseSlim->withHeader('Content-type', $download);

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
})->add($check_access_right_file);




$app->run();
