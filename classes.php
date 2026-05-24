<?php
require_once __DIR__ . '/config.php';
class Auth {

    private $db;
    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function login($username, $password) {
        if ($username == 'admin' && $password == 'admin123') {
            $_SESSION['user_id'] = 0;
            $_SESSION['nama']    = 'Administrator';
            $_SESSION['role']    = 'admin';
            return true;
        }

        $username = $this->db->escape($username);
        $password = $this->db->escape($password);

        $sql = "SELECT * FROM santri WHERE username = '$username' AND password = '$password'";
        $result = $this->db->query($sql);

        if ($result) {
            $row = $result->fetch_assoc();
            if ($row != null) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['nama']    = $row['nama'];
                $_SESSION['role']    = 'santri';
                return true;
            }
        }

        return false;
    }

    public function logout() {
        session_destroy();
    }
}

class Santri {
    private $db;
    private $nama;
    private $kamar;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getNama() {
        return $this->nama;
    }

    public function getKamar() {
        return $this->kamar;
    }

    public function setNama($nama) {
        $this->nama = $nama;
    }

    public function setKamar($kamar) {
        $this->kamar = $kamar;
    }

    public function getAll($id = 0) {
        $id = (int) $id;

        if ($id > 0) {
            // Ambil 1 santri berdasarkan ID
            $sql = "SELECT * FROM santri WHERE id = $id";
            $result = $this->db->query($sql);

            if ($result) {
                $data = $result->fetch_assoc();
                if ($data != null) {
                    $this->nama  = $data['nama'];
                    $this->kamar = $data['kamar'];
                }

                return $data;
            } else {
                return null;
            }
        } else {
            // Ambil semua santri
            $sql = "SELECT * FROM santri ORDER BY nama ASC";
            $result = $this->db->query($sql);

            if ($result) {
                $data = $result->fetch_all(MYSQLI_ASSOC);
                return $data;
            } else {
                return array();
            }
        }
    }

    // Tambah santri baru
    public function tambah($nama, $kamar, $noHp, $username, $password) {
        $nama     = $this->db->escape($nama);
        $kamar    = $this->db->escape($kamar);
        $noHp     = $this->db->escape($noHp);
        $username = $this->db->escape($username);
        $password = $this->db->escape($password);

        $sql = "INSERT INTO santri (nama, kamar, no_hp, username, password) 
                VALUES ('$nama', '$kamar', '$noHp', '$username', '$password')";

        $result = $this->db->query($sql);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    // Edit santri
    public function edit($id, $noHp) {
        $id    = (int) $id;
        $nama  = $this->db->escape($this->nama);
        $kamar = $this->db->escape($this->kamar);
        $noHp  = $this->db->escape($noHp);

        $sql = "UPDATE santri SET nama = '$nama', kamar = '$kamar', no_hp = '$noHp' WHERE id = $id";

        $result = $this->db->query($sql);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    // Hapus santri
    public function hapus($id) {
        $id = (int) $id;
        $sql = "DELETE FROM santri WHERE id = $id";
        $result = $this->db->query($sql);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}

class Tagihan {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Ambil tagihan
    public function getAll($santriId = 0) {
        $santriId = (int) $santriId;

        if ($santriId > 0) {
            $sql = "SELECT * FROM tagihan WHERE santri_id = $santriId ORDER BY tahun DESC, bulan DESC";
        } else {
            $sql = "SELECT t.*, s.nama, s.kamar 
                    FROM tagihan t 
                    JOIN santri s ON t.santri_id = s.id 
                    ORDER BY t.created_at DESC";
        }

        $result = $this->db->query($sql);

        if ($result) {
            $data = $result->fetch_all(MYSQLI_ASSOC);
            return $data;
        } else {
            return array();
        }
    }

    // Tambah tagihan baru
    public function tambah($santriId, $jenis, $nominal, $bulan, $tahun, $keterangan) {
        $santriId    = (int) $santriId;
        $jenis       = $this->db->escape($jenis);
        $nominal     = (float) $nominal;
        $bulan       = (int) $bulan;
        $tahun       = (int) $tahun;
        $keterangan  = $this->db->escape($keterangan);

        $sql = "INSERT INTO tagihan (santri_id, jenis, nominal, bulan, tahun, keterangan) 
                VALUES ($santriId, '$jenis', $nominal, $bulan, $tahun, '$keterangan')";

        $result = $this->db->query($sql);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    // Hapus tagihan
    public function hapus($id) {
        $id = (int) $id;
        $sql = "DELETE FROM tagihan WHERE id = $id AND status = 'belum'";
        $result = $this->db->query($sql);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    // Bayar tagihan
    public function bayar($id) {
        $id = (int) $id;
        $sql = "UPDATE tagihan SET status = 'lunas' WHERE id = $id";
        $result = $this->db->query($sql);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}

class Laporan extends Tagihan {
    private $db;

    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    public function getDashboard() {
        $bulan = (int) date('n');
        $tahun = (int) date('Y');
        $data = array();

        // Total santri
        $sql = "SELECT COUNT(*) AS total FROM santri";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        $data['total_santri'] = $row['total'];

        // Sudah bayar bulan ini
        $sql = "SELECT COUNT(*) AS total FROM tagihan WHERE bulan = $bulan AND tahun = $tahun AND status = 'lunas'";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        $data['sudah_bayar'] = $row['total'];

        // Belum bayar bulan ini
        $sql = "SELECT COUNT(*) AS total FROM tagihan WHERE bulan = $bulan AND tahun = $tahun AND status = 'belum'";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        $data['belum_bayar'] = $row['total'];

        // Total pemasukan bulan ini
        $sql = "SELECT COALESCE(SUM(nominal), 0) AS total FROM tagihan WHERE status = 'lunas' AND bulan = $bulan AND tahun = $tahun";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        $data['pemasukan'] = (float) $row['total'];

        return $data;
    }

    public function getLaporanBulanan($bulan, $tahun) {
        $bulan = (int) $bulan;
        $tahun = (int) $tahun;

        $sql = "SELECT t.*, s.nama, s.kamar 
                FROM tagihan t 
                JOIN santri s ON t.santri_id = s.id 
                WHERE t.bulan = $bulan AND t.tahun = $tahun 
                ORDER BY s.nama ASC";

        $result = $this->db->query($sql);

        if ($result) {
            $data = $result->fetch_all(MYSQLI_ASSOC);
            return $data;
        } else {
            return array();
        }
    }
}

function formatRupiah($nominal) {
    $hasil = 'Rp ' . number_format($nominal, 0, ',', '.');
    return $hasil;
}

function namaBulan($bulan) {
    $daftarBulan = array(
        1  => 'Januari',
        2  => 'Februari',
        3  => 'Maret',
        4  => 'April',
        5  => 'Mei',
        6  => 'Juni',
        7  => 'Juli',
        8  => 'Agustus',
        9  => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    );

    if (isset($daftarBulan[$bulan])) {
        return $daftarBulan[$bulan];
    } else {
        return '';
    }
}
?>
