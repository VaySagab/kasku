<?php
// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Log start of script
error_log("=== ADMIN_TRANSACTIONS.PHP STARTED ===");
error_log("Session Role: " . ($_SESSION['role'] ?? 'NOT SET'));
error_log("Admin ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));
// Define access constant for includes
define('ALLOW_ACCESS', true);

require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/function.php';

// Session sudah dimulai di function.php

// Protect route: allow only admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$admin_id = $_SESSION['user_id'];
$admin_username = $_SESSION['username'];

$message = '';
$message_type = '';

// Get filter parameters
$filter_kelas = isset($_GET['kelas']) ? intval($_GET['kelas']) : 0;
$filter_jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';

// Check for message from URL parameters (for redirects)
if (isset($_GET['message']) && isset($_GET['message_type'])) {
    $message = urldecode($_GET['message']);
    $message_type = $_GET['message_type'];
}

// Fungsi untuk mendapatkan data kas berdasarkan kelas - VERSI DIPERBAIKI
function getKasByKelas($conn, $id_kelas) {
    // Gunakan query langsung untuk menghindari masalah prepared statement
    $sql = "SELECT id_kas FROM kas WHERE id_kelas = $id_kelas";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        error_log("Error in getKasByKelas query: " . mysqli_error($conn));
        return null;
    }
    
    $row = mysqli_fetch_assoc($result);
    $id_kas = $row ? $row['id_kas'] : null;
    
    error_log("getKasByKelas: id_kelas=$id_kelas -> id_kas=$id_kas");
    
    return $id_kas;
}

