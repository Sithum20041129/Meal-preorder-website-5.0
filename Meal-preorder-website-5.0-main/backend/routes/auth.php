<?php
class AuthRoutes {
    
    public static function register() {
        try {
            list($errors, $data) = validateRequest([
                'name' => 'required|min:2',
                'email' => 'required|email',
                'password' => 'required|min:6',
                'role' => 'required|in:customer,merchant'
            ]);
            
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode(['message' => 'Validation failed', 'errors' => $errors]);
                return;
            }
            
            // Merchant-specific validations
            if ($data['role'] === 'merchant') {
                if (empty($data['shopName'])) {
                    $errors['shopName'] = 'Shop name is required for merchants';
                }
                if (empty($data['location'])) {
                    $errors['location'] = 'Location is required for merchants';
                }
                if (empty($data['phone'])) {
                    $errors['phone'] = 'Phone is required for merchants';
                }
                
                if (!empty($errors)) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Validation failed', 'errors' => $errors]);
                    return;
                }
            }
            
            $userModel = new User();
            
            // Check if user exists
            $existingUser = $userModel->findByEmail($data['email']);
            if ($existingUser) {
                http_response_code(400);
                echo json_encode(['message' => 'User already exists with this email']);
                return;
            }
            
            // Create user
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
                'isActive' => true
            ];
            
            // Add merchant-specific data
            if ($data['role'] === 'merchant') {
                $userData['shopName'] = $data['shopName'];
                $userData['location'] = $data['location'];
                $userData['phone'] = $data['phone'];
                $userData['approved'] = false; // Merchants need approval
            } else {
                $userData['approved'] = true; // Customers are auto-approved
            }
            
            $userId = $userModel->create($userData);
            $user = $userModel->findById($userId);
            unset($user['password']);
            
            if ($data['role'] !== 'merchant') {
                $token = AuthMiddleware::generateJWT($userId, $data['role']);
                
                http_response_code(201);
                echo json_encode([
                    'message' => 'User registered successfully',
                    'token' => $token,
                    'user' => $user
                ]);
            } else {
                http_response_code(201);
                echo json_encode([
                    'message' => 'Merchant registration submitted. Please wait for admin approval.',
                    'user' => $user
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Server error during registration']);
        }
    }
    
    public static function login() {
        try {
            list($errors, $data) = validateRequest([
                'email' => 'required|email',
                'password' => 'required'
            ]);
            
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode(['message' => 'Validation failed', 'errors' => $errors]);
                return;
            }
            
            $userModel = new User();
            $user = $userModel->findByEmail($data['email']);
            
            if (!$user) {
                http_response_code(401);
                echo json_encode(['message' => 'Invalid credentials']);
                return;
            }
            
            // Check password
            if (!$userModel->verifyPassword($data['password'], $user['password'])) {
                http_response_code(401);
                echo json_encode(['message' => 'Invalid credentials']);
                return;
            }
            
            // Check if account is active
            if (!$user['isActive']) {
                http_response_code(401);
                echo json_encode(['message' => 'Account is deactivated']);
                return;
            }
            
            // Check merchant approval
            if ($user['role'] === 'merchant' && !$user['approved']) {
                http_response_code(401);
                echo json_encode(['message' => 'Merchant account pending approval']);
                return;
            }
            
            // Update last login
            $userModel->updateLastLogin($user['id']);
            
            // Generate token
            $token = AuthMiddleware::generateJWT($user['id'], $user['role']);
            unset($user['password']);
            
            echo json_encode([
                'message' => 'Login successful',
                'token' => $token,
                'user' => $user
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Server error during login']);
        }
    }
    
    public static function getCurrentUser() {
        AuthMiddleware::authenticate()(null, null, function() {
            $user = $GLOBALS['user'];
            unset($user['password']);
            echo json_encode(['user' => $user]);
        });
    }
    
    public static function logout() {
        AuthMiddleware::authenticate()(null, null, function() {
            echo json_encode(['message' => 'Logged out successfully']);
        });
    }
    
    public static function changePassword() {
        AuthMiddleware::authenticate()(null, null, function() {
            try {
                list($errors, $data) = validateRequest([
                    'currentPassword' => 'required',
                    'newPassword' => 'required|min:6'
                ]);
                
                if (!empty($errors)) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Validation failed', 'errors' => $errors]);
                    return;
                }
                
                $userModel = new User();
                $user = $userModel->findById($GLOBALS['user']['id']);
                
                // Verify current password
                if (!$userModel->verifyPassword($data['currentPassword'], $user['password'])) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Current password is incorrect']);
                    return;
                }
                
                // Update password
                $userModel->update($user['id'], ['password' => $data['newPassword']]);
                
                echo json_encode(['message' => 'Password changed successfully']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Server error']);
            }
        });
    }
}
?>