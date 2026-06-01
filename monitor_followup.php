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

<div class="main-content dashboard-wrapper flex-grow-1">
    <?php
        // Execute queries only once for metrics
        $search_condition = !empty($search) ? " AND (p.nama LIKE ? OR p.nomor_wa LIKE ?)" : "";
        $search_params = !empty($search) ? ['%'.$search.'%', '%'.$search.'%'] : [];
        
        $stats = [
            'ready' => fetchRow("SELECT COUNT(*) as count FROM followup_logs fl JOIN pelanggan p ON fl.pelanggan_id = p.id WHERE fl.status = 'pending' AND fl.jadwal_kirim <= NOW() $search_condition", $search_params)['count'],
            'waiting' => fetchRow("SELECT COUNT(*) as count FROM followup_logs fl JOIN pelanggan p ON fl.pelanggan_id = p.id WHERE fl.status = 'pending' AND fl.jadwal_kirim > NOW() $search_condition", $search_params)['count'],
            'sent_today' => fetchRow("SELECT COUNT(*) as count FROM followup_logs fl JOIN pelanggan p ON fl.pelanggan_id = p.id WHERE fl.status = 'terkirim' AND DATE(fl.waktu_kirim) = CURDATE() $search_condition", $search_params)['count'],
            'failed_today' => fetchRow("SELECT COUNT(*) as count FROM followup_logs fl JOIN pelanggan p ON fl.pelanggan_id = p.id WHERE fl.status = 'gagal' AND DATE(fl.created_at) = CURDATE() $search_condition", $search_params)['count']
        ];
    ?>

    <div class="dash-header mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <a href="<?= BASE_URL ?>modules/followup/" class="text-muted text-decoration-none fw-bold" style="font-size: 0.85rem;">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Pengaturan Sequence
            </a>
            <h1 class="dash-title mt-2 d-flex align-items-center gap-2">
                <i class="fas fa-satellite-dish text-primary"></i> Observability Log
            </h1>
            <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.85rem;">
                Server Time: <strong class="text-dark"><?= date('d F Y, H:i:s') ?></strong>
            </div>
        </div>
        
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="d-flex align-items-center bg-white rounded-pill border p-1" style="font-size: 0.8rem;">
                <span class="text-muted fw-bold px-2"><i class="fas fa-sync-alt me-1"></i> Auto Refresh:</span>
                <a href="?<?= http_build_query(['refresh' => 10, 'search' => $search]) ?>" class="badge-clean <?= $refresh == 10 ? 'bg-primary text-white' : 'text-muted' ?>" style="text-decoration:none;">10s</a>
                <a href="?<?= http_build_query(['refresh' => 30, 'search' => $search]) ?>" class="badge-clean <?= $refresh == 30 ? 'bg-primary text-white' : 'text-muted' ?>" style="text-decoration:none;">30s</a>
                <a href="?<?= http_build_query(['refresh' => 0, 'search' => $search]) ?>" class="badge-clean <?= $refresh == 0 ? 'bg-danger text-white' : 'text-muted' ?>" style="text-decoration:none;"><i class="fas fa-pause"></i></a>
            </div>
            
            <a href="cron/followup_scheduler.php" class="btn btn-dark fw-bold rounded-pill" target="_blank">
                <i class="fas fa-play-circle me-1"></i> Force Run Cron
            </a>
        </div>
    </div>

    <?php if ($stats['ready'] > 0): ?>
        <div class="alert alert-editorial mb-4" style="border-left-color: var(--warning-color); background: #FFFBEB;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle text-warning me-3 fs-3"></i>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Terdapat <?= number_format($stats['ready']) ?> pesan menunggu eksekusi!</h6>
                        <span class="text-muted" style="font-size: 0.85rem;">Jika antrean tidak kunjung berkurang, kemungkinan Cron Job server sedang mati.</span>
                    </div>
                </div>
                <a href="cron/followup_scheduler.php" target="_blank" class="btn btn-warning fw-bold btn-sm rounded-pill px-3">
                    <i class="fas fa-bolt me-1"></i> Trigger Manual
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="panel-editorial d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 p-3 px-4" style="background: var(--bg-surface);">    
        <div class="d-flex align-items-center gap-3 pe-4 border-end flex-grow-1">
            <div style="width: 44px; height: 44px; background: #ECFDF5; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fas fa-rocket text-success"></i>
            </div>
            <div>
                <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Siap Dikirim</div>
                <div class="fw-bold text-success fs-5" style="line-height: 1;"><?= number_format($stats['ready']) ?></div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3 pe-4 border-end flex-grow-1">
            <div style="width: 44px; height: 44px; background: #FFFBEB; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fas fa-clock text-warning"></i>
            </div>
            <div>
                <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Menunggu Jadwal</div>
                <div class="fw-bold text-dark fs-5" style="line-height: 1;"><?= number_format($stats['waiting']) ?></div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3 pe-4 border-end flex-grow-1">
            <div style="width: 44px; height: 44px; background: #EFF6FF; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fas fa-paper-plane text-primary"></i>
            </div>
            <div>
                <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Sukses (Hari Ini)</div>
                <div class="fw-bold text-primary fs-5" style="line-height: 1;"><?= number_format($stats['sent_today']) ?></div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3 flex-grow-1">
            <div style="width: 44px; height: 44px; background: #FEF2F2; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fas fa-exclamation-triangle text-danger"></i>
            </div>
            <div>
                <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Gagal (Hari Ini)</div>
                <div class="fw-bold text-danger fs-5" style="line-height: 1;"><?= number_format($stats['failed_today']) ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="panel-editorial p-0 overflow-hidden h-100">
                
                <div class="p-3 bg-light border-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <h3 class="panel-title m-0"><i class="fas fa-history text-primary"></i> Traffic Database</h3>
                    
                    <form method="GET" class="m-0 position-relative" style="width: 250px;">
                        <input type="hidden" name="refresh" value="<?= $refresh ?>">
                        <i class="fas fa-search position-absolute text-muted" style="top: 50%; left: 12px; transform: translateY(-50%); font-size: 0.8rem;"></i>
                        <input type="text" name="search" class="form-control bg-white fw-bold text-dark border-0 shadow-sm" 
                               placeholder="Cari nama / no wa..." value="<?= clean($search) ?>" 
                               style="font-size: 0.85rem; padding-left: 32px; border-radius: 100px;">
                        <?php if (!empty($search)): ?>
                            <a href="?<?= http_build_query(['refresh' => $refresh]) ?>" class="position-absolute text-danger" style="top: 50%; right: 12px; transform: translateY(-50%);"><i class="fas fa-times-circle"></i></a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="table-editorial mb-0">
                        <thead class="sticky-top">
                            <tr>
                                <th width="80">Waktu</th>
                                <th>Target Penerima</th>
                                <th>Sequence Pesan</th>
                                <th width="120" class="text-center pe-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
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
                                WHERE 1=1 $search_condition
                                ORDER BY fl.created_at DESC
                                LIMIT 20
                            ", $search_params);

                            if (empty($recent_logs)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3 opacity-50"></i>
                                        <h6 class="fw-bold text-dark">Data Kosong</h6>
                                        <div class="text-muted" style="font-size: 0.85rem;">Tidak ada log aktivitas yang ditemukan.</div>
                                    </td>
                                </tr>
                            <?php else:
                                foreach ($recent_logs as $log):
                                    $status_map = [
                                        'ready' => ['bg' => '#ECFDF5', 'col' => '#059669', 'ic' => 'fa-rocket', 'txt' => 'Siap'],
                                        'waiting' => ['bg' => '#FFFBEB', 'col' => '#D97706', 'ic' => 'fa-clock', 'txt' => 'Menunggu'],
                                        'terkirim' => ['bg' => '#EFF6FF', 'col' => '#2563EB', 'ic' => 'fa-check-circle', 'txt' => 'Sukses'],
                                        'gagal' => ['bg' => '#FEF2F2', 'col' => '#EF4444', 'ic' => 'fa-times-circle', 'txt' => 'Gagal']
                                    ];
                                    $sm = $status_map[$log['display_status']] ?? ['bg' => '#F3F4F6', 'col' => '#6B7280', 'ic' => 'fa-question-circle', 'txt' => 'Unknown'];
                            ?>
                                <tr>
                                    <td>
                                        <div class="text-dark fw-bold" style="font-size: 0.85rem;"><?= date('H:i', strtotime($log['created_at'])) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= date('d/m', strtotime($log['created_at'])) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark" style="font-size: 0.9rem; line-height: 1.2;">
                                            <?php
                                                $hl_nama = clean($log['nama']);
                                                if(!empty($search)) $hl_nama = preg_replace('/('.preg_quote($search, '/').')/i', '<mark class="bg-warning px-1 rounded">$1</mark>', $hl_nama);
                                                echo $hl_nama;
                                            ?>
                                        </div>
                                        <a href="<?= whatsappLink($log['nomor_wa']) ?>" target="_blank" class="text-muted text-decoration-none mt-1 d-inline-block" style="font-size: 0.75rem;">
                                            <i class="fab fa-whatsapp text-success me-1"></i>
                                            <?php
                                                $hl_wa = $log['nomor_wa'];
                                                if(!empty($search)) $hl_wa = preg_replace('/('.preg_quote($search, '/').')/i', '<mark class="bg-warning px-1 rounded">$1</mark>', $hl_wa);
                                                echo $hl_wa;
                                            ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="text-dark fw-bold" style="font-size: 0.85rem;"><i class="fas fa-comment-dots text-muted me-1"></i> <?= clean($log['nama_pesan']) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><i class="fas fa-calendar-alt me-1"></i>Jadwal: <?= date('d/m H:i', strtotime($log['jadwal_kirim'])) ?></div>
                                    </td>
                                    <td class="text-center pe-4">
                                        <span class="badge-clean" style="background: <?= $sm['bg'] ?>; color: <?= $sm['col'] ?>;">
                                            <i class="fas <?= $sm['ic'] ?> me-1"></i><?= $sm['txt'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4 d-flex flex-column gap-4">
            
            <div class="panel-editorial p-0 overflow-hidden d-flex flex-column h-50">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
                    <h3 class="panel-title m-0" style="font-size: 0.9rem;"><i class="fas fa-terminal text-dark"></i> System Logs</h3>
                </div>
                <div class="p-3 flex-grow-1" style="background: #1E1E1E; overflow-y: auto;">
                    <div style="font-family: 'Consolas', 'Courier New', monospace; font-size: 0.75rem; color: #10B981; line-height: 1.5; white-space: pre-wrap; word-break: break-all;">
                        <?php
                        $log_file = __DIR__ . '/logs/followup_' . date('Y-m') . '.log';
                        if (file_exists($log_file)) {
                            $lines = file($log_file);
                            $recent_lines = array_slice($lines, -12);
                            echo !empty($recent_lines) ? clean(implode('', $recent_lines)) : "";
                        } else {
                            echo "[!] No system log generated for " . date('M Y') . "\n";
                            echo "[*] Waiting for execution...";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="panel-editorial p-0 overflow-hidden d-flex flex-column h-50">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
                    <h3 class="panel-title m-0" style="font-size: 0.9rem;"><i class="fas fa-terminal text-dark"></i> WhatsApp API Logs</h3>
                </div>
                <div class="p-3 flex-grow-1" style="background: #1E1E1E; overflow-y: auto;">
                    <div style="font-family: 'Consolas', 'Courier New', monospace; font-size: 0.75rem; color: #3B82F6; line-height: 1.5; white-space: pre-wrap; word-break: break-all;">
                        <?php
                        $wa_log_file = __DIR__ . '/logs/whatsapp_' . date('Y-m') . '.log';
                        if (file_exists($wa_log_file)) {
                            $lines = file($wa_log_file);
                            $recent_lines = array_slice($lines, -12);
                            echo !empty($recent_lines) ? clean(implode('', $recent_lines)) : "";
                        } else {
                            echo "[!] No Fonnte connection logged yet\n";
                            echo "[*] Awaiting API trigger...";
                        }
                        ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    let searchTimeout;
    
    // Auto Submit debounce
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => { this.form.submit(); }, 800);
        });
        
        // Shortcut Ctrl+F
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });
    }

    // UX Feedback on Form Search
    const searchForm = document.querySelector('form');
    if (searchForm) {
        searchForm.addEventListener('submit', function() {
            searchInput.style.opacity = '0.5';
        });
    }

    // Auto Refresh Countdown UX (Tapping into Dash Header)
    <?php if ($refresh > 0): ?>
    let refreshCounter = <?= $refresh ?>;
    const interval = setInterval(() => {
        refreshCounter--;
        if (refreshCounter <= 0) clearInterval(interval);
    }, 1000);
    <?php endif; ?>
});
</script>

<?php require_once 'includes/footer.php'; ?>