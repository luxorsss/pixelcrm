<?php
$page_title = "Bulk Import Transaksi";
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

$import_result = null;
$errors = [];

if (isPost()) {
    if (isset($_POST['csv_data']) && !empty(trim($_POST['csv_data']))) {
        // Process CSV data from textarea
        $csv_data = trim($_POST['csv_data']);
        $transaksi_array = parseCSVTransaksi($csv_data);
        
        if (empty($transaksi_array)) {
            $errors[] = 'Data CSV tidak valid atau kosong';
        } else {
            $import_result = bulkCreateTransaksi($transaksi_array);
        }
    } elseif (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        // Process uploaded CSV file
        $file_content = file_get_contents($_FILES['csv_file']['tmp_name']);
        $transaksi_array = parseCSVTransaksi($file_content);
        
        if (empty($transaksi_array)) {
            $errors[] = 'File CSV tidak valid atau kosong';
        } else {
            $import_result = bulkCreateTransaksi($transaksi_array);
        }
    } else {
        $errors[] = 'Silakan masukkan data CSV atau upload file CSV';
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content dashboard-wrapper">
    <div class="form-container" style="max-width: 1200px;">
        
        <div class="dash-header mb-4">
            <div>
                <a href="index.php" class="text-muted text-decoration-none fw-bold" style="font-size: 0.85rem;">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Transaksi
                </a>
                <h1 class="dash-title mt-2">Bulk Import Transaksi</h1>
            </div>
        </div>

        <?php displaySessionMessage(); ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-editorial mb-4" style="border-left-color: var(--danger-color); background: #FEF2F2;">
                <h6 class="fw-bold text-danger mb-2"><i class="fas fa-exclamation-triangle me-2"></i>Ditemukan Error saat Import:</h6>
                <ul class="mb-0 text-danger" style="font-size: 0.85rem; padding-left: 1.25rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= safeHtml($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($import_result): ?>
            <div class="panel-editorial mb-4" style="background: #EFF6FF; border-color: #BFDBFE;">
                <h3 class="panel-title text-primary"><i class="fas fa-chart-pie me-2"></i>Laporan Hasil Import</h3>
                
                <div class="row text-center g-3 mt-3">
                    <div class="col-4">
                        <div class="p-3 bg-white rounded-4 border">
                            <div class="fw-extrabold text-success mb-1" style="font-size: 2rem; line-height: 1;"><?= $import_result['success_count'] ?></div>
                            <div class="text-muted fw-bold" style="font-size: 0.75rem; text-transform: uppercase;">Berhasil</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-white rounded-4 border">
                            <div class="fw-extrabold text-danger mb-1" style="font-size: 2rem; line-height: 1;"><?= $import_result['failed_count'] ?></div>
                            <div class="text-muted fw-bold" style="font-size: 0.75rem; text-transform: uppercase;">Gagal</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-white rounded-4 border">
                            <div class="fw-extrabold text-primary mb-1" style="font-size: 2rem; line-height: 1;"><?= $import_result['total_processed'] ?></div>
                            <div class="text-muted fw-bold" style="font-size: 0.75rem; text-transform: uppercase;">Total Diproses</div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($import_result['errors'])): ?>
                    <details class="mt-4 bg-white p-3 rounded-3 border">
                        <summary class="text-danger fw-bold" style="cursor: pointer; outline: none;">Tampilkan Detail Error (<?= count($import_result['errors']) ?> baris)</summary>
                        <div class="mt-3 text-danger" style="font-size: 0.85rem; font-family: monospace;">
                            <?php foreach ($import_result['errors'] as $error): ?>
                                <div class="mb-1 border-bottom pb-1 border-danger" style="border-opacity: 0.2;">• <?= safeHtml($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endif; ?>
                
                <?php if ($import_result['success_count'] > 0): ?>
                    <div class="mt-4 text-end">
                        <a href="index.php" class="btn btn-dark fw-bold rounded-pill px-4">
                            <i class="fas fa-list me-2"></i>Lihat Transaksi Masuk
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="importForm">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="panel-editorial p-0 overflow-hidden mb-4">
                        <div class="p-4 bg-white border-bottom d-flex justify-content-between align-items-center">
                            <h3 class="panel-title m-0"><i class="fas fa-keyboard text-primary"></i> Data Editor</h3>
                            
                            <div class="position-relative">
                                <input type="file" class="form-control position-absolute" id="csv_file" name="csv_file" accept=".csv,.txt" style="opacity: 0; width: 100%; height: 100%; cursor: pointer; z-index: 2;">
                                <button type="button" class="btn btn-light border fw-bold text-dark btn-sm rounded-pill px-3" style="pointer-events: none;">
                                    <i class="fas fa-upload me-1 text-primary"></i> Upload CSV
                                </button>
                            </div>
                        </div>
                        
                        <div class="p-4 bg-light">
                            <textarea class="form-control-editorial fw-bold" id="csv_data" name="csv_data" rows="12" 
                                      placeholder="Nama,Nomor WA,Produk,Tanggal,Status&#10;John Doe,08123456789,Course Premium,2024-12-01,selesai&#10;Jane Smith,081987654321,Ebook Digital,,pending"
                                      style="font-family: monospace; font-size: 0.85rem; line-height: 1.6; resize: vertical; border: 1px solid #D1D5DB; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);"><?= safeHtml(post('csv_data', '')) ?></textarea>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="text-muted" style="font-size: 0.75rem;">Pisahkan kolom dengan koma (,). Jangan gunakan spasi setelah koma.</div>
                                <a href="data:text/csv;charset=utf-8,Nama%2CNomor%20WA%2CProduk%2CTanggal%2CStatus%0AJohn%20Doe%2C08123456789%2CCourse%20Premium%2C2024-12-01%2Cselesai%0AJane%20Smith%2C081987654321%2CEbook%20Digital%2C%2Cpending" 
                                   class="text-primary text-decoration-none fw-bold" style="font-size: 0.8rem;" download="template-transaksi.csv">
                                   <i class="fas fa-download me-1"></i> Download Template
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="panel-editorial p-4" style="background: #FAFAFA;">
                        <h3 class="panel-title mb-3" style="font-size: 1rem;"><i class="fas fa-book-open text-warning"></i> Panduan Format</h3>
                        <div class="row g-3 text-muted" style="font-size: 0.8rem;">
                            <div class="col-sm-6">
                                <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                                    <li><strong class="text-dark">Nama:</strong> Wajib diisi</li>
                                    <li><strong class="text-dark">Nomor WA:</strong> Format 08xxx atau 62xxx</li>
                                    <li><strong class="text-dark">Produk:</strong> Tulis sebagian/penuh nama produk</li>
                                </ul>
                            </div>
                            <div class="col-sm-6">
                                <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                                    <li><strong class="text-dark">Tanggal:</strong> YYYY-MM-DD (Kosongkan = Hari ini)</li>
                                    <li><strong class="text-dark">Status:</strong> pending, diproses, selesai, batal</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="panel-editorial sticky-top p-0 overflow-hidden" style="top: 2rem; border-color: #D1D5DB;">
                        <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
                            <h3 class="panel-title m-0" style="font-size: 1rem;"><i class="fas fa-eye text-success"></i> Live Preview</h3>
                            <div id="preview-stats" class="d-flex gap-1"></div>
                        </div>
                        
                        <div class="bg-light" style="max-height: 400px; overflow-y: auto;">
                            <div id="preview-container" class="p-3">
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-magic fa-2x mb-2 opacity-50"></i>
                                    <div style="font-size: 0.85rem;">Ketik data CSV di samping untuk merender tabel.</div>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-white border-top">
                            <button type="submit" class="btn-submit w-100 mb-2">
                                <i class="fas fa-cloud-upload-alt me-2"></i> Jalankan Import
                            </button>
                            <a href="index.php" class="btn-cancel w-100 d-block text-center">Batal</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
/* Tabel Preview Micro-CSS */
.preview-table { width: 100%; border-collapse: separate; border-spacing: 0 4px; }
.preview-table th { font-size: 0.7rem; text-transform: uppercase; color: #6B7280; padding: 0 8px 4px; font-weight: 700; }
.preview-table td { font-size: 0.8rem; background: white; padding: 8px; border-top: 1px solid #E5E7EB; border-bottom: 1px solid #E5E7EB; }
.preview-table td:first-child { border-left: 1px solid #E5E7EB; border-radius: 6px 0 0 6px; }
.preview-table td:last-child { border-right: 1px solid #E5E7EB; border-radius: 0 6px 6px 0; }
.preview-table tr.error td { background: #FEF2F2; border-color: #FCA5A5; color: #EF4444; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csvTextarea = document.getElementById('csv_data');
    const previewContainer = document.getElementById('preview-container');
    const previewStats = document.getElementById('preview-stats');
    const csvFile = document.getElementById('csv_file');
    
    function updatePreview() {
        const csvData = csvTextarea.value.trim();
        if (!csvData) {
            previewContainer.innerHTML = `
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-magic fa-2x mb-2 opacity-50"></i>
                    <div style="font-size: 0.85rem;">Ketik data CSV di samping untuk merender tabel.</div>
                </div>`;
            previewStats.innerHTML = '';
            return;
        }
        
        const lines = csvData.split('\n').filter(line => line.trim());
        let validCount = 0;
        let errorCount = 0;
        
        let html = '<table class="preview-table"><thead><tr>';
        html += '<th>Nama</th><th>WhatsApp</th><th>Produk</th><th class="text-center">Status</th></tr></thead><tbody>';
        
        lines.forEach((line, index) => {
            const columns = line.split(',').map(col => col.trim());
            
            if (columns.length >= 3) {
                const [nama, wa, produk, tanggal, status] = columns;
                const isValid = nama && wa && produk;
                
                if (isValid) validCount++;
                else errorCount++;
                
                const rowClass = isValid ? '' : 'error';
                const statusValue = status || 'pending';
                
                html += `<tr class="${rowClass}">
                    <td class="fw-bold">${nama || '<i class="fas fa-times-circle text-danger"></i>'}</td>
                    <td>${wa || '<i class="fas fa-times-circle text-danger"></i>'}</td>
                    <td>${produk || '<i class="fas fa-times-circle text-danger"></i>'}</td>
                    <td class="text-center"><span class="badge-clean bg-light text-muted border" style="font-size:0.65rem; padding:0.15rem 0.4rem;">${statusValue}</span></td>
                </tr>`;
            } else {
                errorCount++;
                html += `<tr class="error"><td colspan="4" class="text-center fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> Baris ${index + 1}: Format tidak lengkap</td></tr>`;
            }
        });
        
        html += '</tbody></table>';
        
        previewStats.innerHTML = `
            <span class="badge-clean bg-success text-white" style="font-size:0.65rem; padding: 0.2rem 0.5rem;">${validCount} OK</span>
            ${errorCount > 0 ? `<span class="badge-clean bg-danger text-white" style="font-size:0.65rem; padding: 0.2rem 0.5rem;">${errorCount} Err</span>` : ''}
        `;
        
        previewContainer.innerHTML = html;
    }
    
    csvTextarea.addEventListener('input', updatePreview);
    
    // File upload handler yang menyatu mulus
    csvFile.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const btn = this.nextElementSibling;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1 text-primary"></i> Membaca...';
            
            const reader = new FileReader();
            reader.onload = function(e) {
                csvTextarea.value = e.target.result;
                updatePreview();
                setTimeout(() => {
                    btn.innerHTML = '<i class="fas fa-check text-success me-1"></i> Berhasil';
                    setTimeout(() => { btn.innerHTML = '<i class="fas fa-upload me-1 text-primary"></i> Upload Ulang'; }, 2000);
                }, 500);
            };
            reader.readAsText(file);
        }
    });
    
    // Feedback saat submit
    document.getElementById('importForm').addEventListener('submit', function() {
        const btn = this.querySelector('.btn-submit');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengimpor...';
        btn.style.opacity = '0.8';
        btn.style.pointerEvents = 'none';
    });

    updatePreview();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>