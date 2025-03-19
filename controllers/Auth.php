<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../vendor/autoload.php';
use \Firebase\JWT\JWT;

class Auth {
    private $user;
    private $secret_key = "[FILL WITH YOUR OWN SECRETE KEY]"; // Kunci untuk access token
    private $refresh_secret_key = "q1w2e3r4t5y6u7i8o9p0[-]="; // Kunci berbeda untuk refresh token

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->user = new UserModel($db);
    }

    public function login() {
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->email) && !empty($data->password)) {
            $email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
            $password = $data->password;
        
            $user_data = $this->user->getUserByEmail($email);
            if ($user_data && password_verify($password, $user_data['password'])) {
                // Access token (1 jam)
                $access_payload = [
                    "iat" => time(),
                    "exp" => time() + (60 * 60), // 1 jam
                    "data" => ["id" => $user_data['id'], "nama" => $user_data['nama'], "email" => $user_data['email'], "role" => $user_data['role']]
                ];
                $access_token = JWT::encode($access_payload, $this->secret_key, 'HS256');
    
                // Refresh token (1 hari)
                $refresh_payload = [
                    "iat" => time(),
                    "exp" => time() + (24 * 60 * 60),
                    "data" => ["id" => $user_data['id']]
                ];
                $refresh_token = JWT::encode($refresh_payload, $this->refresh_secret_key, 'HS256');
    
                http_response_code(200);
                echo json_encode([
                    "message" => "Login berhasil",
                    "access_token" => $access_token,
                    "refresh_token" => $refresh_token
                ]);
            } else {
                http_response_code(401);
                echo json_encode(["message" => "Kredensial tidak valid"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Email dan kata sandi diperlukan"]);
        }
    }

    public function refresh() {
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->refresh_token)) {
            try {
                $decoded = JWT::decode($data->refresh_token, new \Firebase\JWT\Key($this->refresh_secret_key, 'HS256'));
                error_log('Decoded User ID: ' . $decoded->data->id);
                $user_id = $decoded->data->id;

                // Ambil data pengguna dari DB untuk memastikan masih valid
                $user_data = $this->user->getUserById($user_id);
                if (!$user_data) {
                    throw new Exception("User not found");
                }

                // Buat access token baru
                $access_payload = [
                    "iat" => time(),
                    "exp" => time() + (60 * 60), // 1 jam
                    "data" => ["id" => $user_data['id'], "nama" => $user_data['nama'], "email" => $user_data['email'], "role" => $user_data['role']]
                ];
                $access_token = JWT::encode($access_payload, $this->secret_key, 'HS256');

                http_response_code(200);
                echo json_encode([
                    "message" => "Token berhasil diperbarui",
                    "access_token" => $access_token
                ]);
            } catch (Exception $e) {
                echo('Refresh Error: ' . $e->getMessage());
                http_response_code(401);
                echo json_encode(["message" => "Pembaruan token tidak valid atau kedaluwarsa"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Diperlukan pembaruan token"]);
        }
    }

    public function register() {
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->nama) && !empty($data->email) && !empty($data->password)) {
            $this->user->nama = filter_var($data->nama, FILTER_SANITIZE_STRING);
            $this->user->email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
            $this->user->password = password_hash($data->password, PASSWORD_BCRYPT);
            $this->user->nomor_telepon = isset($data->nomor_telepon) ? filter_var($data->nomor_telepon, FILTER_SANITIZE_STRING) : null;

            if ($this->user->create()) {
                http_response_code(201);
                echo json_encode(["message" => "Pengguna berhasil terdaftar"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Gagal mendaftarkan pengguna"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Data tidak lengkap"]);
        }
    }
}
?>