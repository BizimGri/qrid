<?php

require_once __DIR__ . '/MainModel.php';

class AccessModel extends MainModel
{
    protected $table = 'personAccess';

    function __construct()
    {
        parent::__construct();
    }
}
