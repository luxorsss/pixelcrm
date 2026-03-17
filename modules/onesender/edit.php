<?php
$page_title = "Edit OneSender Account";
require_once __DIR__ . '/../../includes/init.php';

// Get account ID
$id = (int)get('id');
if (!$id) {
    setMessage('ID account tidak valid', 'error');
    redirect('index.php');
}

// Get existing account data
$account = fetchRow("SELECT * FROM onesender_config WHERE id = ?", [$id]);
if (!$account) {
    setMessage('Account tidak ditemukan', 'error');
    redirect('index.php');
}

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
        // Check if account name already exists (except current)
        $existing = fetchRow("SELECT id FROM onesender_config WHERE account_name = ? AND id != ?", [$account_name, $id]);
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
            // Update account
            if (execute("UPDATE onesender_config SET account_name = ?, api_key = ?, api_url = ? WHERE id = ?", 
                       [$account_name, $api_key, $api_url, $id])) {
                setMessage("Account OneSender '$account_name' berhasil diupdate", 'success');
                redirect('index.php');
            } else {
                $errors[] = "Gagal mengupdate data";
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
                <h1 class="page-title mb-0">Edit OneSender Account</h1>
                <nav class="breadcrumb">
                    <a href="<?= BASE_URL ?>" class="breadcrumb-item text-decoration-none">Dashboard</a>
                    <a href="index.php" class="breadcrumb-item text-decoration-none">OneSender</a>
                    <span class="breadcrumb-item active">Edit <?= clean($account['account_name']) ?></span>
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
                            <i class="fas fa-edit me-2"></i>
                            Edit Account: <?= clean($account['account_name']) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="editForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="account_name" class="form-label">Nama Account <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="account_name" 
                                               name="account_name" 
                                               value="<?= clean(post('account_name', $account['account_name'])) ?>" 
                                               placeholder="Contoh: main, backup, marketing"
                                               <?= $account['account_name'] === 'default' ? 'readonly' : '' ?>
                                               required>
                                        <?php if ($account['account_name'] === 'default'): ?>
                                            <small class="form-text text-warning">
                                                <i class="fas fa-lock me-1"></i>Nama default account tidak dapat diubah
                                            </small>
                                        <?php else: ?>
                                            <small class="form-text text-muted">
                                                Nama unik untuk account ini (alfanumerik, tanpa spasi)
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="api_url" class="form-label">API URL <span class="text-danger">*</span></label>
                                        <input type="url" 
                                               class="form-control" 
                                               id="api_url" 
                                               name="api_url" 
                                               value="<?= clean(post('api_url', $account['api_url'])) ?>" 
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
                                           value="<?= clean(post('api_key', $account['api_key'])) ?>" 
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

                            <!-- Current Status -->
                            <div class="mb-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Status Saat Ini</h6>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <strong>Account Name:</strong><br>
                                                <span class="badge bg-primary"><?= clean($account['account_name']) ?></span>
                                            </div>
                                            <div class="col-md-4">
                                                <strong>API Key:</strong><br>
                                                <?php if (!empty($account['api_key'])): ?>
                                                    <span class="badge bg-success">SET (<?= strlen($account['api_key']) ?> chars)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">NOT SET</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Test Koneksi:</strong><br>
                                                <button type="button" class="btn btn-sm btn-outline-info" id="testCurrentButton">
                                                    <i class="fas fa-wifi me-1"></i>Test Sekarang
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
                                        <li>Perubahan akan mempengaruhi semua produk yang menggunakan account ini</li>
                                        <li>Test koneksi setelah mengubah API Key atau URL</li>
                                        <li>Backup konfigurasi lama sebelum mengubah setting penting</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Kembali
                                </a>
                                <div>
                                    <button type="button" class="btn btn-outline-info me-2" id="testButton">
                                        <i class="fas fa-vial me-2"></i>Test Koneksi Baru
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Account
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

    // Test current configuration
    document.getElementById('testCurrentButton').addEventListener('click', function() {
        const button = this;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Testing...';
        button.disabled = true;
        
        // Test with current account name
        fetch('test_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'account=' + encodeURIComponent('<?= $account['account_name'] ?>')
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Koneksi saat ini berhasil!');
            } else {
                alert('❌ Koneksi saat ini gagal: ' + data.error);
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

    // Test new configuration before save
    document.getElementById('testButton').addEventListener('click', function() {
        const apiKey = document.getElementById('api_key').value;
        const apiUrl = document.getElementById('api_url').value;
        
        if (!apiKey || !apiUrl) {
            alert('Mohon isi API Key dan URL terlebih dahulu');
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
                alert('✅ Konfigurasi baru berhasil! API berfungsi dengan baik.');
            } else {
                alert('❌ Konfigurasi baru gagal: ' + data.error);
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

    // Auto-format account name (if not default)
    <?php if ($account['account_name'] !== 'default'): ?>
    document.getElementById('account_name').addEventListener('input', function() {
        this.value = this.value.toLowerCase().replace(/[^a-z0-9_]/g, '');
    });
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>