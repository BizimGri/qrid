<?php


require_once __DIR__ . '/../Models/PersonModel.php';

class PersonController extends MainController
{
    public function __construct()
    {
        parent::__construct(new PersonModel());
    }

    function register() {
        response(["OK" => "register"]);
    }

    function login() {
        response(["OK" => "login"]);
    }
}
