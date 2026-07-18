<?php
/* ============================================================
   api/auth.php
   ONE JOB: account creation, login, logout, session check.
   Called by index.html as: fetch('api/auth.php', {action:'login',...})
   ============================================================ */

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/helpers.php';

try {
    $in     = requestBody();
    $action = $in['action'] ?? '';

    switch ($action) {

        case 'signup': {
            $name   = trim($in['name'] ?? '');
            $email  = strtolower(trim($in['email'] ?? ''));
            $pwd    = $in['password'] ?? '';
            $role_  = $in['role'] ?? 'donor';
            $avatar = $in['avatarData'] ?? '';

            if (!$name || !$email || strlen($pwd) < 6 || !in_array($role_, ['admin','donor','volunteer','shelter'])) {
                out(false, null, 'Please fill all fields correctly.');
            }

            // SELECT — check duplicate email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) out(false, null, 'This email is already registered.');

            $hash = password_hash($pwd, PASSWORD_DEFAULT);
            // INSERT — create the account
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, avatar_data) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hash, $role_, $avatar]);
            out(true, null, 'Account created.');
        }

        case 'login': {
            $email = strtolower(trim($in['email'] ?? ''));
            $pwd   = $in['password'] ?? '';

            // SELECT the account by email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
            $stmt->execute([$email]);
            $u = $stmt->fetch();

            if (!$u || !password_verify($pwd, $u['password'])) {
                out(false, null, 'Incorrect email or password.');
            }

            $_SESSION['uid']  = $u['id'];
            $_SESSION['role'] = $u['role'];

            out(true, [
                'id' => 'U' . $u['id'], 'name' => $u['name'], 'email' => $u['email'],
                'role' => $u['role'], 'avatarData' => $u['avatar_data'] ?? '',
            ]);
        }

        case 'logout':
            $_SESSION = [];
            session_destroy();
            out(true);

        case 'session':
            if (!loggedIn()) out(false, null, 'No session.');
            $u = sessionUser($pdo);
            if (!$u) out(false, null, 'No session.');
            out(true, $u);

        default:
            out(false, null, 'Unknown auth action.');
    }

} catch (PDOException $e) {
    out(false, null, 'Database error: ' . $e->getMessage() . ' — is MySQL running and did you import sql/aidflow.sql?');
} catch (Throwable $e) {
    out(false, null, 'Server error: ' . $e->getMessage());
}
