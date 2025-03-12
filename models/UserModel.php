<?php
class UserModel {
    private $conn;
    private $table_name = "tbm_users";

    public $id;
    public $nama;
    public $email;
    public $password;
    public $nomor_telepon;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getUserByEmail($email) {
        $query = "
            SELECT * FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create() {
        $query = "
            INSERT INTO " . $this->table_name . " (nama, email, password, nomor_telepon) VALUES (:nama, :email, :password, :nomor_telepon)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nama', $this->nama);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':nomor_telepon', $this->nomor_telepon);
        return $stmt->execute();
    }

    public function getUserById($id) {
        $query = "SELECT id, email, role, password FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>