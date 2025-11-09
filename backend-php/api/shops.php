<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Shop.php';
require_once __DIR__ . '/../middleware/auth.php';

$database = new Database();
$db = $database->getConnection();
$shop = new Shop($db);
$auth = new AuthMiddleware();

$request_method = $_SERVER["REQUEST_METHOD"];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));

// Extract endpoint and ID
$endpoint = end($path_parts);
$sub_endpoint = isset($path_parts[count($path_parts) - 2]) ? $path_parts[count($path_parts) - 2] : '';

switch($request_method) {
    case 'GET':
        if ($endpoint === 'shops' || $endpoint === '') {
            // Public endpoint - get all shops
            $shops = $shop->getAllShops();
            http_response_code(200);
            echo json_encode($shops);
        }
        elseif ($sub_endpoint === 'merchant' && $endpoint === 'my-shop') {
            $merchant_user = $auth->requireRole('merchant');
            $merchant_shop = $shop->getShopByUserId($merchant_user['id']);
            
            if ($merchant_shop) {
                http_response_code(200);
                echo json_encode($merchant_shop);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Shop not found."));
            }
        }
        break;

    case 'PUT':
        if ($sub_endpoint === 'merchant' && $endpoint === 'settings') {
            $merchant_user = $auth->requireRole('merchant');
            $data = json_decode(file_get_contents("php://input"));
            
            $settings = array(
                'is_open' => $data->is_open ?? false,
                'accepting_orders' => $data->accepting_orders ?? true,
                'closing_time' => $data->closing_time ?? null,
                'order_limit' => $data->order_limit ?? 20
            );
            
            if ($shop->updateSettings($merchant_user['id'], $settings)) {
                http_response_code(200);
                echo json_encode(array("message" => "Shop settings updated successfully."));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to update shop settings."));
            }
        }
        elseif ($sub_endpoint === 'meal-types' && is_numeric($endpoint)) {
            $merchant_user = $auth->requireRole('merchant');
            $data = json_decode(file_get_contents("php://input"));
            $meal_id = $endpoint;
            
            $meal_data = array(
                'name' => $data->name,
                'price' => $data->price,
                'description' => $data->description ?? '',
                'available' => $data->available ?? true
            );
            
            if ($shop->updateMealType($meal_id, $meal_data)) {
                http_response_code(200);
                echo json_encode(array("message" => "Meal type updated successfully."));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to update meal type."));
            }
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed."));
        break;
}
?>