<?php

require_once __DIR__ . '/MainController.php';
require_once __DIR__ . '/../Models/UserModel.php';

class UserController extends MainController
{
    public function __construct()
    {
        parent::__construct(new UserModel());
    }
}
