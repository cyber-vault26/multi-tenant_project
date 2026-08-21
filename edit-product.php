<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$tenant_id = $_SESSION['tenant_id'];

$product_id = $_GET['id'] ?? null;

if (!$product_id) { header("Location: inventory.php"); exit(); }

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND tenant_id = ?");
$stmt->execute([$product_id, $tenant_id]);
$product = $stmt->fetch();

if (!$product) { die("Bidhaa haijapatikana."); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $sku = $_POST['sku'];
    $p_price = $_POST['purchase_price'];
    $s_price = $_POST['sale_price'];
    $qty = $_POST['stock_quantity'];
    $description = trim($_POST['description'] ?? '') ?: null;
    $imageUrl = trim($_POST['image_url'] ?? '') ?: null;
    $isPublished = isset($_POST['is_published']) ? 1 : 0;

    $update_stmt = $pdo->prepare("UPDATE products SET name = ?, sku = ?, purchase_price = ?, sale_price = ?, stock_quantity = ?, description = ?, image_url = ?, is_published = ? WHERE id = ? AND tenant_id = ?");
    $update_stmt->execute([$name, $sku, $p_price, $s_price, $qty, $description, $imageUrl, $isPublished, $product_id, $tenant_id]);

    header("Location: inventory.php?msg=updated");
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
    <div class="glass w-full max-w-lg p-10 rounded-[2.5rem]">
        <h2 class="text-2xl font-bold mb-2">Edit Product</h2>
        <p class="text-slate-500 text-sm mb-8">Change Product's info: <?php echo htmlspecialchars($product['name']); ?></p>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="text-[10px] uppercase font-bold text-slate-500 ml-1">Product Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-white outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            
            <div>
                <label class="text-[10px] uppercase font-bold text-slate-500 ml-1">SKU Code</label>
                <input type="text" name="sku" value="<?php echo htmlspecialchars($product['sku']); ?>" class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-white outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-[10px] uppercase font-bold text-slate-500 ml-1">Purchase Price</label>
                    <input type="number" name="purchase_price" value="<?php echo $product['purchase_price']; ?>" required class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-white outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                <div>
                    <label class="text-[10px] uppercase font-bold text-slate-500 ml-1">Sale Price</label>
                    <input type="number" name="sale_price" value="<?php echo $product['sale_price']; ?>" required class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-white outline-none focus:ring-2 focus:ring-sky-500">
                </div>
            </div>
            
            <div>
                <label class="text-[10px] uppercase font-bold text-slate-500 ml-1">Current Stock</label>
                <input type="number" name="stock_quantity" value="<?php echo $product['stock_quantity']; ?>" required class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-white outline-none focus:ring-2 focus:ring-sky-500">
            </div>

            <div class="border-t border-white/10 pt-4 mt-4">
                <p class="text-[10px] uppercase font-bold text-slate-500 mb-3">Online Store</p>
                <div>
                    <label class="text-[10px] uppercase font-bold text-slate-500 ml-1">Description</label>
                    <textarea name="description" rows="2" class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-white outline-none focus:ring-2 focus:ring-sky-500"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                </div>
                <div class="mt-3">
                    <label class="text-[10px] uppercase font-bold text-slate-500 ml-1">Image URL</label>
                    <input type="text" name="image_url" value="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>" placeholder="https://..." class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-white outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                <label class="flex items-center gap-2 mt-3 text-sm">
                    <input type="checkbox" name="is_published" value="1" <?php echo !empty($product['is_published']) ? 'checked' : ''; ?> class="w-5 h-5 accent-sky-500">
                    Show this product on the online store
                </label>
            </div>

            <button type="submit" class="w-full bg-sky-500 py-4 rounded-xl font-bold hover:bg-sky-600 transition-all">Save Changes</button>
            <a href="inventory.php" class="block text-center text-sm text-slate-500 mt-4 underline">Cancel</a>
        </form>
    </div>
</body>
</html>
