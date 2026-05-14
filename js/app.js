document.getElementById('logout-btn').addEventListener('click', doLogout);
document.getElementById('new-btn').addEventListener('click',openNew);
document.getElementById('close-modal-btn').addEventListener('click', closeModal);
document.getElementById('view-toggle').addEventListener('click', (e) => {
  const btn = e.target.closest('.vt-btn');
  if(btn){const layoutType = btn.getAttribute('data-layout'); setLayout(layoutType, btn);}
});
document.getElementById('add-todo-btn').addEventListener('click', () => addTodoItem());
document.getElementById('save-btn').addEventListener('click', saveItem);


const navItems = document.querySelectorAll('.nav-item');
const searchInput = document.getElementById('search-bar');
const COLORS = [
  {hex:'#ffffff',name:'Default'},{hex:'#f9dc5c',name:'Yellow'},{hex:'#b5ead7',name:'Teal'},
  {hex:'#ffc8dd',name:'Pink'},{hex:'#c9b1ff',name:'Purple'},{hex:'#aedff7',name:'Blue'},
  {hex:'#ffd6a5',name:'Peach'},{hex:'#d4edda',name:'Green'},{hex:'#ff816b',name:'Red'}
];

let state = {
  currentView: 'notes',
  currentType: 'note',
  editingId: null,
  editingType: null,
  selectedColor: '#ffffff',
  allItems: [],
};

// Init 
document.addEventListener('DOMContentLoaded', () => {
  buildColorBar();
  loadItems();
});

// Load items
async function loadItems() {
  const res  = await fetch(`notes.php?action=fetch&view=${state.currentView}`);
  const data = await res.json();
  state.allItems = [...(data.notes||[]), ...(data.todos||[])];
  renderAll(state.allItems);
}

function renderAll(items) {
  const pinned = items.filter(i => i.is_pinned == 1);
  const others = items.filter(i => i.is_pinned != 1);

  document.getElementById('pinned-section').style.display = pinned.length ? '' : 'none';
  document.getElementById('pinned-grid').innerHTML = pinned.map(cardHTML).join('');
  document.getElementById('main-grid').innerHTML = others.map(cardHTML).join('');

  const isEmpty = !items.length;
  document.getElementById('empty-state').style.display = isEmpty ? '' : 'none';
  document.getElementById('others-section').style.display = isEmpty ? 'none' : '';
}

function cardHTML(item) {
  const isTrash = state.currentView === 'trash';
  const isArchived = state.currentView === 'archived';
  const bg = item.color || '#ffffff';
  const isTodo = item.type === 'todo';

  let body = '';
  if (isTodo && item.items) {
    body = item.items.slice(0,6).map(it =>
      `<div class="todo-item ${it.is_checked?'checked':''}">
        <input type="checkbox" ${it.is_checked?'checked':''} onclick="toggleItem(event,${it.id})">
        <span>${esc(it.content)}</span>
      </div>`
    ).join('');
    if (item.items.length > 6) body += `<div style="font-size:12px;color:#aaa;margin-top:4px">+${item.items.length-6} more</div>`;
  } else {
    body = `<div class="card-body">${esc(item.content||'')}</div>`;
  }

  let actions = '';
  if (isTrash) {
    actions = `
      <button class="card-action-btn" title="Restore" onclick="act(event,'restore','${item.type}',${item.id})"><i class="fas fa-undo"></i></button>
      <button class="card-action-btn danger" title="Delete forever" onclick="act(event,'delete','${item.type}',${item.id})"><i class="fas fa-trash"></i></button>`;
  } else if (isArchived) {
    actions = `
      <button class="card-action-btn" title="Unarchive" onclick="act(event,'archive','${item.type}',${item.id},0)"><i class="fas fa-box-open"></i></button>
      <button class="card-action-btn danger" title="Move to trash" onclick="act(event,'trash','${item.type}',${item.id})"><i class="fas fa-trash"></i></button>`;
  } else {
    actions = `
      <button class="card-action-btn" title="${item.is_pinned?'Unpin':'Pin'}" onclick="act(event,'pin','${item.type}',${item.id})"><i class="fas fa-thumbtack"></i></button>
      <button class="card-action-btn" title="Archive" onclick="act(event,'archive','${item.type}',${item.id},1)"><i class="fas fa-archive"></i></button>
      <button class="card-action-btn danger" title="Delete" onclick="act(event,'trash','${item.type}',${item.id})"><i class="fas fa-trash"></i></button>`;
  }

  return `<div class="card ${item.is_pinned?'pinned':''}" style="background:${bg}" onclick="openEdit(${item.id},'${item.type}')">
    ${item.title ? `<div class="card-title">${esc(item.title)}</div>` : ''}
    ${body}
    <div class="card-actions">${actions}</div>
  </div>`;
}

