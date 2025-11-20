<?php
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/function.php';

// Simple seeder to create an admin account via a small form.
// Usage: open in browser, fill username/email/password and submit.
// The script will hash the password and insert into `admin` table using prepared statements.

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        $message = 'Semua field harus diisi.';
    } else {
        // Check duplicate email
        $sql = "SELECT id_admin FROM admin WHERE email = ? LIMIT 1";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $message = 'Email sudah terdaftar sebagai admin.';
            } else {
                mysqli_stmt_close($stmt);
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $insert = "INSERT INTO admin (username, email, password) VALUES (?, ?, ?)";
                if ($ins = mysqli_prepare($conn, $insert)) {
                    mysqli_stmt_bind_param($ins, 'sss', $username, $email, $hash);
                    if (mysqli_stmt_execute($ins)) {
                        $message = 'Admin berhasil dibuat. Anda bisa login melalui login.php';
                    } else {
                        $message = 'Gagal memasukkan admin: ' . mysqli_error($conn);
                    }
                    mysqli_stmt_close($ins);
                } else {
                    $message = 'Kesalahan persiapan query: ' . mysqli_error($conn);
                }
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = 'Kesalahan query cek duplicate: ' . mysqli_error($conn);
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seed Admin - Kasku</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; padding: 30px; }
        .box { max-width:480px; margin:0 auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,0.08);} 
        label{display:block;margin:8px 0 4px}
        input[type=text], input[type=email], input[type=password]{width:100%;padding:10px;border:1px solid #ddd;border-radius:4px}
        button{margin-top:12px;padding:10px 14px;border:0;background:#2b6cb0;color:#fff;border-radius:6px}
        .msg{margin-bottom:10px;color:#333}
    </style>
</head>
<body>
<div class="box">
    <h2>Buat Admin (Seeder)</h2>
    <?php if ($message): ?>
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <label>Username</label>
        <input type="text" name="username" required>
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Buat Admin</button>
    </form>

    <p style="margin-top:12px;font-size:0.9em;color:#666">Setelah dibuat, login lewat <a href="login.php">login.php</a>.</p>
</div>
</body>
</html>
