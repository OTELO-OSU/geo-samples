<?php
namespace geosamples\model;


use Illuminate\Database\Eloquent\Model as model;

class LostPassword extends model{
	protected $table = 'lost_password';
	protected $primaryKey = 'mail';
	private $lost_password;



	
}
