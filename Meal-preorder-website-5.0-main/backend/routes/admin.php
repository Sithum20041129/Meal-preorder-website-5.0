<?php
class AdminRoutes {
    
    public static function getPendingMerchants() {
        AuthMiddleware::authenticate(['admin'])(null, null, function() {
            try {
                $userModel = new User();
                $pendingMerchants = $userModel->getAllMerchants(false);
                
                echo json_encode(['merchants' => $pendingMerchants]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Server error']);
            }
        });
    }
    
    public static function updateMerchantApproval($merchantId) {
        AuthMiddleware::authenticate(['admin'])(null, null, function() use ($merchantId) {
            try {
                list($errors, $data) = validateRequest([
                    'approved' => 'required|boolean'
                ]);
                
                if (!empty($errors)) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Validation failed', 'errors' => $errors]);
                    return;
                }
                
                $approved = $data['approved'];
                $rejectionReason = $data['rejectionReason'] ?? null;
                
                $userModel = new User();
                $merchant = $userModel->findById($merchantId);
                
                if (!$merchant || $merchant['role'] !== 'merchant') {
                    http_response_code(404);
                    echo json_encode(['message' => 'Merchant not found']);
                    return;
                }
                
                if ($approved) {
                    // Approve merchant
                    $userModel->update($merchantId, [
                        'approved' => true,
                        'isActive' => true
                    ]);
                    
                    // Create shop for approved merchant
                    $shopModel = new Shop();
                    $shopData = [
                        'merchantId' => $merchantId,
                        'name' => $merchant['shopName'],
                        'location' => $merchant['location'],
                        'phone' => $merchant['phone'],
                        'isOpen' => false,
                        'acceptingOrders' => true,
                        'orderLimit' => 50,
                        'mealTypes' => [
                            ['name' => 'Vegetarian Rice', 'price' => 250, 'available' => true],
                            ['name' => 'Chicken Rice', 'price' => 350, 'available' => true],
                            ['name' => 'Fish Rice', 'price' => 400, 'available' => true],
                            ['name' => 'Egg Rice', 'price' => 300, 'available' => true]
                        ],
                        'curries' => [
                            ['name' => 'Dhal Curry', 'available' => true, 'spiceLevel' => 'medium'],
                            ['name' => 'Vegetable Curry', 'available' => true, 'spiceLevel' => 'medium'],
                            ['name' => 'Potato Curry', 'available' => true, 'spiceLevel' => 'medium'],
                            ['name' => 'Chicken Curry', 'available' => true, 'spiceLevel' => 'medium'],
                            ['name' => 'Fish Curry', 'available' => true, 'spiceLevel' => 'medium']
                        ],
                        'customizations' => [
                            ['name' => 'Extra Chicken Piece', 'price' => 100, 'available' => true, 'type' => 'protein'],
                            ['name' => 'Extra Fish Piece', 'price' => 150, 'available' => true, 'type' => 'protein'],
                            ['name' => 'Extra Curry', 'price' => 50, 'available' => true, 'type' => 'curry'],
                            ['name' => 'Extra Rice', 'price' => 30, 'available' => true, 'type' => 'extra']
                        ]
                    ];
                    
                    $shopId = $shopModel->create($shopData);
                    
                    echo json_encode([
                        'message' => 'Merchant approved successfully',
                        'merchant' => $merchant,
                        'shopId' => $shopId
                    ]);
                } else {
                    // Reject merchant
                    $userModel->update($merchantId, [
                        'isActive' => false
                    ]);
                    
                    echo json_encode([
                        'message' => 'Merchant rejected',
                        'merchant' => $merchant
                    ]);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Server error']);
            }
        });
    }
    
    public static function getAdmins() {
        AuthMiddleware::authenticate(['admin'])(null, null, function() {
            try {
                $userModel = new User();
                $admins = [];
                
                // Get all admin users
                $allUsers = $userModel->getAllUsers();
                foreach ($allUsers as $user) {
                    if ($user['role'] === 'admin' && $user['isActive']) {
                        unset($user['password']);
                        $admins[] = $user;
                    }
                }
                
                echo json_encode(['admins' => $admins]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Server error']);
            }
        });
    }
    
    public static function createAdmin() {
        AuthMiddleware::authenticate(['admin'])(null, null, function() {
            try {
                list($errors, $data) = validateRequest([
                    'name' => 'required|min:2',
                    'email' => 'required|email',
                    'password' => 'required|min:6'
                ]);
                
                if (!empty($errors)) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Validation failed', 'errors' => $errors]);
                    return;
                }
                
                $userModel = new User();
                
                // Check if email exists
                $existingUser = $userModel->findByEmail($data['email']);
                if ($existingUser) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Email already exists']);
                    return;
                }
                
                // Create admin
                $adminId = $userModel->create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'role' => 'admin',
                    'approved' => true,
                    'isActive' => true
                ]);
                
                $admin = $userModel->findById($adminId);
                unset($admin['password']);
                
                http_response_code(201);
                echo json_encode([
                    'message' => 'Admin created successfully',
                    'admin' => $admin
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Server error']);
            }
        });
    }
    
    public static function deleteAdmin($adminId) {
        AuthMiddleware::authenticate(['admin'])(null, null, function() use ($adminId) {
            try {
                // Check if trying to delete self
                if ($adminId == $GLOBALS['user']['id']) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Cannot delete your own account']);
                    return;
                }
                
                $userModel = new User();
                
                // Check admin count
                $admins = [];
                $allUsers = $userModel->getAllUsers();
                foreach ($allUsers as $user) {
                    if ($user['role'] === 'admin' && $user['isActive']) {
                        $admins[] = $user;
                    }
                }
                
                if (count($admins) <= 1) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Cannot delete the last admin account']);
                    return;
                }
                
                $admin = $userModel->findById($adminId);
                if (!$admin || $admin['role'] !== 'admin') {
                    http_response_code(404);
                    echo json_encode(['message' => 'Admin not found']);
                    return;
                }
                
                // Soft delete
                $userModel->update($adminId, ['isActive' => false]);
                
                echo json_encode(['message' => 'Admin deleted successfully']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Server error']);
            }
        });
    }
    
    public static function getPlatformStats() {
        AuthMiddleware::authenticate(['admin'])(null, null, function() {
            try {
                $userModel = new User();
                $orderModel = new Order();
                $shopModel = new Shop();
                
                // Get user stats
                $allUsers = $userModel->getAllUsers();
                $totalUsers = 0;
                $totalMerchants = 0;
                $approvedMerchants = 0;
                $pendingMerchants = 0;
                
                foreach ($allUsers as $user) {
                    if ($user['isActive']) {
                        $totalUsers++;
                        if ($user['role'] === 'merchant') {
                            $totalMerchants++;
                            if ($user['approved']) {
                                $approvedMerchants++;
                            } else {
                                $pendingMerchants++;
                            }
                        }
                    }
                }
                
                // Get order stats (simplified - you'd need to implement these methods)
                $totalOrders = 0; // Implement order count method
                $todayOrders = 0; // Implement today's order count
                $totalRevenue = 0; // Implement revenue calculation
                
                echo json_encode([
                    'users' => [
                        'total' => $totalUsers,
                        'merchants' => $totalMerchants,
                        'approved' => $approvedMerchants,
                        'pending' => $pendingMerchants
                    ],
                    'orders' => [
                        'total' => $totalOrders,
                        'today' => $todayOrders
                    ],
                    'revenue' => [
                        'total' => $totalRevenue
                    ]
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Server error']);
            }
        });
    }
}
?>