<?php
require_once __DIR__ . '/../../includes/init.php';

// Set JSON response header
header('Content-Type: application/json');

if (!isPost()) {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$api_key = clean(post('api_key'));
$api_url = clean(post('api_url'));

if (empty($api_key) || empty($api_url)) {
    echo json_encode(['success' => false, 'error' => 'API Key and URL required']);
    exit;
}

try {
    // Create temporary config for testing
    $config = [
        'api_key' => $api_key,
        'api_url' => $api_url
    ];
    
    // URL validation - pastikan URL sudah lengkap dengan /messages
    $test_url = $api_url;
    if (!str_ends_with($test_url, '/messages')) {
        $test_url .= '/messages';
    }
    
    // Test payload
    $payload = [
        'recipient_type' => 'individual',
        'to' => '6281234567890',
        'type' => 'text',
        'text' => [
            'body' => 'Test connection from CRM - please ignore'
        ]
    ];
    
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $test_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_VERBOSE => false
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // Handle response
    if ($error) {
        echo json_encode([
            'success' => false,
            'error' => 'cURL Error: ' . $error,
            'debug' => [
                'url' => $test_url,
                'payload' => $payload
            ]
        ]);
        exit;
    }
    
    $result = json_decode($response, true);
    
    // Check if connection successful
    if ($http_code == 200) {
        // Check various response formats
        if (isset($result['code']) && $result['code'] == 200 && 
            isset($result['messages']) && !empty($result['messages'])) {
            
            echo json_encode([
                'success' => true,
                'message' => 'Connection successful',
                'data' => [
                    'http_code' => $http_code,
                    'message_id' => $result['messages'][0]['id'] ?? null,
                    'response' => $result
                ]
            ]);
        } else {
            // Successful HTTP but unexpected response format
            echo json_encode([
                'success' => false,
                'error' => 'Unexpected response format: ' . ($result['message'] ?? 'Unknown response'),
                'debug' => [
                    'http_code' => $http_code,
                    'response' => $result,
                    'url' => $test_url
                ]
            ]);
        }
    } else {
        // HTTP error
        echo json_encode([
            'success' => false,
            'error' => 'HTTP Error ' . $http_code . ': ' . ($result['message'] ?? $response),
            'debug' => [
                'http_code' => $http_code,
                'response' => $result,
                'url' => $test_url
            ]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Test failed: ' . $e->getMessage()
    ]);
}
?>