<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dbHost = trim((string) ($_POST['db_host'] ?? DB_HOST));
        $dbPort = trim((string) ($_POST['db_port'] ?? DB_PORT));
        $dbName = trim((string) ($_POST['db_name'] ?? DB_NAME));
        $dbUser = trim((string) ($_POST['db_user'] ?? DB_USER));
        $dbPass = (string) ($_POST['db_pass'] ?? DB_PASS);

        $localConfig = "<?php\nreturn " . var_export([
            'host' => $dbHost,
            'port' => $dbPort,
            'name' => $dbName,
            'user' => $dbUser,
            'pass' => $dbPass,
        ], true) . ";\n";
        file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'config.local.php', $localConfig);

        $schemaPath = __DIR__ . DIRECTORY_SEPARATOR . 'petrostation_schema.sql';
        if (!is_file($schemaPath)) {
            $schemaPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'petrostation_schema.sql';
        }
        $schema = file_get_contents($schemaPath);
        if ($schema === false) {
            throw new RuntimeException('Cannot read petrostation_schema.sql');
        }

        $server = new PDO('mysql:host=' . $dbHost . ';port=' . $dbPort . ';charset=utf8mb4', $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $server->exec($schema);

        $pdo = new PDO('mysql:host=' . $dbHost . ';port=' . $dbPort . ';dbname=' . $dbName . ';charset=utf8mb4', $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $roles = $pdo->query('SELECT RoleID, RoleName FROM user_role')->fetchAll();
        $roleMap = [];
        foreach ($roles as $role) {
            $roleMap[$role['RoleName']] = (int) $role['RoleID'];
        }

        $users = [
            ['Station Manager', 'manager', 'Manager User'],
            ['Pump Attendant', 'attendant', 'Pump Attendant'],
            ['Cashier / Accounts Clerk', 'cashier', 'Accounts Clerk'],
            ['Procurement Officer', 'procurement', 'Procurement Officer'],
            ['Owner / Director', 'owner', 'Owner Director'],
        ];
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO user_account (RoleID, Username, PasswordHash, FullName, Email) VALUES (?, ?, ?, ?, ?)');
        foreach ($users as [$role, $username, $name]) {
            $stmt->execute([$roleMap[$role], $username, $password, $name, $username . '@petrostation.local']);
        }

        $pdo->exec("
            INSERT INTO fuel_type (FuelName, UnitPrice, MeasurementUnit) VALUES
            ('Petrol', 5400, 'Litres'),
            ('Diesel', 5200, 'Litres'),
            ('Kerosene', 4800, 'Litres');

            INSERT INTO tank (FuelTypeID, TankLabel, CapacityLitres, CurrentLevel, ReorderThreshold, LastDipDate) VALUES
            (1, 'Tank A - Petrol', 30000, 18500, 6000, CURDATE()),
            (2, 'Tank B - Diesel', 30000, 14200, 6000, CURDATE()),
            (3, 'Tank C - Kerosene', 15000, 4300, 3500, CURDATE());

            INSERT INTO pump (FuelTypeID, PumpNumber, Status, LastCalibrationDate, NextCalibrationDue) VALUES
            (1, 'PUMP-01', 'Active', DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 50 DAY)),
            (2, 'PUMP-02', 'Active', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 60 DAY)),
            (3, 'PUMP-03', 'Maintenance', DATE_SUB(CURDATE(), INTERVAL 80 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY));
        ");

        $managerUserId = (int) $pdo->query("SELECT UserID FROM user_account WHERE Username = 'manager'")->fetch()['UserID'];
        $attendantUserId = (int) $pdo->query("SELECT UserID FROM user_account WHERE Username = 'attendant'")->fetch()['UserID'];
        $procUserId = (int) $pdo->query("SELECT UserID FROM user_account WHERE Username = 'procurement'")->fetch()['UserID'];

        $emp = $pdo->prepare('INSERT INTO employee (UserID, FirstName, LastName, Role, PhoneNumber, NationalID, BaseSalary, HireDate, Status) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)');
        $emp->execute([$managerUserId, 'Manager', 'User', 'Station Manager', '0700000001', 'NIN-MANAGER', 1200000, 'Active']);
        $emp->execute([$attendantUserId, 'Pump', 'Attendant', 'Pump Attendant', '0700000002', 'NIN-ATTENDANT', 450000, 'Active']);
        $emp->execute([$procUserId, 'Procurement', 'Officer', 'Procurement Officer', '0700000003', 'NIN-PROCUREMENT', 700000, 'Active']);

        $pdo->exec("
            INSERT INTO shift (ShiftDate, StartTime, EndTime, ShiftName, Status)
            VALUES (CURDATE(), '06:00:00', '14:00:00', 'Morning Shift', 'Open');

            INSERT INTO customer (CustomerName, CustomerType, PhoneNumber, Email, Address, LoyaltyPoints, RegisteredDate) VALUES
            ('Walk-in Customer', 'Walk-in', NULL, NULL, NULL, 0, CURDATE()),
            ('Ruro Logistics Ltd', 'Fleet', '0700001000', 'accounts@rurologistics.local', 'Ruti, Mbarara-Kabale Road', 120, CURDATE());
            INSERT INTO fleet_account (CustomerID, CreditLimit, OutstandingBalance, LastStatementDate, AccountStatus)
            VALUES (2, 5000000, 850000, CURDATE(), 'Active');
            INSERT INTO supplier (SupplierName, ContactPerson, PhoneNumber, Email, Address, PerformanceRating, Status)
            VALUES ('Uganda Fuel Supplies Ltd', 'Sarah Kato', '0700002000', 'supply@example.local', 'Kampala', 4.50, 'Active');
        ");

        $message = 'Database installed successfully. Login with manager / admin123.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup | PetroStation DBMS</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="setup-page">
    <section class="setup-card">
        <div class="brand large"><span>PS</span><div><strong>PetroStation DBMS</strong><small>XAMPP setup</small></div></div>
        <h1>Install the database and demo users</h1>
        <p>This runs the project schema, creates sample roles, users, fuel, tanks, pumps, employees, customers, fleet account, and supplier records.</p>
        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <label>MySQL host<input name="db_host" value="<?= htmlspecialchars(DB_HOST) ?>" required></label>
            <label>MySQL port<input name="db_port" value="<?= htmlspecialchars(DB_PORT) ?>" required></label>
            <label>Database name<input name="db_name" value="<?= htmlspecialchars(DB_NAME) ?>" required></label>
            <label>MySQL user<input name="db_user" value="<?= htmlspecialchars(DB_USER) ?>" required></label>
            <label>MySQL password<input name="db_pass" type="password" value="<?= htmlspecialchars(DB_PASS) ?>"></label>
            <button type="submit">Install / Reset Database</button>
            <a class="button secondary" href="index.php?page=login">Go to Login</a>
        </form>
        <p class="hint">Default password for demo users: <strong>admin123</strong></p>
    </section>
</body>
</html>
