<?php
/*require_once 'config.php';
$redirectUrl = '';
if (isLoggedIn()) {
    $redirectUrl = in_array($_SESSION['user_role'], ['admin','super_admin']) ? 'admin.php' : 'app.php';
}*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>>KeepNote — Sign In</title>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,600;1,300&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/index.css">
</head>
<body>

<!-- Left Panel -->
<div class="panel-left">
  <div class="note-floats">
    <div class="float-note">📝 Meeting notes<br><small style="opacity:.6">Today, 10:00 AM</small></div>
    <div class="float-note">✅ Buy groceries<br>✅ Call dentist<br>⬜ Read book</div>
    <div class="float-note">💡 App idea<br><small style="opacity:.6">Tap to expand</small></div>
  </div>

  <div class="logo">
    <div class="logo-icon">📒</div>
    <span class="logo-text">KeepNote</span>
  </div>

  <h1 class="hero-title">Your thoughts,<br><em>beautifully</em><br>organized.</h1>
  <p class="hero-sub">Notes, to-do lists, archives — all in one clean, fast, and private space.</p>

  <div class="features">
    <div class="feature"><div class="feature-dot"></div> Create notes & to-do lists instantly</div>
    <div class="feature"><div class="feature-dot"></div> Archive or trash & restore anytime</div>
    <div class="feature"><div class="feature-dot"></div> Color-code and pin for quick access</div>
    <div class="feature"><div class="feature-dot"></div> Secure role-based access control</div>
  </div>
</div>

<!-- Right Panel -->
<div class="panel-right">
  <div class="auth-card">
    <div class="tab-row">
      <div class="tab active" data-tab="login">Sign In</div>
      <div class="tab" data-tab="register">Register</div>
    </div>

    <div id="toast" class="toast"></div>

    <!-- Login Form -->
    <div id="form-login" class="panel-form active">
      <h2 class="form-title">Welcome back</h2>
      <p class="form-sub">Sign in to your account</p>

      <div class="field">
        <label>Username or Email</label>
        <input type="text" id="login-identifier" placeholder="Enter your username or email">
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" id="login-password" placeholder="Enter your password">
      </div>
      <button class="btn-primary" id="login-btn">Sign In →</button>
    </div>

    <!-- Register Form -->
    <div id="form-register" class="panel-form">
      <h2 class="form-title">Create account</h2>
      <p class="form-sub">Join KeepNote for free</p>

      <div class="field">
        <label>Username</label>
        <input type="text" id="reg-username" placeholder="Choose a username">
      </div>
      <div class="field">
        <label>Email</label>
        <input type="email" id="reg-email" placeholder="Your email address">
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" id="reg-password" placeholder="At least 6 characters">
      </div>
      <button class="btn-primary" id="register-btn">Create Account →</button>
    </div>
  </div>
</div>
  
<script src="js/index.js"></script>

<?php /*if ($redirectUrl): ?>
window.location.href = '<?= $redirectUrl ?>';
<?php endif; */ ?>

</body>
</html>