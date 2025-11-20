<?php
// Mulai session jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include fungsi helper
require_once __DIR__ . '/function.php';

// Set page title default jika tidak didefinisikan
if (!isset($pageTitle)) {
    $pageTitle = "Admin Dashboard - KasKelas";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard Admin - Sistem Pengelolaan Kas Kelas Online">
    <meta name="keywords" content="admin, bendahara, kas kelas, dashboard">
    <meta name="author" content="KasKelas">
    <meta name="robots" content="noindex, nofollow">
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
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/kas-admin.css">
    
    <!-- Chart.js for Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
    <style>
        :root {
            --primary-color: #145da0;
            --secondary-color: #0f193d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f6f9;
            color: #333;
        }
        
        /* Professional Navbar Styling */
        .kas-navbar {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            transition: transform 0.3s ease;
        }
        
        .navbar-brand:hover {
            transform: translateY(-2px);
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin: 0 0.25rem;
        }
        
        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: #fff !important;
            transform: translateY(-2px);
        }
        
        /* Professional Card Styling */
        .kas-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border: none;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .kas-card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            transform: translateY(-5px);
        }
        
        .kas-card .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #fff;
            border: none;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
        }
        
        .kas-card .card-body {
            padding: 1.5rem;
        }
        
        /* Professional Statistics Cards */
        .kas-stats {
            background: #fff;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .kas-stats:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            transform: translateY(-5px);
        }
        
        .kas-stats-title {
            font-size: 0.875rem;
            color: #6c757d;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        
        .kas-stats-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0.5rem 0;
        }
        
        .kas-stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: #fff;
        }
        
        .kas-stats-icon.balance {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .kas-stats-icon.income {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        
        .kas-stats-icon.expense {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
        }
        
        /* Professional Table Styling */
        .kas-table {
            margin-bottom: 0;
        }
        
        .kas-table thead th {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.5px;
            border: none;
            padding: 1rem;
        }
        
        .kas-table tbody tr {
            transition: all 0.3s ease;
        }
        
        .kas-table tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
        }
        
        .kas-table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }
        
        /* Professional Buttons */
        .kas-btn {
            padding: 0.625rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .kas-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }
        
        .kas-btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #fff;
        }
        
        .kas-btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: #fff;
        }
        
        .kas-btn-danger {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            color: #fff;
        }
        
        /* Professional Badges */
        .kas-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .kas-badge-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }
        
        .kas-badge-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }
        
        .kas-badge-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
        }
        
        .kas-badge-info {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            color: #0c5460;
        }
        
        /* Professional Loading Spinner */
        .kas-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .kas-stats-value {
                font-size: 1.5rem;
            }
            
            .kas-stats-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
            
            .navbar-collapse {
                background: rgba(15, 25, 61, 0.95);
                padding: 1rem;
                border-radius: 10px;
                margin-top: 1rem;
            }
        }
        
        /* Professional Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        /* Professional Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }
    </style>
</head>
<body>

<!-- Professional Loading Overlay -->
<div id="pageLoader" class="kas-loader" style="display: none;">
    <div class="text-center">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 fw-semibold text-primary">Memuat...</p>
    </div>
</div>