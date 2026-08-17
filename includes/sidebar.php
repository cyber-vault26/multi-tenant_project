<aside class="fixed inset-y-0 left-0 w-64 glass-card border-r border-white/10 hidden lg:flex flex-col z-50 overflow-hidden">
    
    <!-- 1. LOGO & BRANDING -->
    <div class="p-6 flex items-center gap-3">
        <div class="w-10 h-10 bg-sky-500/20 border border-sky-500/40 rounded-xl flex items-center justify-center text-sky-400 font-bold shadow-lg shadow-sky-500/10">
            SB
        </div>
        <div class="flex flex-col">
            <span class="font-bold text-white tracking-tight text-sm"><?php echo defined('BIZ_NAME') ? BIZ_NAME : 'Strong Bridge'; ?></span>
            <span class="text-[9px] text-slate-500 uppercase tracking-widest font-semibold">Workspace</span>
        </div>
    </div>

    <!-- 2. NAVIGATION LINKS -->
    <nav class="flex-1 px-4 space-y-1.5 mt-2 overflow-y-auto custom-scrollbar">
        
        <!-- MAIN -->
        <a href="dashboard.php" class="flex items-center gap-3 p-3 text-sm text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all font-medium group">
            <svg class="w-5 h-5 text-slate-500 group-hover:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            Dashboard
        </a>

        <!-- CORE MODULES (Microfinance) -->
        <div class="pt-4 pb-2 px-3 text-[10px] font-bold text-slate-600 uppercase tracking-[0.2em]">Core Operations</div>
        
        <a href="clients.php" class="flex items-center gap-3 p-3 text-sm text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all group">
             <svg class="w-5 h-5 text-slate-500 group-hover:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
             Manage Clients
        </a>

        <a href="loans.php" class="flex items-center gap-3 p-3 text-sm text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all group">
            <svg class="w-5 h-5 text-slate-500 group-hover:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Loan Portfolio
        </a>

        <a href="collect-payment.php" class="flex items-center gap-3 p-3 text-sm text-slate-400 hover:text-emerald-400 hover:bg-emerald-500/10 rounded-xl transition-all group border border-transparent hover:border-emerald-500/20">
            <svg class="w-5 h-5 text-slate-500 group-hover:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3-3m0 0l3 3m-3-3v8m0-13a9 9 0 110 18 9 9 0 010-18z"></path></svg>
            Collect Payment
        </a>

        <!-- RETAIL & COMMERCE -->
        <div class="pt-4 pb-2 px-3 text-[10px] font-bold text-slate-600 uppercase tracking-[0.2em]">Retail & Commerce</div>

        <a href="inventory.php" class="flex items-center gap-3 p-3 text-sm text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all group">
            <svg class="w-5 h-5 text-slate-500 group-hover:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            Inventory Stock
        </a>

        <a href="pos.php" class="flex items-center gap-3 p-3 text-sm text-slate-400 hover:text-white hover:bg-sky-500/10 rounded-xl transition-all group">
            <svg class="w-5 h-5 text-slate-500 group-hover:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            Point of Sale (POS)
        </a>

        <!-- FINANCIALS (Admin & Manager) -->
        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager' || $_SESSION['role'] === 'super_admin'): ?>
            <div class="pt-4 pb-2 px-3 text-[10px] font-bold text-slate-600 uppercase tracking-[0.2em]">Financial Control</div>
            
            <a href="accounting.php" class="flex items-center gap-3 p-3 text-sm text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all group">
                <svg class="w-5 h-5 text-slate-500 group-hover:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 2v-6m0 10v4a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2m3 0h9a2 2 0 012 2v9a2 2 0 01-2 2h-3m-6 0H9"></path></svg>
                General Ledger
            </a>

            <a href="expenses.php" class="flex items-center gap-3 p-3 text-sm text-slate-400 hover:text-red-400 hover:bg-red-500/10 rounded-xl transition-all group">
                <svg class="w-5 h-5 text-slate-500 group-hover:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Manage Expenses
            </a>

            <a href="profit-loss.php" class="flex items-center gap-3 p-3 text-sm text-slate-400 hover:text-emerald-400 hover:bg-emerald-500/10 rounded-xl transition-all group">
                <svg class="w-5 h-5 text-slate-500 group-hover:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Profit & Loss
            </a>

            <a href="sales-report.php" class="flex items-center gap-3 p-3 text-sm text-slate-400 hover:text-sky-400 hover:bg-sky-500/10 rounded-xl transition-all group">
                <svg class="w-5 h-5 text-slate-500 group-hover:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                Sales Analytics
            </a>
        <?php endif; ?>

        <!-- ADMINISTRATION (Admin Only) -->
        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin'): ?>
            <div class="pt-4 pb-2 px-3 text-[10px] font-bold text-slate-600 uppercase tracking-[0.2em]">Administration</div>
            
            <a href="staff.php" class="flex items-center gap-3 p-3 text-sm text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all group">
                <svg class="w-5 h-5 text-slate-500 group-hover:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Staff Members
            </a>

            <a href="audit-logs.php" class="flex items-center gap-3 p-3 text-sm text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all group">
                <svg class="w-5 h-5 text-slate-500 group-hover:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                Audit Trail
            </a>

            <a href="settings.php" class="flex items-center gap-3 p-3 text-sm text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all group">
                <svg class="w-5 h-5 text-slate-500 group-hover:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Workspace Settings
            </a>
        <?php endif; ?>

        <!-- SYSTEM ADMIN (Super Admin Only) -->
        <?php if ($_SESSION['role'] === 'super_admin'): ?>
            <div class="pt-8 pb-2 px-3 text-[10px] font-black text-indigo-400 uppercase tracking-[0.3em]">System Admin</div>
            <a href="platform.php" class="flex items-center gap-3 p-3 text-sm text-indigo-300 bg-indigo-500/10 border border-indigo-500/20 rounded-xl transition-all group">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                Platform Control
            </a>
        <?php endif; ?>

    </nav>

    <!-- 3. USER PROFILE SECTION -->
    <div class="p-4 mt-auto border-t border-white/5 bg-black/20">
        <div class="flex items-center gap-3 p-2">
            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-sky-500 to-indigo-500 flex items-center justify-center text-[10px] font-bold text-white shadow-lg shadow-sky-500/20">
                <?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?>
            </div>
            <div class="flex flex-col min-w-0">
                <span class="text-xs font-bold text-white truncate"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <span class="text-[9px] text-slate-500 uppercase tracking-tighter"><?php echo $_SESSION['role']; ?></span>
            </div>
        </div>
        <a href="logout.php" class="mt-2 flex items-center justify-center gap-2 p-2 w-full text-[10px] font-bold text-red-400 hover:bg-red-500/10 rounded-lg transition-all uppercase tracking-widest group">
            <svg class="w-3 h-3 text-red-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Sign Out
        </a>
    </div>
</aside>

