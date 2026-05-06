<?php
declare(strict_types=1);

require_once __DIR__ . '/lib.php';

$page = $_GET['page'] ?? 'dashboard';
$action = $_POST['action'] ?? '';

if ($page === 'logout') {
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}

try {
    handle_post($page, (string) $action);
} catch (Throwable $e) {
    flash($e->getMessage(), 'error');
    header('Location: index.php?page=' . urlencode($page));
    exit;
}

match ($page) {
    'login' => page_login(),
    'dashboard' => page_dashboard(),
    'sales' => page_sales(),
    'inventory' => page_inventory(),
    'customers' => page_customers(),
    'procurement' => page_procurement(),
    'employees' => page_hr(),
    'maintenance' => page_maintenance(),
    'reports' => page_reports(),
    'risks' => page_risks(),
    default => redirect('dashboard'),
};

function handle_post(string $page, string $action): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $page === 'login') {
        return;
    }

    require_module($page);
    $pdo = db();

    if ($action === 'add_fuel_type') {
        $pdo->prepare('INSERT INTO fuel_type (FuelName, UnitPrice, MeasurementUnit) VALUES (?, ?, ?)')
            ->execute([post_value('FuelName'), post_value('UnitPrice'), post_value('MeasurementUnit', 'Litres')]);
        done('Fuel type added.', $page);
    }

    if ($action === 'update_fuel_price') {
        $pdo->prepare('UPDATE fuel_type SET UnitPrice = ?, MeasurementUnit = ? WHERE FuelTypeID = ?')
            ->execute([(float) post_value('UnitPrice'), post_value('MeasurementUnit', 'Litres'), (int) post_value('FuelTypeID')]);
        done('Product price updated.', $page);
    }

    if ($action === 'add_tank') {
        $pdo->prepare('INSERT INTO tank (FuelTypeID, TankLabel, CapacityLitres, CurrentLevel, ReorderThreshold, LastDipDate) VALUES (?, ?, ?, ?, ?, CURDATE())')
            ->execute([(int) post_value('FuelTypeID'), post_value('TankLabel'), (float) post_value('CapacityLitres'), (float) post_value('CurrentLevel'), (float) post_value('ReorderThreshold')]);
        done('Tank added.', $page);
    }

    if ($action === 'update_tank') {
        $pdo->prepare('UPDATE tank SET CurrentLevel = ?, LastDipDate = CURDATE() WHERE TankID = ?')
            ->execute([(float) post_value('CurrentLevel'), (int) post_value('TankID')]);
        done('Tank dip reading updated.', $page);
    }

    if ($action === 'add_pump') {
        $pdo->prepare('INSERT INTO pump (FuelTypeID, PumpNumber, Status, LastCalibrationDate, NextCalibrationDue) VALUES (?, ?, ?, ?, ?)')
            ->execute([(int) post_value('FuelTypeID'), post_value('PumpNumber'), post_value('Status'), null_date('LastCalibrationDate'), null_date('NextCalibrationDue')]);
        done('Pump added.', $page);
    }

    if ($action === 'record_sale') {
        record_sale();
        done('Sale recorded and tank stock reduced.', $page);
    }

    if ($action === 'add_customer') {
        $pdo->prepare('INSERT INTO customer (CustomerName, CustomerType, PhoneNumber, Email, Address, LoyaltyPoints, RegisteredDate) VALUES (?, ?, ?, ?, ?, ?, CURDATE())')
            ->execute([post_value('CustomerName'), post_value('CustomerType'), post_value('PhoneNumber'), post_value('Email'), post_value('Address'), (float) post_value('LoyaltyPoints', 0)]);
        done('Customer added.', $page);
    }

    if ($action === 'add_fleet_account') {
        $pdo->prepare('INSERT INTO fleet_account (CustomerID, CreditLimit, OutstandingBalance, LastStatementDate, AccountStatus) VALUES (?, ?, ?, CURDATE(), ?)')
            ->execute([(int) post_value('CustomerID'), (float) post_value('CreditLimit'), (float) post_value('OutstandingBalance'), post_value('AccountStatus')]);
        done('Fleet account added.', $page);
    }

    if ($action === 'add_supplier') {
        $pdo->prepare('INSERT INTO supplier (SupplierName, ContactPerson, PhoneNumber, Email, Address, PerformanceRating, Status) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([post_value('SupplierName'), post_value('ContactPerson'), post_value('PhoneNumber'), post_value('Email'), post_value('Address'), (float) post_value('PerformanceRating'), post_value('Status')]);
        done('Supplier added.', $page);
    }

    if ($action === 'add_po') {
        $pdo->prepare('INSERT INTO purchase_order (SupplierID, EmployeeID, FuelTypeID, OrderDate, ExpectedDelivery, QuantityOrdered, AgreedUnitPrice, Status, Notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([(int) post_value('SupplierID'), (int) post_value('EmployeeID'), (int) post_value('FuelTypeID'), post_value('OrderDate'), null_date('ExpectedDelivery'), (float) post_value('QuantityOrdered'), (float) post_value('AgreedUnitPrice'), post_value('Status'), post_value('Notes')]);
        done('Purchase order added.', $page);
    }

    if ($action === 'record_delivery') {
        record_delivery();
        done('Delivery recorded and tank stock increased.', $page);
    }

    if ($action === 'add_invoice') {
        $pdo->prepare('INSERT INTO invoice (DeliveryID, SupplierID, InvoiceDate, DueDate, Amount, PaymentStatus, PaymentDate) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([(int) post_value('DeliveryID'), (int) post_value('SupplierID'), post_value('InvoiceDate'), post_value('DueDate'), (float) post_value('Amount'), post_value('PaymentStatus'), null_date('PaymentDate')]);
        done('Invoice added.', $page);
    }

    if ($action === 'add_employee') {
        $pdo->prepare('INSERT INTO employee (FirstName, LastName, Role, PhoneNumber, NationalID, BaseSalary, HireDate, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([post_value('FirstName'), post_value('LastName'), post_value('Role'), post_value('PhoneNumber'), post_value('NationalID'), (float) post_value('BaseSalary'), post_value('HireDate'), post_value('Status')]);
        done('Employee added.', $page);
    }

    if ($action === 'add_shift') {
        $pdo->prepare('INSERT INTO shift (ShiftDate, StartTime, EndTime, ShiftName, Status) VALUES (?, ?, ?, ?, ?)')
            ->execute([post_value('ShiftDate'), post_value('StartTime'), post_value('EndTime'), post_value('ShiftName'), post_value('Status')]);
        done('Shift added.', $page);
    }

    if ($action === 'assign_shift') {
        $pdo->prepare('INSERT INTO shift_assignment (ShiftID, EmployeeID, PumpID, OpeningReading, ClosingReading, VarianceLitres) VALUES (?, ?, ?, ?, NULLIF(?, ""), ?)')
            ->execute([(int) post_value('ShiftID'), (int) post_value('EmployeeID'), (int) post_value('PumpID'), (float) post_value('OpeningReading'), post_value('ClosingReading'), (float) post_value('VarianceLitres', 0)]);
        done('Shift assignment added.', $page);
    }

    if ($action === 'add_attendance') {
        $pdo->prepare('INSERT INTO attendance (EmployeeID, AttDate, CheckIn, CheckOut, HoursWorked, Notes) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([(int) post_value('EmployeeID'), post_value('AttDate'), post_value('CheckIn'), post_value('CheckOut'), (float) post_value('HoursWorked'), post_value('Notes')]);
        done('Attendance recorded.', $page);
    }

    if ($action === 'add_payroll') {
        $gross = (float) post_value('GrossSalary');
        $deductions = (float) post_value('Deductions');
        $pdo->prepare('INSERT INTO payroll (EmployeeID, Month, Year, GrossSalary, Deductions, NetSalary, PaymentDate, PaymentStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([(int) post_value('EmployeeID'), (int) post_value('Month'), (int) post_value('Year'), $gross, $deductions, max(0, $gross - $deductions), null_date('PaymentDate'), post_value('PaymentStatus')]);
        done('Payroll record added.', $page);
    }

    if ($action === 'add_leave') {
        $pdo->prepare('INSERT INTO leave_request (EmployeeID, LeaveType, StartDate, EndDate, DaysCount, Status, ApprovedBy) VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, 0))')
            ->execute([(int) post_value('EmployeeID'), post_value('LeaveType'), post_value('StartDate'), post_value('EndDate'), (int) post_value('DaysCount'), post_value('Status'), (int) post_value('ApprovedBy')]);
        done('Leave request added.', $page);
    }

    if ($action === 'add_asset') {
        $pdo->prepare('INSERT INTO asset (PumpID, AssetName, AssetType, SerialNumber, PurchaseDate, PurchaseValue, Status) VALUES (NULLIF(?, 0), ?, ?, ?, ?, ?, ?)')
            ->execute([(int) post_value('PumpID'), post_value('AssetName'), post_value('AssetType'), post_value('SerialNumber'), null_date('PurchaseDate'), (float) post_value('PurchaseValue'), post_value('Status')]);
        done('Asset added.', $page);
    }

    if ($action === 'add_maintenance') {
        $pdo->prepare('INSERT INTO maintenance_log (AssetID, TechnicianID, MaintenanceDate, MaintenanceType, Description, FaultCode, RepairCost, NextServiceDate, ComplianceStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([(int) post_value('AssetID'), (int) post_value('TechnicianID'), post_value('MaintenanceDate'), post_value('MaintenanceType'), post_value('Description'), post_value('FaultCode'), (float) post_value('RepairCost'), null_date('NextServiceDate'), post_value('ComplianceStatus')]);
        done('Maintenance log added.', $page);
    }
}

function page_login(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $stmt = db()->prepare('
                SELECT ua.*, ur.RoleName
                FROM user_account ua
                JOIN user_role ur ON ur.RoleID = ua.RoleID
                WHERE ua.Username = ? AND ua.Status = "Active"
                LIMIT 1
            ');
            $stmt->execute([post_value('username')]);
            $user = $stmt->fetch();
            if ($user && password_verify((string) post_value('password'), $user['PasswordHash'])) {
                unset($user['PasswordHash']);
                $_SESSION['user'] = $user;
                db()->prepare('UPDATE user_account SET LastLogin = NOW() WHERE UserID = ?')->execute([$user['UserID']]);
                redirect('dashboard');
            }
            flash('Invalid username or password.', 'error');
        } catch (Throwable) {
            flash('Database is not ready. Open setup.php first.', 'error');
        }
    }
    render_header('Login');
    echo '<section class="login-panel"><div><h1>PetroStation DBMS</h1><p>Role-based access for fuel sales, inventory, procurement, HR, maintenance, and reports.</p></div>';
    show_flash();
    echo '<form method="post" class="form-card"><label>Username<input name="username" required autofocus placeholder="manager"></label><label>Password<input name="password" type="password" required placeholder="admin123"></label><button type="submit">Log in</button><a href="setup.php">Run setup first</a></form></section>';
    render_footer();
    exit;
}

function page_dashboard(): void
{
    require_module('dashboard');
    render_header('Dashboard');
    show_flash();
    echo '<section class="ops-hero"><div><p class="eyebrow">Station command center</p><h1>Daily operations overview</h1><p>Monitor fuel availability, sales activity, procurement, customers, employees, equipment, and compliance from one manager workspace.</p></div><div class="status-strip"><span>Branch</span><strong>Main Station</strong><span>Operating day</span><strong>' . date('d M Y') . '</strong></div></section>';
    echo '<section class="quick-actions"><a href="index.php?page=sales">Record Sale</a><a href="index.php?page=inventory&sub=fuel">Edit Prices</a><a href="index.php?page=procurement&sub=orders">Create PO</a><a href="index.php?page=customers&sub=fleet">Fleet Credit</a></section>';
    echo '<section class="stats-grid">';
    foreach ([['Fuel types','fuel_type','green'],['Tanks','tank','blue'],['Pumps','pump','orange'],['Sales','sale_transaction','red'],['Suppliers','supplier','blue'],['Employees','employee','green'],['Assets','asset','orange'],['Invoices','invoice','red']] as [$label,$table,$tone]) {
        echo '<article class="stat ' . h($tone) . '"><span>' . count_table($table) . '</span><small>' . h($label) . '</small></article>';
    }
    echo '</section>';
    echo '<section class="dashboard-grid">';
    low_stock_panel();
    recent_sales();
    echo '</section>';
    render_footer();
    exit;
}

function page_sales(): void
{
    require_module('sales');
    render_header('Sales');
    show_flash();
    page_intro('Sales', 'Record fuel transactions with product, pump, attendant, shift, customer, and payment traceability.', 'The selected pump determines the fuel product and unit price; stock is deducted from the matching tank.');
    echo '<section class="module-grid"><article class="panel"><h1>Record fuel sale</h1><p class="hint">Choose a pump; the product and unit price come from the pump fuel type. The system reduces stock from the matching tank.</p><form method="post" class="stack">';
    hidden_action('record_sale');
    select_field('PumpID', 'Pump / product', 'SELECT p.PumpID AS id, CONCAT(p.PumpNumber, " - ", ft.FuelName, " @ UGX ", ft.UnitPrice, " / litre") AS label FROM pump p JOIN fuel_type ft ON ft.FuelTypeID = p.FuelTypeID WHERE p.Status = "Active"');
    select_field('ShiftID', 'Shift', 'SELECT ShiftID AS id, CONCAT(ShiftDate, " - ", ShiftName, " (", Status, ")") AS label FROM shift ORDER BY ShiftDate DESC');
    select_field('EmployeeID', 'Attendant', 'SELECT EmployeeID AS id, CONCAT(FirstName, " ", LastName, " - ", Role) AS label FROM employee WHERE Status = "Active"');
    select_field('CustomerID', 'Customer', 'SELECT CustomerID AS id, CustomerName AS label FROM customer', true, 'Walk-in / none');
    echo '<label>Quantity dispensed (litres)<input name="QuantityDispensed" type="number" min="0.001" step="0.001" required></label>';
    enum_select('PaymentMode', 'Payment mode', ['Cash','Card','Mobile Money','Fleet Credit','Bank Transfer']);
    echo '<label>Receipt number<input name="ReceiptNumber" required value="RCPT-' . date('Ymd-His') . '"></label><button>Save sale</button></form></article>';
    recent_sales();
    echo '</section>';
    render_footer();
    exit;
}

function page_inventory(): void
{
    require_module('inventory');
    $sub = current_sub('fuel');
    render_header('Inventory');
    show_flash();
    page_intro('Inventory', 'Manage fuel products, prices, tanks, pump status, and dip readings.', 'Use the section tabs to work on one inventory function at a time.');
    subnav('inventory', $sub, ['fuel' => 'Fuel Products', 'tanks' => 'Tanks', 'pumps' => 'Pumps', 'dip' => 'Dip Readings']);
    if ($sub === 'fuel') {
        echo '<section class="module-grid"><article class="panel"><h1>Add fuel product</h1><form method="post" class="stack">' . action_input('add_fuel_type') . '<label>Fuel name<input name="FuelName" required></label><label>Unit price<input name="UnitPrice" type="number" min="0" step="0.01" required></label><label>Measurement unit<input name="MeasurementUnit" value="Litres"></label><button>Add product</button></form></article>';
        echo '<article class="panel"><h1>Edit product prices</h1><div class="record-list">';
        foreach (db()->query('SELECT * FROM fuel_type ORDER BY FuelName') as $row) {
            echo '<form method="post" class="record-row">' . action_input('update_fuel_price') . '<input type="hidden" name="FuelTypeID" value="' . h($row['FuelTypeID']) . '"><strong>' . h($row['FuelName']) . '</strong><label>Unit price<input name="UnitPrice" type="number" min="0" step="0.01" value="' . h($row['UnitPrice']) . '"></label><label>Unit<input name="MeasurementUnit" value="' . h($row['MeasurementUnit']) . '"></label><button>Update</button></form>';
        }
        echo '</div></article></section>';
    } elseif ($sub === 'tanks') {
        echo '<section class="module-grid"><article class="panel"><h1>Add tank</h1><form method="post" class="stack">' . action_input('add_tank');
        select_field('FuelTypeID', 'Fuel', 'SELECT FuelTypeID AS id, FuelName AS label FROM fuel_type');
        echo '<label>Tank label<input name="TankLabel" required></label><label>Capacity<input name="CapacityLitres" type="number" min="1" step="0.01" required></label><label>Current level<input name="CurrentLevel" type="number" min="0" step="0.01" required></label><label>Reorder threshold<input name="ReorderThreshold" type="number" min="0" step="0.01" required></label><button>Add tank</button></form></article><article class="panel"><h1>Tank register</h1>';
        tank_table();
        echo '</article></section>';
    } elseif ($sub === 'pumps') {
        echo '<section class="module-grid"><article class="panel"><h1>Add pump</h1><form method="post" class="stack">' . action_input('add_pump');
        select_field('FuelTypeID', 'Fuel dispensed', 'SELECT FuelTypeID AS id, FuelName AS label FROM fuel_type');
        echo '<label>Pump number<input name="PumpNumber" required></label>';
        enum_select('Status', 'Status', ['Active','Inactive','Maintenance','Faulty']);
        echo '<label>Last calibration<input name="LastCalibrationDate" type="date"></label><label>Next calibration<input name="NextCalibrationDue" type="date"></label><button>Add pump</button></form></article><article class="panel"><h1>Pump register</h1>';
        simple_table('SELECT p.PumpNumber, ft.FuelName, p.Status, p.NextCalibrationDue FROM pump p JOIN fuel_type ft ON ft.FuelTypeID = p.FuelTypeID ORDER BY p.PumpNumber', ['PumpNumber'=>'Pump', 'FuelName'=>'Product', 'Status'=>'Status', 'NextCalibrationDue'=>'Next Calibration']);
        echo '</article></section>';
    } else {
        echo '<section class="module-grid"><article class="panel"><h1>Update dip reading</h1><form method="post" class="stack">' . action_input('update_tank');
        select_field('TankID', 'Tank', 'SELECT TankID AS id, TankLabel AS label FROM tank');
        echo '<label>Current level<input name="CurrentLevel" type="number" min="0" step="0.01" required></label><button>Update level</button></form></article><article class="panel"><h1>Current tank levels</h1>';
        tank_table();
        echo '</article></section>';
    }
    render_footer();
    exit;
}

function page_customers(): void
{
    require_module('customers');
    $sub = current_sub('profiles');
    render_header('Customers');
    show_flash();
    page_intro('Customers', 'Manage walk-in, individual, corporate, and fleet customer records.', 'Fleet credit is separated from customer profiles so account control stays clear.');
    subnav('customers', $sub, ['profiles' => 'Customer Profiles', 'fleet' => 'Fleet Accounts']);
    if ($sub === 'fleet') {
        echo '<section class="module-grid"><article class="panel"><h1>Add fleet account</h1><form method="post" class="stack">' . action_input('add_fleet_account');
        select_field('CustomerID', 'Fleet customer', 'SELECT CustomerID AS id, CustomerName AS label FROM customer WHERE CustomerType IN ("Fleet","Corporate")');
        echo '<label>Credit limit<input name="CreditLimit" type="number" min="0" step="0.01"></label><label>Outstanding balance<input name="OutstandingBalance" type="number" min="0" step="0.01"></label>';
        enum_select('AccountStatus', 'Status', ['Active','Suspended','Closed']);
        echo '<button>Add account</button></form></article><article class="panel"><h1>Fleet accounts</h1>';
        simple_table('SELECT c.CustomerName, fa.CreditLimit, fa.OutstandingBalance, fa.AccountStatus, fa.LastStatementDate FROM fleet_account fa JOIN customer c ON c.CustomerID = fa.CustomerID ORDER BY fa.AccountID DESC', ['CustomerName'=>'Customer','CreditLimit'=>'Credit Limit','OutstandingBalance'=>'Outstanding','AccountStatus'=>'Status','LastStatementDate'=>'Statement']);
        echo '</article></section>';
    } else {
        echo '<section class="module-grid"><article class="panel"><h1>Add customer</h1><form method="post" class="stack">' . action_input('add_customer');
        echo '<label>Name<input name="CustomerName" required></label>';
        enum_select('CustomerType', 'Type', ['Walk-in','Fleet','Corporate','Individual']);
        echo '<label>Phone<input name="PhoneNumber"></label><label>Email<input name="Email" type="email"></label><label>Address<input name="Address"></label><label>Loyalty points<input name="LoyaltyPoints" type="number" min="0" step="0.01" value="0"></label><button>Add customer</button></form></article><article class="panel"><h1>Customer register</h1>';
        simple_table('SELECT CustomerName, CustomerType, PhoneNumber, Email, LoyaltyPoints FROM customer ORDER BY CustomerID DESC LIMIT 50', ['CustomerName'=>'Name','CustomerType'=>'Type','PhoneNumber'=>'Phone','Email'=>'Email','LoyaltyPoints'=>'Loyalty']);
        echo '</article></section>';
    }
    render_footer();
    exit;
}

function page_procurement(): void
{
    require_module('procurement');
    $sub = current_sub('suppliers');
    render_header('Procurement');
    show_flash();
    page_intro('Procurement', 'Control suppliers, purchase orders, deliveries, tank receipts, and invoice matching.', 'Deliveries update tank stock immediately and purchase orders show the fuel product being supplied.');
    subnav('procurement', $sub, ['suppliers' => 'Suppliers', 'orders' => 'Purchase Orders', 'deliveries' => 'Deliveries', 'invoices' => 'Invoices']);
    if ($sub === 'orders') {
        echo '<section class="module-grid wide-left"><article class="panel"><h1>Create purchase order</h1><form method="post" class="stack">' . action_input('add_po');
        select_field('SupplierID', 'Supplier', 'SELECT SupplierID AS id, SupplierName AS label FROM supplier');
        select_field('EmployeeID', 'Raised by', 'SELECT EmployeeID AS id, CONCAT(FirstName, " ", LastName) AS label FROM employee WHERE Status = "Active"');
        select_field('FuelTypeID', 'Fuel product', 'SELECT FuelTypeID AS id, FuelName AS label FROM fuel_type');
        echo '<label>Order date<input name="OrderDate" type="date" value="' . date('Y-m-d') . '" required></label><label>Expected delivery<input name="ExpectedDelivery" type="date"></label><label>Quantity<input name="QuantityOrdered" type="number" min="1" step="0.01" required></label><label>Agreed unit price<input name="AgreedUnitPrice" type="number" min="0" step="0.01" required></label>';
        enum_select('Status', 'Status', ['Draft','Submitted','Approved','Delivered','Cancelled']);
        echo '<label>Notes<input name="Notes"></label><button>Create PO</button></form></article><article class="panel"><h1>Purchase order register</h1>';
        po_table();
        echo '</article></section>';
    } elseif ($sub === 'deliveries') {
        echo '<section class="module-grid wide-left"><article class="panel"><h1>Record delivery</h1><form method="post" class="stack">' . action_input('record_delivery');
        select_field('POID', 'Purchase order', 'SELECT po.POID AS id, CONCAT("#", po.POID, " - ", s.SupplierName, " - ", ft.FuelName) AS label FROM purchase_order po JOIN supplier s ON s.SupplierID = po.SupplierID JOIN fuel_type ft ON ft.FuelTypeID = po.FuelTypeID');
        select_field('TankID', 'Receiving tank', 'SELECT TankID AS id, TankLabel AS label FROM tank');
        echo '<label>Delivery date<input name="DeliveryDate" type="date" value="' . date('Y-m-d') . '" required></label><label>Quantity delivered<input name="QuantityDelivered" type="number" min="1" step="0.01" required></label><label>Variance litres<input name="VarianceLitres" type="number" step="0.01" value="0"></label><label>Driver<input name="DriverName"></label><label>Vehicle Reg<input name="VehicleReg"></label><label>Delivery note #<input name="DeliveryNoteNumber" required value="DN-' . date('Ymd-His') . '"></label><button>Record delivery</button></form></article><article class="panel"><h1>Delivery register</h1>';
        delivery_table();
        echo '</article></section>';
    } elseif ($sub === 'invoices') {
        echo '<section class="module-grid wide-left"><article class="panel"><h1>Add invoice</h1><form method="post" class="stack">' . action_input('add_invoice');
        select_field('DeliveryID', 'Delivery', 'SELECT DeliveryID AS id, CONCAT("Delivery #", DeliveryID, " - ", DeliveryNoteNumber) AS label FROM delivery WHERE DeliveryID NOT IN (SELECT DeliveryID FROM invoice)');
        select_field('SupplierID', 'Supplier', 'SELECT SupplierID AS id, SupplierName AS label FROM supplier');
        echo '<label>Invoice date<input name="InvoiceDate" type="date" value="' . date('Y-m-d') . '" required></label><label>Due date<input name="DueDate" type="date" required></label><label>Amount<input name="Amount" type="number" min="0" step="0.01" required></label>';
        enum_select('PaymentStatus', 'Payment status', ['Pending','Partially Paid','Paid','Overdue','Cancelled']);
        echo '<label>Payment date<input name="PaymentDate" type="date"></label><button>Add invoice</button></form></article><article class="panel"><h1>Invoice register</h1>';
        simple_table('SELECT i.InvoiceID, s.SupplierName, i.Amount, i.PaymentStatus, i.DueDate FROM invoice i JOIN supplier s ON s.SupplierID = i.SupplierID ORDER BY i.InvoiceID DESC', ['InvoiceID'=>'Invoice','SupplierName'=>'Supplier','Amount'=>'Amount','PaymentStatus'=>'Status','DueDate'=>'Due']);
        echo '</article></section>';
    } else {
        echo '<section class="module-grid"><article class="panel"><h1>Add supplier</h1><form method="post" class="stack">' . action_input('add_supplier') . '<label>Supplier name<input name="SupplierName" required></label><label>Contact person<input name="ContactPerson"></label><label>Phone<input name="PhoneNumber"></label><label>Email<input name="Email" type="email"></label><label>Address<input name="Address"></label><label>Rating<input name="PerformanceRating" type="number" min="0" max="5" step="0.01" value="0"></label>';
        enum_select('Status', 'Status', ['Active','Inactive','Blacklisted']);
        echo '<button>Add supplier</button></form></article><article class="panel"><h1>Supplier register</h1>';
        simple_table('SELECT SupplierName, ContactPerson, PhoneNumber, PerformanceRating, Status FROM supplier ORDER BY SupplierID DESC', ['SupplierName'=>'Supplier','ContactPerson'=>'Contact','PhoneNumber'=>'Phone','PerformanceRating'=>'Rating','Status'=>'Status']);
        echo '</article></section>';
    }
    render_footer();
    exit;
}

function page_hr(): void
{
    require_module('employees');
    $sub = current_sub('employees');
    render_header('HR');
    show_flash();
    page_intro('HR', 'Manage employees, shifts, attendance, payroll, and leave records.', 'Each HR function has its own workspace so daily operations stay organized.');
    subnav('employees', $sub, ['employees' => 'Employees', 'shifts' => 'Shifts', 'attendance' => 'Attendance', 'payroll' => 'Payroll', 'leave' => 'Leave']);
    if ($sub === 'shifts') {
        echo '<section class="module-grid wide-left"><article class="panel"><h1>Shift setup</h1><form method="post" class="stack">' . action_input('add_shift') . '<label>Shift date<input name="ShiftDate" type="date" value="' . date('Y-m-d') . '" required></label><label>Start<input name="StartTime" type="time" required></label><label>End<input name="EndTime" type="time" required></label><label>Name<input name="ShiftName" required></label>';
        enum_select('Status', 'Status', ['Scheduled','Open','Closed','Cancelled']);
        echo '<button>Add shift</button></form><form method="post" class="stack subform">' . action_input('assign_shift');
        select_field('ShiftID', 'Shift', 'SELECT ShiftID AS id, CONCAT(ShiftDate, " - ", ShiftName) AS label FROM shift ORDER BY ShiftDate DESC');
        select_field('EmployeeID', 'Employee', 'SELECT EmployeeID AS id, CONCAT(FirstName, " ", LastName) AS label FROM employee WHERE Status = "Active"');
        select_field('PumpID', 'Pump', 'SELECT PumpID AS id, PumpNumber AS label FROM pump');
        echo '<label>Opening reading<input name="OpeningReading" type="number" min="0" step="0.01" value="0"></label><label>Closing reading<input name="ClosingReading" type="number" min="0" step="0.01"></label><label>Variance litres<input name="VarianceLitres" type="number" step="0.01" value="0"></label><button>Assign</button></form></article><article class="panel"><h1>Shift assignments</h1>';
        simple_table('SELECT s.ShiftDate, s.ShiftName, CONCAT(e.FirstName, " ", e.LastName) AS Employee, p.PumpNumber, sa.OpeningReading, sa.ClosingReading FROM shift_assignment sa JOIN shift s ON s.ShiftID = sa.ShiftID JOIN employee e ON e.EmployeeID = sa.EmployeeID JOIN pump p ON p.PumpID = sa.PumpID ORDER BY sa.AssignmentID DESC', ['ShiftDate'=>'Date','ShiftName'=>'Shift','Employee'=>'Employee','PumpNumber'=>'Pump','OpeningReading'=>'Opening','ClosingReading'=>'Closing']);
        echo '</article></section>';
    } elseif ($sub === 'attendance') {
        echo '<section class="module-grid"><article class="panel"><h1>Add attendance</h1><form method="post" class="stack">' . action_input('add_attendance');
        select_field('EmployeeID', 'Employee', 'SELECT EmployeeID AS id, CONCAT(FirstName, " ", LastName) AS label FROM employee WHERE Status = "Active"');
        echo '<label>Date<input name="AttDate" type="date" value="' . date('Y-m-d') . '" required></label><label>Check in<input name="CheckIn" type="time"></label><label>Check out<input name="CheckOut" type="time"></label><label>Hours<input name="HoursWorked" type="number" min="0" step="0.01"></label><label>Notes<input name="Notes"></label><button>Add attendance</button></form></article><article class="panel"><h1>Attendance register</h1>';
        simple_table('SELECT a.AttDate, CONCAT(e.FirstName, " ", e.LastName) AS Employee, a.CheckIn, a.CheckOut, a.HoursWorked, a.Notes FROM attendance a JOIN employee e ON e.EmployeeID = a.EmployeeID ORDER BY a.AttendanceID DESC', ['AttDate'=>'Date','Employee'=>'Employee','CheckIn'=>'In','CheckOut'=>'Out','HoursWorked'=>'Hours','Notes'=>'Notes']);
        echo '</article></section>';
    } elseif ($sub === 'payroll') {
        echo '<section class="module-grid"><article class="panel"><h1>Add payroll</h1><form method="post" class="stack">' . action_input('add_payroll');
        select_field('EmployeeID', 'Employee', 'SELECT EmployeeID AS id, CONCAT(FirstName, " ", LastName) AS label FROM employee WHERE Status = "Active"');
        echo '<label>Month<input name="Month" type="number" min="1" max="12" value="' . date('n') . '"></label><label>Year<input name="Year" type="number" value="' . date('Y') . '"></label><label>Gross<input name="GrossSalary" type="number" min="0" step="0.01"></label><label>Deductions<input name="Deductions" type="number" min="0" step="0.01" value="0"></label><label>Payment date<input name="PaymentDate" type="date"></label>';
        enum_select('PaymentStatus', 'Status', ['Pending','Paid','Failed','Cancelled']);
        echo '<button>Add payroll</button></form></article><article class="panel"><h1>Payroll register</h1>';
        simple_table('SELECT CONCAT(e.FirstName, " ", e.LastName) AS Employee, p.Month, p.Year, p.GrossSalary, p.Deductions, p.NetSalary, p.PaymentStatus FROM payroll p JOIN employee e ON e.EmployeeID = p.EmployeeID ORDER BY p.PayrollID DESC', ['Employee'=>'Employee','Month'=>'Month','Year'=>'Year','GrossSalary'=>'Gross','Deductions'=>'Deductions','NetSalary'=>'Net','PaymentStatus'=>'Status']);
        echo '</article></section>';
    } elseif ($sub === 'leave') {
        echo '<section class="module-grid"><article class="panel"><h1>Add leave request</h1><form method="post" class="stack">' . action_input('add_leave');
        select_field('EmployeeID', 'Employee', 'SELECT EmployeeID AS id, CONCAT(FirstName, " ", LastName) AS label FROM employee WHERE Status = "Active"');
        enum_select('LeaveType', 'Leave type', ['Annual','Sick','Maternity','Paternity','Emergency','Unpaid','Other']);
        echo '<label>Start<input name="StartDate" type="date" required></label><label>End<input name="EndDate" type="date" required></label><label>Days<input name="DaysCount" type="number" min="1" required></label>';
        enum_select('Status', 'Status', ['Pending','Approved','Rejected','Cancelled']);
        select_field('ApprovedBy', 'Approved by', 'SELECT EmployeeID AS id, CONCAT(FirstName, " ", LastName) AS label FROM employee WHERE Status = "Active"', true, 'Not approved yet');
        echo '<button>Add leave</button></form></article><article class="panel"><h1>Leave register</h1>';
        simple_table('SELECT CONCAT(e.FirstName, " ", e.LastName) AS Employee, lr.LeaveType, lr.StartDate, lr.EndDate, lr.DaysCount, lr.Status FROM leave_request lr JOIN employee e ON e.EmployeeID = lr.EmployeeID ORDER BY lr.LeaveID DESC', ['Employee'=>'Employee','LeaveType'=>'Type','StartDate'=>'Start','EndDate'=>'End','DaysCount'=>'Days','Status'=>'Status']);
        echo '</article></section>';
    } else {
        echo '<section class="module-grid"><article class="panel"><h1>Add employee</h1><form method="post" class="stack">' . action_input('add_employee') . '<label>First name<input name="FirstName" required></label><label>Last name<input name="LastName" required></label><label>Role<input name="Role" required></label><label>Phone<input name="PhoneNumber"></label><label>National ID<input name="NationalID"></label><label>Base salary<input name="BaseSalary" type="number" min="0" step="0.01"></label><label>Hire date<input name="HireDate" type="date" value="' . date('Y-m-d') . '" required></label>';
        enum_select('Status', 'Status', ['Active','Inactive','On Leave','Terminated']);
        echo '<button>Add employee</button></form></article><article class="panel"><h1>Employee register</h1>';
        simple_table('SELECT EmployeeID, CONCAT(FirstName, " ", LastName) AS Name, Role, PhoneNumber, BaseSalary, Status FROM employee ORDER BY EmployeeID DESC', ['EmployeeID'=>'ID','Name'=>'Name','Role'=>'Role','PhoneNumber'=>'Phone','BaseSalary'=>'Salary','Status'=>'Status']);
        echo '</article></section>';
    }
    render_footer();
    exit;
}

function page_maintenance(): void
{
    require_module('maintenance');
    $sub = current_sub('assets');
    render_header('Maintenance');
    show_flash();
    page_intro('Maintenance', 'Track equipment assets, pump-linked devices, servicing, calibration, and compliance.', 'Assets and maintenance logs are split so managers can inspect equipment condition quickly.');
    subnav('maintenance', $sub, ['assets' => 'Assets', 'logs' => 'Maintenance Logs']);
    if ($sub === 'logs') {
        echo '<section class="module-grid wide-left"><article class="panel"><h1>Add maintenance log</h1><form method="post" class="stack">' . action_input('add_maintenance');
        select_field('AssetID', 'Asset', 'SELECT AssetID AS id, AssetName AS label FROM asset');
        select_field('TechnicianID', 'Technician', 'SELECT EmployeeID AS id, CONCAT(FirstName, " ", LastName) AS label FROM employee WHERE Status = "Active"');
        echo '<label>Date<input name="MaintenanceDate" type="date" value="' . date('Y-m-d') . '" required></label>';
        enum_select('MaintenanceType', 'Type', ['Routine','Repair','Calibration','Inspection','Emergency']);
        echo '<label>Description<input name="Description"></label><label>Fault code<input name="FaultCode"></label><label>Repair cost<input name="RepairCost" type="number" min="0" step="0.01" value="0"></label><label>Next service<input name="NextServiceDate" type="date"></label>';
        enum_select('ComplianceStatus', 'Compliance', ['Compliant','Non-Compliant','Pending Review']);
        echo '<button>Add log</button></form></article><article class="panel"><h1>Maintenance register</h1>';
        simple_table('SELECT a.AssetName, ml.MaintenanceDate, ml.MaintenanceType, ml.RepairCost, ml.NextServiceDate, ml.ComplianceStatus FROM maintenance_log ml JOIN asset a ON a.AssetID = ml.AssetID ORDER BY ml.LogID DESC', ['AssetName'=>'Asset','MaintenanceDate'=>'Date','MaintenanceType'=>'Type','RepairCost'=>'Cost','NextServiceDate'=>'Next Service','ComplianceStatus'=>'Compliance']);
        echo '</article></section>';
    } else {
        echo '<section class="module-grid"><article class="panel"><h1>Add asset</h1><form method="post" class="stack">' . action_input('add_asset');
        select_field('PumpID', 'Linked pump', 'SELECT PumpID AS id, PumpNumber AS label FROM pump', true, 'No pump');
        echo '<label>Asset name<input name="AssetName" required></label><label>Asset type<input name="AssetType" required></label><label>Serial number<input name="SerialNumber"></label><label>Purchase date<input name="PurchaseDate" type="date"></label><label>Purchase value<input name="PurchaseValue" type="number" min="0" step="0.01"></label>';
        enum_select('Status', 'Status', ['Active','Inactive','Maintenance','Disposed']);
        echo '<button>Add asset</button></form></article><article class="panel"><h1>Asset register</h1>';
        simple_table('SELECT a.AssetName, a.AssetType, p.PumpNumber, a.PurchaseValue, a.Status FROM asset a LEFT JOIN pump p ON p.PumpID = a.PumpID ORDER BY a.AssetID DESC', ['AssetName'=>'Asset','AssetType'=>'Type','PumpNumber'=>'Pump','PurchaseValue'=>'Value','Status'=>'Status']);
        echo '</article></section>';
    }
    render_footer();
    exit;
}

function page_reports(): void
{
    require_module('reports');
    render_header('Reports');
    $sales = db()->query('SELECT COALESCE(SUM(TotalAmount),0) AS total, COALESCE(SUM(QuantityDispensed),0) AS litres FROM sale_transaction')->fetch();
    $credit = db()->query('SELECT COALESCE(SUM(OutstandingBalance),0) AS total FROM fleet_account')->fetch();
    echo '<section class="stats-grid"><article class="stat green"><span>' . money($sales['total']) . '</span><small>Total sales value</small></article><article class="stat blue"><span>' . number_format((float) $sales['litres'], 2) . '</span><small>Litres sold</small></article><article class="stat orange"><span>' . money($credit['total']) . '</span><small>Fleet outstanding</small></article><article class="stat red"><span>UGX 12.3M</span><small>Projected annual benefit</small></article></section>';
    echo '<section class="module-grid">';
    recent_sales();
    echo '<article class="panel"><h2>Sales by fuel product</h2>';
    simple_table('SELECT ft.FuelName, COALESCE(SUM(st.QuantityDispensed),0) AS Litres, COALESCE(SUM(st.TotalAmount),0) AS SalesValue FROM fuel_type ft LEFT JOIN pump p ON p.FuelTypeID = ft.FuelTypeID LEFT JOIN sale_transaction st ON st.PumpID = p.PumpID GROUP BY ft.FuelTypeID, ft.FuelName ORDER BY SalesValue DESC', ['FuelName'=>'Fuel','Litres'=>'Litres','SalesValue'=>'Sales Value']);
    echo '</article></section>';
    low_stock_panel();
    render_footer();
    exit;
}

function page_risks(): void
{
    require_module('risks');
    render_header('Risks');
    echo '<section class="panel"><h1>Risk assessment</h1><div class="risk-grid">';
    foreach ([['Technical','Data loss, downtime, cybersecurity breach.','Backups, UPS, encrypted backups, firewall, password policy.'],['Organisational','Resistance, champion loss, poor data quality.','Stakeholder engagement, superuser, validation rules, audits.'],['Environmental','Power outages and internet loss.','UPS, generator integration, local server with cloud backup/sync.']] as [$type,$risk,$mitigation]) {
        echo '<article><h2>' . h($type) . '</h2><p>' . h($risk) . '</p><strong>Mitigation</strong><p>' . h($mitigation) . '</p></article>';
    }
    echo '</div></section>';
    render_footer();
    exit;
}

function record_sale(): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pumpId = (int) post_value('PumpID');
        $qty = (float) post_value('QuantityDispensed');
        $pumpStmt = $pdo->prepare('SELECT p.FuelTypeID, ft.UnitPrice, ft.FuelName FROM pump p JOIN fuel_type ft ON ft.FuelTypeID = p.FuelTypeID WHERE p.PumpID = ? FOR UPDATE');
        $pumpStmt->execute([$pumpId]);
        $pump = $pumpStmt->fetch();
        if (!$pump) {
            throw new RuntimeException('Selected pump was not found.');
        }
        $tankStmt = $pdo->prepare('SELECT TankID, CurrentLevel FROM tank WHERE FuelTypeID = ? AND CurrentLevel >= ? ORDER BY CurrentLevel DESC LIMIT 1 FOR UPDATE');
        $tankStmt->execute([(int) $pump['FuelTypeID'], $qty]);
        $tank = $tankStmt->fetch();
        if (!$tank) {
            throw new RuntimeException('Not enough ' . $pump['FuelName'] . ' stock in any tank for this sale.');
        }
        $price = (float) $pump['UnitPrice'];
        $total = $qty * $price;
        $pdo->prepare('INSERT INTO sale_transaction (PumpID, ShiftID, EmployeeID, CustomerID, QuantityDispensed, UnitPrice, TotalAmount, PaymentMode, ReceiptNumber) VALUES (?, ?, ?, NULLIF(?, 0), ?, ?, ?, ?, ?)')
            ->execute([$pumpId, (int) post_value('ShiftID'), (int) post_value('EmployeeID'), (int) post_value('CustomerID'), $qty, $price, $total, post_value('PaymentMode'), post_value('ReceiptNumber')]);
        $pdo->prepare('UPDATE tank SET CurrentLevel = CurrentLevel - ?, LastDipDate = CURDATE() WHERE TankID = ?')
            ->execute([$qty, (int) $tank['TankID']]);
        if (post_value('PaymentMode') === 'Fleet Credit' && (int) post_value('CustomerID') > 0) {
            $pdo->prepare('UPDATE fleet_account SET OutstandingBalance = OutstandingBalance + ? WHERE CustomerID = ?')
                ->execute([$total, (int) post_value('CustomerID')]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function record_delivery(): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $poid = (int) post_value('POID');
        $tankId = (int) post_value('TankID');
        $qty = (float) post_value('QuantityDelivered');
        $tank = $pdo->prepare('SELECT CurrentLevel, CapacityLitres FROM tank WHERE TankID = ? FOR UPDATE');
        $tank->execute([$tankId]);
        $tankData = $tank->fetch();
        if (!$tankData) {
            throw new RuntimeException('Selected tank was not found.');
        }
        if ((float) $tankData['CurrentLevel'] + $qty > (float) $tankData['CapacityLitres']) {
            throw new RuntimeException('Delivery would exceed tank capacity.');
        }
        $pdo->prepare('INSERT INTO delivery (POID, TankID, DeliveryDate, QuantityDelivered, VarianceLitres, DriverName, VehicleReg, DeliveryNoteNumber) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$poid, $tankId, post_value('DeliveryDate'), $qty, (float) post_value('VarianceLitres'), post_value('DriverName'), post_value('VehicleReg'), post_value('DeliveryNoteNumber')]);
        $pdo->prepare('UPDATE tank SET CurrentLevel = CurrentLevel + ?, LastDipDate = CURDATE() WHERE TankID = ?')
            ->execute([$qty, $tankId]);
        $pdo->prepare('UPDATE purchase_order SET Status = "Delivered" WHERE POID = ?')->execute([$poid]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function select_field(string $name, string $label, string $sql, bool $allowEmpty = false, string $emptyLabel = 'Select'): void
{
    echo '<label>' . h($label) . '<select name="' . h($name) . '" required>';
    if ($allowEmpty) {
        echo '<option value="0">' . h($emptyLabel) . '</option>';
    }
    foreach (fetch_options($sql) as $row) {
        echo '<option value="' . h($row['id']) . '">' . h($row['label']) . '</option>';
    }
    echo '</select></label>';
}

function enum_select(string $name, string $label, array $values): void
{
    echo '<label>' . h($label) . '<select name="' . h($name) . '">';
    foreach ($values as $value) {
        echo '<option>' . h($value) . '</option>';
    }
    echo '</select></label>';
}

function hidden_action(string $action): void
{
    echo action_input($action);
}

function action_input(string $action): string
{
    $sub = (string) ($_GET['sub'] ?? '');
    return '<input type="hidden" name="action" value="' . h($action) . '"><input type="hidden" name="return_sub" value="' . h($sub) . '">';
}

function current_sub(string $default): string
{
    $sub = (string) ($_GET['sub'] ?? $default);
    $sub = preg_replace('/[^a-z0-9_-]/i', '', $sub) ?? '';
    return $sub !== '' ? $sub : $default;
}

function page_intro(string $title, string $subtitle, string $note = ''): void
{
    $user = current_user();
    echo '<section class="page-head"><div><p class="eyebrow">Manager workspace</p><h1>' . h($title) . '</h1><p>' . h($subtitle) . '</p></div>';
    echo '<aside class="page-meta"><span>Business date</span><strong>' . date('d M Y') . '</strong><span>Access level</span><strong>' . h($user['RoleName'] ?? 'Active user') . '</strong></aside>';
    echo '</section>';
}

function subnav(string $page, string $active, array $items): void
{
    echo '<div class="subnav">';
    foreach ($items as $key => $label) {
        $class = $key === $active ? ' class="active"' : '';
        echo '<a' . $class . ' href="index.php?page=' . h($page) . '&sub=' . h($key) . '">' . h($label) . '</a>';
    }
    echo '</div>';
}

function null_date(string $key): ?string
{
    $value = trim((string) post_value($key));
    return $value === '' ? null : $value;
}

function redirect(string $page): never
{
    header('Location: index.php?page=' . urlencode($page));
    exit;
}

function done(string $message, string $page): never
{
    flash($message);
    $sub = trim((string) ($_POST['return_sub'] ?? ''));
    $url = 'index.php?page=' . urlencode($page);
    if ($sub !== '') {
        $url .= '&sub=' . urlencode($sub);
    }
    header('Location: ' . $url);
    exit;
}

function simple_table(string $sql, array $columns): void
{
    $rows = db()->query($sql)->fetchAll();
    echo '<div class="table-wrap"><table><tr>';
    foreach ($columns as $label) {
        echo '<th>' . h($label) . '</th>';
    }
    echo '</tr>';
    if (!$rows) {
        echo '<tr><td colspan="' . count($columns) . '">No records yet.</td></tr>';
    }
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($columns as $key => $label) {
            $value = $row[$key] ?? '';
            if (str_contains(strtolower((string) $key), 'amount') || str_contains(strtolower((string) $key), 'salary') || str_contains(strtolower((string) $key), 'price') || $key === 'SalesValue' || $key === 'BaseSalary' || $key === 'RepairCost' || $key === 'PurchaseValue') {
                $value = money($value);
            }
            echo '<td>' . h($value) . '</td>';
        }
        echo '</tr>';
    }
    echo '</table></div>';
}

function tank_table(): void
{
    $rows = db()->query('SELECT t.*, ft.FuelName FROM tank t JOIN fuel_type ft ON ft.FuelTypeID = t.FuelTypeID ORDER BY t.TankLabel')->fetchAll();
    echo '<div class="table-wrap"><table><tr><th>Tank</th><th>Fuel</th><th>Capacity</th><th>Current</th><th>Threshold</th><th>Status</th></tr>';
    foreach ($rows as $row) {
        $status = (float) $row['CurrentLevel'] <= (float) $row['ReorderThreshold'] ? '<span class="badge red">Reorder</span>' : '<span class="badge green">OK</span>';
        echo '<tr><td>' . h($row['TankLabel']) . '</td><td>' . h($row['FuelName']) . '</td><td>' . h($row['CapacityLitres']) . ' L</td><td>' . h($row['CurrentLevel']) . ' L</td><td>' . h($row['ReorderThreshold']) . ' L</td><td>' . $status . '</td></tr>';
    }
    echo '</table></div>';
}

function customer_tables(): void
{
    echo '<h2>Customers</h2>';
    simple_table('SELECT CustomerName, CustomerType, PhoneNumber, LoyaltyPoints FROM customer ORDER BY CustomerID DESC LIMIT 50', ['CustomerName'=>'Name','CustomerType'=>'Type','PhoneNumber'=>'Phone','LoyaltyPoints'=>'Loyalty']);
    echo '<h2>Fleet accounts</h2>';
    simple_table('SELECT c.CustomerName, fa.CreditLimit, fa.OutstandingBalance, fa.AccountStatus FROM fleet_account fa JOIN customer c ON c.CustomerID = fa.CustomerID ORDER BY fa.AccountID DESC', ['CustomerName'=>'Customer','CreditLimit'=>'Credit Limit','OutstandingBalance'=>'Outstanding','AccountStatus'=>'Status']);
}

function po_table(): void
{
    simple_table('SELECT po.POID, s.SupplierName, ft.FuelName, po.QuantityOrdered, po.AgreedUnitPrice, po.Status FROM purchase_order po JOIN supplier s ON s.SupplierID = po.SupplierID JOIN fuel_type ft ON ft.FuelTypeID = po.FuelTypeID ORDER BY po.POID DESC', ['POID'=>'PO','SupplierName'=>'Supplier','FuelName'=>'Fuel','QuantityOrdered'=>'Qty','AgreedUnitPrice'=>'Unit Price','Status'=>'Status']);
}

function delivery_table(): void
{
    simple_table('SELECT d.DeliveryID, d.DeliveryNoteNumber, t.TankLabel, d.QuantityDelivered, d.VarianceLitres, d.DeliveryDate FROM delivery d JOIN tank t ON t.TankID = d.TankID ORDER BY d.DeliveryID DESC', ['DeliveryID'=>'ID','DeliveryNoteNumber'=>'Note','TankLabel'=>'Tank','QuantityDelivered'=>'Delivered','VarianceLitres'=>'Variance','DeliveryDate'=>'Date']);
}

function recent_sales(): void
{
    echo '<article class="panel"><h2>Recent sales</h2>';
    simple_table('SELECT st.ReceiptNumber, ft.FuelName, p.PumpNumber, st.QuantityDispensed, st.UnitPrice, st.TotalAmount, st.PaymentMode FROM sale_transaction st JOIN pump p ON p.PumpID = st.PumpID JOIN fuel_type ft ON ft.FuelTypeID = p.FuelTypeID ORDER BY st.TransactionDateTime DESC LIMIT 25', ['ReceiptNumber'=>'Receipt','FuelName'=>'Product','PumpNumber'=>'Pump','QuantityDispensed'=>'Litres','UnitPrice'=>'Unit Price','TotalAmount'=>'Amount','PaymentMode'=>'Mode']);
    echo '</article>';
}

function low_stock_panel(): void
{
    echo '<section class="panel"><h2>Reorder alerts</h2>';
    $rows = db()->query('SELECT t.TankLabel, ft.FuelName, t.CurrentLevel, t.ReorderThreshold FROM tank t JOIN fuel_type ft ON ft.FuelTypeID = t.FuelTypeID WHERE t.CurrentLevel <= t.ReorderThreshold ORDER BY t.CurrentLevel ASC')->fetchAll();
    if (!$rows) {
        echo '<p>No tanks are below reorder threshold.</p>';
    } else {
        echo '<div class="table-wrap"><table><tr><th>Tank</th><th>Fuel</th><th>Current</th><th>Threshold</th></tr>';
        foreach ($rows as $row) {
            echo '<tr><td>' . h($row['TankLabel']) . '</td><td>' . h($row['FuelName']) . '</td><td>' . h($row['CurrentLevel']) . ' L</td><td>' . h($row['ReorderThreshold']) . ' L</td></tr>';
        }
        echo '</table></div>';
    }
    echo '</section>';
}
