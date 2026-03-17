<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_module = basename(dirname($_SERVER['PHP_SELF']));

function isActive($page, $module = '') {
    global $current_page, $current_module;
    return $module ? ($current_module === $module ? 'active' : '') : ($current_page === $page ? 'active' : '');
}
?>

<!-- Mobile Menu Button (outside sidebar, always visible) -->
<button class="mobile-menu-btn d-lg-none" onclick="toggleSidebar()" id="mobileMenuBtn">
    <i class="fas fa-bars"></i>
</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <button class="toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h4><i class="fas fa-users me-2"></i><?= APP_NAME ?></h4>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-group">
            <div class="nav-item">
                <a href="<?= BASE_URL ?>" class="nav-link <?= isActive('index.php') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
                </a>
            </div>
        </div>
        
        <div class="nav-group">
            <div class="nav-group-title">Manajemen</div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/produk/" class="nav-link <?= isActive('', 'produk') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-box"></i><span>Produk</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/pelanggan/" class="nav-link <?= isActive('', 'pelanggan') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-users"></i><span>Pelanggan</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/bundling/" class="nav-link <?= isActive('', 'bundling') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-layer-group"></i><span>Bundling</span>
                </a>
            </div>
        </div>
        
        <div class="nav-group">
            <div class="nav-group-title">Transaksi</div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/transaksi/" class="nav-link <?= isActive('', 'transaksi') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-shopping-cart"></i><span>Transaksi</span>
                </a>
            </div>
        </div>
        
        <div class="nav-group">
            <div class="nav-group-title">Laporan</div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/laporan/analitik.php" class="nav-link" onclick="closeSidebarMobile()">
                    <i class="fas fa-chart-line"></i><span>Analitik</span>
                </a>
            </div>
        </div>
        
        <div class="nav-group">
            <div class="nav-group-title">Pesan & Komunikasi</div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/template/" class="nav-link <?= isActive('', 'template') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-envelope"></i><span>Template Pesan</span>
                </a>
            </div>
            <!-- 🆕 Menu Follow-up Messages -->
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/followup/" class="nav-link <?= isActive('', 'followup') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-clock"></i><span>Follow-up Messages</span>
                </a>
            </div>
        </div>
        
        <div class="nav-group">
            <div class="nav-group-title">Pengaturan</div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/rekening/" class="nav-link <?= isActive('', 'rekening') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-university"></i><span>Rekening</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/onesender/" class="nav-link <?= isActive('', 'onesender') ?>">
                    <i class="fas fa-whatsapp"></i><span>OneSender Config</span>
                </a>
            </div>
        </div>
    </nav>
</div>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<style>
/* Mobile Menu Button */
.mobile-menu-btn {
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1002;
    background: #007bff;
    border: none;
    color: white;
    padding: 10px 12px;
    border-radius: 6px;
    font-size: 16px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}

.mobile-menu-btn:hover {
    background: #0056b3;
    transform: scale(1.05);
}

/* Sidebar Base Styles */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 280px;
    height: 100vh;
    background: linear-gradient(180deg, #2c3e50, #34495e);
    color: white;
    z-index: 1001;
    transition: all 0.3s ease;
    overflow-y: auto;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
}

.sidebar-header {
    padding: 15px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    background: linear-gradient(135deg, #3498db, #2980b9);
    display: flex;
    align-items: center;
    gap: 10px;
}

.sidebar-header h4 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.toggle-btn {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 8px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.toggle-btn:hover {
    background: rgba(255,255,255,0.3);
}

.sidebar-nav {
    padding: 20px 0;
}

.nav-group {
    margin-bottom: 15px;
}

.nav-group-title {
    padding: 10px 20px;
    font-size: 0.75rem;
    text-transform: uppercase;
    color: rgba(255,255,255,0.6);
    font-weight: 600;
    letter-spacing: 1px;
}

.nav-item {
    margin: 2px 10px;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.nav-link:hover {
    background: rgba(255,255,255,0.1);
    color: white;
    text-decoration: none;
    transform: translateX(5px);
}

.nav-link.active {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    box-shadow: 0 2px 10px rgba(52, 152, 219, 0.3);
}

.nav-link i {
    width: 20px;
    margin-right: 12px;
    text-align: center;
    font-size: 1.1rem;
}

.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.sidebar-overlay.active {
    opacity: 1;
    visibility: visible;
}

/* Desktop Styles */
@media (min-width: 992px) {
    .mobile-menu-btn {
        display: none !important;
    }
    
    .sidebar {
        position: relative;
        transform: translateX(0) !important;
    }
    
    .sidebar-overlay {
        display: none !important;
    }
    
    .main-content {
        margin-left: 280px;
        transition: margin-left 0.3s ease;
    }
}

/* Mobile Styles */
@media (max-width: 991px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.open {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0 !important;
        padding-top: 60px; /* Space for mobile menu button */
    }
    
    body.sidebar-open {
        overflow: hidden;
    }
}

/* Scrollbar */
.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.1);
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.3);
    border-radius: 3px;
}
</style>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const body = document.body;
    
    // Check if mobile
    if (window.innerWidth <= 991) {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
        body.classList.toggle('sidebar-open');
    }
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const body = document.body;
    
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
    body.classList.remove('sidebar-open');
}

function closeSidebarMobile() {
    // Close sidebar when clicking nav links on mobile
    if (window.innerWidth <= 991) {
        closeSidebar();
    }
}

// Close sidebar when window resizes to desktop
window.addEventListener('resize', function() {
    if (window.innerWidth > 991) {
        closeSidebar();
    }
});

// Close sidebar when clicking outside (mobile only)
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const toggleBtn = document.querySelector('.toggle-btn');
    
    if (window.innerWidth <= 991 && sidebar.classList.contains('open')) {
        if (!sidebar.contains(event.target) && 
            !mobileBtn.contains(event.target) && 
            !toggleBtn.contains(event.target)) {
            closeSidebar();
        }
    }
});

// Prevent scrolling when sidebar is open on mobile
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const body = document.body;
    
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                if (sidebar.classList.contains('open') && window.innerWidth <= 991) {
                    body.style.overflow = 'hidden';
                } else {
                    body.style.overflow = '';
                }
            }
        });
    });
    
    observer.observe(sidebar, { attributes: true });
});
</script>