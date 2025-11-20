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

// Get user's classes
$user_classes = [];
$sql = "SELECT k.*, kas.saldo, a.tanggal_bergabung, a.status as member_status,
        (SELECT COUNT(*) FROM anggota WHERE id_kelas = k.id_kelas AND status = 'aktif') as total_members
        FROM anggota a
        JOIN kelas k ON a.id_kelas = k.id_kelas
        LEFT JOIN kas ON k.id_kelas = kas.id_kelas
        WHERE a.id_user = ? AND a.status = 'aktif'
        ORDER BY a.tanggal_bergabung DESC";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $user_classes[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Get recent transactions from user's classes
$recent_transactions = [];
$sql = "SELECT t.*, k.nama_kelas, k.kode_kelas, kas.saldo
        FROM transaksi t
        JOIN kas ON t.id_kas = kas.id_kas
        JOIN kelas k ON kas.id_kelas = k.id_kelas
        JOIN anggota a ON k.id_kelas = a.id_kelas
        WHERE a.id_user = ? AND a.status = 'aktif'
        ORDER BY t.created_at DESC
        LIMIT 10";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $recent_transactions[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Calculate statistics
$total_classes = count($user_classes);
$total_balance = 0;
$total_transactions = 0;

foreach ($user_classes as $class) {
    $total_balance += $class['saldo'];
}

// Count total transactions
$sql = "SELECT COUNT(*) as total FROM transaksi t
        JOIN kas ON t.id_kas = kas.id_kas
        JOIN kelas k ON kas.id_kelas = k.id_kelas
        JOIN anggota a ON k.id_kelas = a.id_kelas
        WHERE a.id_user = ? AND a.status = 'aktif'";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $total_transactions = $row['total'];
    mysqli_stmt_close($stmt);
}

$pageTitle = "Dashboard Siswa - KasKelas";
include 'includes/header.php';
?>

