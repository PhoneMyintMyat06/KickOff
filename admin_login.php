<?php
session_start();
require_once 'db.php';

$error = '';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($password === $user['password']) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $user['username'];
                header('Location: admin_dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KickOff - Staff Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-login-body">

    <div class="login-card">
        <div class="logo text-center">
            <h2>KICK<span>OFF</span></h2>
            <p class="subtitle">Staff Access Portal</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="admin_login.php" method="POST" class="login-form">
            <div class="form-group">
                <label for="username"><i class="fa-solid fa-user"></i> Username</label>
                <input type="text" id="username" name="username" placeholder="Enter username" required>
            </div>

            <div class="form-group">
                <label for="password"><i class="fa-solid fa-lock"></i> Password</label>
                <div class="password-wrapper" style="position: relative; display: flex; align-items: center;">
                    <input type="password" id="password" name="password" placeholder="••••••••" required style="width: 100%; padding-right: 2.5rem;">
                    <i class="fa-solid fa-eye" id="togglePassword" style="position: absolute; right: 1rem; cursor: pointer; color: var(--text-muted);"></i>
                </div>
            </div>

            <button type="submit" class="btn-submit">Login to Dashboard &rarr;</button>
        </form>

        <div class="login-footer">
            <a href="index.php">&larr; Back to Public Site</a>
        </div>
    </div>

    <!-- Password Toggle Script -->
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
            this.style.color = type === 'text' ? 'var(--accent-lime)' : 'var(--text-muted)';
        });
    </script>

</body>
</html>