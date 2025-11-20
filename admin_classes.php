<?php
// Define access constant for includes
define('ALLOW_ACCESS', true);

require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/function.php';

// Protect route: allow only admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$admin_id = $_SESSION['user_id'];
$admin_username = $_SESSION['username'];

$message = '';
$message_type = '';

// Handle form submission for creating new class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_class') {
    $nama_kelas = trim($_POST['nama_kelas']);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $tahun_ajaran = trim($_POST['tahun_ajaran'] ?? '');
    $semester = $_POST['semester'] ?? NULL;
    $id_admin = $_SESSION['user_id'];
    
    // Generate random 6-digit code
    $kode_kelas = strtoupper(generate_random_string(6));
    
    // Check if kode_kelas already exists and regenerate if needed
    $check_sql = "SELECT id_kelas FROM kelas WHERE kode_kelas = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, 's', $kode_kelas);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    while (mysqli_num_rows($check_result) > 0) {
        $kode_kelas = strtoupper(generate_random_string(6));
        mysqli_stmt_bind_param($check_stmt, 's', $kode_kelas);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
    }
    mysqli_stmt_close($check_stmt);
    
    // Validate input
    if (empty($nama_kelas)) {
        $message = 'Nama kelas harus diisi!';
        $message_type = 'danger';
    } else {
        // Check duplicate class name for this admin
        $dup_sql = "SELECT id_kelas FROM kelas WHERE nama_kelas = ? AND id_admin = ?";
        $dup_stmt = mysqli_prepare($conn, $dup_sql);
        mysqli_stmt_bind_param($dup_stmt, 'si', $nama_kelas, $id_admin);
        mysqli_stmt_execute($dup_stmt);
        $dup_result = mysqli_stmt_get_result($dup_stmt);
        
        if (mysqli_num_rows($dup_result) > 0) {
            $message = 'Nama kelas sudah ada! Gunakan nama yang berbeda.';
            $message_type = 'danger';
            mysqli_stmt_close($dup_stmt);
        } else {
            mysqli_stmt_close($dup_stmt);
            
            // Insert new class - kas will be created by trigger
            $sql = "INSERT INTO kelas (nama_kelas, kode_kelas, id_admin, deskripsi, tahun_ajaran, semester, status) VALUES (?, ?, ?, ?, ?, ?, 'aktif')";
            $stmt = mysqli_prepare($conn, $sql);
            
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ssisss', $nama_kelas, $kode_kelas, $id_admin, $deskripsi, $tahun_ajaran, $semester);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = "Kelas '$nama_kelas' berhasil dibuat dengan kode '<strong>$kode_kelas</strong>'!";
                    $message_type = 'success';
                } else {
                    $message = 'Gagal membuat kelas: ' . mysqli_stmt_error($stmt);
                    $message_type = 'danger';
                }
                mysqli_stmt_close($stmt);
            } else {
                $message = 'Gagal mempersiapkan query: ' . mysqli_error($conn);
                $message_type = 'danger';
            }
        }
    }
}

