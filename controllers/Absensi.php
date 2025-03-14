<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AbsensiModel.php';
require_once __DIR__ . '/../vendor/autoload.php';
use \Firebase\JWT\JWT;

class Absensi {
    private $absensi;
    private $secret_key = "1q2w3e4r5t6y7u8i9o0p-[=]";

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->absensi = new AbsensiModel($db);
    }

    private function verifyToken() {
        $headers = apache_request_headers();
        error_log('Request Headers: ' . json_encode($headers));
        
        $authHeader = null;
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $authHeader = $value;
                break;
            }
        }
        
        if ($authHeader !== null) {
            $token = str_replace("Bearer ", "", $authHeader);
            try {
                $decoded = JWT::decode($token, new \Firebase\JWT\Key($this->secret_key, 'HS256'));
                error_log('Decoded Token ID: ' . $decoded->data->id);
                return $decoded->data->id;
            } catch (Exception $e) {
                http_response_code(401);
                echo json_encode(["message" => "Invalid token"]);
                exit;
            }
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Token required"]);
            exit;
        }
    }

    public function create() {
        $user_id = $this->verifyToken();
        $data = json_decode(file_get_contents("php://input"));
    
        if (!empty($data->latitude) && !empty($data->longitude) && !empty($data->foto_path)) {
            $this->absensi->user_id = $user_id;
            $this->absensi->tanggal = date('Y-m-d');
            $this->absensi->waktu_masuk = date('Y-m-d H:i:s');
            $this->absensi->latitude = filter_var($data->latitude, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $this->absensi->longitude = filter_var($data->longitude, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $this->absensi->foto_path = filter_var($data->foto_path, FILTER_SANITIZE_STRING);
    
            if (!$this->absensi->checkShiftAvailability()) {
                http_response_code(400);
                echo json_encode(["message" => "Tidak ada shift yang tersedia, hubungi admin"]);
                return;
            }
            if (!$this->absensi->checkLokasiAvailability()) {
                http_response_code(400);
                echo json_encode(["message" => "Tidak ada lokasi yang tersedia, hubungi admin"]);
                return;
            }
            if (!$this->absensi->isWithinLocation($this->absensi->latitude, $this->absensi->longitude)) {
                http_response_code(400);
                echo json_encode(["message" => "Lokasi tidak valid untuk absensi"]);
                return;
            }
    
            if ($this->absensi->create()) {
                http_response_code(201);
                echo json_encode(["message" => "Absensi recorded successfully", "foto_path" => $this->absensi->foto_path]);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Sudah absen masuk untuk shift ini"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data"]);
        }
    }

    public function read() {
        $user_id = $this->verifyToken();

        // Ambil parameter dari query string
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

        $result = $this->absensi->read($user_id, $page, $limit, $start_date, $end_date);
        http_response_code(200);
        echo json_encode($result);
    }

    public function update() {
        $user_id = $this->verifyToken();
        $data = json_decode(file_get_contents("php://input"));
        $waktu_keluar = date('Y-m-d H:i:s');
    
        if (!isset($data->latitude) || !isset($data->longitude)) {
            http_response_code(400);
            echo json_encode(["message" => "Latitude dan longitude wajib untuk absen keluar"]);
            return;
        }
    
        $latitude = filter_var($data->latitude, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $longitude = filter_var($data->longitude, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    
        if (!$this->absensi->isWithinLocation($latitude, $longitude)) {
            http_response_code(400);
            echo json_encode(["message" => "Lokasi tidak valid untuk absen keluar"]);
            return;
        }
    
        if ($this->absensi->updateLatestCheckOut($user_id, $waktu_keluar, $latitude, $longitude)) {
            http_response_code(200);
            echo json_encode(["message" => "Absensi keluar recorded"]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "No absensi found for this shift or already checked out"]);
        }
    }

    public function uploadFoto() {
        $user_id = $this->verifyToken();
        
        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(["message" => "No file uploaded or upload error"]);
            return;
        }
    
        $file = $_FILES['foto'];
        $file_name = $user_id . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $upload_path = __DIR__ . '/../uploads/' . $file_name;
    
        $uploadDir = __DIR__ . '/../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
    
        // Validasi tipe file berdasarkan MIME type atau ekstensi
        $allowed_types = ['image/jpeg', 'image/png', 'application/octet-stream'];
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        error_log('File MIME Type: ' . $file['type']); // Debug
        error_log('File Extension: ' . $file_extension); // Debug
    
        if (!in_array($file['type'], $allowed_types) || !in_array($file_extension, $allowed_extensions)) {
            http_response_code(400);
            echo json_encode(["message" => "Invalid file type. Only JPG/PNG allowed"]);
            return;
        }
        if ($file['size'] > 2 * 1024 * 1024) { // Maks 2MB
            http_response_code(400);
            echo json_encode(["message" => "File too large. Max 2MB"]);
            return;
        }
    
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $foto_path = '/uploads/' . $file_name;
            http_response_code(200);
            echo json_encode(["message" => "Foto uploaded successfully", "foto_path" => $foto_path]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to upload foto"]);
        }
    }
}
?>