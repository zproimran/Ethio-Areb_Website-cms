<?php
// ethioareb/index.php - CMS Login
require_once 'config/db_config.php';

if (isLoggedIn()) {
    header('Location: dashboard');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = sanitize($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $conn = getDB();
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? AND password = MD5(?) AND is_active = 1");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($admin = $result->fetch_assoc()) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_username'] = $admin['username'];
            
            $conn->query("UPDATE admins SET last_login = NOW(), login_ip = '{$_SERVER['REMOTE_ADDR']}' WHERE id = {$admin['id']}");
            
            header('Location: dashboard');
            exit();
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$settings = getSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Login - <?php echo $settings['site_name'] ?? 'Ethio Areb'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        h1, h2, h3 { font-family: 'Poppins', sans-serif; }
        
        body {
            background: linear-gradient(135deg, #0B3D91, #1a4fa0);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .login-box {
            background: white;
            border-radius: 24px;
            padding: 3rem;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .login-box .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-box .logo h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #0B3D91;
            font-family: 'Poppins', sans-serif;
        }
        
        .login-box .logo .sub {
            color: #6B7280;
            font-size: 0.9rem;
        }
        
        .login-box .logo .badge {
            background: #D4AF37;
            color: white;
            padding: 2px 12px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1px solid #D1D5DB;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            background: white;
            color: #1F2937;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #0B3D91;
            box-shadow: 0 0 0 3px rgba(11, 61, 145, 0.1);
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
        }
        
        /* Custom Button Styles */
        .btn-login {
            width: 100%;
            background: #0B3D91;
            color: white;
            padding: 14px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Inter', sans-serif;
        }
        
        .btn-login:hover {
            background: #092c6e;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(11, 61, 145, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login i {
            font-size: 1.1rem;
        }
        
        /* Alert Styles */
        .alert-error {
            background: #FEE2E2;
            border: 1px solid #FCA5A5;
            color: #991B1B;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }
        
        .alert-error i {
            font-size: 1.1rem;
        }
        
        /* Label Styles */
        .form-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .login-box {
                padding: 2rem 1.5rem;
            }
            
            .login-box .logo h1 {
                font-size: 1.5rem;
            }
            
            .btn-login {
                padding: 12px 20px;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">
            <h1>Ethio Areb</h1>
            <div class="sub">Content Management System</div>
            <span class="badge mt-2">v1.0</span>
        </div>
        
        <?php if ($error): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-4">
                <label class="form-label">Username</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" required class="form-control" placeholder="Enter username">
                </div>
            </div>
            
            <div class="mb-6">
                <label class="form-label">Password</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" required class="form-control" placeholder="Enter password">
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Login to CMS
            </button>
        </form>
        
        <div class="text-center mt-4">
            <a href="<?php echo SITE_URL; ?>" class="text-sm text-gray-500 hover:text-primary transition" style="text-decoration: none;">
                <i class="fas fa-arrow-left mr-1"></i>Back to Website
            </a>
        </div>
    </div>
</body>
</html>