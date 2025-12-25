<?php
// ============================================
// ไฟล์: login.php (ปรับปรุงใหม่)
// คำอธิบาย: หน้า Login สวยงาม พร้อม Animation
// ============================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/models/User.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);

        if ($user->login($username, $password)) {
            if ($_SESSION['role'] === 'owner' || $_SESSION['role'] === 'admin') {
                header("Location: admin/dashboard.php");
                exit();
            } else {
                header("Location: member/dashboard.php");
                exit();
            }
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Animated Background */
        .bg-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .bg-animation span {
            position: absolute;
            display: block;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.1);
            animation: float 25s linear infinite;
            bottom: -150px;
        }

        .bg-animation span:nth-child(1) { left: 25%; animation-delay: 0s; width: 80px; height: 80px; }
        .bg-animation span:nth-child(2) { left: 10%; animation-delay: 2s; width: 20px; height: 20px; }
        .bg-animation span:nth-child(3) { left: 70%; animation-delay: 4s; width: 30px; height: 30px; }
        .bg-animation span:nth-child(4) { left: 40%; animation-delay: 0s; width: 60px; height: 60px; }
        .bg-animation span:nth-child(5) { left: 65%; animation-delay: 3s; width: 20px; height: 20px; }
        .bg-animation span:nth-child(6) { left: 75%; animation-delay: 7s; width: 110px; height: 110px; }
        .bg-animation span:nth-child(7) { left: 35%; animation-delay: 15s; width: 150px; height: 150px; }
        .bg-animation span:nth-child(8) { left: 50%; animation-delay: 10s; width: 25px; height: 25px; }
        .bg-animation span:nth-child(9) { left: 20%; animation-delay: 2s; width: 15px; height: 15px; }
        .bg-animation span:nth-child(10) { left: 85%; animation-delay: 12s; width: 45px; height: 45px; }

        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
                border-radius: 0;
            }
            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
                border-radius: 50%;
            }
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .login-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .login-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .login-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .login-subtitle {
            color: #666;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 1.1rem;
            z-index: 10;
        }

        .form-control {
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 25px 0;
            color: #999;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e0e0e0;
        }

        .divider span {
            padding: 0 15px;
            font-size: 0.85rem;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
        }

        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: #764ba2;
        }

        .demo-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-top: 25px;
        }

        .demo-info h6 {
            font-weight: 600;
            color: #555;
            margin-bottom: 12px;
            font-size: 0.9rem;
        }

        .demo-account {
            background: white;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 0.85rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .demo-account:hover {
            background: #667eea;
            color: white;
            transform: translateX(5px);
            cursor: pointer;
        }

        .alert {
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            z-index: 10;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 30px 25px;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="bi bi-building"></i>
                </div>
                <h1 class="login-title">ระบบจัดการหอพัก</h1>
                <p class="login-subtitle">เข้าสู่ระบบเพื่อใช้งาน</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label class="form-label">ชื่อผู้ใช้</label>
                    <div class="input-group">
                        <span class="input-icon">
                            <i class="bi bi-person"></i>
                        </span>
                        <input type="text" class="form-control" name="username" 
                               placeholder="กรอกชื่อผู้ใช้" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">รหัสผ่าน</label>
                    <div class="input-group">
                        <span class="input-icon">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" class="form-control" name="password" 
                               id="password" placeholder="กรอกรหัสผ่าน" required>
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    เข้าสู่ระบบ
                </button>
            </form>

            <div class="divider">
                <span>หรือ</span>
            </div>

            <div class="register-link">
                <a href="register.php">
                    <i class="bi bi-person-plus"></i> สมัครสมาชิกใหม่
                </a>
            </div>

            <!-- ข้อมูลทดสอบ -->
            <div class="demo-info">
                <h6><i class="bi bi-info-circle"></i> บัญชีทดสอบ</h6>
                <div class="demo-account" onclick="fillLogin('owner', 'admin123')">
                    <span><strong>Owner:</strong> owner</span>
                    <span class="badge bg-danger">เจ้าของ</span>
                </div>
                <div class="demo-account" onclick="fillLogin('admin', 'admin123')">
                    <span><strong>Admin:</strong> admin</span>
                    <span class="badge bg-primary">ผู้ดูแล</span>
                </div>
                <div class="demo-account" onclick="fillLogin('member001', 'member123')">
                    <span><strong>Member:</strong> member001</span>
                    <span class="badge bg-success">ผู้เช่า</span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle Password Visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }

        // Fill Demo Login
        function fillLogin(username, password) {
            document.querySelector('[name="username"]').value = username;
            document.querySelector('[name="password"]').value = password;
            
            // Optional: Auto submit
            // document.getElementById('loginForm').submit();
        }

        // Add loading state on submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = this.querySelector('.btn-login');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังเข้าสู่ระบบ...';
            btn.disabled = true;
        });
    </script>
</body>
</html>