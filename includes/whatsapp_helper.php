<?php
/**
 * WhatsApp Helper - OneSender Integration
 * Fitur lengkap untuk pengiriman WhatsApp via OneSender API
 */

/**
 * Get OneSender configuration by account name
 */
function getOneSenderConfig($account_name = 'default') {
    $config = fetchRow("SELECT * FROM onesender_config WHERE account_name = ? LIMIT 1", [$account_name]);
    
    if (!$config) {
        // Return default config jika tidak ditemukan
        return [
            'account_name' => $account_name,
            'api_key' => '',
            'api_url' => 'https://api.onesender.id/v1'
        ];
    }
    
    return $config;
}

/**
 * Format WhatsApp number to international format
 */
function formatWhatsAppNumber($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Convert to international format
    if (substr($phone, 0, 1) === '0') {
        $phone = '62' . substr($phone, 1);
    } elseif (substr($phone, 0, 2) !== '62') {
        $phone = '62' . $phone;
    }
    
    return $phone;
}

/**
 * Log WhatsApp activity
 */
function logWhatsAppActivity($data) {
    try {
        $log_dir = __DIR__ . '/../logs';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_file = $log_dir . '/whatsapp_' . date('Y-m') . '.log';
        $log_entry = json_encode(array_merge($data, ['timestamp' => date('Y-m-d H:i:s')])) . PHP_EOL;
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        error_log("Failed to log WhatsApp activity: " . $e->getMessage());
    }
}

/**
 * Raw OneSender API call - Core function dengan URL validation
 */
function sendOneSenderRaw($config, $payload, $account_name = 'default') {
    // Validasi config
    if (empty($config['api_key']) || empty($config['api_url'])) {
        return [
            'success' => false,
            'error' => 'OneSender configuration not found or incomplete',
            'account' => $account_name
        ];
    }
    
    // URL validation - pastikan URL sudah lengkap dengan /messages
    $api_url = $config['api_url'];
    if (!str_ends_with($api_url, '/messages')) {
        $api_url .= '/messages';
    }
    
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $config['api_key'],
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false, // Untuk testing, hapus di production
        CURLOPT_VERBOSE => false
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // Handle response
    if ($error) {
        return [
            'success' => false,
            'error' => 'cURL Error: ' . $error,
            'account' => $account_name,
            'debug' => [
                'url' => $api_url,
                'payload' => $payload
            ]
        ];
    }
    
    $result = json_decode($response, true);
    
    // Validasi response yang lebih akurat sesuai format OneSender
    if ($http_code == 200) {
        // Format: {"code":200,"messages":[...]}
        if (isset($result['code']) && $result['code'] == 200 && 
            isset($result['messages']) && !empty($result['messages'])) {
            return [
                'success' => true,
                'data' => $result,
                'message' => 'Message sent successfully',
                'account' => $account_name,
                'message_id' => $result['messages'][0]['id'] ?? null
            ];
        }
    }
    
    // Jika sampai sini berarti error
    return [
        'success' => false,
        'error' => $result['message'] ?? $result['error'] ?? 'HTTP Error: ' . $http_code,
        'http_code' => $http_code,
        'data' => $result,
        'account' => $account_name,
        'debug' => [
            'url' => $api_url,
            'payload' => $payload,
            'response' => $response
        ]
    ];
}

/**
 * Send WhatsApp message via OneSender API (exact format)
 */
function sendOneSenderMessage($account_name, $to, $type, $content) {
    $config = getOneSenderConfig($account_name);
    
    // Format phone number
    $to = formatWhatsAppNumber($to);
    
    // Build payload sesuai dokumentasi OneSender
    $payload = [
        'recipient_type' => 'individual',
        'to' => $to,
        'type' => $type
    ];
    
    // Add content based on type
    switch ($type) {
        case 'text':
            $payload['text'] = [
                'body' => $content['message']
            ];
            break;
            
        case 'image':
            $payload['image'] = [
                'link' => $content['image_url'],
                'caption' => $content['caption'] ?? $content['message'] ?? ''
            ];
            break;
            
        case 'document':
            $payload['document'] = [
                'link' => $content['document_url'],
                'filename' => $content['filename'] ?? 'Document'
            ];
            break;
    }
    
    $result = sendOneSenderRaw($config, $payload, $account_name);
    
    // Log activity
    logWhatsAppActivity([
        'account' => $account_name,
        'to' => $to,
        'type' => $type,
        'status' => $result['success'] ? 'success' : 'failed',
        'message' => substr($content['message'] ?? '', 0, 100),
        'error' => $result['success'] ? null : $result['error']
    ]);
    
    return $result;
}

/**
 * Helper functions untuk kemudahan penggunaan
 */
function sendWhatsAppText($phone, $message, $account_name = 'default') {
    return sendOneSenderMessage($account_name, $phone, 'text', [
        'message' => $message
    ]);
}

