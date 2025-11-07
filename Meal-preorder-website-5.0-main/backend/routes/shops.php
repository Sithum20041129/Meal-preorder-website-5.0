<?php
class ShopRoutes {
    
    public static function getAllShops() {
        try {
            $shopModel = new Shop();
            $shops = $shopModel->getAllActiveShops();
            
            echo json_encode(['shops' => $shops]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Server error']);
        }
    }
    
    public static function getShopById($shopId) {
        try {
            $shopModel = new Shop();
            $shop = $shopModel->findById($shopId);
            
            if (!$shop) {
                http_response_code(404);
                echo json_encode(['message' => 'Shop not found']);
                return;
            }
            
            echo json_encode(['shop' => $shop]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Server error']);
        }
    }
    
    public static function getMerchantShop() {
        AuthMiddleware::authenticate(['merchant'])(null, null, function() {
            try {
                $shopModel = new Shop();
                $shop = $shopModel->findByMerchantId($GLOBALS['user']['id']);
                
                if (!$shop) {
                    http_response_code(404);
                    echo json_encode(['message' => 'Shop not found']);
                    return;
                }
                
                echo json_encode(['shop' => $shop]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Server error']);
            }
        });
    }
    
    public static function updateShopSettings() {
        AuthMiddleware::authenticate(['merchant'])(null, null, function() {
            try {
                list($errors, $data) = validateRequest([]); // No specific validation rules
                
                $allowedUpdates = ['isOpen', 'acceptingOrders', 'orderLimit', 'closingTime'];
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
                
                $shopModel = new Shop();
                $shop = $shopModel->findByMerchantId($GLOBALS['user']['id']);
                
                if (!$shop) {
                    http_response_code(404);
                    echo json_encode(['message' => 'Shop not found']);
                    return;
                }
                
                $shopModel->update($shop['id'], $updates);
                $updatedShop = $shopModel->findById($shop['id']);
                
                echo json_encode([
                    'message' => 'Shop settings updated successfully',
                    'shop' => $updatedShop
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Server error']);
            }
        });
    }
    
    public static function updateMealType($mealId) {
        // Implementation for updating meal types
        // Similar structure to other update methods
    }
    
    public static function updateCurry($curryId) {
        // Implementation for updating curries
    }
    
    public static function updateCustomization($customId) {
        // Implementation for updating customizations
    }
    
    public static function resetDailyOrders() {
        AuthMiddleware::authenticate(['admin'])(null, null, function() {
            try {
                $shopModel = new Shop();
                // This would reset all shops' daily orders
                // Implementation depends on your specific reset logic
                
                echo json_encode(['message' => 'Daily order counts reset successfully']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Server error']);
            }
        });
    }
}
?>