<?php
require_once __DIR__ . '/../config/database.php';

class Order {
    private $conn;
    private $table_name = "orders";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($order_data) {
        try {
            $this->conn->beginTransaction();

            // Generate order number
            $order_number = 'ORD' . substr(time(), -6);

            // Insert main order
            $query = "INSERT INTO " . $this->table_name . " 
                      SET order_number=:order_number, customer_id=:customer_id, customer_name=:customer_name,
                          merchant_id=:merchant_id, merchant_name=:merchant_name, total=:total";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":order_number", $order_number);
            $stmt->bindParam(":customer_id", $order_data['customer_id']);
            $stmt->bindParam(":customer_name", $order_data['customer_name']);
            $stmt->bindParam(":merchant_id", $order_data['merchant_id']);
            $stmt->bindParam(":merchant_name", $order_data['merchant_name']);
            $stmt->bindParam(":total", $order_data['total']);

            if (!$stmt->execute()) {
                throw new Exception("Failed to create order");
            }

            $order_id = $this->conn->lastInsertId();

            // Insert order items
            foreach ($order_data['items'] as $item) {
                $item_query = "INSERT INTO order_items 
                               SET order_id=:order_id, meal_type_id=:meal_type_id, meal_type_name=:meal_type_name,
                                   meal_type_price=:meal_type_price, subtotal=:subtotal";

                $item_stmt = $this->conn->prepare($item_query);
                $item_stmt->bindParam(":order_id", $order_id);
                $item_stmt->bindParam(":meal_type_id", $item['meal_type']['id']);
                $item_stmt->bindParam(":meal_type_name", $item['meal_type']['name']);
                $item_stmt->bindParam(":meal_type_price", $item['meal_type']['price']);
                $item_stmt->bindParam(":subtotal", $item['subtotal']);

                if (!$item_stmt->execute()) {
                    throw new Exception("Failed to create order item");
                }

                $order_item_id = $this->conn->lastInsertId();

                // Insert curries
                foreach ($item['curries'] as $curry) {
                    $curry_query = "INSERT INTO order_item_curries 
                                    SET order_item_id=:order_item_id, curry_id=:curry_id, curry_name=:curry_name";

                    $curry_stmt = $this->conn->prepare($curry_query);
                    $curry_stmt->bindParam(":order_item_id", $order_item_id);
                    $curry_stmt->bindParam(":curry_id", $curry['id']);
                    $curry_stmt->bindParam(":curry_name", $curry['name']);

                    if (!$curry_stmt->execute()) {
                        throw new Exception("Failed to add curry to order");
                    }
                }

                // Insert customizations
                foreach ($item['customizations'] as $custom) {
                    $custom_query = "INSERT INTO order_item_customizations 
                                     SET order_item_id=:order_item_id, customization_id=:customization_id,
                                         customization_name=:customization_name, customization_price=:customization_price,
                                         customization_type=:customization_type, quantity=:quantity";

                    $custom_stmt = $this->conn->prepare($custom_query);
                    $custom_stmt->bindParam(":order_item_id", $order_item_id);
                    $custom_stmt->bindParam(":customization_id", $custom['id']);
                    $custom_stmt->bindParam(":customization_name", $custom['name']);
                    $custom_stmt->bindParam(":customization_price", $custom['price']);
                    $custom_stmt->bindParam(":customization_type", $custom['type']);
                    $custom_stmt->bindParam(":quantity", $custom['quantity']);

                    if (!$custom_stmt->execute()) {
                        throw new Exception("Failed to add customization to order");
                    }
                }
            }

            $this->conn->commit();
            return $order_number;

        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    public function getCustomerOrders($customer_id) {
        $query = "SELECT o.*, 
                         (SELECT JSON_ARRAYAGG(
                             JSON_OBJECT(
                                 'meal_type', JSON_OBJECT('id', oi.meal_type_id, 'name', oi.meal_type_name, 'price', oi.meal_type_price),
                                 'curries', (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', curry_id, 'name', curry_name)) FROM order_item_curries WHERE order_item_id = oi.id),
                                 'customizations', (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', customization_id, 'name', customization_name, 'price', customization_price, 'type', customization_type, 'quantity', quantity)) FROM order_item_customizations WHERE order_item_id = oi.id),
                                 'subtotal', oi.subtotal
                             )
                         ) FROM order_items oi WHERE oi.order_id = o.id) as items
                  FROM " . $this->table_name . " o 
                  WHERE o.customer_id = :customer_id 
                  ORDER BY o.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":customer_id", $customer_id);
        $stmt->execute();

        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($orders as &$order) {
            $order['items'] = json_decode($order['items'] ?: '[]', true);
        }

        return $orders;
    }

    public function getMerchantOrders($merchant_id) {
        $query = "SELECT o.*, 
                         (SELECT JSON_ARRAYAGG(
                             JSON_OBJECT(
                                 'meal_type', JSON_OBJECT('id', oi.meal_type_id, 'name', oi.meal_type_name, 'price', oi.meal_type_price),
                                 'curries', (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', curry_id, 'name', curry_name)) FROM order_item_curries WHERE order_item_id = oi.id),
                                 'customizations', (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', customization_id, 'name', customization_name, 'price', customization_price, 'type', customization_type, 'quantity', quantity)) FROM order_item_customizations WHERE order_item_id = oi.id),
                                 'subtotal', oi.subtotal
                             )
                         ) FROM order_items oi WHERE oi.order_id = o.id) as items
                  FROM " . $this->table_name . " o 
                  WHERE o.merchant_id = :merchant_id 
                  ORDER BY o.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":merchant_id", $merchant_id);
        $stmt->execute();

        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($orders as &$order) {
            $order['items'] = json_decode($order['items'] ?: '[]', true);
        }

        return $orders;
    }

    public function updateStatus($order_id, $status) {
        $completed_at = ($status === 'completed') ? date('Y-m-d H:i:s') : null;
        
        $query = "UPDATE " . $this->table_name . " 
                  SET status=:status, completed_at=:completed_at 
                  WHERE id=:order_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":completed_at", $completed_at);
        $stmt->bindParam(":order_id", $order_id);

        return $stmt->execute();
    }

    public function getOrderById($order_id) {
        $query = "SELECT o.*, 
                         (SELECT JSON_ARRAYAGG(
                             JSON_OBJECT(
                                 'meal_type', JSON_OBJECT('id', oi.meal_type_id, 'name', oi.meal_type_name, 'price', oi.meal_type_price),
                                 'curries', (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', curry_id, 'name', curry_name)) FROM order_item_curries WHERE order_item_id = oi.id),
                                 'customizations', (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', customization_id, 'name', customization_name, 'price', customization_price, 'type', customization_type, 'quantity', quantity)) FROM order_item_customizations WHERE order_item_id = oi.id),
                                 'subtotal', oi.subtotal
                             )
                         ) FROM order_items oi WHERE oi.order_id = o.id) as items
                  FROM " . $this->table_name . " o 
                  WHERE o.id = :order_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":order_id", $order_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            $order['items'] = json_decode($order['items'] ?: '[]', true);
            return $order;
        }

        return false;
    }
}
?>