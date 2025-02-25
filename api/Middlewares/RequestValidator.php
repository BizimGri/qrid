<?php

class RequestValidator
{
    public function validate()
    {
        // Sadece JSON kabul ediliyorsa
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
            if ($_SERVER['CONTENT_TYPE'] !== 'application/json') {
                http_response_code(400);
                exit(json_encode(['error' => 'Invalid Content-Type. Only application/json is allowed.']));
            }
        }
    }
}
