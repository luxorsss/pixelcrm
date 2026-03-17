<?php
$page_title = 'Edit Followup Message';
require_once '../../includes/header.php';
require_once 'functions.php';

$id = get('id');
if (!$id) {
    setMessage('ID followup message tidak ditemukan', 'error');
    redirect('index.php');
}

// Get followup data
$followup = getFollowupMessage($id);
if (!$followup) {
    setMessage('Followup message tidak ditemukan', 'error');
    redirect('index.php');
}

// Get product info
$product = fetchRow("SELECT * FROM produk WHERE id = ?", [$followup['produk_id']]);

$errors = [];

if (isPost()) {
    $data = [
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
        if (updateFollowupMessage($id, $data)) {
            setMessage('Followup message berhasil diupdate', 'success');
            redirect("index.php?produk_id=" . $followup['produk_id']);
        } else {
            $errors[] = 'Gagal mengupdate followup message';
        }
    }
} else {
    // Fill form with existing data
    $_POST = $followup;
}
?>

<div class="main-content">
    <?php require_once '../../includes/sidebar.php'; ?>
    
    <div class="content-area">
        <div class="row">
            <div class="col-lg-8">
                <div class="d-flex align-items-center mb-4">
                    <a href="index.php?produk_id=<?= $followup['produk_id'] ?>" class="btn btn-outline-secondary me-3">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="page-title mb-0">Edit Followup Message</h1>
                        <small class="text-muted">Untuk produk: <?= clean($product['nama']) ?></small>
                    </div>
                </div>

                <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?= clean($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="card">
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
                                               value="<?= post('urutan') ?>" min="1" required>
                                        <small class="text-muted">Urutan pengiriman pesan</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status">
                                            <option value="aktif" <?= post('status') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
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
                                               value="<?= post('delay_value') ?>" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Unit Waktu</label>
                                        <select class="form-select" name="delay_unit">
                                            <?php foreach (getDelayUnits() as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= post('delay_unit') === $value ? 'selected' : '' ?>>
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
                                            <option value="pesan" <?= post('tipe_pesan') === 'pesan' ? 'selected' : '' ?>>Teks Saja</option>
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
                                <small class="text-muted">URL gambar yang akan dikirim bersama pesan</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Isi Pesan <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="isi_pesan" rows="6" required 
                                          placeholder="Tulis pesan followup..." onkeyup="updatePreview()"><?= clean(post('isi_pesan')) ?></textarea>
                                <small class="text-muted">
                                    Gunakan placeholder: <code>[nama]</code>, <code>[produk]</code>, <code>[harga]</code>
                                </small>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update
                                </button>
                                <a href="index.php?produk_id=<?= $followup['produk_id'] ?>" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Batal
                                </a>
                                <a href="delete.php?id=<?= $id ?>" class="btn btn-outline-danger" 
                                   onclick="return confirm('Yakin hapus followup message ini?')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Message History (jika ada) -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-history"></i> Riwayat Pesan</h6>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">
                            <strong>Dibuat:</strong> <?= formatDate($followup['created_at'], 'd/m/Y H:i') ?><br>
                            <strong>Terakhir diupdate:</strong> <?= formatDate($followup['updated_at'], 'd/m/Y H:i') ?>
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Live Preview -->
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-eye"></i> Live Preview</h6>
                    </div>
                    <div class="card-body">
                        <div class="border rounded p-3" style="background: #f8f9fa; min-height: 150px;">
                            <strong>📱 WhatsApp Preview:</strong>
                            <hr>
                            <div id="previewContent"></div>
                        </div>
                        
                        <!-- Info -->
                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>Timing Info:</strong><br>
                                Pesan ini akan terkirim <strong><?= formatDelay(post('delay_value'), post('delay_unit')) ?></strong> 
                                setelah pesan sebelumnya<?= post('urutan') == 1 ? ' (atau langsung setelah transaksi)' : '' ?>.
                            </small>
                            
                            <?php 
                            $bundling_info = fetchAll("
                                SELECT p2.nama 
                                FROM bundling b 
                                JOIN produk p2 ON b.produk_bundling_id = p2.id 
                                WHERE b.produk_id = ?
                                ORDER BY p2.nama ASC
                            ", [$product['id']]);
                            
                            if ($bundling_info): ?>
                            <hr class="my-2">
                            <small class="text-info">
                                <strong>Bundling Info:</strong><br>
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
    ", [$product['id']]);
    
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
        '[harga]': '<?= formatCurrency(getBundlingTotalPrice($product['id'])) ?>'
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

<?php require_once '../../includes/footer.php'; ?>