<?php
session_start();
$authed = isset($_SESSION['user_id']);

// Data Arrays (Mapped from React code)
$marqueeItems = ["Microfinance", "SACCOS", "Retail & POS", "Wholesale", "Manufacturing", "Logistics", "HR & Payroll", "Inventory", "Procurement", "Finance & GL", "E-commerce", "Biometric attendance"];

$stats = [
    ['value' => 48, 'suffix' => '+', 'label' => 'Business modules & features'],
    ['value' => 100, 'suffix' => '%', 'label' => 'Database-enforced data isolation'],
    ['value' => 15, 'suffix' => '+', 'label' => 'Industries supported'],
    ['value' => 24, 'suffix' => '/7', 'label' => 'Biometric attendance sync'],
];

$features = [
    ['title' => 'Biometric attendance', 'body' => 'Connect fingerprint devices and pull punches automatically.'],
    ['title' => 'Search the web', 'body' => 'Built-in media search lets you find and import images straight from the internet.'],
    ['title' => 'E-commerce storefront', 'body' => 'Publish products, manage inventory, and process orders with your own branded store.'],
    ['title' => 'Real financial statements', 'body' => 'Profit & loss and balance sheet generated from posted journals.'],
    ['title' => 'Modular by subscription', 'body' => 'Switch modules on or off per organization anytime.'],
    ['title' => 'Strict data isolation', 'body' => 'Isolation is enforced in the database itself for maximum security.'],
];

