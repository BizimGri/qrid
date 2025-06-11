<?php

require_once __DIR__ . '/../Models/DataModel.php';
require_once __DIR__ . '/../Models/SubDataModel.php';
require_once __DIR__ . '/../Models/VirtualModel.php';
require_once __DIR__ . '/../Middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../Models/AccessModel.php';

class DataController extends MainController
{
    private $subDataModel;
    private $accessModel;
    public function __construct()
    {
        $this->subDataModel = new SubDataModel();
        $this->accessModel = new AccessModel();
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

        if (!empty($data["subDatas"])) {
            foreach ($data["subDatas"] as $subData) {
                switch ($subData["subDataTypeID"]) {
                    case "100":
                        $data["chat_video"] = true;
                        $data["chat_audio"] = true;
                        $data["chat_text"] = true;
                        break;
                    case "101":
                        $data["chat_video"] = true;
                        break;
                    case "102":
                        $data["chat_audio"] = true;
                        break;
                    case "103":
                        $data["chat_text"] = true;
                        break;
                    default:
                        break;
                }
            }
        }

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
            "releaseTime" => $this->params['accessTypeID'] == 1 ? date_create()->format('Y-m-d H:i:s') : NULL,
            "type" => !empty($this->params["type"]) ? $this->params["type"] : NULL
        ];

        $createdData = $this->model->create($data);
        if (!empty($createdData)) {
            if (!empty($this->params["subDatas"])) {
                foreach ($this->params["subDatas"] as $value) {
                    $subData = [
                        "dataID" => $createdData["id"],
                        "subDataTypeID" => $value['subDataTypeID'],
                        "accessLevelID" => $value['accessLevelID'],
                        "sdKey" => $value['sdKey'],
                        "sdValue" => $value['sdValue']
                    ];

                    $this->subDataModel->create($subData);
                }
            }
            unset($createdData["id"]);
            unset($createdData['personID']);
            $createdData["accessType"] = $accessType["title"];
            $createdData["accessLevel"] = $accessLevel["title"];
            response($createdData, 201, "Data created.");
        } else response(NULL, 500, "Internal Server Error");
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
            "releaseTime" => $releaseTime ?? NULL,
            "type" => !empty($this->params["type"]) ? $this->params["type"] : NULL
        ];
        $updatedData = $this->model->update($data["id"], $newData);
        unset($updatedData["id"]);
        unset($updatedData["personID"]);
        response($updatedData);
    }

    public function delete($vID)
    {
        $dataVID = sanitizeId($vID);
        $data = $this->model->getWhere(["vID" => $dataVID, "personID" => AuthMiddleware::$person["id"]])[0];
        if (!$data) {
            response(NULL, 404, "Data not found or you are not authorized to update subdata for this data.");
        }

        $deletedData = $this->model->delete($data["id"]);
        if ($deletedData) {
            // DELETE SUBDATAS
            $subDatas = $this->subDataModel->getWhere(["dataID" => $data["id"]], "id");
            foreach ($subDatas as $subData) {
                $this->subDataModel->delete($subData["id"]);
            }
            // DELETE ACCESS REQUESTS
            $accesssRequests = $this->accessModel->getWhere(["entityID" => $data["id"], "type" => "d"], "id");
            foreach ($accesssRequests as $accessRequest) {
                $this->accessModel->delete($accessRequest["id"]);
            }
            response(NULL, 200, "Data deleted.");
        } else {
            response(NULL, 500, "Internal Server Error.");
        }
    }
}