// DEBUG: Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle Add Transaction - DEBUG DETAIL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_transaksi'])) {
    
    // Ambil data dari POST
    $id_kelas = isset($_POST['id_kelas']) ? intval($_POST['id_kelas']) : 0;
    $jenis = isset($_POST['jenis']) ? trim($_POST['jenis']) : '';
    $jumlah = isset($_POST['jumlah']) ? floatval($_POST['jumlah']) : 0;
    $tanggal = isset($_POST['tanggal']) ? trim($_POST['tanggal']) : '';
    $deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
    
    error_log("=== PROSES TAMBAH TRANSAKSI DETAIL ===");
    error_log("Data POST: id_kelas=$id_kelas, jenis=$jenis, jumlah=$jumlah, tanggal=$tanggal");
    
    // Validasi input
    $errors = [];
    
    if ($id_kelas <= 0) {
        $errors[] = "Pilih kelas terlebih dahulu";
    }
    
    if (!in_array($jenis, ['pemasukan', 'pengeluaran'])) {
        $errors[] = "Jenis transaksi tidak valid";
    }
    
    if ($jumlah <= 0) {
        $errors[] = "Jumlah harus lebih dari 0";
    }
    
    if (empty($tanggal)) {
        $errors[] = "Tanggal tidak boleh kosong";
    }
    
    if (empty($errors)) {
        // Dapatkan id_kas dari id_kelas
        error_log("Mencari id_kas untuk id_kelas: $id_kelas");
        $id_kas = getKasByKelas($conn, $id_kelas);
        
        if ($id_kas) {
            error_log("ID Kas ditemukan: $id_kas");
            
            // Test koneksi database
            if (!$conn) {
                error_log("KONEKSI DATABASE NULL!");
                $message = "Koneksi database gagal!";
                $message_type = 'danger';
            } else {
                // Test query sederhana
                $test_query = "SELECT 1 as test";
                $test_result = mysqli_query($conn, $test_query);
                if (!$test_result) {
                    error_log("TEST QUERY GAGAL: " . mysqli_error($conn));
                    $message = "Koneksi database bermasalah: " . mysqli_error($conn);
                    $message_type = 'danger';
                } else {
                    error_log("Test query berhasil");
                    
                    // Insert transaksi
                    $deskripsi_safe = mysqli_real_escape_string($conn, $deskripsi);
                    $sql = "INSERT INTO transaksi (id_kas, jenis, jumlah, tanggal, deskripsi, created_by) 
                            VALUES ($id_kas, '$jenis', $jumlah, '$tanggal', '$deskripsi_safe', $admin_id)";
                    
                    error_log("SQL Insert: $sql");
                    
                    if (mysqli_query($conn, $sql)) {
                        $insert_id = mysqli_insert_id($conn);
                        error_log("Insert BERHASIL! ID: $insert_id");
                        
                        // Update saldo kas
                        if ($jenis === 'pemasukan') {
                            $update_sql = "UPDATE kas SET saldo = saldo + $jumlah WHERE id_kas = $id_kas";
                        } else {
                            $update_sql = "UPDATE kas SET saldo = saldo - $jumlah WHERE id_kas = $id_kas";
                        }
                        
                        error_log("Update SQL: $update_sql");
                        
                        if (mysqli_query($conn, $update_sql)) {
                            error_log("Update saldo BERHASIL!");
                            
                            // Cek data yang baru dimasukkan
                            $check_sql = "SELECT * FROM transaksi WHERE id_transaksi = $insert_id";
                            $check_result = mysqli_query($conn, $check_sql);
                            $new_transaction = mysqli_fetch_assoc($check_result);
                            
                            if ($new_transaction) {
                                error_log("Data transaksi baru ditemukan: ID " . $new_transaction['id_transaksi']);
                            } else {
                                error_log("DATA TRANSAKSI BARU TIDAK DITEMUKAN SETELAH INSERT!");
                            }
                            
                            // Success - redirect
                            $redirect_url = "admin_transactions.php?message=" . urlencode("Transaksi berhasil ditambahkan! ID: $insert_id") . "&message_type=success";
                            if ($filter_kelas > 0) $redirect_url .= "&kelas=" . $filter_kelas;
                            if (!empty($filter_jenis)) $redirect_url .= "&jenis=" . urlencode($filter_jenis);
                            if (!empty($filter_bulan)) $redirect_url .= "&bulan=" . urlencode($filter_bulan);
                            
                            error_log("Redirect ke: $redirect_url");
                            header("Location: " . $redirect_url);
                            exit;
                        } else {
                            $error_msg = "Error update saldo: " . mysqli_error($conn);
                            error_log($error_msg);
                            $message = $error_msg;
                            $message_type = 'danger';
                        }
                    } else {
                        $error_msg = "Error insert transaksi: " . mysqli_error($conn);
                        error_log($error_msg);
                        $message = $error_msg;
                        $message_type = 'danger';
                    }
                }
            }
        } else {
            $message = "Kas untuk kelas ini tidak ditemukan!";
            $message_type = 'danger';
            error_log("Kas tidak ditemukan untuk id_kelas: $id_kelas");
            
            // Debug: cek data kas yang ada
            $debug_sql = "SELECT * FROM kas WHERE id_kelas = $id_kelas";
            $debug_result = mysqli_query($conn, $debug_sql);
            $debug_data = mysqli_fetch_assoc($debug_result);
            error_log("Debug kas data: " . print_r($debug_data, true));
        }
    } else {
        $message = "Validasi gagal: " . implode(", ", $errors);
        $message_type = 'danger';
        error_log("Validasi gagal: " . implode(", ", $errors));
    }
}

