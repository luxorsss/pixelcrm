<?php
ob_start(); // 1. TAHAN SEMUA CETAKAN HTML

$page_title = 'Kelola Template Pesan';
require_once __DIR__ . '/../../includes/header.php'; // 2. Load header (Fungsi get() & database ada di sini)
require_once __DIR__ . '/functions.php';

$produk_id = (int)get('id');
if (!$produk_id) {
    setMessage('ID produk tidak valid', 'error');
    redirect('index.php');
}

// Get product info
$product = fetchRow("SELECT * FROM produk WHERE id = ?", [$produk_id]);
if (!$product) {
    setMessage('Produk tidak ditemukan', 'error');
    redirect('index.php');
}

// -----------------------------------------------------------------------------
// 1. HANDLE AJAX REQUEST (LIVE PREVIEW)
// -----------------------------------------------------------------------------
if (get('action') === 'preview') {
    ob_clean(); // 3. BUANG cetakan HTML dari header.php tadi! Kita cuma butuh JSON.
    
    header('Content-Type: application/json');
    $template_text = post('template');
    $sample_data = getSampleTemplateData($produk_id);
    $preview = replaceTemplatePlaceholders($template_text, $sample_data);
    
    echo json_encode(['preview' => nl2br(clean($preview))]);
    exit; // Berhenti di sini
}

// -----------------------------------------------------------------------------
// 2. HANDLE FORM SUBMIT
// -----------------------------------------------------------------------------
if (isPost() && !get('action')) {
    $template_invoice = clean(post('template_invoice', ''));
    $template_akses = clean(post('template_akses', ''));
    
    $success = true;
    
    // Save invoice template
    if (!saveTemplate($produk_id, 'invoice', $template_invoice)) {
        $success = false;
    }
    
    // Save akses template
    if (!saveTemplate($produk_id, 'akses_produk', $template_akses)) {
        $success = false;
    }
    
    if ($success) {
        setMessage('Template berhasil disimpan', 'success');
    } else {
        setMessage('Gagal menyimpan template', 'error');
    }
    redirect("edit.php?id=$produk_id");
}

// Get existing templates and sample data
$templates = getTemplatesByProduct($produk_id);
$sample_data = getSampleTemplateData($produk_id);
$placeholders = getAvailablePlaceholders();

// Biarkan HTML mengalir ke bawah jika ini bukan AJAX
?>

<div class="d-flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="main-content dashboard-wrapper flex-grow-1">
        <div class="content-area">
            
            <div class="dash-header flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <a href="index.php" class="text-muted text-decoration-none fw-bold" style="font-size: 0.85rem;">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Template
                    </a>
                    <h1 class="dash-title mt-2 d-flex align-items-center gap-2">
                        <i class="fas fa-box text-warning" style="font-size: 1.5rem;"></i> <?= clean($product['nama']) ?>
                    </h1>
                </div>
                <div>
                    <button type="submit" form="templateForm" class="btn btn-dark fw-bold rounded-pill px-4 btn-submit">
                        <i class="fas fa-save me-2"></i> Simpan Template
                    </button>
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

            <form method="POST" id="templateForm" class="row g-4">
                
                <div class="col-lg-7">
                    
                    <div class="panel-editorial mb-4" style="background: #F9FAFB; border: 1px dashed var(--border-light);">
                        <h3 class="panel-title" style="font-size: 1rem;"><i class="fas fa-magic text-primary"></i> Variabel Otomatis (Klik untuk memasukkan)</h3>
                        
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($placeholders as $category => $items): ?>
                                <div>
                                    <div class="text-muted fw-bold text-uppercase mb-2" style="font-size: 0.7rem; letter-spacing: 0.05em; border-bottom: 1px solid #E5E7EB; padding-bottom: 4px;"><?= $category ?></div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($items as $placeholder => $description): ?>
                                            <div class="badge-clean placeholder-chip" 
                                                 onclick="insertPlaceholder('<?= $placeholder ?>')" 
                                                 title="<?= $description ?>">
                                                <?= $placeholder ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="panel-editorial mb-4" style="border-left: 4px solid #F59E0B;">
                        <h3 class="panel-title d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-receipt text-warning"></i> Template Invoice (Pending)</span>
                        </h3>
                        <div class="text-muted mb-3" style="font-size: 0.85rem; line-height: 1.5;">
                            Dikirim saat pesanan dibuat. Gunakan ini untuk memberikan instruksi pembayaran kepada pembeli.
                        </div>
                        <textarea name="template_invoice" id="templateInvoice" class="form-control-editorial fw-medium" rows="12" 
                                  placeholder="Ketik pesan invoice di sini..." 
                                  style="resize: vertical; font-family: monospace; font-size: 0.85rem; line-height: 1.6;"><?= clean($templates['invoice']) ?></textarea>
                    </div>

                    <div class="panel-editorial mb-4 mb-lg-0" style="border-left: 4px solid #10B981;">
                        <h3 class="panel-title d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-key text-success"></i> Template Akses (Lunas)</span>
                        </h3>
                        <div class="text-muted mb-3" style="font-size: 0.85rem; line-height: 1.5;">
                            Dikirim saat pesanan sudah lunas. Gunakan ini untuk memberikan link download atau akses produk.
                        </div>
                        <textarea name="template_akses" id="templateAkses" class="form-control-editorial fw-medium" rows="12" 
                                  placeholder="Ketik pesan akses produk di sini..." 
                                  style="resize: vertical; font-family: monospace; font-size: 0.85rem; line-height: 1.6;"><?= clean($templates['akses_produk']) ?></textarea>
                    </div>

                </div>

                <div class="col-lg-5">
                    <div class="panel-editorial sticky-top p-0 overflow-hidden" style="top: 2rem;">
                        
                        <div class="bg-dark p-3 text-white border-bottom border-secondary d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold m-0" style="font-size: 0.95rem;"><i class="fab fa-whatsapp text-success me-2 fs-5 align-middle"></i> Live Preview</h6>
                            <span class="badge bg-secondary" style="font-size: 0.65rem;" id="activePreviewLabel">Preview Invoice</span>
                        </div>

                        <div style="background: #E5DDD5; padding: 1.5rem; min-height: 350px;">
                            <div class="bg-white rounded-3 p-3 shadow-sm position-relative" style="border-radius: 0 12px 12px 12px !important;">
                                <div style="position: absolute; top: 0; left: -8px; width: 0; height: 0; border-top: 10px solid white; border-left: 10px solid transparent;"></div>
                                
                                <div class="template-preview fw-medium text-dark" id="livePreviewContainer" style="font-size: 0.9rem; line-height: 1.5; white-space: pre-wrap; word-wrap: break-word;">
                                    <em class="text-muted text-center d-block py-4">Memuat preview...</em>
                                </div>
                                <div class="text-end mt-2 text-muted" style="font-size: 0.65rem;">
                                    <?= date('H:i') ?> <i class="fas fa-check-double text-info ms-1"></i>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-white border-top">
                            <div class="text-muted fw-bold text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 0.05em;"><i class="fas fa-database me-2"></i>Data Sample Saat Ini:</div>
                            <div class="row g-2" style="font-size: 0.8rem;">
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded border">
                                        <div class="text-muted mb-1">Nama Customer</div>
                                        <div class="fw-bold text-dark text-truncate"><?= $sample_data['nama_customer'] ?></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded border">
                                        <div class="text-muted mb-1">Total Tagihan</div>
                                        <div class="fw-bold text-success text-truncate"><?= formatCurrency($sample_data['total_harga']) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<style>
