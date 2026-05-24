<?php
// =============================================
// FILE: logout.php
// Proses logout — hapus session dan kembali ke login
// =============================================

session_start();
require_once __DIR__ . '/classes.php';

// Buat object Auth lalu panggil method logout()
$auth = new Auth();
$auth->logout();

// Redirect ke halaman login
header("Location: index.php");
exit;
?>
