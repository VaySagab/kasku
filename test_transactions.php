<?php
// test_transaction.php
require_once 'includes/db_config.php';
require_once 'includes/function.php';

// Test langsung insert ke database
$test_sql = "INSERT INTO transaksi (id_kas, jenis, jumlah, tanggal, deskripsi, created_by) 
             VALUES (1, 'pemasukan', 10000, '2025-11-20', 'Test langsung', 1)";

if (mysqli_query($conn, $test_sql)) {
    echo "SUCCESS: Test transaction inserted. ID: " . mysqli_insert_id($conn);
} else {
    echo "ERROR: " . mysqli_error($conn);
}
?>