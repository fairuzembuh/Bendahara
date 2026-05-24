<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/classes.php';

$auth         = new Auth();
$santriModel  = new Santri();
$tagihanModel = new Tagihan();
$laporan      = new Laporan();

$loginError = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi']) && $_POST['aksi'] == 'login') {
    $berhasil = $auth->login($_POST['username'], $_POST['password']);
    if ($berhasil) {
        header("Location: index.php");
        exit;
    } else {
        $loginError = 'Username atau password salah!';
    }
}

$sudahLogin = false;
$role = '';
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'santri')) {
        $sudahLogin = true;
        $role = $_SESSION['role'];
    } else {
        session_destroy();
        session_start();
    }
}

if ($sudahLogin && $role == 'admin' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $aksi = '';
    if (isset($_POST['aksi'])) {
        $aksi = $_POST['aksi'];
    }

    if ($aksi == 'tambah_santri') {
        $santriModel->tambah($_POST['nama'], $_POST['kamar'], $_POST['no_hp'], $_POST['username'], $_POST['password']);
    }

    if ($aksi == 'edit_santri') {
        $id = $_POST['id'];
        $santriModel->setNama($_POST['nama']);
        $santriModel->setKamar($_POST['kamar']);
        $santriModel->edit($id, $_POST['no_hp']);
    }

    if ($aksi == 'hapus_santri') {
        $santriModel->hapus($_POST['id']);
    }

    if ($aksi == 'tambah_tagihan') {
        $tagihanModel->tambah($_POST['santri_id'], $_POST['jenis'], $_POST['nominal'], $_POST['bulan'], $_POST['tahun'], $_POST['keterangan']);
    }

    if ($aksi == 'hapus_tagihan') {
        $tagihanModel->hapus($_POST['id']);
    }

    if ($aksi == 'konfirmasi_bayar') {
        $tagihanModel->bayar($_POST['id']);
    }

    $tab = 'dashboard';
    if (isset($_POST['tab'])) {
        $tab = $_POST['tab'];
    }
    header("Location: index.php?tab=" . $tab);
    exit;
}

if ($sudahLogin && $role == 'santri' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $aksi = '';
    if (isset($_POST['aksi'])) {
        $aksi = $_POST['aksi'];
    }
    if ($aksi == 'bayar') {
        $tagihanModel->bayar($_POST['id']);
    }
    header("Location: index.php");
    exit;
}

$tab = 'dashboard';
if (isset($_GET['tab'])) {
    $tab = $_GET['tab'];
}

$dashboard    = $laporan->getDashboard();
$semuaSantri  = $santriModel->getAll();
$semuaTagihan = $tagihanModel->getAll();

$editSantri = null;
$editNama   = '';
$editKamar  = '';
$editNoHp   = '';
if (isset($_GET['edit_santri'])) {
    $editSantri = $santriModel->getAll($_GET['edit_santri']);
    if ($editSantri != null) {
        $editNama  = $santriModel->getNama();
        $editKamar = $santriModel->getKamar();
        $editNoHp  = $editSantri['no_hp'];
    }
}

$blnFilter = date('n');
if (isset($_GET['bulan'])) {
    $blnFilter = (int) $_GET['bulan'];
}
$thnFilter = date('Y');
if (isset($_GET['tahun'])) {
    $thnFilter = (int) $_GET['tahun'];
}
$laporanData = $laporan->getLaporanBulanan($blnFilter, $thnFilter);

