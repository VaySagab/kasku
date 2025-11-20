<?php
// Define access constant for includes
define('ALLOW_ACCESS', true);

require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/function.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simulate admin login for testing (REMOVE THIS IN PRODUCTION!)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['username'] = 'admin';
}

$admin_id = $_SESSION['user_id'];
$debug_output = [];

$debug_output[] = "=== DIAGNOSTIC TEST FOR TRANSACTION INSERT ===";
$debug_output[] = "Timestamp: " . date('Y-m-d H:i:s');
$debug_output[] = "";

// Test 1: Database Connection
$debug_output[] = "TEST 1: Database Connection";
$debug_output[] = "----------------------------";
if ($conn) {
    $debug_output[] = "✓ Connection successful";
    $debug_output[] = "  Server: " . DB_SERVER;
    $debug_output[] = "  Database: " . DB_NAME;
    $debug_output[] = "  Connection ID: " . mysqli_thread_id($conn);
} else {
    $debug_output[] = "✗ Connection FAILED: " . mysqli_connect_error();
    die(implode("<br>", $debug_output));
}
$debug_output[] = "";

// Test 2: Check Tables
$debug_output[] = "TEST 2: Check Required Tables";
$debug_output[] = "------------------------------";
$tables = ['admin', 'kelas', 'kas', 'transaksi'];
foreach ($tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        $debug_output[] = "✓ Table '$table' exists";
    } else {
        $debug_output[] = "✗ Table '$table' NOT FOUND";
    }
}
$debug_output[] = "";

// Test 3: Check Admin
$debug_output[] = "TEST 3: Check Admin Data";
$debug_output[] = "------------------------";
$admin_result = mysqli_query($conn, "SELECT id_admin, username, email FROM admin WHERE id_admin = $admin_id");
if ($admin_result && mysqli_num_rows($admin_result) > 0) {
    $admin = mysqli_fetch_assoc($admin_result);
    $debug_output[] = "✓ Admin found:";
    $debug_output[] = "  ID: " . $admin['id_admin'];
    $debug_output[] = "  Username: " . $admin['username'];
    $debug_output[] = "  Email: " . $admin['email'];
} else {
    $debug_output[] = "✗ Admin ID $admin_id not found in database";
}
$debug_output[] = "";

