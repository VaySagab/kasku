<!-- Professional Admin Footer - Sticky Bottom -->
<footer class="bg-dark text-white py-4 mt-auto">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <div class="me-3 p-2 rounded-3" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                        <i class="bi bi-wallet2 text-white"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">Kas<span class="text-warning">Kelas</span></h6>
                        <small class="text-white-50">Admin Dashboard</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <small class="text-white-50">
                    © <?php echo date('Y'); ?> KasKelas. Sistem Pengelolaan Kas Kelas Online.
                </small>
            </div>
        </div>
    </div>
</footer>

<!-- CSS untuk Sticky Footer - Sesuai Bootstrap -->
<style>
/* Pastikan html dan body memiliki height 100% */
html {
    height: 100%;
    scroll-behavior: smooth;
}

body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    margin: 0;
}

/* Main content akan mengambil sisa ruang yang tersedia */
main {
    flex: 1 0 auto;
}

/* Footer akan selalu di bawah */
footer.mt-auto {
    flex-shrink: 0;
}
</style>

<!-- Bootstrap JS -->
<script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>

<!-- Professional Admin JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.kas-alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.classList.contains('show')) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 150);
            }
        }, 5000);
    });

    // Enhanced table interactions
    const tableRows = document.querySelectorAll('.kas-table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.02)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // Form validation enhancements
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.classList.add('kas-loading');
                submitBtn.disabled = true;
                
                // Re-enable after 3 seconds as fallback
                setTimeout(() => {
                    submitBtn.classList.remove('kas-loading');
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    });

    // Enhanced card animations
    const cards = document.querySelectorAll('.kas-card');
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const cardObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = `all 0.6s ease ${index * 0.1}s`;
        cardObserver.observe(card);
    });

    // Smooth scrolling for internal links
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

    // Enhanced navbar scroll effect
    let lastScrollTop = 0;
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.kas-navbar');
        if (navbar) {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > 100) {
                navbar.style.boxShadow = '0 8px 40px rgba(0, 0, 0, 0.15)';
            } else {
                navbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.1)';
            }
            
            lastScrollTop = scrollTop;
        }
    });

    // Real-time form validation
    const inputs = document.querySelectorAll('.kas-form-control');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value.trim() === '' && this.hasAttribute('required')) {
                this.style.borderColor = '#dc3545';
                this.style.boxShadow = '0 0 0 4px rgba(220, 53, 69, 0.1)';
            } else {
                this.style.borderColor = '#28a745';
                this.style.boxShadow = '0 0 0 4px rgba(40, 167, 69, 0.1)';
            }
        });

        input.addEventListener('focus', function() {
            this.style.borderColor = '#667eea';
            this.style.boxShadow = '0 0 0 4px rgba(102, 126, 234, 0.1)';
        });
    });
});

// Professional notification system
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `kas-alert kas-alert-${type} position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideInRight 0.3s ease;
    `;
    notification.innerHTML = `
        <i class="bi ${type === 'success' ? 'bi-check-circle' : 'bi-exclamation-circle'} me-2"></i>
        ${message}
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>

</body>
</html>