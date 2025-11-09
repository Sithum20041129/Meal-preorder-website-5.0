<?php
require_once __DIR__ . '/../utils/jwt.php';

class AuthMiddleware {
    private $jwt_handler;

    public function __construct() {
        $this->jwt_handler = new JWTHandler();
    }

    public function authenticate() {
        $headers = getallheaders();
        $token = null;

        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            $token = str_replace('Bearer ', '', $auth_header);
        }

        if (!$token) {
            http_response_code(401);
            echo json_encode(array("message" => "Access denied. No token provided."));
            exit();
        }

        $user_data = $this->jwt_handler->validateToken($token);
        
        if (!$user_data) {
            http_response_code(401);
            echo json_encode(array("message" => "Invalid token."));
            exit();
        }

        return $user_data;
    }

    public function requireRole($required_role) {
        $user = $this->authenticate();
        
        if ($user['role'] !== $required_role) {
            http_response_code(403);
            echo json_encode(array("message" => "Access denied. Insufficient permissions."));
            exit();
        }

        return $user;
    }

    public function requireRoles($required_roles) {
        $user = $this->authenticate();
        
        if (!in_array($user['role'], $required_roles)) {
            http_response_code(403);
            echo json_encode(array("message" => "Access denied. Insufficient permissions."));
            exit();
        }

        return $user;
    }
}
?>