<?php
session_start();
$login_error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
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
  .error-message {
    background: rgba(255, 68, 68, 0.1);
    border: 1px solid rgba(255, 68, 68, 0.3);
    color: #ff4444;
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideIn 0.3s ease;
  }
  .error-message svg {
    flex-shrink: 0;
  }
  @keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
  }
</style>
</head>
<body>

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
        <form action="../database/login.php" method="POST">
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
              <label class="chk"><input type="checkbox"> Remember me</label>
              <a href="#" class="forgot">Forgot password?</a>
            </div>

            <button class="btn btn-l" type="submit">Log In <span class="barrow">→</span></button>
          </div>
        </form>
        <!-- END LOGIN FORM -->

        <div class="split-badge" aria-hidden="true">OR</div>

        <!-- SIGN UP FORM -->
        <form action="../database/signup.php" method="POST">
          <div class="panel-signup">
            <div class="ptag"><span class="pip"></span> New here?</div>
            <h1 class="ptitle">Create Account</h1>
            <p class="psub">Free forever. No credit card needed.</p>

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
</body>
</html>