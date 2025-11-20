<?php
include 'includes/function.php';
include 'includes/db_config.php';

// Jika user sudah login, redirect sesuai role
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] === 'admin') {
        redirect('admin.php');
    } else {
        redirect('user.php');
    }
}

// Inisialisasi variabel
$username = $email = $password = $confirm_password = "";
$role = 'user';
$username_err = $email_err = $password_err = $confirm_password_err = $role_err = "";
$register_success = "";
$register_err = "";

// Memproses data form ketika form di-submit
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validasi username
    if(empty(trim($_POST["username"]))) {
        $username_err = "Mohon masukkan username.";
    } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))) {
        $username_err = "Username hanya boleh berisi huruf, angka, dan underscore.";
    } elseif(strlen(trim($_POST["username"])) < 3) {
        $username_err = "Username minimal 3 karakter.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Validasi email
    if(empty(trim($_POST["email"]))) {
        $email_err = "Mohon masukkan email.";
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Format email tidak valid.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // Validasi password
    if(empty(trim($_POST["password"]))) {
        $password_err = "Mohon masukkan password.";     
    } elseif(strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password harus memiliki minimal 6 karakter.";
    } elseif(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', trim($_POST["password"]))) {
        $password_err = "Password harus mengandung huruf besar, huruf kecil, dan angka.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validasi konfirmasi password
    if(empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Mohon konfirmasi password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password tidak cocok.";
        }
    }
    
    // role validation
    $role = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';

    // Cek input errors sebelum insert ke database
    if(empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($role_err)) {
        // Cek apakah email atau username sudah ada di tabel admin atau user
        $exists = false;
        $checkAdmin = "SELECT id_admin FROM admin WHERE email = ? OR username = ? LIMIT 1";
        if($s = mysqli_prepare($conn, $checkAdmin)) {
            mysqli_stmt_bind_param($s, 'ss', $email, $username);
            mysqli_stmt_execute($s);
            mysqli_stmt_store_result($s);
            if(mysqli_stmt_num_rows($s) > 0) $exists = true;
            mysqli_stmt_close($s);
        }
        if(!$exists) {
            $checkUser = "SELECT id_user FROM `user` WHERE email = ? OR username = ? LIMIT 1";
            if($s2 = mysqli_prepare($conn, $checkUser)) {
                mysqli_stmt_bind_param($s2, 'ss', $email, $username);
                mysqli_stmt_execute($s2);
                mysqli_stmt_store_result($s2);
                if(mysqli_stmt_num_rows($s2) > 0) $exists = true;
                mysqli_stmt_close($s2);
            }
        }

        if($exists) {
            $email_err = 'Email atau username sudah terdaftar.';
        }

        if(empty($email_err)) {
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            if($role === 'admin') {
                $sql = "INSERT INTO admin (username, email, password) VALUES (?, ?, ?)";
                if($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "sss", $username, $email, $param_password);
                    if(mysqli_stmt_execute($stmt)) {
                        $register_success = "Registrasi bendahara berhasil! Silakan login sebagai bendahara.";
                        $username = $email = $password = $confirm_password = "";
                        $role = 'user';
                    } else {
                        $register_err = "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
                    }
                    mysqli_stmt_close($stmt);
                }
            } else {
                // insert into user
                $sql = "INSERT INTO `user` (username, email, password) VALUES (?, ?, ?)";
                if($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "sss", $username, $email, $param_password);
                    
                    if(mysqli_stmt_execute($stmt)) {
                        $register_success = "Registrasi siswa berhasil! Silakan login.";
                        $username = $email = $password = $confirm_password = "";
                    } else {
                        $register_err = "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $register_err = "Oops! Terjadi kesalahan dalam menyiapkan data. Silakan coba lagi nanti.";
                }
            }
        }
    }
    mysqli_close($conn);
}

$pageTitle = "Daftar Akun Baru - KasKelas";
include 'includes/header.php';
?>

<!-- Load existing CSS -->
<link rel="stylesheet" href="assets/css/register.css">

