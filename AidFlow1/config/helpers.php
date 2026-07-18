<?php
/* ============================================================
   config/helpers.php
   ONE JOB: small helper functions shared by every file in api/.
   Session handling, access control, id formatting.
   ============================================================ */

session_start();
header('Content-Type: application/json');

// Standard JSON response + stop execution
function out($success, $data = null, $message = '') {
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

function loggedIn() { return isset($_SESSION['uid']); }
function role()     { return $_SESSION['role'] ?? null; }

function requireLogin() {
    if (!loggedIn()) out(false, null, 'Please log in.');
}
function requireAdmin() {
    requireLogin();
    if (role() !== 'admin') out(false, null, 'Admins only.');
}

// Front-end ids look like "D12", "I7", "U3" — strip the letters, keep the number
function numId($v) {
    return (int) preg_replace('/\D/', '', (string) $v);
}

// Derive inventory stock status from quantity
function calcStatus($qty) {
    return $qty <= 5 ? 'Critical' : ($qty <= 15 ? 'Low' : 'OK');
}

// Read the JSON body sent by fetch() from index.html
function requestBody() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

// Fetch the currently logged-in user's public profile
function sessionUser($pdo) {
    $stmt = $pdo->prepare("SELECT id, name, email, role, avatar_data AS avatarData FROM users WHERE id=?");
    $stmt->execute([$_SESSION['uid']]);
    $u = $stmt->fetch();
    if (!$u) return null;
    $u['id'] = 'U' . $u['id'];
    return $u;
}
