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

// Get selected class from URL parameter
$selected_kelas = isset($_GET['kelas']) ? intval($_GET['kelas']) : 0;

// Get user's classes
$user_classes = [];
$sql = "SELECT k.*, kas.saldo, a.tanggal_bergabung,
        (SELECT COUNT(*) FROM anggota WHERE id_kelas = k.id_kelas AND status = 'aktif') as total_members
        FROM anggota a
        JOIN kelas k ON a.id_kelas = k.id_kelas
        LEFT JOIN kas ON k.id_kelas = kas.id_kelas
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

// If no class selected and user has classes, select the first one
if ($selected_kelas == 0 && !empty($user_classes)) {
    $selected_kelas = $user_classes[0]['id_kelas'];
}

// Get selected class details
$class_details = null;
if ($selected_kelas > 0) {
    foreach ($user_classes as $class) {
        if ($class['id_kelas'] == $selected_kelas) {
            $class_details = $class;
            break;
        }
    }
}

// Get transactions for selected class
$transactions = [];
$total_pemasukan = 0;
$total_pengeluaran = 0;

if ($selected_kelas > 0) {
    $sql = "SELECT t.*, kas.saldo
            FROM transaksi t
            JOIN kas ON t.id_kas = kas.id_kas
            WHERE kas.id_kelas = ?
            ORDER BY t.tanggal DESC, t.created_at DESC";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, 'i', $selected_kelas);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $transactions[] = $row;
            if ($row['jenis'] === 'pemasukan') {
                $total_pemasukan += $row['jumlah'];
            } else {
                $total_pengeluaran += $row['jumlah'];
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Get monthly statistics for chart
$monthly_data = [];
if ($selected_kelas > 0) {
    $sql = "SELECT 
                DATE_FORMAT(t.tanggal, '%Y-%m') as bulan,
                SUM(CASE WHEN t.jenis = 'pemasukan' THEN t.jumlah ELSE 0 END) as pemasukan,
                SUM(CASE WHEN t.jenis = 'pengeluaran' THEN t.jumlah ELSE 0 END) as pengeluaran
            FROM transaksi t
            JOIN kas ON t.id_kas = kas.id_kas
            WHERE kas.id_kelas = ?
            GROUP BY DATE_FORMAT(t.tanggal, '%Y-%m')
            ORDER BY bulan DESC
            LIMIT 6";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, 'i', $selected_kelas);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $monthly_data[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}

$pageTitle = "Laporan Kelas - KasKelas";
include 'includes/header.php';
?>

<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }
    
    .report-page {
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
    
    .class-selector {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 2rem;
    }
    
    .class-info-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 2rem;
        color: white;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
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
    
    .chart-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 2rem;
    }
</style>

<div class="container report-page">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="mb-2 fw-bold">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i>
                    Laporan Kelas
                </h2>
                <p class="mb-0 opacity-75">
                    Lihat detail keuangan dan transaksi kelas Anda
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="user.php" class="btn btn-light rounded-pill">
                    <i class="bi bi-arrow-left me-2"></i>Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($user_classes)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h5 class="fw-bold mb-2">Belum Ada Kelas</h5>
            <p class="text-muted mb-3">Anda belum bergabung dengan kelas manapun</p>
            <a href="user.php" class="btn btn-primary rounded-pill">
                <i class="bi bi-plus-circle me-2"></i>Gabung Kelas Sekarang
            </a>
        </div>
    <?php else: ?>
        <!-- Class Selector -->
        <div class="class-selector">
            <label for="class-select" class="form-label fw-semibold">Pilih Kelas</label>
            <select id="class-select" class="form-select form-select-lg" onchange="window.location.href='laporan.php?kelas=' + this.value">
                <?php foreach ($user_classes as $class): ?>
                    <option value="<?php echo $class['id_kelas']; ?>" <?php echo $selected_kelas == $class['id_kelas'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($class['nama_kelas']); ?> (<?php echo htmlspecialchars($class['kode_kelas']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($class_details): ?>
            <!-- Class Info Card -->
            <div class="class-info-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="fw-bold mb-3"><?php echo htmlspecialchars($class_details['nama_kelas']); ?></h3>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-key fs-5"></i>
                                    <div>
                                        <small class="opacity-75 d-block">Kode Kelas</small>
                                        <strong><?php echo htmlspecialchars($class_details['kode_kelas']); ?></strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-people fs-5"></i>
                                    <div>
                                        <small class="opacity-75 d-block">Anggota</small>
                                        <strong><?php echo $class_details['total_members']; ?> Orang</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-calendar-check fs-5"></i>
                                    <div>
                                        <small class="opacity-75 d-block">Bergabung</small>
                                        <strong><?php echo format_date_id($class_details['tanggal_bergabung']); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <small class="opacity-75 d-block mb-2">Saldo Kelas</small>
                        <h2 class="fw-bold mb-0"><?php echo format_rupiah($class_details['saldo']); ?></h2>
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
                            <i class="bi bi-arrow-up-circle me-1"></i>Semua Waktu
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
                            <i class="bi bi-arrow-down-circle me-1"></i>Semua Waktu
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
                            <i class="bi bi-list-ul me-1"></i>Semua Transaksi
                        </small>
                    </div>
                </div>
            </div>

            <!-- Monthly Chart -->
            <?php if (!empty($monthly_data)): ?>
                <div class="chart-card">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-bar-chart me-2 text-primary"></i>
                        Grafik Bulanan (6 Bulan Terakhir)
                    </h5>
                    <canvas id="monthlyChart" height="80"></canvas>
                </div>
            <?php endif; ?>

            <!-- Transactions List -->
            <div class="transaction-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-list-ul me-2 text-primary"></i>
                        Riwayat Transaksi (<?php echo count($transactions); ?>)
                    </h5>
                    <a href="transaksi.php?kelas=<?php echo $selected_kelas; ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                        <i class="bi bi-eye me-1"></i>Lihat Semua
                    </a>
                </div>
                
                <?php if (empty($transactions)): ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h5 class="fw-bold mb-2">Belum Ada Transaksi</h5>
                        <p class="text-muted">Kelas ini belum memiliki transaksi</p>
                    </div>
                <?php else: ?>
                    <?php 
                    // Show only last 10 transactions
                    $displayed_transactions = array_slice($transactions, 0, 10);
                    foreach ($displayed_transactions as $trans): 
                    ?>
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
                                            <?php if ($trans['deskripsi']): ?>
                                                <small class="text-muted d-block">
                                                    <?php echo htmlspecialchars($trans['deskripsi']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mt-3 mt-md-0">
                                    <small class="text-muted d-block mb-1">Tanggal</small>
                                    <div class="fw-semibold">
                                        <?php echo date('d/m/Y', strtotime($trans['tanggal'])); ?>
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
                    
                    <?php if (count($transactions) > 10): ?>
                        <div class="text-center mt-3">
                            <a href="transaksi.php?kelas=<?php echo $selected_kelas; ?>" class="btn btn-outline-primary rounded-pill">
                                <i class="bi bi-arrow-right-circle me-2"></i>
                                Lihat <?php echo count($transactions) - 10; ?> Transaksi Lainnya
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<?php if (!empty($monthly_data)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Prepare data for chart
const monthlyData = <?php echo json_encode(array_reverse($monthly_data)); ?>;
const labels = monthlyData.map(d => {
    const [year, month] = d.bulan.split('-');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return months[parseInt(month) - 1] + ' ' + year;
});
const pemasukanData = monthlyData.map(d => parseFloat(d.pemasukan));
const pengeluaranData = monthlyData.map(d => parseFloat(d.pengeluaran));

// Create chart
const ctx = document.getElementById('monthlyChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Pemasukan',
                data: pemasukanData,
                backgroundColor: 'rgba(40, 167, 69, 0.8)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 2
            },
            {
                label: 'Pengeluaran',
                data: pengeluaranData,
                backgroundColor: 'rgba(220, 53, 69, 0.8)',
                borderColor: 'rgba(220, 53, 69, 1)',
                borderWidth: 2
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                        return label;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + value.toLocaleString('id-ID');
                    }
                }
            }
        }
    }
});
</script>
<?php endif; ?>

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