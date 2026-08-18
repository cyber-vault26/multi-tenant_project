<?php
require 'db.php';
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$tenant_id = $_SESSION['tenant_id'];


$stmt = $pdo->prepare("SELECT id, full_name, email, role, created_at FROM users WHERE tenant_id = ? ORDER BY created_at DESC");
$stmt->execute([$tenant_id]);
$staff = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management — Strong Bridge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #020617; color: white; font-family: 'Inter', sans-serif; }
        .glass-card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="text-slate-200">
    <?php include 'includes/sidebar.php'; ?>

    <main class="lg:ml-64 p-8">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-bold">Wafanyakazi (Staff)</h1>
                <p class="text-slate-500 text-sm">Simamia timu inayofanya kazi kwenye workspace yako.</p>
            </div>
            <a href="add-staff.php" class="bg-sky-500 hover:bg-sky-600 text-white px-6 py-3 rounded-xl text-sm font-bold transition-all">
                + Sajili Mfanyakazi
            </a>
        </header>

        <div class="glass-card rounded-[2rem] overflow-hidden">
            <table class="w-full text-left text-sm border-collapse">
                <thead class="bg-white/5 text-slate-500 uppercase text-[10px] tracking-widest">
                    <tr>
                        <th class="p-5">Jina Kamili</th>
                        <th class="p-5">Email</th>
                        <th class="p-5">Role</th>
                        <th class="p-5">Siku ya Kujiunga</th>
                        <th class="p-5">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($staff as $s): ?>
                    <tr class="border-t border-white/5 hover:bg-white/5 transition-all">
                        <td class="p-5 font-bold text-white"><?php echo htmlspecialchars($s['full_name']); ?></td>
                        <td class="p-5 text-slate-400"><?php echo htmlspecialchars($s['email']); ?></td>
                        <td class="p-5">
                            <span class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase <?php echo ($s['role'] == 'admin') ? 'bg-sky-500/10 text-sky-400' : 'bg-slate-500/10 text-slate-400'; ?>">
                                <?php echo $s['role']; ?>
                            </span>
                        </td>
                        <td class="p-5 text-slate-500"><?php echo date('M d, Y', strtotime($s['created_at'])); ?></td>
                        <td class="p-5">
                            <?php if($s['id'] != $_SESSION['user_id']): ?>
                                <button class="text-red-500 hover:underline">Remove</button>
                            <?php else: ?>
                                <span class="text-slate-600 italic">You</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
