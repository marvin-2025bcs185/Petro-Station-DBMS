# PetroStation DBMS

PetroStation DBMS is a web-based database management system for petrol station operations. It was developed for a Database Systems project by **Group 5 | Oil & Gas**.

The system centralizes fuel sales, inventory, customers, fleet accounts, suppliers, procurement, HR, equipment maintenance, reporting, and risk review in one role-based application.

## Overview

Petrol stations handle fast-moving products, shift-based staff, customer credit accounts, supplier deliveries, and sensitive stock records. When these activities are handled manually, managers can face revenue leakage, poor stock visibility, weak customer tracking, and delayed reports.

PetroStation DBMS solves this by providing a relational database and PHP frontend where station users can record, view, and manage operational data through a browser.

## Features

- Role-based login and module access
- Manager dashboard with KPIs, quick actions, low-stock alerts, and recent sales
- Fuel product management with editable product prices
- Tank, pump, and dip reading management
- Product-aware fuel sale recording
- Automatic tank stock reduction after sales
- Customer and fleet account management
- Supplier, purchase order, delivery, and invoice management
- Employee, shift, attendance, payroll, and leave management
- Asset and maintenance log management
- Reports and risk assessment pages

## Tech Stack

| Layer | Technology |
| --- | --- |
| Frontend | HTML, CSS |
| Backend | PHP |
| Database | MySQL / MariaDB |
| Local server | XAMPP |
| Database access | PDO |

## Project Structure

```text
Project/
|-- webapp/
|   |-- assets/
|   |   `-- style.css
|   |-- config.php
|   |-- config.local.example.php
|   |-- index.php
|   |-- lib.php
|   `-- setup.php
|-- petrostation_schema.sql
|-- PetroStation_ER_Diagram.pdf
|-- PetroStation_DBMS_Final_Report.pdf
|-- PetroStation_DBMS_v2_Slides.pptx
|-- start_xampp_mysql_3307.bat
|-- .gitignore
`-- README.md
```

## Requirements

Install XAMPP with:

- Apache
- MySQL / MariaDB
- PHP

Recommended local database settings:

```text
Host: 127.0.0.1
Port: 3307
Database: petrostation_dbms
User: root
Password: blank
```

If your MySQL server uses the default port, use `3306` instead.

## Installation

1. Clone this repository:

   ```bash
   git clone <repository-url>
   ```

2. Copy the `webapp` folder into XAMPP's `htdocs` directory and rename it to `petrostation`:

   ```text
   C:\xampp\htdocs\petrostation
   ```

3. Copy the schema file into the deployed folder:

   ```text
   petrostation_schema.sql
   ```

   The deployed folder should contain:

   ```text
   C:\xampp\htdocs\petrostation\index.php
   C:\xampp\htdocs\petrostation\setup.php
   C:\xampp\htdocs\petrostation\petrostation_schema.sql
   ```

4. Create a local database config file by copying:

   ```text
   webapp/config.local.example.php
   ```

   to:

   ```text
   webapp/config.local.php
   ```

5. Update `config.local.php` if your MySQL credentials are different.

## Configuration

Example local configuration:

```php
<?php
declare(strict_types=1);

return [
    'host' => '127.0.0.1',
    'port' => '3307',
    'name' => 'petrostation_dbms',
    'user' => 'root',
    'pass' => '',
];
```

`config.local.php` is ignored by Git because it is machine-specific and may contain private database credentials.

## Database Setup

1. Start Apache and MySQL from XAMPP Control Panel.

2. Open the setup page:

   ```text
   http://localhost/petrostation/setup.php
   ```

3. Use the database connection values from your local config.

4. Click:

   ```text
   Install / Reset Database
   ```

This creates the `petrostation_dbms` database, creates all project tables, and inserts demo data.

## Running the System

After setup, open:

```text
http://localhost/petrostation/
```

Log in using one of the demo accounts below.

## Demo Accounts

All demo accounts use this password:

```text
admin123
```

| Username | Role |
| --- | --- |
| `manager` | Station Manager |
| `attendant` | Pump Attendant |
| `cashier` | Cashier / Accounts Clerk |
| `procurement` | Procurement Officer |
| `owner` | Owner / Director |

## Main Modules

| Module | Purpose |
| --- | --- |
| Dashboard | View KPIs, quick actions, low-stock alerts, and recent sales |
| Sales | Record fuel sales by pump, product, shift, attendant, customer, and payment mode |
| Inventory | Manage fuel products, prices, tanks, pumps, and dip readings |
| Customers | Manage customer profiles and fleet credit accounts |
| Procurement | Manage suppliers, purchase orders, deliveries, and invoices |
| HR | Manage employees, shifts, attendance, payroll, and leave |
| Maintenance | Manage assets and maintenance logs |
| Reports | View sales and inventory summaries |
| Risks | View risk categories and mitigations |

## Database Tables

| Area | Tables |
| --- | --- |
| Security | `user_role`, `user_account` |
| Fuel and inventory | `fuel_type`, `tank`, `pump` |
| Sales and customers | `customer`, `fleet_account`, `sale_transaction` |
| Procurement | `supplier`, `purchase_order`, `delivery`, `invoice` |
| HR | `employee`, `shift`, `shift_assignment`, `attendance`, `payroll`, `leave_request` |
| Maintenance | `asset`, `maintenance_log` |

## Usage Notes

- Use `Inventory -> Fuel Products` to add fuel products and edit product prices.
- Use `Inventory -> Tanks` to add tanks and view tank levels.
- Use `Inventory -> Dip Readings` to update current tank levels.
- Use `Sales` to record fuel sales. The selected pump determines the affected fuel product and unit price.
- Use `Customers -> Fleet Accounts` to manage credit limits and outstanding balances.
- Use `Procurement -> Purchase Orders` to create fuel orders.
- Use `Procurement -> Deliveries` to record fuel deliveries and increase tank stock.
- Use `HR` to manage employees, shifts, attendance, payroll, and leave.
- Use `Maintenance` to manage assets and maintenance logs.

## Limitations

- The prototype is designed for a single petrol station.
- Pump readings and tank dip readings are entered manually.
- The system supports adding and viewing most records, but full edit/delete workflows are not implemented for every register.
- Automated bank reconciliation is not included.
- Predictive analytics is not included.
- The system depends on accurate data entry.

## Troubleshooting

### Apache is not starting

Another application may be using port `80`. Stop the conflicting application or change Apache's port in XAMPP.

### MySQL is not starting

Another MySQL service may be using port `3306`. This project can run XAMPP MySQL on port `3307`.

Run:

```text
start_xampp_mysql_3307.bat
```

### Login says the database is not ready

Open:

```text
http://localhost/petrostation/setup.php
```

Then install or reset the database.

### Access denied for root

Check your local config file:

```text
webapp/config.local.php
```

Make sure the host, port, username, and password match your local MySQL setup.

## Project Deliverables

- `webapp/`
- `petrostation_schema.sql`
- `PetroStation_ER_Diagram.pdf`
- `Petro_Station_DBMS.pptx`
- `PetroStation_DBMS_Final_Report.pdf`

## Contributors

Group 5 | Oil & Gas | WASENDA BLASIO MARVIN 

## License

This project is for academic use.
