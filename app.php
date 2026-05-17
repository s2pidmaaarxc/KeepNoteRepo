<?php
require_once 'config.php';
requireLogin();
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KeepNote</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 
    viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📒</text></svg>">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,600;1,300&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="css/app.css">
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">📒</div>
    <span class="logo-text">KeepNote</span>
  </div>

  <div class="nav-section">Menu</div>
  <div class="nav-item active" data-view="notes"><i class="fas fa-lightbulb"></i> Notes</div>
  <div class="nav-item" data-view="archived"><i class="fas fa-archive"></i> Archive</div>
  <div class="nav-item" data-view="trash"><i class="fas fa-trash"></i> Trash</div>
  <?php if (in_array($user['role'], ['admin', 'super_admin'])): ?>
    <div class="nav-item" onclick="window.location.href='admin.php'">
      <i class="fas fa-shield-alt"></i> Admin Panel </div>
  <?php endif; ?>

  <div class="sidebar-bottom">
    <div class="user-chip">
      <div class="avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
      <div>
        <div style="font-weight:500;color:#fff;font-size:13px"><?= htmlspecialchars($user['username']) ?></div>
        <div style="font-size:11px;color:rgba(255,255,255,0.4)"><?= $user['role'] ?></div>
      </div>
    </div>
    <div class="nav-item" id="logout-btn" style="margin-top:4px"><i class="fas fa-sign-out-alt"></i> Sign Out</div>
  </div>
</aside>

<!-- MAIN -->
<main class="main">
  <div class="topbar">
    <h1 id="view-title">Notes</h1>
    <div class="search-box">
      <i class="fas fa-search"></i>
      <input type="text" placeholder="Search notes…" id="search-bar">
    </div>
    <div class="view-toggle" id="view-toggle">
      <button class="vt-btn active" title="Grid" data-layout="grid" ><i class="fas fa-th"></i></button>
      <button class="vt-btn" title="List" data-layout="list"><i class="fas fa-list"></i></button>
    </div>
    <button class="btn-new" id="new-btn"><i class="fas fa-plus"></i> New</button>
  </div>

  <div class="content">
    <div id="pinned-section" style="display:none">
      <div class="section-header">📌 Pinned</div>
      <div class="notes-grid" id="pinned-grid"></div>
    </div>
    <div id="others-section">
      <div class="section-header" id="others-label">All Notes</div>
      <div class="notes-grid" id="main-grid"></div>
    </div>
    <div id="empty-state" class="empty-state" style="display:none">
      <div class="icon">📭</div>
      <h3>Nothing here yet</h3>
      <p>Create your first note or to-do list!</p>
    </div>
  </div>
</main>

<!-- NEW/EDIT MODAL -->
<div class="modal-overlay" id="modal">
  <div class="modal" style="background: var(--selected-color, #fff)">
    <div class="modal-header">
      <span class="modal-title" id="modal-title">New</span>
      <button class="modal-close" id="close-modal-btn">✕</button>
    </div>

    <div class="type-selector" id="type-selector">
      <button class="type-btn active" data-type="note">📝 Note</button>
      <button class="type-btn" data-type="todo">✅ To-Do List</button>
    </div>

    <div class="field-group">
      <label>Title</label>
      <input type="text" id="m-title" placeholder="Note title…">
    </div>

    <!-- Note content -->
    <div id="note-panel" class="field-group">
      <label>Content</label>
      <textarea id="m-content" placeholder="Start writing…"></textarea>
    </div>

    <!--Todo panel -->
    <div id="todo-panel" style="display:none">
      <div class="field-group">
        <label>Items</label>
        <div class="todo-editor-list" id="todo-items-list"></div>
        <button class="add-item-btn" id="add-todo-btn"><i class="fas fa-plus"></i> Add item</button>
      </div>
    </div>

    <!-- Color -->
    <div class="field-group">
      <label>Color</label>
      <div class="color-bar" id="color-bar"></div>
    </div>

    <button class="btn-save" id="save-btn">Save</button>
  </div>
</div>

<div id="app-toast"></div>

<script src="js/app.js"></script>

</body>
</html>
