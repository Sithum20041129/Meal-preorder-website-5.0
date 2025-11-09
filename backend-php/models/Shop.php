<?php
require_once __DIR__ . '/../config/database.php';

class Shop {
    private $conn;
    private $table_name = "shops";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($user_id, $shop_data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id, name=:name, location=:location, phone=:phone";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":name", $shop_data['name']);
        $stmt->bindParam(":location", $shop_data['location']);
        $stmt->bindParam(":phone", $shop_data['phone']);

        if ($stmt->execute()) {
            $shop_id = $this->conn->lastInsertId();
            $this->createDefaultMenuItems($shop_id);
            return $shop_id;
        }

        return false;
    }

    private function createDefaultMenuItems($shop_id) {
        // Default meal types
        $meal_types = [
            ['name' => 'Vegetarian Rice', 'price' => 250.00, 'description' => 'Fresh vegetables with aromatic basmati rice'],
            ['name' => 'Chicken Rice', 'price' => 350.00, 'description' => 'Tender chicken pieces with spiced rice'],
            ['name' => 'Fish Rice', 'price' => 400.00, 'description' => 'Fresh fish curry with fragrant rice'],
            ['name' => 'Egg Rice', 'price' => 300.00, 'description' => 'Scrambled eggs with seasoned rice']
        ];

        foreach ($meal_types as $meal) {
            $query = "INSERT INTO meal_types (shop_id, name, price, description) VALUES (:shop_id, :name, :price, :description)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":shop_id", $shop_id);
            $stmt->bindParam(":name", $meal['name']);
            $stmt->bindParam(":price", $meal['price']);
            $stmt->bindParam(":description", $meal['description']);
            $stmt->execute();
        }

        // Default curries
        $curries = ['Dhal Curry', 'Vegetable Curry', 'Potato Curry', 'Chicken Curry', 'Fish Curry'];
        foreach ($curries as $curry) {
            $query = "INSERT INTO curries (shop_id, name) VALUES (:shop_id, :name)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":shop_id", $shop_id);
            $stmt->bindParam(":name", $curry);
            $stmt->execute();
        }

        // Default customizations
        $customizations = [
            ['name' => 'Extra Chicken Piece', 'price' => 100.00, 'type' => 'protein'],
            ['name' => 'Extra Fish Piece', 'price' => 150.00, 'type' => 'protein'],
            ['name' => 'Extra Curry', 'price' => 50.00, 'type' => 'curry'],
            ['name' => 'Extra Rice', 'price' => 30.00, 'type' => 'extra']
        ];

        foreach ($customizations as $custom) {
            $query = "INSERT INTO customizations (shop_id, name, price, type) VALUES (:shop_id, :name, :price, :type)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":shop_id", $shop_id);
            $stmt->bindParam(":name", $custom['name']);
            $stmt->bindParam(":price", $custom['price']);
            $stmt->bindParam(":type", $custom['type']);
            $stmt->execute();
        }
    }

    public function getAllShops() {
        $query = "SELECT s.*, 
                         (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', id, 'name', name, 'price', price, 'description', description, 'available', available)) 
                          FROM meal_types WHERE shop_id = s.id) as meal_types,
                         (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', id, 'name', name, 'available', available, 'price', price)) 
                          FROM curries WHERE shop_id = s.id) as curries,
                         (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', id, 'name', name, 'price', price, 'type', type, 'available', available)) 
                          FROM customizations WHERE shop_id = s.id) as customizations
                  FROM " . $this->table_name . " s 
                  WHERE EXISTS (SELECT 1 FROM users WHERE id = s.user_id AND approved = TRUE)
                  ORDER BY s.name";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode JSON fields
        foreach ($shops as &$shop) {
            $shop['meal_types'] = json_decode($shop['meal_types'] ?: '[]', true);
            $shop['curries'] = json_decode($shop['curries'] ?: '[]', true);
            $shop['customizations'] = json_decode($shop['customizations'] ?: '[]', true);
        }

        return $shops;
    }

    public function getShopByUserId($user_id) {
        $query = "SELECT s.*, 
                         (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', id, 'name', name, 'price', price, 'description', description, 'available', available)) 
                          FROM meal_types WHERE shop_id = s.id) as meal_types,
                         (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', id, 'name', name, 'available', available, 'price', price)) 
                          FROM curries WHERE shop_id = s.id) as curries,
                         (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', id, 'name', name, 'price', price, 'type', type, 'available', available)) 
                          FROM customizations WHERE shop_id = s.id) as customizations
                  FROM " . $this->table_name . " s 
                  WHERE s.user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $shop = $stmt->fetch(PDO::FETCH_ASSOC);
            $shop['meal_types'] = json_decode($shop['meal_types'] ?: '[]', true);
            $shop['curries'] = json_decode($shop['curries'] ?: '[]', true);
            $shop['customizations'] = json_decode($shop['customizations'] ?: '[]', true);
            return $shop;
        }

        return false;
    }

    public function updateSettings($user_id, $settings) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_open=:is_open, accepting_orders=:accepting_orders, 
                      closing_time=:closing_time, order_limit=:order_limit 
                  WHERE user_id=:user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":is_open", $settings['is_open'], PDO::PARAM_BOOL);
        $stmt->bindParam(":accepting_orders", $settings['accepting_orders'], PDO::PARAM_BOOL);
        $stmt->bindParam(":closing_time", $settings['closing_time']);
        $stmt->bindParam(":order_limit", $settings['order_limit']);
        $stmt->bindParam(":user_id", $user_id);

        return $stmt->execute();
    }

    public function updateMealType($meal_id, $data) {
        $query = "UPDATE meal_types 
                  SET name=:name, price=:price, description=:description, available=:available 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":price", $data['price']);
        $stmt->bindParam(":description", $data['description']);
        $stmt->bindParam(":available", $data['available'], PDO::PARAM_BOOL);
        $stmt->bindParam(":id", $meal_id);

        return $stmt->execute();
    }

    public function incrementOrderCount($shop_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET orders_received = orders_received + 1 
                  WHERE id = :shop_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":shop_id", $shop_id);

        return $stmt->execute();
    }
}
?>