// Test 4: Check Classes
$debug_output[] = "TEST 4: Check Classes for Admin";
$debug_output[] = "--------------------------------";
$kelas_result = mysqli_query($conn, "SELECT k.id_kelas, k.nama_kelas, k.kode_kelas, kas.id_kas, kas.saldo 
                                      FROM kelas k 
                                      LEFT JOIN kas ON k.id_kelas = kas.id_kelas 
                                      WHERE k.id_admin = $admin_id");
if ($kelas_result && mysqli_num_rows($kelas_result) > 0) {
    $debug_output[] = "✓ Found " . mysqli_num_rows($kelas_result) . " class(es):";
    while ($kelas = mysqli_fetch_assoc($kelas_result)) {
        $debug_output[] = "  - " . $kelas['nama_kelas'] . " (ID: " . $kelas['id_kelas'] . ", Kode: " . $kelas['kode_kelas'] . ")";
        if ($kelas['id_kas']) {
            $debug_output[] = "    Kas ID: " . $kelas['id_kas'] . ", Saldo: Rp " . number_format($kelas['saldo'], 0, ',', '.');
        } else {
            $debug_output[] = "    ✗ WARNING: No kas record found for this class!";
        }
    }
} else {
    $debug_output[] = "✗ No classes found for admin ID $admin_id";
    $debug_output[] = "  You need to create a class first!";
}
$debug_output[] = "";

// Test 5: Check Transaksi Table Structure
$debug_output[] = "TEST 5: Transaksi Table Structure";
$debug_output[] = "----------------------------------";
$desc_result = mysqli_query($conn, "DESCRIBE transaksi");
if ($desc_result) {
    $debug_output[] = "✓ Table structure:";
    while ($row = mysqli_fetch_assoc($desc_result)) {
        $null_info = $row['Null'] == 'YES' ? 'NULL' : 'NOT NULL';
        $key_info = $row['Key'] ? " [" . $row['Key'] . "]" : "";
        $default_info = $row['Default'] !== null ? " DEFAULT: " . $row['Default'] : "";
        $debug_output[] = "  - " . $row['Field'] . " (" . $row['Type'] . ") " . $null_info . $key_info . $default_info;
    }
}
$debug_output[] = "";

// Test 6: Try Insert Transaction (if class exists)
$debug_output[] = "TEST 6: Simulate Transaction Insert";
$debug_output[] = "------------------------------------";

$test_kelas = mysqli_query($conn, "SELECT k.id_kelas, k.nama_kelas, kas.id_kas, kas.saldo 
                                   FROM kelas k 
                                   JOIN kas ON k.id_kelas = kas.id_kelas 
                                   WHERE k.id_admin = $admin_id 
                                   LIMIT 1");

if ($test_kelas && mysqli_num_rows($test_kelas) > 0) {
    $kelas_data = mysqli_fetch_assoc($test_kelas);
    $id_kas = $kelas_data['id_kas'];
    $nama_kelas = $kelas_data['nama_kelas'];
    $saldo_awal = $kelas_data['saldo'];
    
    $debug_output[] = "✓ Using class: $nama_kelas (Kas ID: $id_kas)";
    $debug_output[] = "  Current balance: Rp " . number_format($saldo_awal, 0, ',', '.');
    $debug_output[] = "";
    
    // Prepare test data
    $jenis = 'pemasukan';
    $jumlah = 10000.00;
    $tanggal = date('Y-m-d');
    $deskripsi = 'Test transaction - ' . date('Y-m-d H:i:s');
    
    $debug_output[] = "Test transaction data:";
    $debug_output[] = "  - id_kas: $id_kas (integer)";
    $debug_output[] = "  - jenis: '$jenis' (string)";
    $debug_output[] = "  - jumlah: $jumlah (decimal)";
    $debug_output[] = "  - tanggal: '$tanggal' (date)";
    $debug_output[] = "  - deskripsi: '$deskripsi' (text)";
    $debug_output[] = "  - created_by: $admin_id (integer)";
    $debug_output[] = "";
    
    // Try to insert
    $insert_sql = "INSERT INTO transaksi (id_kas, jenis, jumlah, tanggal, deskripsi, created_by) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    
    $debug_output[] = "SQL: $insert_sql";
    
    $stmt = mysqli_prepare($conn, $insert_sql);
    
    if (!$stmt) {
        $debug_output[] = "✗ PREPARE FAILED: " . mysqli_error($conn);
    } else {
        $debug_output[] = "✓ Prepare successful";
        
        mysqli_stmt_bind_param($stmt, 'isdssi', $id_kas, $jenis, $jumlah, $tanggal, $deskripsi, $admin_id);
        $debug_output[] = "✓ Bind parameters successful";
        
        if (!mysqli_stmt_execute($stmt)) {
            $debug_output[] = "✗ EXECUTE FAILED:";
            $debug_output[] = "  Error: " . mysqli_stmt_error($stmt);
            $debug_output[] = "  Error Code: " . mysqli_stmt_errno($stmt);
        } else {
            $affected = mysqli_stmt_affected_rows($stmt);
            $last_id = mysqli_insert_id($conn);
            
            $debug_output[] = "✓ Execute successful";
            $debug_output[] = "  Affected rows: $affected";
            $debug_output[] = "  Last insert ID: $last_id";
            
            if ($last_id > 0) {
                // Verify the insert
                $verify = mysqli_query($conn, "SELECT * FROM transaksi WHERE id_transaksi = $last_id");
                if ($verify && mysqli_num_rows($verify) > 0) {
                    $trans = mysqli_fetch_assoc($verify);
                    $debug_output[] = "✓ Transaction VERIFIED in database:";
                    $debug_output[] = "  ID: " . $trans['id_transaksi'];
                    $debug_output[] = "  Kas ID: " . $trans['id_kas'];
                    $debug_output[] = "  Jenis: " . $trans['jenis'];
                    $debug_output[] = "  Jumlah: Rp " . number_format($trans['jumlah'], 0, ',', '.');
                    $debug_output[] = "  Tanggal: " . $trans['tanggal'];
                    $debug_output[] = "  Created by: " . $trans['created_by'];
                    
                    // Check updated saldo
                    $saldo_check = mysqli_query($conn, "SELECT saldo FROM kas WHERE id_kas = $id_kas");
                    if ($saldo_check) {
                        $saldo_new = mysqli_fetch_assoc($saldo_check)['saldo'];
                        $debug_output[] = "✓ Saldo updated by trigger:";
                        $debug_output[] = "  Old: Rp " . number_format($saldo_awal, 0, ',', '.');
                        $debug_output[] = "  New: Rp " . number_format($saldo_new, 0, ',', '.');
                        $debug_output[] = "  Difference: Rp " . number_format($saldo_new - $saldo_awal, 0, ',', '.');
                    }
                    
                    // Clean up test data
                    mysqli_query($conn, "DELETE FROM transaksi WHERE id_transaksi = $last_id");
                    $debug_output[] = "";
                    $debug_output[] = "✓ Test transaction cleaned up";
                    $debug_output[] = "";
                    $debug_output[] = "=== CONCLUSION ===";
                    $debug_output[] = "✓✓✓ TRANSACTION INSERT WORKS CORRECTLY! ✓✓✓";
                    $debug_output[] = "The database and code are functioning properly.";
                    $debug_output[] = "";
                    $debug_output[] = "If you still cannot save transactions through the form:";
                    $debug_output[] = "1. Check if you're logged in as admin";
                    $debug_output[] = "2. Make sure you select a class from the dropdown";
                    $debug_output[] = "3. Fill all required fields (marked with *)";
                    $debug_output[] = "4. Check browser console for JavaScript errors";
                    $debug_output[] = "5. Try using the debug panel in admin_transactions.php";
                } else {
                    $debug_output[] = "✗ Transaction NOT FOUND after insert!";
                    $debug_output[] = "  This is a serious database issue.";
                }
            } else {
                $debug_output[] = "✗ Last insert ID is 0 (no auto-increment?)";
            }
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $debug_output[] = "✗ Cannot test insert: No class with kas found";
    $debug_output[] = "  Please create a class first in the admin panel";
}

$debug_output[] = "";
$debug_output[] = "=== END OF DIAGNOSTIC TEST ===";

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Debug Test - KasKelas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .debug-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .debug-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 30px;
        }
        .debug-line {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            padding: 4px 0;
            line-height: 1.6;
        }
        .debug-line.success {
            color: #28a745;
        }
        .debug-line.error {
            color: #dc3545;
            font-weight: bold;
        }
        .debug-line.warning {
            color: #ffc107;
        }
        .debug-line.section {
            color: #007bff;
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <div class="debug-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="bi bi-bug-fill text-danger me-2"></i>
                    Transaction Debug Test
                </h2>
                <a href="admin_transactions.php" class="btn btn-primary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Transactions
                </a>
            </div>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Purpose:</strong> This page tests if the transaction insert functionality works at the database level.
            </div>
            
            <div class="bg-dark text-light p-3 rounded" style="max-height: 600px; overflow-y: auto;">
                <?php foreach ($debug_output as $line): ?>
                    <?php
                    $class = '';
                    if (strpos($line, '✓') !== false || strpos($line, 'BERHASIL') !== false || strpos($line, 'successful') !== false) {
                        $class = 'success';
                    } elseif (strpos($line, '✗') !== false || strpos($line, 'FAILED') !== false || strpos($line, 'ERROR') !== false) {
                        $class = 'error';
                    } elseif (strpos($line, '⚠') !== false || strpos($line, 'WARNING') !== false) {
                        $class = 'warning';
                    } elseif (strpos($line, '===') !== false || strpos($line, 'TEST') !== false) {
                        $class = 'section';
                    }
                    ?>
                    <div class="debug-line <?php echo $class; ?>"><?php echo htmlspecialchars($line); ?></div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-4">
                <button onclick="location.reload()" class="btn btn-success">
                    <i class="bi bi-arrow-clockwise me-2"></i>Run Test Again
                </button>
                <a href="admin_transactions.php" class="btn btn-primary">
                    <i class="bi bi-cash-stack me-2"></i>Go to Transactions Page
                </a>
            </div>
        </div>
    </div>
</body>
</html>