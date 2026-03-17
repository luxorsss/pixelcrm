<?php
$page_title = "Tambah Bundling";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/functions.php';

$errors = [];
$produk_id = (int)get('produk_id', '');
$bundling_items = [];

// Handle form submission
if (isPost()) {
    $produk_id = (int)post('produk_id');
    $bundling_data = post('bundling', []);
    $errors = [];

    if (empty($produk_id)) {
        $errors[] = 'Produk utama harus dipilih';
    }

    if (empty($bundling_data)) {
        $errors[] = 'Minimal 1 produk bundling harus dipilih';
    }

    $valid_items = [];
    if (!empty($bundling_data)) {
        foreach ($bundling_data as $item) {
            $bundle_id = (int)($item['produk_id'] ?? 0);
            $diskon = (int)($item['diskon'] ?? 0);
            $deskripsi = trim($item['deskripsi'] ?? '');

            if ($bundle_id > 0 && $diskon > 0) {
                if ($bundle_id == $produk_id) {
                    $errors[] = 'Produk tidak bisa dibundle dengan dirinya sendiri';
                    break;
                }

                if (bundlingExists($produk_id, $bundle_id)) {
                    $product = fetchRow("SELECT nama FROM produk WHERE id = ?", [$bundle_id]);
                    $errors[] = 'Bundling dengan produk "' . clean($product['nama'] ?? 'Unknown') . '" sudah ada';
                    break;
                }

                $valid_items[] = [
                    'produk_id' => $bundle_id,
                    'diskon' => $diskon,
                    'deskripsi' => $deskripsi
                ];
            }
        }
    }

    if (empty($errors) && !empty($valid_items)) {
        $success_count = 0;
        foreach ($valid_items as $item) {
            if (createBundling($produk_id, $item['produk_id'], $item['diskon'], $item['deskripsi'])) {
                $success_count++;
            }
        }

        if ($success_count > 0) {
            setMessage("Berhasil menambahkan {$success_count} bundling produk", 'success');
            redirect('index.php');
        } else {
            $errors[] = 'Gagal menyimpan semua data bundling';
        }
    }

    $bundling_items = $bundling_data;
}

$products = getAllProducts();
?>

<div class="main-content">
    <div class="content-area">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-plus me-2"></i>Tambah Multiple Bundling</h2>
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
                        <h5 class="mb-0">Form Multiple Bundling Produk</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="bundlingForm">
                            <!-- Produk Utama -->
                            <div class="mb-4">
                                <label class="form-label">Produk Utama <span class="text-danger">*</span></label>
                                <select name="produk_id" class="form-select" required id="produkUtama">
                                    <option value="">Pilih Produk Utama</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= $produk_id == $p['id'] ? 'selected' : '' ?> data-harga="<?= $p['harga'] ?>">
                                            <?= clean($p['nama']) ?> - <?= formatCurrency($p['harga']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Pilih produk yang akan menjadi basis bundling</small>
                            </div>

                            <!-- Bundling Items -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="form-label mb-0">Produk Bundling <span class="text-danger">*</span></label>
                                    <button type="button" class="btn btn-sm btn-success" onclick="addBundlingItem()" id="addBtn">
                                        <i class="fas fa-plus me-1"></i>Tambah Produk
                                    </button>
                                </div>

                                <div id="bundlingContainer">
                                    <!-- Items will be added here -->
                                </div>

                                <small class="text-muted">Maksimal 5 produk bundling. Klik "Tambah Produk" untuk menambah item.</small>
                            </div>

                            <!-- Preview Total -->
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
                                    <i class="fas fa-save me-1"></i>Simpan Semua Bundling
                                </button>
                                <a href="index.php" class="btn btn-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Tips Multiple Bundling
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="small mb-0">
                            <li class="mb-2">Pilih 1 produk utama</li>
                            <li class="mb-2">Tambah hingga 5 produk bundling</li>
                            <li class="mb-2">Set diskon & deskripsi per produk</li>
                            <li class="mb-2">Deskripsi akan muncul di invoice/notifikasi</li>
                            <li class="mb-0">Preview total muncul otomatis</li>
                        </ul>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">
                            <i class="fas fa-lightbulb me-2"></i>
                            Contoh Bundling
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="small">
                            <strong>Produk Utama:</strong> Course Premium<br>
                            <strong>Bundling 1:</strong> Ebook Marketing (Diskon Rp 25.000)<br>
                            <em>Deskripsi:</em> Panduan lengkap strategi digital marketing 2025.<br>
                            <strong>Bundling 2:</strong> Template Design (Diskon Rp 15.000)<br>
                            <em>Deskripsi:</em> 30+ template Figma siap edit untuk UMKM.
                        </div>
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

document.addEventListener('DOMContentLoaded', function() {
    addBundlingItem();
    document.getElementById('produkUtama').addEventListener('change', updateAllPreviews);
});

function addBundlingItem() {
    if (bundlingCount >= maxBundling) {
        alert('Maksimal 5 produk bundling');
        return;
    }

    bundlingCount++;
    const container = document.getElementById('bundlingContainer');

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
                        <select name="bundling[${bundlingCount}][produk_id]" class="form-select bundling-product" data-index="${bundlingCount}" required>
                            <option value="">Pilih Produk</option>
                            ${allProducts.map(p => `<option value="${p.id}" data-harga="${p.harga}">${p.nama} - ${formatRupiah(p.harga)}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Diskon (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="bundling[${bundlingCount}][diskon]" class="form-control bundling-diskon" 
                                   data-index="${bundlingCount}" min="1000" step="1000" placeholder="0" required>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label">Deskripsi Produk dalam Bundling</label>
                    <textarea name="bundling[${bundlingCount}][deskripsi]" class="form-control" rows="2" 
                              placeholder="Contoh: Ebook PDF berisi panduan instalasi..."><?= isset($bundling_items[$bundlingCount]['deskripsi']) ? clean($bundling_items[$bundlingCount]['deskripsi']) : '' ?></textarea>
                    <small class="text-muted">Opsional. Akan muncul saat menampilkan detail bundling.</small>
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

    // Re-attach event listeners
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
}

function removeBundlingItem(index) {
    document.getElementById(`bundlingItem${index}`).remove();
    updateAvailableProducts();
    updateTotalPreview();
    updateAddButton();
}

function updateAvailableProducts() {
    const produkUtama = document.getElementById('produkUtama').value;
    const selectedProducts = [produkUtama];

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
    const produkUtama = document.getElementById('produkUtama');
    const totalPreview = document.getElementById('totalPreview');
    const totalContent = document.getElementById('totalContent');

    if (!produkUtama.value) {
        totalPreview.style.display = 'none';
        return;
    }

    const mainPrice = parseInt(produkUtama.options[produkUtama.selectedIndex].dataset.harga);
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

function updateAllPreviews() {
    updateAvailableProducts();
    document.querySelectorAll('.bundling-product').forEach(select => {
        if (select.dataset.index) {
            updateItemPreview(parseInt(select.dataset.index));
        }
    });
    updateTotalPreview();
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
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>