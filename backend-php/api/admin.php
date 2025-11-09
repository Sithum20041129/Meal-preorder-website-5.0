<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Shop.php';
require_once __DIR__ . '/../middleware/auth.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$shop = new Shop($db);
$auth = new AuthMiddleware();

$request_method = $_SERVER["REQUEST_METHOD"];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));

// Extract endpoint and ID
$endpoint = $path_parts[count($path_parts) - 1];
$sub_endpoint = isset($path_parts[count($path_parts) - 2]) ? $path_parts[count($path_parts) - 2] : '';

switch($request_method) {
    case 'GET':
        $admin_user = $auth->requireRole('admin');
        
        if ($sub_endpoint === 'merchants' && $endpoint === 'pending') {
            $pending_merchants = $user->getPendingMerchants();
            http_response_code(200);
            echo json_encode($pending_merchants);
        }
        elseif ($endpoint === 'admins') {
            $admins = $user->getAllAdmins();
            http_response_code(200);
            echo json_encode($admins);
        }
        elseif ($endpoint === 'stats') {
            // Platform statistics
            $stats_query = "SELECT 
                              (SELECT COUNT(*) FROM users WHERE role = 'merchant' AND approved = TRUE) as approved_merchants,
                              (SELECT COUNT(*) FROM users WHERE role = 'merchant' AND approved = FALSE) as pending_merchants,
                              (SELECT COUNT(*) FROM users WHERE role = 'customer') as total_customers,
                              (SELECT COUNT(*) FROM orders) as total_orders,
                              (SELECT SUM(total) FROM orders WHERE DATE(created_at) = CURDATE()) as today_revenue";
            
            $stmt = $db->prepare($stats_query);
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode($stats);
        }
        break;

    case 'PUT':
        $admin_user = $auth->requireRole('admin');
        
        if ($sub_endpoint === 'merchants' && is_numeric($endpoint)) {
            $data = json_decode(file_get_contents("php://input"));
            $merchant_id = $endpoint;
            
            if (isset($data->approved)) {
                if ($user->approveMerchant($merchant_id, $data->approved)) {
                    // If approved, create shop for merchant
                    if ($data->approved) {
                        $merchant_data = $user->findById($merchant_id);
                        if ($merchant_data) {
                            $shop_data = array(
                                'name' => $merchant_data['shop_name'] ?? $merchant_data['name'] . "'s Shop",
                                'location' => $merchant_data['location'] ?? 'Location not set',
                                'phone' => $merchant_data['phone'] ?? ''
                            );
                            $shop->create($merchant_id, $shop_data);
                        }
                    }
                    
                    http_response_code(200);
                    echo json_encode(array("message" => "Merchant status updated successfully."));
                } else {
                    http_response_code(500);
                    echo json_encode(array("message" => "Failed to update merchant status."));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Approval status required."));
            }
        }
        break;

    case 'POST':
        $admin_user = $auth->requireRole('admin');
        
        if ($endpoint === 'admins') {
            $data = json_decode(file_get_contents("php://input"));
            
            if (!empty($data->email) && !empty($data->password) && !empty($data->name)) {
                // Check if email already exists
                if ($user->findByEmail($data->email)) {
                    http_response_code(400);
                    echo json_encode(array("message" => "Email already exists."));
                    break;
                }

                $user->email = $data->email;
                $user->password = $data->password;
                $user->name = $data->name;
                $user->role = 'admin';
                $user->approved = true;

                if ($user->create()) {
                    http_response_code(201);
                    echo json_encode(array("message" => "Admin created successfully."));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Unable to create admin."));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Name, email and password required."));
            }
        }
        break;

    case 'DELETE':
        $admin_user = $auth->requireRole('admin');
        
        if ($sub_endpoint === 'admins' && is_numeric($endpoint)) {
            $admin_id = $endpoint;
            
            if ($user->deleteAdmin($admin_id)) {
                http_response_code(200);
                echo json_encode(array("message" => "Admin deleted successfully."));
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Cannot delete admin. This might be the last admin account."));
            }
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed."));
        break;
}
?>