<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Simple CSRF
function generateToken() {
    if (!isset($_SESSION['token'])) {
        $_SESSION['token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['token'];
}

function verifyToken($token) {
    return isset($_SESSION['token']) && $_SESSION['token'] === $token;
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div style='background: yellow; padding: 20px; margin: 20px;'>";
    echo "<h3>🎉 POST RECEIVED!</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    if (verifyToken($_POST['token'])) {
        echo "<p>✅ Token valid</p>";
        
        // Simple database test
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            $produk_id = (int)$_POST['produk_id'];
            $produk_bundling_id = (int)$_POST['produk_bundling_id'];
            $diskon = (int)$_POST['diskon'];
            
            $stmt = $conn->prepare("INSERT INTO bundling (produk_id, produk_bundling_id, diskon) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $produk_id, $produk_bundling_id, $diskon);
            
            if ($stmt->execute()) {
                echo "<p>✅ Data berhasil disimpan ke database!</p>";
                echo "<p>Insert ID: " . $conn->insert_id . "</p>";
            } else {
                echo "<p>❌ Database error: " . $stmt->error . "</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Exception: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>❌ Token invalid</p>";
    }
    echo "</div>";
}

// Get products
try {
    $db = new Database();
    $conn = $db->getConnection();
    $result = $conn->query("SELECT id, nama, harga FROM produk ORDER BY nama");
    $products = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Bundling</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>🧪 Test Bundling Form</h2>
        
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="token" value="<?php echo generateToken(); ?>">
                    
                    <div class="mb-3">
                        <label>Produk Utama:</label>
                        <select name="produk_id" class="form-select" required>
                            <option value="">Pilih...</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?php echo $p['id']; ?>">
                                    <?php echo htmlspecialchars($p['nama']); ?> - Rp <?php echo number_format($p['harga']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label>Produk Bundling:</label>
                        <select name="produk_bundling_id" class="form-select" required>
                            <option value="">Pilih...</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?php echo $p['id']; ?>">
                                    <?php echo htmlspecialchars($p['nama']); ?> - Rp <?php echo number_format($p['harga']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label>Diskon (Rupiah):</label>
                        <input type="number" name="diskon" class="form-control" min="1000" step="1000" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Test Submit</button>
                </form>
            </div>
        </div>
        
        <div class="mt-4">
            <h5>Current Data in Database:</h5>
            <?php
            try {
                $result = $conn->query("SELECT b.*, p1.nama as produk_utama, p2.nama as produk_bundling FROM bundling b LEFT JOIN produk p1 ON b.produk_id = p1.id LEFT JOIN produk p2 ON b.produk_bundling_id = p2.id ORDER BY b.id DESC LIMIT 5");
                echo "<table class='table table-sm'>";
                echo "<tr><th>ID</th><th>Produk Utama</th><th>Produk Bundling</th><th>Diskon</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr><td>{$row['id']}</td><td>{$row['produk_utama']}</td><td>{$row['produk_bundling']}</td><td>Rp " . number_format($row['diskon']) . "</td></tr>";
                }
                echo "</table>";
            } catch (Exception $e) {
                echo "<p>Error loading data: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>
    </div>
</body>
</html>