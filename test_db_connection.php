<?php
require_once 'includes/db_config.php';

echo "<h1>Database Connection Test</h1>";

if (!$conn) {
    die("❌ Koneksi database GAGAL: " . mysqli_connect_error());
}

echo "✅ Koneksi database BERHASIL<br>";

// Test INSERT
echo "<h2>Test INSERT Transaction</h2>";
$test_sql = "INSERT INTO transaksi (id_kas, jenis, jumlah, tanggal, deskripsi, created_by) 
             VALUES (1, 'pemasukan', 100000, '2024-01-01', 'Test dari connection test', 1)";

if (mysqli_query($conn, $test_sql)) {
    $insert_id = mysqli_insert_id($conn);
    echo "✅ Insert BERHASIL! ID: $insert_id<br>";
    
    // Test SELECT
    $select_sql = "SELECT * FROM transaksi WHERE id_transaksi = $insert_id";
    $result = mysqli_query($conn, $select_sql);
    if ($result && mysqli_num_rows($result) > 0) {
        echo "✅ Data ditemukan setelah insert<br>";
    } else {
        echo "❌ Data TIDAK ditemukan setelah insert!<br>";
    }
    
    // Hapus data test
    mysqli_query($conn, "DELETE FROM transaksi WHERE id_transaksi = $insert_id");
} else {
    echo "❌ Insert GAGAL: " . mysqli_error($conn) . "<br>";
}

// Test kas data
echo "<h2>Test Kas Data</h2>";
$kas_sql = "SELECT * FROM kas WHERE id_kelas = 15";
$kas_result = mysqli_query($conn, $kas_sql);
if ($kas_result && mysqli_num_rows($kas_result) > 0) {
    $kas_data = mysqli_fetch_assoc($kas_result);
    echo "✅ Kas data ditemukan: ID Kas = " . $kas_data['id_kas'] . ", Saldo = " . $kas_data['saldo'] . "<br>";
} else {
    echo "❌ Kas data TIDAK ditemukan untuk kelas 15<br>";
}

// Test current transactions
echo "<h2>Current Transactions Count</h2>";
$count_sql = "SELECT COUNT(*) as total FROM transaksi";
$count_result = mysqli_query($conn, $count_sql);
$count_data = mysqli_fetch_assoc($count_result);
echo "Total transaksi: " . $count_data['total'] . "<br>";
?>