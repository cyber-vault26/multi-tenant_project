<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Strong Bridge Investment</title>
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
        .input-dark {
            background: rgba(2, 6, 23, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }
    </style>
</head>
<body class="min-h-screen text-slate-200 flex items-center justify-center p-6 relative">
   
    <div class="absolute inset-0 bg-stardust pointer-events-none"></div>
    <div class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(circle_at_50%_50%,rgba(56,189,248,0.08),transparent_50%)] pointer-events-none"></div>

    <div class="relative z-10 w-full max-w-md">
       
        <div class="flex items-center justify-center gap-3 mb-8">
            <div class="w-10 h-10 bg-sky-500/20 border border-sky-500/40 rounded-xl flex items-center justify-center text-sky-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
            </div>
            <span class="font-bold text-xl tracking-tight text-white">Strong Bridge</span>
        </div>

      
        <div class="glass-card p-10 rounded-[2.5rem] shadow-2xl">
            <h2 class="text-3xl font-bold text-white tracking-tight">Reset Password</h2>
            <p class="mt-3 text-slate-400 text-sm leading-relaxed">
               Enter email here to reset your password
            </p>

            <!-- PHP MESSAGES (Success/Error) -->
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'sent'): ?>
                <div class="mt-6 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs rounded-2xl flex items-start gap-3">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Check your email link already sent.</span>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="mt-6 p-4 bg-red-500/10 border border-red-500/20 text-red-400 text-xs rounded-2xl flex items-start gap-3">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Try again.</span>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form action="forgot_process.php" method="POST" class="mt-8 space-y-6">
                <div>
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-widest ml-1">Work email</label>
                    <input type="email" name="email" required placeholder="name@company.com" 
                        class="mt-2 w-full input-dark rounded-2xl p-4 text-sm focus:ring-2 focus:ring-sky-500 outline-none transition-all placeholder:text-slate-600">
                </div>

                <button type="submit" 
                    class="w-full bg-gradient-to-r from-sky-400 to-sky-600 text-white py-4 rounded-2xl font-bold text-sm shadow-lg shadow-sky-500/20 hover:scale-[1.02] active:scale-[0.98] transition-all">
                    Send Reset Link
                </button>
            </form>

            <!-- Footer Links -->
            <div class="mt-8 text-center">
                <a href="login.php" class="inline-flex items-center gap-2 text-sm text-sky-400 font-medium hover:text-sky-300 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Sign in
                </a>
            </div>
        </div>

        <p class="mt-8 text-center text-[11px] text-slate-600 uppercase tracking-[0.2em]">
            © 2026 Strong Bridge Investment · Secured Workspace
        </p>
    </div>
</body>
</html>
