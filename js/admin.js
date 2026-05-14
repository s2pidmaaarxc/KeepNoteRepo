document.getElementById('logout-btn').addEventListener('click', doLogout);
document.getElementById('close-btn').addEventListener('click', closeViewer);

// Section switch
function showSection(name, el) {

  // Hides all elements whose starts with "section-"
  document.querySelectorAll('[id^="section-"]').forEach(sec => {
    sec.style.display = 'none';
  })

  // Shows the specific section requested
  const targetSec = document.getElementById('section-' + name);
  if(targetSec) targetSec.style.display = '';
  
  // Update the active class on nav item
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  if (el) el.classList.add('active');

  // Update the titles
  const titleMap = {dashboard:'Dashboard', users:'Users', logs:'Audit Logs'};
  document.getElementById('section-title').textContent = titleMap[name] || name;

  // Trigger Data Loading
  if (name === 'dashboard') loadStats();
  if (name === 'users') loadUsers();
  if (name === 'logs') loadLogs();
}

document.addEventListener('DOMContentLoaded', () => {
  const navItems = document.querySelectorAll('.nav-item');

  navItems.forEach(item => {
    item.addEventListener('click', function(){
      const sectionName = this.getAttribute('data-view');
      if(sectionName){ showSection(sectionName, this); }
    });
  });

});

// Stats
async function loadStats() {
  const res = await fetch('admin_api.php?action=stats');
  const data = await res.json();
  if (!data.success) return;
  const s = data.stats;
  document.getElementById('s-total-users').textContent = s.total_users;
  document.getElementById('s-active-users').textContent = s.active_users;
  document.getElementById('s-total-notes').textContent = s.total_notes;
  document.getElementById('s-total-todos').textContent = s.total_todos;
  document.getElementById('s-trash').textContent = s.trash_count;
}

//  Users 
async function loadUsers() {
  const res = await fetch('admin_api.php?action=list_users');
  const data = await res.json();
  const tbody = document.getElementById('users-tbody');
  if (!data.success) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#e74c3c">Failed to load users.</td></tr>'; return; }

  tbody.innerHTML = data.users.map(u => {
    const roleBadge = u.role === 'super_admin' ? '<span class="badge badge-super">⭐ Super Admin</span>'
                    : u.role === 'admin' ? '<span class="badge badge-admin">🛡 Admin</span>'
                    : '<span class="badge badge-user">👤 User</span>';
    const statusBadge = u.is_active == 1 ? '<span class="badge badge-active">Active</span>' : '<span class="badge badge-inactive">Inactive</span>';

    const roleSelect = IS_SUPER_ADMIN
      ? `<select class="role-select" onchange="changeRole(${u.id},this.value)">
          <option value="user" ${u.role==='user'?'selected':''}>User</option>
          <option value="admin" ${u.role==='admin'?'selected':''}>Admin</option>
          <option value="super_admin" ${u.role==='super_admin'?'selected':''}>Super Admin</option>
        </select>`
      : roleBadge;

    const actions = `
      <button class="action-btn btn-view" onclick="viewUserNotes(${u.id},'${esc(u.username)}')"><i class="fas fa-eye"></i> Notes</button>
      <button class="action-btn btn-toggle" onclick="toggleUser(${u.id})">${u.is_active?'Deactivate':'Activate'}</button>
      ${IS_SUPER_ADMIN ? `<button class="action-btn btn-danger" onclick="deleteUser(${u.id},'${esc(u.username)}')"><i class="fas fa-trash"></i></button>` : ''}
    `;

    const joined = new Date(u.created_at).toLocaleDateString();

    return `<tr>
      <td><strong>${esc(u.username)}</strong></td>
      <td style="color:#999">${esc(u.email)}</td>
      <td>${roleSelect}</td>
      <td>${statusBadge}</td>
      <td style="color:#aaa;font-size:12px">${joined}</td>
      <td>${actions}</td>
    </tr>`;
  }).join('');
}

function esc(str) {
  return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/'/g,'&#39;');
}

async function toggleUser(id) {
  const fd = new FormData();
  fd.append('action','toggle_user'); fd.append('user_id',id);
  const res = await fetch('admin_api.php',{method:'POST',body:fd});
  const data = await res.json();
  if (data.success) { showToast(data.message, true); loadUsers(); }
  else showToast(data.message);
}

