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

    // Bu fonksiyonlar tekrar eden işlemleri kısaltmak için kullanılabilir ya da kaldırılacak!
    public function index()
    {
        response($this->model->getAll());
    }

    public function getById($id)
    {
        $result = $this->model->getById($id);
        $result ? response($result) : response(NULL, 404, "Not Found");
    }

    public function store($data)
    {
        // ...
    }
}
