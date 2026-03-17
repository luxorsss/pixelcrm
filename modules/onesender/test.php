<?php
$page_title = "Test OneSender";
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/whatsapp_helper.php';

$account_name = clean(get('account', 'default'));

// Get account configuration
$account = fetchRow("SELECT * FROM onesender_config WHERE account_name = ?", [$account_name]);
if (!$account) {
    setMessage('Account tidak ditemukan', 'error');
    redirect('index.php');
}

// Process manual test
$test_result = null;
if (isPost() && post('test_phone') && post('test_message')) {
    $test_phone = clean(post('test_phone'));
    $test_message = clean(post('test_message'));
    
    try {
        $test_result = sendWhatsAppText($test_phone, $test_message, $account_name);
    } catch (Exception $e) {
        $test_result = [
            'success' => false,
            'error' => $e->getMessage()
        ];
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
                <h1 class="page-title mb-0">Test OneSender: <?= clean($account_name) ?></h1>
                <nav class="breadcrumb">
                    <a href="<?= BASE_URL ?>" class="breadcrumb-item text-decoration-none">Dashboard</a>
                    <a href="index.php" class="breadcrumb-item text-decoration-none">OneSender</a>
                    <span class="breadcrumb-item active">Test <?= clean($account_name) ?></span>
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

        <div class="row">
            <!-- Configuration Info -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-cog me-2"></i>Konfigurasi Account
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Account Name:</strong><br>
                            <span class="badge bg-primary"><?= clean($account['account_name']) ?></span>
                        </div>
                        
                        <div class="mb-3">
                            <strong>API URL:</strong><br>
                            <code class="small"><?= clean($account['api_url']) ?></code>
                        </div>
                        
                        <div class="mb-3">
                            <strong>API Key:</strong><br>
                            <?php if (!empty($account['api_key'])): ?>
                                <span class="badge bg-success">SET (<?= strlen($account['api_key']) ?> chars)</span>
                                <br><small class="text-muted"><?= substr($account['api_key'], 0, 10) ?>...</small>
                            <?php else: ?>
                                <span class="badge bg-danger">NOT SET</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Connection Test:</strong><br>
                            <button class="btn btn-outline-info btn-sm" id="quickTestBtn">
                                <i class="fas fa-wifi me-1"></i>Quick Test
                            </button>
                        </div>
                        
                        <div id="quickTestResult"></div>
                        
                        <hr>
                        
                        <div class="text-center">
                            <a href="edit.php?id=<?= $account['id'] ?>" class="btn btn-outline-warning btn-sm">
                                <i class="fas fa-edit me-1"></i>Edit Config
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>Kembali
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <?php 
                $stats = getWhatsAppStats(3);
                $recent_activity = array_filter($stats['recent_activity'], function($activity) use ($account_name) {
                    return $activity['account'] === $account_name;
                });
                if (!empty($recent_activity)): 
                ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-history me-2"></i>Aktivitas Terbaru
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php foreach (array_slice($recent_activity, 0, 5) as $activity): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <small class="text-muted"><?= date('H:i', strtotime($activity['timestamp'])) ?></small><br>
                                <small><?= truncateText($activity['message'], 30) ?></small>
                            </div>
                            <div>
                                <?php if ($activity['status'] === 'success'): ?>
                                    <span class="badge bg-success"><i class="fas fa-check"></i></span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="fas fa-times"></i></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Manual Test Form -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-paper-plane me-2"></i>Test Manual WhatsApp
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="testForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="test_phone" class="form-label">Nomor WhatsApp Tujuan</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="test_phone" 
                                               name="test_phone" 
                                               value="<?= clean(post('test_phone', '081234567890')) ?>" 
                                               placeholder="081234567890"
                                               required>
                                        <small class="form-text text-muted">Format: 08xxxx atau +628xxxx</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="account_select" class="form-label">Account Test</label>
                                        <select class="form-select" id="account_select" onchange="changeAccount(this.value)">
                                            <?php 
                                            $all_accounts = fetchAll("SELECT account_name FROM onesender_config ORDER BY account_name");
                                            foreach ($all_accounts as $acc): 
                                            ?>
                                                <option value="<?= $acc['account_name'] ?>" <?= $acc['account_name'] === $account_name ? 'selected' : '' ?>>
                                                    <?= clean($acc['account_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="test_message" class="form-label">Pesan Test</label>
                                <textarea class="form-control" 
                                          id="test_message" 
                                          name="test_message" 
                                          rows="4" 
                                          placeholder="Ketik pesan test di sini..."
                                          required><?= clean(post('test_message', 'Test message dari CRM OneSender - ' . date('d/m/Y H:i:s'))) ?></textarea>
                                <small class="form-text text-muted">Pesan akan dikirim ke nomor yang diisi di atas</small>
                            </div>
                            
                            <div class="mb-4">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Perhatian:</strong> Test ini akan mengirim pesan WhatsApp asli ke nomor yang dimasukkan. 
                                    Pastikan nomor benar dan Anda memiliki izin untuk mengirim pesan ke nomor tersebut.
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="setTestMessage('simple')">
                                        Pesan Sederhana
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="setTestMessage('template')">
                                        Template Akses
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="setTestMessage('emoji')">
                                        Dengan Emoji
                                    </button>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Kirim Test Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Test Result -->
                <?php if ($test_result): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-<?= $test_result['success'] ? 'check-circle text-success' : 'times-circle text-danger' ?> me-2"></i>
                            Hasil Test
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if ($test_result['success']): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Berhasil!</strong> Pesan berhasil dikirim.
                                <?php if (isset($test_result['message_id'])): ?>
                                    <br><small>Message ID: <code><?= $test_result['message_id'] ?></code></small>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-times-circle me-2"></i>
                                <strong>Gagal!</strong> <?= clean($test_result['error']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Debug Info -->
                        <details class="mt-3">
                            <summary class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-bug me-1"></i>Debug Info
                            </summary>
                            <div class="mt-2">
                                <pre class="bg-light p-3 rounded"><code><?= json_encode($test_result, JSON_PRETTY_PRINT) ?></code></pre>
                            </div>
                        </details>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quick test button
    document.getElementById('quickTestBtn').addEventListener('click', function() {
        const button = this;
        const resultDiv = document.getElementById('quickTestResult');
        const originalText = button.innerHTML;
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Testing...';
        button.disabled = true;
        resultDiv.innerHTML = '';
        
        fetch('test_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'account=' + encodeURIComponent('<?= $account_name ?>')
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = '<div class="alert alert-success alert-sm mt-2"><i class="fas fa-check me-1"></i>Connection OK</div>';
            } else {
                resultDiv.innerHTML = '<div class="alert alert-danger alert-sm mt-2"><i class="fas fa-times me-1"></i>' + data.error + '</div>';
            }
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="alert alert-danger alert-sm mt-2"><i class="fas fa-times me-1"></i>Test Error</div>';
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
    });
});

function changeAccount(account) {
    window.location.href = 'test.php?account=' + encodeURIComponent(account);
}

function setTestMessage(type) {
    const messageField = document.getElementById('test_message');
    const now = new Date().toLocaleString('id-ID');
    
    switch(type) {
        case 'simple':
            messageField.value = `Test message dari CRM OneSender - ${now}`;
            break;
        case 'template':
            messageField.value = `Halo!\n\nTerima kasih telah melakukan pembelian.\n\n📦 Produk: Test Produk\n🔗 Link: https://example.com/access\n\nSilakan simpan link tersebut dengan baik.\n\nTerima kasih!\n\n_Test dari CRM - ${now}_`;
            break;
        case 'emoji':
            messageField.value = `🎉 Test Message CRM 🎉\n\n✅ Koneksi berhasil\n📱 Account: <?= $account_name ?>\n⏰ ${now}\n\n🚀 System ready to go!`;
            break;
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>