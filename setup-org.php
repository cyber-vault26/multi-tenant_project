<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Final Submission
    $org_name = $_POST['org_name'];
    $biz_type = $_POST['business_type'];
    $category = $_POST['category'];
    $currency = $_POST['currency'];
    $timezone = $_POST['timezone'];
    $address = $_POST['address'];
    $biz_email = $_POST['business_email'];
    $biz_phone = $_POST['business_phone'];
    $brand_color = $_POST['brand_color'];
    $modules = isset($_POST['modules']) ? json_encode($_POST['modules']) : '[]';

    try {
        $pdo->beginTransaction();

        // 1. Handle Logo Upload
        $logo_path = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $logo_path = 'assets/uploads/' . time() . '_' . $_FILES['logo']['name'];
            move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path);
        }

        // 2. Insert Tenant
        $stmt = $pdo->prepare("INSERT INTO tenants (name, business_type, category, currency, timezone, address, business_email, phone, brand_color, modules_enabled, logo_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$org_name, $biz_type, $category, $currency, $timezone, $address, $biz_email, $biz_phone, $brand_color, $modules, $logo_path]);
        $tenant_id = $pdo->lastInsertId();

        // 3. Insert Branches
        if (isset($_POST['branch_names'])) {
            foreach ($_POST['branch_names'] as $key => $name) {
                if(!empty($name)) {
                    $b_stmt = $pdo->prepare("INSERT INTO branches (tenant_id, branch_name, branch_code, city, region) VALUES (?, ?, ?, ?, ?)");
                    $b_stmt->execute([$tenant_id, $name, $_POST['branch_codes'][$key], $_POST['branch_cities'][$key], $_POST['branch_regions'][$key]]);
                }
            }
        }

        // 4. Update User
        $pdo->prepare("UPDATE users SET tenant_id = ?, role = 'admin' WHERE id = ?")->execute([$tenant_id, $_SESSION['user_id']]);
        
        // 5. Seed Accounts
        $pdo->prepare("INSERT INTO accounts (tenant_id, account_name, account_type) VALUES (?, 'Cash on Hand', 'Asset'), (?, 'Bank Account', 'Asset')")->execute([$tenant_id, $tenant_id]);

        $_SESSION['tenant_id'] = $tenant_id;
        $_SESSION['role'] = 'admin';

        $pdo->commit();
        header("Location: dashboard.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}

$regions = ["Arusha", "Dar es Salaam", "Dodoma", "Geita", "Iringa", "Kagera", "Katavi", "Kigoma", "Kilimanjaro", "Lindi", "Manyara", "Mara", "Mbeya", "Morogoro", "Mtwara", "Mwanza", "Njombe", "Pemba", "Pwani", "Rukwa", "Ruvuma", "Shinyanga", "Simiyu", "Singida", "Songwe", "Tabora", "Tanga", "Unguja"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Organization — SBI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #020617; color: white; font-family: 'Inter', sans-serif; }
        .glass-card { background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .step-node.active { background-color: #38bdf8; border-color: #38bdf8; color: #020617; }
        .selection-card { border: 2px solid rgba(255,255,255,0.05); transition: all 0.3s; }
        .selection-card.selected { border-color: #38bdf8; background: rgba(56, 189, 248, 0.1); }
        .hidden { display: none; }
    </style>
</head>
<body class="p-4 md:p-10">
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-sky-500 rounded-xl flex items-center justify-center font-bold text-slate-950">SB</div>
                <h1 class="text-xl font-bold">Strong Bridge Investment</h1>
            </div>
            <a href="logout.php" class="text-slate-400 text-sm hover:text-white border border-white/10 px-4 py-2 rounded-lg">Sign out</a>
        </div>

        <div class="mb-8">
            <p class="text-sky-500 font-bold text-[10px] uppercase tracking-[0.2em]">New Workspace</p>
            <h2 class="text-3xl font-bold">Register an organization</h2>
        </div>

        <!-- Wizard Form -->
        <form id="multiStepForm" method="POST" enctype="multipart/form-data" class="glass-card rounded-[2.5rem] p-8 md:p-12">
            
            <!-- PROGRESS BAR -->
            <div class="flex items-center justify-between mb-12 overflow-x-auto gap-4">
                <?php $steps = ["NATURE", "DETAILS", "CURRENCY", "PROFILE", "MODULES", "BRANCHES", "REVIEW"]; 
                foreach($steps as $i => $s): ?>
                    <div class="flex items-center gap-2">
                        <div class="step-node w-6 h-6 rounded-full border border-white/20 flex items-center justify-center text-[10px] font-bold" id="node-<?= $i+1 ?>"><?= $i+1 ?></div>
                        <span class="text-[10px] font-bold text-slate-500"><?= $s ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- STEP 1: NATURE -->
            <div class="step" id="step-1">
                <h3 class="text-xl font-bold mb-6">What type of business is this?</h3>
                <input type="hidden" name="business_type" id="business_type_input" value="Microfinance">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div onclick="selectNature('Microfinance', this)" class="selection-card selected p-6 rounded-2xl cursor-pointer">
                        <p class="font-bold text-lg">Microfinance</p>
                        <p class="text-sm text-slate-400 mt-2">Lending institutions serving individuals and small businesses.</p>
                    </div>
                    <div onclick="selectNature('Service', this)" class="selection-card p-6 rounded-2xl cursor-pointer">
                        <p class="font-bold text-lg">Service Business</p>
                        <p class="text-sm text-slate-400 mt-2">Organizations that sell services rather than physical goods.</p>
                    </div>
                    <div onclick="selectNature('Trading', this)" class="selection-card p-6 rounded-2xl cursor-pointer">
                        <p class="font-bold text-lg">Trading Business</p>
                        <p class="text-sm text-slate-400 mt-2">Businesses that buy and resell goods or manufacture products.</p>
                    </div>
                    <div onclick="selectNature('SACCOs', this)" class="selection-card p-6 rounded-2xl cursor-pointer">
                        <p class="font-bold text-lg">SACCOs</p>
                        <p class="text-sm text-slate-400 mt-2">Savings and Credit Cooperative Societies managing members.</p>
                    </div>
                </div>
            </div>

            <!-- STEP 2: DETAILS -->
            <div class="step hidden" id="step-2">
                <h3 class="text-xl font-bold mb-6">Which category fits best?</h3>
                <input type="hidden" name="category" id="category_input" value="Consulting">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-8">
                    <?php foreach(["Logistics", "Legal services", "E-commerce", "Consulting", "Other"] as $cat): ?>
                        <div onclick="selectCategory('<?= $cat ?>', this)" class="selection-card p-4 rounded-xl cursor-pointer text-sm font-medium <?= $cat=='Consulting'?'selected':'' ?>"><?= $cat ?></div>
                    <?php endforeach; ?>
                </div>
                <label class="text-xs font-bold text-slate-500 uppercase">Organization Name</label>
                <input type="text" name="org_name" id="org_name" required class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl mt-2 outline-none focus:ring-2 focus:ring-sky-500">
            </div>

            <!-- STEP 3: CURRENCY -->
            <div class="step hidden" id="step-3">
                <h3 class="text-xl font-bold mb-6">Accounting Currency</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-xs font-bold text-slate-500">Base Currency</label>
                        <select name="currency" class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl mt-2 outline-none">
                            <option value="TZS">TZS — Tanzanian Shilling</option>
                            <option value="USD">USD — US Dollar</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500">Timezone</label>
                        <select name="timezone" class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl mt-2 outline-none">
                            <option value="Africa/Dar_es_Salaam">Africa/Arusha</option>
                            <option value="Africa/Nairobi">Africa/Nairobi</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- STEP 4: PROFILE -->
            <div class="step hidden" id="step-4">
                <h3 class="text-xl font-bold mb-6">Company Profile</h3>
                <div class="space-y-6">
                    <div class="flex items-center gap-6">
                        <div class="w-24 h-24 rounded-2xl bg-slate-900 border-2 border-dashed border-white/20 flex items-center justify-center overflow-hidden" id="logo-preview">
                            <span class="text-slate-500 text-[10px]">No Logo</span>
                        </div>
                        <input type="file" name="logo" accept="image/*" onchange="previewImage(this)" class="text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <input type="email" name="business_email" placeholder="Business Email" class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl outline-none">
                        <input type="text" name="business_phone" placeholder="Business Phone" class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl outline-none">
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="text-sm">Brand Colour:</label>
                        <input type="color" name="brand_color" value="#38bdf8" class="w-12 h-12 bg-transparent border-none">
                    </div>
                    <textarea name="address" placeholder="Physical Address" class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl outline-none h-24"></textarea>
                </div>
            </div>

            <!-- STEP 5: MODULES -->
            <div class="step hidden" id="step-5">
                <h3 class="text-xl font-bold mb-4">Enable Modules</h3>
                <p class="text-xs text-slate-500 mb-6 uppercase tracking-widest">Select modules to activate in your workspace</p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <?php 
                    $mod_list = ["Dashboard", "Organization", "Microfinance", "SACCOs", "Finance / GL", "CRM", "Inventory", "Sales", "Human resources", "Logistics", "Retail / POS", "E-commerce"];
                    foreach($mod_list as $m): ?>
                        <label class="flex items-center gap-3 p-4 glass-card rounded-xl cursor-pointer hover:bg-white/5 transition-all">
                            <input type="checkbox" name="modules[]" value="<?= $m ?>" checked class="w-5 h-5 accent-sky-500">
                            <span class="text-sm"><?= $m ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- STEP 6: BRANCHES -->
            <div class="step hidden" id="step-6">
                <h3 class="text-xl font-bold mb-6">Add Branches</h3>
                <div id="branch-container" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-white/5 p-4 rounded-xl">
                        <input type="text" name="branch_names[]" placeholder="Branch Name" class="bg-slate-900 border border-white/10 p-3 rounded-lg text-sm outline-none">
                        <input type="text" name="branch_codes[]" placeholder="Code (e.g ARS)" class="bg-slate-900 border border-white/10 p-3 rounded-lg text-sm outline-none">
                        <input type="text" name="branch_cities[]" placeholder="City" class="bg-slate-900 border border-white/10 p-3 rounded-lg text-sm outline-none">
                        <select name="branch_regions[]" class="bg-slate-900 border border-white/10 p-3 rounded-lg text-sm outline-none">
                            <?php foreach($regions as $r) echo "<option value='$r'>$r</option>"; ?>
                        </select>
                    </div>
                </div>
                <button type="button" onclick="addBranchRow()" class="mt-4 text-sky-400 text-sm font-bold">+ Add Another Branch</button>
            </div>

            <!-- STEP 7: REVIEW -->
            <div class="step hidden" id="step-7">
                <h3 class="text-xl font-bold mb-6 text-sky-400">Review your details</h3>
                <div id="review-content" class="space-y-4 text-sm text-slate-300">
                    <!-- Dynamic Content -->
                </div>
            </div>

            <!-- BUTTONS -->
            <div class="mt-12 flex justify-between pt-8 border-t border-white/5">
                <button type="button" id="prevBtn" onclick="changeStep(-1)" class="text-slate-400 font-bold px-8 py-4 rounded-xl border border-white/10 hidden">Back</button>
                <div class="flex-1 text-right">
                    <button type="button" id="nextBtn" onclick="changeStep(1)" class="bg-sky-500 text-slate-950 font-extrabold px-10 py-4 rounded-xl shadow-lg hover:scale-105 transition-all">Continue</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 7;

        function selectNature(val, el) {
            document.getElementById('business_type_input').value = val;
            document.querySelectorAll('#step-1 .selection-card').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
        }

        function selectCategory(val, el) {
            document.getElementById('category_input').value = val;
            document.querySelectorAll('#step-2 .selection-card').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
        }

        function previewImage(input) {
            if (input.files && input.files[0]) {
                let reader = new FileReader();
                reader.onload = e => {
                    document.getElementById('logo-preview').innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function addBranchRow() {
            const container = document.getElementById('branch-container');
            const row = container.children[0].cloneNode(true);
            row.querySelectorAll('input').forEach(i => i.value = "");
            container.appendChild(row);
        }

        function changeStep(n) {
            if (n === 1 && !validateStep()) return;

            document.getElementById(`step-${currentStep}`).classList.add('hidden');
            document.getElementById(`node-${currentStep}`).classList.remove('active');

            currentStep += n;

            if (currentStep > totalSteps) {
                document.getElementById('multiStepForm').submit();
                return;
            }

            if (currentStep === 7) populateReview();

            document.getElementById(`step-${currentStep}`).classList.remove('hidden');
            document.getElementById(`node-${currentStep}`).classList.add('active');

            document.getElementById('prevBtn').classList.toggle('hidden', currentStep === 1);
            document.getElementById('nextBtn').innerText = currentStep === 7 ? 'Submit for Approval' : 'Continue';
            document.getElementById('nextBtn').classList.toggle('bg-sky-500', currentStep < 7);
            document.getElementById('nextBtn').classList.toggle('bg-emerald-500', currentStep === 7);
        }

        function validateStep() {
            if (currentStep === 2 && document.getElementById('org_name').value === "") {
                alert("Please enter Organization Name"); return false;
            }
            return true;
        }

        function populateReview() {
            const name = document.getElementById('org_name').value;
            const type = document.getElementById('business_type_input').value;
            const cat = document.getElementById('category_input').value;
            const email = document.getElementsByName('business_email')[0].value;
            
            let html = `
                <div class="grid grid-cols-2 gap-8">
                    <div><p class="text-slate-500 uppercase text-[10px] font-bold">Organization</p><p class="text-white text-lg font-bold">${name}</p></div>
                    <div><p class="text-slate-500 uppercase text-[10px] font-bold">Business Nature</p><p class="text-white">${type} (${cat})</p></div>
                    <div><p class="text-slate-500 uppercase text-[10px] font-bold">Business Email</p><p class="text-white">${email}</p></div>
                    <div><p class="text-slate-500 uppercase text-[10px] font-bold">Status</p><p class="text-amber-400 font-bold italic">Review Pending</p></div>
                </div>
                <div class="mt-6 p-4 bg-white/5 rounded-xl border border-white/10">
                    <p class="text-xs text-slate-400">Everything looks correct? Click submit to send your application for review.</p>
                </div>
            `;
            document.getElementById('review-content').innerHTML = html;
        }
        
 
        document.getElementById('node-1').classList.add('active');
    </script>
</body>
</html>
