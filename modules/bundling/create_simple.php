<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/functions.php';

// Initialize BundlingManager
$bundlingManager = new BundlingManager();

$errors = [];
$produk_id = '';
$bundling_data = [];

// Simple CSRF functions (inline)
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Token keamanan tidak valid!';
    }
    
    $produk_id = (int)$_POST['produk_id'];
    $bundling_data = isset($_POST['bundling_data']) ? $_POST['bundling_data'] : [];
    
    // Validation
    if (empty($produk_id)) {
        $errors[] = "Produk utama harus dipilih.";
    }
    
    if (empty($bundling_data)) {
        $errors[] = "Minimal 1 produk bundling harus dipilih.";
    }
    
    if (count($bundling_data) > 5) {
        $errors[] = "Maksimal 5 produk bundling yang dapat dipilih.";
    }
    
    // Validate each bundling data
    if (!empty($bundling_data)) {
        foreach ($bundling_data as $bundling_product_id => $bundling_info) {
            // Skip empty diskon_value (untuk produk yang tidak dicentang)
            if (empty($bundling_info['diskon_value'])) {
                continue;
            }
            
            if (!is_numeric($bundling_info['diskon_value']) || $bundling_info['diskon_value'] <= 0) {
                $errors[] = "Diskon untuk produk bundling harus berupa angka positif.";
                break;
            }
            
            if ($bundling_info['diskon_type'] === 'persen' && $bundling_info['diskon_value'] > 100) {
                $errors[] = "Diskon persentase tidak boleh lebih dari 100%.";
                break;
            }
            
            // Check if product is trying to bundle with itself
            if ($produk_id == $bundling_product_id) {
                $errors[] = "Produk tidak dapat dibundling dengan dirinya sendiri.";
                break;
            }
            
            // Check for duplicate bundling
            if ($bundlingManager->bundlingExists($produk_id, $bundling_product_id)) {
                $produk_bundling = $bundlingManager->getAllProduk();
                $produk_name = '';
                foreach ($produk_bundling as $p) {
                    if ($p['id'] == $bundling_product_id) {
                        $produk_name = $p['nama'];
                        break;
                    }
                }
                $errors[] = "Bundling dengan produk '$produk_name' sudah ada.";
                break;
            }
        }
        
        // Check if we have any valid bundling data
        $valid_bundling_count = 0;
        foreach ($bundling_data as $bundling_info) {
            if (!empty($bundling_info['diskon_value'])) {
                $valid_bundling_count++;
            }
        }
        
        if ($valid_bundling_count === 0) {
            $errors[] = "Minimal 1 produk bundling harus dipilih dengan diskon yang valid.";
        }
    }
    
    // If no errors, create bundling
    if (empty($errors)) {
        $success_count = 0;
        
        foreach ($bundling_data as $bundling_product_id => $bundling_info) {
            // Skip produk yang tidak memiliki diskon value (tidak dicentang)
            if (empty($bundling_info['diskon_value'])) {
                continue;
            }
            
            $diskon_final = ($bundling_info['diskon_type'] === 'persen') ? (int)$bundling_info['diskon_value'] : (int)$bundling_info['diskon_value'];
            
            $data = [
                'produk_id' => $produk_id,
                'produk_bundling_id' => (int)$bundling_product_id,
                'diskon' => $diskon_final
            ];
            
            if ($bundlingManager->createBundling($data)) {
                $success_count++;
            }
        }
        
        if ($success_count > 0) {
            $_SESSION['success_message'] = "Berhasil membuat $success_count bundling produk!";
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Gagal membuat bundling produk.";
        }
    }
}