// Handle delete class - MODIFIED: Allow deletion even with transactions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_class') {
    $id_kelas = intval($_POST['id_kelas']);
    
    // Check if class exists and belongs to admin
    $check_sql = "SELECT nama_kelas FROM kelas WHERE id_kelas = ? AND id_admin = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, 'ii', $id_kelas, $admin_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) === 0) {
        $message = 'Kelas tidak ditemukan atau Anda tidak memiliki akses!';
        $message_type = 'danger';
        mysqli_stmt_close($check_stmt);
    } else {
        $class_data = mysqli_fetch_assoc($check_result);
        $nama_kelas = $class_data['nama_kelas'];
        mysqli_stmt_close($check_stmt);
        
        // Get kas ID for this class
        $kas_sql = "SELECT id_kas FROM kas WHERE id_kelas = ?";
        $kas_stmt = mysqli_prepare($conn, $kas_sql);
        mysqli_stmt_bind_param($kas_stmt, 'i', $id_kelas);
        mysqli_stmt_execute($kas_stmt);
        $kas_result = mysqli_stmt_get_result($kas_stmt);
        $kas_data = mysqli_fetch_assoc($kas_result);
        $id_kas = $kas_data['id_kas'] ?? null;
        mysqli_stmt_close($kas_stmt);
        
        // Start transaction for data integrity
        mysqli_begin_transaction($conn);
        
        try {
            // 1. Delete related data first (if any)
            if ($id_kas) {
                // Delete transaksi records for this kas
                $delete_transaksi_sql = "DELETE FROM transaksi WHERE id_kas = ?";
                $delete_transaksi_stmt = mysqli_prepare($conn, $delete_transaksi_sql);
                mysqli_stmt_bind_param($delete_transaksi_stmt, 'i', $id_kas);
                mysqli_stmt_execute($delete_transaksi_stmt);
                mysqli_stmt_close($delete_transaksi_stmt);
            }
            
            // Delete anggota records
            $delete_anggota_sql = "DELETE FROM anggota WHERE id_kelas = ?";
            $delete_anggota_stmt = mysqli_prepare($conn, $delete_anggota_sql);
            mysqli_stmt_bind_param($delete_anggota_stmt, 'i', $id_kelas);
            mysqli_stmt_execute($delete_anggota_stmt);
            mysqli_stmt_close($delete_anggota_stmt);
            
            // Delete iuran records
            $delete_iuran_sql = "DELETE FROM iuran WHERE id_kelas = ?";
            $delete_iuran_stmt = mysqli_prepare($conn, $delete_iuran_sql);
            mysqli_stmt_bind_param($delete_iuran_stmt, 'i', $id_kelas);
            mysqli_stmt_execute($delete_iuran_stmt);
            mysqli_stmt_close($delete_iuran_stmt);
            
            // 2. Delete kas record
            if ($id_kas) {
                $delete_kas_sql = "DELETE FROM kas WHERE id_kas = ?";
                $delete_kas_stmt = mysqli_prepare($conn, $delete_kas_sql);
                mysqli_stmt_bind_param($delete_kas_stmt, 'i', $id_kas);
                mysqli_stmt_execute($delete_kas_stmt);
                mysqli_stmt_close($delete_kas_stmt);
            }
            
            // 3. Finally delete the class
            $delete_class_sql = "DELETE FROM kelas WHERE id_kelas = ? AND id_admin = ?";
            $delete_class_stmt = mysqli_prepare($conn, $delete_class_sql);
            mysqli_stmt_bind_param($delete_class_stmt, 'ii', $id_kelas, $admin_id);
            mysqli_stmt_execute($delete_class_stmt);
            $affected_rows = mysqli_stmt_affected_rows($delete_class_stmt);
            mysqli_stmt_close($delete_class_stmt);
            
            if ($affected_rows > 0) {
                mysqli_commit($conn);
                $message = "Kelas '$nama_kelas' berhasil dihapus beserta semua data terkait (transaksi, anggota, iuran)!";
                $message_type = 'success';
            } else {
                mysqli_rollback($conn);
                $message = 'Gagal menghapus kelas!';
                $message_type = 'danger';
            }
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = 'Terjadi kesalahan saat menghapus kelas: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// ===== PERBAIKAN BUG: Handle Update Class =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_class') {
    $kelas_id = intval($_POST['kelas_id']);
    $nama_kelas = trim($_POST['nama_kelas']);
    $status = $_POST['status'];
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $tahun_ajaran = trim($_POST['tahun_ajaran'] ?? '');
    $semester = $_POST['semester'] ?? NULL;
    
    // Validasi input
    if (empty($nama_kelas)) {
        $message = 'Nama kelas harus diisi!';
        $message_type = 'danger';
    } else {
        // Get current data untuk perbandingan
        $current_sql = "SELECT nama_kelas, status, deskripsi, tahun_ajaran, semester FROM kelas WHERE id_kelas = ? AND id_admin = ?";
        $current_stmt = mysqli_prepare($conn, $current_sql);
        mysqli_stmt_bind_param($current_stmt, 'ii', $kelas_id, $admin_id);
        mysqli_stmt_execute($current_stmt);
        $current_result = mysqli_stmt_get_result($current_stmt);
        $current_data = mysqli_fetch_assoc($current_result);
        mysqli_stmt_close($current_stmt);
        
        if (!$current_data) {
            $message = 'Kelas tidak ditemukan atau Anda tidak memiliki akses!';
            $message_type = 'danger';
        } else {
            // Check jika ada perubahan
            $has_changes = (
                $current_data['nama_kelas'] !== $nama_kelas ||
                $current_data['status'] !== $status ||
                $current_data['deskripsi'] !== $deskripsi ||
                $current_data['tahun_ajaran'] !== $tahun_ajaran ||
                $current_data['semester'] !== $semester
            );
            
            if (!$has_changes) {
                $message = 'Tidak ada perubahan data!';
                $message_type = 'info';
            } else {
                // Check duplicate nama kelas (kecuali kelas sendiri)
                $dup_sql = "SELECT id_kelas FROM kelas WHERE nama_kelas = ? AND id_admin = ? AND id_kelas != ?";
                $dup_stmt = mysqli_prepare($conn, $dup_sql);
                mysqli_stmt_bind_param($dup_stmt, 'sii', $nama_kelas, $admin_id, $kelas_id);
                mysqli_stmt_execute($dup_stmt);
                $dup_result = mysqli_stmt_get_result($dup_stmt);
                
                if (mysqli_num_rows($dup_result) > 0) {
                    $message = 'Nama kelas sudah digunakan! Gunakan nama yang berbeda.';
                    $message_type = 'danger';
                    mysqli_stmt_close($dup_stmt);
                } else {
                    mysqli_stmt_close($dup_stmt);
                    
                    // Konfirmasi jika mengubah status ke nonaktif
                    if ($current_data['status'] === 'aktif' && $status === 'nonaktif') {
                        // Check jika ada anggota aktif
                        $member_sql = "SELECT COUNT(*) as count FROM anggota WHERE id_kelas = ? AND status = 'aktif'";
                        $member_stmt = mysqli_prepare($conn, $member_sql);
                        mysqli_stmt_bind_param($member_stmt, 'i', $kelas_id);
                        mysqli_stmt_execute($member_stmt);
                        $member_result = mysqli_stmt_get_result($member_stmt);
                        $member_row = mysqli_fetch_assoc($member_result);
                        mysqli_stmt_close($member_stmt);
                        
                        if ($member_row['count'] > 0) {
                            $message = "Perhatian: Kelas memiliki {$member_row['count']} anggota aktif. Status diubah ke nonaktif.";
                            $message_type = 'warning';
                        }
                    }
                    
                    // Update kelas
                    $sql = "UPDATE kelas SET nama_kelas = ?, status = ?, deskripsi = ?, tahun_ajaran = ?, semester = ? WHERE id_kelas = ? AND id_admin = ?";
                    if ($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, 'sssssii', $nama_kelas, $status, $deskripsi, $tahun_ajaran, $semester, $kelas_id, $admin_id);
                        if (mysqli_stmt_execute($stmt)) {
                            if (mysqli_stmt_affected_rows($stmt) > 0 || !$has_changes) {
                                if (empty($message)) {
                                    $message = 'Kelas berhasil diupdate!';
                                    $message_type = 'success';
                                }
                            } else {
                                $message = 'Gagal mengupdate kelas!';
                                $message_type = 'danger';
                            }
                        } else {
                            $message = 'Gagal mengupdate kelas: ' . mysqli_stmt_error($stmt);
                            $message_type = 'danger';
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        $message = 'Gagal mempersiapkan query: ' . mysqli_error($conn);
                        $message_type = 'danger';
                    }
                }
            }
        }
    }
}

// Handle Generate New Code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_code') {
    $kelas_id = intval($_POST['kelas_id']);
    $new_code = strtoupper(generate_random_string(6));
    
    // Check if code already exists
    $check_sql = "SELECT kode_kelas FROM kelas WHERE kode_kelas = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, 's', $new_code);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    while (mysqli_num_rows($check_result) > 0) {
        $new_code = strtoupper(generate_random_string(6));
        mysqli_stmt_bind_param($check_stmt, 's', $new_code);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
    }
    mysqli_stmt_close($check_stmt);
    
    $sql = "UPDATE kelas SET kode_kelas = ? WHERE id_kelas = ? AND id_admin = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, 'sii', $new_code, $kelas_id, $admin_id);
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $message = "Kode baru berhasil dibuat: <strong>$new_code</strong>";
                $message_type = 'success';
            } else {
                $message = 'Kelas tidak ditemukan atau Anda tidak memiliki akses!';
                $message_type = 'danger';
            }
        } else {
            $message = 'Gagal membuat kode baru: ' . mysqli_stmt_error($stmt);
            $message_type = 'danger';
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = 'Gagal mempersiapkan query: ' . mysqli_error($conn);
        $message_type = 'danger';
    }
}

