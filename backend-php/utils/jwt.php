<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHandler {
    private $secret_key = "your-secret-key-change-this-in-production";
    private $issuer = "meal-preorder-platform";
    private $audience = "meal-preorder-users";
    private $issued_at;
    private $not_before;
    private $expire;

    public function __construct() {
        $this->issued_at = time();
        $this->not_before = $this->issued_at;
        $this->expire = $this->issued_at + (7 * 24 * 60 * 60); // 7 days
    }

    public function generateToken($user_data) {
        $token = array(
            "iss" => $this->issuer,
            "aud" => $this->audience,
            "iat" => $this->issued_at,
            "nbf" => $this->not_before,
            "exp" => $this->expire,
            "data" => array(
                "id" => $user_data['id'],
                "email" => $user_data['email'],
                "name" => $user_data['name'],
                "role" => $user_data['role']
            )
        );

        return JWT::encode($token, $this->secret_key, 'HS256');
    }

    public function validateToken($token) {
        try {
            $decoded = JWT::decode($token, new Key($this->secret_key, 'HS256'));
            return (array) $decoded->data;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>