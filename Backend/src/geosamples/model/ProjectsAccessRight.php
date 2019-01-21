<?php
namespace geosamples\model;

use Illuminate\Database\Eloquent\Model as model;

class ProjectsAccessRight extends model
{
    protected $table      = 'Projects_access_right';
    protected $primaryKey = 'id';
    private $projects_access_right;

}
