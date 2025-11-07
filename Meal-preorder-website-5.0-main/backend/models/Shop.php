<?php
class Shop {
    private $conn;
    private $table_name = "shops";

    public $id;
    public $merchantId;
    public $name;
    public $location;
    public $phone;
    public $description;
    public $image;
    public $isOpen;
    public $acceptingOrders;
    public $closingTime;
    public $orderLimit;
    public $ordersReceived;
    public $totalOrders;
    public $totalRevenue;
    public $rating;
    public $reviewCount;
    public $createdAt;
    public $updatedAt;

    // Related data
    public $businessHours = [];
    public $mealTypes = [];
    public $curries = [];
    public $customizations = [];

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
            $shop = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->loadRelatedData($shop);
            return $shop;
        }
        return false;
    }

    public function findByMerchantId($merchantId) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE merchantId = :merchantId";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":merchantId", $merchantId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $shop = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->loadRelatedData($shop);
            return $shop;
        }
        return false;
    }

    public function getAllActiveShops() {
        $query = "SELECT s.*, u.name as merchantName 
                  FROM " . $this->table_name . " s
                  JOIN users u ON s.merchantId = u.id
                  WHERE s.isOpen = 1 AND s.acceptingOrders = 1 
                  AND u.approved = 1 AND u.isActive = 1
                  ORDER BY s.name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $shops = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->loadRelatedData($row);
            $shops[] = $row;
        }
        
        return $shops;
    }

    private function loadRelatedData(&$shop) {
        $shopId = $shop['id'];
        
        // Load business hours
        $hoursQuery = "SELECT * FROM shop_business_hours WHERE shopId = :shopId";
        $hoursStmt = $this->conn->prepare($hoursQuery);
        $hoursStmt->bindParam(":shopId", $shopId);
        $hoursStmt->execute();
        $shop['businessHours'] = $hoursStmt->fetchAll(PDO::FETCH_ASSOC);

        // Load meal types
        $mealQuery = "SELECT * FROM shop_meal_types WHERE shopId = :shopId AND available = 1";
        $mealStmt = $this->conn->prepare($mealQuery);
        $mealStmt->bindParam(":shopId", $shopId);
        $mealStmt->execute();
        $shop['mealTypes'] = $mealStmt->fetchAll(PDO::FETCH_ASSOC);

        // Load curries
        $curryQuery = "SELECT * FROM shop_curries WHERE shopId = :shopId AND available = 1";
        $curryStmt = $this->conn->prepare($curryQuery);
        $curryStmt->bindParam(":shopId", $shopId);
        $curryStmt->execute();
        $shop['curries'] = $curryStmt->fetchAll(PDO::FETCH_ASSOC);

        // Load customizations
        $customQuery = "SELECT * FROM shop_customizations WHERE shopId = :shopId AND available = 1";
        $customStmt = $this->conn->prepare($customQuery);
        $customStmt->bindParam(":shopId", $shopId);
        $customStmt->execute();
        $shop['customizations'] = $customStmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert boolean fields
        $shop['isOpen'] = (bool)$shop['isOpen'];
        $shop['acceptingOrders'] = (bool)$shop['acceptingOrders'];
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET merchantId=:merchantId, name=:name, location=:location, 
                  phone=:phone, description=:description, image=:image, 
                  isOpen=:isOpen, acceptingOrders=:acceptingOrders, 
                  closingTime=:closingTime, orderLimit=:orderLimit, 
                  createdAt=NOW(), updatedAt=NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":merchantId", $data['merchantId']);
        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":location", $data['location']);
        $stmt->bindParam(":phone", $data['phone']);
        $stmt->bindParam(":description", $data['description']);
        $stmt->bindParam(":image", $data['image']);
        $stmt->bindParam(":isOpen", $data['isOpen'], PDO::PARAM_BOOL);
        $stmt->bindParam(":acceptingOrders", $data['acceptingOrders'], PDO::PARAM_BOOL);
        $stmt->bindParam(":closingTime", $data['closingTime']);
        $stmt->bindParam(":orderLimit", $data['orderLimit']);

        if ($stmt->execute()) {
            $shopId = $this->conn->lastInsertId();
            
            // Insert business hours
            if (!empty($data['businessHours'])) {
                $this->saveBusinessHours($shopId, $data['businessHours']);
            }
            
            // Insert meal types
            if (!empty($data['mealTypes'])) {
                $this->saveMealTypes($shopId, $data['mealTypes']);
            }
            
            // Insert curries
            if (!empty($data['curries'])) {
                $this->saveCurries($shopId, $data['curries']);
            }
            
            // Insert customizations
            if (!empty($data['customizations'])) {
                $this->saveCustomizations($shopId, $data['customizations']);
            }
            
            return $shopId;
        }
        return false;
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET name=:name, location=:location, phone=:phone, 
                  description=:description, image=:image, isOpen=:isOpen, 
                  acceptingOrders=:acceptingOrders, closingTime=:closingTime, 
                  orderLimit=:orderLimit, updatedAt=NOW() 
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":location", $data['location']);
        $stmt->bindParam(":phone", $data['phone']);
        $stmt->bindParam(":description", $data['description']);
        $stmt->bindParam(":image", $data['image']);
        $stmt->bindParam(":isOpen", $data['isOpen'], PDO::PARAM_BOOL);
        $stmt->bindParam(":acceptingOrders", $data['acceptingOrders'], PDO::PARAM_BOOL);
        $stmt->bindParam(":closingTime", $data['closingTime']);
        $stmt->bindParam(":orderLimit", $data['orderLimit']);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }

    public function resetDailyOrders($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET ordersReceived = 0, updatedAt = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    private function saveBusinessHours($shopId, $businessHours) {
        $query = "INSERT INTO shop_business_hours (shopId, day, openTime, closeTime, closed) 
                  VALUES (:shopId, :day, :openTime, :closeTime, :closed)";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($businessHours as $day => $hours) {
            $stmt->bindParam(":shopId", $shopId);
            $stmt->bindParam(":day", $day);
            $stmt->bindParam(":openTime", $hours['open']);
            $stmt->bindParam(":closeTime", $hours['close']);
            $stmt->bindParam(":closed", $hours['closed'], PDO::PARAM_BOOL);
            $stmt->execute();
        }
    }

    private function saveMealTypes($shopId, $mealTypes) {
        $query = "INSERT INTO shop_meal_types (shopId, name, price, description, available, image) 
                  VALUES (:shopId, :name, :price, :description, :available, :image)";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($mealTypes as $mealType) {
            $stmt->bindParam(":shopId", $shopId);
            $stmt->bindParam(":name", $mealType['name']);
            $stmt->bindParam(":price", $mealType['price']);
            $stmt->bindParam(":description", $mealType['description']);
            $stmt->bindParam(":available", $mealType['available'], PDO::PARAM_BOOL);
            $stmt->bindParam(":image", $mealType['image']);
            $stmt->execute();
        }
    }

    private function saveCurries($shopId, $curries) {
        $query = "INSERT INTO shop_curries (shopId, name, available, spiceLevel) 
                  VALUES (:shopId, :name, :available, :spiceLevel)";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($curries as $curry) {
            $stmt->bindParam(":shopId", $shopId);
            $stmt->bindParam(":name", $curry['name']);
            $stmt->bindParam(":available", $curry['available'], PDO::PARAM_BOOL);
            $stmt->bindParam(":spiceLevel", $curry['spiceLevel']);
            $stmt->execute();
        }
    }

    private function saveCustomizations($shopId, $customizations) {
        $query = "INSERT INTO shop_customizations (shopId, name, price, type, available) 
                  VALUES (:shopId, :name, :price, :type, :available)";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($customizations as $customization) {
            $stmt->bindParam(":shopId", $shopId);
            $stmt->bindParam(":name", $customization['name']);
            $stmt->bindParam(":price", $customization['price']);
            $stmt->bindParam(":type", $customization['type']);
            $stmt->bindParam(":available", $customization['available'], PDO::PARAM_BOOL);
            $stmt->execute();
        }
    }
}
?>
