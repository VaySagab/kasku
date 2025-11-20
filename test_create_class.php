<?php
// Test script untuk create class dengan error handling lengkap
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/function.php';

echo "<h2>Test Create Class Form</h2>";
echo "<hr>";

// Simulate admin login if not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    // Get first admin from database
    $result = mysqli_query($conn, "SELECT id_admin, username FROM admin LIMIT 1");
    if ($row = mysqli_fetch_assoc($result)) {
        $_SESSION['user_id'] = $row['id_admin'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = 'admin';
        echo "✅ Auto-logged in as admin: {$row['username']}<br>";
    } else {
        echo "❌ No admin found in database!<br>";
        exit;
    }
}

$admin_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_class'])) {
    echo "<h3>Processing Form Submission...</h3>";
    
    $nama_kelas = trim($_POST['nama_kelas']);
    $kode_kelas = strtoupper(generate_random_string(6));
    
    echo "Nama Kelas: $nama_kelas<br>";
    echo "Kode Kelas: $kode_kelas<br>";
    echo "Admin ID: $admin_id<br><br>";
    
    if (empty($nama_kelas)) {
        $message = 'Nama kelas tidak boleh kosong!';
        $message_type = 'danger';
        echo "❌ $message<br>";
    } else {
        echo "Starting transaction...<br>";
        mysqli_begin_transaction($conn);
        
        try {
            // Insert class
            echo "Preparing INSERT statement for kelas...<br>";
            $sql = "INSERT INTO kelas (nama_kelas, kode_kelas, id_admin, status) VALUES (?, ?, ?, 'aktif')";
            $stmt = mysqli_prepare($conn, $sql);
            
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
            }
            
            echo "Binding parameters...<br>";
            mysqli_stmt_bind_param($stmt, 'ssi', $nama_kelas, $kode_kelas, $admin_id);
            
            echo "Executing INSERT...<br>";
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to execute: " . mysqli_stmt_error($stmt));
            }
            
            $kelas_id = mysqli_insert_id($conn);
            echo "✅ Kelas inserted successfully! ID: $kelas_id<br>";
            mysqli_stmt_close($stmt);
            
            // Create kas for the class
            echo "Preparing INSERT statement for kas...<br>";
            $sql = "INSERT INTO kas (id_kelas, saldo) VALUES (?, 0)";
            $stmt = mysqli_prepare($conn, $sql);
            
            if (!$stmt) {
                throw new Exception("Failed to prepare kas statement: " . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($stmt, 'i', $kelas_id);
            
            echo "Executing kas INSERT...<br>";
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to execute kas insert: " . mysqli_stmt_error($stmt));
            }
            
            echo "✅ Kas inserted successfully!<br>";
            mysqli_stmt_close($stmt);
            
            echo "Committing transaction...<br>";
            mysqli_commit($conn);
            
            $message = "Kelas berhasil dibuat dengan kode: <strong>$kode_kelas</strong>";
            $message_type = 'success';
            echo "<br>✅ <strong>SUCCESS!</strong> $message<br>";
            
        } catch (Exception $e) {
            echo "Rolling back transaction...<br>";
            mysqli_rollback($conn);
            $message = 'Gagal membuat kelas: ' . $e->getMessage();
            $message_type = 'danger';
            echo "<br>❌ <strong>ERROR!</strong> $message<br>";
        }
    }
    
    echo "<hr>";
}

// Display form
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Create Class</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"] { width: 300px; padding: 8px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
        .alert { padding: 15px; margin: 15px 0; border-radius: 5px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #007bff; color: white; }
    </style>
</head>
<body>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<h3>Create New Class</h3>
<form method="POST">
    <div class="form-group">
        <label for="nama_kelas">Nama Kelas:</label>
        <input type="text" id="nama_kelas" name="nama_kelas" required placeholder="Contoh: XII IPA 1">
    </div>
    <button type="submit" name="create_class">Create Class</button>
</form>

<hr>

<h3>Existing Classes</h3>
<?php
$result = mysqli_query($conn, "SELECT k.*, kas.saldo FROM kelas k LEFT JOIN kas ON k.id_kelas = kas.id_kelas WHERE k.id_admin = $admin_id ORDER BY k.created_at DESC");

if (mysqli_num_rows($result) > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Nama Kelas</th><th>Kode Kelas</th><th>Saldo</th><th>Status</th><th>Created At</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['id_kelas']}</td>";
        echo "<td>{$row['nama_kelas']}</td>";
        echo "<td><strong>{$row['kode_kelas']}</strong></td>";
        echo "<td>Rp " . number_format($row['saldo'], 0, ',', '.') . "</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No classes found.</p>";
}
?>

<hr>
<p>
    <a href="debug_create_class.php">Run Debug Script</a> | 
    <a href="admin_classes.php">Go to Admin Classes Page</a>
</p>

</body>
</html>

<?php
mysqli_close($conn);
?>