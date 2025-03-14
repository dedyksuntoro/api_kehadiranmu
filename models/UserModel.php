<?php
class UserModel {
    private $conn;
    private $table_name = "tbm_users";

    public $id;
    public $nama;
    public $email;
    public $password;
    public $nomor_telepon;
    public $role;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getUserByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (nama, email, password, nomor_telepon, role) VALUES (:nama, :email, :password, :nomor_telepon, :role)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nama', $this->nama);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':nomor_telepon', $this->nomor_telepon);
        $stmt->bindParam(':role', $this->role);
        return $stmt->execute();
    }

    public function getUserById($id) {
        $query = "SELECT id, nama, email, nomor_telepon, role, password, created_at FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update getAllUsers dengan filter
    public function getAllUsers($nama = null, $role = 'user') {
        $query = "SELECT id, nama, email, nomor_telepon, role, created_at FROM " . $this->table_name;
        $params = [];
        $conditions = [];

        if ($nama) {
            $conditions[] = "nama LIKE :nama";
            $params[':nama'] = "%$nama%";
        }
        if ($role) {
            $conditions[] = "role = :role";
            $params[':role'] = $role;
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY nama ASC";
        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET nama = :nama, email = :email, nomor_telepon = :nomor_telepon, role = :role" . 
                 ($this->password ? ", password = :password" : "") . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nama', $this->nama);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':nomor_telepon', $this->nomor_telepon);
        $stmt->bindParam(':role', $this->role);
        if ($this->password) {
            $stmt->bindParam(':password', $this->password);
        }
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    public function isEmailDuplicate($email, $excludeId = null) {
        $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE email = :email" . ($excludeId ? " AND id != :id" : "");
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        if ($excludeId) {
            $stmt->bindParam(':id', $excludeId);
        }
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
}
?>