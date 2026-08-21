<?php
require 'db.php';
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
if (!isset($_SESSION[$cartKey])) { $_SESSION[$cartKey] = []; }

// ---------------------------------------------------------------
// Handle cart actions
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = (int) ($_POST['product_id'] ?? 0);

    if ($action === 'add') {
        // Confirm the product actually belongs to this tenant and is published
        // before trusting the ID from the form.
        $check = $pdo->prepare("SELECT id, stock_quantity FROM products WHERE id = ? AND tenant_id = ? AND is_published = 1");
        $check->execute([$productId, $tenant['id']]);
        $product = $check->fetch();

        if ($product) {
            $currentQty = $_SESSION[$cartKey][$productId] ?? 0;
            // Don't let the cart quantity exceed available stock.
            if ($currentQty < $product['stock_quantity']) {
                $_SESSION[$cartKey][$productId] = $currentQty + 1;
            }
        }
        header("Location: store.php?shop=" . urlencode($slug) . "&msg=added");
        exit();
    }

    if ($action === 'update') {
        $qty = max(0, (int) ($_POST['quantity'] ?? 0));
        if ($qty === 0) {
            unset($_SESSION[$cartKey][$productId]);
        } else {
            $check = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ? AND tenant_id = ?");
            $check->execute([$productId, $tenant['id']]);
            $stock = (int) $check->fetchColumn();
            $_SESSION[$cartKey][$productId] = min($qty, $stock);
        }
        header("Location: cart.php?shop=" . urlencode($slug));
        exit();
    }

    if ($action === 'remove') {
        unset($_SESSION[$cartKey][$productId]);
        header("Location: cart.php?shop=" . urlencode($slug));
        exit();
    }
}

// ---------------------------------------------------------------
// Build cart display data
// ---------------------------------------------------------------
$items = [];
$total = 0;
if (!empty($_SESSION[$cartKey])) {
    $ids = array_keys($_SESSION[$cartKey]);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND tenant_id = ?");
    $stmt->execute([...$ids, $tenant['id']]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

    foreach ($_SESSION[$cartKey] as $pid => $qty) {
        if (!isset($products[$pid])) continue; // product was deleted/unpublished since being added
        $p = $products[$pid];
        $subtotal = $p['sale_price'] * $qty;
        $items[] = ['product' => $p, 'quantity' => $qty, 'subtotal' => $subtotal];
        $total += $subtotal;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart — <?php echo htmlspecialchars($tenant['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #020617; color: white; }
        .glass-card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="text-slate-200">
    <header class="border-b border-white/5">
        <div class="max-w-3xl mx-auto px-6 py-5 flex justify-between items-center">
            <a href="store.php?shop=<?php echo urlencode($slug); ?>" class="font-bold text-lg">← <?php echo htmlspecialchars($tenant['name']); ?></a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-6 py-10">
        <h1 class="text-2xl font-bold mb-8">Your Cart</h1>

        <?php if (empty($items)): ?>
            <div class="glass-card rounded-[2rem] p-12 text-center">
                <p class="text-slate-500 mb-4">Your cart is empty.</p>
                <a href="store.php?shop=<?php echo urlencode($slug); ?>" class="text-sky-400 font-bold hover:underline">Continue Shopping →</a>
            </div>
        <?php else: ?>
            <div class="glass-card rounded-[2rem] overflow-hidden mb-6">
                <?php foreach ($items as $item): ?>
                <div class="flex items-center gap-4 p-5 border-b border-white/5 last:border-b-0">
                    <div class="w-16 h-16 bg-slate-900 rounded-xl flex items-center justify-center overflow-hidden flex-shrink-0">
                        <?php if (!empty($item['product']['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($item['product']['image_url']); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <svg class="w-6 h-6 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-white"><?php echo htmlspecialchars($item['product']['name']); ?></p>
                        <p class="text-sm text-slate-500"><?php echo htmlspecialchars($tenant['currency']); ?> <?php echo number_format($item['product']['sale_price']); ?> each</p>
                    </div>
                    <form method="POST" class="flex items-center gap-2">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">
                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="0" max="<?php echo $item['product']['stock_quantity']; ?>"
                               class="w-16 bg-slate-900 border border-white/10 rounded-lg text-center text-sm p-2 outline-none" onchange="this.form.submit()">
                    </form>
                    <p class="font-bold w-24 text-right"><?php echo htmlspecialchars($tenant['currency']); ?> <?php echo number_format($item['subtotal']); ?></p>
                    <form method="POST">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">
                        <button type="submit" class="text-red-400 hover:text-red-300 text-xs">✕</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="glass-card rounded-[2rem] p-6 flex justify-between items-center mb-6">
                <span class="text-slate-400 font-bold">Total</span>
                <span class="text-2xl font-bold text-sky-400"><?php echo htmlspecialchars($tenant['currency']); ?> <?php echo number_format($total); ?></span>
            </div>

            <a href="checkout.php?shop=<?php echo urlencode($slug); ?>" class="block w-full text-center bg-sky-500 hover:bg-sky-600 py-4 rounded-xl font-bold transition-all">
                Proceed to Checkout
            </a>
        <?php endif; ?>
    </main>
</body>
</html>
