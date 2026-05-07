document.getElementById('login-btn').addEventListener('click', doLogin);
document.getElementById('register-btn').addEventListener('click', doRegister);
document.querySelectorAll('.tab').forEach(tabBtn => {
    tabBtn.addEventListener('click', () => {
        switchTab(tabBtn.dataset.tab);
    });
});

function switchTab(tab){
    document.querySelectorAll('.tab').forEach((t, i) => t.classList.toggle('active', (i === 0) === (tab === 'login')));
    document.getElementById('form-login').classList.toggle('active', tab === 'login');
    document.getElementById('form-register').classList.toggle('active', tab === 'register');
    hideToast();
}

function showToast(msg, type = 'error'){
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast' + type;
    t.style.display = 'block';
}

function hideToast(){
    document.getElementById('toast').style.display = 'none';
}

async function doLogin() {
    const identifier = document.getElementById('login-identifier').value.trim();
    const password = document.getElementById('login-password').value;
    if (!identifier || !password) return showToast('Please fill in all fields.');

    const fd = new FormData();
    fd.append('action', 'login');
    fd.append('identifier', identifier);
    fd.append('password', password);

    const res =  await fetch('auth.php', { method: 'POST', body: fd});
    const data = await res.json();
    if (data.success){
        showToast('Signing in...', 'success');
        setTimeout(() => window.location.href = data.redirect, 600);
    } else {
        showToast(data.message);
    }
}

async function doRegister() {
    const username = document.getElementById('reg-username').value.trim();
    const email = document.getElementById('reg-email').value.trim();
    const password = document.getElementById('reg-password').value;
    if (!username || !email) return showToast('Please fill in all field.');

    const fd = new FormData();
    fd.append('action', 'register');
    fd.append('username', username);
    fd.append('email', email);
    fd.append('password', password);

    const res = await fetch('auth.php', {method: 'POST', body: fd});
    const data = await res.json();
    if (data.success){
        showToast(data.message, 'success');
        setTimeout(() => switchTab('login'), 1500);
    } else {
        showToast(data.message);
    }
}

// Enter key support
document.addEventListener('keydown', e => {
    if (e.key !== 'Enter') return;
    if (document.getElementById('form-login').classList.contains('active')) doLogin();
    else doRegister();
});