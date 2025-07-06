<?php

require_once __DIR__ . '/../Models/CompanyModel.php';
require_once __DIR__ . '/../Models/PersonModel.php';
require_once __DIR__ . '/../Models/VirtualModel.php';
require_once __DIR__ . '/../Middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../Helpers/mailer.php';

class CompanyController extends MainController
{
    private $personModel;
    private $personCompanyModel;

    public function __construct()
    {
        $this->personModel = new PersonModel();
        $this->personCompanyModel = new VirtualModel("personCompany");
        parent::__construct(new CompanyModel());
    }

    public function create()
    {
        $companyCheck = $this->model->getWhere(["createdByPersonId" => AuthMiddleware::$person["id"]]);
        if (!empty($companyCheck)) response($companyCheck[0], 208, "You have already created a company!");

        checkRequiredParams(["name", "fullName", "type", "phone", "email", "address"], $this->params);

        $newCompany = [
            "name" => $this->params["name"],
            "fullName" => $this->params["fullName"],
            "type" => $this->params["type"],
            "phone" => $this->params["phone"],
            "email" => $this->params["email"],
            "address" => $this->params["address"],
            "createdByPersonId" => AuthMiddleware::$person["id"]
        ];
        $result = $this->model->create($newCompany);


        // Send Confirmation Email HERE...


        if ($result) response($result);
        else response(NULL, 500);
    }

    public function update()
    {
        checkRequiredParams(["companyID", "name", "fullName", "type", "phone", "email", "address"], $this->params);

        $company = $this->model->getWhere(["id" => $this->params["companyID"], "createdByPersonId" => AuthMiddleware::$person["id"]]);
        if (empty($company)) response(NULL, 404, "Company not found!");


        // Email Change Operations! will be here...
        if ($this->params["email"] != $company[0]["email"]) response(NULL, 400, "Wait we didn't code this feature yet!");


        $updatedCompany = [
            "name" => $this->params["name"],
            "fullName" => $this->params["fullName"],
            "type" => $this->params["type"],
            "phone" => $this->params["phone"],
            "address" => $this->params["address"],
        ];

        $result = $this->model->update($company[0]["id"], $updatedCompany);
        if ($result) response($result);
        else response(NULL, 500);
    }

    public function sendEmailConfirmationMail() {}

    public function confirmEmail() {}

    public function addMember()
    {
        // admin and moderator can add new member...
    }

    public function addModerator()
    {
        // admin can add new moderator...
    }
}
