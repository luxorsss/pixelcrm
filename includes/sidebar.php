<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_module = basename(dirname($_SERVER['PHP_SELF']));
// Deteksi apakah URL sedang berada di dalam folder modul
$is_in_module = strpos($_SERVER['PHP_SELF'], '/modules/') !== false;

function isActive($page, $module = '')
{
    global $current_page, $current_module, $is_in_module;
    
    // Logika khusus untuk menu Dashboard
    if ($module === '' && $page === 'index.php') {
        // Hanya nyala jika nama file index.php DAN bukan di dalam folder modules
        return (!$is_in_module && $current_page === 'index.php') ? 'active' : '';
    }
    
    // Logika untuk menu Modul lainnya
    return ($current_module === $module) ? 'active' : '';
}
?>

<button class="mobile-menu-btn d-lg-none" onclick="toggleSidebar()" id="mobileMenuBtn" style="background: #111827; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
    <i class="fas fa-bars-staggered"></i>
</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <button class="toggle-btn d-lg-none" onclick="toggleSidebar()" style="background: transparent; color: #94A3B8; padding: 0; margin-right: 0.5rem;">
            <i class="fas fa-times fs-5"></i>
        </button>
        <h4 class="sidebar-title"><i class="fas fa-bolt text-warning me-2"></i><?= APP_NAME ?></h4>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-group">
            <div class="nav-item">
                <a href="<?= BASE_URL ?>" class="nav-link <?= isActive('index.php') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-chart-pie"></i><span>Dashboard</span>
                </a>
            </div>
        </div>

        <div class="nav-group">
            <div class="nav-group-title">Manajemen Data</div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/produk/" class="nav-link <?= isActive('', 'produk') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-box-open"></i><span>Katalog Produk</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/pelanggan/" class="nav-link <?= isActive('', 'pelanggan') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-user-circle"></i><span>Database Pelanggan</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/bundling/" class="nav-link <?= isActive('', 'bundling') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-layer-group"></i><span>Paket Bundling</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/kupon/" class="nav-link <?= isActive('', 'kupon') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-ticket-alt"></i><span>Kupon Diskon</span>
                </a>
            </div>
        </div>

        <div class="nav-group">
            <div class="nav-group-title">Finansial</div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/transaksi/" class="nav-link <?= isActive('', 'transaksi') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-cash-register"></i><span>Transaksi & Kasir</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/laporan/analitik.php" class="nav-link <?= isActive('analitik.php', 'laporan') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-chart-line"></i><span>Analisis Laporan</span>
                </a>
            </div>
        </div>

        <div class="nav-group">
            <div class="nav-group-title">Automasi & Pesan</div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/template/" class="nav-link <?= isActive('', 'template') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-envelope-open-text"></i><span>Template Pesan</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/followup/" class="nav-link <?= isActive('', 'followup') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-history"></i><span>Smart Follow-up</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/broadcast/" class="nav-link <?= isActive('', 'broadcast') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-broadcast-tower"></i><span>Broadcast WA</span>
                </a>
            </div>
        </div>

        <div class="nav-group">
            <div class="nav-group-title">Sistem</div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/rekening/" class="nav-link <?= isActive('', 'rekening') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-landmark"></i><span>Rekening Bank</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>modules/onesender/" class="nav-link <?= isActive('', 'onesender') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-plug"></i><span>Koneksi WA (API)</span>
                </a>
            </div>
        </div>
    </nav>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const body = document.body;

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
        if (window.innerWidth <= 991) {
            closeSidebar();
        }
    }

    window.addEventListener('resize', function () {
        if (window.innerWidth > 991) {
            closeSidebar();
        }
    });

    document.addEventListener('click', function (event) {
        const sidebar = document.getElementById('sidebar');
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const toggleBtn = document.querySelector('.toggle-btn');

        if (window.innerWidth <= 991 && sidebar.classList.contains('open')) {
            if (!sidebar.contains(event.target) &&
                !mobileBtn.contains(event.target) &&
                (!toggleBtn || !toggleBtn.contains(event.target))) {
                closeSidebar();
            }
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const body = document.body;

        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
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