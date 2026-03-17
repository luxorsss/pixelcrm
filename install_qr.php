<?php
/**
 * QR Library Auto Installer
 * Letakkan file ini di folder root (crm2/)
 * Jalankan sekali untuk download & install phpqrcode library
 */

$libUrl = 'https://sourceforge.net/projects/phpqrcode/files/phpqrcode-2010-12-07.tar.gz/download';
$targetDir = 'includes/phpqrcode/';
$tempFile = 'qr_temp.tar.gz';

echo "<h2>🔧 QR Library Installer</h2>";

// Check if already installed
if (file_exists($targetDir . 'qrlib.php')) {
    echo "<p>✅ Library sudah terinstall di: <code>{$targetDir}</code></p>";
    echo "<p><a href='modules/rekening/'>← Kembali ke Rekening</a></p>";
    exit;
}

echo "<p>📦 Downloading phpqrcode library...</p>";

// Create directory
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
    echo "<p>📁 Created directory: {$targetDir}</p>";
}

// Download with cURL or file_get_contents
$downloaded = false;

// Try cURL first
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $libUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $data) {
        file_put_contents($tempFile, $data);
        $downloaded = true;
        echo "<p>✅ Downloaded via cURL</p>";
    }
}

// Fallback to file_get_contents
if (!$downloaded && ini_get('allow_url_fopen')) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    $data = file_get_contents($libUrl, false, $context);
    if ($data) {
        file_put_contents($tempFile, $data);
        $downloaded = true;
        echo "<p>✅ Downloaded via file_get_contents</p>";
    }
}

if (!$downloaded) {
    echo "<p>❌ <strong>Download gagal!</strong> Silakan download manual:</p>";
    echo "<ol>";
    echo "<li>Download dari: <a href='{$libUrl}' target='_blank'>SourceForge</a></li>";
    echo "<li>Extract file ke folder: <code>{$targetDir}</code></li>";
    echo "<li>Pastikan file <code>{$targetDir}qrlib.php</code> ada</li>";
    echo "</ol>";
    exit;
}

// Extract tar.gz
echo "<p>📦 Extracting archive...</p>";

try {
    // Try PharData (PHP 5.3+)
    if (class_exists('PharData')) {
        $phar = new PharData($tempFile);
        $phar->extractTo('.', null, true);
        
        // Move files from phpqrcode folder to our target
        if (is_dir('phpqrcode')) {
            $files = scandir('phpqrcode');
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $srcPath = 'phpqrcode/' . $file;
                    $destPath = $targetDir . $file;
                    
                    if (is_file($srcPath)) {
                        copy($srcPath, $destPath);
                    } elseif (is_dir($srcPath)) {
                        if (!is_dir($destPath)) mkdir($destPath, 0755, true);
                        // Copy directory contents recursively
                        $iterator = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($srcPath),
                            RecursiveIteratorIterator::SELF_FIRST
                        );
                        foreach ($iterator as $item) {
                            if ($item->isDir()) {
                                $dir = $destPath . '/' . $iterator->getSubPathName();
                                if (!is_dir($dir)) mkdir($dir, 0755, true);
                            } else {
                                copy($item, $destPath . '/' . $iterator->getSubPathName());
                            }
                        }
                    }
                }
            }
            
            // Cleanup
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator('phpqrcode'),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    rmdir($item);
                } else {
                    unlink($item);
                }
            }
            rmdir('phpqrcode');
        }
        
        echo "<p>✅ Extracted successfully</p>";
    } else {
        throw new Exception("PharData not available");
    }
} catch (Exception $e) {
    echo "<p>❌ <strong>Extract gagal:</strong> {$e->getMessage()}</p>";
    echo "<p>Silakan extract manual file <code>{$tempFile}</code> ke folder <code>{$targetDir}</code></p>";
}

// Cleanup temp file
if (file_exists($tempFile)) {
    unlink($tempFile);
}

// Verify installation
if (file_exists($targetDir . 'qrlib.php')) {
    echo "<p>🎉 <strong>Installation berhasil!</strong></p>";
    echo "<p>✅ Library tersedia di: <code>{$targetDir}qrlib.php</code></p>";
    echo "<p><a href='modules/rekening/' class='btn btn-primary'>🏦 Test di Rekening Module</a></p>";
    
    // Test QR generation
    echo "<h3>🧪 Test QR Generation</h3>";
    try {
        require_once $targetDir . 'qrlib.php';
        
        $testDir = 'assets/qr/';
        if (!is_dir($testDir)) mkdir($testDir, 0755, true);
        
        $testFile = $testDir . 'test.png';
        QRcode::png('Test QR Code', $testFile, QR_ECLEVEL_M, 6, 2);
        
        if (file_exists($testFile)) {
            echo "<p>✅ QR Generation test successful!</p>";
            echo "<p><img src='{$testFile}' alt='Test QR' style='border: 1px solid #ddd; padding: 10px;'></p>";
        } else {
            echo "<p>❌ QR Generation test failed</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Test error: {$e->getMessage()}</p>";
    }
} else {
    echo "<p>❌ <strong>Installation gagal!</strong> File qrlib.php tidak ditemukan.</p>";
    echo "<p>Silakan download & extract manual dari SourceForge.</p>";
}

echo "<hr>";
echo "<p><small>Installer ini hanya perlu dijalankan sekali. Setelah selesai, file ini bisa dihapus.</small></p>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
.btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
.btn:hover { background: #0056b3; }
</style>