<?php
class OrderRoutes {
    
    public static function createOrder() {
        AuthMiddleware::authenticate(['customer'])(null, null, function() {
            try {
                list($errors, $data) = validateRequest([
                    'shopId' => 'required',
                    'items' => 'required|array',
                    'total' => 'required'
                ]);
                
                if (!empty($errors)) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Validation failed', 'errors' => $errors]);
                    return;
                }
                
                // Validate items
                if (empty($data['items']) || !is_array($data['items'])) {
                    http_response_code(400);
                    echo json_encode(['message' => 'At least one item is required']);
                    return;
                }
                
                foreach ($data['items'] as $item) {
                    if (empty($item['mealType'])) {
                        http_response_code(400);
                        echo json_encode(['message' => 'Meal type is required']);
                        return;
                    }
                    if (empty($item['curries']) || !is_array($item['curries']) || count($item['curries']) < 1 || count($item['curries']) > 3) {
                        http_response_code(400);
                        echo json_encode(['message' => '1-3 curries required']);
                        return;
                    }
                    if (empty($item['subtotal']) || $item['subtotal'] < 0) {
                        http_response_code(400);
                        echo json_encode(['message' => 'Valid subtotal required']);
                        return;
                    }
                }
                
                $shopModel = new Shop();
                $shop = $shopModel->findById($data['shopId']);
                
                if (!$shop) {
                    http_response_code(404);
                    echo json_encode(['message' => 'Shop not found']);
                    return;
                }
                
                if (!$shop['isOpen']) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Shop is currently closed']);
                    return;
                }
                
                if (!$shop['acceptingOrders']) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Shop is not accepting orders at the moment']);
                    return;
                }
                
                if ($shop['orderLimit'] && $shop['ordersReceived'] >= $shop['orderLimit']) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Shop has reached its daily order limit']);
                    return;
                }
                
                // Create order
                $orderModel = new Order();
                $orderData = [
                    'customerId' => $GLOBALS['user']['id'],
                    'customerName' => $GLOBALS['user']['name'],
                    'customerPhone' => $GLOBALS['user']['phone'],
                    'merchantId' => $shop['merchantId'],
                    'shopId' => $shop['id'],
                    'merchantName' => $shop['name'],
                    'items' => $data['items'],
                    'total' => $data['total'],
                    'status' => 'pending',
                    'paymentStatus' => 'pending',
                    'paymentMethod' => $data['paymentMethod'] ?? 'cash',
                    'notes' => $data['notes'] ?? null
                ];
                
                $orderId = $orderModel->create($orderData);
                
                // Update shop order count
                $shopModel->update($shop['id'], [
                    'ordersReceived' => $shop['ordersReceived'] + 1,
                    'totalOrders' => $shop['totalOrders'] + 1
                ]);
                
                $order = $orderModel->findById($orderId);
                
