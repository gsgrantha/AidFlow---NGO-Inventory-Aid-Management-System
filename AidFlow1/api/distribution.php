<?php
/* ============================================================
   api/distribution.php
   ONE JOB: everything about the distribution (delivery) table,
   including the transaction that deducts inventory on delivery.
   ============================================================ */

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/helpers.php';

try {
    $in     = requestBody();
    $action = $in['action'] ?? '';

    switch ($action) {

        case 'list': {
            requireLogin();
            $rows = $pdo->query("SELECT id, receiver_name AS receiverName, item_name AS itemName, category, quantity,
                                         volunteer_name AS volunteerName, dist_date AS date, status FROM distribution ORDER BY id")->fetchAll();
            foreach ($rows as &$r) $r['id'] = 'DT' . $r['id'];
            out(true, $rows);
        }

        case 'add': {
            requireLogin();
            $recv = trim($in['receiverName'] ?? '');
            $item = trim($in['itemName'] ?? '');
            $qty  = (int) ($in['quantity'] ?? 0);
            $cat  = $in['category'] ?? 'Food';
            $vol  = trim($in['volunteerName'] ?? '');
            $date = $in['date'] ?? date('Y-m-d');
            if (!$recv || !$item || $qty < 1 || !$vol) out(false, null, 'All fields are required.');

            $stmt = $pdo->prepare("INSERT INTO distribution (receiver_name, item_name, category, quantity, volunteer_name, dist_date, status, user_id)
                                    VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?)");
            $stmt->execute([$recv, $item, $cat, $qty, $vol, $date, $_SESSION['uid']]);
            out(true);
        }

        case 'updateStatus': {
            requireLogin();
            $id     = numId($in['id'] ?? '');
            $status = $in['status'] ?? 'Pending';
            if (!in_array($status, ['Pending','In Transit','Delivered'])) out(false, null, 'Invalid status.');

            $pdo->beginTransaction(); // TRANSACTION: delivering deducts inventory stock
            try {
                $stmt = $pdo->prepare("SELECT * FROM distribution WHERE id=?");
                $stmt->execute([$id]);
                $d = $stmt->fetch();
                if (!$d) { $pdo->rollBack(); out(false, null, 'Record not found.'); }

                $pdo->prepare("UPDATE distribution SET status=? WHERE id=?")->execute([$status, $id]);

                if ($status === 'Delivered') {
                    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE item_name=? AND category=?");
                    $stmt->execute([$d['item_name'], $d['category']]);
                    $inv = $stmt->fetch();
                    if ($inv) {
                        $newQty = max(0, $inv['quantity'] - $d['quantity']);
                        $pdo->prepare("UPDATE inventory SET quantity=?, status=? WHERE id=?")
                            ->execute([$newQty, calcStatus($newQty), $inv['id']]);
                    }
                }

                $pdo->commit();
                out(true);
            } catch (Exception $e) {
                $pdo->rollBack();
                out(false, null, 'Transaction failed, rolled back.');
            }
        }

        case 'update': {
            requireAdmin();
            $id = numId($in['id'] ?? '');
            $stmt = $pdo->prepare("UPDATE distribution SET receiver_name=?, volunteer_name=?, item_name=?, quantity=?, category=?, status=?, dist_date=? WHERE id=?");
            $stmt->execute([trim($in['receiverName']), trim($in['volunteerName']), trim($in['itemName']),
                             (int) $in['quantity'], $in['category'], $in['status'], $in['date'], $id]);
            out(true);
        }

        case 'delete':
            requireAdmin();
            $pdo->prepare("DELETE FROM distribution WHERE id=?")->execute([numId($in['id'] ?? '')]);
            out(true);

        default:
            out(false, null, 'Unknown distribution action.');
    }

} catch (PDOException $e) {
    out(false, null, 'Database error: ' . $e->getMessage() . ' — is MySQL running and did you import sql/aidflow.sql?');
} catch (Throwable $e) {
    out(false, null, 'Server error: ' . $e->getMessage());
}
