<?php

require_once __DIR__ . '/../Models/DataModel.php';
require_once __DIR__ . '/../Models/SubDataModel.php';
require_once __DIR__ . '/../Models/VirtualModel.php';
require_once __DIR__ . '/../Middlewares/AuthMiddleware.php';

class SubDataController extends MainController
{
    private $DataModel;
    public function __construct()
    {
        $this->DataModel = new DataModel();
        parent::__construct(new SubDataModel());
    }

    public function store() {
        checkRequiredParams(['dataID', 'subDataTypeID', 'accessLevelID', 'key', 'value'], $this->params);
        $data = $this->DataModel->getByVID($this->params['dataID'], "id, personID, vID");
        if (!$data) {
            response(NULL, 404, "Data not found.");
        } elseif ($data['personID'] != AuthMiddleware::$person['id']) {
            response(NULL, 403, "You are not authorized to create sub-data for this data.");
        }

        $subDataType = (new VirtualModel('sub_data_types'))->getById($this->params['subDataTypeID'], "title");
        if (!$subDataType) {
            response(NULL, 404, "Sub-data type not found.");
        }

        $accessLevel = (new VirtualModel('access_levels'))->getById($this->params['accessLevelID'], "title");
        if (!$accessLevel) {
            response(NULL, 404, "Access level not found.");
        }
        
        $subData = [
            "dataID" => $data["id"],
            "subDataTypeID" => $this->params['subDataTypeID'],
            "accessLevelID" => $this->params['accessLevelID'],
            "sdKey" => $this->params['key'],
            "sdValue" => $this->params['value']
        ];

        $createdSubData = $this->model->create($subData);
        if ($createdSubData) {
            $createdSubData['dataID'] = $data["vID"];
            $createdSubData['subDataType'] = $subDataType["title"];
            $createdSubData['accessLevel'] = $accessLevel["title"];
            response($createdSubData, 201, "Sub-data created.");
        } else {
            response(NULL, 500, "Internal Server Error.");
        }
    }

}
