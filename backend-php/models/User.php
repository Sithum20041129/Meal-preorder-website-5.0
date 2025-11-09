<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $email;
    public $password;
    public $name;
    public $role;
    public $approved;
    public $shop_name;
    public $location;
    public $phone;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET email=:email, password=:password, name=:name, role=:role, 
                      approved=:approved, shop_name=:shop_name, location=:location, phone=:phone";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->approved = $this->role === 'merchant' ? false : true;

        // Bind values
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":approved", $this->approved, PDO::PARAM_BOOL);
        $stmt->bindParam(":shop_name", $this->shop_name);
        $stmt->bindParam(":location", $this->location);
        $stmt->bindParam(":phone", $this->phone);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    public function login($email, $password) {
        $query = "SELECT id, email, password, name, role, approved 
                  FROM " . $this->table_name . " 
                  WHERE email = :email LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $row['password'])) {
                // Check if merchant is approved
                if ($row['role'] === 'merchant' && !$row['approved']) {
                    return false;
                }
                
                $this->id = $row['id'];
                $this->email = $row['email'];
                $this->name = $row['name'];
                $this->role = $row['role'];
                $this->approved = $row['approved'];
                
                return true;
            }
        }

        return false;
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function findById($id) {
        $query = "SELECT id, email, name, role, approved, shop_name, location, phone, created_at 
                  FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return false;
    }

    public function getPendingMerchants() {
        $query = "SELECT id, email, name, shop_name, location, phone, created_at 
                  FROM " . $this->table_name . " 
                  WHERE role = 'merchant' AND approved = FALSE 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function approveMerchant($id, $approved) {
        $query = "UPDATE " . $this->table_name . " SET approved = :approved WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":approved", $approved, PDO::PARAM_BOOL);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }

    public function getAllAdmins() {
        $query = "SELECT id, email, name, created_at 
                  FROM " . $this->table_name . " 
                  WHERE role = 'admin' 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteAdmin($id) {
        // Check if this is the last admin
        $count_query = "SELECT COUNT(*) as admin_count FROM " . $this->table_name . " WHERE role = 'admin'";
        $count_stmt = $this->conn->prepare($count_query);
        $count_stmt->execute();
        $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);

        if ($count_result['admin_count'] <= 1) {
            return false; // Cannot delete the last admin
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND role = 'admin'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }
}
?>