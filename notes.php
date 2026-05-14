<?php
// notes.php — CRUD for Notes and Todos

require_once 'config.php';
requireLogin();

$action  =  isset($_POST['action']) ? $_POST['action'] : 
            (isset($_GET['action']) ? $_GET['action'] : '');
$user    = currentUser();
$db      = getDB();

// Admins can view all users' notes
$isAdmin = in_array($user['role'], ['admin', 'super_admin']);

switch ($action) {

    // FETCH ALL (notes + todos)
    case 'fetch':
        $view    = isset($_GET['view']) ? $_GET['view'] : 'notes';   // notes | archived | trash
        $userId  = isset($_GET['user_id']) ? $_GET['user_id'] : $user['id'];

        // Only admins can view other users' data
        if ($userId != $user['id'] && !$isAdmin)
            jsonResponse(false, 'Access denied.');

        $archived = ($view === 'archived') ? 1 : 0;
        $deleted  = ($view === 'trash')    ? 1 : 0;

        // Fetch notes
        $stmt = $db -> prepare("
            SELECT id, title, content, color, is_pinned, is_archived, is_deleted, created_at, updated_at, 'note' AS type
            FROM notes
            WHERE user_id = ? AND is_archived = ? AND is_deleted = ?
            ORDER BY is_pinned DESC, updated_at DESC
        ");
        $stmt -> execute([$userId, $archived, $deleted]);
        $notes = $stmt -> fetchAll();

        // Fetch todos with their items
        $stmt = $db -> prepare("
            SELECT id, title, color, is_pinned, is_archived, is_deleted, created_at, updated_at, 'todo' AS type
            FROM todos
            WHERE user_id = ? AND is_archived = ? AND is_deleted = ?
            ORDER BY is_pinned DESC, updated_at DESC
        ");
        $stmt -> execute([$userId, $archived, $deleted]);
        $todos = $stmt -> fetchAll();

        // Attach items to each todo
        foreach ($todos as &$todo) {
            $stmt = $db -> prepare("SELECT id, content, is_checked, sort_order FROM todo_items WHERE todo_id = ? ORDER BY sort_order ASC");
            $stmt -> execute([$todo['id']]);
            $todo['items'] = $stmt -> fetchAll();
        }

        jsonResponse(true, '', ['notes' => $notes, 'todos' => $todos]);

    // SAVE NOTE
    case 'save_note':
        $id      = intval(isset($_POST['id']) ? $_POST['id'] : 0);
        $title   = trim(isset($_POST['title']) ? $_POST['title'] : '');
        $content = trim(isset($_POST['content']) ? $_POST['content'] : '');
        $color   = isset($_POST['color']) ? $_POST['color'] : '#ffffff';

        if (!$title && !$content)
            jsonResponse(false, 'Note cannot be empty.');

        if ($id) {
            // Update — verify ownership or admin
            $stmt = $db -> prepare("SELECT user_id FROM notes WHERE id = ?");
            $stmt -> execute([$id]);
            $note = $stmt->fetch();
            if (!$note || ($note['user_id'] != $user['id'] && !$isAdmin))
                jsonResponse(false, 'Access denied.');

            $stmt = $db->prepare("UPDATE notes SET title = ?, content = ?, color = ? WHERE id = ?");
            $stmt -> execute([$title, $content, $color, $id]);
            jsonResponse(true, 'Note updated.', ['id' => $id]);
        } else {
            $stmt = $db -> prepare("INSERT INTO notes (user_id, title, content, color, created_at) 
            VALUES (?,?,?,?,NOW())");
            $stmt -> execute([$user['id'], $title, $content, $color]);
            jsonResponse(true, 'Note saved.', ['id' => $db -> lastInsertId()]);
        }

    // SAVE TODO 
    case 'save_todo':
        $id    = intval(isset($_POST['id']) ? $_POST['id'] :  0);
        $title = trim(isset($_POST['title']) ? $_POST['title'] : '');
        $items = json_decode(isset($_POST['items']) ? $_POST['items'] : '[]', true);
        $color = isset($_POST['color']) ? $_POST['color'] : '#ffffff';

        if (!$title && empty($items))
            jsonResponse(false, 'Todo list cannot be empty.');

        if ($id) {
            $stmt = $db -> prepare ("SELECT user_id FROM todos WHERE id = ?");
            $stmt -> execute([$id]);
            $todo = $stmt -> fetch();
            if (!$todo || ($todo['user_id'] != $user['id'] && !$isAdmin))
                jsonResponse(false, 'Access denied.');

            $stmt = $db -> prepare("UPDATE todos SET title = ?, color = ? WHERE id = ?");
            $stmt -> execute([$title, $color, $id]);

            // Replace items
            $db -> prepare("DELETE FROM todo_items WHERE todo_id = ?") -> execute([$id]);
        } else {
            $stmt = $db->prepare("INSERT INTO todos (user_id, title, color, created_at) VALUES (?,?,?,NOW())");
            $stmt -> execute([$user['id'], $title, $color]);
            $id = $db -> lastInsertId();
        }

        foreach ($items as $i => $item) {
            $content = trim(isset($item['content']) ? $item['content'] : '');
            if (!$content) continue;
            $checked = intval(isset($item['is_checked']) ? $item['is_checked'] : 0);
            $stmt = $db -> prepare("INSERT INTO todo_items (todo_id, content, is_checked, sort_order, created_at) VALUES (?,?,?,?,NOW())");
            $stmt -> execute([$id, $content, $checked, $i]);
        }

        jsonResponse(true, 'Todo saved.', ['id' => $id]);

    // TOGGLE CHECK (todo item)
    case 'toggle_item':
        $itemId = intval(isset($_POST['item_id']) ? $_POST['item_id'] : 0);
        $stmt = $db -> prepare("SELECT ti.id, t.user_id FROM todo_items ti JOIN todos t ON t.id = ti.todo_id WHERE ti.id = ?");
        $stmt -> execute([$itemId]);
        $row = $stmt -> fetch();
        if (!$row || ($row['user_id'] != $user['id'] && !$isAdmin))
            jsonResponse(false, 'Access denied.');

        $db -> prepare("UPDATE todo_items SET is_checked = 1 - is_checked WHERE id = ?") 
    -> execute([$itemId]);
        jsonResponse(true, 'Item toggled.');

    // PIN / UNPIN 
    case 'pin':
        $type   = isset($_POST['type']) ? $_POST['type'] : 'note';
        $itemId = intval(isset($_POST['id']) ? $_POST['id'] : 0);
        $table  = $type === 'todo' ? 'todos' : 'notes';

        $stmt = $db -> prepare("SELECT user_id, is_pinned FROM $table WHERE id = ?");
        $stmt -> execute([$itemId]);
        $row = $stmt -> fetch();
        if (!$row || ($row['user_id'] != $user['id'] && !$isAdmin))
            jsonResponse(false, 'Access denied.');

        $newPin = $row['is_pinned'] ? 0 : 1;
        $db -> prepare("UPDATE $table SET is_pinned=? WHERE id=?") -> execute([$newPin, $itemId]);
        jsonResponse(true, $newPin ? 'Pinned.' : 'Unpinned.', ['pinned' => $newPin]);

    // ARCHIVE / UNARCHIVE
    case 'archive':
        $type   = isset($_POST['type']) ? $_POST['type'] : 'note';
        $itemId = intval(isset($_POST['id']) ? $_POST['id'] : 0);
        $val    = intval(isset($_POST['value']) ? $_POST['value'] : 1);
        $table  = $type === 'todo' ? 'todos' : 'notes';

        $stmt = $db -> prepare("SELECT user_id FROM $table WHERE id = ?");
        $stmt -> execute([$itemId]);
        $row = $stmt -> fetch();
        if (!$row || ($row['user_id'] != $user['id'] && !$isAdmin))
            jsonResponse(false, 'Access denied.');

        $db -> prepare("UPDATE $table SET is_archived = ?, is_pinned = 0 WHERE id=?") -> execute([$val, $itemId]);
        jsonResponse(true, $val ? 'Archived.' : 'Restored.');

    // SOFT DELETE (move to trash)
    case 'trash':
        $type   = isset($_POST['type']) ? $_POST['type'] : 'note';
        $itemId = intval(isset($_POST['id']) ? $_POST['id'] : 0);
        $table  = $type === 'todo' ? 'todos' : 'notes';

        $stmt = $db -> prepare("SELECT user_id FROM $table WHERE id = ?");
        $stmt -> execute([$itemId]);
        $row = $stmt -> fetch();
        if (!$row || ($row['user_id'] != $user['id'] && !$isAdmin))
            jsonResponse(false, 'Access denied.');

        $db -> prepare("UPDATE $table SET is_deleted = 1, deleted_at = NOW(),
         is_pinned = 0, is_archived = 0 WHERE id = ?") -> execute([$itemId]);
        jsonResponse(true, 'Moved to trash.');

    // RESTORE FROM TRASH 
    case 'restore':
        $type   = isset($_POST['type']) ? $_POST['type'] : 'note';
        $itemId = intval(isset($_POST['id']) ? $_POST['id'] : 0);
        $table  = $type === 'todo' ? 'todos' : 'notes';

        $stmt = $db -> prepare("SELECT user_id FROM $table WHERE id = ?");
        $stmt -> execute([$itemId]);
        $row = $stmt -> fetch();
        if (!$row || ($row['user_id'] != $user['id'] && !$isAdmin))
            jsonResponse(false, 'Access denied.');

        $db -> prepare("UPDATE $table SET is_deleted = 0, 
        deleted_at = NULL WHERE id = ?") -> execute([$itemId]);
        jsonResponse(true, 'Restored.');

    // PERMANENT DELETE
    case 'delete':
        $type    = isset($_POST['type']) ? $_POST['type'] : 'note';
        $itemId = intval(isset($_POST['id']) ? $_POST['id'] : 0);
        $table  = $type === 'todo' ? 'todos' : 'notes';

        $stmt = $db -> prepare("SELECT user_id FROM $table WHERE id = ?");
        $stmt -> execute([$itemId]);
        $row = $stmt -> fetch();
        if (!$row || ($row['user_id'] != $user['id'] && !$isAdmin))
            jsonResponse(false, 'Access denied.');

        $db -> prepare("DELETE FROM $table WHERE id = ?") -> execute([$itemId]);

        if ($isAdmin && $row['user_id'] != $user['id']) {
            logAction($user['id'], 'permanent_delete_' . $type, $row['user_id'], 
            "Deleted $type ID $itemId");
        }

        jsonResponse(true, 'Permanently deleted.');

    // CHANGE COLOR
    case 'color':
        $type   = isset($_POST['type']) ? $_POST['type'] : 'note';
        $itemId = intval(isset($_POST['id']) ? $_POST['id'] : 0);
        $color  = isset($_POST['color']) ? $_POST['color'] : '#ffffff';
        $table  = $type === 'todo' ? 'todos' : 'notes';

        $stmt = $db -> prepare("SELECT user_id FROM $table WHERE id = ?");
        $stmt -> execute([$itemId]);
        $row = $stmt -> fetch();
        if (!$row || ($row['user_id'] != $user['id'] && !$isAdmin))
            jsonResponse(false, 'Access denied.');

        $db -> prepare("UPDATE $table SET color=? WHERE id = ?") -> execute([$color, $itemId]);
        jsonResponse(true, 'Color updated.');

    default:
        jsonResponse(false, 'Invalid action.');
}