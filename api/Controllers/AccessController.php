<?php

require_once __DIR__ . '/../Models/AccessModel.php';
require_once __DIR__ . '/../Models/DataModel.php';
require_once __DIR__ . '/../Models/SubDataModel.php';
require_once __DIR__ . '/../Models/PersonModel.php';
require_once __DIR__ . '/../Middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../Helpers/mailer.php';


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
                    } else if ($result["isApproved"] == -1) {
                        response(["recommendation" => "request_rejected"], 200, "Access request denied.");
                    }
                } else {
                    response(["accessTypeID" => $this->sharedID, "recommendation" => "craete_access_request", "name" => $person["name"]], 200, "create access request.");
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
                    } else if ($result["isApproved"] == -1) {
                        response(["recommendation" => "request_rejected"], 200, "Access request denied.");
                    }
                } else {
                    response(["accessTypeID" => $this->sharedID, "recommendation" => "craete_access_request", "note" => $data["note"]], 200, "create access request.");
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

            if ($access) {
                response($access, 201, "Access request created.", true);
                // Sending email for request
                $dataOwner = $this->personModel->getById($data["personID"], "email, fcmToken");
                $this->requestAccessNotification($dataOwner["email"], AuthMiddleware::$person["name"], $data["title"], $type, $dataOwner["fcmToken"]);
            } else response(NULL, 500, "Access request could not be created.");
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

            if ($access) {
                response($access, 201, "Access request created.", true);
                // Sending email for request
                $this->requestAccessNotification($person["email"], AuthMiddleware::$person["name"], $person["name"], $type, $person["fcmToken"]);
            } else response(NULL, 500, "Access request could not be created.");
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
            $data = $this->dataModel->getById($this->params["entityID"], "personID, title, vID");
            if ($data["personID"] != $personID) {
                response(NULL, 403, "You are not authorized to approve this request.");
            }

            $updatedAccess = [
                "isApproved" => $this->params["isApproved"],
                "accessLevelID" => $this->params["accessLevelID"]
            ];

            $result = $this->model->update($this->params["id"], $updatedAccess);

            if ($result) {
                response($result, 200, "Access request updated.", true);

                if ($this->params["isApproved"] == 1) {
                    // Sending email to data owner for approvision
                    $requestOwner = $this->personModel->getById($accessRequest["personID"], "name, email, fcmToken");
                    $this->accessGrantedNotification(AuthMiddleware::$person["email"], $requestOwner["name"], $data["title"], $type);

                    // Sending email to requester for approvision
                    $path = "/access/data/" . $data["vID"];
                    $this->notifyRequester($requestOwner["email"], $path, AuthMiddleware::$person["name"], $data["title"], $type, $requestOwner["fcmToken"]);
                }
            } else response(NULL, 500, "Internal Server Error.");
        } else if ($type == ApiConstants::getWord("entityTypes", "person")) {
            if ($personID != $this->params["entityID"]) {
                response(NULL, 403, "You are not authorized to approve this request.");
            }

            $updatedAccess = [
                "isApproved" => $this->params["isApproved"],
                "accessLevelID" => $this->params["accessLevelID"]
            ];

            $result = $this->model->update($this->params["id"], $updatedAccess);

            if ($result) {
                response($result, 200, "Access request updated.", true);
                if ($this->params["isApproved"] == 1) {
                    // Sending email to profile owner for approvision
                    $requestOwner = $this->personModel->getById($accessRequest["personID"], "name, email, fcmToken");
                    $this->accessGrantedNotification(AuthMiddleware::$person["email"], $requestOwner["name"], AuthMiddleware::$person["name"], $type);

                    // Sending email to requester for approvision
                    $path = "/access/profile/" . AuthMiddleware::$person["vID"];
                    $this->notifyRequester($requestOwner["email"], $path, AuthMiddleware::$person["name"], "Profile", $type, $requestOwner["fmcToken"]);
                }
            } else response(NULL, 500, "Internal Server Error.");
        } else {
            response(400, "Invalid type.");
        }
    }

    function requestAccessNotification($email, $requesterName, $resourceName, $type, $token)
    {
        $type = $type == "p" ? "Profile" : "Data";
        $subject = "Someone Requested Access to Your QRID {$type}!";
        $subBody = "{$requesterName} has requested access to your {$type}";
        $path = "/access-requests";
        $body = "
            <h2>Access Request Alert</h2>
            <p>{$subBody} : <strong>{$resourceName}</strong>.</p>
            <p>Please review this request and decide whether to grant or deny access.</p>
            <p><a href='https://qrid.space" . $path . "'>Review Requests</a></p>
            <br />
            <p style='color:gray; font-size: 0.9rem;'>If you believe this was a mistake or need assistance, contact us at support@qrid.space.</p>
        ";
        $altBody = "{$requesterName} has requested access to your {$type}: {$resourceName}";
        sendMail($email, $subject, $body, $altBody, "access-request");
        if (!empty($token)) sendNotification($token, $subject, $subBody, $path);
    }

    function accessGrantedNotification($email, $requesterName, $resourceName, $type)
    {
        $type = $type == "p" ? "Profile" : "Data";
        try {
            $mail = createMailer();
            $mail->addAddress($email);
            $mail->Subject = 'You Approved an Access Request';

            $mail->isHTML(true);
            $mail->Body = "
            <h2>Access Request Approved</h2>
            <p>You have successfully approved <strong>{$requesterName}</strong>'s request to access <strong>{$resourceName}</strong>.</p>
            <p>This person now has access to the requested information.</p>
            <br />
            <p style='color:gray; font-size: 0.9rem;'>If you did not intend to approve this access, please contact our support team (support@qrid.space) immediately.</p>
        ";
            $mail->AltBody = "You approved access for {$requesterName} to your {$type}: {$resourceName}";

            $mail->send();
        } catch (Exception $e) {
            response(NULL, 500, "", true);
            error_log("Failed to send access approval email: #" . strtotime('now') . " -> " . $e->getMessage());
        }
    }

    function notifyRequester($email, $path, $ownerName, $resourceName, $type, $token)
    {
        $type = $type == "p" ? "Profile" : "Data";
        $subject = 'Your Access Request Has Been Approved';
        $subBody = "{$ownerName} has approved your request to access the following {$type}";
        $body = "
            <h2>Access Approved</h2>
            <p>{$subBody}: <strong>{$resourceName}</strong>.</p>
            <p>You can now view the {$type} by logging into your QRID account.</p>
            <p><a href='https://qrid.space{$path}'>Go to Shared {$type}!</a></p>
            <br />
            <p style='color:gray; font-size: 0.9rem;'>If you did not request this access, please contact us at support@qrid.space.</p>
        ";
        $altBody = "{$ownerName} approved your request to access: {$resourceName}";
        sendMail($email, $subject, $body, $altBody, "notify-requester");
        if (!empty($token)) sendNotification($token, $subject, $subBody, $path);
    }
}
