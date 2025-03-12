<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ShiftModel.php';
require_once __DIR__ . '/../models/LokasiModel.php';
require_once __DIR__ . '/../models/AbsensiModel.php';
require_once __DIR__ . '/../vendor/autoload.php';
use \Firebase\JWT\JWT;

class Admin {
    private $shift;
    private $lokasi;
    private $absensi;
    private $secret_key = "1q2w3e4r5t6y7u8i9o0p-[=]";

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->shift = new ShiftModel($db);
        $this->lokasi = new LokasiModel($db);
        $this->absensi = new AbsensiModel($db);
    }

    private function verifyAdminToken() {
        $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(["message" => "Token required"]);
            exit;
        }
        $token = str_replace("Bearer ", "", $headers['Authorization']);
        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($this->secret_key, 'HS256'));
            if ($decoded->data->role !== 'admin') {
                http_response_code(403);
                echo json_encode(["message" => "Access denied. Admin only"]);
                exit;
            }
            return $decoded->data->id;
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid token"]);
            exit;
        }
    }

    // Shift CRUD
    public function getShifts() {
        $this->verifyAdminToken();
        $result = $this->shift->read();
        http_response_code(200);
        echo json_encode($result);
    }

    public function createShift() {
        $this->verifyAdminToken();
        $data = json_decode(file_get_contents("php://input"));
    
        if (!empty($data->shift) && !empty($data->jam_mulai) && !empty($data->jam_selesai)) {
            $this->shift->shift = filter_var($data->shift, FILTER_SANITIZE_STRING);
            $this->shift->jam_mulai = $data->jam_mulai;
            $this->shift->jam_selesai = $data->jam_selesai;
    
            if ($this->shift->isDuplicate($this->shift->shift)) {
                http_response_code(400);
                echo json_encode(["message" => "Nama shift sudah ada"]);
                return;
            }
    
            if ($this->shift->create()) {
                http_response_code(201);
                echo json_encode(["message" => "Shift created successfully"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Failed to create shift"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data"]);
        }
    }

    public function updateShift($id) {
        $this->verifyAdminToken();
        $data = json_decode(file_get_contents("php://input"));
    
        if (!empty($data->shift) && !empty($data->jam_mulai) && !empty($data->jam_selesai)) {
            $this->shift->id = $id;
            $this->shift->shift = filter_var($data->shift, FILTER_SANITIZE_STRING);
            $this->shift->jam_mulai = $data->jam_mulai;
            $this->shift->jam_selesai = $data->jam_selesai;
    
            if ($this->shift->isDuplicate($this->shift->shift, $this->shift->id)) {
                http_response_code(400);
                echo json_encode(["message" => "Nama shift sudah ada"]);
                return;
            }
    
            if ($this->shift->update()) {
                http_response_code(200);
                echo json_encode(["message" => "Shift updated successfully"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Failed to update shift"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data"]);
        }
    }

    public function deleteShift($id) {
        $this->verifyAdminToken();
        $this->shift->id = $id;

        if ($this->shift->delete()) {
            http_response_code(200);
            echo json_encode(["message" => "Shift deleted successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete shift"]);
        }
    }

    // Lokasi CRUD
    public function getLokasi() {
        $this->verifyAdminToken();
        $result = $this->lokasi->read();
        http_response_code(200);
        echo json_encode($result);
    }

    public function createLokasi() {
        $this->verifyAdminToken();
        $data = json_decode(file_get_contents("php://input"));
    
        if (!empty($data->nama_lokasi) && isset($data->latitude) && isset($data->longitude) && !empty($data->radius)) {
            $this->lokasi->nama_lokasi = filter_var($data->nama_lokasi, FILTER_SANITIZE_STRING);
            $this->lokasi->latitude = filter_var($data->latitude, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $this->lokasi->longitude = filter_var($data->longitude, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $this->lokasi->radius = filter_var($data->radius, FILTER_SANITIZE_NUMBER_INT);
    
            if ($this->lokasi->isDuplicate($this->lokasi->nama_lokasi)) {
                http_response_code(400);
                echo json_encode(["message" => "Nama lokasi sudah ada"]);
                return;
            }
    
            if ($this->lokasi->create()) {
                http_response_code(201);
                echo json_encode(["message" => "Lokasi created successfully"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Failed to create lokasi"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data"]);
        }
    }
    
    public function updateLokasi($id) {
        $this->verifyAdminToken();
        $data = json_decode(file_get_contents("php://input"));
    
        if (!empty($data->nama_lokasi) && isset($data->latitude) && isset($data->longitude) && !empty($data->radius)) {
            $this->lokasi->id = $id;
            $this->lokasi->nama_lokasi = filter_var($data->nama_lokasi, FILTER_SANITIZE_STRING);
            $this->lokasi->latitude = filter_var($data->latitude, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $this->lokasi->longitude = filter_var($data->longitude, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $this->lokasi->radius = filter_var($data->radius, FILTER_SANITIZE_NUMBER_INT);
    
            if ($this->lokasi->isDuplicate($this->lokasi->nama_lokasi, $this->lokasi->id)) {
                http_response_code(400);
                echo json_encode(["message" => "Nama lokasi sudah ada"]);
                return;
            }
    
            if ($this->lokasi->update()) {
                http_response_code(200);
                echo json_encode(["message" => "Lokasi updated successfully"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Failed to update lokasi"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data"]);
        }
    }

    public function deleteLokasi($id) {
        $this->verifyAdminToken();
        $this->lokasi->id = $id;

        if ($this->lokasi->delete()) {
            http_response_code(200);
            echo json_encode(["message" => "Lokasi deleted successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete lokasi"]);
        }
    }

    public function getAllAbsensi() {
        $this->verifyAdminToken();
    
        $tanggal_awal = isset($_GET['tanggal_awal']) ? filter_var($_GET['tanggal_awal'], FILTER_SANITIZE_STRING) : null;
        $tanggal_akhir = isset($_GET['tanggal_akhir']) ? filter_var($_GET['tanggal_akhir'], FILTER_SANITIZE_STRING) : null;
        $shift = isset($_GET['shift']) ? filter_var($_GET['shift'], FILTER_SANITIZE_STRING) : null;
        $user_id = isset($_GET['user_id']) ? filter_var($_GET['user_id'], FILTER_SANITIZE_NUMBER_INT) : null;
        $status_telat = isset($_GET['status_telat']) ? filter_var($_GET['status_telat'], FILTER_SANITIZE_STRING) : null;
        $page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_SANITIZE_NUMBER_INT) : 1;
        $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_SANITIZE_NUMBER_INT) : 10;
    
        try {
            $result = $this->absensi->readAll($tanggal_awal, $tanggal_akhir, $shift, $user_id, $status_telat, $page, $limit);
            http_response_code(200);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Failed to fetch absensi: " . $e->getMessage()]);
        }
    }
}
?>