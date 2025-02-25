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

    public function index()
    {
        response($this->model->getAll());
    }

    public function getById($id)
    {
        $this->model->getById($id) ? response($this->model->getById($id)) : response(NULL, 404, "Not Found");
    }

    public function store($data)
    {
        // ...
    }
}