/* CSS khusus untuk Chip Placeholder */
.placeholder-chip {
    background: #EFF6FF;
    color: #2563EB;
    border: 1px solid #BFDBFE;
    padding: 0.35rem 0.85rem;
    font-size: 0.75rem;
    font-family: monospace;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
}
.placeholder-chip:hover {
    background: #3B82F6;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
}
.placeholder-chip:active { transform: scale(0.95); }
</style>

<script>
let currentActiveTextarea = 'templateInvoice'; // Default aktif

// Fungsi menyisipkan placeholder ke cursor position
function insertPlaceholder(placeholder) {
    const textarea = document.getElementById(currentActiveTextarea);
    if(!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const value = textarea.value;
    
    textarea.value = value.substring(0, start) + placeholder + value.substring(end);
    textarea.setSelectionRange(start + placeholder.length, start + placeholder.length);
    textarea.focus();
    
    updateLivePreview(currentActiveTextarea);
}

// Update live preview dengan error handling simpel
function updateLivePreview(textareaId) {
    const textarea = document.getElementById(textareaId);
    const previewContainer = document.getElementById('livePreviewContainer');
    const label = document.getElementById('activePreviewLabel');
    
    // Update label
    label.textContent = textareaId === 'templateInvoice' ? 'Preview Invoice' : 'Preview Akses Lunas';
    label.className = textareaId === 'templateInvoice' ? 'badge bg-warning text-dark' : 'badge bg-success text-white';

    const template = textarea.value;
    
    if (!template.trim()) {
        previewContainer.innerHTML = '<em class="text-muted d-block py-4 text-center">Template masih kosong...</em>';
        return;
    }
    
    previewContainer.style.opacity = '0.5';
    
    fetch('edit.php?id=<?= $produk_id ?>&action=preview', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'template=' + encodeURIComponent(template)
    })
    .then(response => response.json())
    .then(data => {
        if(data.preview) {
            previewContainer.innerHTML = data.preview;
        } else {
            previewContainer.innerHTML = '<em class="text-danger d-block py-4 text-center">Gagal merender teks.</em>';
        }
        previewContainer.style.opacity = '1';
    })
    .catch(error => {
        previewContainer.innerHTML = '<em class="text-danger d-block py-4 text-center">Gagal memuat AJAX Preview.</em>';
        previewContainer.style.opacity = '1';
    });
}

// Delay untuk typing agar tidak spam AJAX
const debounce = (func, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(this, args), delay);
    };
};

document.addEventListener('DOMContentLoaded', function() {
    const invoiceTextarea = document.getElementById('templateInvoice');
    const aksesTextarea = document.getElementById('templateAkses');
    
    const debouncedPreview = debounce((id) => updateLivePreview(id), 500);

    // Event listener Invoice
    if (invoiceTextarea) {
        invoiceTextarea.addEventListener('focus', () => { 
            currentActiveTextarea = 'templateInvoice'; 
            updateLivePreview('templateInvoice'); 
        });
        invoiceTextarea.addEventListener('input', () => debouncedPreview('templateInvoice'));
    }
    
    // Event listener Akses
    if (aksesTextarea) {
        aksesTextarea.addEventListener('focus', () => { 
            currentActiveTextarea = 'templateAkses'; 
            updateLivePreview('templateAkses'); 
        });
        aksesTextarea.addEventListener('input', () => debouncedPreview('templateAkses'));
    }
    
    // Muat preview pertama kali
    updateLivePreview('templateInvoice');

    // UX Feedback Tombol Simpan
    document.getElementById('templateForm').addEventListener('submit', function() {
        const btn = this.querySelector('.btn-submit');
        if(btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
            btn.style.opacity = '0.8';
            btn.style.pointerEvents = 'none';
        }
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>