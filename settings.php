<?php
require 'db.php';
require 'includes/functions.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit();
}

$tenant_id = $_SESSION['tenant_id'];

$stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
$stmt->execute([$tenant_id]);
$tenant = $stmt->fetch();
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $currency = $_POST['currency'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $storeEnabled = isset($_POST['store_enabled']) ? 1 : 0;
    $storeSlugInput = trim($_POST['store_slug'] ?? '');
    // Keep slugs URL-safe: lowercase, alphanumeric + dashes only.
    $storeSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $storeSlugInput));
    $storeSlug = trim($storeSlug, '-') ?: null;

    if ($storeSlug) {
        // Make sure another tenant hasn't already taken this slug.
        $check = $pdo->prepare("SELECT id FROM tenants WHERE store_slug = ? AND id != ?");
        $check->execute([$storeSlug, $tenant_id]);
        if ($check->fetch()) {
            die("That store URL is already taken by another business. Please choose a different one.");
        }
    }

    $stmt = $pdo->prepare("UPDATE tenants SET name = ?, currency = ?, address = ?, phone = ?, store_enabled = ?, store_slug = ? WHERE id = ?");
    $stmt->execute([$name, $currency, $address, $phone, $storeEnabled, $storeSlug, $tenant_id]);
    
    logAction($pdo, "Settings Updated", "Updated workspace branding and info.");
    header("Location: settings.php?msg=updated");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace Settings — Strong Bridge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { background-color: #020617; color: white; } .glass { background: rgba(15,23,42,0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }</style>
</head>
<body class="p-8 lg:ml-64 bg-slate-950 min-h-screen">
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
    <?php include 'includes/sidebar.php'; ?>

    <div class="max-w-3xl mx-auto">
        <header class="mb-10">
            <h1 class="text-3xl font-bold">Workspace Settings</h1>
            <p class="text-slate-500 text-sm">Manage your company branding and configuration.</p>
        </header>

        <?php if(isset($_GET['msg'])) echo "<div class='bg-emerald-500/10 text-emerald-400 p-4 rounded-xl mb-6 border border-emerald-500/20'>Changes saved successfully!</div>"; ?>

        <div class="glass p-10 rounded-[2.5rem]">
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Company Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($tenant['name']); ?>" required 
                            class="mt-2 w-full bg-slate-900 border border-white/10 rounded-2xl p-4 text-sm text-white focus:ring-2 focus:ring-sky-500 outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Currency Code</label>
                        <select name="currency" class="mt-2 w-full bg-slate-900 border border-white/10 rounded-2xl p-4 text-sm text-white focus:ring-2 focus:ring-sky-500 outline-none">
                            <option value="TZS" <?php if($tenant['currency'] == 'TZS') echo 'selected'; ?>>TZS (Shilingi)</option>
                            <option value="USD" <?php if($tenant['currency'] == 'USD') echo 'selected'; ?>>USD (Dollar)</option>
                            <option value="KES" <?php if($tenant['currency'] == 'KES') echo 'selected'; ?>>KES (Kenya Shilling)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Business Phone</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($tenant['phone']); ?>" 
                        class="mt-2 w-full bg-slate-900 border border-white/10 rounded-2xl p-4 text-sm text-white focus:ring-2 focus:ring-sky-500 outline-none">
                </div>

                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Physical Address</label>
                    <textarea name="address" rows="3" 
                        class="mt-2 w-full bg-slate-900 border border-white/10 rounded-2xl p-4 text-sm text-white focus:ring-2 focus:ring-sky-500 outline-none"><?php echo htmlspecialchars($tenant['address']); ?></textarea>
                </div>

                <button type="submit" class="w-full bg-sky-500 py-4 rounded-2xl font-bold text-sm shadow-lg shadow-sky-500/20 hover:bg-sky-600 transition-all">
                    Save Workspace Settings
		</button>

                <div class="pt-6 border-t border-white/5">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4">Online Store</h3>
                    <label class="flex items-center gap-3 cursor-pointer mb-4">
                        <input type="checkbox" name="store_enabled" value="1" <?php echo !empty($tenant['store_enabled']) ? 'checked' : ''; ?> class="w-5 h-5 rounded bg-slate-900 border-white/10 text-sky-500">
                        <span class="text-sm">Enable public online storefront</span>
                    </label>
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Store URL</label>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-xs text-slate-600 whitespace-nowrap">/store.php?shop=</span>
                        <input type="text" name="store_slug" value="<?php echo htmlspecialchars($tenant['store_slug'] ?? ''); ?>"
                               placeholder="your-business-name"
                               class="w-full bg-slate-900 border border-white/10 rounded-xl p-3 text-sm text-white focus:ring-2 focus:ring-sky-500 outline-none">
                    </div>
                    <?php if (!empty($tenant['store_slug']) && !empty($tenant['store_enabled'])): ?>
                        <p class="text-xs text-emerald-400 mt-2">
                            Live at: <a href="store.php?shop=<?php echo urlencode($tenant['store_slug']); ?>" target="_blank" class="underline">store.php?shop=<?php echo htmlspecialchars($tenant['store_slug']); ?></a>
                        </p>
                    <?php endif; ?>
                    <p class="text-xs text-slate-600 mt-2">Mark products as "Show on online store" in Inventory to make them appear here.</p>
                </div>
                
                <div class="pt-6 border-t border-white/5">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4">Enable Modules</h3>
                <div class="space-y-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="has_microfinance" value="1" <?php echo $tenant['has_microfinance'] ? 'checked' : ''; ?> class="w-5 h-5 rounded bg-slate-900 border-white/10 text-sky-500">
                    <span class="text-sm">Microfinance & Loans</span>
                   </label>
                   <label class="flex items-center gap-3 cursor-pointer">
                   <input type="checkbox" name="has_retail" value="1" <?php echo $tenant['has_retail'] ? 'checked' : ''; ?> class="w-5 h-5 rounded bg-slate-900 border-white/10 text-sky-500">
                  <span class="text-sm">Retail & POS Stock</span>
                  </label>
                </div>
              </div>
            </form>
        </div>
    </div>
</body>
</html>
