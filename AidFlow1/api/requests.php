<?php
/* ============================================================
   api/requests.php
   ONE JOB: everything about the aid-requests table.
   ============================================================ */

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/helpers.php';

try {
    $in     = requestBody();
    $action = $in['action'] ?? '';

    switch ($action) {

        case 'list': {
            requireLogin();
            $rows = $pdo->query("SELECT id, shelter_name AS shelterName, item_required AS itemRequired, quantity,
                                         priority, request_date AS date, status FROM requests ORDER BY id")->fetchAll();
            foreach ($rows as &$r) $r['id'] = 'R' . $r['id'];
            out(true, $rows);
        }

        case 'add': {
            requireLogin();
            $shelter = trim($in['shelterName'] ?? '');
            $item    = trim($in['itemRequired'] ?? '');
            $qty     = (int) ($in['quantity'] ?? 0);
            $pri     = $in['priority'] ?? 'Medium';
            $date    = $in['date'] ?? date('Y-m-d');
            if (!$shelter || !$item || $qty < 1) out(false, null, 'Please fill all required fields.');

            $stmt = $pdo->prepare("INSERT INTO requests (shelter_name, item_required, quantity, priority, request_date, status, user_id)
                                    VALUES (?, ?, ?, ?, ?, 'Pending', ?)");
            $stmt->execute([$shelter, $item, $qty, $pri, $date, $_SESSION['uid']]);
            out(true);
        }

        case 'approve':
            requireAdmin();
            $pdo->prepare("UPDATE requests SET status='Approved' WHERE id=?")->execute([numId($in['id'] ?? '')]);
            out(true);

        case 'reject':
            requireAdmin();
            $pdo->prepare("UPDATE requests SET status='Rejected' WHERE id=?")->execute([numId($in['id'] ?? '')]);
            out(true);

        case 'delete':
            requireAdmin();
            $pdo->prepare("DELETE FROM requests WHERE id=?")->execute([numId($in['id'] ?? '')]);
            out(true);

        case 'update': {
            requireAdmin();
            $id = numId($in['id'] ?? '');
            $stmt = $pdo->prepare("UPDATE requests SET shelter_name=?, item_required=?, quantity=?, priority=?, status=? WHERE id=?");
            $stmt->execute([trim($in['shelterName']), trim($in['itemRequired']), (int) $in['quantity'], $in['priority'], $in['status'], $id]);
            out(true);
        }

        default:
            out(false, null, 'Unknown requests action.');
    }

} catch (PDOException $e) {
    out(false, null, 'Database error: ' . $e->getMessage() . ' — is MySQL running and did you import sql/aidflow.sql?');
} catch (Throwable $e) {
    out(false, null, 'Server error: ' . $e->getMessage());
}