function esc(str) {
  return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// Filter
function filterCards(q) {
  if (!q) return renderAll(state.allItems);
  const lq = q.toLowerCase();
  const filtered = state.allItems.filter(i =>
    (i.title||'').toLowerCase().includes(lq) ||
    (i.content||'').toLowerCase().includes(lq) ||
    (i.items||[]).some(it => it.content.toLowerCase().includes(lq))
  );
  renderAll(filtered);
}

searchInput.addEventListener('input', (event)=> {
  const query = event.target.value
  filterCards(query);
})

// View switch
function setView(view) {
  state.currentView = view;
  const titles = { notes:'Notes', archived:'Archive', trash:'Trash' };
  document.getElementById('view-title').textContent = titles[view];
  document.getElementById('others-label').textContent = view === 'notes' ? 'All Notes' : view === 'archived' ? 'Archived Items' : 'Trash';
  document.querySelectorAll('.nav-item').forEach((el, i) => el.classList.toggle('active', ['notes','archived','trash'][i] === view));
  loadItems();
}

function setLayout(type, btn) {
  document.querySelectorAll('.notes-grid').forEach(g => g.classList.toggle('list-view', type === 'list'));
  document.querySelectorAll('.vt-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  localStorage.setItem('preferred-layout', type);
}

function updateActiveTab(selectedItem){
  navItems.forEach( nav => nav.classList.remove('active'));
  selectedItem.classList.add('active');
}

navItems.forEach(item => {
  item.addEventListener('click', () => {
    const viewName = item.getAttribute('data-view');
    if (viewName) { setView(viewName); updateActiveTab(item); }
  });
});

// Modal
function openNew() {
  state.editingId = null;
  state.editingType = null;
  state.selectedColor = '#ffffff';
  state.currentType = 'note';
  document.getElementById('modal-title').textContent = 'New';
  document.getElementById('type-selector').style.display = 'flex';
  document.getElementById('m-title').value = '';
  document.getElementById('m-content').value = '';
  document.getElementById('todo-items-list').innerHTML = '';
  document.getElementById('note-panel').style.display = '';
  document.getElementById('todo-panel').style.display = 'none';
  document.querySelectorAll('.type-btn').forEach((b,i) => b.classList.toggle('active', i===0));
  syncColorBar();
  document.querySelector('.modal').style.background = '#ffffff';
  document.getElementById('modal').classList.add('open');
}

function openEdit(id, type) {
  const item = state.allItems.find(i => i.id == id && i.type === type);
  if (!item) return;
  state.editingId = id;
  state.editingType = type;
  state.selectedColor = item.color || '#ffffff';
  state.currentType = type;

  document.getElementById('modal-title').textContent = 'Edit';
  document.getElementById('type-selector').style.display = 'none';
  document.getElementById('m-title').value = item.title || '';
  document.querySelector('.modal').style.background = item.color || '#fff';

  if (type === 'note') {
    document.getElementById('note-panel').style.display = '';
    document.getElementById('todo-panel').style.display = 'none';
    document.getElementById('m-content').value = item.content || '';
  } else {
    document.getElementById('note-panel').style.display = 'none';
    document.getElementById('todo-panel').style.display = '';
    renderTodoEditor(item.items || []);
  }
  syncColorBar();
  document.getElementById('modal').classList.add('open');
}

function closeModal() { document.getElementById('modal').classList.remove('open'); }

// Click outside to close
document.getElementById('modal').addEventListener('click', e => { 
  if (e.target === document.getElementById('modal')) 
    closeModal(); 
});

// To do & Note selector  
document.getElementById('type-selector').addEventListener('click', (e) => {
  const btn = e.target.closest('.type-btn');
  if(btn){ const type = btn.getAttribute('data-type'); setType(type, btn);}
});

function setType(type, btn) {
  state.currentType = type;
  document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  document.getElementById('note-panel').style.display = type === 'note' ? '' : 'none';
  document.getElementById('todo-panel').style.display = type === 'todo' ? '' : 'none';

  if (type === 'todo'){
    document.getElementById('todo-items-list').children.length;
    if(list && !list.children.length){
      addTodoItem();
    }
  }
}



// To do editor
function renderTodoEditor(items) {
  const list = document.getElementById('todo-items-list');
  list.innerHTML = '';
  items.forEach(it => addTodoItem(it.content, it.is_checked));
  if (!items.length) addTodoItem();
}

function addTodoItem(content='', checked=false) {
  const list = document.getElementById('todo-items-list');
  const div = document.createElement('div');
  div.className = 'todo-editor-item';
  div.innerHTML = `
    <input type="checkbox" ${checked?'checked':''}>
    <input type="text" placeholder="List item…" value="${esc(content)}">
    <button class="remove-item" onclick="this.parentElement.remove()">×</button>
  `;
  list.appendChild(div);
  div.querySelector('input[type=text]').focus();
}

// Colors
function buildColorBar() {
  const bar = document.getElementById('color-bar');
  bar.innerHTML = COLORS.map(c =>
    `<div class="color-swatch ${c.hex==state.selectedColor?'selected':''}" style="background:${c.hex}" title="${c.name}" onclick="selectColor('${c.hex}')"></div>`
  ).join('');
}

function selectColor(hex) {
  state.selectedColor = hex;
  document.querySelector('.modal').style.background = hex;
  syncColorBar();
}

function syncColorBar() {
  document.querySelectorAll('#color-bar .color-swatch').forEach(sw => {
    sw.classList.toggle('selected', sw.style.background === hexToRgb(state.selectedColor) || sw.getAttribute('onclick').includes(state.selectedColor));
  });
}

function hexToRgb(hex) {
  const r = parseInt(hex.slice(1,3),16), g = parseInt(hex.slice(3,5),16), b = parseInt(hex.slice(5,7),16);
  return `rgb(${r}, ${g}, ${b})`;
}

async function changeColor(e, type, id, color) {
  e.stopPropagation();
  const fd = new FormData();
  fd.append('action','color'); fd.append('type',type); fd.append('id',id); fd.append('color',color);
  await fetch('notes.php',{method:'POST',body:fd});
  loadItems();
}

document.addEventListener('click', () => document.querySelectorAll('.color-picker-panel.open').forEach(p => p.classList.remove('open')));

// Save
async function saveItem() {
  const title = document.getElementById('m-title').value.trim();
  const type = state.editingType || state.currentType;
  const fd = new FormData();

  if (type === 'note') {
    const content = document.getElementById('m-content').value.trim();
    if (!title && !content) return showToast('Note cannot be empty!');
    fd.append('action','save_note');
    fd.append('title',title);
    fd.append('content',content);
  } else {
    const items = [...document.querySelectorAll('#todo-items-list .todo-editor-item')].map(div => ({
      content: div.querySelector('input[type=text]').value.trim(),
      is_checked: div.querySelector('input[type=checkbox]').checked ? 1 : 0
    })).filter(i => i.content);
    if (!title && !items.length) return showToast('Todo cannot be empty!');
    fd.append('action','save_todo');
    fd.append('title',title);
    fd.append('items',JSON.stringify(items));
  }

  fd.append('color', state.selectedColor);
  if (state.editingId) fd.append('id', state.editingId);

  const res = await fetch('notes.php',{method:'POST',body:fd});
  const data = await res.json();
  if (data.success) { closeModal(); showToast(data.message, true); loadItems(); }
  else showToast(data.message);
}

// Actions
async function act(e, action, type, id, value) {
  e.stopPropagation();
  let msg = '';
  if (action === 'delete') {
    if (!confirm('Permanently delete this item? This cannot be undone.')) return;
    msg = 'Deleted permanently.';
  }
  const fd = new FormData();
  fd.append('action',action); fd.append('type',type); fd.append('id',id);
  if (value !== undefined) fd.append('value',value);
  const res = await fetch('notes.php',{method:'POST',body:fd});
  const data = await res.json();
  if (data.success) { showToast(msg || data.message, true); loadItems(); }
  else showToast(data.message);
}

async function toggleItem(e, itemId) {
  e.stopPropagation();
  const fd = new FormData();
  fd.append('action','toggle_item'); fd.append('item_id',itemId);
  await fetch('notes.php',{method:'POST',body:fd});
  loadItems();
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
  const fd = new FormData(); fd.append('action','logout');
  const res = await fetch('auth.php',{method:'POST',body:fd});
  const data = await res.json();
  window.location.href = data.redirect;
}