// Get all products for dropdown
$all_products = $bundlingManager->getAllProduk();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Bundling Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-plus me-2"></i>Tambah Bundling Produk</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Kembali
                    </a>
                </div>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Terjadi kesalahan:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-boxes me-2"></i>
                            Form Bundling Produk
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="bundlingForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                            <!-- Produk Utama -->
                            <div class="mb-4">
                                <label for="produk_id" class="form-label">
                                    <i class="fas fa-star text-warning me-1"></i>
                                    Produk Utama <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="produk_id" name="produk_id" required>
                                    <option value="">Pilih Produk Utama</option>
                                    <?php foreach ($all_products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>" 
                                                data-harga="<?php echo $product['harga']; ?>"
                                                <?php echo $produk_id == $product['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($product['nama']); ?> 
                                            (Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Pilih produk yang akan dijadikan bundling utama.</div>
                            </div>

                            <!-- Produk Bundling -->
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-boxes me-1"></i>
                                    Produk Bundling <span class="text-danger">*</span>
                                    <span class="badge bg-info ms-2">Maksimal 5 produk</span>
                                </label>
                                <div id="bundling-products-container">
                                    <!-- Dynamic content will be inserted here -->
                                </div>
                                <div class="form-text">Pilih produk yang akan dibundling dan tentukan diskon masing-masing (maksimal 5 produk).</div>
                            </div>

                            <!-- Preview Harga -->
                            <div class="mb-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-calculator me-2"></i>
                                            Preview Harga Bundling
                                        </h6>
                                        <div id="price-preview">
                                            <p class="text-muted">Pilih produk untuk melihat preview harga.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>
                                    Batal
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>
                                    Simpan Bundling
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Panduan Bundling
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Pilih 1 produk utama sebagai basis bundling
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Tambahkan maksimal 5 produk bundling
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Setiap produk bundling memiliki diskon sendiri
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Preview harga akan muncul otomatis
                            </li>
                            <li class="mb-0">
                                <i class="fas fa-check text-success me-2"></i>
                                Produk tidak dapat dibundling dengan dirinya sendiri
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Contoh Diskon
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Diskon Per Produk:</strong></p>
                        <p class="small text-muted mb-3">Setiap produk bundling dapat memiliki diskon yang berbeda.<br>
                        Contoh: Produk A (20%), Produk B (Rp 50.000)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const produkUtama = document.getElementById('produk_id');
        const bundlingContainer = document.getElementById('bundling-products-container');
        const pricePreview = document.getElementById('price-preview');
        
        let allProducts = <?php echo json_encode($all_products); ?>;
        let selectedBundlingProducts = [];
        
        // Update bundling products dropdown
        function updateBundlingProducts() {
            const selectedMainProduct = parseInt(produkUtama.value);
            bundlingContainer.innerHTML = '';
            selectedBundlingProducts = [];
            
            if (!selectedMainProduct) {
                bundlingContainer.innerHTML = '<p class="text-muted">Pilih produk utama terlebih dahulu.</p>';
                updatePricePreview();
                return;
            }
            
            // Filter products (exclude main product)
            const availableProducts = allProducts.filter(p => p.id !== selectedMainProduct);
            
            if (availableProducts.length === 0) {
                bundlingContainer.innerHTML = '<p class="text-muted">Tidak ada produk lain yang tersedia untuk bundling.</p>';
                updatePricePreview();
                return;
            }
            
            // Create container for bundling products
            const containerDiv = document.createElement('div');
            containerDiv.className = 'border rounded p-3 bg-light';
            
            const titleDiv = document.createElement('div');
            titleDiv.className = 'mb-3';
            titleDiv.innerHTML = '<h6 class="mb-0"><i class="fas fa-list me-2"></i>Pilih Produk & Tentukan Diskon</h6>';
            containerDiv.appendChild(titleDiv);
            
            // Create checkboxes for bundling products
            availableProducts.forEach(product => {
                const productDiv = document.createElement('div');
                productDiv.className = 'row align-items-center mb-3 p-2 border rounded bg-white';
                productDiv.id = `product-row-${product.id}`;
                
                productDiv.innerHTML = `
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input bundling-checkbox" 
                                   type="checkbox" 
                                   value="${product.id}" 
                                   id="bundling_${product.id}"
                                   data-harga="${product.harga}"
                                   data-nama="${product.nama}">
                            <label class="form-check-label fw-bold" for="bundling_${product.id}">
                                ${product.nama}
                            </label>
                            <div class="small text-muted">Rp ${new Intl.NumberFormat('id-ID').format(product.harga)}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="discount-input" id="discount-input-${product.id}" style="display: none;">
                            <div class="row">
                                <div class="col-5">
                                    <select class="form-select form-select-sm discount-type" name="bundling_data[${product.id}][diskon_type]">
                                        <option value="persen">%</option>
                                        <option value="rupiah">Rp</option>
                                    </select>
                                </div>
                                <div class="col-7">
                                    <input type="number" 
                                           class="form-control form-control-sm discount-value" 
                                           name="bundling_data[${product.id}][diskon_value]"
                                           placeholder="Diskon"
                                           min="1" 
                                           step="1">
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                containerDiv.appendChild(productDiv);
            });
            
            bundlingContainer.appendChild(containerDiv);
            
            // Add event listeners to checkboxes
            document.querySelectorAll('.bundling-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const productId = this.value;
                    const discountInput = document.getElementById(`discount-input-${productId}`);
                    const checkedBoxes = document.querySelectorAll('.bundling-checkbox:checked');
                    
                    // Limit to 5 products
                    if (checkedBoxes.length > 5) {
                        this.checked = false;
                        alert('Maksimal 5 produk bundling yang dapat dipilih.');
                        return;
                    }
                    
                    // Show/hide discount input
                    if (this.checked) {
                        discountInput.style.display = 'block';
                        const discountValueInput = discountInput.querySelector('.discount-value');
                        const discountTypeSelect = discountInput.querySelector('.discount-type');
                        
                        // Restore original name attributes and make required
                        discountValueInput.setAttribute('name', `bundling_data[${productId}][diskon_value]`);
                        discountTypeSelect.setAttribute('name', `bundling_data[${productId}][diskon_type]`);
                        discountValueInput.required = true;
                    } else {
                        discountInput.style.display = 'none';
                        const discountValueInput = discountInput.querySelector('.discount-value');
                        const discountTypeSelect = discountInput.querySelector('.discount-type');
                        
                        // Remove name attributes so they're not submitted
                        discountValueInput.removeAttribute('name');
                        discountTypeSelect.removeAttribute('name');
                        discountValueInput.required = false;
                        discountValueInput.value = '';
                    }
                    
                    updateSelectedProducts();
                    updatePricePreview();
                });
            });
            
            // Add event listeners to discount inputs (delegated)
            bundlingContainer.addEventListener('change', updatePricePreview);
            bundlingContainer.addEventListener('input', updatePricePreview);
            
            updatePricePreview();
        }
        
        // Update selected products array
        function updateSelectedProducts() {
            selectedBundlingProducts = [];
            document.querySelectorAll('.bundling-checkbox:checked').forEach(checkbox => {
                selectedBundlingProducts.push({
                    id: parseInt(checkbox.value),
                    nama: checkbox.dataset.nama,
                    harga: parseFloat(checkbox.dataset.harga)
                });
            });
        }
        
        // Update price preview
        function updatePricePreview() {
            const selectedMainProduct = parseInt(produkUtama.value);
            updateSelectedProducts();
            
            if (!selectedMainProduct || selectedBundlingProducts.length === 0) {
                pricePreview.innerHTML = '<p class="text-muted">Pilih produk dan tentukan diskon untuk melihat preview harga.</p>';
                return;
            }
            
            // Find main product
            const mainProduct = allProducts.find(p => p.id === selectedMainProduct);
            if (!mainProduct) return;
            
            let html = '<div class="table-responsive"><table class="table table-sm">';
            html += '<thead><tr><th>Produk</th><th>Harga Normal</th><th>Diskon</th><th>Harga Bundling</th></tr></thead><tbody>';
            
            // Main product
            html += `<tr class="table-warning">
                <td><strong>${mainProduct.nama}</strong> <span class="badge bg-warning">Utama</span></td>
                <td>Rp ${new Intl.NumberFormat('id-ID').format(mainProduct.harga)}</td>
                <td>-</td>
                <td>Rp ${new Intl.NumberFormat('id-ID').format(mainProduct.harga)}</td>
            </tr>`;
            
            let totalNormal = parseFloat(mainProduct.harga);
            let totalBundling = parseFloat(mainProduct.harga);
            
            // Bundling products
            selectedBundlingProducts.forEach(product => {
                const discountTypeSelect = document.querySelector(`select[name="bundling_data[${product.id}][diskon_type]"]`);
                const discountValueInput = document.querySelector(`input[name="bundling_data[${product.id}][diskon_value]"]`);
                
                const discountType = discountTypeSelect ? discountTypeSelect.value : 'persen';
                const discountValue = discountValueInput ? parseFloat(discountValueInput.value) || 0 : 0;
                
                let hargaSetelahDiskon = product.harga;
                let discountText = '-';
                
                if (discountValue > 0) {
                    if (discountType === 'persen') {
                        if (discountValue <= 100) {
                            hargaSetelahDiskon = product.harga * (1 - discountValue / 100);
                            discountText = `${discountValue}%`;
                        }
                    } else {
                        hargaSetelahDiskon = Math.max(0, product.harga - discountValue);
                        discountText = `Rp ${new Intl.NumberFormat('id-ID').format(discountValue)}`;
                    }
                }
                
                totalNormal += product.harga;
                totalBundling += hargaSetelahDiskon;
                
                html += `<tr>
                    <td>${product.nama} <span class="badge bg-info">Bundle</span></td>
                    <td>Rp ${new Intl.NumberFormat('id-ID').format(product.harga)}</td>
                    <td>${discountText}</td>
                    <td>Rp ${new Intl.NumberFormat('id-ID').format(hargaSetelahDiskon)}</td>
                </tr>`;
            });
            
            html += '</tbody></table></div>';
            
            // Summary
            const totalDiscount = totalNormal - totalBundling;
            html += '<hr>';
            html += `<div class="row">
                <div class="col-6"><strong>Total Harga Normal:</strong></div>
                <div class="col-6 text-end">Rp ${new Intl.NumberFormat('id-ID').format(totalNormal)}</div>
            </div>`;
            html += `<div class="row">
                <div class="col-6"><strong>Total Diskon:</strong></div>
                <div class="col-6 text-end text-danger">- Rp ${new Intl.NumberFormat('id-ID').format(totalDiscount)}</div>
            </div>`;
            html += `<div class="row border-top pt-2">
                <div class="col-6"><strong>Total Harga Bundling:</strong></div>
                <div class="col-6 text-end text-success"><strong>Rp ${new Intl.NumberFormat('id-ID').format(totalBundling)}</strong></div>
            </div>`;
            
            pricePreview.innerHTML = html;
        }
        
        // Event listeners
        produkUtama.addEventListener('change', updateBundlingProducts);
        
        // Form submission validation
        document.getElementById('bundlingForm').addEventListener('submit', function(e) {
            const checkedBoxes = document.querySelectorAll('.bundling-checkbox:checked');
            
            console.log('Form submitted with', checkedBoxes.length, 'products selected');
            
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('Pilih minimal 1 produk bundling!');
                return false;
            }
            
            // Check if all selected products have discount values
            let hasEmptyDiscount = false;
            let emptyProducts = [];
            
            checkedBoxes.forEach(checkbox => {
                const productId = checkbox.value;
                const productName = checkbox.dataset.nama;
                const discountValue = document.querySelector(`input[name="bundling_data[${productId}][diskon_value]"]`);
                
                console.log(`Product ${productName} (${productId}):`, discountValue ? discountValue.value : 'INPUT NOT FOUND');
                
                if (!discountValue || !discountValue.value || parseFloat(discountValue.value) <= 0) {
                    hasEmptyDiscount = true;
                    emptyProducts.push(productName);
                }
            });
            
            if (hasEmptyDiscount) {
                e.preventDefault();
                alert('Produk berikut belum memiliki nilai diskon yang valid: ' + emptyProducts.join(', '));
                return false;
            }
            
            console.log('Form validation passed, submitting...');
            return true;
        });
        
        // Initial setup
        updateBundlingProducts();
    });
    </script>
</body>
</html>