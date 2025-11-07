<?php
class Order {
    private $conn;
    private $table_name = "orders";

    public $id;
    public $orderNumber;
    public $customerId;
    public $customerName;
    public $customerPhone;
    public $merchantId;
    public $shopId;
    public $merchantName;
    public $total;
    public $status;
    public $paymentStatus;
    public $paymentMethod;
    public $confirmedAt;
    public $preparingAt;
    public $readyAt;
    public $completedAt;
    public $cancelledAt;
    public $estimatedPickupTime;
    public $notes;
    public $rating;
    public $review;
    public $cancellationReason;
    public $createdAt;
    public $updatedAt;

    // Order items
    public $items = [];

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function findById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->loadOrderItems($order);
            return $order;
        }
        return false;
    }

    public function findByOrderNumber($orderNumber) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE orderNumber = :orderNumber";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":orderNumber", $orderNumber);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->loadOrderItems($order);
            return $order;
        }
        return false;
    }

    public function getCustomerOrders($customerId) {
        $query = "SELECT o.*, s.name as shopName 
                  FROM " . $this->table_name . " o
                  JOIN shops s ON o.shopId = s.id
                  WHERE o.customerId = :customerId 
                  ORDER BY o.createdAt DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":customerId", $customerId);
        $stmt->execute();

        $orders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->loadOrderItems($row);
            $orders[] = $row;
        }
        
        return $orders;
    }

    public function getShopOrders($shopId, $status = null) {
        $query = "SELECT o.*, u.name as customerName 
                  FROM " . $this->table_name . " o
                  JOIN users u ON o.customerId = u.id
                  WHERE o.shopId = :shopId";
        
        if ($status) {
            $query .= " AND o.status = :status";
        }
        
        $query .= " ORDER BY o.createdAt DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":shopId", $shopId);
        
        if ($status) {
            $stmt->bindParam(":status", $status);
        }
        
        $stmt->execute();

        $orders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->loadOrderItems($row);
            $orders[] = $row;
        }
        
        return $orders;
    }

    private function loadOrderItems(&$order) {
        $orderId = $order['id'];
        
        // Load order items
        $itemsQuery = "SELECT * FROM order_items WHERE orderId = :orderId";
        $itemsStmt = $this->conn->prepare($itemsQuery);
        $itemsStmt->bindParam(":orderId", $orderId);
        $itemsStmt->execute();
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Load curries and customizations for each item
        foreach ($items as &$item) {
            // Load curries
            $curriesQuery = "SELECT * FROM order_item_curries WHERE orderItemId = :orderItemId";
            $curriesStmt = $this->conn->prepare($curriesQuery);
            $curriesStmt->bindParam(":orderItemId", $item['id']);
            $curriesStmt->execute();
            $item['curries'] = $curriesStmt->fetchAll(PDO::FETCH_ASSOC);

            // Load customizations
            $customQuery = "SELECT * FROM order_item_customizations WHERE orderItemId = :orderItemId";
            $customStmt = $this->conn->prepare($customQuery);
            $customStmt->bindParam(":orderItemId", $item['id']);
            $customStmt->execute();
            $item['customizations'] = $customStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $order['items'] = $items;
    }

    public function create($data) {
        // Generate order number
        $orderNumber = $this->generateOrderNumber();
        
        $this->conn->beginTransaction();
        
        try {
            // Insert order
            $query = "INSERT INTO " . $this->table_name . " 
                      SET orderNumber=:orderNumber, customerId=:customerId, 
                      customerName=:customerName, customerPhone=:customerPhone,
                      merchantId=:merchantId, shopId=:shopId, merchantName=:merchantName,
                      total=:total, status=:status, paymentStatus=:paymentStatus,
                      paymentMethod=:paymentMethod, notes=:notes, estimatedPickupTime=:estimatedPickupTime,
                      createdAt=NOW(), updatedAt=NOW()";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":orderNumber", $orderNumber);
            $stmt->bindParam(":customerId", $data['customerId']);
            $stmt->bindParam(":customerName", $data['customerName']);
            $stmt->bindParam(":customerPhone", $data['customerPhone']);
            $stmt->bindParam(":merchantId", $data['merchantId']);
            $stmt->bindParam(":shopId", $data['shopId']);
            $stmt->bindParam(":merchantName", $data['merchantName']);
            $stmt->bindParam(":total", $data['total']);
            $stmt->bindParam(":status", $data['status']);
            $stmt->bindParam(":paymentStatus", $data['paymentStatus']);
            $stmt->bindParam(":paymentMethod", $data['paymentMethod']);
            $stmt->bindParam(":notes", $data['notes']);
            $stmt->bindParam(":estimatedPickupTime", $data['estimatedPickupTime']);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create order");
            }
            
            $orderId = $this->conn->lastInsertId();
            
            // Insert order items
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->createOrderItem($orderId, $item);
                }
            }
            
            $this->conn->commit();
            return $orderId;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    private function createOrderItem($orderId, $item) {
        // Insert order item
        $query = "INSERT INTO order_items (orderId, mealTypeId, mealTypeName, mealTypePrice, 
                  subtotal, specialInstructions) 
                  VALUES (:orderId, :mealTypeId, :mealTypeName, :mealTypePrice, 
                  :subtotal, :specialInstructions)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":orderId", $orderId);
        $stmt->bindParam(":mealTypeId", $item['mealType']['id']);
        $stmt->bindParam(":mealTypeName", $item['mealType']['name']);
        $stmt->bindParam(":mealTypePrice", $item['mealType']['price']);
        $stmt->bindParam(":subtotal", $item['subtotal']);
        $stmt->bindParam(":specialInstructions", $item['specialInstructions']);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create order item");
        }
        
        $orderItemId = $this->conn->lastInsertId();
        
        // Insert curries
        if (!empty($item['curries'])) {
            foreach ($item['curries'] as $curry) {
                $this->createOrderItemCurry($orderItemId, $curry);
            }
        }
        
        // Insert customizations
        if (!empty($item['customizations'])) {
            foreach ($item['customizations'] as $customization) {
                $this->createOrderItemCustomization($orderItemId, $customization);
            }
        }
    }

    private function createOrderItemCurry($orderItemId, $curry) {
        $query = "INSERT INTO order_item_curries (orderItemId, curryId, curryName) 
                  VALUES (:orderItemId, :curryId, :curryName)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":orderItemId", $orderItemId);
        $stmt->bindParam(":curryId", $curry['id']);
        $stmt->bindParam(":curryName", $curry['name']);
        
        $stmt->execute();
    }

    private function createOrderItemCustomization($orderItemId, $customization) {
        $query = "INSERT INTO order_item_customizations (orderItemId, customizationId, 
                  customizationName, customizationPrice, customizationType) 
                  VALUES (:orderItemId, :customizationId, :customizationName, 
                  :customizationPrice, :customizationType)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":orderItemId", $orderItemId);
        $stmt->bindParam(":customizationId", $customization['id']);
        $stmt->bindParam(":customizationName", $customization['name']);
        $stmt->bindParam(":customizationPrice", $customization['price']);
        $stmt->bindParam(":customizationType", $customization['type']);
        
        $stmt->execute();
    }

    private function generateOrderNumber() {
        // Try using the MySQL function first
        try {
            $query = "SELECT generate_order_number() as orderNumber";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['orderNumber'];
        } catch (Exception $e) {
            // Fallback PHP generation
            do {
                $timestamp = substr(time(), -6);
                $random = str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);
                $orderNumber = "ORD{$timestamp}{$random}";
                
                $checkQuery = "SELECT COUNT(*) as count FROM orders WHERE orderNumber = :orderNumber";
                $checkStmt = $this->conn->prepare($checkQuery);
                $checkStmt->bindParam(":orderNumber", $orderNumber);
                $checkStmt->execute();
                $count = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
            } while ($count > 0);
            
            return $orderNumber;
        }
    }

    public function updateStatus($orderId, $status, $cancellationReason = null) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status, updatedAt = NOW()";
        
        // Add timestamp fields based on status
        switch ($status) {
            case 'confirmed':
                $query .= ", confirmedAt = NOW()";
                break;
            case 'preparing':
                $query .= ", preparingAt = NOW()";
                break;
            case 'ready':
                $query .= ", readyAt = NOW()";
                break;
            case 'completed':
                $query .= ", completedAt = NOW()";
                break;
            case 'cancelled':
                $query .= ", cancelledAt = NOW(), cancellationReason = :cancellationReason";
                break;
        }
        
        $query .= " WHERE id = :orderId";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":orderId", $orderId);
        
        if ($status === 'cancelled' && $cancellationReason) {
            $stmt->bindParam(":cancellationReason", $cancellationReason);
        }
        
        return $stmt->execute();
    }

    public function updatePaymentStatus($orderId, $paymentStatus) {
        $query = "UPDATE " . $this->table_name . " 
                  SET paymentStatus = :paymentStatus, updatedAt = NOW() 
                  WHERE id = :orderId";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":paymentStatus", $paymentStatus);
        $stmt->bindParam(":orderId", $orderId);
        
        return $stmt->execute();
    }

    public function addRating($orderId, $rating, $review) {
        $query = "UPDATE " . $this->table_name . " 
                  SET rating = :rating, review = :review, updatedAt = NOW() 
                  WHERE id = :orderId AND status = 'completed'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":rating", $rating);
        $stmt->bindParam(":review", $review);
        $stmt->bindParam(":orderId", $orderId);
        
        return $stmt->execute();
    }
}
?>
