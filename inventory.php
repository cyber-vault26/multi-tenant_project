<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$tenant_id = $_SESSION['tenant_id'];

// Pata bidhaa za kampuni hii tu
$stmt = $pdo->prepare("SELECT * FROM products WHERE tenant_id = ? ORDER BY name ASC");
$stmt->execute([$tenant_id]);
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory — Strong Bridge ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { background-color: #020617; color: white; } .glass { background: rgba(15,23,42,0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }</style>
</head>

<body class="text-slate-200">
    <?php include 'includes/sidebar.php'; ?>

    <main class="lg:ml-64 p-8">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-2xl font-bold">Inventory & Stock</h1>
                <p class="text-sm text-slate-500">Manage your products and stock levels.</p>
            </div>
            <a href="add-product.php" class="bg-sky-500 hover:bg-sky-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all">
                + Add Product
            </a>
	</header>
        <script>
          function confirmDelete(id) {
             if (confirm("Are you sure you want to delete this product?")) {
                window.location.href = "delete-product.php?id=" + id;
        }
    }
       </script>
        <div class="grid grid-cols-1 gap-6">
            <div class="glass rounded-[2rem] overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-white/5 text-slate-500 uppercase text-[10px]">
                        <tr>
                            <th class="p-5">Product Name</th>
                            <th class="p-5">SKU</th>
                            <th class="p-5">Cost Price</th>
                            <th class="p-5">Sale Price</th>
                            <th class="p-5">In Stock</th>
                            <th class="p-5">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($products as $p): ?>
                        <tr class="border-t border-white/5 hover:bg-white/5 transition-all">
                            <td class="p-5 font-bold text-white"><?php echo htmlspecialchars($p['name']); ?></td>
                            <td class="p-5 text-slate-400"><?php echo htmlspecialchars($p['sku']); ?></td>
                            <td class="p-5"><?php echo number_format($p['purchase_price']); ?></td>
                            <td class="p-5 text-sky-400 font-bold"><?php echo number_format($p['sale_price']); ?></td>
                            <td class="p-5">
                                <span class="px-3 py-1 rounded-lg <?php echo ($p['stock_quantity'] < 10) ? 'bg-red-500/10 text-red-500' : 'bg-emerald-500/10 text-emerald-500'; ?>">
                                    <?php echo $p['stock_quantity']; ?> units
                                </span>
			    </td>
                            <td class="p-5 flex gap-4">
                                 <a href="edit-product.php?id=<?php echo $p['id']; ?>" class="text-sky-400 hover:underline">Edit</a>
                
                            <button onclick="confirmDelete(<?php echo $p['id']; ?>)" class="text-red-500 hover:underline">
                                Delete
                            </button>
                           </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($products)): ?>
                            <tr><td colspan="6" class="p-10 text-center text-slate-500 italic">No products found. Add your first product to start selling.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
