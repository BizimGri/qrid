<?php

class AuthMiddleware // JWT işlemleri için düzenlenecek
{
    public function handle()
    {
        // Token doğrulama
        if (!$this->checkAuth()) { // login işleminde de Auth kontrol edilip istek türü login ise kontrol atlanacak.
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    private function checkAuth()
    {
        // Örnek: Authorization Header kontrolü
        $headers = getallheaders();
        if (!isset($headers['Authorization']) || $headers['Authorization'] !== 'Bearer valid_token') {
            return false;
        }
        return true;
    }
}
