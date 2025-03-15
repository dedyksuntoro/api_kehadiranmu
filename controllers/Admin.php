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
    private $user;
    private $secret_key = "1q2w3e4r5t6y7u8i9o0p-[=]";

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->shift = new ShiftModel($db);
        $this->lokasi = new LokasiModel($db);
        $this->absensi = new AbsensiModel($db);
        $this->user = new UserModel($db);
    }

    private function verifyAdminToken() {
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
                if ($decoded->data->role !== 'admin') {
                    http_response_code(403);
                    echo json_encode(["message" => "Akses ditolak. Hanya admin"]);
                    exit;
                }
                return $decoded->data->id;
            } catch (Exception $e) {
                http_response_code(401);
                echo json_encode(["message" => "Token tidak valid"]);
                exit;
            }
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Token diperlukan"]);
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
                echo json_encode(["message" => "Shift berhasil dibuat"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Gagal membuat shift"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Data tidak lengkap"]);
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
                echo json_encode(["message" => "Shift berhasil diperbarui"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Gagal memperbarui shift"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Data tidak lengkap"]);
        }
    }

    public function deleteShift($id) {
        $this->verifyAdminToken();
        $this->shift->id = $id;

        if ($this->shift->delete()) {
            http_response_code(200);
            echo json_encode(["message" => "Shift berhasil dihapus"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Gagal menghapus shift"]);
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
                echo json_encode(["message" => "Lokasi berhasil dibuat"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Gagal membuat lokasi"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Data tidak lengkap"]);
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
                echo json_encode(["message" => "Lokasi berhasil diperbarui"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Gagal memperbarui lokasi"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Data tidak lengkap"]);
        }
    }

    public function deleteLokasi($id) {
        $this->verifyAdminToken();
        $this->lokasi->id = $id;

        if ($this->lokasi->delete()) {
            http_response_code(200);
            echo json_encode(["message" => "Lokasi berhasil dihapus"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Gagal menghapus lokasi"]);
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
            echo json_encode(["message" => "Gagal mengambil absensi: " . $e->getMessage()]);
        }
    }

    public function getUsers() {
        $this->verifyAdminToken();
        $nama = isset($_GET['nama']) ? filter_var($_GET['nama'], FILTER_SANITIZE_STRING) : null;
        $role = isset($_GET['role']) ? filter_var($_GET['role'], FILTER_SANITIZE_STRING) : 'user'; // Default ke 'user'

        // Validasi role
        if ($role && !in_array($role, ['admin', 'user'])) {
            http_response_code(400);
            echo json_encode(["message" => "Role tidak valid. Gunakan 'admin' atau 'user'"]);
            return;
        }

        $users = $this->user->getAllUsers($nama, $role);
        http_response_code(200);
        echo json_encode($users);
    }

    public function createUser() {
        $this->verifyAdminToken();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->nama) && !empty($data->email) && !empty($data->password) && !empty($data->nomor_telepon) && !empty($data->role)) {
            $this->user->nama = filter_var($data->nama, FILTER_SANITIZE_STRING);
            $this->user->email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
            $this->user->password = password_hash($data->password, PASSWORD_DEFAULT); // Hash password
            $this->user->nomor_telepon = filter_var($data->nomor_telepon, FILTER_SANITIZE_STRING);
            $this->user->role = filter_var($data->role, FILTER_SANITIZE_STRING);

            if ($this->user->isEmailDuplicate($this->user->email)) {
                http_response_code(400);
                echo json_encode(["message" => "Email sudah digunakan"]);
                return;
            }

            if ($this->user->create()) {
                http_response_code(201);
                echo json_encode(["message" => "Karyawan berhasil ditambahkan"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Gagal menambahkan karyawan"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Data tidak lengkap"]);
        }
    }

    public function updateUser($id) {
        $this->verifyAdminToken();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->nama) && !empty($data->email) && !empty($data->nomor_telepon) && !empty($data->role)) {
            $this->user->id = $id;
            $this->user->nama = filter_var($data->nama, FILTER_SANITIZE_STRING);
            $this->user->email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
            $this->user->nomor_telepon = filter_var($data->nomor_telepon, FILTER_SANITIZE_STRING);
            $this->user->role = filter_var($data->role, FILTER_SANITIZE_STRING);
            $this->user->password = !empty($data->password) ? password_hash($data->password, PASSWORD_DEFAULT) : null;

            if ($this->user->isEmailDuplicate($this->user->email, $id)) {
                http_response_code(400);
                echo json_encode(["message" => "Email sudah digunakan oleh karyawan lain"]);
                return;
            }

            if ($this->user->update()) {
                http_response_code(200);
                echo json_encode(["message" => "Karyawan berhasil diperbarui"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Gagal memperbarui karyawan"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Data tidak lengkap"]);
        }
    }

    public function deleteUser($id) {
        $this->verifyAdminToken();
        $this->user->id = $id;

        if ($this->user->getUserById($id)) {
            if ($this->user->delete()) {
                http_response_code(200);
                echo json_encode(["message" => "Karyawan berhasil dihapus"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Gagal menghapus karyawan"]);
            }
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Karyawan tidak ditemukan"]);
        }
    }
}
?>