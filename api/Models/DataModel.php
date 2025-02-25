<?php

class DataModel
{
    private $data = [
        ['id' => 1, 'value' => 'item1'],
        ['id' => 2, 'value' => 'item2']
    ];

    public function getAll()
    {
        return $this->data;
    }

    public function getById($id)
    {
        foreach ($this->data as $item) {
            if ($item['id'] == $id) {
                return $item;
            }
        }
        return null;
    }

    public function create($data)
    {
        $this->data[] = $data;
        return "Data created successfully!";
    }
}
