<?php
require_once 'auth.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $_POST['user']);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    
    if ($u && password_verify($_POST['pass'], $u['password'])) {
        session_regenerate_id(true); 
        $_SESSION['user_id'] = $u['id']; 
        $_SESSION['username'] = $u['username']; 
        $_SESSION['role'] = $u['role'];
        header("Location: index.php"); exit;
    } else {
        $error = "Username atau Password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - File Shield Pro</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: #ffffff; padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); width: 100%; max-width: 380px; }
        .login-card h2 { margin: 0 0 8px 0; color: #0f172a; font-size: 24px; font-weight: 700; text-align: center; }
        .login-card p.subtitle { text-align: center; color: #64748b; font-size: 14px; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box; transition: all 0.2s; outline: none; }
        .form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2); }
        .btn-primary { width: 100%; padding: 12px; background: #4f46e5; color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-primary:hover { background: #4338ca; }
        .alert-error { background: #fef2f2; border: 1px solid #f87171; color: #b91c1c; padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; text-align: center;}
    </style>
</head>
<body>
    <div class="login-card">
        <h2>File Shield Pro</h2>
        <p class="subtitle">Secure Document Management System</p>
        
        <?php if(isset($error)): ?>
            <div class="alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="user" class="form-control" placeholder="Enter your username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="pass" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-primary">Sign In</button>
        </form>
    </div>
</body>
</html>