<?php
namespace geosamples\backend\controller;

class FileController
{

    public function ConfigFile()
    {
        $config                    = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/../config.ini');
        $config['PROJECT_NAME'] = strtoupper($config["PROJECT_NAME"]);
        
        return $config;
        
    }

}
