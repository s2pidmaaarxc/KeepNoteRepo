document.getElementById('login-btn').addEventListener('click', doLogin);
document.getElementById('register-btn').addEventListener('click', doRegister);
document.querySelectorAll('.tab').forEach(tabBtn => {
    tabBtn.addEventListener('click', () => {
        switchTab(tabBtn.dataset.tab);
    });
});

document.addEventListener('click', (e) => {
    const btn = e.target.closest('.eye-btn');
    if (!btn) return;

    const targetId = btn.getAttribute('data-target');
    const input = document.getElementById(targetId);
    
    if (input) {
        const isPwd = input.type === 'password';
        input.type = isPwd ? 'text' : 'password';
        
        // Dim the eye icon when hidden
        btn.style.opacity = isPwd ? '1' : '0.5';
    }
});

document.addEventListener('input', (e) => {
    if (e.target.classList.contains('count-chars')) {
        const input = e.target;
        const counterId = input.getAttribute('data-counter');
        const counterEl = document.getElementById(counterId);
        
        if (counterEl) {
            const length = input.value.length;
            const max = input.getAttribute('maxlength');
            counterEl.textContent = `${length} / ${max} characters`;
            
            // Change color if it's too short (e.g., less than 6)
            counterEl.style.color = length < 6 ? '#ff4d4d' : '#888';
        }
    }
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
    const btn = document.getElementById('login-btn');
    const identifier = document.getElementById('login-identifier').value.trim();
    const password = document.getElementById('login-password').value;

    if (!identifier || !password) return showToast('Please fill in all fields.');

    // Prevent multiple clicks
    if (btn.disabled) return;
    btn.disabled = true;
    const originalText = btn.textContent;
    btn.textContent = 'Authenticating...';

    const fd = new FormData();
    fd.append('action', 'login');
    fd.append('identifier', identifier);
    fd.append('password', password);

    try {
        const res =  await fetch('auth.php', { 
            method: 'POST',
            credentials: 'same-origin', 
            body: fd, 
            headers: {'X-Requested-With' : 'XMLHttpRequest'}
        });

        if(!res.ok){
            showToast('Server error: ' + res.status);
            return;
        }
        
        const text  = await res.text();
        let data;

        try {
            data = JSON.parse(text);
        } catch (e) {
            showToast('Invalid server response: ' + text.substring(0, 100));
            return;
        }

        if(data.success){
           showToast('Signing in...', 'success');
            setTimeout(() => window.location.href = data.redirect, 600);
        } else {
            showToast(data.message);
            btn.disabled = false;
            btn.textContent = originalText;      
        }

    }  catch (e) {
        showToast('Network Error. Please try again.')
        btn.disabled = false;
        btn.textContent = originalText;
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

    try {
        const res = await fetch('auth.php', { 
            method: 'POST', 
            body: fd, 
            headers: {'X-Requested-With' : 'XMLHttpRequest'}
        });

        if(!res.ok){
            showToast('Server error: ' + res.status);
            return;
        }
        
        const text  = await res.text();
        let data;

        try {
            data = JSON.parse(text);
        } catch (e) {
            showToast('Invalid server response: ' + text.substring(0, 100));
            return;
        }

        if (data.success){
            showToast(data.message, 'success');
            setTimeout(() => switchTab('login'), 1500);
        } else {
            showToast(data.message);
        }

    } catch (e) {
        showToast('Network Error. Please try again.')
    }

}

function togglePassword(inputId, btn) {
  var input = document.getElementById(inputId);
  var isHidden = input.type === 'password';
  input.type = isHidden ? 'text' : 'password';

  // Swap the eye icon
  btn.innerHTML = isHidden
    ? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
    : '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
}

function updateCharCount(input) {
  var count = input.value.length;
  var max = input.maxLength;
  var el = document.getElementById('reg-char-count');
  el.textContent = count + ' / ' + max + ' characters';

  el.className = 'char-count';
  if (count >= max) {
    el.classList.add('danger');
  } else if (count >= max * 0.8) {
    el.classList.add('warning');
  }
}

// Enter key support
document.addEventListener('keydown', e => {
    if (e.key !== 'Enter') return;
    if (document.getElementById('form-login').classList.contains('active')) doLogin();
    else doRegister();
});