<?php
// auth.php — Handles login, register, logout

require_once 'config.php';

$action = $_POST['action'] ?? '';

switch ($action) {

    // REGISTER
    case 'register':
        $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
        $email    = trim(isset($_POST['email']) ? $_POST['email'] : '');
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (!$username || !$email || !$password)
            jsonResponse(false, 'All fields are required.');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            jsonResponse(false, 'Invalid email address.');

        if (strlen($password) < 6)
            jsonResponse(false, 'Password must be at least 6 characters.');

        $db = getDB();

        // Check duplicates
        $stmt = $db -> prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt -> execute([$username, $email]);
        if ($stmt -> fetch())
            jsonResponse(false, 'Username or email already exists.');

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db -> prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$username, $email, $hash]);

        jsonResponse(true, 'Account created successfully! You can now log in.');

    // LOGIN
    case 'login':
        error_log("RAW POST: " . print_r($_POST, true));
        $identifier = trim(isset($_POST['identifier']) ? $_POST['identifier'] : ''); // username or email
        $password   = isset($_POST['password']) ? $_POST['password'] : '';

        if (!$identifier || !$password)
            jsonResponse(false, 'Please enter your credentials.');

        $db   = getDB();
        $stmt = $db -> prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
        $stmt -> execute([$identifier, $identifier]);
        $user = $stmt -> fetch();

        if (!$user || !password_verify($password, $user['password']))
            jsonResponse(false, 'Invalid credentials.');

        // Set session
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in_at'] = time();

        $redirect = in_array($user['role'], ['admin', 'super_admin']) ? 'admin.php' : 'app.php';
        jsonResponse(true, 'Login successful!', ['redirect' => $redirect, 'role' => $user['role']]);

    // LOGOUT 
    case 'logout':
        session_destroy();
        jsonResponse(true, 'Logged out.', ['redirect' => 'index.html']);

    default:
        jsonResponse(false, 'Invalid action.');
}
