<?php

require_once __DIR__ . '/../Models/PersonModel.php';

class PersonController extends MainController
{
    public function __construct()
    {
        parent::__construct(new PersonModel());
    }

    function register() {
        checkRequiredParams(['name', 'email', 'password'], $this->params);

        if($this->model->exists(["email" => $this->params['email']])) {
            response(NULL, 409, "Email already exists.");
        }

        $vIDCount = 0;
        do {
            $vID = generateVid();
            if($vIDCount > 0) createLog("WARNING", "VID collision detected: $vID # vIDCount: $vIDCount");
        } while ($this->model->exists(["vID" => $vID]) && $vIDCount++ < 5);

        if ($vIDCount >= 5) {
            createLog("ERROR", "Failed to generate a unique VID after 5 attempts.");
            response(NULL, 500, "Internal Server Error");
        }

        $person = [
            "vID" => $vID,
            "name" => $this->params['name'],
            "email" => $this->params['email'],
            "password" => hashPassword($this->params['password'])
        ];

        $this->model->create($person, false) ? response([], 201, "Person created.") : response(NULL, 500, "Internal Server Error");        
    }

    function login() {
        response(["OK" => "login"]);
    }
}
