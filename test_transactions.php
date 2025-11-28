<?php
// Koneksi ke database
require_once './includes/db_config.php';

// Fungsi untuk mendapatkan data transaksi
function getTransaksi($conn) {
    $sql = "SELECT t.*, k.nama_kelas, kas.id_kelas 
            FROM transaksi t 
            JOIN kas ON t.id_kas = kas.id_kas 
            JOIN kelas k ON kas.id_kelas = k.id_kelas 
            ORDER BY t.tanggal DESC, t.created_at DESC";
    $result = mysqli_query($conn, $sql);
    return $result;
}

// Fungsi untuk mendapatkan data kelas
function getKelas($conn) {
    $sql = "SELECT k.*, kas.id_kas FROM kelas k JOIN kas ON k.id_kelas = kas.id_kelas WHERE k.status = 'aktif'";
    $result = mysqli_query($conn, $sql);
    return $result;
}

// Fungsi untuk mendapatkan data kas berdasarkan kelas
function getKasByKelas($conn, $id_kelas) {
    $sql = "SELECT id_kas FROM kas WHERE id_kelas = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_kelas);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row ? $row['id_kas'] : null;
}

// Proses Tambah Transaksi
if (isset($_POST['tambah_transaksi'])) {
    $id_kelas = $_POST['id_kelas'];
    $jenis = $_POST['jenis'];
    $jumlah = $_POST['jumlah'];
    $tanggal = $_POST['tanggal'];
    $kategori = $_POST['kategori'];
    $deskripsi = $_POST['deskripsi'];
    $created_by = 1; // ID admin dari session, sementara hardcode
    
    // Dapatkan id_kas dari id_kelas
    $id_kas = getKasByKelas($conn, $id_kelas);
    
    if ($id_kas) {
        $sql = "INSERT INTO transaksi (id_kas, jenis, jumlah, tanggal, deskripsi, kategori, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isssssi", $id_kas, $jenis, $jumlah, $tanggal, $deskripsi, $kategori, $created_by);
        
        if (mysqli_stmt_execute($stmt)) {
            $pesan_sukses = "Transaksi berhasil ditambahkan!";
        } else {
            $pesan_error = "Error: " . mysqli_error($conn);
        }
    } else {
        $pesan_error = "Error: Kas untuk kelas ini tidak ditemukan!";
    }
}

// Proses Edit Transaksi
if (isset($_POST['edit_transaksi'])) {
    $id_transaksi = $_POST['id_transaksi'];
    $id_kelas = $_POST['id_kelas'];
    $jenis = $_POST['jenis'];
    $jumlah = $_POST['jumlah'];
    $tanggal = $_POST['tanggal'];
    $kategori = $_POST['kategori'];
    $deskripsi = $_POST['deskripsi'];
    
    // Dapatkan id_kas dari id_kelas
    $id_kas = getKasByKelas($conn, $id_kelas);
    
    if ($id_kas) {
        $sql = "UPDATE transaksi SET id_kas = ?, jenis = ?, jumlah = ?, tanggal = ?, deskripsi = ?, kategori = ? 
                WHERE id_transaksi = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isssssi", $id_kas, $jenis, $jumlah, $tanggal, $deskripsi, $kategori, $id_transaksi);
        
        if (mysqli_stmt_execute($stmt)) {
            $pesan_sukses = "Transaksi berhasil diupdate!";
        } else {
            $pesan_error = "Error: " . mysqli_error($conn);
        }
    } else {
        $pesan_error = "Error: Kas untuk kelas ini tidak ditemukan!";
    }
}

// Proses Hapus Transaksi
if (isset($_GET['hapus'])) {
    $id_transaksi = $_GET['hapus'];
    
    $sql = "DELETE FROM transaksi WHERE id_transaksi = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_transaksi);
    
    if (mysqli_stmt_execute($stmt)) {
        $pesan_sukses = "Transaksi berhasil dihapus!";
    } else {
        $pesan_error = "Error: " . mysqli_error($conn);
    }
}

// Ambil data untuk ditampilkan
$transaksi = getTransaksi($conn);
$kelas = getKelas($conn);

// Hitung statistik
$sql_pemasukan = "SELECT SUM(jumlah) as total FROM transaksi WHERE jenis = 'pemasukan'";
$result_pemasukan = mysqli_query($conn, $sql_pemasukan);
$total_pemasukan = mysqli_fetch_assoc($result_pemasukan)['total'] ?? 0;

$sql_pengeluaran = "SELECT SUM(jumlah) as total FROM transaksi WHERE jenis = 'pengeluaran'";
$result_pengeluaran = mysqli_query($conn, $sql_pengeluaran);
$total_pengeluaran = mysqli_fetch_assoc($result_pengeluaran)['total'] ?? 0;

