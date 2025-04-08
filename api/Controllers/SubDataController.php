<?php

require_once __DIR__ . '/../Models/DataModel.php';
require_once __DIR__ . '/../Models/SubDataModel.php';
require_once __DIR__ . '/../Models/VirtualModel.php';
require_once __DIR__ . '/../Middlewares/AuthMiddleware.php';

class SubDataController extends MainController
{
    private $dataModel;
    public function __construct()
    {
        $this->dataModel = new DataModel();
        parent::__construct(new SubDataModel());
    }

    public function create()
    {
        checkRequiredParams(['dataID', 'subDataTypeID', 'accessLevelID', 'sdKey', 'sdValue'], $this->params);
        $data = $this->dataModel->getByVID($this->params['dataID'], "id, personID, vID");
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
            "sdKey" => $this->params['sdKey'],
            "sdValue" => $this->params['sdValue']
        ];

        $createdSubData = $this->model->create($subData);
        if ($createdSubData) {
            $createdSubData['dataID'] = $data["vID"];
            $createdSubData['subDataType'] = $subDataType["title"];
            $createdSubData['accessLevel'] = $accessLevel["title"];
            response($createdSubData, 201, "Subdata created.");
        } else {
            response(NULL, 500, "Internal Server Error.");
        }
    }

    public function update($id)
    {
        checkRequiredParams(["dataID", "subDataTypeID", "accessLevelID", "sdKey", "sdValue"], $this->params);
        $dataID = sanitizeId($this->params["dataID"]);


        $data = $this->dataModel->getWhere(["vID" => $dataID, "personID" => AuthMiddleware::$person["id"]], "id, vID, personID");
        if (!$data) {
            response(NULL, 404, "Data not found or you are not authorized to update subdata for this data.");
        }

        $subDataCheck = $this->model->exists(["id" => $id, "dataID" => $data[0]["id"]]);
        if (!$subDataCheck) {
            response(NULL, 404, "Subdata not found or you are not authorized to update subdata for this data.");
        }

        $subDataType = (new VirtualModel('sub_data_types'))->getById($this->params['subDataTypeID'], "title");
        if (!$subDataType) {
            response(NULL, 404, "Subdata type not found.");
        }

        $accessLevel = (new VirtualModel('access_levels'))->getById($this->params['accessLevelID'], "title");
        if (!$accessLevel) {
            response(NULL, 404, "Access level not found.");
        }

        $subData = [
            "dataID" => $data[0]["id"],
            "subDataTypeID" => $this->params['subDataTypeID'],
            "accessLevelID" => $this->params['accessLevelID'],
            "sdKey" => $this->params['sdKey'],
            "sdValue" => $this->params['sdValue']
        ];

        $updatedSubData = $this->model->update($id, $subData);
        if ($updatedSubData) {
            $updatedSubData['dataID'] = $dataID;
            $updatedSubData['subDataType'] = $subDataType["title"];
            $updatedSubData['accessLevel'] = $accessLevel["title"];
            response($updatedSubData, 201, "Subdata updated.");
        } else {
            response(NULL, 500, "Internal Server Error.");
        }
    }
}
