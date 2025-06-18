<?php

require_once __DIR__ . '/../Models/ChatModel.php';
require_once __DIR__ . '/../Models/PersonModel.php';
require_once __DIR__ . '/../Models/DataModel.php';

class ChatController extends MainController
{

    private $personModel;
    private $dataModel;
    public function __construct()
    {
        $this->personModel = new PersonModel();
        $this->dataModel = new DataModel();
        parent::__construct(new ChatModel());
    }

    public function getRoom($roomName)
    {
        if (empty($roomName)) response(NULL, 400);

        $room = $this->model->getWhere(["roomName" => $roomName, "selfID" => AuthMiddleware::$person["id"]]);
        if (!empty($room)) {
            $room[0]["isCaller"] = true;
            $room[0]["other"] = $this->personModel->getById($room[0]["otherID"], "name, vID");
        } else {
            $room = $this->model->getWhere(["roomName" => $roomName, "otherID" => AuthMiddleware::$person["id"]]);
            if (empty($room)) response(NULL, 404);
            else {
                $room[0]["isCaller"] = false;
                $room[0]["other"] = $this->personModel->getById($room[0]["selfID"], "name, vID");
            }
        }

        unset($room[0]["id"]);
        unset($room[0]["selfID"]);
        unset($room[0]["otherID"]);
        response($room[0]);
    }

    public function create()
    {
        checkRequiredParams(["dataVID", "type"], $this->params);

        $self = AuthMiddleware::$person["id"];
        $dataOwner = $this->dataModel->getByVID($this->params["dataVID"], "personID")["personID"];

        if ($self == $dataOwner) response(NULL, 400, "You cannot craete chat with yourself!");

        $room = $this->model->getWhere(["selfID" => $self, "otherID" => $dataOwner, "type" => $this->params["type"]]);
        if (empty($room)) {
            $room = $this->model->create([
                "selfID" => $self,
                "otherID" => $dataOwner,
                "type" => $this->params["type"],
                "roomName" => generateVid(10)
            ]);
        } else $room = $room[0];

        if ($room) response($room);
        else response(NULL, 500);
    }

    public function getLastChats()
    {
        $lastChats_self = $this->model->getWhere(["selfID" => AuthMiddleware::$person["id"]], "roomName, otherID, type", "otherNotifiedTime DESC, selfNotifiedTime DESC", 3);
        $lastChats_other = $this->model->getWhere(["otherID" => AuthMiddleware::$person["id"]], "roomName, selfID, type", "selfNotifiedTime DESC, otherNotifiedTime DESC", 3);

        if (!empty($lastChats_self)) {
            foreach ($lastChats_self as $key => $value) {
                $lastChats_self[$key]["other"] = $this->personModel->getById($value["otherID"], "name");
            }
        }

        if (!empty($lastChats_other)) {
            foreach ($lastChats_other as $key => $value) {
                $lastChats_other[$key]["other"] = $this->personModel->getById($value["selfID"], "name");
            }
        }

        response(["self_created" => $lastChats_self, "other_created" => $lastChats_other]);
    }

    public function notifyOther()
    {
        checkRequiredParams(["roomName"], $this->params);
        $oneHourAgo = new DateTime("-1 hour");
        $tenSecondAgo = new DateTime("-10 second");

        $chat = $this->model->getWhere(["roomName" => $this->params["roomName"]])[0];
        if ($chat["selfID"] == AuthMiddleware::$person["id"]) {
            $other = $this->personModel->getById($chat["otherID"], "name, email, fcmToken");
            $notifiedInOneHour = $chat["otherNotifiedTime"] ? date_create($chat["otherNotifiedTime"]) > $oneHourAgo : false;
            $notifiedInOneMinute = $chat["otherNotifiedTime"] ? date_create($chat["otherNotifiedTime"]) > $tenSecondAgo : false;

            $this->model->update($chat["id"], [
                "otherNotifiedTime" => date(DATE_ATOM)
            ], false);
        } else if ($chat["otherID"] == AuthMiddleware::$person["id"]) {
            $other = $this->personModel->getById($chat["selfID"], "name, email, fcmToken");
            $notifiedInOneHour = $chat["selfNotifiedTime"] ? date_create($chat["selfNotifiedTime"]) > $oneHourAgo : false;
            $notifiedInOneMinute = $chat["selfNotifiedTime"] ? date_create($chat["selfNotifiedTime"]) > $tenSecondAgo : false;

            $this->model->update($chat["id"], [
                "selfNotifiedTime" => date(DATE_ATOM)
            ], false);
        } else response(NULL, 400);

        switch ($chat["type"]) {
            case 'a':
                $chatPath = "audio-chat";
                break;
            case 't':
                $chatPath = "text-chat";
                break;
            case 'v':
            default:
                $chatPath = "chat";
                break;
        }
        
        $subject = "Chat: " . AuthMiddleware::$person["name"] . " is Waiting For You!";
        if (!$notifiedInOneHour) {
            $email = $other["email"];
            $roomLink = "https://qrid.space/{$chatPath}/" . $this->params["roomName"];

            $body = "<h2>Your Chatmate is Waiting For You!</h2>
            <p>Please join the room.</p>
            <p><a href='" . $roomLink . "'>Click here to join Chat with " . AuthMiddleware::$person["name"] . "</a></p>
            <br />
            <p style='color:gray; font-size: 0.9rem;'>If you did not request this, you can safely ignore this email.</p>";

            sendMail($email, $subject, $body, $roomLink, "chat-notify");
        }

        if (!$notifiedInOneMinute) sendNotification($other["fcmToken"], "Chat Action", $subject, "/{$chatPath}/{$this->params["roomName"]}");
    }
}
