<?php

require_once __DIR__ . '/../Models/AccessModel.php';
require_once __DIR__ . '/../Models/DataModel.php';
require_once __DIR__ . '/../Models/DataModel.php';
require_once __DIR__ . '/../Models/PersonModel.php';
require_once __DIR__ . '/../Middlewares/AuthMiddleware.php';

class AccessController extends MainController
{
    private $dataModel;
    private $subDataModel;
    private $personModel;

    public function __construct()
    {
        $this->dataModel = new DataModel();
        $this->subDataModel = new SubDataModel();
        $this->personModel = new PersonModel();
        parent::__construct(new AccessModel());
    }

    public function get()
    {
        $publicID = ApiConstants::getWord("accessTypes", "public");
        $sharedID = ApiConstants::getWord("accessTypes", "shared");

        if (!empty($this->params["data"])) {
            $vID = sanitizeId($this->params["data"]);
            $data = $this->dataModel->getByVID($vID);
            if (!$data || ($data["accessTypeID"] != $publicID && $data["accessTypeID"] != $sharedID)) response(NULL, 404, "Data not found.");

            if ($data["accessTypeID"] == $publicID) {
                $data["person"] = $this->personModel->getById($data["personID"], "vID, name, nickname");
                $data["subDatas"] = $this->subDataModel->getWhere(["dataID" => $data["id"]], "id, subDataTypeID, accessLevelID, sdKey, sdValue");

                unset($data["id"]);
                unset($data["personID"]);
                response($data);
            }

            if ($data["accessTypeID"] == $sharedID) {
                $dataType = ApiConstants::getWord("entityTypes", "data");
                AuthMiddleware::check();
                if (AuthMiddleware::$person) {
                    $result = $this->model->getWhere(["personID" => AuthMiddleware::$person["id"], "type" => $dataType, "entityID" => $data["id"]])[0];

                    if ($result) {

                        if ($result["isApproved"] == "1") {

                            $data = $this->dataModel->getDataByIdAndAccessLevel($data["id"], $result["accessLevelID"]);

                            response($data);
                        } else if ($result["isApproved"] == 0) {
                            response(["recommendation" => "wait_for_approvision"], 200, "The access request has not yet been approved.");
                        }
                    } else {
                        response(["accessTypeID" => $sharedID, "recommendation" => "craete_access_request"], 200, "create access request.");
                    }
                } else {
                    response(["accessTypeID" => $sharedID, "recommendation" => "login"], 200, "data shared, login and create access request.");
                }
            }
        } else if (!empty($this->params["person"])) {
            $vID = sanitizeId($this->params["person"]);

            $person = $this->personModel->getByVID($vID, "id, vID, name, nickname, accessTypeID");

            if (!$person) response(NULL, 404, "Person not found.");

            if ($person["accessTypeID"] == $publicID) {

                $datas = $this->dataModel->getAllDataByPersonId($person["id"], true, 0);

                unset($person["id"]);
                $data = [
                    "accessTypeID" => $publicID,
                    "person" => $person,
                    "datas" => $datas
                ];
                response($data, 200, "person public");
            }

            if ($person["accessTypeID"] == $sharedID) {
                $personType = ApiConstants::getWord("entityTypes", "person");
                AuthMiddleware::check();
                if (AuthMiddleware::$person) {
                    $result = $this->model->getWhere(["personID" => AuthMiddleware::$person["id"], "type" => $personType, "entityID" => $person["id"]])[0];

                    if ($result) {

                        if ($result["isApproved"] == "1") {

                            if ($result["accessLevelID"] <= 1) $datas_al1 = $this->dataModel->getAllDataByPersonId($person["id"], false, 1);
                            if ($result["accessLevelID"] <= 2) $datas_al2 = $this->dataModel->getAllDataByPersonId($person["id"], false, 2);
                            if ($result["accessLevelID"] <= 3) $datas_al3 = $this->dataModel->getAllDataByPersonId($person["id"], false, 3);
                            if ($result["accessLevelID"] <= 4) $datas_al4 = $this->dataModel->getAllDataByPersonId($person["id"], false, 4);
                            $datas = array_merge($datas_al1 ?? [], $datas_al2 ?? [], $datas_al3 ?? [], $datas_al4 ?? []);

                            unset($person["id"]);
                            $data = [
                                "accessTypeID" => $publicID,
                                "person" => $person,
                                "datas" => $datas
                            ];

                            response($data);
                        } else if ($result["isApproved"] == 0) {
                            response(["recommendation" => "wait_for_approvision"], 200, "The access request has not yet been approved.");
                        }
                    } else {
                        response(["accessTypeID" => $sharedID, "recommendation" => "craete_access_request"], 200, "create access request.");
                    }
                } else {
                    response(["accessTypeID" => $sharedID, "recommendation" => "login"], 200, "person shared, login and create access request.");
                }
            }
        } else {
            response(400);
        }
    }

    public function create() {}

    public function approve() {}
}
