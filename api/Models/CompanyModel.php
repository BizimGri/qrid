<?php

require_once __DIR__ . '/MainModel.php';

class CompanyModel extends MainModel
{
    protected $table = 'companies';

    function __construct()
    {
        parent::__construct();
    }
}
