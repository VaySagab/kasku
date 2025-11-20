<?php
// Mulai session jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include fungsi helper
require_once __DIR__ . '/function.php';

// Set page title default jika tidak didefinisikan
if (!isset($pageTitle)) {
    $pageTitle = "Kas Kelas - Sistem Pengelolaan Kas Kelas Online";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem pengelolaan kas kelas online yang modern, transparan, dan mudah digunakan">
    <meta name="keywords" content="kas kelas, pengelolaan keuangan, bendahara kelas, sistem kas online">
    <meta name="author" content="KasKelas">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="Sistem pengelolaan kas kelas online yang modern dan transparan">
    <meta property="og:type" content="website">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    
    <!-- Bootstrap 5.3 CSS - Local -->
    <link href="assets/css/bootstrap/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing-animations.css">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="assets/css/bootstrap/bootstrap.min.css" as="style">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" as="style">
</head>
<body>

<!-- Professional Loading Spinner -->
<div id="pageLoader" class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" 
     style="background: linear-gradient(135deg, #0f193d 0%, #145da0 100%); z-index: 9999;">
    <div class="text-center text-white">
        <div class="spinner-border text-warning mb-3" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h5 class="fw-semibold">KasKelas</h5>
        <p class="small opacity-75">Memuat sistem...</p>
    </div>
</div>

<!-- Enhanced Professional Navbar -->
<?php 
$currentPage = basename($_SERVER['PHP_SELF']); 
$isLandingPage = ($currentPage === 'landing.php');
?>
<nav class="navbar navbar-expand-lg navbar-dark <?php echo $isLandingPage ? 'fixed-top' : 'sticky-top'; ?> kas-navbar" id="mainNavbar">
    <div class="container">
        <!-- Professional Brand -->
        <a class="navbar-brand d-flex align-items-center fw-bold text-decoration-none" href="landing.php">
            <div class="me-3 p-2 rounded-3 shadow-sm" style="background: linear-gradient(135deg, #145da0, #1e88e5);">
                <i class="bi bi-wallet2 text-white fs-5"></i>
            </div>
            <div>
                <span class="fs-4 fw-bold">Kas<span class="text-warning">Kelas</span></span>
                <div class="small text-white-50 lh-1" style="font-size: 0.7rem; margin-top: -2px;">
                    Sistem Pengelolaan Kas
                </div>
            </div>
        </a>
        
        <!-- Professional Toggle Button -->
        <button class="navbar-toggler border-0 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Professional Navigation Menu -->
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link text-white px-4 py-2 rounded-pill fw-medium position-relative <?php echo $isLandingPage ? 'active' : ''; ?>" href="landing.php">
                        <i class="bi bi-house-door me-2"></i>Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white px-4 py-2 rounded-pill fw-medium position-relative" href="landing.php#features">
                        <i class="bi bi-star me-2"></i>Fitur Unggulan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white px-4 py-2 rounded-pill fw-medium position-relative" href="landing.php#how-it-works">
                        <i class="bi bi-gear me-2"></i>Cara Kerja
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white px-4 py-2 rounded-pill fw-medium position-relative" href="#contact">
                        <i class="bi bi-telephone me-2"></i>Kontak
                    </a>
                </li>
            </ul>
            
            <!-- Professional User Section -->
            <div class="navbar-nav">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <!-- Professional User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center px-3 py-2 rounded-pill shadow-sm" 
                           href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false"
                           style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px);">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-2 shadow-sm" 
                                 style="width: 36px; height: 36px; background: linear-gradient(135deg, #ffd700, #ff6b6b);">
                                <i class="bi bi-person-fill text-white fs-6"></i>
                            </div>
                            <div class="text-start">
                                <div class="fw-semibold lh-1"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                                <small class="text-white-50 lh-1">
                                    <?php echo $_SESSION['role'] === 'admin' ? 'Bendahara' : 'Siswa'; ?>
                                </small>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2" 
                            style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); min-width: 200px;">
                            <li class="px-3 py-2 border-bottom">
                                <div class="small text-muted">Selamat datang,</div>
                                <div class="fw-semibold text-dark"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 px-3 rounded-3 mx-2 my-1" 
                                   href="<?php echo $_SESSION['role'] === 'admin' ? 'admin.php' : 'user.php'; ?>">
                                    <i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 px-3 rounded-3 mx-2 my-1" href="#profile">
                                    <i class="bi bi-person-gear me-2 text-info"></i>Profil Saya
                                </a>
                            </li>
                            <li><hr class="dropdown-divider mx-2"></li>
                            <li>
                                <a class="dropdown-item py-2 px-3 rounded-3 mx-2 my-1 text-danger" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Keluar
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Professional Auth Buttons -->
                    <div class="d-flex gap-2 align-items-center">
                        <a class="btn btn-outline-light btn-sm rounded-pill px-4 py-2 fw-semibold shadow-sm" 
                           href="login.php" style="backdrop-filter: blur(10px);">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                        </a>
                        <a class="btn btn-warning btn-sm rounded-pill px-4 py-2 fw-semibold shadow kas-pulse-btn" 
                           href="register.php">
                            <i class="bi bi-person-plus me-2"></i>Daftar Gratis
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Professional Breadcrumb (for non-landing pages) -->
<?php if(!$isLandingPage): ?>
<div class="container mt-3" style="padding-top: 80px;">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent p-0 mb-0">
            <li class="breadcrumb-item">
                <a href="landing.php" class="text-decoration-none text-primary fw-medium">
                    <i class="bi bi-house-door me-1"></i>Beranda
                </a>
            </li>
            <li class="breadcrumb-item active text-dark fw-medium" aria-current="page">
                <?php 
                $currentPageName = basename($_SERVER['PHP_SELF'], '.php');
                echo $currentPageName === 'login' ? 'Masuk' : ($currentPageName === 'register' ? 'Daftar' : ($currentPageName === 'user' ? 'Dashboard Siswa' : ucfirst($currentPageName)));
                ?>
            </li>
        </ol>
    </nav>
