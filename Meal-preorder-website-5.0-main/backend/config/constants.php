<?php
// Application Constants
define('APP_NAME', 'Meal Pre-Order Platform');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'production');

// JWT Configuration
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'fallback-secret-key');
define('JWT_EXPIRY', 60 * 60 * 24 * 7); // 7 days

// File Upload Configuration
define('MAX_FILE_SIZE', getenv('MAX_FILE_SIZE') ?: 5242880); // 5MB
define('UPLOAD_PATH', getenv('UPLOAD_PATH') ?: __DIR__ . '/../uploads');

// CORS Configuration
define('ALLOWED_ORIGINS', [
    getenv('FRONTEND_URL') ?: 'http://localhost:5173',
    'http://localhost:5173',
    'https://yourdomain.com'
]);

// Rate Limiting
define('RATE_LIMIT_WINDOW', 15 * 60); // 15 minutes
define('RATE_LIMIT_MAX_REQUESTS', 100);

// Response Codes
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_METHOD_NOT_ALLOWED', 405);
define('HTTP_INTERNAL_ERROR', 500);
?>