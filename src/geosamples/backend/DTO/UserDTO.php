<?php

namespace geosamples\backend\DTO;

class UserDTO
{
    public $id_user;
    public $name;
    public $firstname;
    public $mail;
    public $mdp;
    public $status;
    public $mail_validation;
    public $type;

    public function __construct($id_user, $name, $firstname, $mail, $mdp, $status, $mail_validation, $type){
        $this->id_user = $id_user;
        $this->name = $name;
        $this->firstname = $firstname;
        $this->mail = $mail;
        $this->mdp = $mdp;
        $this->status = $status;
        $this->mail_validation = $mail_validation;
        $this->type = $type;
    }
}