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

// Handle Remove Member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_member'])) {
    $anggota_id = intval($_POST['anggota_id']);
    
    $sql = "UPDATE anggota a
            JOIN kelas k ON a.id_kelas = k.id_kelas
            SET a.status = 'nonaktif'
            WHERE a.id_anggota = ? AND k.id_admin = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, 'ii', $anggota_id, $admin_id);
        if (mysqli_stmt_execute($stmt)) {
            $message = 'Anggota berhasil dihapus dari kelas!';
            $message_type = 'success';
        } else {
            $message = 'Gagal menghapus anggota!';
            $message_type = 'danger';
        }
        mysqli_stmt_close($stmt);
    }
}

// Get filter parameters
$filter_kelas = isset($_GET['kelas']) ? intval($_GET['kelas']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all members
$members = [];
$sql = "SELECT a.*, u.username, u.email, k.nama_kelas, k.id_kelas,
        (SELECT COUNT(*) FROM transaksi t 
         JOIN kas ks ON t.id_kas = ks.id_kas 
         WHERE ks.id_kelas = a.id_kelas AND t.created_by = u.id_user) as transaction_count
        FROM anggota a
        JOIN user u ON a.id_user = u.id_user
        JOIN kelas k ON a.id_kelas = k.id_kelas
        WHERE k.id_admin = ? AND a.status = 'aktif'";

$params = [$admin_id];
$types = 'i';

if ($filter_kelas > 0) {
    $sql .= " AND k.id_kelas = ?";
    $params[] = $filter_kelas;
    $types .= 'i';
}

if (!empty($search)) {
    $sql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$sql .= " ORDER BY a.tanggal_bergabung DESC";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $members[] = $row;
    }
    mysqli_stmt_close($stmt);
}

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

$pageTitle = "Manajemen Anggota - Admin KasKelas";
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
                    <a class="nav-link active" href="admin_members.php">
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
            <h2 class="mb-1 fw-bold">
                <i class="bi bi-person-badge text-primary me-2"></i>Manajemen Anggota
            </h2>
            <p class="text-muted mb-0">Kelola semua anggota di kelas Anda</p>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($message)): ?>
        <div class="alert kas-alert kas-alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <i class="bi <?php echo $message_type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?> me-2"></i>
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

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
                        <div class="col-md-6">
                            <label for="search" class="form-label fw-semibold">Cari Anggota</label>
                            <input type="text" name="search" id="search" class="form-control kas-form-control" 
                                   placeholder="Cari berdasarkan username atau email..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn kas-btn kas-btn-primary w-100">
                                <i class="bi bi-search me-2"></i>Cari
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Members List -->
    <div class="row">
        <div class="col-12">
            <div class="kas-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-ul me-2"></i>Daftar Anggota (<?php echo count($members); ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($members)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                            <p class="text-muted mt-3">Tidak ada anggota ditemukan</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table kas-table table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Kelas</th>
                                        <th>Bergabung</th>
                                        <th>Transaksi</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($members as $member): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                                                         style="width: 40px; height: 40px;">
                                                        <i class="bi bi-person-fill"></i>
                                                    </div>
                                                    <strong><?php echo htmlspecialchars($member['username']); ?></strong>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($member['nama_kelas']); ?></span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($member['tanggal_bergabung'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo $member['transaction_count']; ?> transaksi</span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-danger" onclick="removeMember(<?php echo $member['id_anggota']; ?>, '<?php echo htmlspecialchars($member['username']); ?>', '<?php echo htmlspecialchars($member['nama_kelas']); ?>')">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
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

<!-- Remove Member Modal -->
<div class="modal fade" id="removeMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Hapus Anggota</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="anggota_id" id="remove_anggota_id">
                <div class="modal-body">
                    <div class="alert alert-danger border-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Perhatian!</strong> Tindakan ini tidak dapat dibatalkan.
                    </div>
                    <p>Apakah Anda yakin ingin menghapus <strong id="remove_username"></strong> dari kelas <strong id="remove_kelas_name"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="remove_member" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Hapus Anggota
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function removeMember(anggotaId, username, kelasName) {
    document.getElementById('remove_anggota_id').value = anggotaId;
    document.getElementById('remove_username').textContent = username;
    document.getElementById('remove_kelas_name').textContent = kelasName;
    new bootstrap.Modal(document.getElementById('removeMemberModal')).show();
}
</script>

<?php include 'includes/admin_footer.php'; ?>