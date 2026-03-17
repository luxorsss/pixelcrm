<?php
/**
 * Followup Monitor Dashboard - Enhanced Clean Version
 * Simpan sebagai: monitor_followup.php
 */

require_once 'includes/init.php';

$page_title = "Monitor Followup";

// Auto refresh setiap 30 detik
$refresh = isset($_GET['refresh']) ? (int)$_GET['refresh'] : 30;

// Search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// === PRESENTATION SECTION ===
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<?php if ($refresh > 0): ?>
<meta http-equiv="refresh" content="<?= $refresh ?>;url=?<?= http_build_query(['refresh' => $refresh, 'search' => $search]) ?>">
<?php endif; ?>

<style>
.status-card { 
    transition: all 0.3s ease; 
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}
.status-card:hover { 
    transform: translateY(-2px); 
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}
.status-ready { border-left: 4px solid #28a745; background: linear-gradient(135deg, #fff, #f8fff8); }
.status-waiting { border-left: 4px solid #ffc107; background: linear-gradient(135deg, #fff, #fffcf0); }
.status-sent { border-left: 4px solid #007bff; background: linear-gradient(135deg, #fff, #f0f8ff); }
.status-failed { border-left: 4px solid #dc3545; background: linear-gradient(135deg, #fff, #fff5f5); }
.refresh-badge { 
    background: linear-gradient(135deg, #17a2b8, #138496); 
    color: white; 
    border-radius: 20px;
    padding: 8px 15px;
    font-size: 0.85rem;
}
.search-badge {
    background: linear-gradient(135deg, #28a745, #1e7e34);
    color: white;
    border-radius: 20px;
    padding: 8px 15px;
    font-size: 0.85rem;
}
.log-preview {
    background: #f8f9fa;
    border-left: 3px solid #007bff;
    font-family: 'Courier New', monospace;
    font-size: 0.8rem;
    max-height: 200px;
    overflow-y: auto;
}
.activity-item {
    border-left: 3px solid transparent;
    transition: all 0.2s ease;
}
.activity-item:hover {
    border-left-color: #007bff;
    background-color: #f8f9fa;
}
.search-highlight {
    background-color: #fff3cd;
    padding: 1px 3px;
    border-radius: 3px;
}
</style>
<div class="main-content">
    <!-- Top Header -->
    <div class="bg-white border-bottom p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1">Monitor Followup</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/followup/">Followup</a></li>
                        <li class="breadcrumb-item active">Monitor</li>
                    </ol>
                </nav>
            </div>
            <div class="text-muted">
                <i class="fas fa-calendar me-1"></i><?= date('d F Y H:i:s') ?>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4">
        <!-- Header Actions -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-satellite-dish me-2 text-primary"></i>Monitor Followup</h2>
            <div class="btn-group">
                <a href="cron/followup_scheduler.php" class="btn btn-success btn-sm" target="_blank">
                    <i class="fas fa-play me-1"></i>Manual Run
                </a>
                <a href="modules/followup/" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </div>

        <!-- Refresh Status & Search -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="refresh-badge">
                    <i class="fas fa-sync-alt me-1"></i>
                    <strong>Auto Refresh:</strong> <?= $refresh > 0 ? "Aktif ($refresh detik)" : "Nonaktif" ?>
                </div>
                <?php if (!empty($search)): ?>
                    <div class="search-badge mt-2">
                        <i class="fas fa-search me-1"></i>
                        <strong>Filter:</strong> "<?= clean($search) ?>"
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-end">
                <div class="btn-group btn-group-sm mb-2">
                    <a href="?<?= http_build_query(['refresh' => 10, 'search' => $search]) ?>" class="btn btn-outline-info <?= $refresh == 10 ? 'active' : '' ?>">10s</a>
                    <a href="?<?= http_build_query(['refresh' => 30, 'search' => $search]) ?>" class="btn btn-outline-info <?= $refresh == 30 ? 'active' : '' ?>">30s</a>
                    <a href="?<?= http_build_query(['refresh' => 60, 'search' => $search]) ?>" class="btn btn-outline-info <?= $refresh == 60 ? 'active' : '' ?>">60s</a>
                    <a href="?<?= http_build_query(['refresh' => 0, 'search' => $search]) ?>" class="btn btn-outline-secondary <?= $refresh == 0 ? 'active' : '' ?>">
                        <i class="fas fa-pause"></i> Stop
                    </a>
                </div>
            </div>
        </div>

        <!-- Search Form -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">
                    <i class="fas fa-search me-2 text-primary"></i>Pencarian Followup
                </h6>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="refresh" value="<?= $refresh ?>">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Cari nama pelanggan atau nomor WhatsApp..." 
                                   value="<?= clean($search) ?>"
                                   autocomplete="off">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Cari
                            </button>
                        </div>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Contoh: "John", "081234567890", "628123"
                        </small>
                    </div>
                    <div class="col-md-4">
                        <?php if (!empty($search)): ?>
                            <a href="?<?= http_build_query(['refresh' => $refresh]) ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Reset Pencarian
                            </a>
                        <?php endif; ?>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Auto refresh tetap aktif saat pencarian
                            </small>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Real-time Stats -->
        <div class="row mb-4">
            <?php
            // Build search condition untuk stats
            $search_condition = '';
            $search_params = [];
            
            if (!empty($search)) {
                $search_condition = " AND (p.nama LIKE ? OR p.nomor_wa LIKE ?)";
                $search_term = '%' . $search . '%';
                $search_params = [$search_term, $search_term];
            }
            
            $stats = [
                'ready' => fetchRow("SELECT COUNT(*) as count FROM followup_logs fl JOIN pelanggan p ON fl.pelanggan_id = p.id WHERE fl.status = 'pending' AND fl.jadwal_kirim <= NOW() $search_condition", $search_params)['count'],
                'waiting' => fetchRow("SELECT COUNT(*) as count FROM followup_logs fl JOIN pelanggan p ON fl.pelanggan_id = p.id WHERE fl.status = 'pending' AND fl.jadwal_kirim > NOW() $search_condition", $search_params)['count'],
                'sent_today' => fetchRow("SELECT COUNT(*) as count FROM followup_logs fl JOIN pelanggan p ON fl.pelanggan_id = p.id WHERE fl.status = 'terkirim' AND DATE(fl.waktu_kirim) = CURDATE() $search_condition", $search_params)['count'],
                'failed_today' => fetchRow("SELECT COUNT(*) as count FROM followup_logs fl JOIN pelanggan p ON fl.pelanggan_id = p.id WHERE fl.status = 'gagal' AND DATE(fl.created_at) = CURDATE() $search_condition", $search_params)['count']
            ];
            ?>
            <div class="col-md-3 mb-3">
                <div class="card status-card status-ready h-100">
                    <div class="card-body text-center">
                        <div class="d-flex justify-content-center align-items-center mb-2">
                            <i class="fas fa-rocket fa-2x text-success me-2"></i>
                            <h3 class="text-success mb-0"><?= number_format($stats['ready']) ?></h3>
                        </div>
                        <h6 class="text-muted mb-0">Siap Dikirim</h6>
                        <?php if ($stats['ready'] > 0): ?>
                            <small class="text-success">⚡ Ready to go!</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card status-card status-waiting h-100">
                    <div class="card-body text-center">
                        <div class="d-flex justify-content-center align-items-center mb-2">
                            <i class="fas fa-clock fa-2x text-warning me-2"></i>
                            <h3 class="text-warning mb-0"><?= number_format($stats['waiting']) ?></h3>
                        </div>
                        <h6 class="text-muted mb-0">Menunggu Jadwal</h6>
                        <small class="text-warning">⏳ Scheduled</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card status-card status-sent h-100">
                    <div class="card-body text-center">
                        <div class="d-flex justify-content-center align-items-center mb-2">
                            <i class="fas fa-paper-plane fa-2x text-primary me-2"></i>
                            <h3 class="text-primary mb-0"><?= number_format($stats['sent_today']) ?></h3>
                        </div>
                        <h6 class="text-muted mb-0">Terkirim Hari Ini</h6>
                        <small class="text-primary">📤 Success</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card status-card status-failed h-100">
                    <div class="card-body text-center">
                        <div class="d-flex justify-content-center align-items-center mb-2">
                            <i class="fas fa-exclamation-triangle fa-2x text-danger me-2"></i>
                            <h3 class="text-danger mb-0"><?= number_format($stats['failed_today']) ?></h3>
                        </div>
                        <h6 class="text-muted mb-0">Gagal Hari Ini</h6>
                        <small class="text-danger">❌ Failed</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert untuk pesan ready -->
        <?php if ($stats['ready'] > 0): ?>
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x text-warning me-3"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-1">Ada <?= number_format($stats['ready']) ?> pesan siap dikirim!</h5>
                    <p class="mb-2">Cron job mungkin tidak berjalan. Solusi cepat:</p>
                    <div class="btn-group btn-group-sm">
                        <a href="cron/followup_scheduler.php" target="_blank" class="btn btn-warning">
                            <i class="fas fa-play me-1"></i>Manual Run
                        </a>
                        <a href="modules/followup/" class="btn btn-outline-warning">
                            <i class="fas fa-cog me-1"></i>Kelola Followup
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Activity -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2 text-primary"></i>Aktivitas Terbaru
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <?php if (!empty($search)): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-filter me-1"></i>
                                Hasil untuk: "<?= clean($search) ?>"
                            </span>
                        <?php endif; ?>
                        <span class="badge bg-light text-dark">
                            <?php
                            $total_matching = !empty($search) ? 
                                fetchRow("SELECT COUNT(*) as count FROM followup_logs fl LEFT JOIN pelanggan p ON fl.pelanggan_id = p.id WHERE (p.nama LIKE ? OR p.nomor_wa LIKE ?)", ['%'.$search.'%', '%'.$search.'%'])['count'] : 
                                fetchRow("SELECT COUNT(*) as count FROM followup_logs")['count'];
                            ?>
                            <?= !empty($search) ? "Ditemukan: " . number_format($total_matching) : "Total: " . number_format($total_matching) ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="80">Waktu</th>
                                <th>Pelanggan</th>
                                <th width="140">No. WhatsApp</th>
                                <th>Pesan</th>
                                <th width="100">Jadwal</th>
                                <th width="120">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Build search query untuk recent logs
                            $search_where = '';
                            $search_params = [];
                            
                            if (!empty($search)) {
                                $search_where = " AND (p.nama LIKE ? OR p.nomor_wa LIKE ?)";
                                $search_term = '%' . $search . '%';
                                $search_params = [$search_term, $search_term];
                            }
                            
                            $recent_logs = fetchAll("
                                SELECT fl.*, p.nama, p.nomor_wa, fm.nama_pesan,
                                       CASE 
                                           WHEN fl.status = 'pending' AND fl.jadwal_kirim <= NOW() THEN 'ready'
                                           WHEN fl.status = 'pending' AND fl.jadwal_kirim > NOW() THEN 'waiting'
                                           ELSE fl.status
                                       END as display_status
                                FROM followup_logs fl
                                LEFT JOIN pelanggan p ON fl.pelanggan_id = p.id
                                LEFT JOIN followup_messages fm ON fl.followup_message_id = fm.id
                                WHERE 1=1 $search_where
                                ORDER BY fl.created_at DESC
                                LIMIT 20
                            ", $search_params);

                            if (empty($recent_logs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <?php if (!empty($search)): ?>
                                            <i class="fas fa-search fa-2x mb-2"></i><br>
                                            Tidak ada aktivitas followup yang cocok dengan pencarian "<strong><?= clean($search) ?></strong>"
                                            <br><small class="text-muted mt-2">Coba gunakan kata kunci yang berbeda</small>
                                        <?php else: ?>
                                            <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                            Belum ada aktivitas followup
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else:
                                foreach ($recent_logs as $log):
                                    $badge_class = [
                                        'ready' => 'bg-success',
                                        'waiting' => 'bg-warning text-dark',
                                        'terkirim' => 'bg-primary',
                                        'gagal' => 'bg-danger'
                                    ][$log['display_status']] ?? 'bg-secondary';

                                    $status_icon = [
                                        'ready' => 'fas fa-rocket',
                                        'waiting' => 'fas fa-clock', 
                                        'terkirim' => 'fas fa-check-circle',
                                        'gagal' => 'fas fa-times-circle'
                                    ][$log['display_status']] ?? 'fas fa-question-circle';
                                    
                                    // Highlight search term dalam nama dan nomor
                                    $highlighted_nama = $log['nama'];
                                    $highlighted_nomor = $log['nomor_wa'];
                                    
                                    if (!empty($search)) {
                                        $highlighted_nama = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<span class="search-highlight">$1</span>', $log['nama']);
                                        $highlighted_nomor = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<span class="search-highlight">$1</span>', $log['nomor_wa']);
                                    }
                                ?>
                                <tr class="activity-item">
                                    <td class="text-muted">
                                        <?= date('H:i:s', strtotime($log['created_at'])) ?>
                                    </td>
                                    <td class="fw-bold"><?= $highlighted_nama ?></td>
                                    <td>
                                        <a href="<?= whatsappLink($log['nomor_wa']) ?>" target="_blank" 
                                           class="text-success text-decoration-none">
                                            <i class="fab fa-whatsapp me-1"></i><?= $highlighted_nomor ?>
                                        </a>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= clean($log['nama_pesan']) ?></small>
                                    </td>
                                    <td class="text-muted">
                                        <small><?= date('d/m H:i', strtotime($log['jadwal_kirim'])) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badge_class ?>">
                                            <i class="<?= $status_icon ?> me-1"></i><?= ucfirst($log['display_status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Log Files -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0">
                            <i class="fas fa-file-alt me-2 text-success"></i>Latest Followup Log
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="log-preview p-3"><?php
                        $log_file = __DIR__ . '/logs/followup_' . date('Y-m') . '.log';
                        if (file_exists($log_file)) {
                            $lines = file($log_file);
                            $recent_lines = array_slice($lines, -10); // Last 10 lines
                            if (!empty($recent_lines)) {
                                echo clean(implode('', $recent_lines));
                            } else {
                                echo "Log file kosong";
                            }
                        } else {
                            echo "📄 Belum ada log file\n📅 File: followup_" . date('Y-m') . ".log";
                        }
                        ?></div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>Menampilkan 10 baris terakhir
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0">
                            <i class="fab fa-whatsapp me-2 text-success"></i>Latest WhatsApp Log
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="log-preview p-3"><?php
                        $wa_log_file = __DIR__ . '/logs/whatsapp_' . date('Y-m') . '.log';
                        if (file_exists($wa_log_file)) {
                            $lines = file($wa_log_file);
                            $recent_lines = array_slice($lines, -5); // Last 5 lines
                            if (!empty($recent_lines)) {
                                echo clean(implode('', $recent_lines));
                            } else {
                                echo "Log file kosong";
                            }
                        } else {
                            echo "📄 Belum ada log WhatsApp\n📅 File: whatsapp_" . date('Y-m') . ".log";
                        }
                        ?></div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>Menampilkan 5 baris terakhir
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Info Footer -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <i class="fas fa-server text-primary fa-2x mb-2"></i>
                        <h6>Server Time</h6>
                        <small class="text-muted"><?= date('d/m/Y H:i:s') ?></small>
                    </div>
                    <div class="col-md-3">
                        <i class="fas fa-database text-info fa-2x mb-2"></i>
                        <h6>Total Logs</h6>
                        <small class="text-muted">
                            <?php
                            $total_logs = !empty($search) ? 
                                fetchRow("SELECT COUNT(*) as count FROM followup_logs fl LEFT JOIN pelanggan p ON fl.pelanggan_id = p.id WHERE (p.nama LIKE ? OR p.nomor_wa LIKE ?)", ['%'.$search.'%', '%'.$search.'%'])['count'] :
                                fetchRow("SELECT COUNT(*) as count FROM followup_logs")['count'];
                            echo number_format($total_logs);
                            ?>
                            <?= !empty($search) ? ' (filtered)' : ' records' ?>
                        </small>
                    </div>
                    <div class="col-md-3">
                        <i class="fas fa-cog text-warning fa-2x mb-2"></i>
                        <h6>Auto Refresh</h6>
                        <small class="text-muted">
                            <?= $refresh > 0 ? "Every {$refresh}s" : "Disabled" ?>
                        </small>
                    </div>
                    <div class="col-md-3">
                        <i class="fas fa-heart text-danger fa-2x mb-2"></i>
                        <h6>System Status</h6>
                        <small class="text-success">
                            <i class="fas fa-check-circle me-1"></i>Online
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>    
</div>

<!-- JavaScript untuk Enhanced UX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit search form dengan delay
    const searchInput = document.querySelector('input[name="search"]');
    let searchTimeout;
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const form = this.form;
            
            // Debounce search untuk menghindari terlalu banyak request
            searchTimeout = setTimeout(function() {
                if (searchInput.value.length >= 2 || searchInput.value.length === 0) {
                    form.submit();
                }
            }, 800); // 800ms delay
        });
        
        // Submit langsung saat Enter ditekan
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                clearTimeout(searchTimeout);
                this.form.submit();
            }
        });
        
        // Focus pada search input dengan keyboard shortcut
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });
    }
    
    // Show loading state saat search
    const searchForm = document.querySelector('form');
    if (searchForm) {
        searchForm.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Mencari...';
                submitBtn.disabled = true;
            }
        });
    }
    
    // Highlight search results dengan smooth animation
    document.querySelectorAll('.search-highlight').forEach(function(element) {
        element.style.animation = 'highlight-fade 2s ease-in-out';
    });
    
    // Auto-refresh counter
    <?php if ($refresh > 0): ?>
    let refreshCounter = <?= $refresh ?>;
    const refreshBadge = document.querySelector('.refresh-badge');
    
    if (refreshBadge) {
        const countdownInterval = setInterval(function() {
            refreshCounter--;
            if (refreshCounter <= 0) {
                refreshBadge.innerHTML = '<i class="fas fa-sync-alt fa-spin me-1"></i><strong>Refreshing...</strong>';
                clearInterval(countdownInterval);
            } else {
                refreshBadge.innerHTML = '<i class="fas fa-sync-alt me-1"></i><strong>Auto Refresh:</strong> ' + refreshCounter + ' detik';
            }
        }, 1000);
    }
    <?php endif; ?>
    
    console.log('📊 Monitor Followup with Search loaded');
    <?php if (!empty($search)): ?>
    console.log('🔍 Search active for: "<?= addslashes($search) ?>"');
    <?php endif; ?>
});
</script>

<style>
@keyframes highlight-fade {
    0% { background-color: #fff3cd; }
    50% { background-color: #ffeaa7; }
    100% { background-color: #fff3cd; }
}

/* Loading state */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Search input enhancements */
.input-group .form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Enhanced tooltips */
[title] {
    cursor: help;
}

/* Responsive search form */
@media (max-width: 768px) {
    .search-badge {
        font-size: 0.75rem;
        padding: 6px 12px;
    }
    
    .refresh-badge {
        font-size: 0.75rem;
        padding: 6px 12px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>