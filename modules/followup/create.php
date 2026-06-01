<?php
// === LOGIC SECTION (No Output) ===
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

$produk_id = get('produk_id');
if (!$produk_id) {
    setMessage('Pilih produk terlebih dahulu', 'error');
    redirect('index.php');
}

// Get product info
$product = fetchRow("SELECT * FROM produk WHERE id = ?", [$produk_id]);
if (!$product) {
    setMessage('Produk tidak ditemukan', 'error');
    redirect('index.php');
}

$page_title = 'Tambah Followup Message';
$errors = [];

if (isPost()) {
    $data = [
        'produk_id' => $produk_id,
        'nama_pesan' => post('nama_pesan'),
        'urutan' => post('urutan'),
        'delay_value' => post('delay_value'),
        'delay_unit' => post('delay_unit'),
        'tipe_pesan' => post('tipe_pesan'),
        'isi_pesan' => post('isi_pesan'),
        'link_gambar' => post('link_gambar'),
        'status' => post('status', 'aktif')
    ];
    
    $errors = validateFollowupData($data);
    
    if (!$errors) {
        if (createFollowupMessage($data)) {
            $new_message_id = db()->insert_id;
            
            // Generate followup logs for existing transactions
            $existing_count = generateForExistingTransactionsTrueSequential($new_message_id);
            
            if ($existing_count > 0) {
                setMessage("Followup message berhasil ditambahkan dan dijadwalkan untuk $existing_count transaksi existing", 'success');
            } else {
                setMessage('Followup message berhasil ditambahkan', 'success');
            }
            
            redirect("index.php?produk_id=$produk_id");
        } else {
            $errors[] = 'Gagal menyimpan followup message';
        }
    }
}

// Get suggested urutan
$next_urutan = getNextUrutan($produk_id);

