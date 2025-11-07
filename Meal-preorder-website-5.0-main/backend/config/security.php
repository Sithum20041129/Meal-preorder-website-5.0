<?php
class Security {
    
    // Rate limiting implementation
    private static $rateLimitFile = __DIR__ . '/../storage/rate_limits.json';
    
    public static function checkRateLimit($ip) {
        if (APP_ENV === 'development') {
            return true; // Skip rate limiting in development
        }
        
        $now = time();
        $window = RATE_LIMIT_WINDOW;
        $maxRequests = RATE_LIMIT_MAX_REQUESTS;
        
        // Create storage directory if it doesn't exist
        $storageDir = dirname(self::$rateLimitFile);
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        
        // Read existing rate limit data
        $rateData = [];
        if (file_exists(self::$rateLimitFile)) {
            $rateData = json_decode(file_get_contents(self::$rateLimitFile), true) ?: [];
        }
        
        // Clean old entries
        foreach ($rateData as $storedIp => $data) {
            if ($now - $data['firstRequest'] > $window) {
                unset($rateData[$storedIp]);
            }
        }
        
        // Check current IP
        if (!isset($rateData[$ip])) {
            $rateData[$ip] = [
                'count' => 1,
                'firstRequest' => $now,
                'lastRequest' => $now
            ];
        } else {
            $rateData[$ip]['count']++;
            $rateData[$ip]['lastRequest'] = $now;
        }
        
        // Save updated data
        file_put_contents(self::$rateLimitFile, json_encode($rateData));
        
        return $rateData[$ip]['count'] <= $maxRequests;
    }
    
    // Input sanitization
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    // Validate email
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    // Generate CSRF token
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
    
    // Verify CSRF token
    public static function verifyCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        // Token expires after 1 hour
        if (time() - $_SESSION['csrf_token_time'] > 3600) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
?>