<?php
class UserRoutes {
    
    public static function getUserProfile() {
        AuthMiddleware::authenticate()(null, null, function() {
            $user = $GLOBALS['user'];
            unset($user['password']);
            echo json_encode(['user' => $user]);
        });
    }
    
    public static function updateProfile() {
        AuthMiddleware::authenticate()(null, null, function() {
            try {
                list($errors, $data) = validateRequest([]);
                
                $allowedUpdates = ['name', 'phone'];
                if ($GLOBALS['user']['role'] === 'merchant') {
                    $allowedUpdates[] = 'shopName';
                    $allowedUpdates[] = 'location';
                }
                
                $updates = [];
                foreach ($allowedUpdates as $field) {
                    if (isset($data[$field])) {
                        $updates[$field] = $data[$field];
                    }
                }
                
                if (empty($updates)) {
                    http_response_code(400);
                    echo json_encode(['message' => 'No valid fields to update']);
                    return;
                }
                
                $userModel = new User();
                $userModel->update($GLOBALS['user']['id'], $updates);
                
                $updatedUser = $userModel->findById($GLOBALS['user']['id']);
                unset($updatedUser['password']);
                
                echo json_encode([
                    'message' => 'Profile updated successfully',
                    'user' => $updatedUser
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Server error']);
            }
        });
    }
    
    public static function getAllUsers() {
        AuthMiddleware::authenticate(['admin'])(null, null, function() {
            try {
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 20;
                $role = $_GET['role'] ?? null;
                
                $userModel = new User();
                $allUsers = $userModel->getAllUsers();
                
                // Filter by role and active status
                $filteredUsers = [];
                foreach ($allUsers as $user) {
                    if ($user['isActive']) {
                        if (!$role || $user['role'] === $role) {
                            unset($user['password']);
                            $filteredUsers[] = $user;
                        }
                    }
                }
                
                // Simple pagination
                $total = count($filteredUsers);
                $offset = ($page - 1) * $limit;
                $paginatedUsers = array_slice($filteredUsers, $offset, $limit);
                
                echo json_encode([
                    'users' => $paginatedUsers,
                    'pagination' => [
                        'page' => (int)$page,
                        'limit' => (int)$limit,
                        'total' => $total,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Server error']);
            }
        });
    }
    
    public static function deactivateUser($userId) {
        AuthMiddleware::authenticate(['admin'])(null, null, function() use ($userId) {
            try {
                if ($userId == $GLOBALS['user']['id']) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Cannot deactivate your own account']);
                    return;
                }
                
                $userModel = new User();
                $user = $userModel->findById($userId);
                
                if (!$user) {
                    http_response_code(404);
                    echo json_encode(['message' => 'User not found']);
                    return;
                }
                
                $userModel->update($userId, ['isActive' => false]);
                
                echo json_encode(['message' => 'User deactivated successfully']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Server error']);
            }
        });
    }
    
    public static function activateUser($userId) {
        AuthMiddleware::authenticate(['admin'])(null, null, function() use ($userId) {
            try {
                $userModel = new User();
                $user = $userModel->findById($userId);
                
                if (!$user) {
                    http_response_code(404);
                    echo json_encode(['message' => 'User not found']);
                    return;
                }
                
                $userModel->update($userId, ['isActive' => true]);
                
                echo json_encode(['message' => 'User activated successfully']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Server error']);
            }
        });
    }
}
?>