<?php
require 'db.php';
require 'includes/functions.php';
session_start();

if (!isset($_SESSION['tenant_id']) || empty($_SESSION['tenant_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $phone = trim($_POST['phone'] ?? '') ?: null;
    $nationalId = trim($_POST['national_id'] ?? '') ?: null;
    $memberNumber = trim($_POST['member_number'] ?? '') ?: null;
    $joinDate = $_POST['join_date'] ?: date('Y-m-d');
    $tenant_id = $_SESSION['tenant_id'];

    try {
        $stmt = $pdo->prepare("INSERT INTO sacco_members (tenant_id, member_number, full_name, phone, national_id, join_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tenant_id, $memberNumber, $fullName, $phone, $nationalId, $joinDate]);

        logAction($pdo, "SACCOS Member Registered", "Registered new member: $fullName");

        header("Location: sacco-members.php?msg=registered");
        exit();
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Member — Strong Bridge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { background-color: #020617; color: white; }</style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="lg:hidden flex items-center justify-between p-4 bg-slate-900 border-b border-white/5 mb-4 rounded-xl">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-sky-500 rounded-lg flex items-center justify-center font-bold text-slate-950 text-xs">SB</div>
            <span class="font-bold text-sm">Strong Bridge</span>
        </div>
        <button onclick="toggleSidebar()" class="p-2 text-slate-400 hover:bg-white/5 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
        </button>
    </div>
    <div class="bg-slate-900 border border-white/10 p-10 rounded-[2.5rem] w-full max-w-md">
        <h2 class="text-2xl font-bold">Register SACCOS Member</h2>
        <p class="text-slate-500 text-sm mt-1">Add a new member to the cooperative.</p>
        <form method="POST" class="mt-6 space-y-4">
            <input type="text" name="full_name" placeholder="Full Name" required class="w-full bg-black/40 border border-white/10 p-4 rounded-xl text-white outline-none">
            <input type="text" name="member_number" placeholder="Member Number (optional — auto-assigned if blank)" class="w-full bg-black/40 border border-white/10 p-4 rounded-xl text-white outline-none">
            <input type="text" name="phone" placeholder="Phone Number" class="w-full bg-black/40 border border-white/10 p-4 rounded-xl text-white outline-none">
            <input type="text" name="national_id" placeholder="National ID Number" class="w-full bg-black/40 border border-white/10 p-4 rounded-xl text-white outline-none">
            <div>
                <label class="text-xs text-slate-500 uppercase tracking-widest ml-1">Join Date</label>
                <input type="date" name="join_date" value="<?php echo date('Y-m-d'); ?>" class="w-full bg-black/40 border border-white/10 p-4 rounded-xl text-white outline-none mt-1">
            </div>
            <button type="submit" class="w-full bg-sky-500 py-4 rounded-xl font-bold">Register Member</button>
            <a href="sacco-members.php" class="block text-center text-sm text-slate-500 mt-4">Cancel</a>
        </form>
    </div>
</body>
</html>
