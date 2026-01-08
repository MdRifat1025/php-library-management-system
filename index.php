<?php
// index.php
include 'config.php';

$message = '';
$alert_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $result = login_user($_POST['username'], $_POST['password']);
        if ($result['success']) {
            redirect('dashboard.php');
        } else {
            $message = $result['message'];
            $alert_type = 'danger';
        }
    }
    
    if (isset($_POST['register'])) {
        $result = register_user($_POST['reg_username'], $_POST['reg_email'], $_POST['reg_password'], $_POST['full_name']);
        $message = $result['message'];
        $alert_type = $result['success'] ? 'success' : 'danger';
    }
}

if (is_logged_in()) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-container {
            max-width: 900px;
            width: 100%;
        }
        .auth-box {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        .auth-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .form-section {
            padding: 40px;
            min-height: 500px;
            display: none;
        }
        .form-section.active {
            display: block;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .toggle-form {
            cursor: pointer;
            color: #667eea;
        }
        .toggle-form:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1>📚 Library Management System</h1>
                <p class="mb-0">Manage your books efficiently</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $alert_type; ?> m-3" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <div class="form-section active" id="loginForm">
                <h3 class="mb-4">Login</h3>
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary w-100 mb-3">Login</button>
                </form>
                <p class="text-center">Don't have an account? <span class="toggle-form" onclick="toggleForms()">Register here</span></p>
            </div>
            
            <!-- Register Form -->
            <div class="form-section" id="registerForm">
                <h3 class="mb-4">Create Account</h3>
                <form method="POST">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="reg_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="reg_email" name="reg_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="reg_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="reg_username" name="reg_username" required>
                    </div>
                    <div class="mb-3">
                        <label for="reg_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="reg_password" name="reg_password" required>
                    </div>
                    <button type="submit" name="register" class="btn btn-primary w-100 mb-3">Register</button>
                </form>
                <p class="text-center">Already have an account? <span class="toggle-form" onclick="toggleForms()">Login here</span></p>
            </div>
        </div>
    </div>

    <script>
        function toggleForms() {
            document.getElementById('loginForm').classList.toggle('active');
            document.getElementById('registerForm').classList.toggle('active');
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>