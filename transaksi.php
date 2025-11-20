<?php
// Define access constant for includes
define('ALLOW_ACCESS', true);

require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/function.php';

// Protect route: allow only regular users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get filter parameters
$filter_kelas = isset($_GET['kelas']) ? intval($_GET['kelas']) : 0;
$filter_jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';

// Get user's classes for filter
$user_classes = [];
$sql = "SELECT k.id_kelas, k.nama_kelas, k.kode_kelas
        FROM anggota a
        JOIN kelas k ON a.id_kelas = k.id_kelas
        WHERE a.id_user = ? AND a.status = 'aktif'
        ORDER BY k.nama_kelas";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $user_classes[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Get transactions based on filters
$transactions = [];
$sql = "SELECT t.*, k.nama_kelas, k.kode_kelas, kas.saldo
        FROM transaksi t
        JOIN kas ON t.id_kas = kas.id_kas
        JOIN kelas k ON kas.id_kelas = k.id_kelas
        JOIN anggota a ON k.id_kelas = a.id_kelas
        WHERE a.id_user = ? AND a.status = 'aktif'";

$params = [$user_id];
$types = 'i';

if ($filter_kelas > 0) {
    $sql .= " AND k.id_kelas = ?";
    $params[] = $filter_kelas;
    $types .= 'i';
}

if (!empty($filter_jenis)) {
    $sql .= " AND t.jenis = ?";
    $params[] = $filter_jenis;
    $types .= 's';
}

if (!empty($filter_bulan)) {
    $sql .= " AND DATE_FORMAT(t.tanggal, '%Y-%m') = ?";
    $params[] = $filter_bulan;
    $types .= 's';
}

$sql .= " ORDER BY t.tanggal DESC, t.created_at DESC";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $transactions[] = $row;
    }
    mysqli_stmt_close($stmt);
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

$pageTitle = "Riwayat Transaksi - KasKelas";
include 'includes/header.php';
?>

