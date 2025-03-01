<?php

require_once __DIR__ . '/MainModel.php';

class SubDataModel extends MainModel
{
    protected $table = 'subDatas';

    function __construct() {
        parent::__construct();
    }
}
