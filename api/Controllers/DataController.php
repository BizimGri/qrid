<?php

require_once __DIR__ . '/../Models/DataModel.php';
require_once __DIR__ . '/../Models/SubDataModel.php';
require_once __DIR__ . '/../Models/VirtualModel.php';
require_once __DIR__ . '/../Middlewares/AuthMiddleware.php';

class DataController extends MainController
{
    private $subDataModel;
    public function __construct()
    {
        $this->subDataModel = new SubDataModel();
        parent::__construct(new DataModel());
    }

    public function store() {
        checkRequiredParams(['title', 'note', 'accessTypeID', 'isPassive'], $this->params);
        $vID = self::generateUniqueVid("data");

        $accessType = (new VirtualModel("access_types"))->getById($this->params["accessTypeID"], "title");
        if(!$accessType){
            response(NULL, 404, "Access type not found.");
        }

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
        unset($createdData["id"]);
        unset($createdData['personID']);
        $createdData["accessType"] = $accessType["title"];
        $createdData ? response($createdData, 201, "Data created.") : response(NULL, 500, "Internal Server Error");
    }

}
