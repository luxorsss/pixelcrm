<?php
$page_title = "Kelola Bundling";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/functions.php';

// Helper untuk escape string di JS
function cleanJs($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Handle both old (id) and new (produk_id) parameters
$produk_id = (int)get('produk_id');
$bundling_id = (int)get('id');

if ($bundling_id && !$produk_id) {
    $bundling = getBundlingById($bundling_id);
    if ($bundling) {
        $produk_id = $bundling['produk_id'];
    }
}

if (!$produk_id) {
    setMessage('ID produk tidak valid', 'error');
    redirect('index.php');
}

$mainProduct = fetchRow("SELECT id, nama, harga FROM produk WHERE id = ?", [$produk_id]);
if (!$mainProduct) {
    setMessage('Produk tidak ditemukan', 'error');
    redirect('index.php');
}

// Ambil bundling existing + deskripsi
$existingBundlings = fetchAll(
    "SELECT b.id, b.produk_bundling_id, b.diskon, b.deskripsi, p.nama, p.harga 
     FROM bundling b 
     JOIN produk p ON b.produk_bundling_id = p.id 
     WHERE b.produk_id = ? 
     ORDER BY p.nama", 
    [$produk_id]
);

$errors = [];

if (isPost()) {
    $bundling_data = post('bundling', []);
    
    if (empty($bundling_data)) {
        $errors[] = 'Minimal 1 produk bundling harus dipilih';
    } else {
        execute("DELETE FROM bundling WHERE produk_id = ?", [$produk_id]);
        
        $success_count = 0;
        foreach ($bundling_data as $item) {
            $bundle_id = (int)($item['produk_id'] ?? 0);
            $diskon = (int)($item['diskon'] ?? 0);
            $deskripsi = trim($item['deskripsi'] ?? '');
            
            if ($bundle_id > 0 && $diskon > 0) {
                if ($bundle_id == $produk_id) {
                    $errors[] = 'Produk tidak bisa dibundle dengan dirinya sendiri';
                    break;
                }
                
                if (createBundling($produk_id, $bundle_id, $diskon, $deskripsi)) {
                    $success_count++;
                }
            }
        }
        
        if (empty($errors) && $success_count > 0) {
            setMessage("Berhasil menyimpan {$success_count} bundling produk", 'success');
            redirect('index.php');
        } elseif (empty($errors)) {
            setMessage('Tidak ada bundling yang disimpan', 'warning');
        }
    }
}

$products = getAllProducts();
?>

<div class="main-content">
    <div class="content-area">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-edit me-2"></i>Kelola Bundling</h2>
                <p class="text-muted mb-0">Produk Utama: <strong><?= clean($mainProduct['nama']) ?></strong> - <?= formatCurrency($mainProduct['harga']) ?></p>
            </div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Kembali
            </a>
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

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Edit Bundling untuk <?= clean($mainProduct['nama']) ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="bundlingForm">
                            <input type="hidden" name="produk_id" value="<?= $produk_id ?>">

                            <?php if (!empty($existingBundlings)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Bundling saat ini:</strong> <?= count($existingBundlings) ?> produk. 
                                    Form di bawah akan mengganti semua bundling yang ada.
                                </div>
                            <?php endif; ?>

                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="form-label mb-0">Produk Bundling <span class="text-danger">*</span></label>
                                    <button type="button" class="btn btn-sm btn-success" onclick="addBundlingItem()" id="addBtn">
                                        <i class="fas fa-plus me-1"></i>Tambah Produk
                                    </button>
                                </div>

                                <div id="bundlingContainer"></div>

                                <small class="text-muted">Maksimal 5 produk bundling. Kosongkan untuk tidak menggunakan bundling.</small>
                            </div>

                            <div id="totalPreview" class="mb-4" style="display:none;">
                                <div class="card bg-light border-success">
                                    <div class="card-body">
                                        <h6 class="text-success mb-3">
                                            <i class="fas fa-calculator me-2"></i>Preview Total Bundling
                                        </h6>
                                        <div id="totalContent"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Simpan Perubahan
                                </button>
                                <a href="index.php" class="btn btn-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-star me-2"></i>
                            Produk Utama
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;">
                                <i class="fas fa-star text-white"></i>
                            </div>
                            <div>
                                <div class="fw-bold"><?= clean($mainProduct['nama']) ?></div>
                                <div class="text-success"><?= formatCurrency($mainProduct['harga']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($existingBundlings)): ?>
                <div class="card mt-3">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Bundling Saat Ini (<?= count($existingBundlings) ?>)
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($existingBundlings as $bundle): ?>
                            <div class="d-flex justify-content-between align-items-start mb-2 pb-2 border-bottom">
                                <div class="small">
                                    <div class="fw-bold"><?= clean($bundle['nama']) ?></div>
                                    <?php if (!empty($bundle['deskripsi'])): ?>
                                        <div class="text-muted small mt-1"><?= clean(implode(' ', array_slice(explode(' ', strip_tags($bundle['deskripsi'])), 0, 10))) ?>...</div>
                                    <?php endif; ?>
                                    <div class="text-danger"><?= formatDiscount($bundle['diskon']) ?></div>
                                </div>
                                <div class="text-end small">
                                    <?= formatCurrency($bundle['harga']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="text-center mt-2">
                            <strong>Total Diskon: <?= formatCurrency(array_sum(array_column($existingBundlings, 'diskon'))) ?></strong>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card mt-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Tips Kelola Bundling
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="small mb-0">
                            <li class="mb-2">Deskripsi per item akan muncul di invoice/notifikasi</li>
                            <li class="mb-2">Form ini mengganti semua bundling yang ada</li>
                            <li class="mb-2">Kosongkan semua untuk hapus bundling</li>
                            <li class="mb-0">Maksimal 5 produk bundling</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let bundlingCount = 0;
const maxBundling = 5;
let allProducts = <?= json_encode($products) ?>;
let mainProductId = <?= $produk_id ?>;
let existingBundlings = <?= json_encode($existingBundlings) ?>;

document.addEventListener('DOMContentLoaded', function() {
    if (existingBundlings.length > 0) {
        existingBundlings.forEach(bundle => {
            addBundlingItem(bundle.produk_bundling_id, bundle.diskon, bundle.deskripsi);
        });
    } else {
        addBundlingItem();
    }
    updateTotalPreview();
});

function addBundlingItem(selectedProductId = null, selectedDiskon = null, selectedDeskripsi = null) {
    if (bundlingCount >= maxBundling) {
        alert('Maksimal 5 produk bundling');
        return;
    }

    bundlingCount++;
    const container = document.getElementById('bundlingContainer');

    // Escape deskripsi untuk HTML di textarea
    const escapedDeskripsi = selectedDeskripsi ? <?= json_encode('') ?> + selectedDeskripsi : '';

    const itemHtml = `
        <div class="card mb-3" id="bundlingItem${bundlingCount}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h6 class="card-title mb-0">Bundling Produk #${bundlingCount}</h6>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeBundlingItem(${bundlingCount})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                
                <div class="row">
                    <div class="col-md-7">
                        <label class="form-label">Produk</label>
                        <select name="bundling[${bundlingCount}][produk_id]" class="form-select bundling-product" data-index="${bundlingCount}">
                            <option value="">Pilih Produk</option>
                            ${allProducts.map(p => {
                                if (p.id == mainProductId) return '';
                                const selected = (selectedProductId && p.id == selectedProductId) ? 'selected' : '';
                                return `<option value="${p.id}" data-harga="${p.harga}" ${selected}>${p.nama} - ${formatRupiah(p.harga)}</option>`;
                            }).join('')}
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Diskon (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="bundling[${bundlingCount}][diskon]" class="form-control bundling-diskon" 
                                   data-index="${bundlingCount}" min="1000" step="1000" placeholder="0" 
                                   value="${selectedDiskon || ''}">
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label">Deskripsi Produk dalam Bundling</label>
                    <textarea name="bundling[${bundlingCount}][deskripsi]" class="form-control" rows="2" 
                              placeholder="Contoh: Ebook berisi panduan instalasi...">${selectedDeskripsi ? cleanJs(selectedDeskripsi) : ''}</textarea>
                    <small class="text-muted">Opsional.</small>
                </div>

                <div class="mt-3">
                    <div class="item-preview" id="itemPreview${bundlingCount}" style="display:none;">
                        <div class="bg-light p-2 rounded">
                            <small class="text-muted">Preview:</small>
                            <div id="itemPreviewContent${bundlingCount}"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', itemHtml);

    const productSelect = document.querySelector(`[name="bundling[${bundlingCount}][produk_id]"]`);
    const diskonInput = document.querySelector(`[name="bundling[${bundlingCount}][diskon]"]`);

    productSelect.addEventListener('change', function() {
        updateAvailableProducts();
        updateItemPreview(bundlingCount);
        updateTotalPreview();
    });

    diskonInput.addEventListener('input', function() {
        updateItemPreview(bundlingCount);
        updateTotalPreview();
    });

    updateAvailableProducts();
    updateAddButton();

    if (selectedProductId && selectedDiskon) {
        updateItemPreview(bundlingCount);
    }
}

function removeBundlingItem(index) {
    document.getElementById(`bundlingItem${index}`).remove();
    updateAvailableProducts();
    updateTotalPreview();
    updateAddButton();
}

function updateAvailableProducts() {
    const selectedProducts = [mainProductId];
    document.querySelectorAll('.bundling-product').forEach(select => {
        if (select.value) selectedProducts.push(select.value);
    });
    document.querySelectorAll('.bundling-product').forEach(select => {
        const currentValue = select.value;
        select.querySelectorAll('option').forEach(option => {
            if (option.value === '') return;
            option.disabled = (selectedProducts.includes(option.value) && option.value !== currentValue);
        });
    });
}

function updateItemPreview(index) {
    const productSelect = document.querySelector(`[name="bundling[${index}][produk_id]"]`);
    const diskonInput = document.querySelector(`[name="bundling[${index}][diskon]"]`);
    const preview = document.getElementById(`itemPreview${index}`);
    const content = document.getElementById(`itemPreviewContent${index}`);

    if (productSelect.value && diskonInput.value > 0) {
        const harga = parseInt(productSelect.options[productSelect.selectedIndex].dataset.harga);
        const diskon = parseInt(diskonInput.value);
        const final = Math.max(0, harga - diskon);

        content.innerHTML = `
            <div class="d-flex justify-content-between">
                <span>Harga Normal:</span>
                <span>${formatRupiah(harga)}</span>
            </div>
            <div class="d-flex justify-content-between text-danger">
                <span>Diskon:</span>
                <span>-${formatRupiah(diskon)}</span>
            </div>
            <div class="d-flex justify-content-between fw-bold text-success border-top pt-1">
                <span>Harga Final:</span>
                <span>${formatRupiah(final)}</span>
            </div>
        `;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
}

function updateTotalPreview() {
    const totalPreview = document.getElementById('totalPreview');
    const totalContent = document.getElementById('totalContent');
    const mainPrice = <?= $mainProduct['harga'] ?>;
    let totalNormal = mainPrice;
    let totalDiskon = 0;
    let bundlingItems = [];

    document.querySelectorAll('.bundling-product').forEach(select => {
        if (select.value) {
            const index = select.dataset.index;
            const diskonInput = document.querySelector(`[name="bundling[${index}][diskon]"]`);
            const price = parseInt(select.options[select.selectedIndex].dataset.harga);
            const diskon = parseInt(diskonInput.value) || 0;

            if (diskon > 0) {
                totalNormal += price;
                totalDiskon += diskon;
                bundlingItems.push({
                    name: select.options[select.selectedIndex].text.split(' - ')[0],
                    price: price,
                    diskon: diskon,
                    final: Math.max(0, price - diskon)
                });
            }
        }
    });

    if (bundlingItems.length > 0) {
        const totalFinal = totalNormal - totalDiskon;
        let html = `
            <div class="row mb-2">
                <div class="col-8"><strong>Produk Utama:</strong></div>
                <div class="col-4 text-end">${formatRupiah(mainPrice)}</div>
            </div>
        `;
        bundlingItems.forEach(item => {
            html += `
                <div class="row mb-1">
                    <div class="col-8">${item.name}:</div>
                    <div class="col-4 text-end">${formatRupiah(item.final)} <small class="text-muted">(-${formatRupiah(item.diskon)})</small></div>
                </div>
            `;
        });
        html += `
            <hr class="my-2">
            <div class="row">
                <div class="col-8"><strong>Total Normal:</strong></div>
                <div class="col-4 text-end">${formatRupiah(totalNormal)}</div>
            </div>
            <div class="row">
                <div class="col-8"><strong>Total Diskon:</strong></div>
                <div class="col-4 text-end text-danger">-${formatRupiah(totalDiskon)}</div>
            </div>
            <div class="row border-top pt-2">
                <div class="col-8"><strong>Total Bundling:</strong></div>
                <div class="col-4 text-end"><strong class="text-success">${formatRupiah(totalFinal)}</strong></div>
            </div>
            <div class="row">
                <div class="col-12 text-center">
                    <small class="text-success">Hemat ${formatRupiah(totalDiskon)} (${Math.round(totalDiskon/totalNormal*100)}%)</small>
                </div>
            </div>
        `;
        totalContent.innerHTML = html;
        totalPreview.style.display = 'block';
    } else {
        totalPreview.style.display = 'none';
    }
}

function updateAddButton() {
    const addBtn = document.getElementById('addBtn');
    const currentItems = document.querySelectorAll('[id^="bundlingItem"]').length;
    addBtn.disabled = currentItems >= maxBundling;
    addBtn.innerHTML = currentItems >= maxBundling 
        ? '<i class="fas fa-check me-1"></i>Maksimal 5' 
        : '<i class="fas fa-plus me-1"></i>Tambah Produk';
}

function formatRupiah(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

// Helper untuk escape di JS (fallback)
function cleanJs(str) {
    if (!str) return '';
    return str.replace(/</g, '<')
              .replace(/>/g, '>')
              .replace(/"/g, '&quot;')
              .replace(/'/g, '&#039;');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>