async function changeRole(id, role) {
  const fd = new FormData();
  fd.append('action','change_role'); 
  fd.append('user_id',id); 
  fd.append('role',role);
  const res = await fetch('admin_api.php',{method:'POST',body:fd});
  const data = await res.json();
  if (data.success) showToast('Role updated!', true);
  else showToast(data.message);
}

async function deleteUser(id, username) {
  if (!confirm(`Permanently delete user "${username}" and ALL their notes? This cannot be undone.`)) return;
  const fd = new FormData();
  fd.append('action','delete_user'); 
  fd.append('user_id',id);
  const res = await fetch('admin_api.php',{method:'POST',body:fd});
  const data = await res.json();
  if (data.success) { showToast('User deleted.', true); loadUsers(); }
  else showToast(data.message);
}

// Notes Viewer 
async function viewUserNotes(userId, username) {
  document.getElementById('viewer-username').textContent = username + "'s Notes";
  document.getElementById('viewer-cards').innerHTML = '<div style="color:#aaa;padding:20px">Loading…</div>';
  document.getElementById('notes-viewer').classList.add('open');

  const res = await fetch(`admin_api.php?action=user_notes&user_id=${userId}`);
  const data = await res.json();

  if (!data.success) { 
    document.getElementById('viewer-cards').innerHTML = '<div style="color:#e74c3c;padding:20px">Failed to load.</div>'; 
    return; 
  }

  const all = [...(data.notes||[]), ...(data.todos||[])];
  if (!all.length) { 
    document.getElementById('viewer-cards').innerHTML = '<div style="color:#aaa;padding:20px">No notes found.</div>'; 
    return; 
  }

  document.getElementById('viewer-cards').innerHTML = all.map(item => {
    const bg = item.color || '#fff';
    let body = '';
    if (item.type === 'todo' && item.items) {
      body = item.items.slice(0,5).map(it => `<div style="font-size:12px;color:${it.is_checked?'#aaa':'#555'};
        ${it.is_checked?'text-decoration:line-through':''}">• ${esc(it.content)}</div>`).join('');
    } else {
      body = `<div class="mini-card-body">${esc((item.content||'').slice(0,100))}${(item.content||'').length>100?'…':''}</div>`;
    }
    const type = item.type === 'todo' ? '✅ To-Do' : '📝 Note';
    const archived = item.is_archived ? ' · Archived' : '';
    const trashed = item.is_deleted ? ' · 🗑 Trashed' : '';
    return `<div class="mini-card" style="background:${bg}">
      <div class="mini-card-title">${esc(item.title||'(no title)')}</div>
      ${body}
      <div class="mini-card-meta">${type}${archived}${trashed} · ${new Date(item.created_at).toLocaleDateString()}</div>
    </div>`;
  }).join('');
}

function closeViewer() { document.getElementById('notes-viewer').classList.remove('open'); }
document.getElementById('notes-viewer').addEventListener('click', e => { 
  if (e.target === document.getElementById('notes-viewer')) closeViewer(); 
});

// Logs 
async function loadLogs() {
  const res = await fetch('admin_api.php?action=audit_logs');
  const data = await res.json();
  const cont = document.getElementById('logs-container');
  if (!data.success) { 
    cont.innerHTML = '<div style="padding:20px;color:#e74c3c">Access denied or failed.</div>'; 
    return; 
  }
  if (!data.logs.length) { 
    cont.innerHTML = '<div style="padding:20px;color:#aaa">No logs yet.</div>'; 
    return; 
  }

  cont.innerHTML = data.logs.map(log => {
    const time = new Date(log.created_at).toLocaleString();
    const target = log.target_name ? ` → <strong>${esc(log.target_name)}</strong>` : '';
    return `<div class="log-entry">
      <div><span class="log-actor">${esc(log.actor_name)}</span>${target} <span class="log-action">· ${esc(log.action)}${log.details ? ': '+esc(log.details) : ''}</span></div>
      <div class="log-time">${time}</div>
    </div>`;
  }).join('');
}

// Toast
function showToast(msg, success=false) {
  const t = document.getElementById('app-toast');
  t.textContent = msg;
  t.style.background = success ? '#06d6a0' : '#e84393';
  t.style.opacity = '1';
  setTimeout(() => t.style.opacity = '0', 2500);
}

// Logout 
async function doLogout() {
  const fd = new FormData(); 
  fd.append('action','logout');
  const res = await fetch('auth.php',{method:'POST',body:fd});
  const data = await res.json();
  window.location.href = data.redirect;
}

// Init 
loadStats();