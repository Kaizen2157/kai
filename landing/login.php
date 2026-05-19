<?php
session_start();
$login_error = $_SESSION['login_error'] ?? '';
$signup_error = $_SESSION['signup_error'] ?? '';
$success_message = $_SESSION['success_message'] ?? '';
$forgot_error = $_SESSION['forgot_error'] ?? '';
$forgot_success = $_SESSION['forgot_success'] ?? '';
unset($_SESSION['login_error']);
unset($_SESSION['signup_error']);
unset($_SESSION['success_message']);
unset($_SESSION['forgot_error']);
unset($_SESSION['forgot_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resumazing — Login & Sign Up</title>
<link rel="stylesheet" href="login.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Nunito:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  .error-message, .success-message, .info-message {
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideIn 0.3s ease;
  }
  .error-message {
    background: rgba(255, 68, 68, 0.1);
    border: 1px solid rgba(255, 68, 68, 0.3);
    color: #ff4444;
  }
  .success-message {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #22c55e;
  }
  .info-message {
    background: rgba(0, 180, 216, 0.1);
    border: 1px solid rgba(0, 180, 216, 0.3);
    color: #00b4d8;
  }
  .error-message svg, .success-message svg, .info-message svg {
    flex-shrink: 0;
  }
  @keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* Loading Screen */
  .loading-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(3, 0, 69, 0.9);
    backdrop-filter: blur(10px);
    z-index: 9999;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    gap: 20px;
  }
  .loading-overlay.active {
    display: flex;
  }
  .loader {
    width: 50px;
    height: 50px;
    border: 3px solid rgba(144, 224, 239, 0.2);
    border-top-color: var(--sky, #00b4d8);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }
  @keyframes spin {
    to { transform: rotate(360deg); }
  }
  .loading-text {
    color: rgba(255, 255, 255, 0.8);
    font-family: 'Nunito', sans-serif;
    font-size: 0.9rem;
    letter-spacing: 0.02em;
  }
  .loading-subtext {
    color: rgba(255, 255, 255, 0.4);
    font-family: 'Nunito', sans-serif;
    font-size: 0.75rem;
  }

  /* Success Modal */
  .success-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(3, 0, 69, 0.9);
    backdrop-filter: blur(10px);
    z-index: 9999;
    justify-content: center;
    align-items: center;
  }
  .success-modal.active {
    display: flex;
  }
  .success-card {
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(34, 197, 94, 0.3);
    border-radius: 16px;
    padding: 30px;
    text-align: center;
    max-width: 380px;
    animation: modalIn 0.4s ease;
  }
  @keyframes modalIn {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
  }
  .success-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(34, 197, 94, 0.15);
    border: 2px solid rgba(34, 197, 94, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
  }
  .success-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.3rem;
    font-weight: 700;
    color: #22c55e;
    margin-bottom: 8px;
  }
  .success-text {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 20px;
    line-height: 1.5;
  }

  /* Forgot Password Modal */
  .modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(3, 0, 69, 0.85);
    backdrop-filter: blur(8px);
    z-index: 9998;
    justify-content: center;
    align-items: center;
  }
  .modal-overlay.active {
    display: flex;
  }
  .modal-card {
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(144, 224, 239, 0.2);
    border-radius: 16px;
    padding: 28px;
    max-width: 400px;
    width: 90%;
    animation: modalIn 0.3s ease;
  }
  .modal-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 6px;
  }
  .modal-sub {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.5);
    margin-bottom: 18px;
  }
  .modal-close {
    float: right;
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.4);
    cursor: pointer;
    font-size: 1.2rem;
    padding: 4px;
    transition: color 0.2s;
  }
  .modal-close:hover { color: #fff; }
  
  .forgot-link {
    cursor: pointer;
    color: var(--sky, #00b4d8);
    text-decoration: none;
    transition: color 0.2s;
  }
  .forgot-link:hover { color: var(--ice, #90e0ef); }
</style>
</head>
<body>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
  <div class="loader"></div>
  <div class="loading-text" id="loadingText">Signing you in...</div>
  <div class="loading-subtext">Please wait a moment</div>
</div>

<!-- Success Modal -->
<div class="success-modal" id="successModal">
  <div class="success-card">
    <div class="success-icon">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="20 6 9 17 4 12"/>
      </svg>
    </div>
    <div class="success-title">Account Created!</div>
    <div class="success-text">Welcome to Resumazing! You're being redirected to your dashboard.</div>
  </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal-overlay" id="forgotModal">
  <div class="modal-card">
    <button class="modal-close" onclick="closeForgotModal()">&times;</button>
    <div class="modal-title">Reset Password</div>
    <div class="modal-sub">Enter your email and we'll send you a reset link.</div>
    
    <form id="forgotForm" onsubmit="return false;">
      <div class="fg">
        <label for="reset-email">Email</label>
        <div class="iw">
          <span class="iico"><svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg></span>
          <input type="email" id="reset-email" name="reset_email" placeholder="you@example.com" required>
        </div>
      </div>
      
      <div id="forgotMsg" style="margin-bottom: 10px;"></div>
      
      <button class="btn btn-l" type="button" onclick="submitForgotPassword()">Send Reset Link <span class="barrow">→</span></button>
    </form>
    
    <p style="text-align:center;margin-top:12px;font-size:0.73rem;color:rgba(255,255,255,0.4);">
      Remember your password? <span class="forgot-link" onclick="closeForgotModal()">Log in</span>
    </p>
  </div>
</div>

<div class="bg-wrap">
  <div class="bg-base"></div>
  <div class="orb orb1"></div>
  <div class="orb orb2"></div>
  <div class="orb orb3"></div>
  <div class="grid"></div>
  <div class="float-card fc1"><div class="fc-label">MINIMAL</div><div class="fc-bar a"></div><div class="fc-bar b"></div><div class="fc-bar c"></div><div class="fc-bar" style="width:65%"></div></div>
  <div class="float-card fc2"><div class="fc-label">CREATIVE</div><div class="fc-bar a" style="width:70%"></div><div class="fc-bar c"></div><div class="fc-bar b"></div></div>
  <div class="float-card fc3"><div class="fc-label">CORPORATE</div><div class="fc-bar a" style="width:50%"></div><div class="fc-bar" style="width:75%"></div><div class="fc-bar c"></div></div>
  <div class="float-card fc4"><div class="fc-label">MODERN</div><div class="fc-bar a" style="width:60%"></div><div class="fc-bar b"></div><div class="fc-bar c"></div></div>
</div>

<div class="page">
  <header class="topbar">
    <a href="index.html" class="logo">Resumazing<span class="ldot"></span></a>
    <a href="index.html" class="back">
      <span class="back-icon"><svg viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg></span>
      Back to home
    </a>
  </header>

  <div class="center">
    <div style="position:relative">

      <div class="auth-card">

        <!-- LOGIN FORM -->
        <form action="../database/login.php" method="POST" id="loginForm" onsubmit="showLoading('Signing you in...')">
          <div class="panel-login">
            <div class="ptag"><span class="pip on"></span> Welcome back</div>
            <h1 class="ptitle">Log In</h1>
            <p class="psub">Pick up where you left off.</p>

            <!-- Error Message Display -->
            <?php if ($login_error): ?>
            <div class="error-message">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
              </svg>
              <?php echo htmlspecialchars($login_error); ?>
            </div>
            <?php endif; ?>
            
            <!-- Success Message -->
            <?php if ($success_message): ?>
            <div class="success-message">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <div class="soc-row">
              <a href="../google/google-login.php" class="soc-btn">
                <span class="soc-icon g-ico">G</span> Google
              </a>
            </div>
            <div class="or"><span>OR CONTINUE WITH EMAIL</span></div>

            <div class="fg">
              <label for="l-email">Email</label>
              <div class="iw">
                <span class="iico"><svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg></span>
                <input type="email" id="l-email" name="email" placeholder="you@example.com" autocomplete="email" required>
              </div>
            </div>

            <div class="fg">
              <label for="l-pw">Password</label>
              <div class="iw">
                <span class="iico"><svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></span>
                <input type="password" id="l-pw" name="password" placeholder="Your password" autocomplete="off" required>
                <button class="pwtoggle" onclick="togglePw('l-pw',this)" type="button" aria-label="Toggle password">
                  <svg viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
            </div>

            <div class="extras">
              <label class="chk"><input type="checkbox" name="remember_me" id="rememberMe"> Remember me</label>
              <a href="#" class="forgot" onclick="openForgotModal(event)">Forgot password?</a>
            </div>

            <button class="btn btn-l" type="submit">Log In <span class="barrow">→</span></button>
          </div>
        </form>
        <!-- END LOGIN FORM -->

        <div class="split-badge" aria-hidden="true">OR</div>

        <!-- SIGN UP FORM -->
        <form action="../database/signup.php" method="POST" id="signupForm" onsubmit="showLoading('Creating your account...')">
          <div class="panel-signup">
            <div class="ptag"><span class="pip"></span> New here?</div>
            <h1 class="ptitle">Create Account</h1>
            <p class="psub">Free forever. No credit card needed.</p>

            <!-- Signup Error Message Display -->
            <?php if ($signup_error): ?>
            <div class="error-message">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
              </svg>
              <?php echo htmlspecialchars($signup_error); ?>
            </div>
            <?php endif; ?>

            <div class="soc-row">
              <a href="../google/google-login.php" class="soc-btn">
                <span class="soc-icon g-ico">G</span> Google
              </a>
            </div>
            <div class="or"><span>OR CONTINUE WITH EMAIL</span></div>

            <div class="irow">
              <div class="fg">
                <label for="s-fn">First name</label>
                <div class="iw">
                  <span class="iico"><svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M4 21v-1a8 8 0 0116 0v1"/></svg></span>
                  <input type="text" id="s-fn" name="fname" placeholder="Juan" autocomplete="given-name" required>
                </div>
              </div>
              <div class="fg">
                <label for="s-ln">Last name</label>
                <div class="iw">
                  <span class="iico"><svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M4 21v-1a8 8 0 0116 0v1"/></svg></span>
                  <input type="text" id="s-ln" name="lname" placeholder="Dela Cruz" autocomplete="family-name" required>
                </div>
              </div>
            </div>

            <div class="fg">
              <label for="s-email">Email</label>
              <div class="iw">
                <span class="iico"><svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg></span>
                <input type="email" id="s-email" name="email" placeholder="you@example.com" autocomplete="email" required>
              </div>
            </div>

            <div class="fg">
              <label for="s-pw">Password</label>
              <div class="iw">
                <span class="iico"><svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></span>
                <input type="password" id="s-pw" name="password" placeholder="Create a strong password" autocomplete="new-password" oninput="strength(this)" required>
                <button class="pwtoggle" onclick="togglePw('s-pw',this)" type="button" aria-label="Toggle password">
                  <svg viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
              <div class="str-wrap">
                <div class="str-seg" id="ss1"></div>
                <div class="str-seg" id="ss2"></div>
                <div class="str-seg" id="ss3"></div>
                <div class="str-seg" id="ss4"></div>
              </div>
            </div>

            <button class="btn btn-s" type="submit">Create Free Account <span class="barrow">→</span></button>
            <p class="terms">By signing up you agree to our <a href="#">Terms</a> &amp; <a href="#">Privacy Policy</a>.</p>
          </div>
        </form>
        <!-- END SIGN UP FORM -->

      </div>
      
    </div>
  </div>
</div>

<script src="script.js"></script>
<script>
// Loading Screen
function showLoading(text) {
  document.getElementById('loadingText').textContent = text || 'Please wait...';
  document.getElementById('loadingOverlay').classList.add('active');
}

// Auto-dismiss all messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
  const messages = document.querySelectorAll('.error-message, .success-message, .info-message');
  messages.forEach(function(msg) {
    setTimeout(function() {
      msg.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      msg.style.opacity = '0';
      msg.style.transform = 'translateY(-10px)';
      setTimeout(function() {
        if (msg.parentNode) {
          msg.remove();
        }
      }, 500);
    }, 5000); // 5 seconds for all messages
  });
});

// Forgot Password Modal
function openForgotModal(e) {
  e.preventDefault();
  document.getElementById('forgotModal').classList.add('active');
  document.getElementById('forgotMsg').innerHTML = '';
  document.getElementById('reset-email').value = '';
}

function closeForgotModal() {
  document.getElementById('forgotModal').classList.remove('active');
}

function submitForgotPassword() {
  const email = document.getElementById('reset-email').value;
  const msgDiv = document.getElementById('forgotMsg');
  
  if (!email) {
    msgDiv.innerHTML = '<div class="error-message">Please enter your email address.</div>';
    return;
  }
  
  // Show loading on button
  const btn = event.target;
  btn.disabled = true;
  btn.innerHTML = 'Sending...';
  
  fetch('../database/forgot-password.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'email=' + encodeURIComponent(email)
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      msgDiv.innerHTML = '<div class="success-message"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>' + data.message + '</div>';
      setTimeout(closeForgotModal, 3000);
    } else {
      msgDiv.innerHTML = '<div class="error-message"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' + data.message + '</div>';
    }
    btn.disabled = false;
    btn.innerHTML = 'Send Reset Link <span class="barrow">→</span>';
  })
  .catch(err => {
    msgDiv.innerHTML = '<div class="error-message">Something went wrong. Please try again.</div>';
    btn.disabled = false;
    btn.innerHTML = 'Send Reset Link <span class="barrow">→</span>';
  });
}

// Close modals on escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeForgotModal();
  }
});

// Close modals on outside click
document.getElementById('forgotModal').addEventListener('click', function(e) {
  if (e.target === this) closeForgotModal();
});
</script>
</body>
</html>