<!-- Professional Register Section -->
<section class="kas-hero-section bg-primary text-white position-relative overflow-hidden" style="min-height: 100vh; padding-top: 0;">
    <!-- Enhanced Animated Background -->
    <div class="kas-animated-bg"></div>
    
    <!-- Professional Floating Particles -->
    <div class="kas-particles">
        <div class="kas-particle"></div>
        <div class="kas-particle"></div>
        <div class="kas-particle"></div>
        <div class="kas-particle"></div>
        <div class="kas-particle"></div>
        <div class="kas-particle"></div>
        <div class="kas-particle"></div>
        <div class="kas-particle"></div>
    </div>
    
    <div class="container position-relative d-flex align-items-center justify-content-center" style="z-index: 10; min-height: 100vh;">
        <div class="row justify-content-center w-100">
            <!-- Professional Register Card -->
            <div class="col-lg-6 col-md-8 col-sm-10">
                
                <!-- Professional Register Card -->
                <div class="kas-register-card shadow-lg">
                    <div class="card-body p-5">
                        <!-- Professional Header -->
                        <div class="text-center mb-5">
                            <div class="mb-4">
                                <div class="d-inline-block p-3 rounded-4 shadow-lg" 
                                     style="background: linear-gradient(135deg, #4ecdc4, #44a08d);">
                                    <i class="bi bi-person-plus-fill text-white" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                            <h1 class="kas-register-brand mb-2">
                                Kas<span class="text-warning">Kelas</span>
                            </h1>
                            <p class="text-white-50 mb-0 fs-6">Bergabung dengan komunitas kami</p>
                            <p class="text-white-50 small mb-0">Mulai kelola kas kelas dengan mudah dan transparan</p>
                        </div>

                        <!-- Professional Success Alert -->
                        <?php if(!empty($register_success)): ?>
                            <div class="alert kas-alert-success alert-dismissible fade show mb-4 border-0 rounded-4" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill me-3 fs-5 text-success"></i>
                                    <div>
                                        <strong>Registrasi Berhasil!</strong><br>
                                        <small><?php echo $register_success; ?></small>
                                    </div>
                                </div>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Professional Error Alert -->
                        <?php if(!empty($register_err)): ?>
                            <div class="alert kas-alert-danger alert-dismissible fade show mb-4 border-0 rounded-4" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-exclamation-triangle-fill me-3 fs-5"></i>
                                    <div>
                                        <strong>Registrasi Gagal!</strong><br>
                                        <small><?php echo $register_err; ?></small>
                                    </div>
                                </div>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Professional Register Form -->
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="kas-register-form" id="registerForm">
                            
                            <!-- Username Input -->
                            <div class="form-floating mb-3">
                                <input type="text" 
                                       name="username" 
                                       class="form-control form-control-lg <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" 
                                       id="username" 
                                       value="<?php echo $username; ?>"
                                       required
                                       autocomplete="username"
                                       minlength="3"
                                       pattern="[a-zA-Z0-9_]+">
                                <label for="username" class="fw-medium">
                                    <i class="bi bi-person-fill me-2"></i>Username
                                </label>
                                <div class="form-text text-white-50 small mt-1">
                                    Minimal 3 karakter, hanya huruf, angka, dan underscore
                                </div>
                                <?php if(!empty($username_err)): ?>
                                    <div class="invalid-feedback">
                                        <i class="bi bi-exclamation-circle me-1"></i><?php echo $username_err; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Email Input -->
                            <div class="form-floating mb-3">
                                <input type="email" 
                                       name="email" 
                                       class="form-control form-control-lg <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" 
                                       id="email" 
                                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                                       required
                                       autocomplete="email">
                                <label for="email" class="fw-medium">
                                    <i class="bi bi-envelope-fill me-2"></i>Alamat Email
                                </label>
                                <?php if(!empty($email_err)): ?>
                                    <div class="invalid-feedback">
                                        <i class="bi bi-exclamation-circle me-1"></i><?php echo $email_err; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Password Input -->
                            <div class="form-floating mb-3 position-relative">
                                <input type="password" 
                                       name="password" 
                                       class="form-control form-control-lg <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
                                       id="password" 
                                       required
                                       autocomplete="new-password"
                                       minlength="6">
                                <label for="password" class="fw-medium">
                                    <i class="bi bi-lock-fill me-2"></i>Kata Sandi
                                </label>
                                <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-3 text-white-50" 
                                        id="togglePassword" style="z-index: 10; border: none; background: none;">
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </button>
                                <div class="form-text text-white-50 small mt-1">
                                    Minimal 6 karakter dengan huruf besar, kecil, dan angka
                                </div>
                                <!-- Password Strength Indicator -->
                                <div class="password-strength mt-2">
                                    <div class="progress" style="height: 4px;">
                                        <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="text-white-50" id="passwordStrengthText"></small>
                                </div>
                                <?php if(!empty($password_err)): ?>
                                    <div class="invalid-feedback">
                                        <i class="bi bi-exclamation-circle me-1"></i><?php echo $password_err; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Confirm Password Input -->
                            <div class="form-floating mb-3 position-relative">
                                <input type="password" 
                                       name="confirm_password" 
                                       class="form-control form-control-lg <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" 
                                       id="confirm_password" 
                                       required
                                       autocomplete="new-password">
                                <label for="confirm_password" class="fw-medium">
                                    <i class="bi bi-shield-check me-2"></i>Konfirmasi Kata Sandi
                                </label>
                                <div class="password-match mt-2" id="passwordMatch"></div>
                                <?php if(!empty($confirm_password_err)): ?>
                                    <div class="invalid-feedback">
                                        <i class="bi bi-exclamation-circle me-1"></i><?php echo $confirm_password_err; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Professional Role Selection -->
                            <div class="mb-4">
                                <label class="form-label text-white fw-semibold mb-3">
                                    <i class="bi bi-people-fill me-2"></i>Pilih Peran Anda
                                </label>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="role" id="roleUser" value="user" 
                                               <?php echo ($role === 'user') ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-light w-100 py-3 rounded-4" for="roleUser">
                                            <i class="bi bi-person-circle fs-4 d-block mb-2"></i>
                                            <strong>Siswa</strong>
                                            <small class="d-block text-white-50">Anggota kelas</small>
                                        </label>
                                    </div>
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="role" id="roleAdmin" value="admin"
                                               <?php echo ($role === 'admin') ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-warning w-100 py-3 rounded-4" for="roleAdmin">
                                            <i class="bi bi-person-gear fs-4 d-block mb-2"></i>
                                            <strong>Bendahara</strong>
                                            <small class="d-block text-warning-50">Pengelola kas</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="form-text text-white-50 small mt-2 text-center">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Pilih "Bendahara" untuk mengelola kas kelas, atau "Siswa" sebagai anggota kelas
                                </div>
                            </div>

                            <!-- Terms and Conditions -->
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                                <label class="form-check-label text-white-50 small" for="agreeTerms">
                                    Saya setuju dengan <a href="#" class="text-warning">Syarat & Ketentuan</a> 
                                    dan <a href="#" class="text-warning">Kebijakan Privasi</a>
                                </label>
                            </div>

                            <!-- Professional Register Button -->
                            <button type="submit" class="btn kas-register-btn w-100 py-3 mb-4 fw-bold fs-6" id="registerBtn">
                                <span class="btn-text">
                                    <i class="bi bi-person-plus-fill me-2"></i>Daftar Akun Sekarang
                                </span>
                                <span class="btn-loading d-none">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    Memproses...
                                </span>
                            </button>

                            <!-- Professional Divider -->
                            <div class="text-center mb-4">
                                <div class="position-relative">
                                    <hr class="text-white-50">
                                    <span class="position-absolute top-50 start-50 translate-middle bg-primary px-3 text-white-50 small">
                                        sudah punya akun?
                                    </span>
                                </div>
                            </div>

                            <!-- Login Link -->
                            <div class="text-center">
                                <a href="login.php" class="kas-link text-decoration-none fw-semibold">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Masuk ke Akun Anda
                                </a>
                            </div>
                        </form>

                        <!-- Professional Footer Info -->
                        <div class="text-center mt-5 pt-4 border-top border-white border-opacity-25">
                            <div class="row g-3 text-white-50 small">
                                <div class="col-3 text-center">
                                    <i class="bi bi-shield-lock fs-5 text-success d-block mb-1"></i>
                                    <span>Aman</span>
                                </div>
                                <div class="col-3 text-center">
                                    <i class="bi bi-check-circle fs-5 text-info d-block mb-1"></i>
                                    <span>Gratis</span>
                                </div>
                                <div class="col-3 text-center">
                                    <i class="bi bi-lightning-charge fs-5 text-warning d-block mb-1"></i>
                                    <span>Cepat</span>
                                </div>
                                <div class="col-3 text-center">
                                    <i class="bi bi-people fs-5 text-primary d-block mb-1"></i>
                                    <span>Terpercaya</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Professional Pattern Background -->
    <div class="kas-cta-pattern"></div>
