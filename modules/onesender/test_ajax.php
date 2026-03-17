<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/whatsapp_helper.php';

// Set JSON response header
header('Content-Type: application/json');

if (!isPost()) {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$account_name = clean(post('account'));

if (empty($account_name)) {
    echo json_encode(['success' => false, 'error' => 'Account name required']);
    exit;
}

try {
    // Test connection using WhatsApp helper
    $result = testOneSenderConnection($account_name);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Test failed: ' . $e->getMessage(),
        'account' => $account_name
    ]);
}
?>