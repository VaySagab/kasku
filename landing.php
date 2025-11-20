<?php 
include 'includes/header.php'; 
?>

<!-- Load additional CSS files -->
<link rel="stylesheet" href="assets/css/bootstrap/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/landing-animations.css">
<link rel="stylesheet" href="assets/css/style.css">

<!-- Hero Section with Bootstrap -->
<section class="hero-section bg-primary text-white position-relative overflow-hidden kas-hero-section">
    <!-- Animated Background -->
    <div class="kas-animated-bg"></div>
    
    <!-- Floating Particles -->
    <div class="kas-particles">
        <div class="kas-particle"></div>
        <div class="kas-particle"></div>
        <div class="kas-particle"></div>
        <div class="kas-particle"></div>
        <div class="kas-particle"></div>
    </div>
    
    <div class="container position-relative" style="z-index: 10;">
        <div class="row align-items-center min-vh-100 py-5">
            <!-- Left Content -->
            <div class="col-lg-6 mb-5 mb-lg-0" data-aos="fade-right">
                <!-- Badge -->
                <span class="kas-badge-pill mb-4">
                    <i class="bi bi-star-fill text-warning me-2"></i>
                    Platform Terpercaya #1 untuk Kas Kelas
                </span>
                
                <!-- Main Heading -->
                <h1 class="display-3 fw-bold mb-4 lh-1 kas-text-shadow">
                    Kelola Kas Kelas Lebih 
                    <span class="d-block kas-gradient-text">Praktis & Transparan</span>
                </h1>
                
                <!-- Description -->
                <p class="lead mb-5 fs-5 text-white kas-text-shadow-small">
                    Sistem pengelolaan kas kelas online berbasis web. Tinggalkan buku catatan, beralih ke era digital dengan akurasi data real-time.
                </p>
                
                <!-- CTA Buttons -->
                <div class="d-flex flex-wrap gap-3 mb-5">
                    <a href="register.php" class="btn btn-warning btn-lg px-5 py-3 rounded-pill shadow-lg fw-semibold kas-main-btn-animated kas-pulse-btn">
                        <i class="bi bi-rocket-takeoff me-2"></i>
                        <span>Mulai Gratis Sekarang</span>
                    </a>
                    <a href="login.php" class="btn btn-outline-light btn-lg px-5 py-3 rounded-pill fw-semibold kas-sec-btn-outline">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Login
                    </a>
                </div>
                
              
            </div>
            
            <!-- Right Image -->
            <div class="col-lg-6 text-center" data-aos="fade-left">
                <div class="kas-hero-image-wrapper">
                    <!-- Floating Cards -->
                    <div class="kas-floating-card kas-float-1">
                        <i class="bi bi-graph-up-arrow text-success fs-1"></i>
                        <p class="small mb-0 mt-2 fw-semibold" style="color: #101010;">Real-time</p>
                    </div>
                    <div class="kas-floating-card kas-float-2">
                        <i class="bi bi-shield-check text-primary fs-1"></i>
                        <p class="small mb-0 mt-2 fw-semibold" style="color: #101010;">Aman</p>
                    </div>
                    <div class="kas-floating-card kas-float-3">
                        <i class="bi bi-people-fill text-warning fs-1"></i>
                        <p class="small mb-0 mt-2 fw-semibold" style="color: #101010;">Multi-User</p>
                    </div>

                </div>
            </div>
        </div>
    </div>
    
    <!-- Scroll Indicator -->
    <div class="kas-scroll-indicator">
        <a href="#features" class="text-white text-decoration-none">
            <div class="text-center">
                <div class="kas-mouse">
                    <div class="kas-wheel"></div>
                </div>
                <p class="small mt-2">Scroll Down</p>
            </div>
        </a>
    </div>
    
    <!-- CTA Pattern Background -->
    <div class="kas-cta-pattern"></div>
</section>

