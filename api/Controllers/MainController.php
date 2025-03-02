<?php

class MainController
{
    protected $model;
    protected $params;

    public function __construct($model)
    {
        $this->model = $model;
        $this->params = getRequestParams();
    }

    // public function getById($id)
    // {
    //     $result = $this->model->getById($id);
    //     $result ? response($result) : response(NULL, 404, "Not Found");
    // }
    //
    // public function create($data)
    // {
    //     $data = $this->model->create($data);
    //     if ($data) {
    //         response($data, 201, "Created.");
    //     } else {
    //         response(NULL, 500, "Internal Server Error");
    //     }
    // }

    public function generateUniqueVid($type = "vID")
    {
        $vIDCount = 0;
        do {
            $vID = generateVid();
            if ($vIDCount > 0) createLog("WARNING", "VID ({$type}) collision detected: $vID # vIDCount: $vIDCount");
        } while ($this->model->exists(["vID" => $vID]) && $vIDCount++ < 5);
        
        if ($vIDCount >= 5) {
            createLog("ERROR", "Failed to generate a unique VID for ({$type}) after 5 attempts.");
            response(NULL, 500, "Internal Server Error");
        }

        return $vID;
    }
}
