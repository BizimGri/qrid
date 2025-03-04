<?php

require_once __DIR__ . '/MainModel.php';

class VirtualModel extends MainModel
{
    protected $table;

    function __construct($tableName)
    {
        $this->table = $tableName;
        parent::__construct();
    }
}
