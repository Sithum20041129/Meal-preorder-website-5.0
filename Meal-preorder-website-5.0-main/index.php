<?php
// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
} else {
    // Fallback for Hostinger (you'll set these in control panel)
    $_ENV['DB_HOST'] = getenv('DB_HOST') ?: 'localhost';
    $_ENV['DB_NAME'] = getenv('DB_NAME') ?: 'your_database_name';
    $_ENV['DB_USERNAME'] = getenv('DB_USERNAME') ?: 'your_username';
    $_ENV['DB_PASSWORD'] = getenv('DB_PASSWORD') ?: 'your_password';
    $_ENV['JWT_SECRET'] = getenv('JWT_SECRET') ?: 'your-secret-key';
    $_ENV['BASE_URL'] = getenv('BASE_URL') ?: 'http://yourdomain.com';
    $_ENV['APP_ENV'] = getenv('APP_ENV') ?: 'production';
}

// Include required files
require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/config/constants.php';
require_once __DIR__ . '/backend/config/security.php';
require_once __DIR__ . '/backend/middleware/auth.php';
require_once __DIR__ . '/backend/models/User.php';
require_once __DIR__ . '/backend/models/Shop.php';
require_once __DIR__ . '/backend/models/Order.php';

// Set headers for API responses
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: " . (in_array($_SERVER['HTTP_ORIGIN'] ?? '', ALLOWED_ORIGINS) ? $_SERVER['HTTP_ORIGIN'] : ALLOWED_ORIGINS[0]));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN");
header("Access-Control-Allow-Credentials: true");

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Apply rate limiting
$clientIP = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!Security::checkRateLimit($clientIP)) {
    http_response_code(429);
    echo json_encode(['message' => 'Too many requests. Please try again later.']);
    exit();
}

// Get request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove base path if exists (for subdirectory installations)
$basePath = '/';
$path = str_replace($basePath, '', $path);

// Helper functions
function getRequestBody() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?: [];
}

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

