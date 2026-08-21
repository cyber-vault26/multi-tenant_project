<?php
date_default_timezone_set('Africa/Nairobi');

// 1. Railway (or any host that injects real environment variables)
$host     = getenv('MYSQLHOST');
$db_name  = getenv('MYSQLDATABASE');
$username = getenv('MYSQLUSER');
$password = getenv('MYSQLPASSWORD');
$port     = getenv('MYSQLPORT') ?: '3306';
$charset  = 'utf8mb4';

// 2. cPanel or any host without injected env vars: config.local.php.
// This file is gitignored — it exists only on the actual server and
// is never committed, so real credentials never touch GitHub.
if (!$host && file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
    if (defined('DB_HOST')) {
        $host     = DB_HOST;
        $db_name  = DB_NAME;
        $username = DB_USER;
        $password = DB_PASS;
        $port     = defined('DB_PORT') ? DB_PORT : '3306';
    }
}

// 3. Local development fallback (e.g. testing on your own machine)
if (!$host) {
    $host     = 'localhost';
    $db_name  = 'multi_tenant_system';
    $username = 'admin_erp';
    $password = 'kali';
    $port     = '3306';
}

$dsn = "mysql:host=$host;dbname=$db_name;port=$port;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
    PDO::ATTR_EMULATE_PREPARES   => false,               
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // HAPA NDIO MUHIMU: Itakuambia kosa ni nini (mfano: Access Denied, au Unknown Host)
    echo "<h1>Database Connection Failed</h1>";
    echo "<p>Error Message: " . $e->getMessage() . "</p>";
    echo "<hr>";
    echo "<strong>Check these variables in Railway:</strong><br>";
    echo "Host: $host <br>";
    echo "Database: $db_name <br>";
    echo "User: $username <br>";
    echo "Port: $port <br>";
    exit();
}

// Global Constants
if (isset($_SESSION['tenant_id'])) {
    $stmt = $pdo->prepare("SELECT currency, name FROM tenants WHERE id = ?");
    $stmt->execute([$_SESSION['tenant_id']]);
    $global_tenant = $stmt->fetch();
    define('CURRENCY', $global_tenant['currency'] ?? 'TZS');
    define('BIZ_NAME', $global_tenant['name'] ?? 'Strong Bridge');
} else {
    define('CURRENCY', 'TZS');
    define('BIZ_NAME', 'Strong Bridge');
}