<?php
// Simpan sebagai modules/bundling/test.php
session_start();

echo "<h1>Basic Test</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>POST Data Received!</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Simple Form Test</h2>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Test Input</label>
                <input type="text" name="test" class="form-control" value="hello world">
            </div>
            
            <button type="submit" class="btn btn-primary">Submit Test</button>
        </form>
        
        <hr>
        <p><strong>Server Info:</strong></p>
        <ul>
            <li>PHP Version: <?php echo PHP_VERSION; ?></li>
            <li>Method: <?php echo $_SERVER['REQUEST_METHOD']; ?></li>
            <li>Script: <?php echo $_SERVER['SCRIPT_NAME']; ?></li>
        </ul>
    </div>
</body>
</html>