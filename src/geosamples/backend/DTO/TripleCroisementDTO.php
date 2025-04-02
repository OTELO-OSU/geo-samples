<?php

namespace geosamples\backend\DTO;

class TripleCroisementDTO
{
    public $userId;
    public $mail;
    public $name;
    public $firstname;
    public $user_type;
    public $project_name;

    public function __construct($userId, $mail, $name, $firstname, $userType, $projectName)
    {
        $this->userId = $userId;
        $this->mail = $mail;
        $this->name = $name;
        $this->firstname = $firstname;
        $this->user_type = $userType;
        $this->project_name = $projectName;
    }
}