// === PRESENTATION SECTION ===
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content dashboard-wrapper flex-grow-1">
    <div class="form-container" style="max-width: 1200px;">
        
        <div class="dash-header mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <a href="index.php?produk_id=<?= $produk_id ?>" class="text-muted text-decoration-none fw-bold" style="font-size: 0.85rem;">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Sequence
                </a>
                <h1 class="dash-title mt-2 d-flex align-items-center gap-2">
                    Buat Pesan Follow-up
                </h1>
                <div class="text-muted" style="font-size: 0.9rem; font-weight: 500;">
                    Target Produk: <strong class="text-primary"><?= clean($product['nama']) ?></strong>
                </div>
            </div>
            <div>
                <button type="submit" form="followupForm" class="btn btn-dark fw-bold rounded-pill px-4 btn-submit">
                    <i class="fas fa-save me-2"></i> Simpan Pesan
                </button>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-editorial mb-4" style="border-left-color: var(--danger-color); background: #FEF2F2;">
                <ul class="mb-0 text-danger fw-bold" style="font-size: 0.9rem; list-style-type: none; padding-left: 0;">
                    <?php foreach ($errors as $error): ?>
                        <li><i class="fas fa-exclamation-circle me-2"></i><?= clean($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="followupForm" class="row g-4">
            
            <div class="col-lg-7">
                
                <div class="panel-editorial mb-4">
                    <h3 class="panel-title"><i class="fas fa-tag text-primary"></i> Identitas Pesan</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Pengenal <span class="text-danger">*</span></label>
                            <input type="text" class="form-control-editorial fw-bold text-dark" name="nama_pesan" 
                                   value="<?= clean(post('nama_pesan')) ?>" required 
                                   placeholder="Contoh: Hari ke-1 (Tanya Kabar)">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Urutan Ke- <span class="text-danger">*</span></label>
                            <input type="number" class="form-control-editorial text-center fw-bold" name="urutan" 
                                   value="<?= post('urutan', $next_urutan) ?>" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-control-editorial fw-bold" name="status" style="appearance: auto;">
                                <option value="aktif" <?= post('status', 'aktif') === 'aktif' ? 'selected' : '' ?>>🟢 Aktif</option>
                                <option value="nonaktif" <?= post('status') === 'nonaktif' ? 'selected' : '' ?>>🔴 Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="panel-editorial mb-4">
                    <h3 class="panel-title d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-stopwatch text-warning"></i> Jeda Pengiriman (Delay)</span>
                    </h3>
                    <div class="text-muted mb-3" style="font-size: 0.8rem;">
                        Pesan urutan ke-1 akan dihitung dari waktu checkout. Pesan ke-2 dan seterusnya dihitung dari waktu pesan sebelumnya.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Lama Jeda <span class="text-danger">*</span></label>
                            <input type="number" class="form-control-editorial fw-bold" name="delay_value" 
                                   value="<?= post('delay_value', 1) ?>" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Satuan Waktu <span class="text-danger">*</span></label>
                            <select class="form-control-editorial fw-bold" name="delay_unit" style="appearance: auto;">
                                <?php foreach (getDelayUnits() as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= post('delay_unit', 'hari') === $value ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="panel-editorial mb-4 mb-lg-0" style="border-left: 4px solid #10B981;">
                    <h3 class="panel-title"><i class="fas fa-comment-dots text-success"></i> Konten Pesan WA</h3>
                    
                    <div class="mb-3">
                        <label class="form-label">Format Media</label>
                        <select class="form-control-editorial fw-bold" name="tipe_pesan" onchange="toggleImageField()" style="appearance: auto; max-width: 250px;">
                            <option value="pesan" <?= post('tipe_pesan', 'pesan') === 'pesan' ? 'selected' : '' ?>>Teks Saja</option>
                            <option value="pesan_gambar" <?= post('tipe_pesan') === 'pesan_gambar' ? 'selected' : '' ?>>Teks + Gambar</option>
                        </select>
                    </div>

                    <div id="imageField" class="mb-4 p-3 bg-light rounded-3 border" style="display: none; transition: all 0.3s;">
                        <label class="form-label text-info fw-bold"><i class="fas fa-link me-1"></i> URL Gambar Attachment</label>
                        <input type="url" class="form-control-editorial bg-white" name="link_gambar" id="linkGambar"
                               value="<?= clean(post('link_gambar')) ?>" 
                               placeholder="https://domain.com/gambar-promo.jpg" oninput="updatePreview()">
                        <div class="text-muted mt-2" style="font-size: 0.75rem;">Pastikan link gambar dapat diakses secara publik (akhiran .jpg / .png).</div>
                    </div>

                    <div class="mb-2 d-flex justify-content-between align-items-end">
                        <label class="form-label m-0">Teks Pesan <span class="text-danger">*</span></label>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            Bisa pakai: <code class="badge-clean bg-light text-primary border px-1">[nama]</code> 
                            <code class="badge-clean bg-light text-primary border px-1">[produk]</code> 
                            <code class="badge-clean bg-light text-primary border px-1">[harga]</code>
                        </div>
                    </div>
                    
                    <textarea class="form-control-editorial fw-medium" name="isi_pesan" id="isiPesan" rows="10" required 
                              placeholder="Ketik pesan follow-up di sini..." 
                              style="resize: vertical; font-family: monospace; font-size: 0.85rem; line-height: 1.6;"
                              oninput="updatePreview()"><?= clean(post('isi_pesan')) ?></textarea>
                </div>

            </div>

            <div class="col-lg-5">
                <div class="panel-editorial sticky-top p-0 overflow-hidden" style="top: 2rem;">
                    
                    <div class="bg-dark p-3 text-white border-bottom border-secondary d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold m-0" style="font-size: 0.95rem;"><i class="fab fa-whatsapp text-success me-2 fs-5 align-middle"></i> Live Preview</h6>
                        <span class="badge bg-success text-white" style="font-size: 0.65rem;">Simulasi Layar</span>
                    </div>

                    <div style="background: #E5DDD5; padding: 1.5rem; min-height: 350px;">
                        <div class="bg-white rounded-3 p-3 shadow-sm position-relative" style="border-radius: 0 12px 12px 12px !important;">
                            <div style="position: absolute; top: 0; left: -8px; width: 0; height: 0; border-top: 10px solid white; border-left: 10px solid transparent;"></div>
                            
                            <div class="template-preview fw-medium text-dark" id="previewContent" style="font-size: 0.9rem; line-height: 1.5; white-space: pre-wrap; word-wrap: break-word;">
                                <em class="text-muted text-center d-block py-4">Ketik pesan di samping untuk melihat preview...</em>
                            </div>
                            <div class="text-end mt-1 text-muted" style="font-size: 0.65rem;">
                                <?= date('H:i') ?> <i class="fas fa-check-double text-info ms-1"></i>
                            </div>
                        </div>
                    </div>

                    <?php 
                    $bundling_info = fetchAll("
                        SELECT p2.nama 
                        FROM bundling b 
                        JOIN produk p2 ON b.produk_bundling_id = p2.id 
                        WHERE b.produk_id = ?
                        ORDER BY p2.nama ASC
                    ", [$produk_id]);
                    
                    if ($bundling_info): ?>
                        <div class="p-3 bg-white border-top">
                            <div class="p-3 rounded-3" style="background: #EFF6FF; border: 1px dashed #BFDBFE;">
                                <div class="text-primary fw-bold mb-1" style="font-size: 0.8rem;"><i class="fas fa-link me-1"></i> Info Tag [produk]</div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    Karena ini adalah paket Bundling, tag <code>[produk]</code> otomatis me-render:<br>
                                    <strong class="text-dark"><?= clean($product['nama']) ?> + <?= implode(', ', array_column($bundling_info, 'nama')) ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </form>

    </div>
</div>

<script>
// Logika PHP untuk render data dummy di JavaScript
<?php 
$bundling_products = fetchAll("
    SELECT p2.nama 
    FROM bundling b 
    JOIN produk p2 ON b.produk_bundling_id = p2.id 
    WHERE b.produk_id = ?
    ORDER BY p2.nama ASC
", [$produk_id]);

$sample_products = [$product['nama']];
foreach ($bundling_products as $bp) {
    $sample_products[] = $bp['nama'];
}

$count = count($sample_products);
if ($count == 1) {
    $product_display = $sample_products[0];
} elseif ($count == 2) {
    $product_display = $sample_products[0] . ' & ' . $sample_products[1];
} else {
    $last = array_pop($sample_products);
    $product_display = implode(', ', $sample_products) . ', & ' . $last;
}
?>

const sampleData = {
    '[nama]': 'Budi Santoso',
    '[produk]': '<?= clean($product_display) ?>',
    '[harga]': '<?= formatCurrency(getBundlingTotalPrice($produk_id)) ?>'
};

function toggleImageField() {
    const tipeField = document.querySelector('select[name="tipe_pesan"]');
    const imageField = document.getElementById('imageField');
    const linkInput = document.getElementById('linkGambar');
    
    if (tipeField.value === 'pesan_gambar') {
        imageField.style.display = 'block';
        linkInput.setAttribute('required', 'required');
    } else {
        imageField.style.display = 'none';
        linkInput.removeAttribute('required');
    }
    updatePreview();
}

function updatePreview() {
    const message = document.getElementById('isiPesan').value;
    const tipeField = document.querySelector('select[name="tipe_pesan"]').value;
    const imageUrl = document.getElementById('linkGambar').value;
    const previewDiv = document.getElementById('previewContent');
    
    if (!message.trim() && (tipeField !== 'pesan_gambar' || !imageUrl.trim())) {
        previewDiv.innerHTML = '<em class="text-muted d-block py-4 text-center">Ketik pesan di samping untuk melihat preview...</em>';
        return;
    }
    
    // Replace placeholders
    let previewMessage = message;
    Object.keys(sampleData).forEach(placeholder => {
        previewMessage = previewMessage.replace(
            new RegExp(placeholder.replace(/[\[\]]/g, '\\$&'), 'g'), 
            sampleData[placeholder]
        );
    });
    
    // Konversi newline
    let contentHtml = previewMessage.replace(/\n/g, '<br>');
    
    // Sisipkan gambar jika tipe adalah pesan_gambar
    if (tipeField === 'pesan_gambar' && imageUrl.trim() !== '') {
        const imgTag = `<img src="${imageUrl}" class="img-fluid rounded mb-2 w-100" style="max-height: 250px; object-fit: cover; background: #F3F4F6;" onerror="this.src='https://via.placeholder.com/300x200?text=Gambar+Tidak+Valid';">`;
        contentHtml = imgTag + contentHtml;
    }
    
    previewDiv.innerHTML = contentHtml;
}

// Inisialisasi Event & UI
document.addEventListener('DOMContentLoaded', function() {
    toggleImageField();
    updatePreview();

    // UX Feedback form submit
    document.getElementById('followupForm').addEventListener('submit', function() {
        const btn = this.querySelector('.btn-submit');
        if(btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
            btn.style.opacity = '0.8';
            btn.style.pointerEvents = 'none';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>