function sendWhatsAppImage($phone, $image_url, $caption = '', $account_name = 'default') {
    return sendOneSenderMessage($account_name, $phone, 'image', [
        'image_url' => $image_url,
        'caption' => $caption,
        'message' => $caption
    ]);
}

function sendWhatsAppDocument($phone, $document_url, $filename = 'Document', $account_name = 'default') {
    return sendOneSenderMessage($account_name, $phone, 'document', [
        'document_url' => $document_url,
        'filename' => $filename
    ]);
}

/**
 * Test connection dengan format OneSender dan debugging detail
 */
function testOneSenderConnection($account_name = 'default') {
    $config = getOneSenderConfig($account_name);
    
    if (empty($config['api_key'])) {
        return [
            'success' => false,
            'message' => 'API Key not configured for account: ' . $account_name,
            'account' => $account_name
        ];
    }
    
    if (empty($config['api_url'])) {
        return [
            'success' => false,
            'message' => 'API URL not configured for account: ' . $account_name,
            'account' => $account_name
        ];
    }
    
    // Test dengan pesan sederhana
    $test_payload = [
        'recipient_type' => 'individual',
        'to' => '6281234567890',
        'type' => 'text',
        'text' => [
            'body' => 'Test connection from CRM - please ignore'
        ]
    ];
    
    $result = sendOneSenderRaw($config, $test_payload, $account_name);
    
    return [
        'success' => $result['success'],
        'message' => $result['success'] ? 'Connection OK' : 'Connection Failed: ' . $result['error'],
        'account' => $account_name,
        'config' => [
            'api_url' => $config['api_url'],
            'has_api_key' => !empty($config['api_key']),
            'api_key_preview' => substr($config['api_key'], 0, 10) . '...'
        ],
        'debug' => $result['debug'] ?? null,
        'raw_result' => $result
    ];
}

/**
 * Debug OneSender configuration
 */
function debugOneSenderConfig($account_name = 'default') {
    $config = getOneSenderConfig($account_name);
    
    return [
        'account_name' => $account_name,
        'api_url' => $config['api_url'] ?? 'NOT SET',
        'api_key_exists' => !empty($config['api_key']),
        'api_key_length' => strlen($config['api_key'] ?? ''),
        'api_key_preview' => !empty($config['api_key']) ? substr($config['api_key'], 0, 10) . '...' : 'NOT SET',
        'full_endpoint' => ($config['api_url'] ?? '') . '/messages'
    ];
}

/**
 * Get WhatsApp statistics
 */
function getWhatsAppStats($days = 7) {
    try {
        $log_pattern = __DIR__ . '/../logs/whatsapp_*.log';
        $log_files = glob($log_pattern);
        
        $stats = [
            'total_sent' => 0,
            'success' => 0,
            'failed' => 0,
            'by_type' => ['text' => 0, 'image' => 0, 'document' => 0],
            'recent_activity' => []
        ];
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        foreach ($log_files as $log_file) {
            if (!file_exists($log_file)) continue;
            
            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                $log_data = json_decode($line, true);
                if (!$log_data || $log_data['timestamp'] < $cutoff_date) continue;
                
                $stats['total_sent']++;
                
                if ($log_data['status'] === 'success') {
                    $stats['success']++;
                } else {
                    $stats['failed']++;
                }
                
                if (isset($stats['by_type'][$log_data['type']])) {
                    $stats['by_type'][$log_data['type']]++;
                }
                
                $stats['recent_activity'][] = $log_data;
            }
        }
        
        // Sort recent activity by timestamp desc
        usort($stats['recent_activity'], function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        // Limit recent activity to 20 items
        $stats['recent_activity'] = array_slice($stats['recent_activity'], 0, 20);
        
        return $stats;
        
    } catch (Exception $e) {
        return [
            'total_sent' => 0,
            'success' => 0,
            'failed' => 0,
            'by_type' => ['text' => 0, 'image' => 0, 'document' => 0],
            'recent_activity' => [],
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Kirim pesan dengan multiple retry dan error handling
 */
function sendWhatsAppWithRetry($phone, $message, $account_name = 'default', $max_retries = 2) {
    $attempts = 0;
    $last_error = '';
    
    while ($attempts < $max_retries) {
        $attempts++;
        
        $result = sendWhatsAppText($phone, $message, $account_name);
        
        if ($result['success']) {
            return $result;
        }
        
        $last_error = $result['error'];
        
        // Jika bukan retry terakhir, tunggu sebentar
        if ($attempts < $max_retries) {
            usleep(500000); // 500ms delay
        }
    }
    
    // Semua attempt gagal
    return [
        'success' => false,
        'error' => "Failed after $max_retries attempts. Last error: $last_error",
        'account' => $account_name,
        'attempts' => $attempts
    ];
}
?>