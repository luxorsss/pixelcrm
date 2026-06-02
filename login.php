<?php
// === LOGIC SECTION ===
require_once __DIR__ . '/includes/init.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$page_title = "Login";
$errors = [];

if (isPost()) {
    $username = clean(post('username'));
    $password = post('password');
    
    if (empty($username)) {
        $errors[] = 'Username harus diisi';
    }
    
    if (empty($password)) {
        $errors[] = 'Password harus diisi';
    }
    
    if (empty($errors)) {
        if (loginUser($username, $password)) {
            setMessage('Login berhasil! Selamat datang ' . $username, 'success');
            redirect('index.php');
        } else {
            $errors[] = 'Username atau password salah';
        }
    }
}

// === PRESENTATION SECTION ===
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --brand-primary: #1E3A8A; /* Deep Navy */
            --brand-accent: #3B82F6;  /* Bright Blue */
            --surface: #F9FAFB;
        }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background-color: #FFFFFF;
            overflow-x: hidden;
        }
        /* Split Layout */
        .split-layout {
            min-height: 100vh;
            display: flex;
        }
        /* Left: Branding Side */
        .brand-section {
            background: linear-gradient(135deg, var(--brand-primary) 0%, #111827 100%);
            color: white;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem;
        }
        /* CSS Abstract Pattern */
        .brand-section::before {
            content: ''; position: absolute; top: -10%; left: -10%; width: 50%; height: 50%;
            background: radial-gradient(circle, rgba(59,130,246,0.3) 0%, transparent 70%); border-radius: 50%;
        }
        .brand-section::after {
            content: ''; position: absolute; bottom: -20%; right: -10%; width: 70%; height: 70%;
            background: radial-gradient(circle, rgba(59,130,246,0.15) 0%, transparent 70%); border-radius: 50%;
        }
        
        /* Right: Form Side */
        .form-section {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem;
            background: #FFFFFF;
        }
        .form-wrapper {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
            animation: fadeUp 0.6s ease forwards;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Form Controls */
        .form-control-custom {
            background-color: var(--surface);
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 0.85rem 1.2rem;
            font-weight: 500;
            color: #111827;
            transition: all 0.2s;
        }
        .form-control-custom:focus {
            background-color: #FFFFFF;
            border-color: var(--brand-accent);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        .input-icon-wrap { position: relative; }
        .input-icon-wrap i.prefix {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #9CA3AF;
        }
        .input-icon-wrap .form-control-custom { padding-left: 45px; }
        .password-toggle {
            position: absolute; right: 16px; top: 50%; transform: translateY(-50%);
            color: #9CA3AF; cursor: pointer; border: none; background: none; padding: 0;
        }
        .password-toggle:hover { color: var(--brand-accent); }

        .btn-brand {
            background-color: var(--brand-primary);
            color: white;
            border-radius: 12px;
            padding: 0.85rem;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
        }
        .btn-brand:hover {
            background-color: #1E40AF;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(30, 58, 138, 0.2);
            color: white;
        }

        .auth-divider {
            display: flex; align-items: center; text-align: center; margin: 2rem 0; color: #9CA3AF; font-size: 0.85rem; font-weight: 500;
        }
        .auth-divider::before, .auth-divider::after {
            content: ''; flex: 1; border-bottom: 1px solid #E5E7EB;
        }
        .auth-divider::before { margin-right: .5em; }
        .auth-divider::after { margin-left: .5em; }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0 split-layout">
            
            <div class="col-lg-5 col-xl-6 d-none d-lg-flex brand-section">
                <div style="position: relative; z-index: 2; max-width: 500px; margin: 0 auto;">
                    <div style="width: 64px; height: 64px; background: white; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 2rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
                        <i class="fas fa-layer-group fs-2 text-primary"></i>
                    </div>
                    <h1 class="fw-bold mb-4" style="font-size: 3rem; line-height: 1.2;">Kelola Relasi &<br>Transaksi Otomatis</h1>
                    <p class="opacity-75" style="font-size: 1.1rem; line-height: 1.8;">
                        Sistem CRM terintegrasi untuk memaksimalkan penjualan, menargetkan pelanggan, dan mengotomatisasi pesan WhatsApp bisnismu.
                    </p>
                    
                    <div class="mt-5 p-4 rounded-4" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(10px);">
                        <div class="d-flex gap-2 mb-2 text-warning">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="fst-italic opacity-75 m-0" style="font-size: 0.9rem;">"Platform ini mengubah cara kami mem-follow up pelanggan. Sangat efisien dan mudah digunakan."</p>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-7 col-xl-6 form-section">
                <div class="form-wrapper">
                    
                    <div class="d-lg-none d-flex align-items-center gap-2 mb-4">
                        <div style="width: 40px; height: 40px; background: var(--brand-primary); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-layer-group text-white"></i>
                        </div>
                        <h4 class="fw-bold m-0" style="color: var(--brand-primary);"><?= APP_NAME ?></h4>
                    </div>

                    <h2 class="fw-bold text-dark mb-2">Selamat Datang 👋</h2>
                    <p class="text-muted mb-4">Silakan masuk menggunakan akun yang terdaftar.</p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger rounded-3 border-0 bg-danger bg-opacity-10 text-danger fw-bold" style="font-size: 0.85rem;">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-4">
                            <label for="username" class="form-label fw-bold text-dark" style="font-size: 0.85rem;">Username</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-user prefix"></i>
                                <input type="text" class="form-control-custom w-100" id="username" name="username" 
                                       value="<?= post('username') ?>" placeholder="Masukkan username Anda" required autocomplete="off">
                            </div>
                        </div>
                        
                        <div class="mb-5">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="password" class="form-label fw-bold text-dark m-0" style="font-size: 0.85rem;">Password</label>
                                </div>
                            <div class="input-icon-wrap">
                                <i class="fas fa-lock prefix"></i>
                                <input type="password" class="form-control-custom w-100" id="password" name="password" 
                                       placeholder="••••••••" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-brand w-100">
                            Masuk ke Dashboard
                        </button>
                    </form>
                    
                    <div class="auth-divider">Atau</div>
                    
                    <div class="text-center">
                        <span class="text-muted" style="font-size: 0.9rem;">Belum memiliki akun?</span>
                        <a href="register.php" class="text-decoration-none fw-bold" style="color: var(--brand-accent); font-size: 0.9rem;">
                            Daftar Sekarang
                        </a>
                    </div>

                    <div class="text-center mt-5">
                        <small class="text-muted fw-medium" style="font-size: 0.75rem;">
                            &copy; <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?? '1.0' ?>. All rights reserved.
                        </small>
                    </div>

                </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>