<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$tenant_id = $_SESSION['tenant_id'];

// Pull members with their computed running balances in one query.
// Shares balance = SUM(purchase.amount) - SUM(withdrawal.amount), same for savings.
$stmt = $pdo->prepare("
    SELECT
        m.id, m.member_number, m.full_name, m.phone, m.join_date, m.status,
        COALESCE(SUM(CASE WHEN st.type = 'purchase'   THEN st.amount ELSE 0 END), 0)
      - COALESCE(SUM(CASE WHEN st.type = 'withdrawal' THEN st.amount ELSE 0 END), 0) AS shares_balance,
        COALESCE(SUM(CASE WHEN st.type = 'purchase'   THEN st.shares_count ELSE 0 END), 0)
      - COALESCE(SUM(CASE WHEN st.type = 'withdrawal' THEN st.shares_count ELSE 0 END), 0) AS shares_count,
        COALESCE((
            SELECT SUM(CASE WHEN sv.type = 'deposit' THEN sv.amount ELSE -sv.amount END)
            FROM savings_transactions sv WHERE sv.member_id = m.id
        ), 0) AS savings_balance
    FROM sacco_members m
    LEFT JOIN share_transactions st ON st.member_id = m.id
    WHERE m.tenant_id = ?
    GROUP BY m.id
    ORDER BY m.created_at DESC
");
$stmt->execute([$tenant_id]);
$members = $stmt->fetchAll();

// Totals for the summary header
$totalShares = array_sum(array_column($members, 'shares_balance'));
$totalSavings = array_sum(array_column($members, 'savings_balance'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SACCOS Members — Strong Bridge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #020617; color: white; }
        .glass-card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="text-slate-200">
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

    <main class="lg:ml-64 p-8">
        <header class="flex flex-wrap justify-between items-center gap-4 mb-10">
            <div>
                <h1 class="text-2xl font-bold text-white">SACCOS Members</h1>
                <p class="text-sm text-slate-500">Manage member shares, savings, and dividends.</p>
            </div>
            <a href="add-sacco-member.php" class="bg-sky-500 hover:bg-sky-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all">
                + Register New Member
            </a>
        </header>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'registered'): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 text-emerald-400 text-sm font-medium">Member registered successfully.</div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="glass-card rounded-2xl p-6">
                <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold">Total Members</p>
                <p class="text-3xl font-bold mt-2"><?php echo count($members); ?></p>
            </div>
            <div class="glass-card rounded-2xl p-6">
                <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold">Total Share Capital</p>
                <p class="text-3xl font-bold mt-2 text-sky-400">TZS <?php echo number_format($totalShares); ?></p>
            </div>
            <div class="glass-card rounded-2xl p-6">
                <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold">Total Savings</p>
                <p class="text-3xl font-bold mt-2 text-emerald-400">TZS <?php echo number_format($totalSavings); ?></p>
            </div>
        </div>

        <div class="glass-card rounded-[2rem] overflow-hidden">
            <table class="w-full text-left text-sm border-collapse">
                <thead class="bg-white/5 text-slate-500 uppercase text-[10px] tracking-widest">
                    <tr>
                        <th class="p-5">Member</th>
                        <th class="p-5">Phone</th>
                        <th class="p-5">Shares</th>
                        <th class="p-5">Savings</th>
                        <th class="p-5">Status</th>
                        <th class="p-5">Joined</th>
                        <th class="p-5"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $m): ?>
                    <tr class="border-t border-white/5 hover:bg-white/[0.02]">
                        <td class="p-5">
                            <p class="font-bold text-white"><?php echo htmlspecialchars($m['full_name']); ?></p>
                            <p class="text-xs text-slate-600"><?php echo htmlspecialchars($m['member_number'] ?: ('SM-' . str_pad($m['id'], 4, '0', STR_PAD_LEFT))); ?></p>
                        </td>
                        <td class="p-5 text-slate-400"><?php echo htmlspecialchars($m['phone'] ?: '—'); ?></td>
                        <td class="p-5">
                            <p class="font-bold">TZS <?php echo number_format($m['shares_balance']); ?></p>
                            <p class="text-xs text-slate-600"><?php echo (int) $m['shares_count']; ?> shares</p>
                        </td>
                        <td class="p-5 font-bold text-emerald-400">TZS <?php echo number_format($m['savings_balance']); ?></td>
                        <td class="p-5">
                            <span class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase <?php echo $m['status'] === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-500/10 text-slate-500'; ?>">
                                <?php echo htmlspecialchars($m['status']); ?>
                            </span>
                        </td>
                        <td class="p-5 text-slate-500"><?php echo date('M d, Y', strtotime($m['join_date'])); ?></td>
                        <td class="p-5">
                            <a href="sacco-member.php?id=<?php echo $m['id']; ?>" class="text-sky-400 hover:underline font-bold text-xs">Manage →</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($members)): ?>
                    <tr><td colspan="7" class="p-8 text-center text-slate-600 italic">No members registered yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
