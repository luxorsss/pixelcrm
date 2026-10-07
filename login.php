<?php
require_once __DIR__ . '/includes/init.php';

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-primary: #0F172A;
            --brand-accent: #2563EB;
            --surface: #F8FAFC;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --border-light: #E2E8F0;
        }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #FFFFFF;
            color: var(--text-main);
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }
        :focus-visible {
            outline: 2px solid var(--brand-accent) !important;
            outline-offset: 2px !important;
        }
        .split-layout {
            min-height: 100vh;
        }
        .brand-section {
            background-color: var(--brand-primary);
            color: #FFFFFF;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 4rem;
            position: relative;
        }
        .form-section {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem;
            background: #FFFFFF;
        }
        .form-wrapper {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }
        .form-control-custom {
            background-color: var(--surface);
            border: 1px solid var(--border-light);
            border-radius: 12px;
            padding: 0.85rem 1.2rem;
            font-weight: 500;
            color: var(--text-main);
            transition: border-color 160ms ease, box-shadow 160ms ease;
            width: 100%;
        }
        .form-control-custom:focus {
            background-color: #FFFFFF;
            border-color: var(--brand-accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
            outline: none;
        }
        .input-icon-wrap { position: relative; }
        .input-icon-wrap i.prefix {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted);
            font-size: 0.95rem;
        }
        .input-icon-wrap .form-control-custom { padding-left: 46px; }
        .password-toggle {
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
            color: var(--text-muted); cursor: pointer; border: none; background: none; 
            width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center;
            border-radius: 8px;
        }
        .password-toggle:hover { color: var(--text-main); }

        .btn-brand {
            background-color: var(--brand-primary);
            color: #FFFFFF;
            border-radius: 12px;
            padding: 0.9rem 1.5rem;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 160ms ease;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        .btn-brand:hover {
            background-color: #1E293B;
            color: #FFFFFF;
        }
        .btn-brand:active {
            transform: scale(0.98);
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .feature-icon-box {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #FFFFFF;
            font-size: 0.9rem;
        }
        @media (max-width: 576px) {
            .form-section {
                padding: 2.5rem 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0 split-layout">
            
            <div class="col-lg-5 col-xl-6 d-none d-lg-flex brand-section">
                <div>
                    <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 2rem;">
                        <i class="fas fa-layer-group fs-4 text-white"></i>
                    </div>
                    <div class="text-uppercase tracking-wider fw-bold text-white-50 mb-2" style="font-size: 0.75rem; letter-spacing: 0.08em;"><?= APP_NAME ?></div>
                    <h1 class="fw-bold mb-3" style="font-size: 2.4rem; line-height: 1.25; letter-spacing: -0.02em;">Operasional & Kasir Digital</h1>
                    <p class="text-white-50 mb-5" style="font-size: 1rem; line-height: 1.7; max-width: 440px;">
                        Platform terpadu untuk mengelola katalog, kasir pesanan, integrasi Meta Pixel, dan pengiriman notifikasi WhatsApp.
                    </p>
                    
                    <div class="mt-4 pt-4 border-top border-secondary border-opacity-25" style="max-width: 440px;">
                        <div class="feature-item">
                            <div class="feature-icon-box"><i class="fas fa-box-open"></i></div>
                            <div>
                                <div class="fw-bold text-white" style="font-size: 0.9rem;">Katalog & Bundling</div>
                                <div class="text-white-50" style="font-size: 0.8rem;">Kelola varian produk digital dan paket penawaran khusus.</div>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon-box"><i class="fas fa-cash-register"></i></div>
                            <div>
                                <div class="fw-bold text-white" style="font-size: 0.9rem;">Checkout Otomatis</div>
                                <div class="text-white-50" style="font-size: 0.8rem;">Halaman invoice mandiri dilengkapi kupon dan QRIS dinamis.</div>
                            </div>
                        </div>
                        <div class="feature-item mb-0">
                            <div class="feature-icon-box"><i class="fas fa-chart-line"></i></div>
                            <div>
                                <div class="fw-bold text-white" style="font-size: 0.9rem;">Pelacakan Pixel & CAPI</div>
                                <div class="text-white-50" style="font-size: 0.8rem;">Sinkronisasi event purchase dan analitik langsung ke Meta.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-white-50" style="font-size: 0.8rem;">
                    &copy; <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?? '1.0' ?>
                </div>
            </div>

            <div class="col-12 col-lg-7 col-xl-6 form-section">
                <div class="form-wrapper">
                    
                    <div class="d-lg-none d-flex align-items-center gap-2 mb-4">
                        <div style="width: 36px; height: 36px; background: var(--brand-primary); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-layer-group text-white"></i>
                        </div>
                        <h4 class="fw-bold m-0" style="color: var(--brand-primary);"><?= APP_NAME ?></h4>
                    </div>

                    <h2 class="fw-bold text-dark mb-1" style="letter-spacing: -0.02em;">Masuk ke Akun</h2>
                    <p class="mb-4" style="color: var(--text-muted); font-size: 0.9rem;">Gunakan kredensial pengelola untuk mengakses dashboard.</p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger rounded-3 border-0 bg-danger bg-opacity-10 text-danger fw-bold mb-4" style="font-size: 0.85rem;" role="alert">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label fw-bold" style="font-size: 0.85rem; color: var(--text-main);">Username</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-user prefix"></i>
                                <input type="text" class="form-control-custom" id="username" name="username" 
                                       value="<?= post('username') ?>" placeholder="Masukkan username" required autocomplete="username">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold" style="font-size: 0.85rem; color: var(--text-main);">Password</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-lock prefix"></i>
                                <input type="password" class="form-control-custom" id="password" name="password" 
                                       placeholder="••••••••" required autocomplete="current-password">
                                <button type="button" class="password-toggle" onclick="togglePassword('password', this)" aria-label="Lihat atau sembunyikan password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-brand">
                            Masuk
                        </button>
                    </form>

                    <div class="text-center mt-5 pt-3 border-top" style="border-color: var(--border-light) !important;">
                        <small style="color: var(--text-muted); font-size: 0.8rem;">
                            Sistem internal. Kontak administrator untuk bantuan akses.
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