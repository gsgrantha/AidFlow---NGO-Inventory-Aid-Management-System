<?php
/* ============================================================
   api/donations.php
   ONE JOB: everything about the donations table, including the
   approve transaction that also updates inventory.
   ============================================================ */

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/helpers.php';

try {
    $in     = requestBody();
    $action = $in['action'] ?? '';

    switch ($action) {

        case 'list': {
            requireLogin();
            $rows = $pdo->query("SELECT id, donor_name AS donorName, item_name AS itemName, category, quantity,
                                         donation_date AS date, status FROM donations ORDER BY id")->fetchAll();
            foreach ($rows as &$r) $r['id'] = 'D' . $r['id'];
            out(true, $rows);
        }

        case 'add': {
            requireLogin();
            $donor = trim($in['donorName'] ?? '');
            $item  = trim($in['itemName'] ?? '');
            $cat   = $in['category'] ?? 'Food';
            $qty   = (int) ($in['quantity'] ?? 0);
            $date  = $in['date'] ?? date('Y-m-d');
            if (!$donor || !$item || $qty < 1) out(false, null, 'Please fill all required fields.');

            $stmt = $pdo->prepare("INSERT INTO donations (donor_name, item_name, category, quantity, donation_date, status, user_id)
                                    VALUES (?, ?, ?, ?, ?, 'Pending', ?)");
            $stmt->execute([$donor, $item, $cat, $qty, $date, $_SESSION['uid']]);
            out(true);
        }

        case 'approve': {
            requireAdmin();
            $id = numId($in['id'] ?? '');

            $pdo->beginTransaction(); // TRANSACTION: two tables change together, or neither does
            try {
                $stmt = $pdo->prepare("SELECT * FROM donations WHERE id=?");
                $stmt->execute([$id]);
                $d = $stmt->fetch();
                if (!$d) { $pdo->rollBack(); out(false, null, 'Donation not found.'); }

                $pdo->prepare("UPDATE donations SET status='Approved' WHERE id=?")->execute([$id]);

                $stmt = $pdo->prepare("SELECT * FROM inventory WHERE item_name=? AND category=?");
                $stmt->execute([$d['item_name'], $d['category']]);
                $inv = $stmt->fetch();

                if ($inv) {
                    $newQty = $inv['quantity'] + $d['quantity'];
                    $pdo->prepare("UPDATE inventory SET quantity=?, status=? WHERE id=?")
                        ->execute([$newQty, calcStatus($newQty), $inv['id']]);
                } else {
                    $pdo->prepare("INSERT INTO inventory (item_name, category, quantity, status) VALUES (?, ?, ?, ?)")
                        ->execute([$d['item_name'], $d['category'], $d['quantity'], calcStatus($d['quantity'])]);
                }

                $pdo->commit();
                out(true);
            } catch (Exception $e) {
                $pdo->rollBack();
                out(false, null, 'Transaction failed, rolled back.');
            }
        }

        case 'reject':
            requireAdmin();
            $pdo->prepare("UPDATE donations SET status='Rejected' WHERE id=?")->execute([numId($in['id'] ?? '')]);
            out(true);

        case 'delete':
            requireAdmin();
            $pdo->prepare("DELETE FROM donations WHERE id=?")->execute([numId($in['id'] ?? '')]);
            out(true);

        case 'update': {
            requireAdmin();
            $id = numId($in['id'] ?? '');
            $stmt = $pdo->prepare("UPDATE donations SET donor_name=?, item_name=?, quantity=?, category=?, donation_date=? WHERE id=?");
            $stmt->execute([trim($in['donorName']), trim($in['itemName']), (int) $in['quantity'], $in['category'], $in['date'], $id]);
            out(true);
        }

        default:
            out(false, null, 'Unknown donations action.');
    }

} catch (PDOException $e) {
    out(false, null, 'Database error: ' . $e->getMessage() . ' — is MySQL running and did you import sql/aidflow.sql?');
} catch (Throwable $e) {
    out(false, null, 'Server error: ' . $e->getMessage());
}
