<?php
// ===============================
// LOGIC SECTION
// ===============================
$page_title = "Bulk Import Pelanggan";
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

$import_result = null;
$errors = [];

if (isPost()) {
    if (isset($_POST['csv_data']) && !empty(trim($_POST['csv_data']))) {
        // Process CSV data from textarea
        $csv_data = trim($_POST['csv_data']);
        $pelanggan_array = parseCSVData($csv_data);
        
        if (empty($pelanggan_array)) {
            $errors[] = 'Data CSV tidak valid atau kosong';
        } else {
            $import_result = bulkCreatePelanggan($pelanggan_array);
        }
    } elseif (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        // Process uploaded CSV file
        $file_content = file_get_contents($_FILES['csv_file']['tmp_name']);
        $pelanggan_array = parseCSVData($file_content);
        
        if (empty($pelanggan_array)) {
            $errors[] = 'File CSV tidak valid atau kosong';
        } else {
            $import_result = bulkCreatePelanggan($pelanggan_array);
        }
    } else {
        $errors[] = 'Silakan masukkan data CSV atau upload file CSV';
    }
}

// ===============================
// PRESENTATION SECTION
// ===============================
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content">
    <!-- Top Header -->
    <div class="top-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title mb-0">Bulk Import Pelanggan</h1>
                <nav class="breadcrumb">
                    <a href="<?= BASE_URL ?>" class="breadcrumb-item text-decoration-none">Dashboard</a>
                    <a href="index.php" class="breadcrumb-item text-decoration-none">Pelanggan</a>
                    <span class="breadcrumb-item active">Bulk Import</span>
                </nav>
            </div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($import_result): ?>
            <div class="alert alert-info">
                <h5><i class="fas fa-chart-bar me-2"></i>Hasil Import</h5>
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="h4 text-success"><?= $import_result['success_count'] ?></div>
                            <small>Berhasil</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="h4 text-warning"><?= $import_result['duplicate_count'] ?></div>
                            <small>Duplikat</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="h4 text-danger"><?= $import_result['failed_count'] ?></div>
                            <small>Gagal</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="h4 text-info"><?= $import_result['total_processed'] ?></div>
                            <small>Total</small>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($import_result['errors'])): ?>
                    <details class="mt-3">
                        <summary class="text-danger fw-bold">Detail Error (<?= count($import_result['errors']) ?>)</summary>
                        <ul class="mt-2 mb-0">
                            <?php foreach ($import_result['errors'] as $error): ?>
                                <li class="text-danger"><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endif; ?>
                
                <?php if ($import_result['success_count'] > 0): ?>
                    <div class="mt-3">
                        <a href="index.php" class="btn btn-success">
                            <i class="fas fa-users me-2"></i>Lihat Daftar Pelanggan
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
                        <ul class="nav nav-tabs card-header-tabs" id="importTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="textarea-tab" data-bs-toggle="tab" href="#textarea" role="tab">
                                    <i class="fas fa-keyboard me-2"></i>Input Manual
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="file-tab" data-bs-toggle="tab" href="#file" role="tab">
                                    <i class="fas fa-file-csv me-2"></i>Upload File CSV
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="tab-content" id="importTabContent">
                                <!-- Input Manual Tab -->
                                <div class="tab-pane fade show active" id="textarea" role="tabpanel">
                                    <div class="mb-3">
                                        <label for="csv_data" class="form-label">Data Pelanggan (Format CSV)</label>
                                        <textarea class="form-control" id="csv_data" name="csv_data" rows="10" 
                                                  placeholder="Nama,Nomor WA&#10;John Doe,08123456789&#10;Jane Smith,081987654321&#10;Ahmad Rahman,08111222333"><?= safeHtml(post('csv_data', '')) ?></textarea>
                                        <small class="form-text text-muted">
                                            Format: Nama,Nomor WA (satu baris per pelanggan)
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- Upload File Tab -->
                                <div class="tab-pane fade" id="file" role="tabpanel">
                                    <div class="mb-3">
                                        <label for="csv_file" class="form-label">Upload File CSV</label>
                                        <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv,.txt">
                                        <small class="form-text text-muted">
                                            File harus berformat CSV dengan kolom: Nama,Nomor WA
                                        </small>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Template CSV:</strong> 
                                        <a href="data:text/csv;charset=utf-8,Nama%2CNomor%20WA%0AJohn%20Doe%2C08123456789%0AJane%20Smith%2C081987654321%0AAhmad%20Rahman%2C08111222333" 
                                           class="alert-link" download="template-pelanggan.csv">Download template CSV</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="index.php" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-upload me-2"></i>Import Data
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Instructions Panel -->
            <div class="col-lg-4">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-question-circle me-2"></i>Cara Penggunaan</h6>
                    </div>
                    <div class="card-body">
                        <h6>Format Data CSV:</h6>
                        <div class="bg-light p-3 rounded mb-3">
                            <code>
                                Nama,Nomor WA<br>
                                John Doe,08123456789<br>
                                Jane Smith,081987654321<br>
                                Ahmad Rahman,08111222333
                            </code>
                        </div>
                        
                        <h6>Ketentuan:</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Pisahkan nama dan nomor WA dengan koma (,)
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Satu baris untuk satu pelanggan
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Nomor WA otomatis dinormalisasi ke format 62xxx
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Duplikasi nomor WA akan diabaikan
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                Data yang error akan ditampilkan di hasil import
                            </li>
                        </ul>
                        
                        <hr>
                        
                        <h6>Contoh Nomor WA Valid:</h6>
                        <ul class="list-unstyled text-muted">
                            <li>08123456789</li>
                            <li>628123456789</li>
                            <li>+628123456789</li>
                            <li>0812-3456-789</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Live Preview -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-eye me-2"></i>Live Preview</h6>
                    </div>
                    <div class="card-body">
                        <div id="preview-container">
                            <p class="text-muted">Preview akan muncul saat Anda mengetik...</p>
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
    
    function normalizePhoneNumber(phone) {
        phone = phone.replace(/[^0-9]/g, '');
        if (phone.startsWith('0')) {
            phone = '62' + phone.substring(1);
        } else if (!phone.startsWith('62')) {
            phone = '62' + phone;
        }
        return phone;
    }
    
    function updatePreview() {
        const csvData = csvTextarea.value.trim();
        if (!csvData) {
            previewContainer.innerHTML = '<p class="text-muted">Preview akan muncul saat Anda mengetik...</p>';
            return;
        }
        
        const lines = csvData.split('\n');
        let html = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Nama</th><th>Nomor WA</th><th>Status</th></tr></thead><tbody>';
        
        lines.forEach((line, index) => {
            line = line.trim();
            if (!line) return;
            
            const columns = line.split(',');
            if (columns.length >= 2) {
                const nama = columns[0].trim();
                const nomor = columns[1].trim();
                const normalizedNomor = normalizePhoneNumber(nomor);
                const isValid = /^62[0-9]{8,11}$/.test(normalizedNomor);
                
                html += `<tr>
                    <td>${nama || '<span class="text-danger">Kosong</span>'}</td>
                    <td>${normalizedNomor}</td>
                    <td>
                        ${isValid && nama ? 
                            '<span class="badge bg-success">Valid</span>' : 
                            '<span class="badge bg-danger">Invalid</span>'
                        }
                    </td>
                </tr>`;
            } else {
                html += `<tr>
                    <td colspan="3" class="text-danger">Baris ${index + 1}: Format tidak valid</td>
                </tr>`;
            }
        });
        
        html += '</tbody></table></div>';
        previewContainer.innerHTML = html;
    }
    
    csvTextarea.addEventListener('input', updatePreview);
    
    // File upload preview
    document.getElementById('csv_file').addEventListener('change', function(e) {
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
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>