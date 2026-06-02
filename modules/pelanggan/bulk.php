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
<div class="main-content dashboard-wrapper flex-grow-1">
    <div class="form-container" style="max-width: 1200px;">
        
        <div class="dash-header mb-4">
            <a href="index.php" class="text-muted text-decoration-none fw-bold mb-2 d-inline-block" style="font-size: 0.85rem;">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Database
            </a>
            <h1 class="dash-title d-flex align-items-center gap-2">
                <i class="fas fa-file-import text-success"></i> Bulk Import Pelanggan
            </h1>
            <p class="text-muted mt-1 fw-medium" style="font-size: 0.95rem;">
                Tambahkan banyak pelanggan sekaligus menggunakan file CSV atau teks.
            </p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-editorial mb-4" style="border-left-color: var(--danger-color); background: #FEF2F2;">
                <ul class="mb-0 text-danger fw-bold" style="font-size: 0.9rem; list-style-type: none; padding-left: 0;">
                    <?php foreach ($errors as $error): ?>
                        <li><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($import_result): ?>
            <div class="panel-editorial mb-4 p-4 border border-info" style="background: #F0F9FF;">
                <h4 class="fw-bold text-dark mb-4"><i class="fas fa-clipboard-check text-info me-2"></i> Laporan Eksekusi</h4>
                
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-white border rounded-3 text-center h-100">
                            <div class="fw-bold text-muted mb-1" style="font-size: 0.75rem; text-transform: uppercase;">Berhasil</div>
                            <div class="fw-bold text-success" style="font-size: 1.75rem; line-height: 1;"><?= $import_result['success_count'] ?></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-white border rounded-3 text-center h-100">
                            <div class="fw-bold text-muted mb-1" style="font-size: 0.75rem; text-transform: uppercase;">Duplikat</div>
                            <div class="fw-bold text-warning" style="font-size: 1.75rem; line-height: 1;"><?= $import_result['duplicate_count'] ?></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-white border rounded-3 text-center h-100">
                            <div class="fw-bold text-muted mb-1" style="font-size: 0.75rem; text-transform: uppercase;">Gagal</div>
                            <div class="fw-bold text-danger" style="font-size: 1.75rem; line-height: 1;"><?= $import_result['failed_count'] ?></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-white border rounded-3 text-center h-100">
                            <div class="fw-bold text-muted mb-1" style="font-size: 0.75rem; text-transform: uppercase;">Total Diproses</div>
                            <div class="fw-bold text-info" style="font-size: 1.75rem; line-height: 1;"><?= $import_result['total_processed'] ?></div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($import_result['errors'])): ?>
                    <div class="bg-white border rounded-3 p-3 mb-3">
                        <h6 class="fw-bold text-danger mb-2" style="font-size: 0.85rem;"><i class="fas fa-exclamation-triangle me-1"></i> Log Error (<?= count($import_result['errors']) ?>)</h6>
                        <ul class="mb-0 text-muted" style="font-size: 0.8rem; max-height: 150px; overflow-y: auto;">
                            <?php foreach ($import_result['errors'] as $error): ?>
                                <li class="mb-1"><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($import_result['success_count'] > 0): ?>
                    <a href="index.php" class="btn btn-success fw-bold rounded-pill px-4">
                        <i class="fas fa-users me-2"></i> Lihat Database
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <div class="col-lg-7 col-xl-8 d-flex flex-column gap-4">
                
                <div class="panel-editorial p-0 overflow-hidden h-100">
                    
                    <div class="d-flex bg-light border-bottom p-2 gap-2" role="tablist">
                        <button class="btn fw-bold flex-grow-1 active nav-link-custom" id="tab-file" data-bs-toggle="tab" data-bs-target="#panel-file" role="tab" style="border-radius: 12px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05); color: #111827;">
                            <i class="fas fa-cloud-upload-alt text-primary me-2"></i> Upload CSV
                        </button>
                        <button class="btn fw-bold flex-grow-1 text-muted nav-link-custom" id="tab-manual" data-bs-toggle="tab" data-bs-target="#panel-manual" role="tab" style="border-radius: 12px;">
                            <i class="fas fa-keyboard text-muted me-2"></i> Input Manual
                        </button>
                    </div>

                    <form method="POST" enctype="multipart/form-data" id="importForm" class="p-4">
                        <div class="tab-content">
                            
                            <div class="tab-pane fade show active" id="panel-file" role="tabpanel">
                                
                                <div class="upload-zone text-center p-5 mb-4" id="uploadZone" onclick="document.getElementById('csv_file').click()">
                                    <div class="icon-wrap mx-auto mb-3">
                                        <i class="fas fa-file-csv fs-1 text-success"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">Klik atau Drag file CSV ke sini</h5>
                                    <p class="text-muted small mb-0" id="fileNameDisplay">Maksimal ukuran file: 5MB</p>
                                    <input type="file" class="d-none" id="csv_file" name="csv_file" accept=".csv,.txt">
                                </div>

                                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 border">
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="fas fa-download text-primary fs-4"></i>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.85rem;">Butuh format data?</div>
                                            <div class="text-muted" style="font-size: 0.75rem;">Unduh template untuk memastikan kolom sesuai.</div>
                                        </div>
                                    </div>
                                    <a href="data:text/csv;charset=utf-8,Nama%2CNomor%20WA%0AJohn%20Doe%2C08123456789%0AJane%20Smith%2C081987654321%0AAhmad%20Rahman%2C08111222333" 
                                       class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3" download="Template_Pelanggan.csv">Download</a>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="panel-manual" role="tabpanel">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <label class="form-label text-dark fw-bold m-0" style="font-size: 0.85rem;">Teks Data (Format: Nama,Nomor)</label>
                                        <span class="text-muted" style="font-size: 0.75rem;" id="lineCount">0 Baris</span>
                                    </div>
                                    <textarea class="form-control-editorial" id="csv_data" name="csv_data" rows="12" 
                                              placeholder="John Doe, 08123456789&#10;Jane Smith, 081987654321&#10;Ahmad Rahman, 08111222333"
                                              style="font-family: monospace; white-space: pre; line-height: 1.6; resize: vertical;"><?= safeHtml(post('csv_data', '')) ?></textarea>
                                </div>
                            </div>

                        </div>
                        
                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-success fw-bold rounded-pill px-5 py-3 w-100 btn-submit" style="font-size: 1rem; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);">
                                <i class="fas fa-rocket me-2"></i> Jalankan Impor Data
                            </button>
                        </div>
                    </form>

                </div>
            </div>

            <div class="col-lg-5 col-xl-4 d-flex flex-column gap-4">
                
                <div class="panel-editorial p-0 overflow-hidden" style="border-top: 4px solid var(--success-color);">
                    <div class="p-3 bg-light border-bottom">
                        <h6 class="fw-bold m-0 text-dark"><i class="fas fa-info-circle text-success me-2"></i> Panduan Impor</h6>
                    </div>
                    <div class="p-3">
                        <ul class="list-unstyled mb-0" style="font-size: 0.8rem; line-height: 1.6;">
                            <li class="mb-2 d-flex align-items-start gap-2">
                                <i class="fas fa-check text-success mt-1"></i>
                                <span class="text-muted">Baris pertama **boleh** berisi header <code>Nama,Nomor WA</code>.</span>
                            </li>
                            <li class="mb-2 d-flex align-items-start gap-2">
                                <i class="fas fa-check text-success mt-1"></i>
                                <span class="text-muted">Pisahkan Nama dan Nomor menggunakan <strong class="text-dark">koma ( , )</strong>.</span>
                            </li>
                            <li class="mb-2 d-flex align-items-start gap-2">
                                <i class="fas fa-check text-success mt-1"></i>
                                <span class="text-muted">Format awal nomor bisa <code>081..</code>, <code>628..</code>, atau <code>+62..</code>, sistem akan menormalisasinya.</span>
                            </li>
                            <li class="d-flex align-items-start gap-2">
                                <i class="fas fa-bolt text-warning mt-1"></i>
                                <span class="text-muted">Nomor WA yang sama (Duplikat) akan dilewati otomatis.</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="panel-editorial p-0 overflow-hidden h-100 d-flex flex-column">
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold m-0 text-dark"><i class="fas fa-eye text-primary me-2"></i> Validasi Real-time</h6>
                        <span class="badge bg-secondary" id="validCount">0 Valid</span>
                    </div>
                    <div class="p-0 flex-grow-1" style="background: #FAFAFA; max-height: 350px; overflow-y: auto;">
                        <div id="preview-container" class="p-4 text-center">
                            <i class="fas fa-spell-check fs-2 text-muted opacity-25 mb-2"></i>
                            <p class="text-muted small m-0">Ketik di kotak teks kiri atau upload file untuk melihat hasil ekstrak.</p>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Tab Switching Logic (Karena struktur tabnya dicustom)
    const tabBtns = document.querySelectorAll('.nav-link-custom');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            // Reset semua
            tabBtns.forEach(b => {
                b.classList.remove('active');
                b.style.background = 'transparent';
                b.style.boxShadow = 'none';
                b.style.color = '#6B7280';
            });
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('show', 'active'));
            
            // Aktifkan yang diklik
            this.classList.add('active');
            this.style.background = 'white';
            this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.05)';
            this.style.color = '#111827';
            
            const target = document.querySelector(this.getAttribute('data-bs-target'));
            target.classList.add('show', 'active');
        });
    });

    const csvTextarea = document.getElementById('csv_data');
    const previewContainer = document.getElementById('preview-container');
    const validCountBadge = document.getElementById('validCount');
    const lineCountLabel = document.getElementById('lineCount');
    
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
            previewContainer.innerHTML = '<div class="p-4 text-center"><i class="fas fa-spell-check fs-2 text-muted opacity-25 mb-2"></i><p class="text-muted small m-0">Menunggu data...</p></div>';
            validCountBadge.textContent = '0 Valid';
            lineCountLabel.textContent = '0 Baris';
            return;
        }
        
        const lines = csvData.split('\n');
        lineCountLabel.textContent = lines.length + ' Baris';
        
        let validNum = 0;
        let html = '<table class="table-editorial m-0" style="font-size: 0.8rem;"><tbody>';
        
        lines.forEach((line, index) => {
            line = line.trim();
            if (!line) return;
            
            // Skip the first line if it looks like a header
            if(index === 0 && line.toLowerCase().includes('nama') && line.toLowerCase().includes('wa')) {
                html += `<tr><td colspan="2" class="text-muted bg-light text-center py-2"><i class="fas fa-info-circle me-1"></i> Header dilewati</td></tr>`;
                return;
            }

            const columns = line.split(',');
            if (columns.length >= 2) {
                const nama = columns[0].trim();
                const nomor = columns[1].trim();
                const normalizedNomor = normalizePhoneNumber(nomor);
                const isValid = /^62[0-9]{8,12}$/.test(normalizedNomor);
                
                if(isValid && nama) validNum++;

                html += `<tr>
                    <td class="py-2 px-3">
                        <div class="fw-bold text-dark text-truncate" style="max-width:120px;">${nama || '<span class="text-danger">Kosong</span>'}</div>
                        <div class="text-muted" style="font-family: monospace;">${normalizedNomor}</div>
                    </td>
                    <td class="text-end py-2 px-3">
                        ${isValid && nama ? 
                            '<i class="fas fa-check-circle text-success fs-5"></i>' : 
                            '<i class="fas fa-times-circle text-danger fs-5" title="Nomor/Nama tidak valid"></i>'
                        }
                    </td>
                </tr>`;
            } else {
                html += `<tr>
                    <td colspan="2" class="py-2 px-3 text-danger bg-danger bg-opacity-10">Baris ${index + 1}: Format rusak (Kurang Koma)</td>
                </tr>`;
            }
        });
        
        html += '</tbody></table>';
        
        // Hapus styling container bawaan kalau ada data
        previewContainer.className = ''; 
        previewContainer.innerHTML = html;
        validCountBadge.textContent = validNum + ' Valid';
    }
    
    csvTextarea.addEventListener('input', updatePreview);
    
    // File Upload Preview & Drag Drop Logic
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('csv_file');
    const fileNameDisplay = document.getElementById('fileNameDisplay');

    // Drag events
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadZone.addEventListener(eventName, preventDefaults, false);
    });
    function preventDefaults (e) { e.preventDefault(); e.stopPropagation(); }

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadZone.addEventListener(eventName, () => uploadZone.classList.add('dragover'), false);
    });
    ['dragleave', 'drop'].forEach(eventName => {
        uploadZone.addEventListener(eventName, () => uploadZone.classList.remove('dragover'), false);
    });

    // Handle Drop
    uploadZone.addEventListener('drop', function(e) {
        let dt = e.dataTransfer;
        let files = dt.files;
        if(files.length) {
            fileInput.files = files; // Pindahkan file ke input aslinya
            processFile(files[0]);
        }
    });

    // Handle Click Upload
    fileInput.addEventListener('change', function(e) {
        if(this.files.length) {
            processFile(this.files[0]);
        }
    });

    function processFile(file) {
        if (file) {
            // Update nama file di UI
            fileNameDisplay.innerHTML = `<span class="text-success fw-bold"><i class="fas fa-file-alt me-1"></i> ${file.name}</span> terpilih. Klik tab 'Input Manual' untuk melihat data.`;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                // Pindahkan isinya ke text area lalu paksa render ulang preview
                csvTextarea.value = e.target.result;
                updatePreview();
                
                // Beri jeda 500ms lalu otomatis pindah ke Tab Manual biar admin bisa ngedit/lihat
                setTimeout(() => {
                    document.getElementById('tab-manual').click();
                }, 500);
            };
            reader.readAsText(file);
        }
    }

    // UX Feedback on Form Submit
    document.getElementById('importForm').addEventListener('submit', function() {
        const btn = this.querySelector('.btn-submit');
        if(btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses Database...';
            btn.style.opacity = '0.8';
            btn.style.pointerEvents = 'none';
        }
    });

    // Initialize jika ada data sisa (misal pasca error submit)
    if(csvTextarea.value.trim() !== '') {
        updatePreview();
        document.getElementById('tab-manual').click();
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>