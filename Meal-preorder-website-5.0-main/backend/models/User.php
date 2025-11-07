<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $name;
    public $email;
    public $password;
    public $role;
    public $approved;
    public $shopName;
    public $location;
    public $phone;
    public $isActive;
    public $lastLogin;
    public $profileImage;
    public $createdAt;
    public $updatedAt;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function findById($id) {
        $query = "SELECT id, name, email, role, approved, shopName, location, phone, 
                         isActive, lastLogin, profileImage, createdAt, updatedAt 
                  FROM " . $this->table_name . " 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            // Convert boolean fields
            $user['approved'] = (bool)$user['approved'];
            $user['isActive'] = (bool)$user['isActive'];
            return $user;
        }
        return false;
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            // Convert boolean fields
            $user['approved'] = (bool)$user['approved'];
            $user['isActive'] = (bool)$user['isActive'];
            return $user;
        }
        return false;
    }

    public function create($data) {
        // Set default approved status based on role
        $approved = isset($data['approved']) ? $data['approved'] : ($data['role'] !== 'merchant');
        
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name, email=:email, password=:password, role=:role, 
                  approved=:approved, shopName=:shopName, location=:location, 
                  phone=:phone, isActive=:isActive, profileImage=:profileImage, 
                  createdAt=NOW(), updatedAt=NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":email", $data['email']);
        $stmt->bindParam(":password", password_hash($data['password'], PASSWORD_DEFAULT));
        $stmt->bindParam(":role", $data['role']);
        $stmt->bindParam(":approved", $approved, PDO::PARAM_BOOL);
        $stmt->bindParam(":shopName", $data['shopName']);
        $stmt->bindParam(":location", $data['location']);
        $stmt->bindParam(":phone", $data['phone']);
        $stmt->bindParam(":isActive", $data['isActive'], PDO::PARAM_BOOL);
        $stmt->bindParam(":profileImage", $data['profileImage']);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET name=:name, email=:email, role=:role, approved=:approved, 
                  shopName=:shopName, location=:location, phone=:phone, 
                  isActive=:isActive, profileImage=:profileImage, updatedAt=NOW() 
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":email", $data['email']);
        $stmt->bindParam(":role", $data['role']);
        $stmt->bindParam(":approved", $data['approved'], PDO::PARAM_BOOL);
        $stmt->bindParam(":shopName", $data['shopName']);
        $stmt->bindParam(":location", $data['location']);
        $stmt->bindParam(":phone", $data['phone']);
        $stmt->bindParam(":isActive", $data['isActive'], PDO::PARAM_BOOL);
        $stmt->bindParam(":profileImage", $data['profileImage']);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }

    public function updateLastLogin($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET lastLogin = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function verifyPassword($password, $hashedPassword) {
        return password_verify($password, $hashedPassword);
    }

    public function getAllMerchants($approvedOnly = false) {
        $query = "SELECT id, name, email, role, approved, shopName, location, phone, 
                         isActive, createdAt 
                  FROM " . $this->table_name . " 
                  WHERE role = 'merchant'";
        
        if ($approvedOnly) {
            $query .= " AND approved = 1";
        }
        
        $query .= " ORDER BY createdAt DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $merchants = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['approved'] = (bool)$row['approved'];
            $row['isActive'] = (bool)$row['isActive'];
            $merchants[] = $row;
        }
        
        return $merchants;
    }
}
?>
