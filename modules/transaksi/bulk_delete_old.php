<?php
/**
 * Bulk Delete Old Pending Transactions
 * Menghapus transaksi pending yang lebih dari 3 bulan
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

// Pastikan user sudah login
requireAuth();

// Handle POST request untuk hapus
if (isPost()) {
    $confirm = post('confirm');
    
    if ($confirm === 'yes') {
        try {
            // Start transaction untuk memastikan konsistensi
            mysqli_begin_transaction(db());
            
            // 1. Hapus detail transaksi terlebih dulu (foreign key constraint)
            $delete_details_sql = "DELETE dt FROM detail_transaksi dt
                                  JOIN transaksi t ON dt.transaksi_id = t.id
                                  WHERE t.status = 'pending' 
                                  AND t.tanggal_transaksi < DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            
            $details_result = mysqli_query(db(), $delete_details_sql);
            if (!$details_result) {
                throw new Exception("Gagal menghapus detail transaksi: " . mysqli_error(db()));
            }
            
            $details_deleted = mysqli_affected_rows(db());
            
            // 2. Hapus transaksi utama
            $delete_transaksi_sql = "DELETE FROM transaksi 
                                     WHERE status = 'pending' 
                                     AND tanggal_transaksi < DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            
            $transaksi_result = mysqli_query(db(), $delete_transaksi_sql);
            if (!$transaksi_result) {
                throw new Exception("Gagal menghapus transaksi: " . mysqli_error(db()));
            }
            
            $transaksi_deleted = mysqli_affected_rows(db());
            
            // Commit transaction
            mysqli_commit(db());
            
            if ($transaksi_deleted > 0) {
                setMessage("Berhasil menghapus {$transaksi_deleted} transaksi dan {$details_deleted} detail transaksi.", 'success');
            } else {
                setMessage("Tidak ada transaksi pending yang lebih dari 3 bulan.", 'info');
            }
            
        } catch (Exception $e) {
            // Rollback pada error
            mysqli_rollback(db());
            setMessage("System error: " . $e->getMessage(), 'error');
        }
        
        redirect('index.php');
    } else {
        setMessage("Konfirmasi diperlukan untuk menghapus transaksi.", 'error');
        redirect('bulk_delete_old.php');
    }
}

// Get count transaksi yang akan dihapus
$count_sql = "SELECT COUNT(*) as total 
              FROM transaksi t
              JOIN pelanggan p ON t.pelanggan_id = p.id
              WHERE t.status = 'pending' 
              AND t.tanggal_transaksi < DATE_SUB(NOW(), INTERVAL 3 MONTH)";

$count_result = mysqli_query(db(), $count_sql);
$count_data = mysqli_fetch_assoc($count_result);
$total_to_delete = $count_data['total'];

// Get sample data untuk ditampilkan
$sample_sql = "SELECT t.id, t.total_harga, t.tanggal_transaksi, p.nama as nama_pelanggan
               FROM transaksi t
               JOIN pelanggan p ON t.pelanggan_id = p.id
               WHERE t.status = 'pending' 
               AND t.tanggal_transaksi < DATE_SUB(NOW(), INTERVAL 3 MONTH)
               ORDER BY t.tanggal_transaksi ASC
               LIMIT 10";

$sample_result = mysqli_query(db(), $sample_sql);
$sample_data = [];
while ($row = mysqli_fetch_assoc($sample_result)) {
    $sample_data[] = $row;
}

$page_title = "Hapus Transaksi Pending Lama";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content dashboard-wrapper">
    <div class="form-container" style="max-width: 800px; margin: 0 auto;">
        
        <div class="dash-header mb-4 text-center">
            <div class="mb-3">
                <div style="width: 72px; height: 72px; background: #FEF2F2; color: #EF4444; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem;">
                    <i class="fas fa-trash-alt"></i>
                </div>
            </div>
            <h1 class="dash-title">Bersihkan Transaksi Lama</h1>
            <div class="text-muted mt-2 mx-auto" style="font-weight: 500; font-size: 0.95rem; max-width: 500px;">
                Hapus transaksi berstatus <strong>Pending</strong> yang umurnya sudah melebihi 3 bulan untuk meringankan beban database.
            </div>
        </div>

        <?php displaySessionMessage(); ?>
        
        <div class="panel-editorial p-0" style="overflow: hidden;">
            <?php if ($total_to_delete > 0): ?>
                
                <div class="bg-light p-4 border-bottom text-center">
                    <div class="text-dark fw-bold fs-4 mb-1">
                        Ditemukan <span class="text-danger"><?= number_format($total_to_delete) ?></span> Transaksi
                    </div>
                    <div class="text-muted" style="font-size: 0.85rem;">Transaksi ini akan dihapus secara permanen dari sistem.</div>
                </div>

                <?php if (!empty($sample_data)): ?>
                    <div class="p-4">
                        <div class="text-muted fw-bold text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 0.05em;">Cuplikan Data yang Akan Terhapus</div>
                        <div class="table-responsive rounded-3 border">
                            <table class="table-editorial mb-0">
                                <thead style="background: #F9FAFB;">
                                    <tr>
                                        <th width="80">Order ID</th>
                                        <th>Pelanggan</th>
                                        <th class="text-end">Nominal</th>
                                        <th width="150" class="text-end">Umur Transaksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sample_data as $item): ?>
                                    <tr>
                                        <td><span class="text-muted fw-bold" style="font-size: 0.85rem;">#<?= $item['id'] ?></span></td>
                                        <td>
                                            <div class="fw-bold text-dark" style="font-size: 0.9rem;"><?= safeHtml($item['nama_pelanggan']) ?></div>
                                            <div class="text-muted" style="font-size: 0.75rem;"><?= formatDate($item['tanggal_transaksi'], 'd/m/Y') ?></div>
                                        </td>
                                        <td class="text-end fw-bold text-success" style="font-size: 0.9rem;">
                                            <?= formatCurrency($item['total_harga']) ?>
                                        </td>
                                        <td class="text-end">
                                            <?php
                                            $days = (strtotime('now') - strtotime($item['tanggal_transaksi'])) / (60*60*24);
                                            ?>
                                            <span class="badge-clean" style="background: #FEF2F2; color: #EF4444;">
                                                <?= floor($days) ?> hari
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($total_to_delete > 10): ?>
                            <div class="text-center mt-3 text-muted" style="font-size: 0.8rem;">
                                <i class="fas fa-ellipsis-v"></i><br>
                                Menampilkan 10 data terlama dari total <?= number_format($total_to_delete) ?>.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="p-4" style="background: #FAFAFA; border-top: 1px dashed #D1D5DB;">
                    <form method="POST" id="deleteForm" class="d-flex flex-column align-items-center">
                        
                        <label class="toggle-switch w-100 mb-4" style="max-width: 450px; background: white; border-color: #EF4444; cursor: pointer;">
                            <div class="text-start pe-3">
                                <div class="toggle-label text-danger">Konfirmasi Penghapusan</div>
                                <div class="toggle-desc" style="line-height: 1.4;">Saya mengerti bahwa aksi ini tidak bisa dibatalkan dan detail transaksi akan ikut hilang.</div>
                            </div>
                            <input type="checkbox" id="confirmCheck" class="switch-input" required>
                            <div class="switch-slider" style="background-color: #FCA5A5;"></div>
                        </label>
                        
                        <div class="d-flex flex-column flex-sm-row gap-2 w-100" style="max-width: 450px;">
                            <button type="submit" name="confirm" value="yes" class="btn btn-danger flex-grow-1 fw-bold" id="deleteBtn" disabled style="padding: 0.85rem; border-radius: 12px; transition: all 0.2s;">
                                Hapus Standard
                            </button>
                            <?php if(isset($deleteAjaxBtn)): /* Menjaga kompatibilitas jika var di set di PHP */ ?>
                            <button type="button" class="btn btn-dark flex-grow-1 fw-bold" id="deleteAjaxBtn" disabled style="padding: 0.85rem; border-radius: 12px; transition: all 0.2s;">
                                <i class="fas fa-bolt text-warning me-1"></i> Fast Delete
                            </button>
                            <?php endif; ?>
                        </div>
                        
                        <a href="index.php" class="text-muted mt-4 text-decoration-none fw-bold hover-text-dark" style="font-size: 0.85rem; transition: color 0.2s;">
                            Batalkan dan Kembali
                        </a>
                    </form>
                </div>

            <?php else: ?>
                <div class="p-5 text-center">
                    <div style="width: 80px; height: 80px; background: #ECFDF5; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                        <i class="fas fa-check-circle text-success fs-2"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Database Bersih</h4>
                    <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto;">
                        Tidak ada transaksi pending yang melebihi batas waktu 3 bulan. Sistem kamu berjalan dengan optimal.
                    </p>
                    <a href="index.php" class="btn btn-dark rounded-pill px-4 fw-bold">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Transaksi
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* CSS khusus untuk mewarnai slider toggle bahaya */
#confirmCheck:checked + .switch-slider { background-color: #EF4444 !important; }
.hover-text-dark:hover { color: #111827 !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmCheck = document.getElementById('confirmCheck');
    const deleteBtn = document.getElementById('deleteBtn');
    const deleteAjaxBtn = document.getElementById('deleteAjaxBtn');

    if (confirmCheck && deleteBtn) {
        // Toggle Buttons Logic
        confirmCheck.addEventListener('change', function() {
            const isChecked = this.checked;
            
            // Visual Update for Submit Button
            deleteBtn.disabled = !isChecked;
            if (isChecked) {
                deleteBtn.style.opacity = '1';
                deleteBtn.style.transform = 'translateY(-2px)';
                deleteBtn.style.boxShadow = '0 8px 15px rgba(239, 68, 68, 0.2)';
            } else {
                deleteBtn.style.opacity = '0.5';
                deleteBtn.style.transform = 'none';
                deleteBtn.style.boxShadow = 'none';
            }

            // Visual Update for Fast Delete Button (If exists)
            if (deleteAjaxBtn) {
                deleteAjaxBtn.disabled = !isChecked;
                if (isChecked) {
                    deleteAjaxBtn.style.opacity = '1';
                    deleteAjaxBtn.style.transform = 'translateY(-2px)';
                    deleteAjaxBtn.style.boxShadow = '0 8px 15px rgba(17, 24, 39, 0.2)';
                } else {
                    deleteAjaxBtn.style.opacity = '0.5';
                    deleteAjaxBtn.style.transform = 'none';
                    deleteAjaxBtn.style.boxShadow = 'none';
                }
            }
        });
        
        // Setup initial disabled state visual
        deleteBtn.style.opacity = '0.5';
        if(deleteAjaxBtn) deleteAjaxBtn.style.opacity = '0.5';

        // Form Standard Submit Handler
        const form = document.getElementById('deleteForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!confirmCheck.checked) {
                    e.preventDefault();
                    return false;
                }

                // FIX: Suntikkan input rahasia agar PHP tetap membaca "confirm=yes" 
                // meskipun tombol aslinya kita matikan untuk animasi.
                const hiddenConfirm = document.createElement('input');
                hiddenConfirm.type = 'hidden';
                hiddenConfirm.name = 'confirm';
                hiddenConfirm.value = 'yes';
                form.appendChild(hiddenConfirm);
                
                // Animasi Loading
                deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Membersihkan Database...';
                deleteBtn.disabled = true;
                if(deleteAjaxBtn) deleteAjaxBtn.style.display = 'none'; // Sembunyikan tombol lain
                
                // Cegah double submit untuk input lain
                const formElements = form.querySelectorAll('input');
                formElements.forEach(element => {
                    // Jangan disable checkbox dan input hidden yang baru dibuat
                    if(element.type !== 'checkbox' && element.type !== 'hidden') element.disabled = true;
                });
            });
        }

        // AJAX Fast Delete Handler
        if (deleteAjaxBtn) {
            deleteAjaxBtn.addEventListener('click', function() {
                if (!confirmCheck.checked) return;
                
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin text-warning me-2"></i>Menghapus...';
                this.disabled = true;
                deleteBtn.style.display = 'none'; // Sembunyikan tombol standard
                
                fetch('bulk_delete_process.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'confirm=yes'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const panel = document.querySelector('.panel-editorial');
                        panel.innerHTML = `
                            <div class="p-5 text-center">
                                <div style="width: 80px; height: 80px; background: #ECFDF5; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                                    <i class="fas fa-check-circle text-success fs-2"></i>
                                </div>
                                <h4 class="fw-bold text-dark mb-2">Sukses Dibersihkan</h4>
                                <p class="text-muted mb-4 mx-auto" style="max-width: 400px;">${data.message}</p>
                            </div>
                        `;
                        setTimeout(() => window.location.href = 'index.php', 2000);
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    alert('Gagal: ' + error.message);
                    this.innerHTML = originalText;
                    this.disabled = false;
                    deleteBtn.style.display = 'block';
                });
            });
        }
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>