<?php

require_once __DIR__ . '/../Models/AccessModel.php';
require_once __DIR__ . '/../Models/DataModel.php';
require_once __DIR__ . '/../Models/DataModel.php';
require_once __DIR__ . '/../Models/PersonModel.php';
require_once __DIR__ . '/../Middlewares/AuthMiddleware.php';

class AccessController extends MainController
{
    private $dataModel;
    private $subDataModel;
    private $personModel;

    public function __construct()
    {
        $this->dataModel = new DataModel();
        $this->subDataModel = new SubDataModel();
        $this->personModel = new PersonModel();
        parent::__construct(new AccessModel());
    }

    public function get() {
        $publicID = ApiConstants::getWord("accessTypes", "public");
        $sharedID = ApiConstants::getWord("accessTypes", "shared");

        if(!empty($this->params["data"])){
            $vID = sanitizeId($this->params["data"]);
            $dataPublic = $this->dataModel->exists(["vID" => $vID, "accessTypeID" => $publicID]);
            if ($dataPublic) {
                $data = $this->dataModel->getByVID($vID);
                $data["person"] = $this->personModel->getById($data["personID"], "vID, name, nickname");
                $data["subDatas"] = $this->subDataModel->getWhere(["dataID" => $data["id"]], "id, subDataTypeID, accessLevelID, sdKey, sdValue");

                unset($data["id"]);
                unset($data["personID"]);
                response($data);
            }

            $dataShared = $this->dataModel->exists(["vID" => $vID, "accessTypeID" => $sharedID]);
            if ($dataShared) {
                
            }

            response(NULL, 404, "Data not found.");
        } else if (!empty($this->params["person"])){
            $vID = sanitizeId($this->params["person"]);

            $person = $this->personModel->getByVID($vID, "id, vID, name, nickname, accessTypeID");

            if($person["accessTypeID"] == $publicID){

                $datas = $this->dataModel->getAllDataByPersonId($person["id"], true);
                
                unset($person["id"]);
                $data = [
                    "accessTypeID" => $publicID,
                    "person" => $person,
                    "datas" => $datas
                ];
                response($data, 200, "person public");
            }

            if ($person["accessTypeID"] == $sharedID) {
                AuthMiddleware::check();
                if(AuthMiddleware::$person){
                    $result = $this->model->getWhere(["personID" => AuthMiddleware::$person["id"], "type" => "p", "entityID" => $person["id"]])[0];


                    if ($result) {
                        if ($result["isApproved"] == "1") {

                            // Access Level ID bakılarak daha genel seviyede verilere erişilmesi sağlanacak!!! $result["accessLevelID"] !!!

                            /*
                                "accessLevelID" konusunu sadece "subData" için değil "data" için de geçerli olacak.
                                datas.accessLevelID tanımlandı.
                            */

                            $datas = $this->dataModel->getAllDataByPersonId($person["id"]);

                            unset($person["id"]);
                            $data = [
                                "accessTypeID" => $publicID,
                                "person" => $person,
                                "datas" => $datas
                            ];

                            response($data);
                        } else {
                            response(NULL, 401, "The access request has not yet been approved.");
                        }
                        
                        response(NULL, 200);
                    } else {
                        response(NULL, 404);
                    }
                } else {
                    response(["accessTypeID" => $sharedID], 200, "person shared, login and create access request.");
                }
            }

            response(NULL, 404, "Person not found.");
        } else {
            response(400);
        }

    }

    public function create() {
        
    }

    public function approve() {
        
    }

}