<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }
    
    .transaction-page {
        padding-top: 100px;
        padding-bottom: 50px;
    }
    
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 2rem;
        color: white;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
        margin-bottom: 2rem;
    }
    
    .stats-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border-left: 4px solid #667eea;
        margin-bottom: 1.5rem;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }
    
    .filter-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 2rem;
    }
    
    .transaction-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    }
    
    .transaction-item {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }
    
    .transaction-item:hover {
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        transform: translateX(5px);
    }
    
    .transaction-item.pemasukan {
        border-left-color: #28a745;
    }
    
    .transaction-item.pengeluaran {
        border-left-color: #dc3545;
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
</style>

<div class="container transaction-page">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="mb-2 fw-bold">
                    <i class="bi bi-clock-history me-2"></i>
                    Riwayat Transaksi
                </h2>
                <p class="mb-0 opacity-75">
                    Lihat semua transaksi pemasukan dan pengeluaran kelas Anda
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="user.php" class="btn btn-light rounded-pill">
                    <i class="bi bi-arrow-left me-2"></i>Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stats-card" style="border-left-color: #28a745;">
                <div class="text-muted small mb-2">Total Pemasukan</div>
                <h3 class="fw-bold mb-0 text-success">
                    <?php echo format_rupiah($total_pemasukan); ?>
                </h3>
                <small class="text-success">
                    <i class="bi bi-arrow-up-circle me-1"></i>Semua Transaksi
                </small>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stats-card" style="border-left-color: #dc3545;">
                <div class="text-muted small mb-2">Total Pengeluaran</div>
                <h3 class="fw-bold mb-0 text-danger">
                    <?php echo format_rupiah($total_pengeluaran); ?>
                </h3>
                <small class="text-danger">
                    <i class="bi bi-arrow-down-circle me-1"></i>Semua Transaksi
                </small>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stats-card" style="border-left-color: #ffc107;">
                <div class="text-muted small mb-2">Total Transaksi</div>
                <h3 class="fw-bold mb-0 text-primary">
                    <?php echo count($transactions); ?>
                </h3>
                <small class="text-info">
                    <i class="bi bi-list-ul me-1"></i>Semua Kelas
                </small>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-card">
        <h5 class="fw-bold mb-3">
            <i class="bi bi-funnel me-2 text-primary"></i>Filter Transaksi
        </h5>
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="kelas" class="form-label fw-semibold">Kelas</label>
                <select name="kelas" id="kelas" class="form-select">
                    <option value="0">Semua Kelas</option>
                    <?php foreach ($user_classes as $class): ?>
                        <option value="<?php echo $class['id_kelas']; ?>" <?php echo $filter_kelas == $class['id_kelas'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['nama_kelas']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="jenis" class="form-label fw-semibold">Jenis Transaksi</label>
                <select name="jenis" id="jenis" class="form-select">
                    <option value="">Semua Jenis</option>
                    <option value="pemasukan" <?php echo $filter_jenis === 'pemasukan' ? 'selected' : ''; ?>>Pemasukan</option>
                    <option value="pengeluaran" <?php echo $filter_jenis === 'pengeluaran' ? 'selected' : ''; ?>>Pengeluaran</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="bulan" class="form-label fw-semibold">Bulan</label>
                <input type="month" name="bulan" id="bulan" class="form-control" value="<?php echo htmlspecialchars($filter_bulan); ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-2"></i>Filter
                </button>
            </div>
        </form>
        <?php if ($filter_kelas > 0 || !empty($filter_jenis) || !empty($filter_bulan)): ?>
            <div class="mt-3">
                <a href="transaksi.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Reset Filter
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Transactions List -->
    <div class="transaction-card">
        <h5 class="fw-bold mb-3">
            <i class="bi bi-list-ul me-2 text-primary"></i>
            Daftar Transaksi (<?php echo count($transactions); ?>)
        </h5>
        
        <?php if (empty($transactions)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h5 class="fw-bold mb-2">Tidak Ada Transaksi</h5>
                <p class="text-muted mb-3">
                    <?php if ($filter_kelas > 0 || !empty($filter_jenis) || !empty($filter_bulan)): ?>
                        Tidak ada transaksi yang sesuai dengan filter yang dipilih
                    <?php else: ?>
                        Belum ada transaksi di kelas Anda
                    <?php endif; ?>
                </p>
                <?php if ($filter_kelas > 0 || !empty($filter_jenis) || !empty($filter_bulan)): ?>
                    <a href="transaksi.php" class="btn btn-primary rounded-pill">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Tampilkan Semua
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($transactions as $trans): ?>
                <div class="transaction-item <?php echo $trans['jenis']; ?>">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-start gap-3">
                                <div class="<?php echo $trans['jenis'] === 'pemasukan' ? 'bg-success' : 'bg-danger'; ?> bg-opacity-10 rounded-3 p-3">
                                    <i class="bi <?php echo $trans['jenis'] === 'pemasukan' ? 'bi-arrow-down-circle' : 'bi-arrow-up-circle'; ?> fs-4 <?php echo $trans['jenis'] === 'pemasukan' ? 'text-success' : 'text-danger'; ?>"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">
                                        <?php echo get_transaction_badge($trans['jenis']); ?>
                                    </h6>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-building me-1"></i>
                                        <?php echo htmlspecialchars($trans['nama_kelas']); ?>
                                    </small>
                                    <?php if ($trans['deskripsi']): ?>
                                        <small class="text-muted d-block mt-1">
                                            <i class="bi bi-chat-left-text me-1"></i>
                                            <?php echo htmlspecialchars($trans['deskripsi']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mt-3 mt-md-0">
                            <small class="text-muted d-block mb-1">Tanggal</small>
                            <div class="fw-semibold">
                                <i class="bi bi-calendar-event me-1"></i>
                                <?php echo format_date_id($trans['tanggal']); ?>
                            </div>
                        </div>
                        <div class="col-md-3 text-md-end mt-3 mt-md-0">
                            <small class="text-muted d-block mb-1">Jumlah</small>
                            <h5 class="fw-bold mb-0 <?php echo $trans['jenis'] === 'pemasukan' ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $trans['jenis'] === 'pemasukan' ? '+' : '-'; ?>
                                <?php echo format_rupiah($trans['jumlah']); ?>
                            </h5>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Animation on load
document.addEventListener('DOMContentLoaded', function() {
    const items = document.querySelectorAll('.transaction-item');
    items.forEach((item, index) => {
        setTimeout(() => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(20px)';
            item.style.transition = 'all 0.5s ease';
            setTimeout(() => {
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, 50);
        }, index * 50);
    });
});
</script>