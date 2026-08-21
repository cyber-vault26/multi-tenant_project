<?php
require 'db.php';
session_start();

$slug = $_GET['shop'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM tenants WHERE store_slug = ? AND store_enabled = 1 AND status = 'active'");
$stmt->execute([$slug]);
$tenant = $stmt->fetch();

if (!$tenant) {
    http_response_code(404);
    die("This store is not available. It may not exist, or the owner hasn't enabled it yet.");
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE tenant_id = ? AND is_published = 1 ORDER BY name ASC");
$stmt->execute([$tenant['id']]);
$products = $stmt->fetchAll();

$cartKey = 'cart_' . $tenant['id'];
$cartCount = isset($_SESSION[$cartKey]) ? array_sum($_SESSION[$cartKey]) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tenant['name']); ?> — Online Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #020617; color: white; }
        .glass-card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="text-slate-200">
    <header class="border-b border-white/5 sticky top-0 bg-slate-950/90 backdrop-blur z-10">
        <div class="max-w-6xl mx-auto px-6 py-5 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <?php if (!empty($tenant['logo_path'])): ?>
                    <img src="<?php echo htmlspecialchars($tenant['logo_path']); ?>" class="w-10 h-10 rounded-xl object-cover">
                <?php else: ?>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-slate-950 text-sm" style="background-color: <?php echo htmlspecialchars($tenant['brand_color'] ?: '#0ea5e9'); ?>">
                        <?php echo strtoupper(substr($tenant['name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <span class="font-bold text-lg"><?php echo htmlspecialchars($tenant['name']); ?></span>
            </div>
            <a href="cart.php?shop=<?php echo urlencode($slug); ?>" class="relative bg-white/5 border border-white/10 hover:bg-white/10 px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Cart
                <?php if ($cartCount > 0): ?>
                    <span class="absolute -top-2 -right-2 bg-sky-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center"><?php echo $cartCount; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-10">
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'added'): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 text-emerald-400 text-sm font-medium">Added to cart.</div>
        <?php endif; ?>

        <h1 class="text-3xl font-bold mb-1">Shop <?php echo htmlspecialchars($tenant['name']); ?></h1>
        <p class="text-slate-500 text-sm mb-8"><?php echo count($products); ?> products available</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($products as $p): ?>
            <div class="glass-card rounded-[2rem] overflow-hidden flex flex-col">
                <div class="aspect-square bg-slate-900 flex items-center justify-center overflow-hidden">
                    <?php if (!empty($p['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($p['image_url']); ?>" class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($p['name']); ?>">
                    <?php else: ?>
                        <svg class="w-16 h-16 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <?php endif; ?>
                </div>
                <div class="p-5 flex flex-col flex-1">
                    <h3 class="font-bold text-white"><?php echo htmlspecialchars($p['name']); ?></h3>
                    <?php if (!empty($p['description'])): ?>
                        <p class="text-xs text-slate-500 mt-1 line-clamp-2"><?php echo htmlspecialchars($p['description']); ?></p>
                    <?php endif; ?>
                    <div class="flex-1"></div>
                    <div class="flex justify-between items-center mt-4">
                        <span class="font-bold text-lg text-sky-400"><?php echo htmlspecialchars($tenant['currency']); ?> <?php echo number_format($p['sale_price']); ?></span>
                        <?php if ($p['stock_quantity'] > 0): ?>
                            <form method="POST" action="cart.php?shop=<?php echo urlencode($slug); ?>">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                <button type="submit" class="bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl text-xs font-bold transition-all">Add to Cart</button>
                            </form>
                        <?php else: ?>
                            <span class="text-xs text-red-400 font-bold uppercase">Out of stock</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
                <p class="col-span-full text-center text-slate-600 italic py-16">No products available yet. Check back soon.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
