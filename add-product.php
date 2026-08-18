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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { background-color: #020617; color: white; } .glass { background: rgba(15,23,42,0.6); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
    <!-- Kitufe cha Menu kwa ajili ya Simu -->
<div class="lg:hidden flex items-center justify-between p-4 bg-slate-900 border-b border-white/5 mb-4 rounded-xl">
    <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-sky-500 rounded-lg flex items-center justify-center font-bold text-slate-950 text-xs">SB</div>
        <span class="font-bold text-sm">Strong Bridge</span>
    </div>
    <button onclick="toggleSidebar()" class="p-2 text-slate-400 hover:bg-white/5 rounded-lg">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
    </button>
</div>
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
