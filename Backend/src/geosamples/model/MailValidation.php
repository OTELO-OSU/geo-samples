<?php
namespace geosamples\model;


use Illuminate\Database\Eloquent\Model as model;

class MailValidation extends model{
	protected $table = 'mail_validation';
	protected $primaryKey = 'mail';
	private $mailvalidation;



	
}
