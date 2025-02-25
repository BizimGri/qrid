<?php

class UserModel
{
    private $users = [
        ['id' => "1", 'name' => 'Alice'],
        ['id' => "2", 'name' => 'Bob'],
        ['id' => "3", 'name' => 'Charlie'],
        ['id' => "4", 'name' => 'David'],
        ['id' => "5", 'name' => 'Eve'],
        ['id' => "6", 'name' => 'Frank'],
        ['id' => "7", 'name' => 'Grace'],
        ['id' => "8", 'name' => 'Hank'],
        ['id' => "9", 'name' => 'Ivy'],
        ['id' => "10", 'name' => 'Jack']
    ];

    public function getAll()
    {
        return $this->users;
    }

    public function getById($id)
    {
        foreach ($this->users as $user) {
            if ($user['id'] == $id) {
                return $user;
            }
        }
        return null;
    }

    public function create($data)
    {
        $this->users[] = $data;
        return "User created successfully!";
    }
}
