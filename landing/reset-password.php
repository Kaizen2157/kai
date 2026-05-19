<?php
session_start();
require __DIR__ . '/../db.php';

$token = $_GET['token'] ?? '';
$error = '';
$user = null; // Initialize user variable

// Verify token
if ($token) {
    // Check if token exists in database
    $stmt = $conn->prepare("SELECT id, email, name FROM users WHERE remember_token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        $error = "Invalid or expired reset link. Please request a new one.";
    }
} else {
    $error = "No reset token provided.";
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $new_password = $_POST['password'] ?? '';
    
    if (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password and clear the token
        $stmt = $conn->prepare("UPDATE users SET password=?, remember_token=NULL WHERE id=?");
        $stmt->bind_param("si", $hashed, $user['id']);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Password reset successful! Please log in with your new password.";
            header("Location: login.php");
            exit();
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Resumazing</title>
    <link rel="stylesheet" href="login.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Nunito:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { background: #030045; font-family: 'Nunito', sans-serif; color: #fff; margin: 0; }
        .reset-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .reset-card {
            background: rgba(255,255,255,0.052);
            border: 1px solid rgba(144,224,239,0.16);
            border-radius: 20px;
            padding: 34px 30px;
            width: 400px;
            max-width: 100%;
            backdrop-filter: blur(28px);
        }
        .ptag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #00b4d8;
            margin-bottom: 7px;
        }
        .pip {
            width: 5px;
            height: 5px;
            background: #00b4d8;
            border-radius: 50%;
        }
        .ptitle {
            font-family: 'Outfit', sans-serif;
            font-size: 1.55rem;
            font-weight: 700;
            letter-spacing: -.025em;
            line-height: 1.1;
            margin-bottom: 4px;
        }
        .psub {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.28);
            font-weight: 300;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .error-message, .success-message {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .error-message { background: rgba(255,68,68,0.1); border: 1px solid rgba(255,68,68,0.3); color: #ff4444; }
        .success-message { background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); color: #22c55e; }
        .fg { margin-bottom: 11px; }
        label { display: block; font-size: 0.73rem; font-weight: 600; color: rgba(255,255,255,0.58); margin-bottom: 5px; }
        .iw { position: relative; }
        .iico { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); opacity: .38; pointer-events: none; display: flex; align-items: center; }
        .iico svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        input[type=password], input[type=text] {
            width: 100%;
            background: rgba(255,255,255,0.052);
            border: 1px solid rgba(144,224,239,0.15);
            border-radius: 10px;
            color: #fff;
            font-family: 'Nunito', sans-serif;
            font-size: 0.84rem;
            padding: 9px 34px 9px 32px;
            outline: none;
            box-sizing: border-box;
            transition: border-color .2s, background .2s;
        }
        input:focus { border-color: #00b4d8; background: rgba(0,180,216,0.08); }
        .btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 20px;
            border-radius: 10px;
            font-family: 'Nunito', sans-serif;
            font-size: 0.88rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            background: linear-gradient(135deg, #00b4d8, #0077b6);
            color: #fff;
            transition: transform .15s, box-shadow .2s;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 14px 32px rgba(0,180,216,0.32); }
        .barrow { display: inline-block; transition: transform .2s; }
        .btn:hover .barrow { transform: translateX(4px); }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            font-size: 0.8rem;
            color: #00b4d8;
            text-decoration: none;
        }
        .back-link:hover { color: #90e0ef; }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="ptag"><span class="pip"></span> Reset Password</div>
            <h1 class="ptitle">Create New Password</h1>
            <p class="psub">Enter your new password below.</p>
            
            <?php if ($error): ?>
            <div class="error-message">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($user): ?>
            <form method="POST">
                <div class="fg">
                    <label for="r-pw">New Password</label>
                    <div class="iw">
                        <span class="iico"><svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></span>
                        <input type="password" id="r-pw" name="password" placeholder="Min. 6 characters" required minlength="6">
                    </div>
                </div>
                <button class="btn" type="submit">Reset Password <span class="barrow">→</span></button>
            </form>
            <?php endif; ?>
            
            <a href="login.php" class="back-link">← Back to Login</a>
        </div>
    </div>
</body>
</html>