<!-- Custom User Dashboard CSS -->
<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }
    
    .user-dashboard {
        padding-top: 100px;
        padding-bottom: 50px;
    }
    
    .welcome-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 2rem;
        color: white;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
        margin-bottom: 2rem;
    }
    
    .quick-action-btn {
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .quick-action-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
        color: white;
        transform: translateY(-2px);
    }
    
    .stats-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border-left: 4px solid #667eea;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }
    
    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        color: white;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .class-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border-top: 4px solid #667eea;
        margin-bottom: 1.5rem;
    }
    
    .class-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }
    
    .transaction-item {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1rem;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }
    
    .transaction-item:hover {
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        transform: translateX(5px);
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

<!-- Main Dashboard Content -->
<div class="container user-dashboard">
    <!-- Welcome Card -->
    <div class="welcome-card">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="mb-2 fw-bold">
                    <i class="bi bi-hand-wave me-2"></i>
                    Selamat Datang, <?php echo htmlspecialchars($username); ?>!
                </h2>
                <p class="mb-3 opacity-75">
                    Pantau kas kelas Anda dengan mudah dan transparan
                </p>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="transaksi.php" class="quick-action-btn">
                        <i class="bi bi-clock-history"></i>
                        Riwayat Transaksi
                    </a>
                    <a href="laporan.php" class="quick-action-btn">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        Laporan Kelas
                    </a>
                </div>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <div class="d-flex flex-column gap-2 align-items-md-end">
                    <small class="opacity-75">
                        <i class="bi bi-calendar-event me-2"></i>
                        <?php echo format_date_id(date('Y-m-d')); ?>
                    </small>
                    <small class="opacity-75">
                        <i class="bi bi-clock me-2"></i>
                        <?php echo date('H:i'); ?> WIB
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small mb-2">Total Kelas</div>
                        <h3 class="fw-bold mb-0" style="color: #667eea;">
                            <?php echo $total_classes; ?>
                        </h3>
                        <small class="text-success">
                            <i class="bi bi-check-circle me-1"></i>Kelas Aktif
                        </small>
                    </div>
                    <div class="stats-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stats-card" style="border-left-color: #28a745;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small mb-2">Total Saldo</div>
                        <h3 class="fw-bold mb-0" style="color: #28a745;">
                            <?php echo format_rupiah($total_balance); ?>
                        </h3>
                        <small class="text-info">
                            <i class="bi bi-wallet2 me-1"></i>Semua Kelas
                        </small>
                    </div>
                    <div class="stats-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stats-card" style="border-left-color: #ffc107;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small mb-2">Total Transaksi</div>
                        <h3 class="fw-bold mb-0" style="color: #ffc107;">
                            <?php echo $total_transactions; ?>
                        </h3>
                        <small class="text-warning">
                            <i class="bi bi-graph-up me-1"></i>Semua Waktu
                        </small>
                    </div>
                    <div class="stats-icon" style="background: linear-gradient(135deg, #ffc107, #ff6b6b);">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- My Classes Section -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-people me-2 text-primary"></i>Kelas Saya
                </h4>
                <button class="btn btn-primary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#joinClassModal">
                    <i class="bi bi-plus-circle me-2"></i>Gabung Kelas
                </button>
            </div>
            
            <?php if (empty($user_classes)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h5 class="fw-bold mb-2">Belum Ada Kelas</h5>
                    <p class="text-muted mb-3">Anda belum bergabung dengan kelas manapun</p>
                    <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#joinClassModal">
                        <i class="bi bi-plus-circle me-2"></i>Gabung Kelas Sekarang
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($user_classes as $class): ?>
                    <div class="class-card">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                                        <i class="bi bi-building fs-3 text-primary"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($class['nama_kelas']); ?></h5>
                                        <div class="d-flex gap-3 mb-2">
                                            <small class="text-muted">
                                                <i class="bi bi-key me-1"></i>
                                                <code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($class['kode_kelas']); ?></code>
                                            </small>
                                            <small class="text-muted">
                                                <i class="bi bi-people me-1"></i>
                                                <?php echo $class['total_members']; ?> Anggota
                                            </small>
                                        </div>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar-check me-1"></i>
                                            Bergabung: <?php echo format_date_id($class['tanggal_bergabung']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <div class="mb-2">
                                    <small class="text-muted d-block mb-1">Saldo Kelas</small>
                                    <h4 class="fw-bold text-success mb-0">
                                        <?php echo format_rupiah($class['saldo']); ?>
                                    </h4>
                                </div>
                                <a href="laporan.php?kelas=<?php echo $class['id_kelas']; ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                    <i class="bi bi-eye me-1"></i>Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Recent Transactions Section -->
        <div class="col-lg-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-clock-history me-2 text-primary"></i>Transaksi Terbaru
                </h4>
                <?php if (!empty($recent_transactions)): ?>
                    <a href="transaksi.php" class="btn btn-sm btn-outline-primary rounded-pill">
                        <i class="bi bi-arrow-right me-1"></i>Lihat Semua
                    </a>
                <?php endif; ?>
            </div>
            
            <div style="max-height: 600px; overflow-y: auto;">
                <?php if (empty($recent_transactions)): ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p class="text-muted mb-0">Belum ada transaksi</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_transactions as $trans): ?>
                        <div class="transaction-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <?php echo get_transaction_badge($trans['jenis']); ?>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y', strtotime($trans['tanggal'])); ?>
                                </small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <small class="text-muted d-block">
                                        <i class="bi bi-building me-1"></i>
                                        <?php echo htmlspecialchars($trans['nama_kelas']); ?>
                                    </small>
                                    <?php if ($trans['deskripsi']): ?>
                                        <small class="text-muted d-block">
                                            <?php echo htmlspecialchars(substr($trans['deskripsi'], 0, 50)) . (strlen($trans['deskripsi']) > 50 ? '...' : ''); ?>
                                        </small>
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
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Join Class Modal -->
<div class="modal fade" id="joinClassModal" tabindex="-1" aria-labelledby="joinClassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 bg-primary text-white" style="border-radius: 20px 20px 0 0;">
                <h5 class="modal-title fw-bold" id="joinClassModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Gabung Kelas Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="join_class.php" method="POST">
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-key-fill text-primary" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2">Masukkan kode kelas yang diberikan oleh bendahara</p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="kode_kelas" class="form-label fw-semibold">Kode Kelas</label>
                        <input type="text" 
                               class="form-control form-control-lg text-center" 
                               id="kode_kelas" 
                               name="kode_kelas" 
                               placeholder="Contoh: ABC123" 
                               required
                               maxlength="10"
                               style="letter-spacing: 2px; font-weight: 600; text-transform: uppercase;">
                        <small class="text-muted">Kode terdiri dari 6 karakter huruf dan angka</small>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-check-circle me-2"></i>Gabung Kelas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Auto-format class code input
document.getElementById('kode_kelas').addEventListener('input', function(e) {
    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
});

// Professional animations
document.addEventListener('DOMContentLoaded', function() {
    // Animate stats cards
    const statsCards = document.querySelectorAll('.stats-card');
    statsCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.5s ease';
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 50);
        }, index * 100);
    });
    
    // Animate class cards
    const classCards = document.querySelectorAll('.class-card');
    classCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateX(-20px)';
            card.style.transition = 'all 0.5s ease';
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateX(0)';
            }, 50);
        }, index * 150);
    });
});
</script>