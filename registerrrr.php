<?php
// === LOGIC SECTION ===
require_once __DIR__ . '/includes/init.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$page_title = "Register";
$errors = [];

if (isPost()) {
    $username = clean(post('username'));
    $password = post('password');
    $confirm_password = post('confirm_password');
    
    // Validation
    if (empty($username)) {
        $errors[] = 'Username harus diisi';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username minimal 3 karakter';
    } elseif (strlen($username) > 50) {
        $errors[] = 'Username maksimal 50 karakter';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username hanya boleh berisi huruf, angka, dan underscore';
    }
    
    if (empty($password)) {
        $errors[] = 'Password harus diisi';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Konfirmasi password tidak cocok';
    }
    
    // Check if username already exists
    if (empty($errors)) {
        $existing = fetchRow("SELECT id FROM users WHERE username = ?", [$username]);
        if ($existing) {
            $errors[] = 'Username sudah digunakan, pilih username lain';
        }
    }
    
    // Register user
    if (empty($errors)) {
        if (registerUser($username, $password)) {
            setMessage('Registrasi berhasil! Silakan login dengan akun baru Anda.', 'success');
            redirect('login.php');
        } else {
            $errors[] = 'Gagal mendaftar. Silakan coba lagi.';
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
            --brand-primary: #059669; /* Emerald Green */
            --brand-accent: #10B981;
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
        
        /* Right: Form Side (Swapped for variation or keep consistent) */
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

        /* Left: Branding Side */
        .brand-section {
            background: linear-gradient(135deg, #064E3B 0%, var(--brand-primary) 100%);
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
            content: ''; position: absolute; top: -10%; right: -10%; width: 50%; height: 50%;
            background: radial-gradient(circle, rgba(16,185,129,0.3) 0%, transparent 70%); border-radius: 50%;
        }
        .brand-section::after {
            content: ''; position: absolute; bottom: -20%; left: -10%; width: 70%; height: 70%;
            background: radial-gradient(circle, rgba(16,185,129,0.15) 0%, transparent 70%); border-radius: 50%;
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
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
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
            background-color: #047857;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(4, 120, 87, 0.2);
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

        /* Progress Bar Password */
        .progress-bar-wrap { height: 4px; background: #E5E7EB; border-radius: 4px; margin-top: 8px; overflow: hidden; display: flex; gap: 2px; }
        .progress-segment { flex: 1; background: transparent; transition: background 0.3s; }
        .strength-text { font-size: 0.75rem; font-weight: 600; margin-top: 4px; display: block; text-align: right; }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0 split-layout flex-row-reverse">
            
            <div class="col-lg-5 col-xl-6 d-none d-lg-flex brand-section">
                <div style="position: relative; z-index: 2; max-width: 500px; margin: 0 auto;">
                    <div style="width: 64px; height: 64px; background: white; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 2rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
                        <i class="fas fa-rocket fs-2" style="color: var(--brand-primary);"></i>
                    </div>
                    <h1 class="fw-bold mb-4" style="font-size: 3rem; line-height: 1.2;">Mulai Ekspansi<br>Bisnismu Sekarang</h1>
                    <ul class="list-unstyled opacity-90 mt-4" style="font-size: 1.1rem; line-height: 2;">
                        <li><i class="fas fa-check-circle text-warning me-2"></i> Manajemen Pelanggan (CRM)</li>
                        <li><i class="fas fa-check-circle text-warning me-2"></i> Segmentasi RFM Otomatis</li>
                        <li><i class="fas fa-check-circle text-warning me-2"></i> Broadcast & Follow-up WA</li>
                        <li><i class="fas fa-check-circle text-warning me-2"></i> Analisis Transaksi Real-time</li>
                    </ul>
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

                    <h2 class="fw-bold text-dark mb-2">Buat Akun Baru 🚀</h2>
                    <p class="text-muted mb-4">Hanya butuh beberapa detik untuk mengatur akunmu.</p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger rounded-3 border-0 bg-danger bg-opacity-10 text-danger fw-bold" style="font-size: 0.85rem;">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="registerForm">
                        <div class="mb-3">
                            <label for="username" class="form-label fw-bold text-dark" style="font-size: 0.85rem;">Username <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-user prefix"></i>
                                <input type="text" class="form-control-custom w-100" id="username" name="username" 
                                       value="<?= post('username') ?>" placeholder="Pilih username unik" required minlength="3" maxlength="50" autocomplete="off">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold text-dark" style="font-size: 0.85rem;">Password <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-lock prefix"></i>
                                <input type="password" class="form-control-custom w-100" id="password" name="password" 
                                       placeholder="Buat password kuat" required minlength="6">
                                <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="progress-bar-wrap" id="pwdStrengthBar">
                                <div class="progress-segment" id="seg1"></div>
                                <div class="progress-segment" id="seg2"></div>
                                <div class="progress-segment" id="seg3"></div>
                            </div>
                            <span class="strength-text text-muted" id="pwdStrengthText">Minimal 6 karakter</span>
                        </div>
                        
                        <div class="mb-5">
                            <label for="confirm_password" class="form-label fw-bold text-dark" style="font-size: 0.85rem;">Konfirmasi Password <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-check-circle prefix"></i>
                                <input type="password" class="form-control-custom w-100" id="confirm_password" name="confirm_password" 
                                       placeholder="Ketik ulang password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="passwordMatch" style="font-size: 0.75rem; font-weight: 600; margin-top: 4px; text-align: right;"></div>
                        </div>
                        
                        <button type="submit" class="btn-brand w-100">
                            Daftar Sekarang
                        </button>
                    </form>
                    
                    <div class="auth-divider">Atau</div>
                    
                    <div class="text-center">
                        <span class="text-muted" style="font-size: 0.9rem;">Sudah punya akun?</span>
                        <a href="login.php" class="text-decoration-none fw-bold" style="color: var(--brand-accent); font-size: 0.9rem;">
                            Masuk di sini
                        </a>
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

        // Modern Password Strength Checker
        document.getElementById('password').addEventListener('input', function() {
            const val = this.value;
            const seg1 = document.getElementById('seg1');
            const seg2 = document.getElementById('seg2');
            const seg3 = document.getElementById('seg3');
            const txt = document.getElementById('pwdStrengthText');
            
            // Reset
            seg1.style.background = 'transparent';
            seg2.style.background = 'transparent';
            seg3.style.background = 'transparent';
            
            if(val.length === 0) {
                txt.textContent = 'Minimal 6 karakter';
                txt.className = 'strength-text text-muted';
                return;
            }
            
            let strength = 0;
            if(val.length >= 6) strength += 1;
            if(val.length >= 8 && /[A-Za-z]/.test(val) && /[0-9]/.test(val)) strength += 1;
            if(val.length >= 8 && /[^A-Za-z0-9]/.test(val)) strength += 1;

            if (strength === 1) {
                seg1.style.background = '#EF4444'; // Red
                txt.textContent = 'Lemah';
                txt.className = 'strength-text text-danger';
            } else if (strength === 2) {
                seg1.style.background = '#F59E0B'; // Yellow
                seg2.style.background = '#F59E0B';
                txt.textContent = 'Sedang';
                txt.className = 'strength-text text-warning';
            } else if (strength === 3) {
                seg1.style.background = '#10B981'; // Green
                seg2.style.background = '#10B981';
                seg3.style.background = '#10B981';
                txt.textContent = 'Kuat';
                txt.className = 'strength-text text-success';
            }
            
            // Trigger match check
            document.getElementById('confirm_password').dispatchEvent(new Event('input'));
        });
        
        // Password Match Checker
        document.getElementById('confirm_password').addEventListener('input', function() {
            const pwd = document.getElementById('password').value;
            const confirmPwd = this.value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPwd === '') {
                matchDiv.innerHTML = '';
            } else if (pwd === confirmPwd) {
                matchDiv.innerHTML = '<span class="text-success">Password cocok</span>';
            } else {
                matchDiv.innerHTML = '<span class="text-danger">Password tidak cocok</span>';
            }
        });
    </script>
</body>
</html>