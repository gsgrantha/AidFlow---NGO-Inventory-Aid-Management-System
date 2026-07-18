<?php
/* ============================================================
   api/inventory.php
   ONE JOB: everything about the inventory (stock) table.
   ============================================================ */

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/helpers.php';

try {
    $in     = requestBody();
    $action = $in['action'] ?? '';

    switch ($action) {

        case 'list': {
            requireLogin();
            $rows = $pdo->query("SELECT id, item_name AS itemName, category, quantity,
                                         expiry_date AS expiryDate, status FROM inventory ORDER BY id")->fetchAll();
            foreach ($rows as &$r) $r['id'] = 'I' . $r['id'];
            out(true, $rows);
        }

        case 'add': {
            requireAdmin();
            $item = trim($in['itemName'] ?? '');
            $cat  = $in['category'] ?? 'Food';
            $qty  = (int) ($in['quantity'] ?? 0);
            $exp  = $in['expiryDate'] ?: null;
            if (!$item || $qty < 0) out(false, null, 'Item name and quantity required.');

            $stmt = $pdo->prepare("INSERT INTO inventory (item_name, category, quantity, expiry_date, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$item, $cat, $qty, $exp, calcStatus($qty)]);
            out(true);
        }

        case 'update': {
            requireAdmin();
            $id  = numId($in['id'] ?? '');
            $qty = (int) ($in['quantity'] ?? 0);
            $stmt = $pdo->prepare("UPDATE inventory SET item_name=?, category=?, quantity=?, expiry_date=?, status=? WHERE id=?");
            $stmt->execute([trim($in['itemName']), $in['category'], $qty, $in['expiryDate'] ?: null, calcStatus($qty), $id]);
            out(true);
        }

        case 'delete':
            requireAdmin();
            $pdo->prepare("DELETE FROM inventory WHERE id=?")->execute([numId($in['id'] ?? '')]);
            out(true);

        default:
            out(false, null, 'Unknown inventory action.');
    }

} catch (PDOException $e) {
    out(false, null, 'Database error: ' . $e->getMessage() . ' — is MySQL running and did you import sql/aidflow.sql?');
} catch (Throwable $e) {
    out(false, null, 'Server error: ' . $e->getMessage());
}
