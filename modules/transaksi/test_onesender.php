<?php
require_once __DIR__ . '/../../includes/init.php';

// Simple test script untuk OneSender
if (!isLoggedIn()) {
    redirect('../../login.php');
}

echo "<h3>OneSender Configuration Test</h3>";

// 1. Test Database Config
echo "<h4>1. Database Configuration:</h4>";
try {
    $configs = fetchAll("SELECT * FROM onesender_config");
    if (empty($configs)) {
        echo "<p style='color: red;'>❌ No OneSender configuration found!</p>";
        echo "<p>Run this SQL:</p>";
        echo "<pre>INSERT INTO onesender_config (account_name, api_key, api_url) VALUES 
('default', 'YOUR_API_KEY_HERE', 'https://api.onesender.id/v1');</pre>";
    } else {
        foreach ($configs as $config) {
            echo "<p>✅ Account: {$config['account_name']}</p>";
            echo "<p>   API URL: {$config['api_url']}</p>";
            echo "<p>   API Key: " . (strlen($config['api_key']) > 0 ? 'SET (' . strlen($config['api_key']) . ' chars)' : 'NOT SET') . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// 2. Test Template
echo "<h4>2. Template Configuration:</h4>";
try {
    $template = fetchRow("SELECT isi_pesan FROM pengaturan_pesan WHERE jenis_pesan = 'akses_produk' LIMIT 1");
    if ($template) {
        echo "<p>✅ Default template found</p>";
        echo "<pre>" . htmlspecialchars($template['isi_pesan']) . "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠️ No default template found</p>";
        echo "<p>Run this SQL:</p>";
        echo "<pre>INSERT INTO pengaturan_pesan (jenis_pesan, isi_pesan) VALUES 
('akses_produk', 'Halo {nama}!\n\nTerima kasih telah melakukan pembelian.\n\nProduk: {produk}\nLink: {link_akses}\n\nTerima kasih!');</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Template error: " . $e->getMessage() . "</p>";
}

// 3. Test API Call (jika ada POST request)
if (isPost() && post('test_phone') && post('test_message')) {
    echo "<h4>3. API Test Result:</h4>";
    
    $test_phone = clean(post('test_phone'));
    $test_message = clean(post('test_message'));
    $account = clean(post('account', 'default'));
    
    try {
        $config = fetchRow("SELECT * FROM onesender_config WHERE account_name = ? LIMIT 1", [$account]);
        
        if (!$config) {
            echo "<p style='color: red;'>❌ Configuration not found for account: $account</p>";
        } else {
            // Normalize phone
            $nowa = preg_replace('/[^0-9]/', '', $test_phone);
            if (substr($nowa, 0, 1) == '0') {
                $nowa = '62' . substr($nowa, 1);
            } elseif (substr($nowa, 0, 2) != '62') {
                $nowa = '62' . $nowa;
            }
            
            $data = [
                'recipient_type' => 'individual',
                'to' => $nowa,
                'type' => 'text',
                'text' => ['body' => $test_message]
            ];
            
            echo "<p><strong>Request URL:</strong> {$config['api_url']}/messages</p>";
            echo "<p><strong>Full URL Debug:</strong></p>";
            echo "<ul>";
            echo "<li>Base URL dari database: <code>{$config['api_url']}</code></li>";
            echo "<li>Script menambahkan: <code>/messages</code></li>";
            echo "<li>Final URL: <code>{$config['api_url']}/messages</code></li>";
            echo "</ul>";
            
            echo "<p><strong>Request Data:</strong></p>";
            echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
            
            $ch = curl_init($config['api_url']);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $config['api_key'],
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_CONNECTTIMEOUT => 5
            ]);
            
            $response = curl_exec($ch);
            $curl_error = curl_error($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);
            
            echo "<p><strong>Response:</strong></p>";
            echo "<p>HTTP Code: $http_code</p>";
            
            if ($curl_error) {
                echo "<p style='color: red;'>❌ cURL Error: $curl_error</p>";
            } else {
                echo "<p>✅ No cURL errors</p>";
            }
            
            echo "<p><strong>Response Body:</strong></p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
            
            // Parse response
            $result = json_decode($response, true);
            if ($result) {
                echo "<p><strong>Parsed Response:</strong></p>";
                echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
                
                if ($http_code == 200) {
                    echo "<p style='color: green;'>✅ API call successful!</p>";
                } else {
                    echo "<p style='color: red;'>❌ API returned error code: $http_code</p>";
                }
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Test error: " . $e->getMessage() . "</p>";
    }
}
?>

<hr>
<h4>Manual Test:</h4>
<form method="POST">
    <p>
        <label>Account:</label><br>
        <input type="text" name="account" value="default" style="width: 200px;">
    </p>
    <p>
        <label>Test Phone (e.g., 081234567890):</label><br>
        <input type="text" name="test_phone" value="<?= post('test_phone', '081234567890') ?>" style="width: 200px;">
    </p>
    <p>
        <label>Test Message:</label><br>
        <textarea name="test_message" rows="3" style="width: 300px;"><?= post('test_message', 'Test message from CRM debug') ?></textarea>
    </p>
    <button type="submit">🧪 Send Test Message</button>
</form>

<hr>
<p><a href="../">← Back to Transaksi</a></p>