<?php
// DriveRent - MySQL Database Configuration (XAMPP Server)

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'driverent');
define('DB_PORT', '3306');

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Auto-seed tables from driverent.sql if database exists but tables are missing
    $check_table = $pdo->query("SHOW TABLES LIKE 'cars'")->fetch();
    if (!$check_table && file_exists(__DIR__ . '/../database/driverent.sql')) {
        $sql = file_get_contents(__DIR__ . '/../database/driverent.sql');
        $pdo->exec($sql);
    }
} catch (PDOException $e) {
    // If database does not exist, attempt to auto-create and seed
    try {
        $init_pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => true
        ]);
        $init_pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        // Auto-seed tables from driverent.sql if empty
        if (file_exists(__DIR__ . '/../database/driverent.sql')) {
            $sql = file_get_contents(__DIR__ . '/../database/driverent.sql');
            $pdo->exec($sql);
        }
    } catch (PDOException $err) {
        $db_error = $err->getMessage();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database Connection Error - DriveRent</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { background: #0B0F19; color: #FFFFFF; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
                .setup-card { background: #1E293B; border: 1px solid rgba(14, 165, 233, 0.3); border-radius: 16px; padding: 40px; max-width: 600px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
                .accent-text { color: #0EA5E9; }
                .code-block { background: #111827; border: 1px solid #334155; border-radius: 8px; padding: 15px; color: #CBD5E1; font-family: monospace; font-size: 0.9rem; }
            </style>
        </head>
        <body>
            <div class="setup-card text-center">
                <h2 class="fw-bold mb-3"><span class="accent-text">DriveRent</span> MySQL Connection Required</h2>
                <p class="text-secondary mb-4">Could not connect to MySQL server in XAMPP.</p>
                <div class="code-block text-start mb-4">
                    <strong>Error:</strong> <?php echo htmlspecialchars($db_error); ?><br><br>
                    <strong>Steps to Resolve:</strong><br>
                    1. Open <strong>XAMPP Control Panel</strong>.<br>
                    2. Click <strong>Start</strong> next to <strong>MySQL</strong> and <strong>Apache</strong>.<br>
                    3. Ensure MySQL is running on port 3306.
                </div>
                <a href="" class="btn btn-primary px-4 py-2" onclick="location.reload(); return false;">Retry Connection</a>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}
?>
