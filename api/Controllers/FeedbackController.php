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
        checkRequiredParams(["text"], $this->params);
        $new_feedback = [
            "personID" => AuthMiddleware::$person["id"],
            "text" => $this->params["text"]
        ];
        $result = $this->model->create($new_feedback, false);
        if ($result) response(200);
        else response(500);
    }
}