                http_response_code(201);
                echo json_encode([
                    'message' => 'Order placed successfully',
                    'order' => $order
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Server error']);
            }
        });
    }
    
    public static function getCustomerOrders() {
        AuthMiddleware::authenticate(['customer'])(null, null, function() {
            try {
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 10;
                
                $orderModel = new Order();
                $orders = $orderModel->getCustomerOrders($GLOBALS['user']['id']);
                
                // Simple pagination (you might want to implement proper pagination)
                $total = count($orders);
                $offset = ($page - 1) * $limit;
                $paginatedOrders = array_slice($orders, $offset, $limit);
                
                echo json_encode([
                    'orders' => $paginatedOrders,
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
    
    public static function getMerchantOrders() {
        AuthMiddleware::authenticate(['merchant'])(null, null, function() {
            try {
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 20;
                $status = $_GET['status'] ?? null;
                
                $orderModel = new Order();
                $orders = $orderModel->getShopOrders($GLOBALS['user']['id'], $status);
                
                // Simple pagination
                $total = count($orders);
                $offset = ($page - 1) * $limit;
                $paginatedOrders = array_slice($orders, $offset, $limit);
                
                echo json_encode([
                    'orders' => $paginatedOrders,
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
    
    public static function updateOrderStatus($orderId) {
        AuthMiddleware::authenticate(['merchant'])(null, null, function() use ($orderId) {
            try {
                list($errors, $data) = validateRequest([
                    'status' => 'required|in:confirmed,preparing,ready,completed,cancelled'
                ]);
                
                if (!empty($errors)) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Validation failed', 'errors' => $errors]);
                    return;
                }
                
                $status = $data['status'];
                $estimatedPickupTime = $data['estimatedPickupTime'] ?? null;
                $cancellationReason = $data['cancellationReason'] ?? null;
                
                $orderModel = new Order();
                $order = $orderModel->findById($orderId);
                
                if (!$order || $order['merchantId'] != $GLOBALS['user']['id']) {
                    http_response_code(404);
                    echo json_encode(['message' => 'Order not found']);
                    return;
                }
                
                // Validate status transition
                $validTransitions = [
                    'pending' => ['confirmed', 'cancelled'],
                    'confirmed' => ['preparing', 'cancelled'],
                    'preparing' => ['ready', 'cancelled'],
                    'ready' => ['completed'],
                    'completed' => [],
                    'cancelled' => []
                ];
                
                if (!in_array($status, $validTransitions[$order['status']])) {
                    http_response_code(400);
                    echo json_encode(['message' => "Cannot change status from {$order['status']} to $status"]);
                    return;
                }
                
                $orderModel->updateStatus($orderId, $status, $cancellationReason);
                
                // Update shop revenue when completed
                if ($status === 'completed') {
                    $shopModel = new Shop();
                    $shop = $shopModel->findById($order['shopId']);
                    if ($shop) {
                        $shopModel->update($shop['id'], [
                            'totalRevenue' => $shop['totalRevenue'] + $order['total']
                        ]);
                    }
                }
                
                $updatedOrder = $orderModel->findById($orderId);
                
                echo json_encode([
                    'message' => 'Order status updated successfully',
                    'order' => $updatedOrder
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Server error']);
            }
        });
    }
    
    public static function getOrderById($orderId) {
        AuthMiddleware::authenticate()(null, null, function() use ($orderId) {
            try {
                $orderModel = new Order();
                $order = $orderModel->findById($orderId);
                
                if (!$order) {
                    http_response_code(404);
                    echo json_encode(['message' => 'Order not found']);
                    return;
                }
                
                // Check permissions
                $user = $GLOBALS['user'];
                if ($user['role'] === 'customer' && $order['customerId'] != $user['id']) {
                    http_response_code(403);
                    echo json_encode(['message' => 'Access denied']);
                    return;
                }
                
                if ($user['role'] === 'merchant' && $order['merchantId'] != $user['id']) {
                    http_response_code(403);
                    echo json_encode(['message' => 'Access denied']);
                    return;
                }
                
                echo json_encode(['order' => $order]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Server error']);
            }
        });
    }
    
    public static function addReview($orderId) {
        AuthMiddleware::authenticate(['customer'])(null, null, function() use ($orderId) {
            try {
                list($errors, $data) = validateRequest([
                    'rating' => 'required|min:1|max:5',
                    'review' => 'optional'
                ]);
                
                if (!empty($errors)) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Validation failed', 'errors' => $errors]);
                    return;
                }
                
                $orderModel = new Order();
                $order = $orderModel->findById($orderId);
                
                if (!$order || $order['customerId'] != $GLOBALS['user']['id']) {
                    http_response_code(404);
                    echo json_encode(['message' => 'Order not found']);
                    return;
                }
                
                if ($order['status'] !== 'completed') {
                    http_response_code(400);
                    echo json_encode(['message' => 'Order not completed']);
                    return;
                }
                
                if ($order['rating']) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Order already reviewed']);
                    return;
                }
                
                $orderModel->addRating($orderId, $data['rating'], $data['review'] ?? null);
                
                // Update shop rating
                $shopModel = new Shop();
                $shop = $shopModel->findById($order['shopId']);
                if ($shop) {
                    $totalRating = ($shop['rating'] * $shop['reviewCount']) + $data['rating'];
                    $newReviewCount = $shop['reviewCount'] + 1;
                    $newRating = $totalRating / $newReviewCount;
                    
                    $shopModel->update($shop['id'], [
                        'rating' => $newRating,
                        'reviewCount' => $newReviewCount
                    ]);
                }
                
                $updatedOrder = $orderModel->findById($orderId);
                
                echo json_encode([
                    'message' => 'Review added successfully',
                    'order' => $updatedOrder
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Server error']);
            }
        });
    }
}
?>