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

<div class="main-content">
    <div class="top-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title mb-0">Bulk Import Transaksi</h1>
                <nav class="breadcrumb">
                    <a href="<?= BASE_URL ?>" class="breadcrumb-item text-decoration-none">Dashboard</a>
                    <a href="index.php" class="breadcrumb-item text-decoration-none">Transaksi</a>
                    <span class="breadcrumb-item active">Bulk Import</span>
                </nav>
            </div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
        </div>
    </div>

    <div class="content-area">
        <?php displaySessionMessage(); ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h6><i class="fas fa-exclamation-triangle me-2"></i>Error</h6>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= safeHtml($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($import_result): ?>
            <div class="alert alert-info">
                <h5><i class="fas fa-chart-bar me-2"></i>Hasil Import</h5>
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="h3 text-success"><?= $import_result['success_count'] ?></div>
                        <small>Berhasil</small>
                    </div>
                    <div class="col-md-4">
                        <div class="h3 text-danger"><?= $import_result['failed_count'] ?></div>
                        <small>Gagal</small>
                    </div>
                    <div class="col-md-4">
                        <div class="h3 text-info"><?= $import_result['total_processed'] ?></div>
                        <small>Total</small>
                    </div>
                </div>
                
                <?php if (!empty($import_result['errors'])): ?>
                    <details class="mt-3">
                        <summary class="text-danger fw-bold">Detail Error (<?= count($import_result['errors']) ?>)</summary>
                        <div class="mt-2 p-3 bg-light rounded small">
                            <?php foreach ($import_result['errors'] as $error): ?>
                                <div class="text-danger mb-1">• <?= safeHtml($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endif; ?>
                
                <?php if ($import_result['success_count'] > 0): ?>
                    <div class="mt-3">
                        <a href="index.php" class="btn btn-success">
                            <i class="fas fa-list me-2"></i>Lihat Transaksi
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Form Import -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="textarea-tab" data-bs-toggle="tab" href="#textarea" role="tab">
                                    <i class="fas fa-keyboard me-2"></i>Input Manual
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="file-tab" data-bs-toggle="tab" href="#file" role="tab">
                                    <i class="fas fa-file-csv me-2"></i>Upload File
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="tab-content">
                                <!-- Input Manual Tab -->
                                <div class="tab-pane fade show active" id="textarea" role="tabpanel">
                                    <div class="mb-3">
                                        <label for="csv_data" class="form-label">Data CSV</label>
                                        <textarea class="form-control" id="csv_data" name="csv_data" rows="12" 
                                                  placeholder="Nama,Nomor WA,Produk,Tanggal,Status&#10;John Doe,08123456789,Course Premium,2024-12-01,selesai&#10;Jane Smith,081987654321,Ebook Digital,,pending"><?= safeHtml(post('csv_data', '')) ?></textarea>
                                        <small class="text-muted">Format: Nama,Nomor WA,Produk,Tanggal (opsional),Status (opsional)</small>
                                    </div>
                                </div>
                                
                                <!-- Upload File Tab -->
                                <div class="tab-pane fade" id="file" role="tabpanel">
                                    <div class="mb-3">
                                        <label for="csv_file" class="form-label">Upload File CSV</label>
                                        <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv,.txt">
                                        <small class="text-muted">File CSV dengan format yang sama seperti input manual</small>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <strong>Download Template:</strong> 
                                        <a href="data:text/csv;charset=utf-8,Nama%2CNomor%20WA%2CProduk%2CTanggal%2CStatus%0AJohn%20Doe%2C08123456789%2CCourse%20Premium%2C2024-12-01%2Cselesai%0AJane%20Smith%2C081987654321%2CEbook%20Digital%2C%2Cpending" 
                                           class="alert-link" download="template-transaksi.csv">template-transaksi.csv</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="index.php" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-upload me-2"></i>Import Sekarang
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Instructions -->
            <div class="col-lg-4">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Panduan Import</h6>
                    </div>
                    <div class="card-body">
                        <h6>Format CSV:</h6>
                        <div class="bg-light p-2 rounded mb-3 small">
                            <code>Nama,Nomor WA,Produk,Tanggal,Status</code>
                        </div>
                        
                        <h6>Contoh Data:</h6>
                        <div class="bg-light p-2 rounded mb-3 small">
                            John Doe,08123456789,Course Premium,2024-12-01,selesai<br>
                            Jane Smith,081987654321,Ebook Digital,,pending
                        </div>
                        
                        <h6>Ketentuan:</h6>
                        <ul class="small">
                            <li><strong>Nama:</strong> Wajib diisi</li>
                            <li><strong>Nomor WA:</strong> Format 08xxx atau 62xxx</li>
                            <li><strong>Produk:</strong> Akan dicari berdasarkan nama (partial match)</li>
                            <li><strong>Tanggal:</strong> Format YYYY-MM-DD (opsional)</li>
                            <li><strong>Status:</strong> pending, diproses, selesai, batal (opsional)</li>
                        </ul>
                        
                        <h6>Fitur Otomatis:</h6>
                        <ul class="small">
                            <li>✅ Pelanggan baru otomatis terdaftar</li>
                            <li>✅ Nomor WA dinormalisasi ke format 62xxx</li>
                            <li>✅ Pencarian produk dengan partial match</li>
                            <li>✅ Default tanggal = sekarang</li>
                            <li>✅ Default status = pending</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Preview -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-eye me-2"></i>Preview</h6>
                    </div>
                    <div class="card-body">
                        <div id="preview-container">
                            <p class="text-muted text-center">Ketik data untuk melihat preview...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csvTextarea = document.getElementById('csv_data');
    const previewContainer = document.getElementById('preview-container');
    const csvFile = document.getElementById('csv_file');
    
    function updatePreview() {
        const csvData = csvTextarea.value.trim();
        if (!csvData) {
            previewContainer.innerHTML = '<p class="text-muted text-center">Ketik data untuk melihat preview...</p>';
            return;
        }
        
        const lines = csvData.split('\n').filter(line => line.trim());
        let validCount = 0;
        let errorCount = 0;
        
        let html = '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr>';
        html += '<th style="font-size:0.7rem">Nama</th><th style="font-size:0.7rem">WA</th><th style="font-size:0.7rem">Produk</th><th style="font-size:0.7rem">Status</th></tr></thead><tbody>';
        
        lines.forEach((line, index) => {
            const columns = line.split(',').map(col => col.trim());
            
            if (columns.length >= 3) {
                const [nama, wa, produk, tanggal, status] = columns;
                const isValid = nama && wa && produk;
                
                if (isValid) validCount++;
                else errorCount++;
                
                const rowClass = isValid ? '' : 'table-danger';
                const statusValue = status || 'pending';
                
                html += `<tr class="${rowClass}">
                    <td style="font-size:0.7rem">${nama || '<span class="text-danger">❌</span>'}</td>
                    <td style="font-size:0.7rem">${wa || '<span class="text-danger">❌</span>'}</td>
                    <td style="font-size:0.7rem">${produk || '<span class="text-danger">❌</span>'}</td>
                    <td style="font-size:0.7rem"><span class="badge bg-secondary" style="font-size:0.6rem">${statusValue}</span></td>
                </tr>`;
            } else {
                errorCount++;
                html += `<tr class="table-danger"><td colspan="4" style="font-size:0.7rem">Baris ${index + 1}: Format tidak lengkap</td></tr>`;
            }
        });
        
        html += '</tbody></table></div>';
        html += `<div class="text-center mt-2">
            <span class="badge bg-success">${validCount} valid</span>
            <span class="badge bg-danger">${errorCount} error</span>
            <span class="badge bg-info">${lines.length} total</span>
        </div>`;
        
        previewContainer.innerHTML = html;
    }
    
    csvTextarea.addEventListener('input', updatePreview);
    
    // File upload handler
    csvFile.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                csvTextarea.value = e.target.result;
                updatePreview();
                // Switch to manual tab to show preview
                document.getElementById('textarea-tab').click();
            };
            reader.readAsText(file);
        }
    });
    
    // Initial preview
    updatePreview();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>