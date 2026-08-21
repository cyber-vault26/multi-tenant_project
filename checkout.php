<?php
require 'db.php';
require 'includes/functions.php';
session_start();

$slug = $_GET['shop'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM tenants WHERE store_slug = ? AND store_enabled = 1 AND status = 'active'");
$stmt->execute([$slug]);
$tenant = $stmt->fetch();

if (!$tenant) {
    http_response_code(404);
    die("This store is not available.");
}

$cartKey = 'cart_' . $tenant['id'];

if (empty($_SESSION[$cartKey])) {
    header("Location: store.php?shop=" . urlencode($slug));
    exit();
}

// ---------------------------------------------------------------
// Place the order
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim($_POST['customer_name']);
    $customerPhone = trim($_POST['customer_phone']);
    $customerEmail = trim($_POST['customer_email'] ?? '') ?: null;
    $deliveryAddress = trim($_POST['delivery_address'] ?? '') ?: null;

    if (!$customerName || !$customerPhone) {
        die("Name and phone number are required.");
    }

    try {
        $pdo->beginTransaction();

        // Re-fetch products fresh (not trusting session/client data) and
        // re-check stock right before committing, in case it changed
        // since the cart page was loaded (another customer bought it, etc).
        $ids = array_keys($_SESSION[$cartKey]);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND tenant_id = ? FOR UPDATE");
        $stmt->execute([...$ids, $tenant['id']]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

        $orderItems = [];
        $total = 0;
        foreach ($_SESSION[$cartKey] as $pid => $qty) {
            if (!isset($products[$pid])) {
                throw new Exception("One of the items in your cart is no longer available.");
            }
            $p = $products[$pid];
            if ($qty > $p['stock_quantity']) {
                throw new Exception("Not enough stock for \"{$p['name']}\" — only {$p['stock_quantity']} left.");
            }
            $subtotal = $p['sale_price'] * $qty;
            $orderItems[] = ['product' => $p, 'qty' => $qty, 'subtotal' => $subtotal];
            $total += $subtotal;
        }

        if (empty($orderItems)) {
            throw new Exception("Your cart is empty.");
        }

        // Order number: date + short random suffix, human-readable and unique enough for this scale.
        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

        $stmt = $pdo->prepare("INSERT INTO online_orders (tenant_id, order_number, customer_name, customer_phone, customer_email, delivery_address, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tenant['id'], $orderNumber, $customerName, $customerPhone, $customerEmail, $deliveryAddress, $total]);
        $orderId = $pdo->lastInsertId();

        $itemStmt = $pdo->prepare("INSERT INTO online_order_items (order_id, product_id, product_name_snapshot, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
        $stockStmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");

        foreach ($orderItems as $item) {
            $itemStmt->execute([$orderId, $item['product']['id'], $item['product']['name'], $item['qty'], $item['product']['sale_price'], $item['subtotal']]);
            // Reserve stock immediately at order placement, same principle as POS —
            // it's released back if the order is later cancelled (see orders.php).
            $stockStmt->execute([$item['qty'], $item['product']['id']]);
        }

        $pdo->commit();

        unset($_SESSION[$cartKey]);

        logAction($pdo, "Online Order Placed", "$orderNumber — TZS " . number_format($total) . " from $customerName");

        header("Location: order-confirmation.php?order=" . urlencode($orderNumber));
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Could not place order: " . $e->getMessage() . " <a href='cart.php?shop=" . urlencode($slug) . "' style='color:#38bdf8'>Back to cart</a>");
    }
}

// ---------------------------------------------------------------
// Display cart summary for confirmation before placing the order
// ---------------------------------------------------------------
$ids = array_keys($_SESSION[$cartKey]);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND tenant_id = ?");
$stmt->execute([...$ids, $tenant['id']]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

$items = [];
$total = 0;
foreach ($_SESSION[$cartKey] as $pid => $qty) {
    if (!isset($products[$pid])) continue;
    $subtotal = $products[$pid]['sale_price'] * $qty;
    $items[] = ['product' => $products[$pid], 'qty' => $qty, 'subtotal' => $subtotal];
    $total += $subtotal;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — <?php echo htmlspecialchars($tenant['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #020617; color: white; }
        .glass-card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="text-slate-200">
    <header class="border-b border-white/5">
        <div class="max-w-3xl mx-auto px-6 py-5">
            <a href="cart.php?shop=<?php echo urlencode($slug); ?>" class="font-bold text-lg">← Back to Cart</a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-6 py-10 grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
            <h1 class="text-2xl font-bold mb-6">Checkout Details</h1>
            <form method="POST" class="glass-card rounded-[2rem] p-8 space-y-4">
                <input type="text" name="customer_name" placeholder="Full Name" required class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-sm outline-none">
                <input type="text" name="customer_phone" placeholder="Phone Number" required class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-sm outline-none">
                <input type="email" name="customer_email" placeholder="Email (optional)" class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-sm outline-none">
                <textarea name="delivery_address" placeholder="Delivery Address" rows="3" class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-sm outline-none"></textarea>
                <p class="text-xs text-slate-600">Payment is arranged directly with the seller after order confirmation — no payment is collected on this page.</p>
                <button type="submit" class="w-full bg-sky-500 hover:bg-sky-600 py-4 rounded-xl font-bold transition-all">Place Order</button>
            </form>
        </div>

        <div>
            <h2 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-4 mt-1">Order Summary</h2>
            <div class="glass-card rounded-[2rem] p-6 space-y-3">
                <?php foreach ($items as $item): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-300"><?php echo $item['qty']; ?>× <?php echo htmlspecialchars($item['product']['name']); ?></span>
                    <span class="font-bold"><?php echo htmlspecialchars($tenant['currency']); ?> <?php echo number_format($item['subtotal']); ?></span>
                </div>
                <?php endforeach; ?>
                <div class="border-t border-white/10 pt-3 flex justify-between font-bold text-lg">
                    <span>Total</span>
                    <span class="text-sky-400"><?php echo htmlspecialchars($tenant['currency']); ?> <?php echo number_format($total); ?></span>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
