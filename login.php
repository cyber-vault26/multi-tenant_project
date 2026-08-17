<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign in — Strong Bridge ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #020617; color: white; }
        .glass-card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .input-dark { background: rgba(2, 6, 23, 0.5); border: 1px solid rgba(255, 255, 255, 0.1); color: white; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Background Design -->
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(56,189,248,0.1),transparent_50%)] pointer-events-none"></div>

    <div class="glass-card w-full max-w-md p-10 rounded-[2.5rem] shadow-2xl relative z-10">
        <h2 id="form-title" class="text-3xl font-bold tracking-tight">Welcome back</h2>
        <p id="form-subtitle" class="mt-2 text-slate-400 text-sm">Sign in to continue to your workspace.</p>

        <!-- Ujumbe wa Makosa -->
        <?php if (isset($_GET['error'])): ?>
            <div class="mt-6 p-3 bg-red-500/10 border border-red-500/20 text-red-400 text-xs rounded-xl">
                <?php 
                    if($_GET['error'] == 'email_exists') echo "Email is already registered!";
                    else if($_GET['error'] == 'pass_mismatch') echo "Passwords do not match!";
                    else echo "Invalid email or password.";
                ?>
            </div>
        <?php endif; ?>

        <form action="auth_process.php" method="POST" class="mt-8 space-y-4">
            <input type="hidden" name="mode" id="form-mode" value="signin">

            <div id="name-field" class="hidden">
                <label class="text-[10px] font-bold text-slate-500 uppercase ml-1">Full Name</label>
                <input type="text" name="full_name" class="mt-1 w-full input-dark rounded-xl p-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
            </div>

            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase ml-1">Work Email</label>
                <input type="email" name="email" required class="mt-1 w-full input-dark rounded-xl p-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
            </div>

            <div class="relative">
                <label class="text-[10px] font-bold text-slate-500 uppercase ml-1">Password</label>
                <input type="password" name="password" id="p1" required class="mt-1 w-full input-dark rounded-xl p-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                <button type="button" onclick="toggle('p1')" class="absolute right-3 top-8 text-slate-500 hover:text-white">👁️</button>
            </div>

            <div id="confirm-pass-field" class="hidden relative">
                <label class="text-[10px] font-bold text-slate-500 uppercase ml-1">Confirm Password</label>
                <input type="password" name="confirm_password" id="p2" class="mt-1 w-full input-dark rounded-xl p-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                <button type="button" onclick="toggle('p2')" class="absolute right-3 top-8 text-slate-500 hover:text-white">👁️</button>
            </div>

            <button type="submit" class="w-full bg-sky-500 text-white py-3.5 rounded-xl font-bold text-sm shadow-lg hover:bg-sky-600 transition-all mt-4">
                <span id="submit-text">Sign in</span>
            </button>
        </form>

        <div class="mt-8 flex items-center justify-between text-sm">
            <button type="button" onclick="toggleMode()" id="toggle-btn" class="text-sky-400 font-medium">Create an account</button>
            <a href="forgot-password.php" class="text-slate-500 hover:text-white">Forgot?</a>
        </div>
    </div>

    <script>
        function toggle(id) {
            const x = document.getElementById(id);
            x.type = x.type === "password" ? "text" : "password";
        }

        function toggleMode() {
            const mode = document.getElementById('form-mode');
            const nameField = document.getElementById('name-field');
            const confirmField = document.getElementById('confirm-pass-field');
            const title = document.getElementById('form-title');
            const submitText = document.getElementById('submit-text');
            const toggleBtn = document.getElementById('toggle-btn');

            if (mode.value === 'signin') {
                mode.value = 'signup';
                nameField.classList.remove('hidden');
                confirmField.classList.remove('hidden');
                title.innerText = 'Create account';
                submitText.innerText = 'Create account';
                toggleBtn.innerText = 'I have an account';
            } else {
                mode.value = 'signin';
                nameField.classList.add('hidden');
                confirmField.classList.add('hidden');
                title.innerText = 'Welcome back';
                submitText.innerText = 'Sign in';
                toggleBtn.innerText = 'Create an account';
            }
        }
    </script>
</body>
</html>