<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class AuthMiddleware {
    
    // Verify JWT token
    public static function authenticate($requiredRoles = []) {
        return function($request, $response, $next) use ($requiredRoles) {
            try {
                $headers = getallheaders();
                $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
                
                if (empty($authHeader)) {
                    http_response_code(401);
                    echo json_encode(['message' => 'Access denied. No token provided.']);
                    exit;
                }

                $token = str_replace('Bearer ', '', $authHeader);
                
                // Decode JWT token
                $decoded = self::verifyJWT($token);
                
                if (!$decoded) {
                    http_response_code(401);
                    echo json_encode(['message' => 'Invalid token.']);
                    exit;
                }

                // Get user from database
                $userModel = new User();
                $user = $userModel->findById($decoded->userId);
                
                if (!$user) {
                    http_response_code(401);
                    echo json_encode(['message' => 'Invalid token. User not found.']);
                    exit;
                }

                if (!$user['isActive']) {
                    http_response_code(401);
                    echo json_encode(['message' => 'Account is deactivated.']);
                    exit;
                }

                // Store user in request
                $GLOBALS['user'] = $user;

                // Check role authorization if required
                if (!empty($requiredRoles)) {
                    if (!in_array($user['role'], $requiredRoles)) {
                        http_response_code(403);
                        echo json_encode([
                            'message' => 'Access denied. Required role: ' . implode(' or ', $requiredRoles)
                        ]);
                        exit;
                    }
                }

                // Check merchant approval if user is merchant
                if ($user['role'] === 'merchant' && !$user['approved']) {
                    http_response_code(403);
                    echo json_encode(['message' => 'Merchant account pending approval.']);
                    exit;
                }

                return $next($request, $response);
                
            } catch (Exception $e) {
                http_response_code(401);
                echo json_encode(['message' => 'Invalid token.']);
                exit;
            }
        };
    }

    // Verify JWT token
    private static function verifyJWT($token) {
        $secret = getenv('JWT_SECRET') ?: 'your-secret-key';
        
        $parts = explode('.', $token);
        if (count($parts) != 3) {
            return false;
        }

        $header = base64_decode($parts[0]);
        $payload = base64_decode($parts[1]);
        $signature = base64_decode($parts[2]);

        $validSignature = hash_hmac('sha256', "$parts[0].$parts[1]", $secret, true);
        
        if (!hash_equals($signature, $validSignature)) {
            return false;
        }

        $decoded = json_decode($payload);
        
        // Check expiration
        if (isset($decoded->exp) && $decoded->exp < time()) {
            return false;
        }

        return $decoded;
    }

    // Generate JWT token
    public static function generateJWT($userId, $role) {
        $secret = getenv('JWT_SECRET') ?: 'your-secret-key';
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'userId' => $userId,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24) // 24 hours
        ]);

        $base64Header = base64_encode($header);
        $base64Payload = base64_encode($payload);

        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", $secret, true);
        $base64Signature = base64_encode($signature);

        return "$base64Header.$base64Payload.$base64Signature";
    }
}
?>
