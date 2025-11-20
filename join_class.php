<?php
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/function.php';

// Protect route: allow only logged-in users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_kelas = strtoupper(trim($_POST['kode_kelas']));
    
    // Validate class code format
    if (!validate_class_code($kode_kelas)) {
        $_SESSION['error'] = 'Format kode kelas tidak valid. Kode harus 6 karakter huruf dan angka.';
        header('Location: user.php');
        exit;
    }
    
    // Check if class exists and is active
    $sql = "SELECT id_kelas, nama_kelas, status FROM kelas WHERE kode_kelas = ? LIMIT 1";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, 's', $kode_kelas);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $id_kelas = $row['id_kelas'];
            $nama_kelas = $row['nama_kelas'];
            $status = $row['status'];
            
            if ($status !== 'aktif') {
                $_SESSION['error'] = 'Kelas ini sudah tidak aktif.';
                header('Location: user.php');
                exit;
            }
            
            // Check if user is already a member
            $sql_check = "SELECT id_anggota, status FROM anggota WHERE id_kelas = ? AND id_user = ? LIMIT 1";
            if ($stmt_check = mysqli_prepare($conn, $sql_check)) {
                mysqli_stmt_bind_param($stmt_check, 'ii', $id_kelas, $user_id);
                mysqli_stmt_execute($stmt_check);
                $result_check = mysqli_stmt_get_result($stmt_check);
                
                if ($row_check = mysqli_fetch_assoc($result_check)) {
                    if ($row_check['status'] === 'aktif') {
                        $_SESSION['error'] = 'Anda sudah menjadi anggota kelas ini.';
                    } else {
                        // Reactivate membership
                        $sql_update = "UPDATE anggota SET status = 'aktif', tanggal_bergabung = CURDATE() WHERE id_anggota = ?";
                        if ($stmt_update = mysqli_prepare($conn, $sql_update)) {
                            mysqli_stmt_bind_param($stmt_update, 'i', $row_check['id_anggota']);
                            if (mysqli_stmt_execute($stmt_update)) {
                                $_SESSION['success'] = 'Berhasil bergabung kembali ke kelas ' . $nama_kelas . '!';
                            }
                            mysqli_stmt_close($stmt_update);
                        }
                    }
                } else {
                    // Add new member
                    $sql_insert = "INSERT INTO anggota (id_kelas, id_user, tanggal_bergabung, status) VALUES (?, ?, CURDATE(), 'aktif')";
                    if ($stmt_insert = mysqli_prepare($conn, $sql_insert)) {
                        mysqli_stmt_bind_param($stmt_insert, 'ii', $id_kelas, $user_id);
                        if (mysqli_stmt_execute($stmt_insert)) {
                            $_SESSION['success'] = 'Selamat! Anda berhasil bergabung ke kelas ' . $nama_kelas . '!';
                        } else {
                            $_SESSION['error'] = 'Terjadi kesalahan saat bergabung ke kelas.';
                        }
                        mysqli_stmt_close($stmt_insert);
                    }
                }
                mysqli_stmt_close($stmt_check);
            }
        } else {
            $_SESSION['error'] = 'Kode kelas tidak ditemukan. Pastikan kode yang Anda masukkan benar.';
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $_SESSION['error'] = 'Metode request tidak valid.';
}

mysqli_close($conn);
header('Location: user.php');
exit;
?>