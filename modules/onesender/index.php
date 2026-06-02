<?php
$page_title = "Kelola OneSender";
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/whatsapp_helper.php';

// Ambil semua config OneSender
$configs = fetchAll("SELECT * FROM onesender_config ORDER BY account_name");

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content dashboard-wrapper flex-grow-1">
    
    <?php
    // Siapkan data statistik sebelum merender HTML
    $active_count = 0;
    foreach ($configs as $config) {
        if (!empty($config['api_key']) && !empty($config['api_url'])) {
            $active_count++;
        }
    }
    $stats = getWhatsAppStats(7);
    ?>

    <div class="dash-header mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h1 class="dash-title d-flex align-items-center gap-2">
                <i class="fas fa-server text-primary"></i> Gateway OneSender
            </h1>
            <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">
                Kelola perangkat dan koneksi API pengiriman pesan WhatsApp.
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="create.php" class="btn btn-dark fw-bold rounded-pill px-4" style="box-shadow: 0 4px 12px rgba(17, 24, 39, 0.15);">
                <i class="fas fa-plus me-2"></i> Tambah Device
            </a>
        </div>
    </div>

    <?php if ($msg = getMessage()): ?>
        <div class="alert alert-editorial mb-4" style="border-left-color: <?= $msg[1] === 'error' || $msg[1] === 'danger' ? 'var(--danger-color)' : 'var(--success-color)' ?>;">
            <div class="d-flex align-items-center">
                <i class="fas <?= $msg[1] === 'error' || $msg[1] === 'danger' ? 'fa-exclamation-circle text-danger' : 'fa-check-circle text-success' ?> me-2 fs-5"></i>
                <span class="fw-bold text-dark"><?= clean($msg[0]) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-6 col-xl-3">
            <div class="p-3 rounded-4 border bg-white d-flex align-items-center gap-3 h-100 shadow-sm" style="transition: transform 0.2s;">
                <div style="width: 48px; height: 48px; background: #F3F4F6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fas fa-layer-group text-dark"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Total Device</div>
                    <div class="fw-bold text-dark fs-4" style="line-height: 1.2;"><?= count($configs) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-6 col-xl-3">
            <div class="p-3 rounded-4 border bg-white d-flex align-items-center gap-3 h-100 shadow-sm" style="transition: transform 0.2s;">
                <div style="width: 48px; height: 48px; background: #ECFDF5; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fas fa-plug text-success"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Device Aktif</div>
                    <div class="fw-bold text-success fs-4" style="line-height: 1.2;"><?= $active_count ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-6 col-xl-3">
            <div class="p-3 rounded-4 border bg-white d-flex align-items-center gap-3 h-100 shadow-sm" style="transition: transform 0.2s;">
                <div style="width: 48px; height: 48px; background: #EFF6FF; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fas fa-paper-plane text-primary"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Traffic (7 Hari)</div>
                    <div class="fw-bold text-primary fs-4" style="line-height: 1.2;"><?= number_format($stats['total_sent']) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-6 col-xl-3">
            <div class="p-3 rounded-4 border bg-white d-flex align-items-center gap-3 h-100 shadow-sm" style="transition: transform 0.2s;">
                <div style="width: 48px; height: 48px; background: #FEF2F2; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fas fa-check-double text-danger"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Terkirim Sukses</div>
                    <div class="fw-bold text-danger fs-4" style="line-height: 1.2;"><?= number_format($stats['success']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="panel-editorial p-0 overflow-hidden mb-4">
        <div class="p-4 border-bottom bg-white d-flex justify-content-between align-items-center flex-wrap gap-3">
            <h3 class="panel-title m-0"><i class="fas fa-network-wired text-primary me-2"></i> Konfigurasi API</h3>
            <span class="badge-clean bg-light text-dark border"><strong class="text-primary"><?= count($configs) ?></strong> Endpoint Tersimpan</span>
        </div>
        
        <div class="table-responsive">
            <?php if (empty($configs)): ?>
                <div class="text-center py-5">
                    <div style="width: 80px; height: 80px; background: #F3F4F6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                        <i class="fas fa-server text-muted fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Belum Ada Device</h5>
                    <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto;">Kamu belum mengkonfigurasi akun OneSender. Tambahkan API Key untuk mulai mengirim pesan.</p>
                    <a href="create.php" class="btn btn-dark rounded-pill fw-bold px-4">
                        <i class="fas fa-plus me-2"></i>Tambah Device Pertama
                    </a>
                </div>
            <?php else: ?>
                <table class="table-editorial mb-0">
                    <thead>
                        <tr>
                            <th width="25%">Nama Device / Akun</th>
                            <th width="25%">Endpoint URL</th>
                            <th width="15%" class="text-center">API Key</th>
                            <th width="15%" class="text-center">Koneksi</th>
                            <th width="20%" class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($configs as $config): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <?php 
                                        $initial = strtoupper(substr($config['account_name'], 0, 1));
                                        $is_default = ($config['account_name'] === 'default');
                                    ?>
                                    <div style="width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.1rem; <?= $is_default ? 'background: #DBEAFE; color: #1D4ED8;' : 'background: #F3F4F6; color: #4B5563;' ?>">
                                        <?= $initial ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 0.95rem;">
                                            <?= clean($config['account_name']) ?>
                                            <?php if ($is_default): ?>
                                                <span class="ms-1 badge bg-primary text-white" style="font-size: 0.6rem; padding: 2px 6px;">DEFAULT</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.75rem;">ID: #<?= $config['id'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-family: 'Consolas', monospace; font-size: 0.85rem; color: #4B5563; background: #F9FAFB; padding: 4px 8px; border-radius: 6px; border: 1px solid #E5E7EB; display: inline-block; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= clean($config['api_url']) ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if (!empty($config['api_key'])): ?>
                                    <span class="badge-clean" style="background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0;">
                                        <i class="fas fa-check-circle me-1"></i> Terpasang
                                    </span>
                                <?php else: ?>
                                    <span class="badge-clean" style="background: #FEF2F2; color: #EF4444; border: 1px solid #FCA5A5;">
                                        <i class="fas fa-times-circle me-1"></i> Kosong
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div id="status-<?= $config['id'] ?>">
                                    <button type="button" class="btn btn-sm btn-light fw-bold border text-primary rounded-pill px-3 test-connection transition-all" 
                                            data-account="<?= htmlspecialchars($config['account_name']) ?>" 
                                            data-id="<?= $config['id'] ?>">
                                        <i class="fas fa-wifi me-1"></i> Uji
                                    </button>
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="test.php?account=<?= urlencode($config['account_name']) ?>" class="btn-action-icon embed" title="Coba Kirim Pesan">
                                        <i class="fas fa-paper-plane"></i>
                                    </a>
                                    <a href="edit.php?id=<?= $config['id'] ?>" class="btn-action-icon edit" title="Konfigurasi">
                                        <i class="fas fa-cog"></i>
                                    </a>
                                    <?php if (!$is_default): ?>
                                        <button type="button" class="btn-action-icon delete" title="Hapus Device"
                                                onclick="showDeleteModal(<?= $config['id'] ?>, '<?= htmlspecialchars(addslashes($config['account_name'])) ?>')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn-action-icon" disabled style="opacity: 0.3; cursor: not-allowed;" title="Device Default tidak bisa dihapus">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($stats['recent_activity'])): ?>
    <div class="panel-editorial p-0 overflow-hidden mb-5">
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
            <h3 class="panel-title m-0" style="font-size: 1rem;"><i class="fas fa-history text-primary me-2"></i> Log Traffic Terbaru</h3>
            <span class="text-muted" style="font-size: 0.8rem;">(<?= count($stats['recent_activity']) ?> riwayat terakhir)</span>
        </div>
        <div class="table-responsive">
            <table class="table-editorial mb-0">
                <thead>
                    <tr>
                        <th width="15%">Waktu</th>
                        <th width="15%">Device</th>
                        <th width="20%">No. Tujuan</th>
                        <th width="40%">Isi Pesan</th>
                        <th width="10%" class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['recent_activity'] as $activity): ?>
                    <tr>
                        <td>
                            <div class="text-dark fw-bold" style="font-size: 0.85rem;"><?= date('H:i', strtotime($activity['timestamp'])) ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;"><?= date('d/m/y', strtotime($activity['timestamp'])) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-secondary" style="font-size: 0.7rem; font-weight: 600; letter-spacing: 0.05em;"><?= clean($activity['account']) ?></span>
                        </td>
                        <td>
                            <div style="font-family: 'Consolas', monospace; font-size: 0.85rem; font-weight: 600; color: #059669;">
                                <?= clean($activity['to']) ?>
                            </div>
                        </td>
                        <td>
                            <div class="text-muted" style="font-size: 0.8rem; line-height: 1.4; max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($activity['message']) ?>">
                                <?= clean(truncateText($activity['message'], 60)) ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php if ($activity['status'] === 'success'): ?>
                                <i class="fas fa-check-circle text-success fs-5"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle text-danger fs-5" title="<?= htmlspecialchars($activity['error']) ?>" style="cursor: help;"></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" style="transition: transform 200ms cubic-bezier(0.16, 1, 0.3, 1), opacity 200ms cubic-bezier(0.16, 1, 0.3, 1);">
        <div class="modal-content" style="border-radius: 24px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
            <div class="modal-body text-center p-4">
                <div style="width: 64px; height: 64px; background: #FEF2F2; color: #EF4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <i class="fas fa-server" style="font-size: 1.75rem;"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Hapus Device?</h5>
                <p class="text-muted small mb-4" style="line-height: 1.5;">
                    Yakin ingin menghapus koneksi akun <strong id="delAccountName" class="text-dark"></strong>? 
                    Semua antrean pesan yang menggunakan device ini mungkin akan gagal.
                </p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light w-50 fw-bold" data-bs-dismiss="modal" style="border-radius: 12px;">Batal</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger w-50 fw-bold" style="border-radius: 12px; background: #EF4444; border: none;">Hapus</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fungsi panggil Modal Delete
