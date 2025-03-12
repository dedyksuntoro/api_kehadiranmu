<?php
class LokasiModel {
    private $conn;
    private $table_name = "tbm_lokasi";

    public $id;
    public $nama_lokasi;
    public $latitude;
    public $longitude;
    public $radius;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY nama_lokasi";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isDuplicate($nama_lokasi, $exclude_id = null) {
        $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE nama_lokasi = :nama_lokasi";
        if ($exclude_id) {
            $query .= " AND id != :exclude_id";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nama_lokasi', $nama_lokasi);
        if ($exclude_id) {
            $stmt->bindParam(':exclude_id', $exclude_id);
        }
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function create() {
        if ($this->isDuplicate($this->nama_lokasi)) {
            return false;
        }
        $query = "INSERT INTO " . $this->table_name . " (nama_lokasi, latitude, longitude, radius) 
                  VALUES (:nama_lokasi, :latitude, :longitude, :radius)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nama_lokasi', $this->nama_lokasi);
        $stmt->bindParam(':latitude', $this->latitude);
        $stmt->bindParam(':longitude', $this->longitude);
        $stmt->bindParam(':radius', $this->radius);
        return $stmt->execute();
    }

    public function update() {
        if ($this->isDuplicate($this->nama_lokasi, $this->id)) {
            return false;
        }
        $query = "UPDATE " . $this->table_name . " 
                  SET nama_lokasi = :nama_lokasi, latitude = :latitude, longitude = :longitude, radius = :radius 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nama_lokasi', $this->nama_lokasi);
        $stmt->bindParam(':latitude', $this->latitude);
        $stmt->bindParam(':longitude', $this->longitude);
        $stmt->bindParam(':radius', $this->radius);
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