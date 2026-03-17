<?php
$page_title = 'Kelola Template Pesan';
require_once __DIR__ . '/../../includes/header.php';
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

// Handle AJAX preview request
if (get('action') === 'preview') {
    header('Content-Type: application/json');
    $template_text = post('template');
    $sample_data = getSampleTemplateData($produk_id);
    $preview = replaceTemplatePlaceholders($template_text, $sample_data);
    echo json_encode(['preview' => nl2br(clean($preview))]);
    exit;
}

// Process form submission
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
?>

<div class="d-flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-area">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1"><?= $page_title ?></h2>
                    <p class="text-muted mb-0">
                        <i class="fas fa-box me-1"></i><?= clean($product['nama']) ?>
                    </p>
                </div>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Kembali
                </a>
            </div>

            <form method="POST" class="row">
                <!-- Placeholder Helper -->
                <div class="col-12 mb-3">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-tags me-2"></i>Placeholder yang Tersedia
                                <button type="button" class="btn btn-sm btn-light ms-2" onclick="togglePlaceholders()">
                                    <i class="fas fa-eye" id="toggleIcon"></i> <span id="toggleText">Tampilkan</span>
                                </button>
                            </h6>
                        </div>
                        <div class="card-body" id="placeholderList" style="display: none;">
                            <div class="row">
                                <?php foreach ($placeholders as $category => $items): ?>
                                    <div class="col-md-3">
                                        <h6 class="text-primary"><?= $category ?></h6>
                                        <?php foreach ($items as $placeholder => $description): ?>
                                            <div class="mb-2">
                                                <code class="placeholder-item" onclick="insertPlaceholder('<?= $placeholder ?>')" 
                                                      style="cursor: pointer; background: #e3f2fd; padding: 2px 6px; border-radius: 4px;">
                                                    <?= $placeholder ?>
                                                </code>
                                                <small class="d-block text-muted"><?= $description ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="alert alert-info mt-3 mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Tip:</strong> Klik placeholder untuk menambahkan ke template yang sedang aktif
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Template Invoice -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-receipt me-2"></i>Template Invoice
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Isi Template Invoice</label>
                                <textarea name="template_invoice" 
                                          id="templateInvoice"
                                          class="form-control" 
                                          rows="12" 
                                          placeholder="Masukkan template pesan invoice..."><?= clean($templates['invoice']) ?></textarea>
                                <div class="form-text">
                                    Template pesan yang dikirim saat customer melakukan pemesanan
                                </div>
                            </div>
                            
                            <!-- Preview with Sample Data -->
                            <div class="border rounded p-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="text-muted mb-0">Preview dengan Data Sample:</h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="updateLivePreview('templateInvoice', 'invoicePreview')">
                                        <i class="fas fa-sync-alt"></i> Refresh
                                    </button>
                                </div>
                                <div class="template-preview" id="invoicePreview">
                                    <?= $templates['invoice'] ? nl2br(clean(replaceTemplatePlaceholders($templates['invoice'], $sample_data))) : '<em class="text-muted">Template kosong</em>' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Template Akses Produk -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-key me-2"></i>Template Akses Produk
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Isi Template Akses Produk</label>
                                <textarea name="template_akses" 
                                          id="templateAkses"
                                          class="form-control" 
                                          rows="12" 
                                          placeholder="Masukkan template pesan akses produk..."><?= clean($templates['akses_produk']) ?></textarea>
                                <div class="form-text">
                                    Template pesan yang dikirim saat memberikan akses produk digital
                                </div>
                            </div>
                            
                            <!-- Preview with Sample Data -->
                            <div class="border rounded p-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="text-muted mb-0">Preview dengan Data Sample:</h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="updateLivePreview('templateAkses', 'aksesPreview')">
                                        <i class="fas fa-sync-alt"></i> Refresh
                                    </button>
                                </div>
                                <div class="template-preview" id="aksesPreview">
                                    <?= $templates['akses_produk'] ? nl2br(clean(replaceTemplatePlaceholders($templates['akses_produk'], $sample_data))) : '<em class="text-muted">Template kosong</em>' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="col-12 mt-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Simpan Template
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Sample Data Info -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6><i class="fas fa-database me-2"></i>Data Sample untuk Preview</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Customer:</strong>
                            <ul class="text-muted mb-0">
                                <li>Nama: <?= $sample_data['nama_customer'] ?></li>
                                <li>No WA: <?= $sample_data['nomor_wa'] ?></li>
								<li>Email: <?= $sample_data['email_customer'] ?></li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <strong>Transaksi:</strong>
                            <ul class="text-muted mb-0">
                                <li>ID: <?= $sample_data['id_transaksi'] ?></li>
                                <li>Total: <?= formatCurrency($sample_data['total_harga']) ?></li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <strong>Produk:</strong>
                            <?php if (isset($sample_data['produk_list'])): ?>
                                <ul class="text-muted mb-0">
                                    <?php foreach ($sample_data['produk_list'] as $produk): ?>
                                        <li><?= clean($produk['nama']) ?> - <?= formatCurrency($produk['harga']) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <ul class="text-muted mb-0">
                                    <li><?= clean($sample_data['nama_produk']) ?> - <?= formatCurrency($sample_data['harga_produk']) ?></li>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Help Card -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6><i class="fas fa-lightbulb me-2"></i>Contoh Template</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Template Invoice:</strong>
                            <div class="bg-light p-3 rounded text-muted small mt-2">
                                Halo [nama]!<br><br>
                                Terima kasih telah memesan:<br>
                                [daftar_produk]<br><br>
                                Total: [total]<br>
                                ID Transaksi: [id_transaksi]<br><br>
                                Silakan lakukan pembayaran sesuai instruksi...
                            </div>
                        </div>
                        <div class="col-md-6">
                            <strong>Template Akses Produk:</strong>
                            <div class="bg-light p-3 rounded text-muted small mt-2">
                                Halo [nama]!<br><br>
                                Pembayaran berhasil! Berikut akses produk Anda:<br><br>
                                [daftar_link]<br><br>
                                Terima kasih telah berbelanja!<br>
                                Hubungi admin jika ada kendala.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentActiveTextarea = null;

