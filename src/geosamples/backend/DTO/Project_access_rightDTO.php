<?php

namespace geosamples\backend\DTO;

class Project_access_rightDTO
{
    public $id_project;
    public $name;
    public $user_type;

    public function __construct(int $id_project, string $name, int $user_type)
    {
        $this->id_project = $id_project;
        $this->name = $name;
        $this->user_type = $user_type;
        
    }
}