$modules = ["Microfinance", "SACCOS", "Retail / POS", "Logistics", "Manufacturing", "Human resources", "Inventory", "E-commerce", "Biometrics", "Attendance"];
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strong Bridge Investment — Multi-tenant ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #020617; color: white; }
        .glass { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); }
        
        /* Marquee Animation */
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .marquee-track { display: flex; width: max-content; animation: marquee 30s linear infinite; }
        
        .text-gradient {
            background: linear-gradient(to right, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="antialiased">

    <!-- HEADER (Mapped from Header Component) -->
    <header class="fixed inset-x-0 top-0 z-50 glass border-b border-white/5">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-sky-500/20 border border-sky-500/40 rounded-lg flex items-center justify-center font-bold text-sky-400">SB</div>
                <span class="font-bold text-lg tracking-tight">Strong Bridge</span>
            </div>
            
            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-400">
                <a href="#features" class="hover:text-white transition-colors">Features</a>
                <a href="#modules" class="hover:text-white transition-colors">Modules</a>
                <a href="#how-it-works" class="hover:text-white transition-colors">How it works</a>
            </nav>

            <div class="flex items-center gap-3">
                <?php if($authed): ?>
                    <a href="dashboard.php" class="rounded-lg bg-sky-500 px-4 py-2 text-sm font-bold text-slate-950 shadow-lg hover:scale-105 transition-all">Open workspace</a>
                <?php else: ?>
                    <a href="login.php" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-300 hover:text-white">Sign in</a>
                    <a href="login.php?mode=signup" class="rounded-lg bg-sky-500 px-4 py-2 text-sm font-bold text-slate-950 shadow-lg hover:scale-105 transition-all">Create organization</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- HERO SECTION (Mapped from Hero) -->
    <section class="relative pt-40 pb-20 px-6 overflow-hidden">
        <div class="absolute top-20 -left-40 size-[36rem] rounded-full bg-sky-500/10 blur-[120px] -z-10"></div>
        
        <div class="mx-auto max-w-6xl grid lg:grid-cols-2 gap-12 items-center">
            <div class="space-y-8">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                    <span class="text-sky-400">✦</span> Multi-tenant business platform
                </div>
                
                <h1 class="text-6xl md:text-7xl font-extrabold leading-[0.95] tracking-tighter">
                    One <span class="text-sky-500">system.</span><br>
                    Every <span class="text-gradient">business</span> gets its own universe.
                </h1>
                
                <p class="text-lg text-slate-400 max-w-xl leading-relaxed">
                    Strong Bridge Investment gives every organization its own private workspace with biometric attendance, e-commerce, and 15+ business modules — all sharing one secure, isolated core.
                </p>

                <div class="flex flex-wrap gap-4">
                    <a href="login.php?mode=signup" class="bg-sky-500 hover:bg-sky-400 text-slate-950 px-8 py-4 rounded-xl font-bold text-sm shadow-xl transition-all hover:scale-105">Start a 30-day trial</a>
                    <a href="login.php" class="bg-white/5 border border-white/10 hover:bg-white/10 px-8 py-4 rounded-xl font-bold text-sm transition-all">I already have an account</a>
                </div>
            </div>

            <!-- Floating Widgets Mockup -->
            <div class="relative hidden lg:block">
                <div class="rounded-3xl border border-white/10 bg-slate-900/50 p-2 shadow-2xl">
                    <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?q=80&w=1000" class="rounded-2xl opacity-50 grayscale" alt="ERP Dashboard">
                </div>
                <!-- Floating chips -->
                <div class="absolute -left-10 top-10 glass p-4 rounded-2xl shadow-2xl animate-bounce" style="animation-duration: 4s;">
                    <p class="text-[10px] font-bold text-slate-500 uppercase">Fingerprint</p>
                    <p class="text-xs font-semibold">Biometric verified · 09:02 AM</p>
                </div>
            </div>
        </div>
    </section>

    <!-- MARQUEE (Mapped from MarqueeItems) -->
    <section class="border-y border-white/5 bg-white/[0.02] py-6 overflow-hidden">
        <div class="marquee-track">
            <?php for($i=0; $i<2; $i++): // Repeat twice for seamless loop ?>
                <?php foreach($marqueeItems as $item): ?>
                    <span class="flex items-center gap-10 mx-10 font-mono text-sm uppercase tracking-widest text-slate-500 whitespace-nowrap">
                        <?php echo $item; ?> <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                    </span>
                <?php endforeach; ?>
            <?php endfor; ?>
        </div>
    </section>

    <!-- STATS (Mapped from Stats) -->
    <section class="mx-auto max-w-6xl px-6 py-24 grid grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach($stats as $s): ?>
            <div class="glass p-8 rounded-[2rem] hover:border-sky-500/30 transition-colors">
                <p class="text-4xl font-bold tracking-tighter"><?php echo $s['value'] . $s['suffix']; ?></p>
                <p class="mt-2 text-sm text-slate-500 leading-tight"><?php echo $s['label']; ?></p>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- FEATURES (Mapped from Features) -->
    <section id="features" class="mx-auto max-w-6xl px-6 py-16">
        <p class="font-mono text-xs uppercase tracking-widest text-sky-500">Features</p>
        <h2 class="mt-4 text-4xl md:text-5xl font-bold tracking-tight">Everything a growing business needs.</h2>
        
        <div class="grid md:grid-cols-3 gap-6 mt-16">
            <?php foreach($features as $f): ?>
                <div class="glass p-8 rounded-3xl group hover:bg-white/5 transition-all">
                    <div class="w-12 h-12 bg-sky-500/10 rounded-xl mb-6 flex items-center justify-center text-sky-400 font-bold">✓</div>
                    <h3 class="text-xl font-bold mb-3"><?php echo $f['title']; ?></h3>
                    <p class="text-sm text-slate-400 leading-relaxed"><?php echo $f['body']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- MODULES (Mapped from Modules) -->
    <section id="modules" class="bg-white/[0.02] border-y border-white/5 py-24">
        <div class="mx-auto max-w-6xl px-6">
            <p class="font-mono text-xs uppercase tracking-widest text-slate-500">Modules</p>
            <h2 class="text-3xl font-bold mt-4 mb-10">One platform, unlimited ambition.</h2>
            <div class="flex flex-wrap gap-3">
                <?php foreach($modules as $m): ?>
                    <div class="glass px-6 py-3 rounded-full text-sm font-medium hover:border-sky-500 transition-colors">
                        <?php echo $m; ?>
                    </div>
                <?php endforeach; ?>
                <div class="bg-gradient-to-r from-sky-500 to-indigo-600 px-6 py-3 rounded-full text-sm font-bold text-slate-950 italic">
                    and more, always shipping...
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="mx-auto max-w-6xl px-6 py-12 border-t border-white/5 flex flex-col md:flex-row justify-between items-center text-sm text-slate-500">
        <span>© <?php echo date('Y'); ?> Strong Bridge Investment — Multi-tenant ERP</span>
        <div class="flex gap-6 mt-4 md:mt-0">
            <a href="login.php" class="hover:text-white">Sign in</a>
            <a href="login.php?mode=signup" class="hover:text-white">Create organization</a>
        </div>
    </footer>

</body>
</html>
