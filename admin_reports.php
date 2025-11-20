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

// Get filter parameters
$filter_kelas = isset($_GET['kelas']) ? intval($_GET['kelas']) : 0;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

// Get classes for filter
$classes = [];
$sql = "SELECT id_kelas, nama_kelas FROM kelas WHERE id_admin = ? ORDER BY nama_kelas";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $admin_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $classes[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Generate report data
$report_data = null;
$transactions = [];

if ($filter_kelas > 0 && !empty($from_date) && !empty($to_date)) {
    // Get summary data
    $sql = "SELECT 
            SUM(CASE WHEN t.jenis = 'pemasukan' THEN t.jumlah ELSE 0 END) as total_pemasukan,
            SUM(CASE WHEN t.jenis = 'pengeluaran' THEN t.jumlah ELSE 0 END) as total_pengeluaran,
            COUNT(*) as total_transaksi,
            k.nama_kelas,
            kas.saldo as saldo_akhir
            FROM transaksi t
            JOIN kas ON t.id_kas = kas.id_kas
            JOIN kelas k ON kas.id_kelas = k.id_kelas
            WHERE k.id_kelas = ? AND k.id_admin = ? AND t.tanggal BETWEEN ? AND ?
            GROUP BY k.id_kelas";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, 'iiss', $filter_kelas, $admin_id, $from_date, $to_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $report_data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }
    
    // Get detailed transactions
    $sql = "SELECT t.*, k.nama_kelas
            FROM transaksi t
            JOIN kas ON t.id_kas = kas.id_kas
            JOIN kelas k ON kas.id_kelas = k.id_kelas
            WHERE k.id_kelas = ? AND k.id_admin = ? AND t.tanggal BETWEEN ? AND ?
            ORDER BY t.tanggal DESC, t.created_at DESC";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, 'iiss', $filter_kelas, $admin_id, $from_date, $to_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $transactions[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}

$pageTitle = "Laporan Keuangan - Admin KasKelas";
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
                    <a class="nav-link active" href="admin_reports.php">
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
            <h2 class="mb-1 fw-bold">
                <i class="bi bi-file-earmark-bar-graph text-primary me-2"></i>Laporan Keuangan
            </h2>
            <p class="text-muted mb-0">Generate dan analisis laporan keuangan kelas</p>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="kas-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-funnel me-2"></i>Filter Laporan
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="kelas" class="form-label fw-semibold">Pilih Kelas <span class="text-danger">*</span></label>
                            <select name="kelas" id="kelas" class="form-select kas-form-control" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id_kelas']; ?>" <?php echo $filter_kelas == $class['id_kelas'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['nama_kelas']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="from_date" class="form-label fw-semibold">Dari Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="from_date" id="from_date" class="form-control kas-form-control" 
                                   value="<?php echo htmlspecialchars($from_date); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="to_date" class="form-label fw-semibold">Sampai Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="to_date" id="to_date" class="form-control kas-form-control" 
                                   value="<?php echo htmlspecialchars($to_date); ?>" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn kas-btn kas-btn-primary w-100">
                                <i class="bi bi-file-earmark-bar-graph me-2"></i>Generate
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($report_data): ?>
        <!-- Summary Cards -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="kas-stats">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kas-stats-title">Total Pemasukan</div>
                            <h3 class="kas-stats-value text-success"><?php echo format_rupiah($report_data['total_pemasukan'] ?? 0); ?></h3>
                            <small class="text-muted">Periode: <?php echo date('d/m/Y', strtotime($from_date)); ?> - <?php echo date('d/m/Y', strtotime($to_date)); ?></small>
                        </div>
                        <div class="kas-stats-icon income">
                            <i class="bi bi-arrow-up-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="kas-stats">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kas-stats-title">Total Pengeluaran</div>
                            <h3 class="kas-stats-value text-danger"><?php echo format_rupiah($report_data['total_pengeluaran'] ?? 0); ?></h3>
                            <small class="text-muted">Periode: <?php echo date('d/m/Y', strtotime($from_date)); ?> - <?php echo date('d/m/Y', strtotime($to_date)); ?></small>
                        </div>
                        <div class="kas-stats-icon expense">
                            <i class="bi bi-arrow-down-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="kas-stats">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kas-stats-title">Saldo Periode</div>
                            <?php 
                            $saldo_periode = ($report_data['total_pemasukan'] ?? 0) - ($report_data['total_pengeluaran'] ?? 0);
                            ?>
                            <h3 class="kas-stats-value <?php echo $saldo_periode >= 0 ? 'text-primary' : 'text-warning'; ?>">
                                <?php echo format_rupiah($saldo_periode); ?>
                            </h3>
                            <small class="text-muted">Pemasukan - Pengeluaran</small>
                        </div>
                        <div class="kas-stats-icon balance">
                            <i class="bi bi-calculator"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="kas-stats">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kas-stats-title">Saldo Akhir</div>
                            <h3 class="kas-stats-value text-primary"><?php echo format_rupiah($report_data['saldo_akhir'] ?? 0); ?></h3>
                            <small class="text-muted">Saldo terkini di kas</small>
                        </div>
                        <div class="kas-stats-icon balance">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Summary -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="kas-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-file-text me-2"></i>Ringkasan Laporan
                            </h5>
                            <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                                <i class="bi bi-printer me-2"></i>Cetak Laporan
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Nama Kelas:</th>
                                        <td><strong><?php echo htmlspecialchars($report_data['nama_kelas']); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>Periode:</th>
                                        <td><?php echo date('d F Y', strtotime($from_date)); ?> - <?php echo date('d F Y', strtotime($to_date)); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Total Transaksi:</th>
                                        <td><span class="badge bg-info"><?php echo $report_data['total_transaksi']; ?> transaksi</span></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-info border-0">
                                    <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-2"></i>Analisis</h6>
                                    <ul class="mb-0 small">
                                        <li>Pemasukan: <?php echo format_rupiah($report_data['total_pemasukan'] ?? 0); ?></li>
                                        <li>Pengeluaran: <?php echo format_rupiah($report_data['total_pengeluaran'] ?? 0); ?></li>
                                        <li>Selisih: <?php echo format_rupiah($saldo_periode); ?></li>
                                        <?php if ($saldo_periode >= 0): ?>
                                            <li class="text-success fw-semibold">Status: Surplus (Pemasukan > Pengeluaran)</li>
                                        <?php else: ?>
                                            <li class="text-danger fw-semibold">Status: Defisit (Pengeluaran > Pemasukan)</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Transactions -->
        <div class="row">
            <div class="col-12">
                <div class="kas-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-list-ul me-2"></i>Detail Transaksi (<?php echo count($transactions); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($transactions)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox display-1 text-muted"></i>
                                <p class="text-muted mt-3">Tidak ada transaksi pada periode ini</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table kas-table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Jenis</th>
                                            <th>Deskripsi</th>
                                            <th class="text-end">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $trans): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($trans['tanggal'])); ?></td>
                                                <td><?php echo get_transaction_badge($trans['jenis']); ?></td>
                                                <td><?php echo htmlspecialchars($trans['deskripsi'] ?: '-'); ?></td>
                                                <td class="text-end">
                                                    <strong class="<?php echo $trans['jenis'] === 'pemasukan' ? 'text-success' : 'text-danger'; ?>">
                                                        <?php echo $trans['jenis'] === 'pemasukan' ? '+' : '-'; ?>
                                                        <?php echo format_rupiah($trans['jumlah']); ?>
                                                    </strong>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="3" class="text-end">Total Pemasukan:</th>
                                            <th class="text-end text-success"><?php echo format_rupiah($report_data['total_pemasukan'] ?? 0); ?></th>
                                        </tr>
                                        <tr>
                                            <th colspan="3" class="text-end">Total Pengeluaran:</th>
                                            <th class="text-end text-danger"><?php echo format_rupiah($report_data['total_pengeluaran'] ?? 0); ?></th>
                                        </tr>
                                        <tr>
                                            <th colspan="3" class="text-end">Saldo Periode:</th>
                                            <th class="text-end text-primary"><?php echo format_rupiah($saldo_periode); ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="row">
            <div class="col-12">
                <div class="kas-card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-file-earmark-bar-graph display-1 text-muted"></i>
                        <h4 class="mt-4 mb-2">Belum Ada Laporan</h4>
                        <p class="text-muted mb-4">Pilih kelas dan periode untuk generate laporan keuangan</p>
                        <div class="alert alert-info d-inline-block text-start">
                            <h6 class="fw-bold mb-2"><i class="bi bi-lightbulb me-2"></i>Tips:</h6>
                            <ul class="mb-0 small">
                                <li>Pilih kelas yang ingin Anda lihat laporannya</li>
                                <li>Tentukan rentang tanggal periode laporan</li>
                                <li>Klik tombol "Generate" untuk melihat laporan</li>
                                <li>Anda dapat mencetak laporan dengan tombol "Cetak Laporan"</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<style>
@media print {
    .kas-navbar,
    .btn,
    .card-header button {
        display: none !important;
    }
    
    .kas-card {
        box-shadow: none !important;
        border: 1px solid #dee2e6 !important;
    }
    
    body {
        background: white !important;
    }
}
</style>

<?php include 'includes/admin_footer.php'; ?>