<?php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $sku = $_POST['sku'];
    $p_price = $_POST['purchase_price'];
    $s_price = $_POST['sale_price'];
    $qty = $_POST['stock_quantity'];
    $tenant_id = $_SESSION['tenant_id'];

    $stmt = $pdo->prepare("INSERT INTO products (tenant_id, name, sku, purchase_price, sale_price, stock_quantity) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$tenant_id, $name, $sku, $p_price, $s_price, $qty]);

    header("Location: inventory.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { background-color: #020617; color: white; } .glass { background: rgba(15,23,42,0.6); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
    <div class="glass w-full max-w-lg p-10 rounded-[2.5rem]">
        <h2 class="text-2xl font-bold mb-6">Register New Product</h2>
        <form method="POST" class="space-y-4">
            <input type="text" name="name" placeholder="Jina la Bidhaa" required class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-white outline-none focus:ring-2 focus:ring-sky-500">
            <input type="text" name="sku" placeholder="Code/SKU (Optional)" class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-white outline-none focus:ring-2 focus:ring-sky-500">
            
            <div class="grid grid-cols-2 gap-4">
                <input type="number" name="purchase_price" placeholder="Bei ya Kununua" required class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-white outline-none focus:ring-2 focus:ring-sky-500">
                <input type="number" name="sale_price" placeholder="Bei ya Kuuza" required class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-white outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            
            <input type="number" name="stock_quantity" placeholder="Kiasi kilichopo (Quantity)" required class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-white outline-none focus:ring-2 focus:ring-sky-500">
            
            <button type="submit" class="w-full bg-sky-500 py-4 rounded-xl font-bold">Store Product</button>
            <a href="inventory.php" class="block text-center text-sm text-slate-500 mt-4">Cancel</a>
        </form>
    </div>
</body>
</html>
