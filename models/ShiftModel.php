<?php
class ShiftModel {
    private $conn;
    private $table_name = "tbm_jam_shift";

    public $id;
    public $shift;
    public $jam_mulai;
    public $jam_selesai;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY shift";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isDuplicate($shift, $exclude_id = null) {
        $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE shift = :shift";
        if ($exclude_id) {
            $query .= " AND id != :exclude_id";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':shift', $shift);
        if ($exclude_id) {
            $stmt->bindParam(':exclude_id', $exclude_id);
        }
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function create() {
        if ($this->isDuplicate($this->shift)) {
            return false;
        }
        $query = "INSERT INTO " . $this->table_name . " (shift, jam_mulai, jam_selesai) 
                  VALUES (:shift, :jam_mulai, :jam_selesai)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':shift', $this->shift);
        $stmt->bindParam(':jam_mulai', $this->jam_mulai);
        $stmt->bindParam(':jam_selesai', $this->jam_selesai);
        return $stmt->execute();
    }

    public function update() {
        if ($this->isDuplicate($this->shift, $this->id)) {
            return false;
        }
        $query = "UPDATE " . $this->table_name . " 
                  SET shift = :shift, jam_mulai = :jam_mulai, jam_selesai = :jam_selesai 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':shift', $this->shift);
        $stmt->bindParam(':jam_mulai', $this->jam_mulai);
        $stmt->bindParam(':jam_selesai', $this->jam_selesai);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
}
?>