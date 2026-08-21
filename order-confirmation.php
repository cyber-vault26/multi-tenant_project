<?php
require 'db.php';

$orderNumber = $_GET['order'] ?? '';

$stmt = $pdo->prepare("SELECT o.*, t.name AS tenant_name, t.store_slug, t.currency FROM online_orders o JOIN tenants t ON t.id = o.tenant_id WHERE o.order_number = ?");
$stmt->execute([$orderNumber]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    die("Order not found.");
}

$stmt = $pdo->prepare("SELECT * FROM online_order_items WHERE order_id = ?");
$stmt->execute([$order['id']]);
$items = $stmt->fetchAll();

$statusSteps = ['pending' => 1, 'confirmed' => 2, 'shipped' => 3, 'delivered' => 4];
$currentStep = $statusSteps[$order['status']] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order <?php echo htmlspecialchars($order['order_number']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #020617; color: white; }
        .glass-card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="text-slate-200">
    <main class="max-w-2xl mx-auto px-6 py-16">
        <?php if ($order['status'] !== 'cancelled'): ?>
        <div class="text-center mb-10">
            <div class="w-16 h-16 bg-emerald-500/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h1 class="text-2xl font-bold">Order Placed!</h1>
            <p class="text-slate-500 text-sm mt-1">Thanks, <?php echo htmlspecialchars($order['customer_name']); ?> — your order from <?php echo htmlspecialchars($order['tenant_name']); ?> is on its way to being processed.</p>
        </div>
        <?php else: ?>
        <div class="text-center mb-10">
            <h1 class="text-2xl font-bold text-red-400">Order Cancelled</h1>
        </div>
        <?php endif; ?>

        <div class="glass-card rounded-[2rem] p-8 mb-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold">Order Number</p>
                    <p class="font-bold text-lg"><?php echo htmlspecialchars($order['order_number']); ?></p>
                </div>
                <span class="px-4 py-2 rounded-xl text-xs font-bold uppercase bg-sky-500/10 text-sky-400"><?php echo htmlspecialchars($order['status']); ?></span>
            </div>

            <?php if ($order['status'] !== 'cancelled'): ?>
            <div class="flex items-center justify-between mb-8 px-2">
                <?php foreach (['pending' => 'Placed', 'confirmed' => 'Confirmed', 'shipped' => 'Shipped', 'delivered' => 'Delivered'] as $key => $label): ?>
                <div class="flex flex-col items-center flex-1">
                    <div class="w-3 h-3 rounded-full <?php echo $statusSteps[$key] <= $currentStep ? 'bg-sky-400' : 'bg-slate-700'; ?>"></div>
                    <p class="text-[10px] mt-2 <?php echo $statusSteps[$key] <= $currentStep ? 'text-sky-400 font-bold' : 'text-slate-600'; ?>"><?php echo $label; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="space-y-2 border-t border-white/10 pt-4">
                <?php foreach ($items as $item): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-300"><?php echo $item['quantity']; ?>× <?php echo htmlspecialchars($item['product_name_snapshot']); ?></span>
                    <span class="font-bold"><?php echo htmlspecialchars($order['currency']); ?> <?php echo number_format($item['subtotal']); ?></span>
                </div>
                <?php endforeach; ?>
                <div class="border-t border-white/10 pt-3 flex justify-between font-bold text-lg">
                    <span>Total</span>
                    <span class="text-sky-400"><?php echo htmlspecialchars($order['currency']); ?> <?php echo number_format($order['total_amount']); ?></span>
                </div>
            </div>
        </div>

        <a href="store.php?shop=<?php echo urlencode($order['store_slug']); ?>" class="block text-center text-sky-400 font-bold hover:underline">← Continue Shopping</a>
    </main>
</body>
</html>
