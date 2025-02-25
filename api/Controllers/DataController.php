<?php

require_once __DIR__ . '/../Models/DataModel.php';

class DataController extends MainController
{
    public function __construct()
    {
        parent::__construct(new DataModel());
    }
}
