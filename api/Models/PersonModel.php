<?php

require_once __DIR__ . '/MainModel.php';

class PersonModel extends MainModel
{
    protected $table = 'persons';

    function __construct()
    {
        parent::__construct();
    }
}
