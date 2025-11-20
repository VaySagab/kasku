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

// Get admin statistics
$stats = [
    'total_classes' => 0,
    'total_members' => 0,
    'total_balance' => 0,
    'total_transactions' => 0
];

// Get total classes managed by this admin
$sql = "SELECT COUNT(*) as total FROM kelas WHERE id_admin = ? AND status = 'aktif'";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $admin_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $stats['total_classes'] = $row['total'];
    mysqli_stmt_close($stmt);
}

// Get total members across all classes
$sql = "SELECT COUNT(DISTINCT a.id_user) as total 
        FROM anggota a 
        JOIN kelas k ON a.id_kelas = k.id_kelas 
        WHERE k.id_admin = ? AND a.status = 'aktif'";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $admin_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $stats['total_members'] = $row['total'];
    mysqli_stmt_close($stmt);
}

// Get total balance across all classes
$sql = "SELECT SUM(kas.saldo) as total 
        FROM kas 
        JOIN kelas ON kas.id_kelas = kelas.id_kelas 
        WHERE kelas.id_admin = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $admin_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $stats['total_balance'] = $row['total'] ?? 0;
    mysqli_stmt_close($stmt);
}

// Get total transactions
$sql = "SELECT COUNT(*) as total 
        FROM transaksi t
        JOIN kas k ON t.id_kas = k.id_kas
        JOIN kelas kl ON k.id_kelas = kl.id_kelas
        WHERE kl.id_admin = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $admin_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $stats['total_transactions'] = $row['total'];
    mysqli_stmt_close($stmt);
}

// Get recent transactions (last 10)
$recent_transactions = [];
$sql = "SELECT t.*, kl.nama_kelas, k.saldo
        FROM transaksi t
        JOIN kas k ON t.id_kas = k.id_kas
        JOIN kelas kl ON k.id_kelas = kl.id_kelas
        WHERE kl.id_admin = ?
        ORDER BY t.created_at DESC
        LIMIT 10";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $admin_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $recent_transactions[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Get classes list
$classes = [];
$sql = "SELECT k.*, 
        (SELECT COUNT(*) FROM anggota WHERE id_kelas = k.id_kelas AND status = 'aktif') as member_count,
        kas.saldo
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

