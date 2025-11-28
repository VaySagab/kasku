<?php
require_once 'includes/db_config.php';

echo "<h1>TEST DATABASE LANGSUNG</h1>";

// Test 1: Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
echo "<p style='color: green;'>✅ Koneksi database berhasil</p>";

// Test 2: Cek struktur tabel transaksi
echo "<h2>Struktur Tabel transaksi:</h2>";
$result = mysqli_query($conn, "DESCRIBE transaksi");
if ($result) {
    echo "<table border='1'>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Error: " . mysqli_error($conn) . "</p>";
}

// Test 3: Insert data langsung
echo "<h2>Test Insert Data Langsung:</h2>";
$test_sql = "INSERT INTO transaksi (id_kas, jenis, jumlah, tanggal, deskripsi, created_by) 
             VALUES (1, 'pemasukan', 10000, '2024-01-01', 'Test langsung', 1)";

if (mysqli_query($conn, $test_sql)) {
    echo "<p style='color: green;'>✅ Insert langsung BERHASIL! ID: " . mysqli_insert_id($conn) . "</p>";
    
    // Test update saldo
    $update_sql = "UPDATE kas SET saldo = saldo + 10000 WHERE id_kas = 1";
    if (mysqli_query($conn, $update_sql)) {
        echo "<p style='color: green;'>✅ Update saldo BERHASIL!</p>";
    } else {
        echo "<p style='color: red;'>❌ Update saldo GAGAL: " . mysqli_error($conn) . "</p>";
    }
    
    // Hapus data test
    $delete_sql = "DELETE FROM transaksi WHERE deskripsi = 'Test langsung'";
    mysqli_query($conn, $delete_sql);
    
} else {
    echo "<p style='color: red;'>❌ Insert langsung GAGAL: " . mysqli_error($conn) . "</p>";
}

// Test 4: Cek data terakhir
echo "<h2>5 Transaksi Terakhir:</h2>";
$result = mysqli_query($conn, "SELECT * FROM transaksi ORDER BY id_transaksi DESC LIMIT 5");
if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>ID Kas</th><th>Jenis</th><th>Jumlah</th><th>Tanggal</th><th>Deskripsi</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['id_transaksi']}</td>";
        echo "<td>{$row['id_kas']}</td>";
        echo "<td>{$row['jenis']}</td>";
        echo "<td>{$row['jumlah']}</td>";
        echo "<td>{$row['tanggal']}</td>";
        echo "<td>{$row['deskripsi']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Tidak ada transaksi</p>";
}

// Test 5: Cek saldo kas
echo "<h2>Saldo Kas:</h2>";
$result = mysqli_query($conn, "SELECT * FROM kas WHERE id_kas = 1");
if ($result && $row = mysqli_fetch_assoc($result)) {
    echo "<p>ID Kas: {$row['id_kas']}, Saldo: " . number_format($row['saldo'], 2) . "</p>";
}
?>