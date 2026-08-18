<?php
require 'db.php';
require 'includes/functions.php'; 
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$tenant_id = $_SESSION['tenant_id'];

// 1. Pata bidhaa ambazo zipo stoo tu
$stmt = $pdo->prepare("SELECT * FROM products WHERE tenant_id = ? AND stock_quantity > 0 ORDER BY name ASC");
$stmt->execute([$tenant_id]);
$products = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $qty_to_sell = $_POST['quantity'];

    // Pata bei na stock ya sasa
    $stmt = $pdo->prepare("SELECT name, sale_price, stock_quantity FROM products WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$product_id, $tenant_id]);
    $product = $stmt->fetch();

    if ($product && $product['stock_quantity'] >= $qty_to_sell) {
        $total_price = $product['sale_price'] * $qty_to_sell;

        try {
            $pdo->beginTransaction();

           
            $stmt = $pdo->prepare("INSERT INTO sales (tenant_id, product_id, quantity, total_amount) VALUES (?, ?, ?, ?)");
            $stmt->execute([$tenant_id, $product_id, $qty_to_sell, $total_price]);

            
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
            $stmt->execute([$qty_to_sell, $product_id]);

            $stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE account_name = 'Cash on Hand' AND tenant_id = ?");
            $stmt->execute([$total_price, $tenant_id]);

            $stmt = $pdo->prepare("INSERT INTO journal_entries (tenant_id, account_id, description, debit) 
                                   VALUES (?, (SELECT id FROM accounts WHERE account_name = 'Cash on Hand' AND tenant_id = ? LIMIT 1), ?, ?)");
            $stmt->execute([$tenant_id, $tenant_id, "Sale of " . $product['name'], $total_price]);

            $pdo->commit();

            logAction($pdo, "Product Sale", "Sold $qty_to_sell units of {$product['name']} for TZS " . number_format($total_price));

            header("Location: inventory.php?msg=sale_complete");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            die("Kosa la Kiufundi: " . $e->getMessage());
        }
    } else {
        $error = "Stock is not enough!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale — Strong Bridge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #020617; color: white; font-family: 'Inter', sans-serif; } 
        .glass { background: rgba(15,23,42,0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
    </style>
</head>
<body class="min-h-screen p-8 flex items-center justify-center">
    <?php include 'includes/sidebar.php'; ?>

    <div class="glass w-full max-w-md p-10 rounded-[2.5rem] shadow-2xl lg:ml-64">
        <h2 class="text-3xl font-bold">Make sales (POS)</h2>
        <p class="text-slate-400 text-sm mt-2">Select the product and the quantity you are selling.</p>

        <?php if(isset($error)): ?>
            <div class="mt-4 p-3 bg-red-500/10 border border-red-500/20 text-red-400 text-xs rounded-xl">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="mt-8 space-y-6">
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">Product</label>
                <select name="product_id" required class="mt-2 w-full bg-slate-900 border border-white/10 rounded-xl p-4 text-sm outline-none focus:ring-2 focus:ring-sky-500 text-white">
                    <option value="">-- Choose product --</option>
                    <?php foreach($products as $p): ?>
                        <option value="<?php echo $p['id']; ?>">
                            <?php echo htmlspecialchars($p['name']); ?> (Stock: <?php echo $p['stock_quantity']; ?> | Price: <?php echo number_format($p['sale_price']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">Quantity</label>
                <input type="number" name="quantity" min="1" value="1" required class="mt-2 w-full bg-slate-900 border border-white/10 rounded-xl p-4 text-sm outline-none focus:ring-2 focus:ring-sky-500 text-white">
            </div>

            <button type="submit" class="w-full bg-sky-500 py-4 rounded-2xl font-bold hover:bg-sky-600 transition-all shadow-lg shadow-sky-500/20">
                Complete Sales
            </button>
            <a href="inventory.php" class="block text-center text-sm text-slate-500 mt-4 hover:underline">Go back to stock</a>
        </form>
    </div>
</body>
</html>
