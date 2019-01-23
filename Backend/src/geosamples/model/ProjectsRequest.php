<?php
namespace geosamples\model;

use Illuminate\Database\Eloquent\Model as model;

class ProjectsRequest extends model
{
    protected $table      = 'Projects_request';
    protected $primaryKey = 'id';
    private $projects_access_right;

}
