<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/Auth.php';
require_once __DIR__ . '/controllers/Absensi.php';
require_once __DIR__ . '/controllers/Admin.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$request_method = $_SERVER["REQUEST_METHOD"];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', trim($uri, '/'));

$base_offset = (isset($uri[0]) && $uri[0] === 'api_kehadiranmu') ? 1 : 0;
$controller = isset($uri[$base_offset]) ? $uri[$base_offset] : '';
$method = isset($uri[$base_offset + 1]) ? $uri[$base_offset + 1] : '';
$id = isset($uri[$base_offset + 2]) ? $uri[$base_offset + 2] : null;

switch ($controller) {
    case 'auth':
        $auth = new Auth();
        if ($method == 'login') {
            $auth->login();
        } elseif ($method == 'register') {
            $auth->register();
        } elseif ($method == 'refresh' && $request_method == 'POST') {
            $auth->refresh();
        }
        break;
    case 'absensi':
        $absensi = new Absensi();
        if ($request_method == 'POST' && $method === '') {
            $absensi->create();
        } elseif ($request_method == 'GET') {
            $absensi->read();
        } elseif ($request_method == 'PUT' && $method === 'keluar') {
            $absensi->update();
        } elseif ($request_method == 'POST' && $method === 'upload-foto') {
            $absensi->uploadFoto();
        }
        break;
    case 'admin':
        $admin = new Admin();
        if ($method === 'shift') {
            if ($request_method == 'GET') {
                $admin->getShifts();
            } elseif ($request_method == 'POST') {
                $admin->createShift();
            } elseif ($request_method == 'PUT' && $id) {
                $admin->updateShift($id);
            } elseif ($request_method == 'DELETE' && $id) {
                $admin->deleteShift($id);
            }
        } elseif ($method === 'lokasi') {
            if ($request_method == 'GET') {
                $admin->getLokasi();
            } elseif ($request_method == 'POST') {
                $admin->createLokasi();
            } elseif ($request_method == 'PUT' && $id) {
                $admin->updateLokasi($id);
            } elseif ($request_method == 'DELETE' && $id) {
                $admin->deleteLokasi($id);
            }
        } elseif ($method === 'absensi') {
            if ($request_method == 'GET') {
                $admin->getAllAbsensi();
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Invalid request method"]);
            }
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Endpoint not found"]);
        }
        break;
    default:
        http_response_code(404);
        echo json_encode(["message" => "Endpoint not found"]);
        break;
}
?>