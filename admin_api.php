<?php
// admin_api.php — Admin & Super Admin API

require_once 'config.php';
requireLogin();
requireRole('admin', 'super_admin');

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action']: '');
$user = currentUser();
$db = getDB();
$isSuperAdmin = $user['role'] === 'super_admin';

switch ($action) {

    // LIST ALL USERS
    case 'list_users':
        $stmt = $db -> query ("SELECT id, username, email, role, is_active, created_at FROM users ORDER BY created_at DESC");
        jsonResponse(true, '', ['users' => $stmt -> fetchAll()]);

    // GET USER NOTES (admin viewing someone's notes) 
    case 'user_notes':
        $targetId = intval(isset($_GET['user_id']) ? $_GET['user_id']  : 0);
        if (!$targetId) jsonResponse(false, 'Invalid user.');

        $stmt = $db -> prepare("SELECT id, title, content, color, is_pinned, is_archived, is_deleted, created_at, 'note' AS type FROM notes WHERE user_id=? ORDER BY updated_at DESC");
        $stmt -> execute([$targetId]);
        $notes = $stmt -> fetchAll();

        $stmt = $db -> prepare("SELECT id, title, color, is_pinned, is_archived, is_deleted, created_at, 'todo' AS type FROM todos WHERE user_id=? ORDER BY updated_at DESC");
        $stmt -> execute([$targetId]);
        $todos = $stmt -> fetchAll();

        foreach ($todos as &$todo) {
            $s = $db -> 
            prepare("SELECT id, content, is_checked FROM todo_items WHERE todo_id=? ORDER BY sort_order");
            $s -> execute([$todo['id']]);
            $todo['items'] = $s -> fetchAll();
        }

        jsonResponse(true, '', ['notes' => $notes, 'todos' => $todos]);

    // TOGGLE USER ACTIVE
    case 'toggle_user':
        $targetId = intval(isset($_POST['user_id']) ? $_POST['user_id'] : 0);
        $stmt = $db -> prepare("SELECT id, role, is_active FROM users WHERE id=?");
        $stmt -> execute([$targetId]);
        $target = $stmt -> fetch();

        if (!$target) jsonResponse(false, 'User not found.');
        if ($target['id'] == $user['id']) jsonResponse(false, 'Cannot modify yourself.');

        // Admins cannot touch other admins or super admins
        if (!$isSuperAdmin && in_array($target['role'], ['admin', 'super_admin']))
            jsonResponse(false, 'Insufficient permissions.');

        $newStatus = $target['is_active'] ? 0 : 1;
        $db -> prepare("UPDATE users SET is_active=? WHERE id=?") -> execute([$newStatus, $targetId]);
        logAction($user['id'], $newStatus ? 'activate_user' : 'deactivate_user', $targetId);
        jsonResponse(true, $newStatus ? 'User activated.' : 'User deactivated.', ['is_active' => $newStatus]);

    // CHANGE USER ROLE (Super Admin only)
    case 'change_role':
        if (!$isSuperAdmin) jsonResponse(false, 'Only Super Admin can change roles.');
        $targetId = intval(isset($_POST['user_id']) ? $_POST['user_id'] : 0);
        $newRole  = isset($_POST['role']) ? $_POST['role'] : '';
        $userId = isset($_GET['user_id']) ? $_GET['user_id'] : 0;

        if (!in_array($newRole, ['user', 'admin', 'super_admin']))
            jsonResponse(false, 'Invalid role.');

        $stmt = $db -> prepare("SELECT id FROM users WHERE id=?");
        $stmt -> execute([$targetId]);
        if (!$stmt -> fetch()) 
            jsonResponse(false, 'User not found.');
        if ($targetId == $user['id']) 
            jsonResponse(false, 'Cannot change your own role.');

        $db -> prepare("UPDATE users SET role=? WHERE id=?") -> execute([$newRole, $targetId]);
        logAction($user['id'], 'change_role', $targetId, "New role: $newRole");
        jsonResponse(true, 'Role updated.');

    // DELETE USER (Super Admin only)
    case 'delete_user':
        if (!$isSuperAdmin) jsonResponse(false, 'Only Super Admin can delete users.');
        $targetId = intval(isset($_POST['user_id']) ? $_POST['user_id'] : 0);
        if ($targetId == $user['id']) jsonResponse(false, 'Cannot delete yourself.');

        $stmt = $db -> prepare("SELECT username FROM users WHERE id=?");
        $stmt -> execute([$targetId]);
        $target = $stmt -> fetch();
        if (!$target) jsonResponse(false, 'User not found.');

        $db -> prepare("DELETE FROM users WHERE id=?") -> execute([$targetId]);
        logAction($user['id'], 'delete_user', null, "Deleted user: {$target['username']}");
        jsonResponse(true, 'User permanently deleted.');

    // AUDIT LOGS (Super Admin only)
    case 'audit_logs':
        if (!$isSuperAdmin) jsonResponse(false, 'Access denied.');
        $stmt = $db -> query("
            SELECT al.*, u.username AS actor_name, u2.username AS target_name
            FROM audit_logs al
            JOIN users u ON u.id = al.actor_id
            LEFT JOIN users u2 ON u2.id = al.target_user_id
            ORDER BY al.created_at DESC
            LIMIT 200
        ");
        jsonResponse(true, '', ['logs' => $stmt -> fetchAll()]);

    //  STATS 
    case 'stats':
        $totalUsers = $db -> query("SELECT COUNT(*) FROM users") -> fetchColumn();
        $activeUsers = $db -> query("SELECT COUNT(*) FROM users WHERE is_active = 1") -> fetchColumn();
        $totalNotes = $db -> query("SELECT COUNT(*) FROM notes WHERE is_deleted = 0") -> fetchColumn();
        $totalTodos = $db -> query("SELECT COUNT(*) FROM todos WHERE is_deleted = 0") -> fetchColumn();
        $trashCount = $db -> query("SELECT (SELECT COUNT(*) FROM notes WHERE is_deleted = 1) 
        + (SELECT COUNT(*) FROM todos WHERE is_deleted = 1)") -> fetchColumn();

        jsonResponse(true, '', [
            'stats' => [
                'total_users'  => $totalUsers,
                'active_users' => $activeUsers,
                'total_notes'  => $totalNotes,
                'total_todos'  => $totalTodos,
                'trash_count'  => $trashCount,]
        ]);

    default:
        jsonResponse(false, 'Invalid action.');
}