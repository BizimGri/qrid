<?php

require_once __DIR__ . '/MainModel.php';

class ChatModel extends MainModel
{
    protected $table = 'chats';

    function __construct()
    {
        parent::__construct();
    }
}
