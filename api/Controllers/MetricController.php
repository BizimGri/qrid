<?php

require_once __DIR__ . '/../Models/MainModel.php';
require_once __DIR__ . '/../Models/DataModel.php';
require_once __DIR__ . '/../Models/SubDataModel.php';
require_once __DIR__ . '/../Models/PersonModel.php';
require_once __DIR__ . '/../Models/AccessModel.php';

class MetricController extends MainController
{
    private $dataModel;
    private $subDataModel;
    private $personModel;
    private $accessModel;

    public function __construct()
    {
        $this->dataModel = new DataModel();
        $this->subDataModel = new SubDataModel();
        $this->personModel = new PersonModel();
        $this->accessModel = new AccessModel();
    }

    public function getAll()
    {
        $metric = getMetric();
        if ($metric) response($metric);
        else {
            $data = [
                "person" => $this->personModel->count(),
                "data" => $this->dataModel->count(),
                "subData" => $this->subDataModel->count(),
                "accessRequest" => $this->accessModel->count()
            ];
            response(createMetric($data));
        }
    }
}
