<?php

require_once __DIR__ . '/MainModel.php';

class DataModel extends MainModel
{
    protected $table = 'datas';

    function __construct() {
        parent::__construct();
    }
}
