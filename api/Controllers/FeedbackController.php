<?php

require_once __DIR__ . '/../Models/VirtualModel.php';

class FeedbackController extends MainController
{
    public function __construct()
    {
        parent::__construct(new VirtualModel("feedback"));
    }

    public function create()
    {
        checkRequiredParams(["text", "url", "version", "type"], $this->params);
        $inputs = [
            "personID" => AuthMiddleware::$person["id"],
            "text" => $this->params["text"],
            "url" => $this->params["url"],
            "version" => $this->params["version"],
            "type" => $this->params["type"]
        ];
        if ($this->params["type"] == "r") {
            $url = explode("/", $this->params["url"]);
            if ($url[1] == "access") {
                if ($url[2] == "data") {
                    $inputs["entityType"] = "d";
                    $data = (new VirtualModel("datas"))->getWhere(["vID" => $url[3]], "id");
                    if (!empty($data[0])) {
                        $inputs["entityId"] = $data[0]["id"];
                    }
                } else if ($url[2] == "profile") {
                    $inputs["entityType"] = "p";
                    $person = (new VirtualModel("persons"))->getWhere(["vID" => $url[3]], "id");
                    if (!empty($person[0])) {
                        $inputs["entityId"] = $person[0]["id"];
                    }
                }
            }
        }
        $result = $this->model->create($inputs, false);
        if ($result) response(200);
        else response(500);
    }
}
