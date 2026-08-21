<?php
require 'db.php';
require 'includes/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$tenant_id = $_SESSION['tenant_id'];
$role = $_SESSION['role'];
$isManager = in_array($role, ['admin', 'manager', 'super_admin'], true);

if (!$isManager) {
    die("You are not allowed to manage orders.");
}

// ---------------------------------------------------------------
// Actions
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int) $_POST['order_id'];
    $action = $_POST['action'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM online_orders WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$orderId, $tenant_id]);
    $order = $stmt->fetch();

    if (!$order) { die("Order not found."); }

    try {
        if ($action === 'update_status') {
            $newStatus = $_POST['status'];
            if (!in_array($newStatus, ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'], true)) {
                die("Invalid status.");
            }

            if ($newStatus === 'cancelled' && $order['status'] !== 'cancelled') {
                $pdo->beginTransaction();

                // Release the stock this order had reserved.
                $items = $pdo->prepare("SELECT * FROM online_order_items WHERE order_id = ?");
                $items->execute([$orderId]);
                $restockStmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                foreach ($items->fetchAll() as $item) {
                    if ($item['product_id']) {
                        $restockStmt->execute([$item['quantity'], $item['product_id']]);
                    }
                }

                // If it had already been marked paid, reverse that accounting
                // entry too — cancelling a paid order is effectively a refund.
                if ($order['payment_status'] === 'paid') {
                    $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE account_name = 'Cash on Hand' AND tenant_id = ?")
                        ->execute([$order['total_amount'], $tenant_id]);
                    $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE account_name = 'Online Sales' AND tenant_id = ?")
                        ->execute([$order['total_amount'], $tenant_id]);
                    $pdo->prepare("INSERT INTO journal_entries (tenant_id, account_id, description, debit, credit)
                                    VALUES (?, (SELECT id FROM accounts WHERE account_name = 'Online Sales' AND tenant_id = ? LIMIT 1), ?, 0, ?)")
                        ->execute([$tenant_id, $tenant_id, "Refund — order " . $order['order_number'], $order['total_amount']]);
                    $pdo->prepare("UPDATE online_orders SET payment_status = 'unpaid' WHERE id = ?")->execute([$orderId]);
                }

                $pdo->prepare("UPDATE online_orders SET status = 'cancelled' WHERE id = ?")->execute([$orderId]);
                $pdo->commit();
                logAction($pdo, "Online Order Cancelled", $order['order_number'] . " — stock released" . ($order['payment_status'] === 'paid' ? " and payment reversed" : ""));
            } else {
                $pdo->prepare("UPDATE online_orders SET status = ? WHERE id = ?")->execute([$newStatus, $orderId]);
                logAction($pdo, "Online Order Updated", $order['order_number'] . " → $newStatus");
            }
        }

        if ($action === 'mark_paid') {
            if ($order['payment_status'] === 'paid') { die("This order is already marked paid."); }
            if ($order['status'] === 'cancelled') { die("Cannot mark a cancelled order as paid."); }

            $pdo->beginTransaction();

            $pdo->prepare("UPDATE online_orders SET payment_status = 'paid' WHERE id = ?")->execute([$orderId]);

            // Proper double-entry: Debit Cash on Hand, Credit Online Sales (Income).
            $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE account_name = 'Cash on Hand' AND tenant_id = ?")
                ->execute([$order['total_amount'], $tenant_id]);
            $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE account_name = 'Online Sales' AND tenant_id = ?")
                ->execute([$order['total_amount'], $tenant_id]);

            $pdo->prepare("INSERT INTO journal_entries (tenant_id, account_id, description, debit, credit)
                            VALUES (?, (SELECT id FROM accounts WHERE account_name = 'Cash on Hand' AND tenant_id = ? LIMIT 1), ?, ?, 0)")
                ->execute([$tenant_id, $tenant_id, "Payment received — order " . $order['order_number'], $order['total_amount']]);
            $pdo->prepare("INSERT INTO journal_entries (tenant_id, account_id, description, debit, credit)
                            VALUES (?, (SELECT id FROM accounts WHERE account_name = 'Online Sales' AND tenant_id = ? LIMIT 1), ?, 0, ?)")
                ->execute([$tenant_id, $tenant_id, "Sales revenue — order " . $order['order_number'], $order['total_amount']]);

            $pdo->commit();
            logAction($pdo, "Online Order Paid", $order['order_number'] . " — TZS " . number_format($order['total_amount']));
        }

        header("Location: orders.php?msg=updated");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}

// ---------------------------------------------------------------
// Data for display
// ---------------------------------------------------------------
$stmt = $pdo->prepare("SELECT * FROM online_orders WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 100");
$stmt->execute([$tenant_id]);
$orders = $stmt->fetchAll();

$statusColors = [
    'pending' => 'bg-amber-500/10 text-amber-400',
    'confirmed' => 'bg-sky-500/10 text-sky-400',
    'shipped' => 'bg-indigo-500/10 text-indigo-400',
    'delivered' => 'bg-emerald-500/10 text-emerald-400',
    'cancelled' => 'bg-red-500/10 text-red-400',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Orders — Strong Bridge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #020617; color: white; }
        .glass-card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="text-slate-200">
    <div class="lg:hidden flex items-center justify-between p-4 bg-slate-900 border-b border-white/5 mb-4 rounded-xl">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-sky-500 rounded-lg flex items-center justify-center font-bold text-slate-950 text-xs">SB</div>
            <span class="font-bold text-sm">Strong Bridge</span>
        </div>
        <button onclick="toggleSidebar()" class="p-2 text-slate-400 hover:bg-white/5 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
        </button>
    </div>
    <?php include 'includes/sidebar.php'; ?>

    <main class="lg:ml-64 p-8">
        <header class="mb-8">
            <h1 class="text-2xl font-bold text-white">Online Orders</h1>
            <p class="text-sm text-slate-500">Manage orders placed through your online store.</p>
        </header>

        <?php if (isset($_GET['msg'])): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 text-emerald-400 text-sm font-medium">Order updated.</div>
        <?php endif; ?>

        <div class="glass-card rounded-[2rem] overflow-hidden">
            <table class="w-full text-left text-sm border-collapse">
                <thead class="bg-white/5 text-slate-500 uppercase text-[10px] tracking-widest">
                    <tr>
                        <th class="p-4">Order</th>
                        <th class="p-4">Customer</th>
                        <th class="p-4">Total</th>
                        <th class="p-4">Payment</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr class="border-t border-white/5">
                        <td class="p-4">
                            <p class="font-bold text-white"><?php echo htmlspecialchars($o['order_number']); ?></p>
                            <p class="text-xs text-slate-600"><?php echo date('M d, Y H:i', strtotime($o['created_at'])); ?></p>
                        </td>
                        <td class="p-4">
                            <p class="text-slate-300"><?php echo htmlspecialchars($o['customer_name']); ?></p>
                            <p class="text-xs text-slate-600"><?php echo htmlspecialchars($o['customer_phone']); ?></p>
                        </td>
                        <td class="p-4 font-bold">TZS <?php echo number_format($o['total_amount']); ?></td>
                        <td class="p-4">
                            <?php if ($o['payment_status'] === 'paid'): ?>
                                <span class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400">Paid</span>
                            <?php else: ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="mark_paid">
                                    <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                    <button type="submit" class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase bg-amber-500/10 text-amber-400 hover:bg-amber-500/20">Unpaid — Mark Paid</button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td class="p-4">
                            <span class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase <?php echo $statusColors[$o['status']] ?? ''; ?>">
                                <?php echo htmlspecialchars($o['status']); ?>
                            </span>
                        </td>
                        <td class="p-4">
                            <?php if ($o['status'] !== 'cancelled' && $o['status'] !== 'delivered'): ?>
                            <form method="POST" class="flex items-center gap-2">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                <select name="status" class="bg-slate-900 border border-white/10 rounded-lg text-xs px-2 py-1.5 outline-none">
                                    <option value="pending" <?php if($o['status']==='pending') echo 'selected'; ?>>Pending</option>
                                    <option value="confirmed" <?php if($o['status']==='confirmed') echo 'selected'; ?>>Confirmed</option>
                                    <option value="shipped" <?php if($o['status']==='shipped') echo 'selected'; ?>>Shipped</option>
                                    <option value="delivered" <?php if($o['status']==='delivered') echo 'selected'; ?>>Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <button type="submit" class="text-sky-400 hover:underline text-xs font-bold">Set</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($orders)): ?>
                    <tr><td colspan="6" class="p-8 text-center text-slate-600 italic">No online orders yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