// Handle Edit Transaction - VERSI SUPER SIMPLE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_transaksi'])) {
    $id_transaksi = isset($_POST['id_transaksi']) ? intval($_POST['id_transaksi']) : 0;
    $id_kelas = isset($_POST['id_kelas']) ? intval($_POST['id_kelas']) : 0;
    $jenis = isset($_POST['jenis']) ? trim($_POST['jenis']) : '';
    $jumlah = isset($_POST['jumlah']) ? floatval($_POST['jumlah']) : 0;
    $tanggal = isset($_POST['tanggal']) ? trim($_POST['tanggal']) : '';
    $deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
    
    error_log("=== PROSES EDIT TRANSAKSI ===");
    error_log("Data: id=$id_transaksi, id_kelas=$id_kelas, jenis=$jenis, jumlah=$jumlah");
    
    // Validasi input
    if ($id_transaksi <= 0) {
        $message = "ID transaksi tidak valid";
        $message_type = 'danger';
    } elseif ($id_kelas <= 0) {
        $message = "Pilih kelas terlebih dahulu";
        $message_type = 'danger';
    } elseif (!in_array($jenis, ['pemasukan', 'pengeluaran'])) {
        $message = "Jenis transaksi tidak valid";
        $message_type = 'danger';
    } elseif ($jumlah <= 0) {
        $message = "Jumlah harus lebih dari 0";
        $message_type = 'danger';
    } elseif (empty($tanggal)) {
        $message = "Tanggal tidak boleh kosong";
        $message_type = 'danger';
    } else {
        // Dapatkan id_kas dari id_kelas
        $id_kas = getKasByKelas($conn, $id_kelas);
        
        if ($id_kas) {
            // Update transaksi menggunakan query langsung
            $sql = "UPDATE transaksi SET id_kas = $id_kas, jenis = '$jenis', jumlah = $jumlah, 
                    tanggal = '$tanggal', deskripsi = '$deskripsi' 
                    WHERE id_transaksi = $id_transaksi";
            
            error_log("SQL Update: $sql");
            
            if (mysqli_query($conn, $sql)) {
                error_log("Update transaksi BERHASIL!");
                
                // Redirect dengan parameter
                $redirect_url = "admin_transactions.php?message=" . urlencode("Transaksi berhasil diupdate!") . "&message_type=success";
                if ($filter_kelas > 0) $redirect_url .= "&kelas=" . $filter_kelas;
                if (!empty($filter_jenis)) $redirect_url .= "&jenis=" . urlencode($filter_jenis);
                if (!empty($filter_bulan)) $redirect_url .= "&bulan=" . urlencode($filter_bulan);
                
                header("Location: " . $redirect_url);
                exit;
            } else {
                $message = "Error update: " . mysqli_error($conn);
                $message_type = 'danger';
            }
        } else {
            $message = "Kas untuk kelas ini tidak ditemukan!";
            $message_type = 'danger';
        }
    }
}

// Handle Delete Transaction - VERSI SUPER SIMPLE
if (isset($_GET['hapus'])) {
    $id_transaksi = intval($_GET['hapus']);
    
    error_log("=== PROSES HAPUS TRANSAKSI ===");
    error_log("ID Transaksi: $id_transaksi");
    
    // Dapatkan data transaksi untuk koreksi saldo
    $sql = "SELECT jenis, jumlah, id_kas FROM transaksi WHERE id_transaksi = $id_transaksi";
    $result = mysqli_query($conn, $sql);
    $trans_data = mysqli_fetch_assoc($result);
    
    if ($trans_data) {
        // Hapus transaksi
        $delete_sql = "DELETE FROM transaksi WHERE id_transaksi = $id_transaksi";
        
        if (mysqli_query($conn, $delete_sql)) {
            // Update saldo manual
            if ($trans_data['jenis'] === 'pemasukan') {
                $update_sql = "UPDATE kas SET saldo = saldo - {$trans_data['jumlah']} WHERE id_kas = {$trans_data['id_kas']}";
            } else {
                $update_sql = "UPDATE kas SET saldo = saldo + {$trans_data['jumlah']} WHERE id_kas = {$trans_data['id_kas']}";
            }
            
            mysqli_query($conn, $update_sql);
            
            $redirect_url = "admin_transactions.php?message=Transaksi+berhasil+dihapus&message_type=success";
            if (isset($_GET['kelas']) && $_GET['kelas'] != 0) $redirect_url .= "&kelas=" . $_GET['kelas'];
            if (isset($_GET['jenis']) && !empty($_GET['jenis'])) $redirect_url .= "&jenis=" . urlencode($_GET['jenis']);
            if (isset($_GET['bulan']) && !empty($_GET['bulan'])) $redirect_url .= "&bulan=" . urlencode($_GET['bulan']);
            
            header("Location: " . $redirect_url);
            exit;
        }
    }
}

// Get all transactions with kas saldo - GUNAKAN QUERY LANGSUNG
$transactions = [];

