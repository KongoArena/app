<?php
require_once __DIR__ . '/../config/jwt.php';

class Auth {
    public function verify() {
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            return false;
        }
        
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        $decoded = verifyJWT($token);
        
        return $decoded;
    }
}
?>