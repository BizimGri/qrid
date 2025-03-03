<?php

require_once __DIR__ . '/MainModel.php';
require_once __DIR__ . '/SubDataModel.php';

class DataModel extends MainModel
{
    protected $table = 'datas';
    private $subDataModel;

    function __construct() {
        $this->subDataModel = new SubDataModel();
        parent::__construct();
    }

    public function getAllDataByPersonId($id, $onlyPublic = false)
    {
        $where = ["personID" => $id];

        if($onlyPublic) $where["accessTypeID"] = "3";
        
        $datas = $this->getWhere($where, "id, vID, creationTime, releaseTime, title, isPassive, accessTypeID");

        foreach ($datas as $key => $data) {
            $datas[$key]["subDataCount"] = $this->subDataModel->count(["dataID" => $data["id"]]);
            unset($datas[$key]["id"]);
        }

        return $datas;
    }

}