// Build query dasar
$sql = "SELECT t.*, k.nama_kelas, k.id_kelas, kas.saldo
        FROM transaksi t
        JOIN kas ON t.id_kas = kas.id_kas
        JOIN kelas k ON kas.id_kelas = k.id_kelas
        WHERE k.id_admin = $admin_id";

// Tambahkan filter
if ($filter_kelas > 0) {
    $sql .= " AND k.id_kelas = $filter_kelas";
}

if (!empty($filter_jenis)) {
    $filter_jenis_escaped = mysqli_real_escape_string($conn, $filter_jenis);
    $sql .= " AND t.jenis = '$filter_jenis_escaped'";
}

if (!empty($filter_bulan)) {
    $filter_bulan_escaped = mysqli_real_escape_string($conn, $filter_bulan);
    $sql .= " AND DATE_FORMAT(t.tanggal, '%Y-%m') = '$filter_bulan_escaped'";
}

$sql .= " ORDER BY t.tanggal DESC, t.created_at DESC";

error_log("SQL Query Transactions: $sql");

// Execute query langsung
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $transactions[] = $row;
    }
} else {
    error_log("Error in transactions query: " . mysqli_error($conn));
}

// Get classes for filter and form - GUNAKAN QUERY LANGSUNG
$classes = [];
$sql_classes = "SELECT id_kelas, nama_kelas FROM kelas WHERE id_admin = $admin_id ORDER BY nama_kelas";
$result_classes = mysqli_query($conn, $sql_classes);
if ($result_classes) {
    while ($row = mysqli_fetch_assoc($result_classes)) {
        $classes[] = $row;
    }
} else {
    error_log("Error in classes query: " . mysqli_error($conn));
}

// Calculate statistics
$total_pemasukan = 0;
$total_pengeluaran = 0;

foreach ($transactions as $trans) {
    if ($trans['jenis'] === 'pemasukan') {
        $total_pemasukan += $trans['jumlah'];
    } else {
        $total_pengeluaran += $trans['jumlah'];
    }
}

$saldo_kas = $total_pemasukan - $total_pengeluaran;
$jumlah_transaksi = count($transactions);

$pageTitle = "Manajemen Transaksi - Admin KasKelas";
include 'includes/admin_header.php';
?>

