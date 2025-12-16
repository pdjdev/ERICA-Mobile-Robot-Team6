<?php
session_start();
require_once '../db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../items/index.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($action == 'login') {
        $stmt = $pdo->prepare("SELECT * FROM accounts WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: ../items/index.php");
            exit;
        } else {
            $message = "Invalid username or password.";
        }
    } elseif ($action == 'register') {
        // Validate registration key (가입 인증키)
        $reg_key = isset($_POST['reg_key']) ? trim($_POST['reg_key']) : '';
        $required_key = '모로이6조화이팅';

        if ($reg_key !== $required_key) {
            $message = "가입 인증키가 올바르지 않습니다.";
        } else {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $message = "Username already exists.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO accounts (username, password) VALUES (?, ?)");
                if ($stmt->execute([$username, $hashed_password])) {
                    $message = "Registration successful! Please login.";
                } else {
                    $message = "Registration failed.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 로그인</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f0f2f5; margin: 0; }
        .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); width: 300px; }
        h2 { text-align: center; color: #333; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; }
        button:hover { background-color: #0056b3; }
        button.secondary { background-color: #6c757d; }
        button.secondary:hover { background-color: #545b62; }
        .message { color: red; text-align: center; margin-bottom: 10px; }
        .toggle { text-align: center; margin-top: 15px; font-size: 0.9em; cursor: pointer; color: #007bff; }
    </style>
    <script>
        function toggleForm() {
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            const title = document.getElementById('form-title');
            
            if (loginForm.style.display === 'none') {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
                title.innerText = 'Login';
            } else {
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
                title.innerText = 'Register';
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h2 id="form-title">로그인</h2>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div id="login-form">
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <input type="text" name="username" placeholder="아이디" required>
                <input type="password" name="password" placeholder="비밀번호" required>
                <button type="submit">로그인</button>
            </form>
            <div class="toggle" onclick="toggleForm()">회원가입</div>
        </div>

        <div id="register-form" style="display: none;">
            <form method="POST">
                <input type="hidden" name="action" value="register">
                <input type="text" name="username" placeholder="아이디" required>
                <input type="text" name="reg_key" placeholder="가입 인증키" required>
                <input type="password" name="password" placeholder="비밀번호" required>
                <button type="submit" class="secondary">회원가입</button>
            </form>
            <div class="toggle" onclick="toggleForm()">로그인</div>
        </div>
    </div>
</body>
</html>
