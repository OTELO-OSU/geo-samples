<?php
namespace geosamples\model;

use Illuminate\Database\Eloquent\Model as model;

class Projects extends model
{
    protected $table      = 'Projects';
    protected $primaryKey = 'id';
    private $projects;

}
