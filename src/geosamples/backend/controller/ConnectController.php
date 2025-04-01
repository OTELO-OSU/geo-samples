<?php
namespace geosamples\backend\controller;

use Illuminate\Database\Capsule\Manager as DB;

class ConnectController
{

    public static function EloConfigure($filname)
    {
        $config = parse_ini_file($filname);
        if (!$config) {
            throw new \Exception('App::eloConfigure: could not parse config file' . $filname . '<br/>');
        }

        $capsule = new DB();
        $capsule->addConnection($config);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}