$tagihanSaya = array();
if ($sudahLogin && $role == 'santri') {
    $tagihanSaya = $tagihanModel->getAll($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Bendahara Pondok Pesantren</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; color: #333; }
        nav { background: linear-gradient(135deg, #1a5276, #2980b9); color: white; padding: 0 24px; display: flex; align-items: center; justify-content: space-between; height: 56px; }
        .nav-brand { font-size: 18px; font-weight: bold; }
        .nav-links { display: flex; gap: 4px; }
        .nav-links a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 8px 14px; border-radius: 6px; font-size: 14px; }
        .nav-links a:hover, .nav-links a.active { background: rgba(255,255,255,0.2); color: white; }
        .nav-user { display: flex; align-items: center; gap: 10px; font-size: 14px; }
        .container { max-width: 1100px; margin: 0 auto; padding: 24px 16px; }
        .page-title { font-size: 22px; font-weight: bold; color: #1a5276; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #2980b9; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 24px; margin-bottom: 20px; }
        .card-title { font-size: 16px; font-weight: bold; color: #2c3e50; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #eee; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: white; border-radius: 10px; padding: 20px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-top: 4px solid #2980b9; }
        .stat-card.green { border-top-color: #27ae60; }
        .stat-card.red { border-top-color: #e74c3c; }
        .stat-card.purple { border-top-color: #8e44ad; }
        .stat-num { font-size: 28px; font-weight: bold; color: #2c3e50; }
        .stat-label { font-size: 13px; color: #7f8c8d; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { background: #2980b9; color: white; padding: 10px 12px; text-align: left; }
        td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; }
        tr:hover td { background: #f8f9fa; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #555; margin-bottom: 4px; }
        .form-group input, .form-group select { width: 100%; padding: 9px 12px; border: 1px solid #ddd; border-radius: 7px; font-size: 14px; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }
        .btn { display: inline-block; padding: 8px 16px; border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; color: white; }
        .btn-success { background: #27ae60; } .btn-danger { background: #e74c3c; } .btn-warning { background: #e67e22; }
        .btn-sm { padding: 5px 11px; font-size: 12px; }
        .btn-logout { background: rgba(231,76,60,0.8); color: white; border: none; padding: 6px 14px; border-radius: 6px; cursor: pointer; font-size: 13px; text-decoration: none; }
        .badge-lunas { background: #d5f5e3; color: #1e8449; padding: 3px 10px; border-radius: 20px; font-size: 12px; }
        .badge-belum { background: #fadbd8; color: #a93226; padding: 3px 10px; border-radius: 20px; font-size: 12px; }
        .login-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1a5276 0%, #2980b9 60%, #5dade2 100%); }
        .login-card { background: white; border-radius: 16px; padding: 40px 36px; box-shadow: 0 12px 40px rgba(0,0,0,0.2); width: 100%; max-width: 420px; }
        .login-icon { text-align: center; margin-bottom: 8px; font-size: 48px; }
        .login-title { text-align: center; font-size: 22px; color: #1a5276; margin-bottom: 4px; }
        .login-subtitle { text-align: center; color: #7f8c8d; font-size: 13px; margin-bottom: 28px; }
        .login-error { background: #fadbd8; color: #a93226; padding: 10px 14px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; }
        .btn-login { width: 100%; padding: 12px; background: linear-gradient(135deg, #1a5276, #2980b9); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 8px; }
    </style>
</head>
<body>

<?php
// HALAMAN LOGIN
if ($sudahLogin == false) {
?>
<div class="login-wrap">
    <div class="login-card">
        <div class="login-icon">🕌</div>
        <h1 class="login-title">Bendahara Pondok</h1>
        <p class="login-subtitle">Sistem Pembayaran Pondok Pesantren</p>
        <?php if ($loginError != '') { echo '<div class="login-error">' . $loginError . '</div>'; } ?>
        <form method="POST">
            <input type="hidden" name="aksi" value="login">
            <div class="form-group"><label>Username</label><input type="text" name="username" placeholder="Masukkan username" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" placeholder="Masukkan password" required></div>
            <button class="btn-login" type="submit">Masuk</button>
        </form>
    </div>
</div>

<?php
}

// HALAMAN ADMIN
elseif ($role == 'admin') {
?>
<nav>
    <div class="nav-brand">🕌 Bendahara Pondok</div>
    <div class="nav-links">
        <a href="?tab=dashboard" class="<?php if ($tab == 'dashboard') { echo 'active'; } ?>">Dashboard</a>
        <a href="?tab=santri" class="<?php if ($tab == 'santri') { echo 'active'; } ?>">Santri</a>
        <a href="?tab=tagihan" class="<?php if ($tab == 'tagihan') { echo 'active'; } ?>">Tagihan</a>
        <a href="?tab=laporan" class="<?php if ($tab == 'laporan') { echo 'active'; } ?>">Laporan</a>
    </div>
    <div class="nav-user">
        <span><?php echo $_SESSION['nama']; ?></span>
        <a href="logout.php" class="btn-logout">Keluar</a>
    </div>
</nav>

<div class="container">

<?php if ($tab == 'dashboard') { ?>
    <h2 class="page-title">📊 Dashboard</h2>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-num"><?php echo $dashboard['total_santri']; ?></div><div class="stat-label">Total Santri</div></div>
        <div class="stat-card green"><div class="stat-num"><?php echo $dashboard['sudah_bayar']; ?></div><div class="stat-label">Sudah Bayar</div></div>
        <div class="stat-card red"><div class="stat-num"><?php echo $dashboard['belum_bayar']; ?></div><div class="stat-label">Belum Bayar</div></div>
        <div class="stat-card purple"><div class="stat-num"><?php echo formatRupiah($dashboard['pemasukan']); ?></div><div class="stat-label">Pemasukan</div></div>
    </div>

    <div class="card">
        <div class="card-title">📋 Tagihan Terbaru</div>
        <table>
            <tr><th>Nama</th><th>Jenis</th><th>Bulan</th><th>Nominal</th><th>Status</th></tr>
            <?php
            $counter = 0;
            foreach ($semuaTagihan as $t) {
                if ($counter >= 5) { break; }
            ?>
            <tr>
                <td><?php echo htmlspecialchars($t['nama']); ?></td>
                <td><?php echo ucfirst($t['jenis']); ?></td>
                <td><?php echo namaBulan($t['bulan']) . ' ' . $t['tahun']; ?></td>
                <td><?php echo formatRupiah($t['nominal']); ?></td>
                <td><?php if ($t['status'] == 'lunas') { echo '<span class="badge-lunas">Lunas</span>'; } else { echo '<span class="badge-belum">Belum</span>'; } ?></td>
            </tr>
            <?php $counter++; } ?>
        </table>
    </div>
<?php } ?>


<?php if ($tab == 'santri') { ?>
    <h2 class="page-title">👨‍🎓 Kelola Santri</h2>
    <div class="card">
        <?php if ($editSantri != null) { echo '<div class="card-title">✏️ Edit Santri</div>'; } else { echo '<div class="card-title">➕ Tambah Santri</div>'; } ?>
        <form method="POST">
            <?php
            if ($editSantri != null) {
                echo '<input type="hidden" name="aksi" value="edit_santri">';
                echo '<input type="hidden" name="id" value="' . $editSantri['id'] . '">';
            } else {
                echo '<input type="hidden" name="aksi" value="tambah_santri">';
            }
            ?>
            <input type="hidden" name="tab" value="santri">
            <div class="form-row">
                <div class="form-group"><label>Nama</label><input type="text" name="nama" required placeholder="Nama lengkap" value="<?php echo htmlspecialchars($editNama); ?>"></div>
                <div class="form-group"><label>Kamar</label><input type="text" name="kamar" required placeholder="Asrama / kamar" value="<?php echo htmlspecialchars($editKamar); ?>"></div>
                <div class="form-group"><label>No. HP</label><input type="text" name="no_hp" placeholder="08xxx" value="<?php echo htmlspecialchars($editNoHp); ?>"></div>
            </div>
            <?php if ($editSantri == null) { ?>
            <div class="form-row">
                <div class="form-group"><label>Username</label><input type="text" name="username" required placeholder="Contoh: rizki"></div>
                <div class="form-group"><label>Password</label><input type="text" name="password" required placeholder="Contoh: rizki123"></div>
            </div>
            <?php } ?>
            <?php
            if ($editSantri != null) {
                echo '<button class="btn btn-warning" type="submit">Simpan</button> <a href="?tab=santri" class="btn btn-danger">Batal</a>';
            } else {
                echo '<button class="btn btn-success" type="submit">Tambah</button>';
            }
            ?>
        </form>
    </div>

    <div class="card">
        <div class="card-title">📋 Daftar Santri (<?php echo count($semuaSantri); ?>)</div>
        <table>
            <tr><th>No</th><th>Nama</th><th>Kamar</th><th>No. HP</th><th>Username</th><th>Aksi</th></tr>
            <?php
            $nomor = 1;
            foreach ($semuaSantri as $s) {
            ?>
            <tr>
                <td><?php echo $nomor; ?></td>
                <td><?php echo htmlspecialchars($s['nama']); ?></td>
                <td><?php echo htmlspecialchars($s['kamar']); ?></td>
                <td><?php echo htmlspecialchars($s['no_hp']); ?></td>
                <td><?php echo htmlspecialchars($s['username']); ?></td>
                <td>
                    <a href="?tab=santri&edit_santri=<?php echo $s['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Hapus santri ini?')">
                        <input type="hidden" name="aksi" value="hapus_santri">
                        <input type="hidden" name="tab" value="santri">
                        <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                        <button class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                </td>
            </tr>
            <?php $nomor++; } ?>
        </table>
    </div>
<?php } ?>


<?php if ($tab == 'tagihan') { ?>
    <h2 class="page-title">💰 Kelola Tagihan</h2>
    <div class="card">
        <div class="card-title">➕ Buat Tagihan</div>
        <form method="POST">
            <input type="hidden" name="aksi" value="tambah_tagihan">
            <input type="hidden" name="tab" value="tagihan">
            <div class="form-row">
                <div class="form-group"><label>Santri</label>
                    <select name="santri_id" required>
                        <option value="">— Pilih Santri —</option>
                        <?php foreach ($semuaSantri as $s) { echo '<option value="' . $s['id'] . '">' . htmlspecialchars($s['nama']) . '</option>'; } ?>
                    </select>
                </div>
                <div class="form-group"><label>Jenis</label>
                    <select name="jenis"><option value="bulanan">Bulanan</option><option value="insidental">Insidental</option></select>
                </div>
                <div class="form-group"><label>Nominal (Rp)</label><input type="number" name="nominal" required placeholder="200000" min="1000"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Bulan</label>
                    <select name="bulan">
                        <?php for ($i = 1; $i <= 12; $i++) { $sel = ''; if ($i == date('n')) { $sel = 'selected'; } echo '<option value="' . $i . '" ' . $sel . '>' . namaBulan($i) . '</option>'; } ?>
                    </select>
                </div>
                <div class="form-group"><label>Tahun</label><input type="number" name="tahun" value="<?php echo date('Y'); ?>" required></div>
                <div class="form-group"><label>Keterangan</label><input type="text" name="keterangan" placeholder="SPP Bulan Mei 2026"></div>
            </div>
            <button class="btn btn-success" type="submit">Tambah Tagihan</button>
        </form>
    </div>

    <div class="card">
        <div class="card-title">📋 Daftar Tagihan</div>
        <table>
            <tr><th>Nama</th><th>Jenis</th><th>Bulan</th><th>Nominal</th><th>Status</th><th>Aksi</th></tr>
            <?php foreach ($semuaTagihan as $t) { ?>
            <tr>
                <td><?php echo htmlspecialchars($t['nama']); ?></td>
                <td><?php echo ucfirst($t['jenis']); ?></td>
                <td><?php echo namaBulan($t['bulan']) . ' ' . $t['tahun']; ?></td>
                <td><?php echo formatRupiah($t['nominal']); ?></td>
                <td><?php if ($t['status'] == 'lunas') { echo '<span class="badge-lunas">Lunas</span>'; } else { echo '<span class="badge-belum">Belum</span>'; } ?></td>
                <td>
                    <?php if ($t['status'] == 'belum') { ?>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Tandai LUNAS?')">
                            <input type="hidden" name="aksi" value="konfirmasi_bayar"><input type="hidden" name="tab" value="tagihan"><input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                            <button class="btn btn-success btn-sm">✓ Lunas</button>
                        </form>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Hapus tagihan?')">
                            <input type="hidden" name="aksi" value="hapus_tagihan"><input type="hidden" name="tab" value="tagihan"><input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                            <button class="btn btn-danger btn-sm">Hapus</button>
                        </form>
                    <?php } else { echo '<span style="color:#999;">—</span>'; } ?>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>
<?php } ?>


<?php if ($tab == 'laporan') { ?>
    <h2 class="page-title">📋 Laporan Keuangan</h2>
    <div class="card">
        <div class="card-title">Filter Laporan</div>
        <form method="GET">
            <input type="hidden" name="tab" value="laporan">
            <div class="form-row">
                <div class="form-group"><label>Bulan</label>
                    <select name="bulan">
                        <?php for ($i = 1; $i <= 12; $i++) { $sel = ''; if ($i == $blnFilter) { $sel = 'selected'; } echo '<option value="' . $i . '" ' . $sel . '>' . namaBulan($i) . '</option>'; } ?>
                    </select>
                </div>
                <div class="form-group"><label>Tahun</label><input type="number" name="tahun" value="<?php echo $thnFilter; ?>"></div>
                <div class="form-group"><label>&nbsp;</label><button class="btn btn-success">Tampilkan</button></div>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-title">Laporan <?php echo namaBulan($blnFilter) . ' ' . $thnFilter; ?></div>
        <?php
        $totalLunas = 0;
        $totalBelum = 0;
        foreach ($laporanData as $t) {
            if ($t['status'] == 'lunas') { $totalLunas = $totalLunas + $t['nominal']; }
            else { $totalBelum = $totalBelum + $t['nominal']; }
        }
        ?>
        <div class="stats-grid">
            <div class="stat-card green"><div class="stat-num"><?php echo formatRupiah($totalLunas); ?></div><div class="stat-label">Total Lunas</div></div>
            <div class="stat-card red"><div class="stat-num"><?php echo formatRupiah($totalBelum); ?></div><div class="stat-label">Total Belum Bayar</div></div>
        </div>
        <table>
            <tr><th>Nama</th><th>Kamar</th><th>Jenis</th><th>Nominal</th><th>Status</th></tr>
            <?php
            if (count($laporanData) == 0) {
                echo '<tr><td colspan="5" style="text-align:center;color:#999;">Tidak ada data.</td></tr>';
            } else {
                foreach ($laporanData as $t) {
            ?>
            <tr>
                <td><?php echo htmlspecialchars($t['nama']); ?></td>
                <td><?php echo htmlspecialchars($t['kamar']); ?></td>
                <td><?php echo ucfirst($t['jenis']); ?></td>
                <td><?php echo formatRupiah($t['nominal']); ?></td>
                <td><?php if ($t['status'] == 'lunas') { echo '<span class="badge-lunas">Lunas</span>'; } else { echo '<span class="badge-belum">Belum</span>'; } ?></td>
            </tr>
            <?php } } ?>
        </table>
    </div>
<?php } ?>

</div>

<?php
}

// HALAMAN SANTRI
elseif ($role == 'santri') {
?>
<nav>
    <div class="nav-brand">🕌 Bendahara Pondok</div>
    <div class="nav-links"><a href="?tab=dashboard" class="active">Tagihan Saya</a></div>
    <div class="nav-user">
        <span><?php echo $_SESSION['nama']; ?></span>
        <a href="logout.php" class="btn-logout">Keluar</a>
    </div>
</nav>

<div class="container">
    <h2 class="page-title">🏠 Tagihan Saya</h2>
    <?php
    $belumBayar = array();
    $sudahLunas = array();
    $totalHutang = 0;
    foreach ($tagihanSaya as $t) {
        if ($t['status'] == 'belum') {
            $belumBayar[] = $t;
            $totalHutang = $totalHutang + $t['nominal'];
        } else {
            $sudahLunas[] = $t;
        }
    }
    ?>

    <div class="stats-grid">
        <div class="stat-card red"><div class="stat-num"><?php echo count($belumBayar); ?></div><div class="stat-label">Belum Bayar</div></div>
        <div class="stat-card green"><div class="stat-num"><?php echo count($sudahLunas); ?></div><div class="stat-label">Sudah Lunas</div></div>
        <div class="stat-card purple"><div class="stat-num"><?php echo formatRupiah($totalHutang); ?></div><div class="stat-label">Total Belum Dibayar</div></div>
    </div>

    <div class="card">
        <div class="card-title">❌ Tagihan Belum Bayar</div>
        <?php if (count($belumBayar) == 0) { echo '<p style="color:#27ae60;font-weight:bold;">🎉 Semua tagihan sudah lunas!</p>'; } else { ?>
        <table>
            <tr><th>Jenis</th><th>Bulan</th><th>Nominal</th><th>Keterangan</th><th>Aksi</th></tr>
            <?php foreach ($belumBayar as $t) { ?>
            <tr>
                <td><?php echo ucfirst($t['jenis']); ?></td>
                <td><?php echo namaBulan($t['bulan']) . ' ' . $t['tahun']; ?></td>
                <td><strong><?php echo formatRupiah($t['nominal']); ?></strong></td>
                <td><?php echo htmlspecialchars($t['keterangan']); ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Bayar tagihan <?php echo formatRupiah($t['nominal']); ?>?')">
                        <input type="hidden" name="aksi" value="bayar"><input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                        <button class="btn btn-success btn-sm">💳 Bayar</button>
                    </form>
                </td>
            </tr>
            <?php } ?>
        </table>
        <?php } ?>
    </div>

    <div class="card">
        <div class="card-title">✅ Riwayat Tagihan Lunas</div>
        <?php if (count($sudahLunas) == 0) { echo '<p style="color:#999;">Belum ada tagihan yang lunas.</p>'; } else { ?>
        <table>
            <tr><th>Jenis</th><th>Bulan</th><th>Nominal</th><th>Keterangan</th><th>Status</th></tr>
            <?php foreach ($sudahLunas as $t) { ?>
            <tr>
                <td><?php echo ucfirst($t['jenis']); ?></td>
                <td><?php echo namaBulan($t['bulan']) . ' ' . $t['tahun']; ?></td>
                <td><?php echo formatRupiah($t['nominal']); ?></td>
                <td><?php echo htmlspecialchars($t['keterangan']); ?></td>
                <td><span class="badge-lunas">Lunas</span></td>
            </tr>
            <?php } ?>
        </table>
        <?php } ?>
    </div>
</div>

<?php } ?>

</body>
</html>