// Get all classes
$classes = [];
$sql = "SELECT k.*, 
        (SELECT COUNT(*) FROM anggota WHERE id_kelas = k.id_kelas AND status = 'aktif') as member_count,
        kas.saldo,
        (SELECT COUNT(*) FROM transaksi t JOIN kas ks ON t.id_kas = ks.id_kas WHERE ks.id_kelas = k.id_kelas) as transaction_count
        FROM kelas k
        LEFT JOIN kas ON k.id_kelas = kas.id_kelas
        WHERE k.id_admin = ?
        ORDER BY k.created_at DESC";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $admin_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $classes[] = $row;
    }
    mysqli_stmt_close($stmt);
}

$pageTitle = "Manajemen Kelas - Admin KasKelas";
include 'includes/admin_header.php';
?>

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
                    <a class="nav-link active" href="admin_classes.php">
                        <i class="bi bi-people me-1"></i>Kelas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_members.php">
                        <i class="bi bi-person-badge me-1"></i>Anggota
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_transactions.php">
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
                        <i class="bi bi-people text-primary me-2"></i>Manajemen Kelas
                    </h2>
                    <p class="text-muted mb-0">Kelola semua kelas yang Anda buat</p>
                </div>
                <div>
                    <button class="btn kas-btn kas-btn-primary" data-bs-toggle="modal" data-bs-target="#createClassModal">
                        <i class="bi bi-plus-circle me-2"></i>Buat Kelas Baru
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($message)): ?>
        <div class="alert kas-alert kas-alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <i class="bi <?php echo $message_type === 'success' ? 'bi-check-circle' : ($message_type === 'info' ? 'bi-info-circle' : 'bi-exclamation-triangle'); ?> me-2"></i>
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Classes List -->
    <div class="row">
        <div class="col-12">
            <div class="kas-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-ul me-2"></i>Daftar Kelas (<?php echo count($classes); ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($classes)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                            <p class="text-muted mt-3 mb-4">Belum ada kelas. Buat kelas pertama Anda!</p>
                            <button class="btn kas-btn kas-btn-primary" data-bs-toggle="modal" data-bs-target="#createClassModal">
                                <i class="bi bi-plus-circle me-2"></i>Buat Kelas Baru
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table kas-table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama Kelas</th>
                                        <th>Kode Kelas</th>
                                        <th>Tahun Ajaran</th>
                                        <th>Anggota</th>
                                        <th>Saldo</th>
                                        <th>Transaksi</th>
                                        <th>Status</th>
                                        <th>Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($class['nama_kelas']); ?></strong>
                                                <?php if (!empty($class['deskripsi'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($class['deskripsi'], 0, 50)); ?><?php echo strlen($class['deskripsi']) > 50 ? '...' : ''; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <code class="bg-light px-3 py-2 rounded fw-bold"><?php echo htmlspecialchars($class['kode_kelas']); ?></code>
                                                <button class="btn btn-sm btn-link p-0 ms-1" onclick="copyCode('<?php echo htmlspecialchars($class['kode_kelas'], ENT_QUOTES); ?>')" title="Salin kode">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </td>
                                            <td>
                                                <?php if (!empty($class['tahun_ajaran'])): ?>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($class['tahun_ajaran']); ?></span>
                                                    <?php if (!empty($class['semester'])): ?>
                                                        <br><small class="text-muted"><?php echo ucfirst($class['semester']); ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $class['member_count']; ?> siswa</span>
                                            </td>
                                            <td>
                                                <strong class="text-primary"><?php echo format_rupiah($class['saldo'] ?? 0); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo $class['transaction_count']; ?> transaksi</span>
                                            </td>
                                            <td><?php echo get_status_badge($class['status']); ?></td>
                                            <td>
                                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($class['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editClass(<?php echo htmlspecialchars(json_encode($class), ENT_QUOTES); ?>)" title="Edit Kelas">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-warning" onclick="generateNewCode(<?php echo $class['id_kelas']; ?>, '<?php echo htmlspecialchars($class['nama_kelas'], ENT_QUOTES); ?>')" title="Generate Kode Baru">
                                                        <i class="bi bi-arrow-repeat"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteClass(<?php echo $class['id_kelas']; ?>, '<?php echo htmlspecialchars($class['nama_kelas'], ENT_QUOTES); ?>')" title="Hapus Kelas">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
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

<!-- Create Class Modal -->
<div class="modal fade" id="createClassModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Buat Kelas Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_class">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="nama_kelas" class="form-label fw-semibold">Nama Kelas <span class="text-danger">*</span></label>
                            <input type="text" class="form-control kas-form-control" id="nama_kelas" name="nama_kelas" required placeholder="Contoh: XII IPA 1">
                            <small class="text-muted">Masukkan nama kelas yang jelas dan mudah dikenali</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="tahun_ajaran" class="form-label fw-semibold">Tahun Ajaran</label>
                            <input type="text" class="form-control kas-form-control" id="tahun_ajaran" name="tahun_ajaran" placeholder="Contoh: 2024/2025">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="semester" class="form-label fw-semibold">Semester</label>
                            <select class="form-select kas-form-control" id="semester" name="semester">
                                <option value="">Pilih Semester</option>
                                <option value="ganjil">Ganjil</option>
                                <option value="genap">Genap</option>
                            </select>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label for="deskripsi" class="form-label fw-semibold">Deskripsi (Opsional)</label>
                            <textarea class="form-control kas-form-control" id="deskripsi" name="deskripsi" rows="3" placeholder="Tambahkan deskripsi atau catatan untuk kelas ini"></textarea>
                        </div>
                    </div>
                    
                    <div class="alert alert-info border-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Kode kelas 6 digit akan dibuat secara otomatis
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Buat Kelas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== PERBAIKAN BUG: Edit Class Modal ===== -->
<div class="modal fade" id="editClassModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Kelas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_class">
                <input type="hidden" name="kelas_id" id="edit_kelas_id">
                <div class="modal-body">
                    <!-- Tampilkan Kode Kelas (Read-only) -->
                    <div class="alert alert-light border mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted d-block">Kode Kelas:</small>
                                <code class="fs-5 fw-bold" id="edit_kode_kelas">-</code>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyCodeFromEdit()">
                                <i class="bi bi-clipboard me-1"></i>Salin
                            </button>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_nama_kelas" class="form-label fw-semibold">Nama Kelas <span class="text-danger">*</span></label>
                            <input type="text" class="form-control kas-form-control" id="edit_nama_kelas" name="nama_kelas" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_tahun_ajaran" class="form-label fw-semibold">Tahun Ajaran</label>
                            <input type="text" class="form-control kas-form-control" id="edit_tahun_ajaran" name="tahun_ajaran" placeholder="Contoh: 2024/2025">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_semester" class="form-label fw-semibold">Semester</label>
                            <select class="form-select kas-form-control" id="edit_semester" name="semester">
                                <option value="">Pilih Semester</option>
                                <option value="ganjil">Ganjil</option>
                                <option value="genap">Genap</option>
                            </select>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label for="edit_deskripsi" class="form-label fw-semibold">Deskripsi</label>
                            <textarea class="form-control kas-form-control" id="edit_deskripsi" name="deskripsi" rows="3" placeholder="Tambahkan deskripsi atau catatan untuk kelas ini"></textarea>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label for="edit_status" class="form-label fw-semibold">Status</label>
                            <select class="form-select kas-form-control" id="edit_status" name="status" onchange="checkStatusChange(this.value)">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                            <small class="text-muted">Kelas nonaktif tidak dapat digunakan untuk transaksi baru</small>
                            <div id="status_warning" class="alert alert-warning border-0 mt-2" style="display: none;">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Perhatian!</strong> Mengubah status ke nonaktif akan menonaktifkan semua transaksi baru untuk kelas ini.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Generate New Code Modal -->
<div class="modal fade" id="generateCodeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2"></i>Generate Kode Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="generate_code">
                <input type="hidden" name="kelas_id" id="gen_kelas_id">
                <div class="modal-body">
                    <div class="alert alert-warning border-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Perhatian!</strong> Kode lama akan diganti dengan kode baru 6 digit secara otomatis.
                    </div>
                    <p>Apakah Anda yakin ingin membuat kode baru untuk kelas <strong id="gen_kelas_name"></strong>?</p>
                    <p class="text-muted"><small>Anggota yang sudah bergabung tidak akan terpengaruh, namun kode lama tidak dapat digunakan lagi.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-arrow-repeat me-2"></i>Generate Kode Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Class Modal - MODIFIED: Updated message -->
<div class="modal fade" id="deleteClassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Hapus Kelas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete_class">
                <input type="hidden" name="id_kelas" id="delete_kelas_id">
                <div class="modal-body">
                    <div class="alert alert-danger border-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Perhatian!</strong> Tindakan ini tidak dapat dibatalkan!
                    </div>
                    <p>Apakah Anda yakin ingin menghapus kelas <strong id="delete_kelas_name"></strong>?</p>
                    <p class="text-muted"><small>Semua data terkait (transaksi, anggota, iuran, saldo) akan ikut terhapus secara permanen.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Hapus Kelas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== PERBAIKAN BUG: JavaScript dengan Proper Escaping ===== -->
<script>
// Fungsi untuk escape HTML untuk mencegah XSS
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Variabel untuk menyimpan status awal
let originalStatus = '';

// Fungsi Edit Class dengan perbaikan
function editClass(classData) {
    // Set ID kelas
    document.getElementById('edit_kelas_id').value = classData.id_kelas;
    
    // Set kode kelas (read-only display)
    document.getElementById('edit_kode_kelas').textContent = classData.kode_kelas;
    
    // Set nama kelas
    document.getElementById('edit_nama_kelas').value = classData.nama_kelas;
    
    // Set tahun ajaran
    document.getElementById('edit_tahun_ajaran').value = classData.tahun_ajaran || '';
    
    // Set semester
    document.getElementById('edit_semester').value = classData.semester || '';
    
    // Set deskripsi
    document.getElementById('edit_deskripsi').value = classData.deskripsi || '';
    
    // Set status
    document.getElementById('edit_status').value = classData.status;
    originalStatus = classData.status;
    
    // Reset warning
    document.getElementById('status_warning').style.display = 'none';
    
    // Show modal
    new bootstrap.Modal(document.getElementById('editClassModal')).show();
}

// Fungsi untuk check perubahan status
function checkStatusChange(newStatus) {
    const warning = document.getElementById('status_warning');
    
    if (originalStatus === 'aktif' && newStatus === 'nonaktif') {
        warning.style.display = 'block';
    } else {
        warning.style.display = 'none';
    }
}

// Fungsi untuk copy kode dari modal edit
function copyCodeFromEdit() {
    const code = document.getElementById('edit_kode_kelas').textContent;
    copyCode(code);
}

// Fungsi Generate New Code
function generateNewCode(kelasId, kelasName) {
    document.getElementById('gen_kelas_id').value = kelasId;
    document.getElementById('gen_kelas_name').textContent = kelasName;
    new bootstrap.Modal(document.getElementById('generateCodeModal')).show();
}

// Fungsi Delete Class
function deleteClass(kelasId, kelasName) {
    document.getElementById('delete_kelas_id').value = kelasId;
    document.getElementById('delete_kelas_name').textContent = kelasName;
    new bootstrap.Modal(document.getElementById('deleteClassModal')).show();
}

// Fungsi Copy Code dengan feedback yang lebih baik
function copyCode(code) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(code).then(function() {
            // Gunakan toast notification jika tersedia, atau alert
            if (typeof showToast === 'function') {
                showToast('Berhasil', 'Kode berhasil disalin: ' + code, 'success');
            } else {
                // Buat temporary tooltip atau notification
                const btn = event.target.closest('button');
                const originalTitle = btn.getAttribute('title');
                btn.setAttribute('title', 'Tersalin!');
                
                // Reset setelah 2 detik
                setTimeout(function() {
                    btn.setAttribute('title', originalTitle);
                }, 2000);
                
                // Fallback alert
                alert('Kode berhasil disalin: ' + code);
            }
        }).catch(function(err) {
            console.error('Gagal menyalin kode:', err);
            alert('Gagal menyalin kode. Silakan salin manual: ' + code);
        });
    } else {
        // Fallback untuk browser lama
        const textArea = document.createElement('textarea');
        textArea.value = code;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.select();
        
        try {
            document.execCommand('copy');
            alert('Kode berhasil disalin: ' + code);
        } catch (err) {
            alert('Gagal menyalin kode. Silakan salin manual: ' + code);
        }
        
        document.body.removeChild(textArea);
    }
}

// Auto-hide alerts setelah 5 detik
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert:not(.alert-light)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>

<?php include 'includes/admin_footer.php'; ?>