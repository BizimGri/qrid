<?php

require_once __DIR__ . '/../Models/DataModel.php';
require_once __DIR__ . '/../Middlewares/AuthMiddleware.php';

class DataController extends MainController
{
    public function __construct()
    {
        parent::__construct(new DataModel());
    }

    public function store() {
        checkRequiredParams(['title', 'note', 'accessTypeID', 'isPassive'], $this->params);
        $vID = self::generateUniqueVid("data");

        $data = [
            "personID" => AuthMiddleware::$person['id'],
            "vID" => $vID,
            "title" => $this->params['title'],
            "note" => $this->params['note'],
            "accessTypeID" => $this->params['accessTypeID'],
            "isPassive" => $this->params['isPassive'],
            "releaseTime" => $this->params['accessTypeID'] == 1 ? date_create()->format('Y-m-d H:i:s') : NULL
        ];

        $createdData = $this->model->create($data);
        $createdData ? response($createdData, 201, "Data created.") : response(NULL, 500, "Internal Server Error");
    }
}
