<?php

class MainController
{
    protected $model;

    public function __construct($model)
    {
        $this->model = $model;
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
