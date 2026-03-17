<?php
/**
 * All-in-One Init - Super Simple & Fast
 */

// Set timezone Indonesia
date_default_timezone_set('Asia/Jakarta');

// Start output buffering FIRST
ob_start();

// Basic config
define('APP_NAME', 'PixelCRM');
define('APP_VERSION', '2.3.0');
define('BASE_URL', 'https://app.edumuslim.my.id/pixelcrm/');
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'wegqxcgv_crm'); // Sesuai dengan yang Anda gunakan
define('DB_USER', 'wegqxcgv_crm');
define('DB_PASS', '_N8t8mu07');

// Start session
session_start();

// Simple DB connection
function db() {
    static $conn;
    if (!$conn) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

// Quick query functions
// function query($sql, $params = []) {
//     $stmt = db()->prepare($sql);
//     if ($params) $stmt->bind_param(str_repeat('s', count($params)), ...$params);
//     $stmt->execute();
//     return $stmt->get_result();
// }

// function execute($sql, $params = []) {
//     $stmt = db()->prepare($sql);
//     if ($params) $stmt->bind_param(str_repeat('s', count($params)), ...$params);
//     return $stmt->execute();
// }

function query($sql, $params = []) {
    $stmt = db()->prepare($sql);
    if ($params) {
        // Auto-detect parameter types
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

function execute($sql, $params = []) {
    $stmt = db()->prepare($sql);
    if ($params) {
        // Auto-detect parameter types
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        $stmt->bind_param($types, ...$params);
    }
    return $stmt->execute();
}

function fetchRow($sql, $params = []) {
    $result = query($sql, $params);
    return $result->fetch_assoc();
}

function fetchAll($sql, $params = []) {
    $result = query($sql, $params);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Simple helpers
function redirect($url) { 
    ob_clean(); // Clear output buffer
    header("Location: $url"); 
    exit; 
}
function formatCurrency($amount) { 
    // Handle null, empty, atau non-numeric values
    if ($amount === null || $amount === '' || !is_numeric($amount)) {
        $amount = 0;
    }
    return 'Rp ' . number_format((float)$amount, 0, ',', '.'); 
}
function isLoggedIn() { return isset($_SESSION['user_id']); }
function setMessage($msg, $type = 'success') { $_SESSION['message'] = [$msg, $type]; }
function getMessage() { 
    $msg = $_SESSION['message'] ?? null; 
    unset($_SESSION['message']); 
    return $msg; 
}

// Additional helpers
function clean($input) { 
    if ($input === null) return '';
    return is_array($input) ? array_map('clean', $input) : trim(htmlspecialchars($input)); 
}
function post($key, $default = null) { return $_POST[$key] ?? $default; }
function get($key, $default = null) { return $_GET[$key] ?? $default; }
function isPost() { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
function formatDate($date, $format = 'd/m/Y') { return $date ? date($format, strtotime($date)) : '-'; }
function validatePhone($phone) { return preg_match('/^(\+62|62|0)8[1-9][0-9]{6,11}$/', $phone); }
function validateEmail($email) { return filter_var($email, FILTER_VALIDATE_EMAIL); }
function statusBadge($status) {
    $badges = ['pending'=>'warning','diproses'=>'info','selesai'=>'success','batal'=>'danger'];
    $class = $badges[strtolower($status)] ?? 'secondary';
    return "<span class='badge bg-$class'>$status</span>";
}
function whatsappLink($phone, $msg = '') {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) === '0') $phone = '62' . substr($phone, 1);
    return 'https://wa.me/' . $phone . ($msg ? '?text=' . urlencode($msg) : '');
}
function truncateText($text, $length = 100) {
    if (empty($text)) return '';
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}
function pagination($current, $total, $url) {
    if ($total <= 1) return '';
    $html = '<nav><ul class="pagination justify-content-center">';
    if ($current > 1) $html .= '<li class="page-item"><a class="page-link" href="'.$url.'&page='.($current-1).'">‹</a></li>';
    $start = max(1, $current - 2); $end = min($total, $current + 2);
    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $current ? ' active' : '';
        $html .= '<li class="page-item'.$active.'"><a class="page-link" href="'.$url.'&page='.$i.'">'.$i.'</a></li>';
    }
    if ($current < $total) $html .= '<li class="page-item"><a class="page-link" href="'.$url.'&page='.($current+1).'">›</a></li>';
    return $html . '</ul></nav>';
}

// Auth functions
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
function loginUser($username, $password) {
    $user = fetchRow("SELECT * FROM users WHERE username = ?", [$username]);
    if ($user && verifyPassword($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['last_activity'] = time();
        return true;
    }
    return false;
}
function registerUser($username, $password) {
    // Check if username exists
    $existing = fetchRow("SELECT id FROM users WHERE username = ?", [$username]);
    if ($existing) return false;
    
    $hashedPassword = hashPassword($password);
    return execute("INSERT INTO users (username, password) VALUES (?, ?)", [$username, $hashedPassword]);
}
function logoutUser() {
    session_destroy();
    redirect('login.php');
}
function requireAuth() {
    if (!isLoggedIn()) {
        redirect('/pixelcrm/login.php');
    }
}
?>