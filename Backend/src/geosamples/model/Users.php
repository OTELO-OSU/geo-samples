<?php
namespace geosamples\model;

use Illuminate\Database\Eloquent\Model as model;

class Users extends model
{
    protected $table      = 'users';
    protected $primaryKey = 'mail';
    private $users;

}
