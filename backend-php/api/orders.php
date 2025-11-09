<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Shop.php';
require_once __DIR__ . '/../middleware/auth.php';

$database = new Database();
$db = $database->getConnection();
$order = new Order($db);
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
    case 'POST':
        if ($endpoint === 'orders' || $endpoint === '') {
            $customer_user = $auth->requireRole('customer');
            $data = json_decode(file_get_contents("php://input"));
            
            if (!empty($data->merchant_id) && !empty($data->items) && !empty($data->total)) {
                $order_data = array(
                    'customer_id' => $customer_user['id'],
                    'customer_name' => $customer_user['name'],
                    'merchant_id' => $data->merchant_id,
                    'merchant_name' => $data->merchant_name,
                    'items' => $data->items,
                    'total' => $data->total
                );
                
                $order_number = $order->create($order_data);
                
                if ($order_number) {
                    // Increment shop order count
                    $shop->incrementOrderCount($data->merchant_id);
                    
                    http_response_code(201);
                    echo json_encode(array(
                        "message" => "Order placed successfully.",
                        "order_number" => $order_number
                    ));
                } else {
                    http_response_code(500);
                    echo json_encode(array("message" => "Failed to place order."));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Incomplete order data."));
            }
        }
        break;

    case 'GET':
        if ($endpoint === 'my-orders') {
            $customer_user = $auth->requireRole('customer');
            $customer_orders = $order->getCustomerOrders($customer_user['id']);
            
            http_response_code(200);
            echo json_encode($customer_orders);
        }
        elseif ($sub_endpoint === 'merchant' && $endpoint === 'orders') {
            $merchant_user = $auth->requireRole('merchant');
            $merchant_orders = $order->getMerchantOrders($merchant_user['id']);
            
            http_response_code(200);
            echo json_encode($merchant_orders);
        }
        elseif (is_numeric($endpoint)) {
            $user_data = $auth->authenticate();
            $order_details = $order->getOrderById($endpoint);
            
            if ($order_details) {
                // Check if user has permission to view this order
                if ($user_data['role'] === 'admin' || 
                    $order_details['customer_id'] == $user_data['id'] || 
                    $order_details['merchant_id'] == $user_data['id']) {
                    
                    http_response_code(200);
                    echo json_encode($order_details);
                } else {
                    http_response_code(403);
                    echo json_encode(array("message" => "Access denied."));
                }
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Order not found."));
            }
        }
        break;

    case 'PUT':
        if (is_numeric($endpoint)) {
            $merchant_user = $auth->requireRoles(['merchant', 'admin']);
            $data = json_decode(file_get_contents("php://input"));
            $order_id = $endpoint;
            
            if (!empty($data->status)) {
                $valid_statuses = ['pending', 'preparing', 'ready', 'completed'];
                
                if (in_array($data->status, $valid_statuses)) {
                    if ($order->updateStatus($order_id, $data->status)) {
                        http_response_code(200);
                        echo json_encode(array("message" => "Order status updated successfully."));
                    } else {
                        http_response_code(500);
                        echo json_encode(array("message" => "Failed to update order status."));
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(array("message" => "Invalid status."));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Status required."));
            }
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed."));
        break;
}
?>