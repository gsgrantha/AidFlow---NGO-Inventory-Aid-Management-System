<?php
/* ============================================================
   api/users.php
   ONE JOB: manage user accounts (admin) + self profile edits.
   ============================================================ */

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/helpers.php';

try {
    $in     = requestBody();
    $action = $in['action'] ?? '';

    switch ($action) {

        case 'list': {
            requireAdmin();
            // SELECT every registered account
            $rows = $pdo->query("SELECT id, name, email, role, avatar_data AS avatarData, created_at AS createdAt FROM users ORDER BY id")->fetchAll();
            foreach ($rows as &$r) $r['id'] = 'U' . $r['id'];
            out(true, $rows);
        }

        case 'updateRole': {
            requireAdmin();
            $id    = numId($in['id'] ?? '');
            $role_ = $in['role'] ?? '';
            if (!in_array($role_, ['admin','donor','volunteer','shelter'])) out(false, null, 'Invalid role.');
            // UPDATE the target user's role
            $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role_, $id]);
            out(true);
        }

        case 'delete': {
            requireAdmin();
            $id = numId($in['id'] ?? '');
            if ($id === (int) $_SESSION['uid']) out(false, null, 'You cannot delete your own account.');
            // DELETE the user
            $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
            out(true);
        }

        case 'updateProfile': {
            requireLogin();
            $name   = trim($in['name'] ?? '');
            $avatar = $in['avatarData'] ?? '';
            if (!$name) out(false, null, 'Name cannot be empty.');
            // UPDATE the logged-in user's own name/photo
            $pdo->prepare("UPDATE users SET name=?, avatar_data=? WHERE id=?")->execute([$name, $avatar, $_SESSION['uid']]);
            out(true);
        }

        default:
            out(false, null, 'Unknown users action.');
    }

} catch (PDOException $e) {
    out(false, null, 'Database error: ' . $e->getMessage() . ' — is MySQL running and did you import sql/aidflow.sql?');
} catch (Throwable $e) {
    out(false, null, 'Server error: ' . $e->getMessage());
}