<!-- Features Section -->
<section id="features" class="py-5 bg-light">
    <div class="container py-5">
        <!-- Section Header -->
        <div class="text-center mb-5 kas-fade-in-up">
            <span class="kas-section-badge mb-3">
                FITUR UNGGULAN
            </span>
            <h2 class="display-5 fw-bold mb-3">Mengapa Memilih Kami?</h2>
            <p class="lead text-muted col-lg-8 mx-auto">
                Solusi lengkap untuk pengelolaan keuangan kelas yang efisien dan transparan
            </p>
        </div>
        
        <!-- Feature Cards -->
        <div class="row g-4">
            <div class="col-lg-4 col-md-6 kas-fade-in-up">
                <div class="kas-feature-card">
                    <div class="kas-feature-icon-wrapper text-center mb-4">
                        <div class="kas-feature-icon bg-gradient-primary">
                            <i class="bi bi-file-earmark-bar-graph fs-1"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-3 text-center">Laporan Real-Time</h4>
                    <p class="text-muted mb-0 text-center">
                        Setiap transaksi tercatat instan. Pantau saldo, pemasukan, dan pengeluaran kapan saja tanpa harus bertanya.
                    </p>
                    <div class="kas-feature-hover-effect"></div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 kas-fade-in-up">
                <div class="kas-feature-card">
                    <div class="kas-feature-icon-wrapper text-center mb-4">
                        <div class="kas-feature-icon bg-gradient-success">
                            <i class="bi bi-people fs-1"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-3 text-center">Akses Multi-Peran</h4>
                    <p class="text-muted mb-0 text-center">
                        Didesain khusus untuk Admin (Bendahara) dan User (Siswa). Pembagian hak akses yang jelas dan aman.
                    </p>
                    <div class="kas-feature-hover-effect"></div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 kas-fade-in-up">
                <div class="kas-feature-card">
                    <div class="kas-feature-icon-wrapper text-center mb-4">
                        <div class="kas-feature-icon bg-gradient-danger">
                            <i class="bi bi-shield-lock fs-1"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-3 text-center">Keamanan Data</h4>
                    <p class="text-muted mb-0 text-center">
                        Menggunakan sistem database MySQL yang terstruktur dan aman. Data transaksi terjaga kerahasiaannya.
                    </p>
                    <div class="kas-feature-hover-effect"></div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 kas-fade-in-up">
                <div class="kas-feature-card">
                    <div class="kas-feature-icon-wrapper text-center mb-4">
                        <div class="kas-feature-icon bg-gradient-primary">
                            <i class="bi bi-lightning-charge fs-1"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-3 text-center">Cepat & Responsif</h4>
                    <p class="text-muted mb-0 text-center">
                        Interface yang cepat dan responsif. Akses dari desktop, tablet, atau smartphone dengan mudah.
                    </p>
                    <div class="kas-feature-hover-effect"></div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 kas-fade-in-up">
                <div class="kas-feature-card">
                    <div class="kas-feature-icon-wrapper text-center mb-4">
                        <div class="kas-feature-icon bg-gradient-success">
                            <i class="bi bi-graph-up fs-1"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-3 text-center">Analisis Lengkap</h4>
                    <p class="text-muted mb-0 text-center">
                        Dapatkan insight mendalam dengan grafik dan laporan keuangan yang komprehensif dan mudah dipahami.
                    </p>
                    <div class="kas-feature-hover-effect"></div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 kas-fade-in-up">
                <div class="kas-feature-card">
                    <div class="kas-feature-icon-wrapper text-center mb-4">
                        <div class="kas-feature-icon bg-gradient-danger">
                            <i class="bi bi-cloud-check fs-1"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-3 text-center">Cloud Based</h4>
                    <p class="text-muted mb-0 text-center">
                        Data tersimpan di secara private, kapanpun dan dimanapun data anda akan selalu aman.
                    </p>
                    <div class="kas-feature-hover-effect"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section id="how-it-works" class="py-5 bg-white">
    <div class="container py-5">
        <div class="text-center mb-5 kas-fade-in-up">
            <span class="kas-section-badge mb-3">
                CARA KERJA
            </span>
            <h2 class="display-5 fw-bold mb-3">Mudah Digunakan dalam 3 Langkah</h2>
        </div>
        
        <div class="row g-5 align-items-center">
            <div class="col-lg-4 kas-fade-in-left">
                <div class="kas-step-card">
                    <div class="kas-step-number">1</div>
                    <div class="kas-step-icon">
                        <i class="bi bi-person-plus text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Daftar Akun</h4>
                    <p class="text-muted">
                        Buat akun sebagai Admin (Bendahara) atau User (Siswa) dengan mudah dan cepat. Gratis tanpa biaya apapun.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 kas-fade-in-up">
                <div class="kas-step-card">
                    <div class="kas-step-number">2</div>
                    <div class="kas-step-icon">
                        <i class="bi bi-people text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Buat/Gabung Kelas</h4>
                    <p class="text-muted">
                        Admin membuat kelas dan mendapat kode unik. User bergabung dengan memasukkan kode kelas tersebut.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 kas-fade-in-right">
                <div class="kas-step-card">
                    <div class="kas-step-number">3</div>
                    <div class="kas-step-icon">
                        <i class="bi bi-cash-stack text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Kelola Transaksi</h4>
                    <p class="text-muted">
                        Catat setiap transaksi, pantau laporan keuangan real-time, dan kelola kas kelas dengan mudah.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 bg-primary text-white position-relative overflow-hidden">
    <div class="kas-animated-bg"></div>
    <div class="kas-cta-pattern"></div>
    
    <div class="container py-5 position-relative" style="z-index: 10;">
        <div class="row align-items-center">
            <div class="col-lg-8 text-center text-lg-start mb-4 mb-lg-0 kas-fade-in-left">
                <h2 class="display-5 fw-bold mb-3 kas-text-shadow">Siap Mencoba Pengelolaan Kas Tanpa Ribet?</h2>
                <p class="lead mb-0 kas-text-shadow-small">Uji coba gratis, tanpa instalasi, langsung dari browser Anda.</p>
            </div>
            <div class="col-lg-4 text-center text-lg-end kas-fade-in-right">
                <a href="register.php" class="btn btn-warning btn-lg px-5 py-3 rounded-pill shadow-lg fw-semibold kas-pulse-btn">
                    <i class="bi bi-rocket-takeoff me-2"></i>
                    Daftar Sekarang
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Stats/Badges Section -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-2 kas-fade-in">
                <div class="kas-badge-card">
                    <div class="kas-badge-icon mb-2">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <p class="small fw-semibold mb-0">Data Terenkripsi</p>
                </div>
            </div>
            <div class="col-6 col-md-2 kas-fade-in">
                <div class="kas-badge-card">
                    <div class="kas-badge-icon mb-2">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <p class="small fw-semibold mb-0">Transaksi Digital</p>
                </div>
            </div>
            <div class="col-6 col-md-2 kas-fade-in">
                <div class="kas-badge-card">
                    <div class="kas-badge-icon mb-2">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <p class="small fw-semibold mb-0">Laporan Real-Time</p>
                </div>
            </div>
            <div class="col-6 col-md-2 kas-fade-in">
                <div class="kas-badge-card">
                    <div class="kas-badge-icon mb-2">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <p class="small fw-semibold mb-0">Multi-User Access</p>
                </div>
            </div>
            <div class="col-6 col-md-2 kas-fade-in">
                <div class="kas-badge-card">
                    <div class="kas-badge-value mb-2">8+</div>
                    <p class="small fw-semibold mb-0">Tabel Database</p>
                </div>
            </div>
            <div class="col-6 col-md-2 kas-fade-in">
                <div class="kas-badge-card">
                    <div class="kas-badge-value mb-2">B5.3</div>
                    <p class="small fw-semibold mb-0">Bootstrap 5</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<!-- Load JavaScript files -->
<script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/js/animated-bg.js"></script>

<script>
// Counter Animation
function animateCounter(element) {
    const target = parseInt(element.getAttribute('data-target'));
    const duration = 2000;
    const increment = target / (duration / 16);
    let current = 0;
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = target.toLocaleString() + '+';
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current).toLocaleString() + '+';
        }
    }, 16);
}

// Trigger counter animation when visible
const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
            entry.target.classList.add('counted');
            animateCounter(entry.target);
        }
    });
}, { threshold: 0.5 });

document.querySelectorAll('.kas-counter').forEach(counter => {
    counterObserver.observe(counter);
});

// Fade in animations
const fadeObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('kas-visible');
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.kas-fade-in-up, .kas-fade-in-left, .kas-fade-in-right, .kas-fade-in').forEach(el => {
    fadeObserver.observe(el);
});

// Smooth scroll for anchor links
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