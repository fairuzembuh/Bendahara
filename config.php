<?php
// =============================================
// FILE: config.php
// File ini berisi koneksi ke database
// =============================================

// Pengaturan database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bendaharaa');


/**
 * =============================================
 * CLASS: Database
 * =============================================
 * Class ini digunakan untuk membuat koneksi ke database.
 * Menggunakan pola Singleton supaya koneksi hanya dibuat 1 kali.
 *
 * Konsep OOP yang digunakan:
 * - ENCAPSULATION : property $conn dan $instance dibuat PRIVATE
 *                   supaya tidak bisa diakses dari luar class.
 *                   Untuk mengaksesnya harus melalui method getter.
 * - CONSTRUCTOR   : constructor dibuat PRIVATE supaya class ini
 *                   tidak bisa di-new dari luar (Singleton Pattern).
 */
class Database {

    // ---- PROPERTY ----
    // Semua property dibuat PRIVATE (Encapsulation)

    // Property static untuk menyimpan satu-satunya instance
    private static $instance = null;

    // Property untuk menyimpan koneksi database
    private $conn;


    // ---- CONSTRUCTOR ----
    // Constructor PRIVATE supaya tidak bisa dipanggil dari luar class
    private function __construct() {
        // Membuat koneksi ke database MySQL
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Cek apakah koneksi berhasil atau gagal
        if ($this->conn->connect_error) {
            die("Koneksi database gagal: " . $this->conn->connect_error);
        }

        // Set character set ke utf8mb4
        $this->conn->set_charset("utf8mb4");
    }


    // ---- METHOD ----

    // Method static untuk mendapatkan instance Database
    // Kalau belum ada, buat baru. Kalau sudah ada, pakai yang lama.
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Method untuk membersihkan string dari SQL Injection
    public function escape($string) {
        $hasil = $this->conn->real_escape_string($string);
        return $hasil;
    }

    // Method untuk menjalankan query SQL
    public function query($sql) {
        $hasil = $this->conn->query($sql);
        return $hasil;
    }
}
?>