$pageTitle = "Dashboard Admin - KasKelas";
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
                    <a class="nav-link active" href="admin.php">
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
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1 fw-bold">
                        <i class="bi bi-speedometer2 text-primary me-2"></i>Dashboard Admin
                    </h2>
                    <p class="text-muted mb-0">Selamat datang, <strong><?php echo htmlspecialchars($admin_username); ?></strong>! Kelola kas kelas Anda dengan mudah.</p>
                </div>
                <div>
                    <a href="admin_classes.php" class="btn kas-btn kas-btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Buat Kelas Baru
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="kas-stats">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kas-stats-title">Total Kelas</div>
                        <h3 class="kas-stats-value"><?php echo number_format($stats['total_classes']); ?></h3>
                        <small class="text-success"><i class="bi bi-arrow-up"></i> Kelas Aktif</small>
                    </div>
                    <div class="kas-stats-icon balance">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="kas-stats">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kas-stats-title">Total Anggota</div>
                        <h3 class="kas-stats-value"><?php echo number_format($stats['total_members']); ?></h3>
                        <small class="text-info"><i class="bi bi-person-check"></i> Siswa Terdaftar</small>
                    </div>
                    <div class="kas-stats-icon income">
                        <i class="bi bi-person-badge"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="kas-stats">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kas-stats-title">Total Saldo</div>
                        <h3 class="kas-stats-value"><?php echo format_rupiah($stats['total_balance']); ?></h3>
                        <small class="text-primary"><i class="bi bi-wallet2"></i> Semua Kelas</small>
                    </div>
                    <div class="kas-stats-icon balance">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="kas-stats">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kas-stats-title">Total Transaksi</div>
                        <h3 class="kas-stats-value"><?php echo number_format($stats['total_transactions']); ?></h3>
                        <small class="text-warning"><i class="bi bi-graph-up"></i> Semua Waktu</small>
                    </div>
                    <div class="kas-stats-icon expense">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Classes List -->
        <div class="col-lg-8">
            <div class="kas-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-people me-2"></i>Daftar Kelas
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($classes)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                            <p class="text-muted mt-3">Belum ada kelas. Buat kelas pertama Anda!</p>
                            <a href="admin_classes.php" class="btn kas-btn kas-btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Buat Kelas
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table kas-table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama Kelas</th>
                                        <th>Kode</th>
                                        <th>Anggota</th>
                                        <th>Saldo</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($class['nama_kelas']); ?></strong>
                                            </td>
                                            <td>
                                                <code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($class['kode_kelas']); ?></code>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $class['member_count']; ?> siswa</span>
                                            </td>
                                            <td>
                                                <strong class="text-primary"><?php echo format_rupiah($class['saldo'] ?? 0); ?></strong>
                                            </td>
                                            <td><?php echo get_status_badge($class['status']); ?></td>
                                            <td>
                                                <a href="admin_classes.php?view=<?php echo $class['id_kelas']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
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

        <!-- Recent Transactions -->
        <div class="col-lg-4">
            <div class="kas-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>Transaksi Terbaru
                    </h5>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <?php if (empty($recent_transactions)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="text-muted mt-2 mb-0">Belum ada transaksi</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_transactions as $trans): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-1">
                                                <?php echo get_transaction_badge($trans['jenis']); ?>
                                                <small class="text-muted ms-2"><?php echo date('d/m/Y', strtotime($trans['tanggal'])); ?></small>
                                            </div>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-building me-1"></i><?php echo htmlspecialchars($trans['nama_kelas']); ?>
                                            </small>
                                            <?php if ($trans['deskripsi']): ?>
                                                <small class="text-muted d-block"><?php echo htmlspecialchars($trans['deskripsi']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end ms-2">
                                            <strong class="<?php echo $trans['jenis'] === 'pemasukan' ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $trans['jenis'] === 'pemasukan' ? '+' : '-'; ?>
                                                <?php echo format_rupiah($trans['jumlah']); ?>
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="admin_transactions.php" class="btn btn-sm btn-outline-primary">
                                Lihat Semua Transaksi <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-4 mt-2">
        <div class="col-md-3">
            <a href="admin_classes.php" class="text-decoration-none">
                <div class="kas-card text-center">
                    <div class="card-body">
                        <i class="bi bi-plus-circle display-4 text-primary mb-3"></i>
                        <h5 class="fw-bold">Buat Kelas</h5>
                        <p class="text-muted small mb-0">Tambah kelas baru</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="admin_transactions.php" class="text-decoration-none">
                <div class="kas-card text-center">
                    <div class="card-body">
                        <i class="bi bi-cash-stack display-4 text-success mb-3"></i>
                        <h5 class="fw-bold">Tambah Transaksi</h5>
                        <p class="text-muted small mb-0">Catat pemasukan/pengeluaran</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="admin_members.php" class="text-decoration-none">
                <div class="kas-card text-center">
                    <div class="card-body">
                        <i class="bi bi-person-badge display-4 text-info mb-3"></i>
                        <h5 class="fw-bold">Kelola Anggota</h5>
                        <p class="text-muted small mb-0">Lihat dan kelola siswa</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="admin_reports.php" class="text-decoration-none">
                <div class="kas-card text-center">
                    <div class="card-body">
                        <i class="bi bi-file-earmark-bar-graph display-4 text-warning mb-3"></i>
                        <h5 class="fw-bold">Lihat Laporan</h5>
                        <p class="text-muted small mb-0">Analisis keuangan</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</main>

<?php include 'includes/admin_footer.php'; ?>