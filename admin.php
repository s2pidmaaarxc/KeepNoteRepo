<?php
require_once 'config.php';
requireLogin();
requireRole('admin', 'super_admin');
$user = currentUser();
$isSuperAdmin = $user['role'] === 'super_admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KeepNote Admin</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 
    viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📒</text></svg>">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,600;1,300&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="css/admin.css">
</head>
<body>

<script> const IS_SUPER_ADMIN = <?= $isSuperAdmin ? 'true' : 'false' ?>; </script>

<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon"><i class="fas fa-shield-alt"></i></div>
    <div>
      <div class="logo-text">Admin</div>
    </div>
  </div>

  <div class="nav-section">Panel</div>
  <div class="nav-item active" data-view = "dashboard"><i class="fas fa-chart-pie"></i> Dashboard</div>
  <div class="nav-item" data-view="users"><i class="fas fa-users"></i> Users</div>
  <?php if ($isSuperAdmin): ?>
  <div class="nav-item" data-view="logs"><i class="fas fa-scroll"></i> Audit Logs</div>
  <?php endif; ?>
  <div class="nav-item" onclick="window.location.href='app.php'"><i class="fas fa-lightbulb"></i> My Notes</div>

  <div class="sidebar-bottom">
    <div class="user-chip">
      <div class="avatar"><?= strtoupper(substr($user['username'],0,1)) ?></div>
      <div>
        <div style="font-weight:500;color:#fff;font-size:13px">
          <?= htmlspecialchars($user['username']) ?>
        </div>
        <div style="font-size:11px;color:rgba(255,255,255,0.4)">
          <?= $user['role'] === 'super_admin' ? 'Super Admin' : 'Admin' ?>
        </div>
      </div>
    </div>
    <div class="nav-item" id="logout-btn"><i class="fas fa-sign-out-alt"></i> Sign Out</div>
  </div>
</aside>

<main class="main">
  <div class="topbar">
    <h1 id="section-title">Dashboard</h1>
    <span class="badge <?= $isSuperAdmin ? 'badge-super' : 'badge-admin' ?>">
      <?= $isSuperAdmin ? '⭐ Super Admin' : '🛡 Admin' ?>
    </span>
  </div>

  <div class="content">

    <!-- DASHBOARD -->
    <div id="section-dashboard">
      <div class="stats-row" id="stats-row">
        <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-number" id="s-total-users">—</div><div class="stat-label">Total Users</div></div>
        <div class="stat-card"><div class="stat-icon">✅</div><div class="stat-number" id="s-active-users">—</div><div class="stat-label">Active Users</div></div>
        <div class="stat-card"><div class="stat-icon">📝</div><div class="stat-number" id="s-total-notes">—</div><div class="stat-label">Total Notes</div></div>
        <div class="stat-card"><div class="stat-icon">☑️</div><div class="stat-number" id="s-total-todos">—</div><div class="stat-label">Total To-Dos</div></div>
        <div class="stat-card"><div class="stat-icon">🗑️</div><div class="stat-number" id="s-trash">—</div><div class="stat-label">In Trash</div></div>
      </div>

      <div class="panel">
        <div class="panel-header"><span class="panel-title">Quick Info</span></div>
        <div style="padding:20px;color:#666;font-size:14px;line-height:1.8">
          <?php if ($isSuperAdmin): ?>
          <p>🌟 You are a <strong>Super Admin</strong>. You have full control over all users, roles, and the system.</p>
          <?php else: ?>
          <p>🛡 You are an <strong>Admin</strong>. You can view and manage regular user accounts and their notes.</p>
          <?php endif; ?>
          <p>Go to <strong>Users</strong> section to manage accounts.</p>
          <p>Go to <strong>My Notes</strong> section to create your own notes.</p>
        </div>
      </div>
    </div>

    <!-- USERS -->
    <div id="section-users" style="display:none">
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">All Users</span>
        </div>
        <div class="panel-body">
          <table>
            <thead>
              <tr><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
            </thead>
            <tbody id="users-tbody">
              <tr><td colspan="6" style="text-align:center; color:#aaa; padding:30px">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php if ($isSuperAdmin): ?>
    <!-- LOGS -->
    <div id="section-logs" style="display:none">
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Audit Logs</span><span style="font-size:12px;color:#aaa">Last 200 actions</span></div>
        <div id="logs-container"><div style="padding:20px;text-align:center;color:#aaa">Loading…</div></div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</main>

<!-- Notes Viewer -->
<div class="viewer-overlay" id="notes-viewer">
  <div class="viewer-box">
    <div class="viewer-header">
      <h2 id="viewer-username">Notes</h2>
      <button class="btn-close" id="close-btn">✕</button>
    </div>
    <div class="mini-cards" id="viewer-cards"></div>
  </div>
</div>

<div id="app-toast"></div>

<script src="js/admin.js"></script>
    
</body>
</html>