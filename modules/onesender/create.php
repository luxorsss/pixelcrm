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
<div class="main-content">
    <!-- Top Header -->
    <div class="top-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title mb-0">Tambah OneSender Account</h1>
                <nav class="breadcrumb">
                    <a href="<?= BASE_URL ?>" class="breadcrumb-item text-decoration-none">Dashboard</a>
                    <a href="index.php" class="breadcrumb-item text-decoration-none">OneSender</a>
                    <span class="breadcrumb-item active">Tambah Account</span>
                </nav>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <?php 
        $msg = getMessage();
        if ($msg): 
        ?>
            <div class="alert alert-<?= $msg[1] === 'error' ? 'danger' : $msg[1] ?> alert-dismissible fade show" role="alert">
                <?= $msg[0] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-plus me-2"></i>
                            Tambah OneSender Account Baru
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="createForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="account_name" class="form-label">Nama Account <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="account_name" 
                                               name="account_name" 
                                               value="<?= clean(post('account_name')) ?>" 
                                               placeholder="Contoh: main, backup, marketing"
                                               required>
                                        <small class="form-text text-muted">
                                            Nama unik untuk account ini (alfanumerik, tanpa spasi)
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="api_url" class="form-label">API URL <span class="text-danger">*</span></label>
                                        <input type="url" 
                                               class="form-control" 
                                               id="api_url" 
                                               name="api_url" 
                                               value="<?= clean(post('api_url', 'https://wamd0110.api-wa.my.id/api/v1/messages')) ?>" 
                                               placeholder="https://api.example.com/v1/messages"
                                               required>
                                        <small class="form-text text-muted">
                                            URL endpoint API WhatsApp (lengkap dengan /messages)
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="api_key" class="form-label">API Key <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="api_key" 
                                           name="api_key" 
                                           value="<?= clean(post('api_key')) ?>" 
                                           placeholder="Masukkan API Key dari provider WhatsApp"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleApiKey">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">
                                    API Key yang didapat dari dashboard provider WhatsApp Gateway
                                </small>
                            </div>

                            <!-- URL Examples -->
                            <div class="mb-4">
                                <label class="form-label">Contoh URL untuk Provider Populer:</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-body p-3">
                                                <h6 class="card-title">WhatsApp MD</h6>
                                                <code class="small">https://wamd0110.api-wa.my.id/api/v1/messages</code>
                                                <button type="button" class="btn btn-sm btn-outline-primary mt-2 copy-url" 
                                                        data-url="https://wamd0110.api-wa.my.id/api/v1/messages">
                                                    <i class="fas fa-copy me-1"></i>Copy
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-body p-3">
                                                <h6 class="card-title">OneSender</h6>
                                                <code class="small">https://api.onesender.id/v1/messages</code>
                                                <button type="button" class="btn btn-sm btn-outline-primary mt-2 copy-url"
                                                        data-url="https://api.onesender.id/v1/messages">
                                                    <i class="fas fa-copy me-1"></i>Copy
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Tips:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Pastikan API Key valid dan aktif</li>
                                        <li>URL harus diakhiri dengan <code>/messages</code></li>
                                        <li>Test koneksi setelah menyimpan untuk memastikan konfigurasi benar</li>
                                        <li>Account "default" akan digunakan jika tidak ada account spesifik untuk produk</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Kembali
                                </a>
                                <div>
                                    <button type="button" class="btn btn-outline-info me-2" id="testButton">
                                        <i class="fas fa-vial me-2"></i>Test Koneksi
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Simpan Account
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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

    // Copy URL buttons
    document.querySelectorAll('.copy-url').forEach(function(button) {
        button.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            document.getElementById('api_url').value = url;
            
            // Visual feedback
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
            this.classList.add('btn-success');
            this.classList.remove('btn-outline-primary');
            
            setTimeout(() => {
                this.innerHTML = originalText;
                this.classList.remove('btn-success');
                this.classList.add('btn-outline-primary');
            }, 2000);
        });
    });

    // Test connection before save
    document.getElementById('testButton').addEventListener('click', function() {
        const account = document.getElementById('account_name').value;
        const apiKey = document.getElementById('api_key').value;
        const apiUrl = document.getElementById('api_url').value;
        
        if (!account || !apiKey || !apiUrl) {
            alert('Mohon isi semua field terlebih dahulu');
            return;
        }
        
        const button = this;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing...';
        button.disabled = true;
        
        // Test connection via AJAX
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
                alert('✅ Koneksi berhasil! API berfungsi dengan baik.');
            } else {
                alert('❌ Koneksi gagal: ' + data.error);
            }
        })
        .catch(error => {
            alert('❌ Error testing connection: ' + error);
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
    });

    // Auto-format account name (remove spaces, special chars)
    document.getElementById('account_name').addEventListener('input', function() {
        this.value = this.value.toLowerCase().replace(/[^a-z0-9_]/g, '');
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>