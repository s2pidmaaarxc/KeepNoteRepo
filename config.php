<?php
// config.php — Database & App Configuration

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Change to your MySQL username
define('DB_PASS', '');           // Change to your MySQL password
define('DB_NAME', 'keepnote_db');

define('APP_NAME', 'KeepNote');
define('BASE_URL', 'http://localhost/keepnote'); // Change to your domain

// Session & Security
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => false,
    'httponly'  => true,
    'samesite' => 'Lax',
]);
session_start();

error_log("SESSION START - ID: " . session_id() . " | SID: " . session_name() . "=" . session_id());
error_log("COOKIES RECEIVED: " . print_r($_COOKIE, true));

define('SESSION_TIMEOUT', 3600); // 1 hour

// Database Connection (PDO)
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
        }
    }
    return $pdo;
}

// Helper: Check if user is logged in
function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        if (isAjax()) {
            http_response_code(401);
            die(json_encode(['success' => false, 'message' => 'Not authenticated.']));
        }
        header('Location: ' . BASE_URL . '/index.html');
        exit;
    }
}

// Helper: Check role
function requireRole(...$roles) {
    requireLogin();
    if (!in_array($_SESSION['user_role'], $roles)) {
        if (isAjax()) {
            http_response_code(403);
            die(json_encode(['success' => false, 'message' => 'Access denied.']));
        }
        header('Location: ' . BASE_URL . '/app.php');
        exit;
    }
}

function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

function currentUser() {
    return [
        'id'       => $_SESSION['user_id']   ?? null,
        'username' => $_SESSION['username']  ?? null,
        'role'     => $_SESSION['user_role'] ?? null,
    ];
}

// Log admin actions
function logAction($actorId, $action, $targetUserId = null, $details = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO audit_logs (actor_id, target_user_id, action, details) VALUES (?, ?, ?, ?)");
    $stmt->execute([$actorId, $targetUserId, $action, $details]);
}

// JSON response helper
function jsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}
