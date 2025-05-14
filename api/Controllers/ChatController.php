<?php

require_once __DIR__ . '/../Models/ChatModel.php';
require_once __DIR__ . '/../Models/PersonModel.php';

class ChatController extends MainController
{

    private $personModel;
    public function __construct()
    {
        $this->personModel = new PersonModel();
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
        checkRequiredParams(["otherVID", "type"], $this->params);

        $self = AuthMiddleware::$person["id"];
        $other = $this->personModel->getByVID($this->params["otherVID"], "id")["id"];

        $room = $this->model->getWhere(["selfID" => $self, "otherID" => $other]);
        if (empty($room)) {
            $room = $this->model->create([
                "selfID" => $self,
                "otherID" => $other,
                "type" => $this->params["type"],
                "roomName" => generateVid(10)
            ]);
        } else $room = $room[0];

        if ($room) response($room);
        else response(NULL, 500);
    }
}
