<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_module = basename(dirname($_SERVER['PHP_SELF']));
$is_in_module = strpos($_SERVER['PHP_SELF'], '/modules/') !== false;

function isActive($page, $module = '')
{
    global $current_page, $current_module, $is_in_module;
    if ($module === '' && $page === 'index.php') {
        return (!$is_in_module && $current_page === 'index.php') ? 'active' : '';
    }
    return ($current_module === $module) ? 'active' : '';
}
?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4 class="sidebar-title d-flex align-items-center gap-2">
            <div style="width:32px; height:32px; background: #3B82F6; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-layer-group text-white" style="font-size: 0.9rem;"></i>
            </div>
            <?= APP_NAME ?>
        </h4>
        <button type="button" class="btn btn-link text-white d-lg-none p-0 opacity-50" onclick="closeSidebar()" style="text-decoration: none;">
            <i class="fas fa-times fs-4"></i>
        </button>
    </div>

    <nav class="sidebar-nav pb-5">
        <div class="nav-group">
            <div class="nav-item">
                <a href="<?= BASE_URL ?>" class="nav-link <?= isActive('index.php') ?>" onclick="closeSidebarMobile()">
                    <i class="fas fa-chart-pie"></i><span>Dashboard</span>
                </a>
            </div>
        </div>

        <div class="nav-group mt-3">
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

        <div class="nav-group mt-3">
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

        <div class="nav-group mt-3">
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

        <div class="nav-group mt-3">
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

        <div class="nav-group mt-5 border-top pt-3" style="border-color: rgba(255,255,255,0.05) !important;">
            <div class="nav-item">
                <a href="<?= BASE_URL ?>logout.php" class="nav-link nav-logout">
                    <i class="fas fa-sign-out-alt"></i><span>Keluar (Logout)</span>
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
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
            
            // Kunci scroll body saat menu terbuka
            if(sidebar.classList.contains('show')) {
                body.style.overflow = 'hidden';
            } else {
                body.style.overflow = '';
            }
        }
    }

    function closeSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const body = document.body;

        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        body.style.overflow = '';
    }

    function closeSidebarMobile() {
        if (window.innerWidth <= 991) {
            closeSidebar();
        }
    }

    window.addEventListener('resize', function () {
        if (window.innerWidth > 991) {
            closeSidebar(); // Reset status jika ditarik ke layar lebar
        }
    });

    // Menutup sidebar jika layar digeser/diswipe ke kiri (Opsional tapi UX banget)
    let touchstartX = 0;
    let touchendX = 0;
    
    document.addEventListener('touchstart', e => { touchstartX = e.changedTouches[0].screenX; }, {passive: true});
    document.addEventListener('touchend', e => {
        touchendX = e.changedTouches[0].screenX;
        if (touchstartX - touchendX > 50) { // Geser ke kiri
            closeSidebarMobile();
        }
    }, {passive: true});
</script>