<?php
require_once 'includes/db_config.php';
require_once 'includes/function.php';

echo "<h1>FORM TEST SEDERHANA</h1>";

if ($_POST) {
    echo "<h2>Data yang dikirim:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Proses langsung tanpa validasi kompleks
    $id_kelas = $_POST['id_kelas'];
    $jenis = $_POST['jenis'];
    $jumlah = $_POST['jumlah'];
    $tanggal = $_POST['tanggal'];
    $deskripsi = $_POST['deskripsi'];
    $admin_id = 1;
    
    // Dapatkan id_kas
    $id_kas = 1; // Langsung pakai 1 karena kita tahu id_kas=1 untuk kelas 15
    
    // Insert
    $sql = "INSERT INTO transaksi (id_kas, jenis, jumlah, tanggal, deskripsi, created_by) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isdssi", $id_kas, $jenis, $jumlah, $tanggal, $deskripsi, $admin_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<p style='color: green;'>✅ BERHASIL! ID: " . mysqli_insert_id($conn) . "</p>";
        
        // Update saldo
        if ($jenis === 'pemasukan') {
            $update_sql = "UPDATE kas SET saldo = saldo + ? WHERE id_kas = ?";
        } else {
            $update_sql = "UPDATE kas SET saldo = saldo - ? WHERE id_kas = ?";
        }
        
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "di", $jumlah, $id_kas);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
        
        echo "<p style='color: green;'>✅ Saldo updated!</p>";
    } else {
        echo "<p style='color: red;'>❌ GAGAL: " . mysqli_error($conn) . "</p>";
    }
    mysqli_stmt_close($stmt);
}
?>

<form method="POST">
    <div>
        <label>Kelas:</label>
        <select name="id_kelas">
            <option value="15">kelas homok (ID: 15)</option>
        </select>
    </div>
    <div>
        <label>Jenis:</label>
        <select name="jenis">
            <option value="pemasukan">Pemasukan</option>
            <option value="pengeluaran">Pengeluaran</option>
        </select>
    </div>
    <div>
        <label>Jumlah:</label>
        <input type="number" name="jumlah" value="50000" required>
    </div>
    <div>
        <label>Tanggal:</label>
        <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required>
    </div>
    <div>
        <label>Deskripsi:</label>
        <input type="text" name="deskripsi" value="Test dari form sederhana">
    </div>
    <button type="submit">Test Simpan</button>
</form>