<!-- Debug Info Section - DETAILED -->
<div class="container mt-3">
    <div class="alert alert-info">
        <h5>🛠️ Detailed Debug Information</h5>
        <p><strong>Admin ID:</strong> <?php echo $admin_id; ?></p>
        <p><strong>Jumlah Kelas:</strong> <?php echo count($classes); ?></p>
        <p><strong>Jumlah Transaksi:</strong> <?php echo count($transactions); ?></p>
        <p><strong>Total Saldo:</strong> <?php echo format_rupiah($saldo_kas); ?></p>
        <p><strong>Koneksi Database:</strong> <?php echo $conn ? 'OK' : 'GAGAL'; ?></p>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <hr>
            <p><strong>POST Data Received:</strong></p>
            <ul>
                <li>id_kelas: <?php echo $_POST['id_kelas'] ?? 'NULL'; ?></li>
                <li>jenis: <?php echo $_POST['jenis'] ?? 'NULL'; ?></li>
                <li>jumlah: <?php echo $_POST['jumlah'] ?? 'NULL'; ?></li>
                <li>tanggal: <?php echo $_POST['tanggal'] ?? 'NULL'; ?></li>
                <li>deskripsi: <?php echo $_POST['deskripsi'] ?? 'NULL'; ?></li>
            </ul>
            
            <?php
            // Debug: cek id_kas untuk id_kelas yang dipilih
            if (isset($_POST['id_kelas'])) {
                $debug_id_kelas = intval($_POST['id_kelas']);
                $debug_id_kas = getKasByKelas($conn, $debug_id_kelas);
                echo "<p><strong>ID Kas untuk Kelas {$debug_id_kelas}:</strong> " . ($debug_id_kas ? $debug_id_kas : 'TIDAK DITEMUKAN') . "</p>";
                
                // Debug: cek data kas
                $debug_sql = "SELECT * FROM kas WHERE id_kelas = $debug_id_kelas";
                $debug_result = mysqli_query($conn, $debug_sql);
                $debug_data = mysqli_fetch_assoc($debug_result);
                echo "<p><strong>Data Kas:</strong> " . ($debug_data ? print_r($debug_data, true) : 'TIDAK ADA DATA') . "</p>";
            }
            ?>
        <?php endif; ?>
        
        <?php if (!empty($message)): ?>
            <hr>
            <p><strong>System Message:</strong> <?php echo $message; ?></p>
            <p><strong>Message Type:</strong> <?php echo $message_type; ?></p>
        <?php endif; ?>
        
        <hr>
        <p><strong>Last 5 Transactions from Database:</strong></p>
        <?php
        $recent_sql = "SELECT id_transaksi, id_kas, jenis, jumlah, tanggal FROM transaksi ORDER BY id_transaksi DESC LIMIT 5";
        $recent_result = mysqli_query($conn, $recent_sql);
        if ($recent_result && mysqli_num_rows($recent_result) > 0) {
            echo "<ul>";
            while ($row = mysqli_fetch_assoc($recent_result)) {
                echo "<li>ID: {$row['id_transaksi']}, Kas: {$row['id_kas']}, {$row['jenis']}, {$row['jumlah']}, {$row['tanggal']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No transactions found</p>";
        }
        ?>
    </div>
</div>

<!-- [REST OF YOUR HTML CODE REMAINS EXACTLY THE SAME] -->
<!-- Professional Admin Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark kas-navbar">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="admin.php">
            <i class="bi bi-wallet2 me-2"></i>
            <span class="fw-bold">Kas<span class="text-warning">Kelas</span></span>
            <span class="ms-2 badge bg-warning text-dark">Admin</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="admin.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_classes.php">
                        <i class="bi bi-people me-1"></i>Kelas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_members.php">
                        <i class="bi bi-person-badge me-1"></i>Anggota
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="admin_transactions.php">
                        <i class="bi bi-cash-stack me-1"></i>Transaksi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_reports.php">
                        <i class="bi bi-file-earmark-bar-graph me-1"></i>Laporan
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($admin_username); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="landing.php"><i class="bi bi-house me-2"></i>Beranda</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>


<!-- Main Content -->
<main class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1 fw-bold">
                        <i class="bi bi-cash-stack text-primary me-2"></i>Manajemen Transaksi
                    </h2>
                    <p class="text-muted mb-0">Kelola semua transaksi pemasukan dan pengeluaran kelas</p>
                </div>
                <div>
                    <button class="btn kas-btn kas-btn-primary" data-bs-toggle="modal" data-bs-target="#tambahTransaksiModal">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Transaksi
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($message)): ?>
        <div class="alert kas-alert kas-alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <i class="bi <?php echo $message_type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="kas-stats">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kas-stats-title">Total Pemasukan</div>
                        <h3 class="kas-stats-value text-success"><?php echo format_rupiah($total_pemasukan); ?></h3>
                        <small class="text-success"><i class="bi bi-arrow-up"></i> Semua Transaksi</small>
                    </div>
                    <div class="kas-stats-icon income">
                        <i class="bi bi-arrow-down-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="kas-stats">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kas-stats-title">Total Pengeluaran</div>
                        <h3 class="kas-stats-value text-danger"><?php echo format_rupiah($total_pengeluaran); ?></h3>
                        <small class="text-danger"><i class="bi bi-arrow-down"></i> Semua Transaksi</small>
                    </div>
                    <div class="kas-stats-icon expense">
                        <i class="bi bi-arrow-up-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="kas-stats">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kas-stats-title">Saldo Kas</div>
                        <h3 class="kas-stats-value text-primary"><?php echo format_rupiah($saldo_kas); ?></h3>
                        <small class="text-info"><i class="bi bi-wallet2"></i> Net Balance</small>
                    </div>
                    <div class="kas-stats-icon balance">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kas-stats">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kas-stats-title">Jumlah Transaksi</div>
                        <h3 class="kas-stats-value text-info"><?php echo $jumlah_transaksi; ?></h3>
                        <small class="text-info"><i class="bi bi-list-ul"></i> Total Transaksi</small>
                    </div>
                    <div class="kas-stats-icon count">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="kas-card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="kelas" class="form-label fw-semibold">Filter Kelas</label>
                            <select name="kelas" id="kelas" class="form-select kas-form-control">
                                <option value="0">Semua Kelas</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id_kelas']; ?>" <?php echo $filter_kelas == $class['id_kelas'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['nama_kelas']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="jenis" class="form-label fw-semibold">Jenis Transaksi</label>
                            <select name="jenis" id="jenis" class="form-select kas-form-control">
                                <option value="">Semua Jenis</option>
                                <option value="pemasukan" <?php echo $filter_jenis === 'pemasukan' ? 'selected' : ''; ?>>Pemasukan</option>
                                <option value="pengeluaran" <?php echo $filter_jenis === 'pengeluaran' ? 'selected' : ''; ?>>Pengeluaran</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="bulan" class="form-label fw-semibold">Bulan</label>
                            <input type="month" name="bulan" id="bulan" class="form-control kas-form-control" value="<?php echo htmlspecialchars($filter_bulan); ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn kas-btn kas-btn-primary w-100">
                                <i class="bi bi-search me-2"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions List -->
    <div class="row">
        <div class="col-12">
            <div class="kas-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-ul me-2"></i>Daftar Transaksi (<?php echo count($transactions); ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($transactions)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                            <p class="text-muted mt-3">Tidak ada transaksi ditemukan</p>
                            <?php if ($filter_kelas > 0 || !empty($filter_jenis) || !empty($filter_bulan)): ?>
                                <p class="text-muted">Coba ubah filter atau <a href="admin_transactions.php" class="text-primary">tampilkan semua transaksi</a></p>
                            <?php else: ?>
                                <button class="btn kas-btn kas-btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#tambahTransaksiModal">
                                    <i class="bi bi-plus-circle me-2"></i>Tambah Transaksi Pertama
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table kas-table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tanggal</th>
                                        <th>Kelas</th>
                                        <th>Jenis</th>
                                        <th>Jumlah</th>
                                        <th>Deskripsi</th>
                                        <th>Saldo Kelas</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($transactions as $trans): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td>
                                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($trans['tanggal'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($trans['nama_kelas']); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($trans['jenis'] === 'pemasukan'): ?>
                                                    <span class="badge bg-success">Pemasukan</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Pengeluaran</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong class="<?php echo $trans['jenis'] === 'pemasukan' ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo $trans['jenis'] === 'pemasukan' ? '+' : '-'; ?>
                                                    <?php echo format_rupiah($trans['jumlah']); ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <small><?php echo htmlspecialchars($trans['deskripsi'] ?: '-'); ?></small>
                                            </td>
                                            <td>
                                                <strong class="text-primary"><?php echo format_rupiah($trans['saldo']); ?></strong>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editTransaksiModal"
                                                            data-id="<?php echo $trans['id_transaksi']; ?>"
                                                            data-kelas="<?php echo $trans['id_kelas']; ?>"
                                                            data-jenis="<?php echo $trans['jenis']; ?>"
                                                            data-jumlah="<?php echo $trans['jumlah']; ?>"
                                                            data-tanggal="<?php echo $trans['tanggal']; ?>"
                                                            data-deskripsi="<?php echo htmlspecialchars($trans['deskripsi']); ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <a href="?hapus=<?php echo $trans['id_transaksi']; ?>&kelas=<?php echo $filter_kelas; ?>&jenis=<?php echo $filter_jenis; ?>&bulan=<?php echo $filter_bulan; ?>" 
                                                       class="btn btn-outline-danger" 
                                                       onclick="return confirm('Yakin ingin menghapus transaksi ini?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Add Transaction Modal -->
<div class="modal fade" id="tambahTransaksiModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Transaksi Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <?php if (empty($classes)): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Anda belum memiliki kelas. Silakan <a href="admin_classes.php">buat kelas</a> terlebih dahulu.
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label for="id_kelas" class="form-label fw-semibold">Kelas <span class="text-danger">*</span></label>
                            <select name="id_kelas" id="id_kelas" class="form-select kas-form-control" required>
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id_kelas']; ?>">
                                        <?php echo htmlspecialchars($class['nama_kelas']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="jenis" class="form-label fw-semibold">Jenis Transaksi <span class="text-danger">*</span></label>
                            <select name="jenis" id="jenis" class="form-select kas-form-control" required>
                                <option value="">Pilih Jenis</option>
                                <option value="pemasukan">Pemasukan</option>
                                <option value="pengeluaran">Pengeluaran</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="jumlah" class="form-label fw-semibold">Jumlah (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="jumlah" id="jumlah" class="form-control kas-form-control" 
                                       min="1000" step="1000" placeholder="Contoh: 50000" required>
                            </div>
                            <small class="text-muted">Masukkan jumlah dalam rupiah (minimal Rp 1,000)</small>
                        </div>
                        <div class="mb-3">
                            <label for="tanggal" class="form-label fw-semibold">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" id="tanggal" class="form-control kas-form-control" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label fw-semibold">Deskripsi</label>
                            <textarea name="deskripsi" id="deskripsi" class="form-control kas-form-control" 
                                      rows="3" placeholder="Keterangan transaksi (opsional)"></textarea>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <?php if (!empty($classes)): ?>
                        <button type="submit" name="tambah_transaksi" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Simpan Transaksi
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div class="modal fade" id="editTransaksiModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Transaksi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="id_transaksi" id="edit_id_transaksi">
                <div class="modal-body">
                    <?php if (empty($classes)): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Anda belum memiliki kelas.
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label for="edit_id_kelas" class="form-label fw-semibold">Kelas <span class="text-danger">*</span></label>
                            <select name="id_kelas" id="edit_id_kelas" class="form-select kas-form-control" required>
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id_kelas']; ?>">
                                        <?php echo htmlspecialchars($class['nama_kelas']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_jenis" class="form-label fw-semibold">Jenis Transaksi <span class="text-danger">*</span></label>
                            <select name="jenis" id="edit_jenis" class="form-select kas-form-control" required>
                                <option value="pemasukan">Pemasukan</option>
                                <option value="pengeluaran">Pengeluaran</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_jumlah" class="form-label fw-semibold">Jumlah (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="jumlah" id="edit_jumlah" class="form-control kas-form-control" 
                                       min="1000" step="1000" required>
                            </div>
                            <small class="text-muted">Masukkan jumlah dalam rupiah (minimal Rp 1,000)</small>
                        </div>
                        <div class="mb-3">
                            <label for="edit_tanggal" class="form-label fw-semibold">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" id="edit_tanggal" class="form-control kas-form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_deskripsi" class="form-label fw-semibold">Deskripsi</label>
                            <textarea name="deskripsi" id="edit_deskripsi" class="form-control kas-form-control" 
                                      rows="3" placeholder="Keterangan transaksi (opsional)"></textarea>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <?php if (!empty($classes)): ?>
                        <button type="submit" name="edit_transaksi" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Update Transaksi
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-close alert setelah 5 detik
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Set tanggal hari ini sebagai default di form tambah
    document.getElementById('tanggal').valueAsDate = new Date();
    
    // Script untuk mengisi form edit dengan data dari tabel
    const editModal = document.getElementById('editTransaksiModal');
    editModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        
        document.getElementById('edit_id_transaksi').value = button.getAttribute('data-id');
        document.getElementById('edit_id_kelas').value = button.getAttribute('data-kelas');
        document.getElementById('edit_jenis').value = button.getAttribute('data-jenis');
        document.getElementById('edit_jumlah').value = button.getAttribute('data-jumlah');
        document.getElementById('edit_tanggal').value = button.getAttribute('data-tanggal');
        document.getElementById('edit_deskripsi').value = button.getAttribute('data-deskripsi');
    });
});
</script>

<?php include 'includes/admin_footer.php'; ?>