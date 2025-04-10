<?php

require_once __DIR__ . '/MainModel.php';
require_once __DIR__ . '/SubDataModel.php';

class DataModel extends MainModel
{
    protected $table = 'datas';
    private $subDataModel;

    function __construct()
    {
        $this->subDataModel = new SubDataModel();
        parent::__construct();
    }

    public function getAllOwnDataByPersonId($id)
    {
        $where = ["personID" => $id];

        $datas = $this->getWhere($where, "id, vID, creationTime, releaseTime, title, isPassive, accessTypeID, accessLevelID");

        foreach ($datas as $key => $data) {
            $datas[$key]["subDataCount"] = $this->subDataModel->count(["dataID" => $data["id"]]);
            unset($datas[$key]["id"]);
        }

        return $datas;
    }

    public function getAllDataByPersonId($id, $onlyPublic = false, $accessLevelID)
    {
        $where = [
            "personID" => $id,
            "accessLevelID" => $accessLevelID
        ];

        if ($onlyPublic) {
            $where["accessTypeID"] = "3";
            unset($where["accessLevelID"]);
        }

        $datas = $this->getWhere($where, "id, vID, creationTime, releaseTime, title, isPassive, accessTypeID, accessLevelID");

        $count = 0;
        foreach ($datas as $key => $data) {
            if ($accessLevelID <= 1) $count += $this->subDataModel->count(["dataID" => $data["id"], "accessLevelID" => 1]);
            if ($accessLevelID <= 2) $count += $this->subDataModel->count(["dataID" => $data["id"], "accessLevelID" => 2]);
            if ($accessLevelID <= 3) $count += $this->subDataModel->count(["dataID" => $data["id"], "accessLevelID" => 3]);
            if ($accessLevelID <= 4) $count += $this->subDataModel->count(["dataID" => $data["id"], "accessLevelID" => 4]);
            $datas[$key]["subDataCount"] = $count;
            unset($datas[$key]["id"]);
        }

        return $datas;
    }

    public function getDataByIdAndAccessLevel($id, $accessLevelID)
    {
        $where = [
            "id" => $id,
            "accessLevelID" => $accessLevelID
        ];

        $data = $this->getById($id, "id, vID, note, creationTime, releaseTime, title, isPassive, accessTypeID, accessLevelID");

        $subDatas = [];
        if ($accessLevelID <= 1) $subDatas_al1 = $this->subDataModel->getWhere(["dataID" => $data["id"], "accessLevelID" => 1]);
        if ($accessLevelID <= 2) $subDatas_al2 = $this->subDataModel->getWhere(["dataID" => $data["id"], "accessLevelID" => 2]);
        if ($accessLevelID <= 3) $subDatas_al3 = $this->subDataModel->getWhere(["dataID" => $data["id"], "accessLevelID" => 3]);
        if ($accessLevelID <= 4) $subDatas_al4 = $this->subDataModel->getWhere(["dataID" => $data["id"], "accessLevelID" => 4]);
        $subDatas = array_merge($subDatas_al1 ?? [], $subDatas_al2 ?? [], $subDatas_al3 ?? [], $subDatas_al4 ?? []);
        $data["subDatas"] = $subDatas;
        unset($data["id"]);

        return $data;
    }
}