// Toggle placeholder visibility
function togglePlaceholders() {
    const list = document.getElementById('placeholderList');
    const icon = document.getElementById('toggleIcon');
    const text = document.getElementById('toggleText');
    
    if (list.style.display === 'none') {
        list.style.display = 'block';
        icon.className = 'fas fa-eye-slash';
        text.textContent = 'Sembunyikan';
    } else {
        list.style.display = 'none';
        icon.className = 'fas fa-eye';
        text.textContent = 'Tampilkan';
    }
}

// Insert placeholder to active textarea
function insertPlaceholder(placeholder) {
    if (!currentActiveTextarea) {
        alert('Klik pada textarea terlebih dahulu');
        return;
    }
    
    const textarea = document.getElementById(currentActiveTextarea);
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const value = textarea.value;
    
    // Insert at cursor position
    textarea.value = value.substring(0, start) + placeholder + value.substring(end);
    
    // Move cursor after inserted placeholder
    textarea.setSelectionRange(start + placeholder.length, start + placeholder.length);
    textarea.focus();
    
    // Update preview
    updateLivePreview(currentActiveTextarea, currentActiveTextarea === 'templateInvoice' ? 'invoicePreview' : 'aksesPreview');
}

// Update live preview with AJAX
function updateLivePreview(textareaId, previewId) {
    const textarea = document.getElementById(textareaId);
    const preview = document.getElementById(previewId);
    const template = textarea.value;
    
    if (!template.trim()) {
        preview.innerHTML = '<em class="text-muted">Template kosong</em>';
        return;
    }
    
    // Show loading
    preview.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memuat preview...';
    
    // AJAX request
    fetch('edit.php?id=<?= $produk_id ?>&action=preview', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'template=' + encodeURIComponent(template)
    })
    .then(response => response.json())
    .then(data => {
        preview.innerHTML = data.preview || '<em class="text-muted">Error preview</em>';
    })
    .catch(error => {
        preview.innerHTML = '<em class="text-danger">Error loading preview</em>';
    });
}

// Document ready
document.addEventListener('DOMContentLoaded', function() {
    const invoiceTextarea = document.getElementById('templateInvoice');
    const aksesTextarea = document.getElementById('templateAkses');
    
    // Track active textarea
    if (invoiceTextarea) {
        invoiceTextarea.addEventListener('focus', () => currentActiveTextarea = 'templateInvoice');
        invoiceTextarea.addEventListener('input', () => updateLivePreview('templateInvoice', 'invoicePreview'));
    }
    
    if (aksesTextarea) {
        aksesTextarea.addEventListener('focus', () => currentActiveTextarea = 'templateAkses');
        aksesTextarea.addEventListener('input', () => updateLivePreview('templateAkses', 'aksesPreview'));
    }
    
    // Highlight placeholder items on hover
    document.querySelectorAll('.placeholder-item').forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#1976d2';
            this.style.color = 'white';
        });
        item.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '#e3f2fd';
            this.style.color = '#1976d2';
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>