function sendError($message, $statusCode = 400, $errors = []) {
    $response = ['message' => $message];
    if (!empty($errors)) {
        $response['errors'] = $errors;
    }
    if (APP_ENV === 'development' && $statusCode === 500) {
        $response['debug'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
    }
    sendResponse($response, $statusCode);
}

// Route definitions
$routes = [
    'GET' => [
        'api/health' => 'healthCheck',
        'api/auth/me' => 'getCurrentUser',
        'api/admin/merchants/pending' => 'getPendingMerchants',
        'api/admin/admins' => 'getAdmins',
        'api/admin/stats' => 'getPlatformStats',
        'api/orders/my-orders' => 'getCustomerOrders',
        'api/orders/merchant/orders' => 'getMerchantOrders',
        'api/orders/{id}' => 'getOrderById',
        'api/shops' => 'getAllShops',
        'api/shops/{id}' => 'getShopById',
        'api/shops/merchant/my-shop' => 'getMerchantShop',
        'api/users/profile' => 'getUserProfile',
        'api/users' => 'getAllUsers'
    ],
    'POST' => [
        'api/auth/register' => 'registerUser',
        'api/auth/login' => 'loginUser',
        'api/auth/logout' => 'logoutUser',
        'api/auth/change-password' => 'changePassword',
        'api/admin/admins' => 'createAdmin',
        'api/orders' => 'createOrder',
        'api/shops/reset-daily-orders' => 'resetDailyOrders'
    ],
    'PUT' => [
        'api/admin/merchants/{id}/approval' => 'updateMerchantApproval',
        'api/orders/{id}/status' => 'updateOrderStatus',
        'api/orders/{id}/review' => 'addReview',
        'api/shops/merchant/settings' => 'updateShopSettings',
        'api/shops/merchant/meal-types/{id}' => 'updateMealType',
        'api/shops/merchant/curries/{id}' => 'updateCurry',
        'api/shops/merchant/customizations/{id}' => 'updateCustomization',
        'api/users/profile' => 'updateProfile',
        'api/users/{id}/deactivate' => 'deactivateUser',
        'api/users/{id}/activate' => 'activateUser'
    ],
    'DELETE' => [
        'api/admin/admins/{id}' => 'deleteAdmin'
    ]
];

// Route handler
function handleRoute($route, $params = []) {
    global $method;
    
    try {
        switch ($route) {
            // Health check
            case 'healthCheck':
                sendResponse([
                    'status' => 'OK',
                    'timestamp' => date('c'),
                    'environment' => APP_ENV,
                    'version' => APP_VERSION
                ]);
                break;

            // Auth routes
            case 'registerUser':
                require_once __DIR__ . '/backend/routes/auth.php';
                AuthRoutes::register();
                break;
                
            case 'loginUser':
                require_once __DIR__ . '/backend/routes/auth.php';
                AuthRoutes::login();
                break;
                
            case 'getCurrentUser':
                AuthMiddleware::authenticate()(null, null, function() {
                    $user = $GLOBALS['user'];
                    unset($user['password']);
                    sendResponse(['user' => $user]);
                });
                break;
                
            case 'logoutUser':
                AuthMiddleware::authenticate()(null, null, function() {
                    sendResponse(['message' => 'Logged out successfully']);
                });
                break;
                
            case 'changePassword':
                require_once __DIR__ . '/backend/routes/auth.php';
                AuthRoutes::changePassword();
                break;

            // Order routes
            case 'createOrder':
                require_once __DIR__ . '/backend/routes/orders.php';
                OrderRoutes::createOrder();
                break;
                
            case 'getCustomerOrders':
                require_once __DIR__ . '/backend/routes/orders.php';
                OrderRoutes::getCustomerOrders();
                break;
                
            case 'getMerchantOrders':
                require_once __DIR__ . '/backend/routes/orders.php';
                OrderRoutes::getMerchantOrders();
                break;
                
            case 'getOrderById':
                require_once __DIR__ . '/backend/routes/orders.php';
                OrderRoutes::getOrderById($params[0]);
                break;
                
            case 'updateOrderStatus':
                require_once __DIR__ . '/backend/routes/orders.php';
                OrderRoutes::updateOrderStatus($params[0]);
                break;
                
            case 'addReview':
                require_once __DIR__ . '/backend/routes/orders.php';
                OrderRoutes::addReview($params[0]);
                break;

            // Shop routes
            case 'getAllShops':
                require_once __DIR__ . '/backend/routes/shops.php';
                ShopRoutes::getAllShops();
                break;
                
            case 'getShopById':
                require_once __DIR__ . '/backend/routes/shops.php';
                ShopRoutes::getShopById($params[0]);
                break;
                
            case 'getMerchantShop':
                require_once __DIR__ . '/backend/routes/shops.php';
                ShopRoutes::getMerchantShop();
                break;
                
            case 'updateShopSettings':
                require_once __DIR__ . '/backend/routes/shops.php';
                ShopRoutes::updateShopSettings();
                break;
                
            case 'resetDailyOrders':
                require_once __DIR__ . '/backend/routes/shops.php';
                ShopRoutes::resetDailyOrders();
                break;

            // Admin routes
            case 'getPendingMerchants':
                require_once __DIR__ . '/backend/routes/admin.php';
                AdminRoutes::getPendingMerchants();
                break;
                
            case 'updateMerchantApproval':
                require_once __DIR__ . '/backend/routes/admin.php';
                AdminRoutes::updateMerchantApproval($params[0]);
                break;
                
            case 'getAdmins':
                require_once __DIR__ . '/backend/routes/admin.php';
                AdminRoutes::getAdmins();
                break;
                
            case 'createAdmin':
                require_once __DIR__ . '/backend/routes/admin.php';
                AdminRoutes::createAdmin();
                break;
                
            case 'deleteAdmin':
                require_once __DIR__ . '/backend/routes/admin.php';
                AdminRoutes::deleteAdmin($params[0]);
                break;
                
            case 'getPlatformStats':
                require_once __DIR__ . '/backend/routes/admin.php';
                AdminRoutes::getPlatformStats();
                break;

            // User routes
            case 'getUserProfile':
                require_once __DIR__ . '/backend/routes/users.php';
                UserRoutes::getUserProfile();
                break;
                
            case 'updateProfile':
                require_once __DIR__ . '/backend/routes/users.php';
                UserRoutes::updateProfile();
                break;
                
            case 'getAllUsers':
                require_once __DIR__ . '/backend/routes/users.php';
                UserRoutes::getAllUsers();
                break;
                
            case 'deactivateUser':
                require_once __DIR__ . '/backend/routes/users.php';
                UserRoutes::deactivateUser($params[0]);
                break;
                
            case 'activateUser':
                require_once __DIR__ . '/backend/routes/users.php';
                UserRoutes::activateUser($params[0]);
                break;

            default:
                sendError('Route not found', 404);
        }
    } catch (Exception $e) {
        error_log("Route error: " . $e->getMessage());
        sendError('Internal server error', 500);
    }
}

// Find and execute route
$matched = false;
foreach ($routes[$method] as $route => $handler) {
    $pattern = str_replace('/', '\/', $route);
    $pattern = preg_replace('/\{[^}]+\}/', '([^\/]+)', $pattern);
    
    if (preg_match("/^{$pattern}$/", $path, $matches)) {
        array_shift($matches); // Remove full match
        handleRoute($handler, $matches);
        $matched = true;
        break;
    }
}

// Serve frontend if no API route matched (for SPA)
if (!$matched) {
    // Check if it's a file request (CSS, JS, images)
    $requestedFile = __DIR__ . '/' . $path;
    if ($path && file_exists($requestedFile) && is_file($requestedFile)) {
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon'
        ];
        
        $extension = pathinfo($requestedFile, PATHINFO_EXTENSION);
        if (isset($mimeTypes[$extension])) {
            header('Content-Type: ' . $mimeTypes[$extension]);
        }
        
        readfile($requestedFile);
        exit();
    }
    
    // Serve main HTML file for SPA routing
    if (file_exists(__DIR__ . '/index.html')) {
        header('Content-Type: text/html');
        readfile(__DIR__ . '/index.html');
    } else {
        sendError('Route not found', 404);
    }
}
?>