</div>
<?php endif; ?>

<main style="padding-top: <?php echo $isLandingPage ? '0' : '20px'; ?>;">

<!-- Professional JavaScript for Enhanced UX -->
<script>
// Professional page loader
window.addEventListener('load', function() {
    const loader = document.getElementById('pageLoader');
    if (loader) {
        loader.style.opacity = '0';
        loader.style.transition = 'opacity 0.5s ease';
        setTimeout(() => {
            loader.style.display = 'none';
        }, 500);
    }
});

// Professional navbar scroll effect
let lastScrollTop = 0;
window.addEventListener('scroll', function() {
    const navbar = document.getElementById('mainNavbar');
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    
    if (scrollTop > 100) {
        navbar.classList.add('scrolled');
        navbar.style.background = 'rgba(15, 25, 61, 0.95)';
        navbar.style.backdropFilter = 'blur(20px)';
        navbar.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.3)';
    } else {
        navbar.classList.remove('scrolled');
        navbar.style.background = 'rgba(15, 25, 61, 0.8)';
        navbar.style.backdropFilter = 'blur(10px)';
        navbar.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.2)';
    }
    
    // Professional hide/show navbar on scroll (only for landing page)
    <?php if($isLandingPage): ?>
    if (scrollTop > lastScrollTop && scrollTop > 200) {
        navbar.style.transform = 'translateY(-100%)';
    } else {
        navbar.style.transform = 'translateY(0)';
    }
    <?php endif; ?>
    lastScrollTop = scrollTop;
});

// Professional active link highlighting
document.addEventListener('DOMContentLoaded', function() {
    const currentLocation = location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && (href === currentLocation.split('/').pop() || 
            (href.includes('#') && currentLocation.includes('landing.php')))) {
            link.classList.add('active');
            link.style.background = 'rgba(255, 255, 255, 0.2)';
            link.style.boxShadow = '0 4px 15px rgba(255, 255, 255, 0.1)';
        }
        
        // Professional hover effects
        link.addEventListener('mouseenter', function() {
            if (!this.classList.contains('active')) {
                this.style.background = 'rgba(255, 255, 255, 0.1)';
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 5px 15px rgba(255, 255, 255, 0.1)';
            }
        });
        
        link.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.background = 'transparent';
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            }
        });
    });
});

// Professional smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>

<style>
/* Professional navbar enhancements */
.kas-navbar {
    transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    background: rgba(15, 25, 61, 0.8) !important;
    backdrop-filter: blur(10px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.navbar-brand:hover {
    transform: translateY(-2px);
    transition: transform 0.3s ease;
}

.nav-link {
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    margin: 0 0.25rem;
}

.nav-link.active {
    background: rgba(255, 255, 255, 0.2) !important;
    box-shadow: 0 4px 15px rgba(255, 255, 255, 0.1) !important;
}

.dropdown-menu {
    border: 1px solid rgba(255, 255, 255, 0.1);
    animation: dropdownFadeIn 0.3s ease;
}

@keyframes dropdownFadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: rgba(0, 0, 0, 0.5);
    font-weight: bold;
}

/* Professional loading spinner */
#pageLoader {
    animation: fadeOut 0.5s ease 2s forwards;
}

@keyframes fadeOut {
    to {
        opacity: 0;
        visibility: hidden;
    }
}

/* Professional responsive adjustments */
@media (max-width: 991px) {
    .navbar-collapse {
        background: rgba(15, 25, 61, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 15px;
        margin-top: 1rem;
        padding: 1.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    
    .nav-link {
        text-align: center;
        margin: 0.25rem 0;
        padding: 0.75rem 1rem !important;
    }
}
</style>