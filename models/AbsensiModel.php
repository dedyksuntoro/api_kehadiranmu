<?php
class AbsensiModel {
    private $conn;
    private $table_name = "tbl_absensi";

    public $id;
    public $user_id;
    public $tanggal;
    public $waktu_masuk;
    public $waktu_keluar;
    public $latitude;
    public $longitude;
    public $latitude_keluar;  // Tambah
    public $longitude_keluar; // Tambah
    public $foto_path;
    public $shift;
    public $tanggal_shift;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Fungsi untuk menghitung jarak menggunakan Haversine Formula (dalam meter)
    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // Radius bumi dalam meter
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) * sin($deltaLon / 2) * sin($deltaLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    // Fungsi untuk mendapatkan nama lokasi berdasarkan koordinat dari tbm_lokasi
    private function getLocationName($latitude, $longitude) {
        $query = "SELECT nama_lokasi, latitude, longitude, radius FROM tbm_lokasi";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($locations as $location) {
            $distance = $this->calculateDistance(
                (float)$latitude,
                (float)$longitude,
                (float)$location['latitude'],
                (float)$location['longitude']
            );
            $radius = (int)$location['radius']; // Radius dari tbm_lokasi
            error_log("Checking location: lat=$latitude, lon=$longitude, distance=$distance, radius=$radius");
            if ($distance <= $radius) {
                return $location['nama_lokasi'];
            }
        }
        return 'Tidak diketahui';
    }

    public function isWithinLocation($latitude, $longitude) {
        $query = "SELECT latitude, longitude, radius FROM tbm_lokasi";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($locations as $location) {
            $distance = $this->calculateDistance(
                $latitude,
                $longitude,
                (float)$location['latitude'],
                (float)$location['longitude']
            );
            $radius = (int)$location['radius'];
            if ($distance <= $radius) {
                return true;
            }
        }
        return false;
    }

    private function determineShift($waktu_masuk) {
        $hour = (int) date('H', strtotime($waktu_masuk));
        $tanggal = date('Y-m-d', strtotime($waktu_masuk));
        
        if ($hour >= 6 && $hour < 16) {
            $this->shift = 'pagi';
            $this->tanggal_shift = $tanggal;
        } elseif ($hour >= 20 || $hour < 6) {
            $this->shift = 'malam';
            $this->tanggal_shift = ($hour < 6) ? date('Y-m-d', strtotime($waktu_masuk . ' -1 day')) : $tanggal;
        } else {
            $this->shift = 'siang';
            $this->tanggal_shift = $tanggal;
        }
    }

    public function hasCheckedInShift($user_id, $waktu_masuk) {
        $this->determineShift($waktu_masuk);
        
        $query = "SELECT COUNT(*) FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                  AND shift = :shift 
                  AND tanggal_shift = :tanggal_shift";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':shift', $this->shift);
        $stmt->bindParam(':tanggal_shift', $this->tanggal_shift);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        error_log("Shift: $this->shift, Tanggal Shift: $this->tanggal_shift, Count: $count");
        return $count > 0;
    }

    public function create() {
        if ($this->hasCheckedInShift($this->user_id, $this->waktu_masuk)) {
            return false;
        }
        if (!$this->isWithinLocation($this->latitude, $this->longitude)) {
            return false;
        }
        $query = "INSERT INTO " . $this->table_name . " (user_id, tanggal, waktu_masuk, latitude, longitude, foto_path, shift, tanggal_shift) 
                  VALUES (:user_id, :tanggal, :waktu_masuk, :latitude, :longitude, :foto_path, :shift, :tanggal_shift)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':tanggal', $this->tanggal);
        $stmt->bindParam(':waktu_masuk', $this->waktu_masuk);
        $stmt->bindParam(':latitude', $this->latitude);
        $stmt->bindParam(':longitude', $this->longitude);
        $stmt->bindParam(':foto_path', $this->foto_path);
        $stmt->bindParam(':shift', $this->shift);
        $stmt->bindParam(':tanggal_shift', $this->tanggal_shift);
        return $stmt->execute();
    }

    public function read($user_id, $page = 1, $limit = 10, $start_date = null, $end_date = null) {
        $offset = ($page - 1) * $limit;

        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";
        if ($start_date && $end_date) {
            $query .= " AND tanggal BETWEEN :start_date AND :end_date";
        }
        $query .= " ORDER BY tanggal DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        if ($start_date && $end_date) {
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $absensi = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Tambah nama lokasi berdasarkan koordinat dari tbm_lokasi
        foreach ($absensi as &$item) {
            $item['lokasi_masuk'] = $this->getLocationName(
                (float)$item['latitude'],
                (float)$item['longitude']
            );
            $item['lokasi_keluar'] = ($item['latitude_keluar'] && $item['longitude_keluar'])
                ? $this->getLocationName((float)$item['latitude_keluar'], (float)$item['longitude_keluar'])
                : 'Tidak diketahui';
        }
        unset($item); // Hapus referensi

        $totalQuery = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE user_id = :user_id";
        if ($start_date && $end_date) {
            $totalQuery .= " AND tanggal BETWEEN :start_date AND :end_date";
        }
        $totalStmt = $this->conn->prepare($totalQuery);
        $totalStmt->bindParam(':user_id', $user_id);
        if ($start_date && $end_date) {
            $totalStmt->bindParam(':start_date', $start_date);
            $totalStmt->bindParam(':end_date', $end_date);
        }
        $totalStmt->execute();
        $total = $totalStmt->fetchColumn();

        return [
            "data" => $absensi,
            "total" => $total,
            "page" => $page,
            "pages" => ceil($total / $limit),
        ];
    }

    public function getShiftJamMulai($shift) {
        $query = "SELECT jam_mulai FROM tbm_jam_shift WHERE shift = :shift LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':shift', $shift);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['jam_mulai'] : '08:00:00';
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET waktu_keluar = :waktu_keluar, 
                      latitude_keluar = :latitude_keluar, 
                      longitude_keluar = :longitude_keluar 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':waktu_keluar', $this->waktu_keluar);
        $stmt->bindParam(':latitude_keluar', $this->latitude_keluar);
        $stmt->bindParam(':longitude_keluar', $this->longitude_keluar);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    public function updateLatestCheckOut($user_id, $waktu_keluar, $latitude_keluar, $longitude_keluar) {
        $hour = (int) date('H', strtotime($waktu_keluar));
        $today = date('Y-m-d', strtotime($waktu_keluar));
        
        if ($hour < 6 || $hour < 16) {
            $start_shift = date('Y-m-d 20:00:00', strtotime($waktu_keluar . ' -1 day'));
            $end_shift = "$today 15:00:00";
            $shift_condition = "shift IN ('malam', 'pagi')";
        } else {
            $start_shift = "$today 15:00:00";
            $end_shift = date('Y-m-d 06:00:00', strtotime($waktu_keluar . ' +1 day'));
            $shift_condition = "shift IN ('siang', 'malam')";
        }

        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                  AND waktu_masuk >= :start_shift 
                  AND waktu_masuk < :end_shift 
                  AND waktu_keluar IS NULL 
                  AND $shift_condition 
                  ORDER BY waktu_masuk DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':start_shift', $start_shift);
        $stmt->bindParam(':end_shift', $end_shift);
        $stmt->execute();
        $absensi = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($absensi) {
            $this->id = $absensi['id'];
            $this->waktu_keluar = $waktu_keluar;
            $this->latitude_keluar = $latitude_keluar;
            $this->longitude_keluar = $longitude_keluar;
            return $this->update();
        }
        return false;
    }

    public function readAll($tanggal_awal = null, $tanggal_akhir = null, $shift = null, $user_id = null, $status_telat = null, $page = 1, $limit = 10) {
        $query = "SELECT * FROM " . $this->table_name;
        $conditions = [];
        $params = [];
    
        // Filter tanggal
        if ($tanggal_awal && $tanggal_akhir) {
            $conditions[] = "tanggal BETWEEN :tanggal_awal AND :tanggal_akhir";
            $params[':tanggal_awal'] = $tanggal_awal;
            $params[':tanggal_akhir'] = $tanggal_akhir;
        } elseif ($tanggal_awal) {
            $conditions[] = "tanggal >= :tanggal_awal";
            $params[':tanggal_awal'] = $tanggal_awal;
        } else {
            $today = date('Y-m-d');
            $conditions[] = "tanggal = :today";
            $params[':today'] = $today;
        }
    
        // Filter shift
        if ($shift) {
            $conditions[] = "shift = :shift";
            $params[':shift'] = $shift;
        }
    
        // Filter user_id
        if ($user_id) {
            $conditions[] = "user_id = :user_id";
            $params[':user_id'] = $user_id;
        }
    
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        $query .= " ORDER BY waktu_masuk DESC";
    
        // Hitung total record untuk pagination
        $count_query = "SELECT COUNT(*) FROM " . $this->table_name . (empty($conditions) ? "" : " WHERE " . implode(" AND ", $conditions));
        $count_stmt = $this->conn->prepare($count_query);
        foreach ($params as $key => $value) {
            $count_stmt->bindValue($key, $value);
        }
        $count_stmt->execute();
        $total_records = $count_stmt->fetchColumn();
        $total_pages = ceil($total_records / $limit);
    
        // Tambah LIMIT dan OFFSET
        $offset = ($page - 1) * $limit;
        $query .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
    
        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            error_log("Query: $query, Params: " . json_encode($params));
            $stmt->execute();
            $absensi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error executing query: " . $e->getMessage());
            throw $e;
        }
    
        $lokasi_query = "SELECT nama_lokasi, latitude, longitude, radius FROM tbm_lokasi";
        $lokasi_stmt = $this->conn->prepare($lokasi_query);
        $lokasi_stmt->execute();
        $locations = $lokasi_stmt->fetchAll(PDO::FETCH_ASSOC);
    
        $filtered_list = [];
        foreach ($absensi_list as &$absensi) {
            $waktu_masuk = strtotime($absensi['waktu_masuk']);
            $jam_mulai = $this->getShiftJamMulai($absensi['shift']);
            $jam_mulai_full = strtotime($absensi['tanggal_shift'] . ' ' . $jam_mulai);
            $absensi['status_telat'] = ($waktu_masuk > $jam_mulai_full) ? 'telat' : 'tepat waktu';
    
            if ($status_telat && $absensi['status_telat'] !== $status_telat) {
                continue;
            }
    
            $absen_lat_masuk = $absensi['latitude'];
            $absen_lon_masuk = $absensi['longitude'];
            $absensi['lokasi_masuk'] = 'Tidak Diketahui';
            foreach ($locations as $location) {
                $distance = $this->calculateDistance($absen_lat_masuk, $absen_lon_masuk, $location['latitude'], $location['longitude']);
                if ($distance <= $location['radius']) {
                    $absensi['lokasi_masuk'] = $location['nama_lokasi'];
                    break;
                }
            }
    
            $absensi['lokasi_keluar'] = $absensi['waktu_keluar'] ? 'Tidak Diketahui' : null;
            if ($absensi['waktu_keluar'] && $absensi['latitude_keluar'] && $absensi['longitude_keluar']) {
                $absen_lat_keluar = $absensi['latitude_keluar'];
                $absen_lon_keluar = $absensi['longitude_keluar'];
                foreach ($locations as $location) {
                    $distance = $this->calculateDistance($absen_lat_keluar, $absen_lon_keluar, $location['latitude'], $location['longitude']);
                    if ($distance <= $location['radius']) {
                        $absensi['lokasi_keluar'] = $location['nama_lokasi'];
                        break;
                    }
                }
            }
    
            $filtered_list[] = $absensi;
        }
        unset($absensi);
    
        return [
            'data' => $filtered_list,
            'total_records' => $total_records,
            'total_pages' => $total_pages,
            'current_page' => (int) $page,
            'limit' => (int) $limit
        ];
    }
}
?>