</section>

<!-- Professional JavaScript Enhancements -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Professional password toggle
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        toggleIcon.classList.toggle('bi-eye');
        toggleIcon.classList.toggle('bi-eye-slash');
    });
    
    // Professional password strength checker
    const passwordStrength = document.getElementById('passwordStrength');
    const passwordStrengthText = document.getElementById('passwordStrengthText');
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        let strengthText = '';
        let strengthColor = '';
        
        if (password.length >= 6) strength += 25;
        if (password.match(/[a-z]/)) strength += 25;
        if (password.match(/[A-Z]/)) strength += 25;
        if (password.match(/[0-9]/)) strength += 25;
        
        if (strength <= 25) {
            strengthText = 'Lemah';
            strengthColor = 'bg-danger';
        } else if (strength <= 50) {
            strengthText = 'Sedang';
            strengthColor = 'bg-warning';
        } else if (strength <= 75) {
            strengthText = 'Baik';
            strengthColor = 'bg-info';
        } else {
            strengthText = 'Kuat';
            strengthColor = 'bg-success';
        }
        
        passwordStrength.style.width = strength + '%';
        passwordStrength.className = `progress-bar ${strengthColor}`;
        passwordStrengthText.textContent = strengthText;
    });
    
    // Professional password match checker
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordMatch = document.getElementById('passwordMatch');
    
    function checkPasswordMatch() {
        if (confirmPasswordInput.value && passwordInput.value) {
            if (passwordInput.value === confirmPasswordInput.value) {
                passwordMatch.innerHTML = '<small class="text-success"><i class="bi bi-check-circle me-1"></i>Password cocok</small>';
                confirmPasswordInput.classList.remove('is-invalid');
                confirmPasswordInput.classList.add('is-valid');
            } else {
                passwordMatch.innerHTML = '<small class="text-danger"><i class="bi bi-x-circle me-1"></i>Password tidak cocok</small>';
                confirmPasswordInput.classList.remove('is-valid');
                confirmPasswordInput.classList.add('is-invalid');
            }
        } else {
            passwordMatch.innerHTML = '';
            confirmPasswordInput.classList.remove('is-valid', 'is-invalid');
        }
    }
    
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    passwordInput.addEventListener('input', checkPasswordMatch);
    
    // Professional form submission with loading
    const registerForm = document.getElementById('registerForm');
    const registerBtn = document.getElementById('registerBtn');
    const btnText = registerBtn.querySelector('.btn-text');
    const btnLoading = registerBtn.querySelector('.btn-loading');
    
    registerForm.addEventListener('submit', function(e) {
        const agreeTerms = document.getElementById('agreeTerms');
        if (!agreeTerms.checked) {
            e.preventDefault();
            alert('Mohon setujui Syarat & Ketentuan untuk melanjutkan');
            return;
        }
        
        btnText.classList.add('d-none');
        btnLoading.classList.remove('d-none');
        registerBtn.disabled = true;
    });
    
    // Professional form validation
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    
    function validateUsername(username) {
        const re = /^[a-zA-Z0-9_]+$/;
        return re.test(username) && username.length >= 3;
    }
    
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    usernameInput.addEventListener('blur', function() {
        if (this.value && !validateUsername(this.value)) {
            this.classList.add('is-invalid');
        } else if (this.value) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        }
    });
    
    emailInput.addEventListener('blur', function() {
        if (this.value && !validateEmail(this.value)) {
            this.classList.add('is-invalid');
        } else if (this.value) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        }
    });
    
    // Professional auto-focus
    usernameInput.focus();
});

// Professional card entrance animation
window.addEventListener('load', function() {
    const registerCard = document.querySelector('.kas-register-card');
    registerCard.style.opacity = '0';
    registerCard.style.transform = 'translateY(30px)';
    
    setTimeout(() => {
        registerCard.style.transition = 'all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        registerCard.style.opacity = '1';
        registerCard.style.transform = 'translateY(0)';
    }, 200);
});
</script>

<?php include 'includes/footer.php'; ?>