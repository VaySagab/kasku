<?php
include 'includes/function.php';
include 'includes/db_config.php';

if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] === 'admin') {
        redirect('admin.php');
    } else {
        redirect('user.php');
    }
}

$email = $password = "";
$email_err = $password_err = $login_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(empty(trim($_POST["email"]))) {
        $email_err = "Mohon masukkan email.";
    } else {
        $email = trim($_POST["email"]);
    }

    if(empty(trim($_POST["password"]))) {
        $password_err = "Mohon masukkan password.";
    } else {
        $password = trim($_POST["password"]);
    }

    if(empty($email_err) && empty($password_err)) {
        $sql_admin = "SELECT id_admin, username, email, password FROM admin WHERE email = ? LIMIT 1";
        if($stmt = mysqli_prepare($conn, $sql_admin)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;
            if(mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id_admin, $admin_username, $admin_email, $admin_hashed);
                    mysqli_stmt_fetch($stmt);
                    if(password_verify($password, $admin_hashed)) {
                        $_SESSION["user_id"] = $id_admin;
                        $_SESSION["username"] = $admin_username;
                        $_SESSION["role"] = 'admin';
                        redirect('admin.php');
                    } else {
                        $login_err = "Email atau password salah.";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    mysqli_stmt_close($stmt);
                    $sql_user = "SELECT id_user, username, email, password FROM `user` WHERE email = ? LIMIT 1";
                    if($stmt2 = mysqli_prepare($conn, $sql_user)) {
                        mysqli_stmt_bind_param($stmt2, "s", $param_email2);
                        $param_email2 = $email;
                        if(mysqli_stmt_execute($stmt2)) {
                            mysqli_stmt_store_result($stmt2);
                            if(mysqli_stmt_num_rows($stmt2) == 1) {
                                mysqli_stmt_bind_result($stmt2, $id_user, $user_username, $user_email, $user_hashed);
                                mysqli_stmt_fetch($stmt2);
                                if(password_verify($password, $user_hashed)) {
                                    $_SESSION["user_id"] = $id_user;
                                    $_SESSION["username"] = $user_username;
                                    $_SESSION["role"] = 'user';
                                    redirect('user.php');
                                } else {
                                    $login_err = "Email atau password salah.";
                                }
                            } else {
                                $login_err = "Email tidak ditemukan.";
                            }
                        } else {
                            $login_err = "Terjadi kesalahan, coba lagi nanti.";
                        }
                        mysqli_stmt_close($stmt2);
                    }
                }
            } else {
                $login_err = "Terjadi kesalahan, coba lagi nanti.";
            }
        }
    }
    mysqli_close($conn);
}

$pageTitle = "Masuk ke Akun - KasKelas";
include 'includes/header.php';
?>

<!-- Load existing CSS -->
<link rel="stylesheet" href="assets/css/login.css">

<!-- Professional Login Section -->
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
            <!-- Professional Login Card -->
            <div class="col-lg-5 col-md-7 col-sm-9">
                
                <!-- Professional Login Card -->
                <div class="kas-login-card shadow-lg">
                    <div class="card-body p-5">
                        <!-- Professional Header -->
                        <div class="text-center mb-5">
                            <div class="mb-4">
                                <div class="d-inline-block p-3 rounded-4 shadow-lg" 
                                     style="background: linear-gradient(135deg, #145da0, #1e88e5);">
                                    <i class="bi bi-wallet2 text-white" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                            <h1 class="kas-login-brand mb-2">
                                Kas<span class="text-warning">Kelas</span>
                            </h1>
                            <p class="text-white-50 mb-0 fs-6">Masuk ke dashboard Anda</p>
                            <p class="text-white-50 small mb-0">Kelola kas kelas dengan mudah dan transparan</p>
                        </div>

                        <!-- Professional Error Alert -->
                        <?php if(!empty($login_err)): ?>
                            <div class="alert kas-alert alert-dismissible fade show mb-4 border-0 rounded-4" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-exclamation-triangle-fill me-3 fs-5"></i>
                                    <div>
                                        <strong>Login Gagal!</strong><br>
                                        <small><?php echo $login_err; ?></small>
                                    </div>
                                </div>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Professional Login Form -->
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="kas-login-form" id="loginForm">
                            <!-- Email Input -->
                            <div class="form-floating mb-4">
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
                            <div class="form-floating mb-4 position-relative">
                                <input type="password" 
                                       name="password" 
                                       class="form-control form-control-lg <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
                                       id="password" 
                                       required
                                       autocomplete="current-password">
                                <label for="password" class="fw-medium">
                                    <i class="bi bi-lock-fill me-2"></i>Kata Sandi
                                </label>
                                <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-3 text-white-50" 
                                        id="togglePassword" style="z-index: 10; border: none; background: none;">
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </button>
                                <?php if(!empty($password_err)): ?>
                                    <div class="invalid-feedback">
                                        <i class="bi bi-exclamation-circle me-1"></i><?php echo $password_err; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Remember Me & Forgot Password -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="rememberMe">
                                    <label class="form-check-label text-white-50 small" for="rememberMe">
                                        Ingat saya
                                    </label>
                                </div>
                                <a href="#" class="text-warning text-decoration-none small fw-medium">
                                    Lupa kata sandi?
                                </a>
                            </div>

                            <!-- Professional Login Button -->
                            <button type="submit" class="btn kas-login-btn w-100 py-3 mb-4 fw-bold fs-6" id="loginBtn">
                                <span class="btn-text">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Masuk ke Dashboard
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
                                        atau
                                    </span>
                                </div>
                            </div>

                            <!-- Register Link -->
                            <div class="text-center">
                                <p class="text-white-50 mb-2">Belum memiliki akun?</p>
                                <a href="register.php" class="kas-link text-decoration-none fw-semibold">
                                    <i class="bi bi-person-plus-fill me-2"></i>Daftar Akun Baru
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
    
    // Professional form submission with loading
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const btnText = loginBtn.querySelector('.btn-text');
    const btnLoading = loginBtn.querySelector('.btn-loading');
    
    loginForm.addEventListener('submit', function() {
        btnText.classList.add('d-none');
        btnLoading.classList.remove('d-none');
        loginBtn.disabled = true;
    });
    
    // Professional form validation
    const emailInput = document.getElementById('email');
    const passwordInputField = document.getElementById('password');
    
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    emailInput.addEventListener('blur', function() {
        if (this.value && !validateEmail(this.value)) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });
    
    passwordInputField.addEventListener('input', function() {
        if (this.value.length > 0 && this.value.length < 6) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });
    
    // Professional auto-focus
    emailInput.focus();
    
    // Professional keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'Enter') {
            loginForm.submit();
        }
    });
});

// Professional card entrance animation
window.addEventListener('load', function() {
    const loginCard = document.querySelector('.kas-login-card');
    loginCard.style.opacity = '0';
    loginCard.style.transform = 'translateY(30px)';
    
    setTimeout(() => {
        loginCard.style.transition = 'all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        loginCard.style.opacity = '1';
        loginCard.style.transform = 'translateY(0)';
    }, 200);
});
</script>

<?php include 'includes/footer.php'; ?>