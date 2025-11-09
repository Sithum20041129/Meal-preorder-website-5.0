<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Shop.php';
require_once __DIR__ . '/../utils/jwt.php';
require_once __DIR__ . '/../middleware/auth.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$shop = new Shop($db);
$jwt_handler = new JWTHandler();
$auth = new AuthMiddleware();

$request_method = $_SERVER["REQUEST_METHOD"];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));

// Get the endpoint
$endpoint = end($path_parts);

switch($request_method) {
    case 'POST':
        if ($endpoint === 'register') {
            $data = json_decode(file_get_contents("php://input"));

            if (!empty($data->email) && !empty($data->password) && !empty($data->name) && !empty($data->role)) {
                
                // Check if email already exists
                if ($user->findByEmail($data->email)) {
                    http_response_code(400);
                    echo json_encode(array("message" => "Email already exists."));
                    break;
                }

                $user->email = $data->email;
                $user->password = $data->password;
                $user->name = $data->name;
                $user->role = $data->role;
                $user->shop_name = $data->shop_name ?? null;
                $user->location = $data->location ?? null;
                $user->phone = $data->phone ?? null;

                if ($user->create()) {
                    // If merchant is approved or user is not merchant, create shop
                    if ($user->role === 'merchant' && $user->approved) {
                        $shop_data = array(
                            'name' => $data->shop_name ?? $data->name . "'s Shop",
                            'location' => $data->location ?? 'Location not set',
                            'phone' => $data->phone ?? ''
                        );
                        $shop->create($user->id, $shop_data);
                    }

                    http_response_code(201);
                    echo json_encode(array("message" => "User registered successfully."));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Unable to register user."));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Incomplete data."));
            }
        }
        elseif ($endpoint === 'login') {
            $data = json_decode(file_get_contents("php://input"));

            if (!empty($data->email) && !empty($data->password)) {
                if ($user->login($data->email, $data->password)) {
                    $token = $jwt_handler->generateToken(array(
                        'id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->name,
                        'role' => $user->role
                    ));

                    http_response_code(200);
                    echo json_encode(array(
                        "message" => "Login successful.",
                        "token" => $token,
                        "user" => array(
                            'id' => $user->id,
                            'email' => $user->email,
                            'name' => $user->name,
                            'role' => $user->role,
                            'approved' => $user->approved
                        )
                    ));
                } else {
                    http_response_code(401);
                    echo json_encode(array("message" => "Invalid credentials or pending approval."));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Email and password required."));
            }
        }
        break;

    case 'GET':
        if ($endpoint === 'me') {
            $user_data = $auth->authenticate();
            $user_details = $user->findById($user_data['id']);
            
            if ($user_details) {
                http_response_code(200);
                echo json_encode($user_details);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "User not found."));
            }
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed."));
        break;
}
?>