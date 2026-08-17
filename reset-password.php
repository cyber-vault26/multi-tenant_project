<?php
require 'db.php';
date_default_timezone_set('Africa/Nairobi'); 

$token = $_GET['token'] ?? '';
$currentTime = date("Y-m-d H:i:s");


$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ?");
$stmt->execute([$token]);
$resetRequest = $stmt->fetch();


if (!$resetRequest) {
    die("<div style='background:#020617; color:white; height:100vh; display:flex; align-items:center; justify-content:center; font-family:sans-serif;'>
            <div style='text-align:center;'>
                <p>Token hii haipo au siyo sahihi.</p>
                <a href='forgot-password.php' style='color:#38bdf8;'>Omba nyingine hapa</a>
            </div>
         </div>");
}

if ($currentTime > $resetRequest['expires_at']) {
    die("<div style='background:#020617; color:white; height:100vh; display:flex; align-items:center; justify-content:center; font-family:sans-serif;'>
            <div style='text-align:center;'>
                <p>Link hii imeshaisha muda wake (Expired).</p>
                <a href='forgot-password.php' style='color:#38bdf8;'>Omba nyingine hapa</a>
            </div>
         </div>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Password — Strong Bridge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #020617; }
        .bg-stardust {
            background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.05) 1px, transparent 0);
            background-size: 40px 40px;
        }
        .glass-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="min-h-screen text-slate-200 flex items-center justify-center p-6 relative">
    <div class="absolute inset-0 bg-stardust pointer-events-none"></div>

    <div class="relative z-10 w-full max-w-md">
        <div class="glass-card p-10 rounded-[2.5rem] shadow-2xl">
            <h2 class="text-3xl font-bold text-white tracking-tight">New Password</h2>
            <p class="mt-3 text-slate-400 text-sm">
                Enter new password<span class="text-sky-400"><?php echo htmlspecialchars($resetRequest['email']); ?></span>
            </p>

            <form action="reset_process.php" method="POST" class="mt-8 space-y-6">
               
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div>
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-widest ml-1">New Password</label>
                    <input type="password" name="new_password" required minlength="8" placeholder="••••••••" 
                        class="mt-2 w-full bg-slate-900/50 border border-white/10 rounded-2xl p-4 text-sm text-white focus:ring-2 focus:ring-sky-500 outline-none transition-all">
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-widest ml-1">Confirm Password</label>
                    <input type="password" name="confirm_password" required minlength="8" placeholder="••••••••" 
                        class="mt-2 w-full bg-slate-900/50 border border-white/10 rounded-2xl p-4 text-sm text-white focus:ring-2 focus:ring-sky-500 outline-none transition-all">
                </div>

                <button type="submit" 
                    class="w-full bg-gradient-to-r from-sky-400 to-sky-600 text-white py-4 rounded-2xl font-bold text-sm shadow-lg shadow-sky-500/20 hover:scale-[1.02] active:scale-[0.98] transition-all">
                    Update Password
                </button>
            </form>
        </div>
    </div>
</body>
</html>