$saldo_kas = $total_pemasukan - $total_pengeluaran;

$sql_jumlah = "SELECT COUNT(*) as total FROM transaksi";
$result_jumlah = mysqli_query($conn, $sql_jumlah);
$jumlah_transaksi = mysqli_fetch_assoc($result_jumlah)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Transaksi - Kasku Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 bg-primary text-white vh-100 p-3">
                <h4 class="text-center mb-4"><i class="fas fa-wallet"></i> Kasku Admin</h4>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="admin_dashboard.php" class="nav-link text-white">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_kelas.php" class="nav-link text-white">
                            <i class="fas fa-chalkboard me-2"></i>Kelola Kelas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_transaksi.php" class="nav-link active bg-light text-dark">
                            <i class="fas fa-exchange-alt me-2"></i>Kelola Transaksi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_iuran.php" class="nav-link text-white">
                            <i class="fas fa-money-bill-wave me-2"></i>Kelola Iuran
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_laporan.php" class="nav-link text-white">
                            <i class="fas fa-chart-bar me-2"></i>Laporan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_pengguna.php" class="nav-link text-white">
                            <i class="fas fa-users me-2"></i>Pengguna
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
                    <div class="container-fluid">
                        <div class="collapse navbar-collapse">
                            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                                <li class="nav-item">
                                    <a class="nav-link active" href="#">Beranda</a>
                                </li>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Pengaturan
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                        <li><a class="dropdown-item" href="#">Profil</a></li>
                                        <li><a class="dropdown-item" href="#">Keamanan</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="#">Notifikasi</a></li>
                                    </ul>
                                </li>
                            </ul>
                            <form class="d-flex">
                                <div class="input-group">
                                    <input class="form-control" type="search" placeholder="Cari transaksi..." aria-label="Search">
                                    <button class="btn btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </nav>

                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold"><i class="fas fa-exchange-alt me-2"></i>Kelola Transaksi</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahTransaksiModal">
                        <i class="fas fa-plus me-1"></i>Tambah Transaksi
                    </button>
                </div>

                <!-- Alert Pesan -->
                <?php if (isset($pesan_sukses)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $pesan_sukses; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($pesan_error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $pesan_error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Pemasukan</h5>
                                        <h3 class="fw-bold">Rp <?php echo number_format($total_pemasukan, 0, ',', '.'); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-arrow-down fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Pengeluaran</h5>
                                        <h3 class="fw-bold">Rp <?php echo number_format($total_pengeluaran, 0, ',', '.'); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-arrow-up fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Saldo Kas</h5>
                                        <h3 class="fw-bold">Rp <?php echo number_format($saldo_kas, 0, ',', '.'); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-wallet fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Jumlah Transaksi</h5>
                                        <h3 class="fw-bold"><?php echo $jumlah_transaksi; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-exchange-alt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Daftar Transaksi</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Tanggal</th>
                                        <th scope="col">Kelas</th>
                                        <th scope="col">Jenis</th>
                                        <th scope="col">Jumlah</th>
                                        <th scope="col">Kategori</th>
                                        <th scope="col">Deskripsi</th>
                                        <th scope="col">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    if (mysqli_num_rows($transaksi) > 0):
                                        while ($row = mysqli_fetch_assoc($transaksi)): 
                                    ?>
                                        <tr>
                                            <th scope="row"><?php echo $no++; ?></th>
                                            <td><?php echo date('d M Y', strtotime($row['tanggal'])); ?></td>
                                            <td><?php echo $row['nama_kelas']; ?></td>
                                            <td>
                                                <?php if ($row['jenis'] == 'pemasukan'): ?>
                                                    <span class="badge bg-success">Pemasukan</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Pengeluaran</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="fw-bold <?php echo $row['jenis'] == 'pemasukan' ? 'text-success' : 'text-danger'; ?>">
                                                Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?>
                                            </td>
                                            <td><?php echo $row['kategori']; ?></td>
                                            <td><?php echo $row['deskripsi']; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary me-1" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editTransaksiModal"
                                                        data-id="<?php echo $row['id_transaksi']; ?>"
                                                        data-kelas="<?php echo $row['id_kelas']; ?>"
                                                        data-jenis="<?php echo $row['jenis']; ?>"
                                                        data-jumlah="<?php echo $row['jumlah']; ?>"
                                                        data-tanggal="<?php echo $row['tanggal']; ?>"
                                                        data-kategori="<?php echo $row['kategori']; ?>"
                                                        data-deskripsi="<?php echo $row['deskripsi']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?hapus=<?php echo $row['id_transaksi']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Yakin ingin menghapus transaksi ini?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php 
                                        endwhile;
                                    else: 
                                    ?>
                                        <tr>
                                            <td colspan="8" class="text-center">Tidak ada data transaksi</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Transaksi -->
    <div class="modal fade" id="tambahTransaksiModal" tabindex="-1" aria-labelledby="tambahTransaksiModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tambahTransaksiModalLabel"><i class="fas fa-plus me-2"></i>Tambah Transaksi Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="id_kelas" class="form-label">Kelas</label>
                                <select class="form-select" id="id_kelas" name="id_kelas" required>
                                    <option value="" selected disabled>Pilih Kelas</option>
                                    <?php while ($row_kelas = mysqli_fetch_assoc($kelas)): ?>
                                        <option value="<?php echo $row_kelas['id_kelas']; ?>">
                                            <?php echo $row_kelas['nama_kelas']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="jenis" class="form-label">Jenis Transaksi</label>
                                <select class="form-select" id="jenis" name="jenis" required>
                                    <option value="" selected disabled>Pilih Jenis</option>
                                    <option value="pemasukan">Pemasukan</option>
                                    <option value="pengeluaran">Pengeluaran</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="jumlah" class="form-label">Jumlah</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="jumlah" name="jumlah" placeholder="Masukkan jumlah" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="tanggal" class="form-label">Tanggal</label>
                                <input type="date" class="form-control" id="tanggal" name="tanggal" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="kategori" class="form-label">Kategori</label>
                                <input type="text" class="form-control" id="kategori" name="kategori" placeholder="Masukkan kategori" required>
                            </div>
                            <div class="col-md-6">
                                <label for="bukti" class="form-label">Bukti Transaksi</label>
                                <input class="form-control" type="file" id="bukti" name="bukti">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" placeholder="Masukkan deskripsi transaksi"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" name="tambah_transaksi">Simpan Transaksi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Transaksi -->
    <div class="modal fade" id="editTransaksiModal" tabindex="-1" aria-labelledby="editTransaksiModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editTransaksiModalLabel"><i class="fas fa-edit me-2"></i>Edit Data Transaksi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_id_transaksi" name="id_transaksi">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_id_kelas" class="form-label">Kelas</label>
                                <select class="form-select" id="edit_id_kelas" name="id_kelas" required>
                                    <?php 
                                    // Reset pointer result set
                                    mysqli_data_seek($kelas, 0);
                                    while ($row_kelas = mysqli_fetch_assoc($kelas)): 
                                    ?>
                                        <option value="<?php echo $row_kelas['id_kelas']; ?>">
                                            <?php echo $row_kelas['nama_kelas']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_jenis" class="form-label">Jenis Transaksi</label>
                                <select class="form-select" id="edit_jenis" name="jenis" required>
                                    <option value="pemasukan">Pemasukan</option>
                                    <option value="pengeluaran">Pengeluaran</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_jumlah" class="form-label">Jumlah</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="edit_jumlah" name="jumlah" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_tanggal" class="form-label">Tanggal</label>
                                <input type="date" class="form-control" id="edit_tanggal" name="tanggal" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_kategori" class="form-label">Kategori</label>
                                <input type="text" class="form-control" id="edit_kategori" name="kategori" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_bukti" class="form-label">Bukti Transaksi</label>
                                <input class="form-control" type="file" id="edit_bukti" name="bukti">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="edit_deskripsi" name="deskripsi" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" name="edit_transaksi">Perbarui Transaksi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script untuk mengisi form edit dengan data dari tabel
        document.addEventListener('DOMContentLoaded', function() {
            var editModal = document.getElementById('editTransaksiModal');
            editModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                
                document.getElementById('edit_id_transaksi').value = button.getAttribute('data-id');
                document.getElementById('edit_id_kelas').value = button.getAttribute('data-kelas');
                document.getElementById('edit_jenis').value = button.getAttribute('data-jenis');
                document.getElementById('edit_jumlah').value = button.getAttribute('data-jumlah');
                document.getElementById('edit_tanggal').value = button.getAttribute('data-tanggal');
                document.getElementById('edit_kategori').value = button.getAttribute('data-kategori');
                document.getElementById('edit_deskripsi').value = button.getAttribute('data-deskripsi');
            });
            
            // Set tanggal hari ini sebagai default di form tambah
            document.getElementById('tanggal').valueAsDate = new Date();
        });
    </script>
</body>
</html>

<?php
// Tutup koneksi database
mysqli_close($conn);
?>