function showDeleteModal(id, accountName) {
    document.getElementById('delAccountName').textContent = accountName;
    document.getElementById('confirmDeleteBtn').href = 'delete.php?id=' + id;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    
    // Feedback Loading saat hapus
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if(confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Proses...';
            this.style.opacity = '0.8';
            this.style.pointerEvents = 'none';
        });
    }

    // UX Cerdas Test Koneksi via AJAX
    document.querySelectorAll('.test-connection').forEach(function(button) {
        button.addEventListener('click', function() {
            const account = this.getAttribute('data-account');
            const id = this.getAttribute('data-id');
            const statusDiv = document.getElementById('status-' + id);
            
            // Set ke state Loading dengan style kapsul
            statusDiv.innerHTML = '<span class="badge-clean bg-warning text-dark border px-3 py-2 rounded-pill"><i class="fas fa-spinner fa-spin me-1"></i> Testing...</span>';
            
            // Eksekusi AJAX
            fetch('test_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'account=' + encodeURIComponent(account)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusDiv.innerHTML = '<span class="badge-clean" style="background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; padding: 6px 12px; border-radius: 50rem;"><i class="fas fa-check-circle me-1"></i> Online</span>';
                } else {
                    statusDiv.innerHTML = '<span class="badge-clean" style="background: #FEF2F2; color: #EF4444; border: 1px solid #FCA5A5; padding: 6px 12px; border-radius: 50rem;" title="' + data.error + '"><i class="fas fa-times-circle me-1"></i> Error</span>';
                }
                
                // Kembalikan ke tombol semula setelah 4 detik
                setTimeout(() => {
                    statusDiv.innerHTML = `<button type="button" class="btn btn-sm btn-light fw-bold border text-primary rounded-pill px-3 test-connection transition-all" data-account="${account}" data-id="${id}"><i class="fas fa-wifi me-1"></i> Uji</button>`;
                    // Pasang ulang listener karena dom elemen di-recreate
                    statusDiv.querySelector('.test-connection').addEventListener('click', arguments.callee);
                }, 4000);
            })
            .catch(error => {
                statusDiv.innerHTML = '<span class="badge-clean" style="background: #FEF2F2; color: #EF4444; border: 1px solid #FCA5A5; padding: 6px 12px; border-radius: 50rem;"><i class="fas fa-exclamation-triangle me-1"></i> Gagal</span>';
                console.error('Test error:', error);
                
                setTimeout(() => {
                    statusDiv.innerHTML = `<button type="button" class="btn btn-sm btn-light fw-bold border text-primary rounded-pill px-3 test-connection transition-all" data-account="${account}" data-id="${id}"><i class="fas fa-wifi me-1"></i> Uji</button>`;
                    statusDiv.querySelector('.test-connection').addEventListener('click', arguments.callee);
                }, 4000);
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>