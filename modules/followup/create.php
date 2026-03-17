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

<div class="main-content">
    <!-- Top Header -->
    <div class="bg-white border-bottom p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1">Tambah Followup Message</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Followup</a></li>
                        <li class="breadcrumb-item"><a href="index.php?produk_id=<?= $produk_id ?>"><?= clean($product['nama']) ?></a></li>
                        <li class="breadcrumb-item active">Tambah</li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="index.php?produk_id=<?= $produk_id ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= clean($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Form Tambah Followup Message</h5>
                        <small class="text-muted">Untuk produk: <?= clean($product['nama']) ?></small>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="followupForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Pesan <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="nama_pesan" 
                                               value="<?= clean(post('nama_pesan')) ?>" required 
                                               placeholder="Misal: Ucapan Terima Kasih">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Urutan <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="urutan" 
                                               value="<?= post('urutan', $next_urutan) ?>" min="1" required>
                                        <small class="form-text text-muted">Urutan pengiriman pesan</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status">
                                            <option value="aktif" <?= post('status', 'aktif') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                            <option value="nonaktif" <?= post('status') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Delay <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="delay_value" 
                                               value="<?= post('delay_value', 1) ?>" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Unit Waktu</label>
                                        <select class="form-select" name="delay_unit">
                                            <?php foreach (getDelayUnits() as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= post('delay_unit', 'hari') === $value ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Tipe Pesan</label>
                                        <select class="form-select" name="tipe_pesan" onchange="toggleImageField()">
                                            <option value="pesan" <?= post('tipe_pesan', 'pesan') === 'pesan' ? 'selected' : '' ?>>Teks Saja</option>
                                            <option value="pesan_gambar" <?= post('tipe_pesan') === 'pesan_gambar' ? 'selected' : '' ?>>Teks + Gambar</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3" id="imageField" style="<?= post('tipe_pesan') === 'pesan_gambar' ? '' : 'display:none;' ?>">
                                <label class="form-label">Link Gambar</label>
                                <input type="url" class="form-control" name="link_gambar" 
                                       value="<?= clean(post('link_gambar')) ?>" 
                                       placeholder="https://example.com/gambar.jpg">
                                <small class="form-text text-muted">URL gambar yang akan dikirim bersama pesan</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Isi Pesan <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="isi_pesan" rows="6" required 
                                          placeholder="Tulis pesan followup..." onkeyup="updatePreview()"><?= clean(post('isi_pesan')) ?></textarea>
                                <small class="form-text text-muted">
                                        Gunakan placeholder: <code>[nama]</code>, <code>[produk]</code>, <code>[harga]</code>
                                </small>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="index.php?produk_id=<?= $produk_id ?>" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Simpan Followup
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Live Preview -->
                <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0"><i class="fas fa-eye me-2"></i>Live Preview</h6>
                    </div>
                    <div class="card-body">
                        <div class="border rounded p-3" style="background: #f8f9fa; min-height: 150px;">
                            <strong>📱 WhatsApp Preview:</strong>
                            <hr>
                            <div id="previewContent">
                                <em class="text-muted">Ketik pesan untuk melihat preview...</em>
                            </div>
                        </div>
                        
                        <!-- Info Section -->
                        <div class="card border-info mt-3">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Info Timing</h6>
                            </div>
                            <div class="card-body">
                                <small class="text-muted">
                                    • Pesan urutan 1: Langsung setelah transaksi<br>
                                    • Pesan urutan 2+: Sesuai delay dari pesan sebelumnya
                                </small>
                                
                                <?php 
                                $bundling_info = fetchAll("
                                    SELECT p2.nama 
                                    FROM bundling b 
                                    JOIN produk p2 ON b.produk_bundling_id = p2.id 
                                    WHERE b.produk_id = ?
                                    ORDER BY p2.nama ASC
                                ", [$produk_id]);
                                
                                if ($bundling_info): ?>
                                <hr class="my-2">
                                <small class="text-info">
                                    <strong>Bundling Produk:</strong><br>
                                    Produk ini dibundling dengan: 
                                    <?= implode(', ', array_column($bundling_info, 'nama')) ?>
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleImageField() {
    const tipeField = document.querySelector('select[name="tipe_pesan"]');
    const imageField = document.getElementById('imageField');
    
    if (tipeField.value === 'pesan_gambar') {
        imageField.style.display = 'block';
        document.querySelector('input[name="link_gambar"]').setAttribute('required', '');
    } else {
        imageField.style.display = 'none';
        document.querySelector('input[name="link_gambar"]').removeAttribute('required');
    }
}

function updatePreview() {
    const message = document.querySelector('textarea[name="isi_pesan"]').value;
    const previewDiv = document.getElementById('previewContent');
    
    if (!message.trim()) {
        previewDiv.innerHTML = '<em class="text-muted">Ketik pesan untuk melihat preview...</em>';
        return;
    }
    
    // Get bundling products for this product
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
    
    // Format product list
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
    
    // Replace placeholders
    const sampleData = {
        '[nama]': 'John Doe',
        '[produk]': '<?= clean($product_display) ?>',
        '[harga]': '<?= formatCurrency(getBundlingTotalPrice($produk_id)) ?>'
    };
    
    let previewMessage = message;
    Object.keys(sampleData).forEach(placeholder => {
        previewMessage = previewMessage.replace(
            new RegExp(placeholder.replace(/[\[\]]/g, '\\$&'), 'g'), 
            sampleData[placeholder]
        );
    });
    
    previewDiv.innerHTML = previewMessage.replace(/\n/g, '<br>');
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    toggleImageField();
    updatePreview();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>