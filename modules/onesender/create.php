<?php
$page_title = "Tambah OneSender Account";
require_once __DIR__ . '/../../includes/init.php';

// Process form submission
if (isPost()) {
    $account_name = clean(post('account_name'));
    $api_key = clean(post('api_key'));
    $api_url = clean(post('api_url'));
    
    // Validation
    $errors = [];
    
    if (empty($account_name)) {
        $errors[] = "Nama account wajib diisi";
    } else {
        // Check if account name already exists
        $existing = fetchRow("SELECT id FROM onesender_config WHERE account_name = ?", [$account_name]);
        if ($existing) {
            $errors[] = "Nama account sudah digunakan";
        }
    }
    
    if (empty($api_key)) {
        $errors[] = "API Key wajib diisi";
    }
    
    if (empty($api_url)) {
        $errors[] = "API URL wajib diisi";
    } elseif (!filter_var($api_url, FILTER_VALIDATE_URL)) {
        $errors[] = "Format API URL tidak valid";
    }
    
    if (empty($errors)) {
        try {
            // Insert new account
            if (execute("INSERT INTO onesender_config (account_name, api_key, api_url) VALUES (?, ?, ?)", 
                       [$account_name, $api_key, $api_url])) {
                setMessage("Account OneSender '$account_name' berhasil ditambahkan", 'success');
                redirect('index.php');
            } else {
                $errors[] = "Gagal menyimpan data";
            }
        } catch (Exception $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        setMessage(implode('<br>', $errors), 'error');
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content dashboard-wrapper flex-grow-1">
    <div class="form-container" style="max-width: 800px; margin: 0 auto;">
        
        <div class="dash-header mb-4">
            <a href="index.php" class="text-muted text-decoration-none fw-bold mb-2 d-inline-block" style="font-size: 0.85rem;">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Device
            </a>
            <h1 class="dash-title d-flex align-items-center gap-2">
                <i class="fas fa-plug text-primary"></i> Hubungkan Device Baru
            </h1>
            <p class="text-muted mt-1 fw-medium" style="font-size: 0.95rem;">
                Tambahkan konfigurasi API Key dan Endpoint untuk mengaktifkan pengiriman WhatsApp otomatis.
            </p>
        </div>

        <?php if ($msg = getMessage()): ?>
            <div class="alert alert-editorial mb-4" style="border-left-color: <?= $msg[1] === 'error' || $msg[1] === 'danger' ? 'var(--danger-color)' : 'var(--success-color)' ?>;">
                <div class="d-flex align-items-center">
                    <i class="fas <?= $msg[1] === 'error' || $msg[1] === 'danger' ? 'fa-exclamation-circle text-danger' : 'fa-check-circle text-success' ?> me-2 fs-5"></i>
                    <span class="fw-bold text-dark"><?= clean($msg[0]) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="panel-editorial mb-5">
            <form method="POST" id="createForm">
                
                <h3 class="panel-title border-bottom pb-3 mb-4"><i class="fas fa-server text-secondary me-2"></i> Identitas Device</h3>
                
                <div class="mb-4">
                    <label for="account_name" class="form-label text-dark fw-bold" style="font-size: 0.85rem;">Nama Account / Device <span class="text-danger">*</span></label>
                    <input type="text" class="form-control-editorial fw-bold text-primary" 
                           id="account_name" name="account_name" value="<?= clean(post('account_name')) ?>" 
                           placeholder="Contoh: main, marketing, cs_1" required
                           style="letter-spacing: 0.05em;">
                    <div class="text-muted mt-2" style="font-size: 0.75rem;">
                        <i class="fas fa-info-circle me-1"></i> Digunakan sebagai ID (Hanya huruf kecil, angka, dan underscore).
                    </div>
                </div>

                <h3 class="panel-title border-bottom pb-3 mb-4 mt-5"><i class="fas fa-network-wired text-secondary me-2"></i> Konfigurasi API</h3>

                <div class="mb-4">
                    <label class="form-label text-dark fw-bold mb-2" style="font-size: 0.85rem;">Pilih Provider Cepat (Klik untuk menyalin):</label>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 border rounded-3 bg-light integration-card" style="cursor: pointer; transition: all 0.2s;" data-url="https://wamd0110.api-wa.my.id/api/v1/messages">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="m-0 fw-bold text-dark"><i class="fas fa-robot text-success me-2"></i> WhatsApp MD</h6>
                                    <span class="badge-clean bg-white border text-primary px-2 select-badge" style="font-size: 0.7rem;">Pilih</span>
                                </div>
                                <div class="text-muted" style="font-size: 0.7rem; font-family: monospace; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    https://wamd0110.api-wa.my.id/api/v1/messages
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded-3 bg-light integration-card" style="cursor: pointer; transition: all 0.2s;" data-url="https://api.onesender.id/v1/messages">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="m-0 fw-bold text-dark"><i class="fas fa-paper-plane text-info me-2"></i> OneSender ID</h6>
                                    <span class="badge-clean bg-white border text-primary px-2 select-badge" style="font-size: 0.7rem;">Pilih</span>
                                </div>
                                <div class="text-muted" style="font-size: 0.7rem; font-family: monospace; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    https://api.onesender.id/v1/messages
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="api_url" class="form-label text-dark fw-bold" style="font-size: 0.85rem;">Endpoint URL <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <i class="fas fa-link position-absolute text-muted" style="top: 50%; left: 16px; transform: translateY(-50%);"></i>
                        <input type="url" class="form-control-editorial fw-medium" 
                               id="api_url" name="api_url" value="<?= clean(post('api_url', 'https://wamd0110.api-wa.my.id/api/v1/messages')) ?>" 
                               placeholder="https://api.example.com/v1/messages" required
                               style="padding-left: 45px; font-family: monospace; font-size: 0.85rem; color: #4B5563;">
                    </div>
                    <div class="text-muted mt-2" style="font-size: 0.75rem;">Harus berupa URL lengkap yang diakhiri dengan <code>/messages</code>.</div>
                </div>

                <div class="mb-5">
                    <label for="api_key" class="form-label text-dark fw-bold" style="font-size: 0.85rem;">API Key Rahasia <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <i class="fas fa-key position-absolute text-muted" style="top: 50%; left: 16px; transform: translateY(-50%);"></i>
                        <input type="password" class="form-control-editorial fw-bold" 
                               id="api_key" name="api_key" value="<?= clean(post('api_key')) ?>" 
                               placeholder="Masukkan token/API key dari provider" required
                               style="padding-left: 45px; padding-right: 50px; font-family: monospace; font-size: 0.85rem;">
                        <button class="btn btn-link position-absolute text-muted" type="button" id="toggleApiKey" style="top: 50%; right: 5px; transform: translateY(-50%); text-decoration: none;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 p-3 bg-light rounded-3 border">
                    <div class="d-flex align-items-center gap-3 w-100">
                        <button type="button" class="btn btn-light border fw-bold text-dark rounded-pill px-4" id="testButton">
                            <i class="fas fa-wifi text-primary me-2"></i> Uji Koneksi
                        </button>
                        <div id="testResult" style="font-size: 0.85rem; font-weight: 600;"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-5 w-100 w-md-auto btn-submit" style="box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);">
                        <i class="fas fa-save me-2"></i> Simpan Device
                    </button>
                </div>
                
            </form>
        </div>
    </div>
</div>

<style>
/* Hover animation for integration cards */
.integration-card:hover {
    border-color: #3B82F6 !important;
    background: #EFF6FF !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}
.integration-card:hover .select-badge {
    background: #3B82F6 !important;
    color: white !important;
    border-color: #3B82F6 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Toggle API Key visibility
    document.getElementById('toggleApiKey').addEventListener('click', function() {
        const apiKeyField = document.getElementById('api_key');
        const icon = this.querySelector('i');
        
        if (apiKeyField.type === 'password') {
            apiKeyField.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            apiKeyField.type = 'password';
            icon.className = 'fas fa-eye';
        }
    });

    // Preset URL Integration Cards (Click to fill)
    document.querySelectorAll('.integration-card').forEach(function(card) {
        card.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            const urlInput = document.getElementById('api_url');
            
            // Fill value
            urlInput.value = url;
            
            // Visual feedback (Highlight Input)
            urlInput.style.transition = 'all 0.3s';
            urlInput.style.backgroundColor = '#ECFDF5';
            urlInput.style.borderColor = '#10B981';
            
            // Change badge text temporarily
            const badge = this.querySelector('.select-badge');
            const originalBadgeHTML = badge.innerHTML;
            badge.innerHTML = '<i class="fas fa-check"></i> Disalin';
            badge.classList.replace('bg-white', 'bg-success');
            badge.classList.replace('text-primary', 'text-white');
            
            setTimeout(() => {
                urlInput.style.backgroundColor = '';
                urlInput.style.borderColor = '';
                badge.innerHTML = originalBadgeHTML;
                badge.classList.replace('bg-success', 'bg-white');
                badge.classList.replace('text-white', 'text-primary');
            }, 1500);
        });
    });

    // Test connection (Smart Inline Feedback)
    document.getElementById('testButton').addEventListener('click', function() {
        const apiKey = document.getElementById('api_key').value;
        const apiUrl = document.getElementById('api_url').value;
        const resultDiv = document.getElementById('testResult');
        
        if (!apiKey || !apiUrl) {
            resultDiv.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> API URL & Key wajib diisi!</span>';
            return;
        }
        
        const button = this;
        const originalText = button.innerHTML;
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin text-primary me-2"></i> Menghubungi...';
        button.disabled = true;
        resultDiv.innerHTML = '';
        
        fetch('test_connection.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'api_key=' + encodeURIComponent(apiKey) + '&api_url=' + encodeURIComponent(apiUrl)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i> Koneksi Berhasil! API Valid.</span>';
                document.getElementById('api_key').style.borderColor = '#10B981';
            } else {
                resultDiv.innerHTML = '<span class="text-danger" title="'+ data.error +'"><i class="fas fa-times-circle me-1"></i> Gagal terhubung. Cek API/URL.</span>';
                document.getElementById('api_key').style.borderColor = '#EF4444';
            }
        })
        .catch(error => {
            resultDiv.innerHTML = '<span class="text-danger"><i class="fas fa-wifi me-1"></i> Masalah Jaringan / Server Error.</span>';
            console.error(error);
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
    });

    // Auto-format account name (Sleek Real-time validation)
    document.getElementById('account_name').addEventListener('input', function() {
        const original = this.value;
        const formatted = original.toLowerCase().replace(/[^a-z0-9_]/g, '');
        if(original !== formatted) {
            this.value = formatted;
        }
    });

    // Submit Loading UX
    document.getElementById('createForm').addEventListener('submit', function() {
        const btn = this.querySelector('.btn-submit');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Menyimpan...';
        btn.style.opacity = '0.8';
        btn.style.pointerEvents = 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>