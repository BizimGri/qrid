<?php

require_once __DIR__ . '/../Models/AccessModel.php';
require_once __DIR__ . '/../Models/DataModel.php';
require_once __DIR__ . '/../Models/SubDataModel.php';
require_once __DIR__ . '/../Models/PersonModel.php';
require_once __DIR__ . '/../Middlewares/AuthMiddleware.php';

class AccessController extends MainController
{
    private $dataModel;
    private $subDataModel;
    private $personModel;
    private $privateID;
    private $sharedID;
    private $publicID;

    public function __construct()
    {
        $this->dataModel = new DataModel();
        $this->subDataModel = new SubDataModel();
        $this->personModel = new PersonModel();
        $this->privateID = ApiConstants::getWord("accessTypes", "private");
        $this->sharedID = ApiConstants::getWord("accessTypes", "shared");
        $this->publicID = ApiConstants::getWord("accessTypes", "public");
        parent::__construct(new AccessModel());
    }

    public function getPerson($vID)
    {
        $person = $this->personModel->getByVID($vID, "id, vID, name, nickname, accessTypeID");

        if (!$person || $person["accessTypeID"] == $this->privateID) response(NULL, 404, "Person not found.");

        if ($person["accessTypeID"] == $this->publicID) {

            $datas = $this->dataModel->getAllDataByPersonId($person["id"], true, 0);

            unset($person["id"]);
            $data = [
                "accessTypeID" => $this->publicID,
                "person" => $person,
                "datas" => $datas
            ];
            response($data, 200, "person public");
        }

        if ($person["accessTypeID"] == $this->sharedID) {
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
                            "accessTypeID" => $this->publicID,
                            "person" => $person,
                            "datas" => $datas
                        ];

                        response($data);
                    } else if ($result["isApproved"] == 0) {
                        response(["recommendation" => "wait_for_approvision"], 200, "The access request has not yet been approved.");
                    }
                } else {
                    response(["accessTypeID" => $this->sharedID, "recommendation" => "craete_access_request"], 200, "create access request.");
                }
            } else {
                response(["accessTypeID" => $this->sharedID, "recommendation" => "login"], 200, "person shared, login and create access request.");
            }
        }
    }

    public function getData($vID)
    {
        $data = $this->dataModel->getByVID($vID);
        if (!$data || ($data["accessTypeID"] != $this->publicID && $data["accessTypeID"] != $this->sharedID)) response(NULL, 404, "Data not found.");

        if ($data["accessTypeID"] == $this->publicID) {
            $data["person"] = $this->personModel->getById($data["personID"], "vID, name, nickname");
            $data["subDatas"] = $this->subDataModel->getWhere(["dataID" => $data["id"]], "id, subDataTypeID, accessLevelID, sdKey, sdValue");

            unset($data["id"]);
            unset($data["personID"]);
            response($data);
        }

        if ($data["accessTypeID"] == $this->sharedID) {
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
                    response(["accessTypeID" => $this->sharedID, "recommendation" => "craete_access_request"], 200, "create access request.");
                }
            } else {
                response(["accessTypeID" => $this->sharedID, "recommendation" => "login"], 200, "data shared, login and create access request.");
            }
        }
    }

    public function getAll()
    {
        $personID = AuthMiddleware::$person["id"];
        $dataIDs = $this->dataModel->getWhere(["personID" => $personID], "id");

        $accessPersonRequests = $this->model->getWhere(["type" => "p", "entityID" => $personID, "isApproved" => 0]);
        $accessDataRequests = [];
        foreach ($dataIDs as $data) {
            $requests = $this->model->getWhere(["type" => "d", "entityID" => $data["id"], "isApproved" => 0]);
            if (!empty($requests)) {
                foreach ($requests as $request) {
                    $accessDataRequests[] = $request;
                }
            }
        }

        foreach ($accessPersonRequests as $key => $request) {
            $accessPersonRequests[$key]["person"] = $this->personModel->getById($request["personID"], "vID, name, nickname");
        }

        foreach ($accessDataRequests as $key => $request) {
            $accessDataRequests[$key]["person"] = $this->personModel->getById($request["personID"], "vID, name, nickname");
            $accessDataRequests[$key]["data"] = $this->dataModel->getById($request["entityID"], "vID, title, note, accessLevelID, accessTypeID, isPassive, creationTime");
        }

        response(["person" => $accessPersonRequests, "data" => $accessDataRequests]);
    }

    public function create()
    {
        checkRequiredParams(["type", "vID"], $this->params);
        $vID = sanitizeId($this->params["vID"]);
        $type = $this->params["type"];

        if ($type == ApiConstants::getWord("entityTypes", "data")) {
            $data = $this->dataModel->getByVID($vID);
            if (!$data) response(NULL, 404, "Data not found.");

            $accessCheck = $this->model->exists(["personID" => AuthMiddleware::$person["id"], "type" => $type, "entityID" => $data["id"]]);
            if ($accessCheck) response(NULL, 400, "Access request already exists.");

            $accessData = [
                "personID" => AuthMiddleware::$person["id"],
                "type" => $type,
                "entityID" => $data["id"],
                "isApproved" => 0
            ];

            $access = $this->model->create($accessData);
            if ($access) response($access, 201, "Access request created.");
            else response(NULL, 500, "Access request could not be created.");
        } else if ($type == ApiConstants::getWord("entityTypes", "person")) {
            $person = $this->personModel->getByVID($vID);
            if (!$person) response(NULL, 404, "Person not found.");

            $accessCheck = $this->model->exists(["personID" => AuthMiddleware::$person["id"], "type" => $type, "entityID" => $person["id"]]);
            if ($accessCheck) response(NULL, 400, "Access request already exists.");

            $accessData = [
                "personID" => AuthMiddleware::$person["id"],
                "type" => $type,
                "entityID" => $person["id"],
                "isApproved" => 0
            ];

            $access = $this->model->create($accessData);
            if ($access) response($access, 201, "Access request created.");
            else response(NULL, 500, "Access request could not be created.");
        } else {
            response(400, "Invalid type.");
        }
    }

    public function approve()
    {
        checkRequiredParams(["id", "isApproved", "accessLevelID", "type", "entityID"], $this->params);
        $personID = AuthMiddleware::$person["id"];
        $type = $this->params["type"];
        $accessRequest = $this->model->getWhere(["id" => $this->params["id"], "entityID" => $this->params["entityID"], "type" => $type])[0];
        if (!$accessRequest) response(NULL, 404, "Access request not found.");

        if ($type == ApiConstants::getWord("entityTypes", "data")) {
            $data = $this->dataModel->getById($this->params["entityID"], "personID");
            if ($data["personID"] != $personID) {
                response(NULL, 403, "You are not authorized to approve this request.");
            }

            $updatedAccess = [
                "isApproved" => $this->params["isApproved"],
                "accessLevelID" => $this->params["accessLevelID"]
            ];

            $result = $this->model->update($this->params["id"], $updatedAccess);
            if ($result) response($result, 200, "Access request updated.");
            else response(NULL, 500, "Internal Server Error.");
        } else if ($type == ApiConstants::getWord("entityTypes", "person")) {
            if ($personID != $this->params["entityID"]) {
                response(NULL, 403, "You are not authorized to approve this request.");
            }

            $updatedAccess = [
                "isApproved" => $this->params["isApproved"],
                "accessLevelID" => $this->params["accessLevelID"]
            ];

            $result = $this->model->update($this->params["id"], $updatedAccess);
            if ($result) response($result, 200, "Access request updated.");
            else response(NULL, 500, "Internal Server Error.");
        } else {
            response(400, "Invalid type.");
        }
    }
}
