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

    public function getByVID($vID)
    {
        $data = $this->model->getWhere(["vID" => $vID, "personID" => AuthMiddleware::$person["id"]])[0];
        if (!$data) {
            response(NULL, 404, "Data not found.");
        }

        $data["personID"] = AuthMiddleware::$person["vID"];
        $data["subDatas"] = $this->subDataModel->getWhere(["dataID" => $data["id"]], "id, subDataTypeID, accessLevelID, sdKey, sdValue");

        unset($data["id"]);
        response($data);
    }

    public function getAll()
    {
        $datas = $this->model->getAllOwnDataByPersonId(AuthMiddleware::$person["id"]);

        response($datas);
    }

    public function create()
    {
        checkRequiredParams(['title', 'note', 'accessTypeID', 'accessLevelID', 'isPassive'], $this->params);
        $vID = self::generateUniqueVid("data");

        $accessType = (new VirtualModel("access_types"))->getById($this->params["accessTypeID"], "title");
        if (!$accessType) {
            response(NULL, 404, "Access type not found.");
        }

        $accessLevel = (new VirtualModel("access_levels"))->getById($this->params["accessLevelID"], "title");
        if (!$accessLevel) {
            response(NULL, 404, "Access level not found.");
        }

        $data = [
            "personID" => AuthMiddleware::$person['id'],
            "vID" => $vID,
            "title" => $this->params['title'],
            "note" => $this->params['note'],
            "accessTypeID" => $this->params['accessTypeID'],
            "accessLevelID" => $this->params["accessLevelID"],
            "isPassive" => $this->params['isPassive'],
            "releaseTime" => $this->params['accessTypeID'] == 1 ? date_create()->format('Y-m-d H:i:s') : NULL
        ];

        $createdData = $this->model->create($data);
        unset($createdData["id"]);
        unset($createdData['personID']);
        $createdData["accessType"] = $accessType["title"];
        $createdData["accessLevel"] = $accessLevel["title"];
        $createdData ? response($createdData, 201, "Data created.") : response(NULL, 500, "Internal Server Error");
    }

    public function update($vID)
    {
        checkRequiredParams(["title", "note", "accessTypeID", "isPassive"], $this->params);
        $dataVID = sanitizeId($vID);

        $data = $this->model->getWhere(["vID" => $dataVID, "personID" => AuthMiddleware::$person["id"]])[0];
        if (!$data) {
            response(NULL, 404, "Data not found or you are not authorized to update subdata for this data.");
        }

        if ($this->params['accessTypeID'] == 2 || $this->params['accessTypeID'] == 3) {
            $releaseTime = date_create()->format('Y-m-d H:i:s');
        }

        $newData = [
            "title" => $this->params["title"],
            "note" => $this->params["note"],
            "accessTypeID" => $this->params["accessTypeID"],
            "accessLevelID" => $this->params["accessLevelID"],
            "isPassive" => $this->params["isPassive"],
            "releaseTime" => $releaseTime ?? NULL
        ];
        $updatedData = $this->model->update($data["id"], $newData);
        unset($updatedData["id"]);
        unset($updatedData["personID"]);
        response($updatedData);
    }
}
