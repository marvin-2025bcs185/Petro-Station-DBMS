-- MySQL schema for Group 5 Oil & Gas project | Petro Station DBMS

CREATE DATABASE IF NOT EXISTS petrostation_dbms;
USE petrostation_dbms;

SET FOREIGN_KEY_CHECKS = 0;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE user_role (
    RoleID INT AUTO_INCREMENT PRIMARY KEY,
    RoleName VARCHAR(50) NOT NULL UNIQUE,
    Description VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE user_account (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    RoleID INT NOT NULL,
    Username VARCHAR(50) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    FullName VARCHAR(120) NOT NULL,
    Email VARCHAR(120),
    Status ENUM('Active', 'Inactive', 'Suspended') NOT NULL DEFAULT 'Active',
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    LastLogin DATETIME,
    CONSTRAINT fk_user_role
        FOREIGN KEY (RoleID) REFERENCES user_role(RoleID)
) ENGINE=InnoDB;

CREATE TABLE fuel_type (
    FuelTypeID INT AUTO_INCREMENT PRIMARY KEY,
    FuelName VARCHAR(50) NOT NULL UNIQUE,
    UnitPrice DECIMAL(12,2) NOT NULL,
    MeasurementUnit VARCHAR(20) NOT NULL DEFAULT 'Litres',
    CONSTRAINT chk_fuel_unit_price CHECK (UnitPrice >= 0)
) ENGINE=InnoDB;

CREATE TABLE tank (
    TankID INT AUTO_INCREMENT PRIMARY KEY,
    FuelTypeID INT NOT NULL,
    TankLabel VARCHAR(50) NOT NULL UNIQUE,
    CapacityLitres DECIMAL(12,2) NOT NULL,
    CurrentLevel DECIMAL(12,2) NOT NULL DEFAULT 0,
    ReorderThreshold DECIMAL(12,2) NOT NULL DEFAULT 0,
    LastDipDate DATE,
    CONSTRAINT fk_tank_fuel_type
        FOREIGN KEY (FuelTypeID) REFERENCES fuel_type(FuelTypeID),
    CONSTRAINT chk_tank_capacity CHECK (CapacityLitres > 0),
    CONSTRAINT chk_tank_current_level CHECK (CurrentLevel >= 0),
    CONSTRAINT chk_tank_reorder CHECK (ReorderThreshold >= 0),
    CONSTRAINT chk_tank_level_capacity CHECK (CurrentLevel <= CapacityLitres)
) ENGINE=InnoDB;

CREATE TABLE pump (
    PumpID INT AUTO_INCREMENT PRIMARY KEY,
    FuelTypeID INT NOT NULL,
    PumpNumber VARCHAR(30) NOT NULL UNIQUE,
    Status ENUM('Active', 'Inactive', 'Maintenance', 'Faulty') NOT NULL DEFAULT 'Active',
    LastCalibrationDate DATE,
    NextCalibrationDue DATE,
    CONSTRAINT fk_pump_fuel_type
        FOREIGN KEY (FuelTypeID) REFERENCES fuel_type(FuelTypeID)
) ENGINE=InnoDB;

CREATE TABLE employee (
    EmployeeID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT UNIQUE,
    FirstName VARCHAR(60) NOT NULL,
    LastName VARCHAR(60) NOT NULL,
    Role VARCHAR(60) NOT NULL,
    PhoneNumber VARCHAR(30),
    NationalID VARCHAR(50) UNIQUE,
    BaseSalary DECIMAL(12,2) NOT NULL DEFAULT 0,
    HireDate DATE NOT NULL,
    Status ENUM('Active', 'Inactive', 'On Leave', 'Terminated') NOT NULL DEFAULT 'Active',
    CONSTRAINT fk_employee_user
        FOREIGN KEY (UserID) REFERENCES user_account(UserID),
    CONSTRAINT chk_employee_salary CHECK (BaseSalary >= 0)
) ENGINE=InnoDB;

CREATE TABLE shift (
    ShiftID INT AUTO_INCREMENT PRIMARY KEY,
    ShiftDate DATE NOT NULL,
    StartTime TIME NOT NULL,
    EndTime TIME NOT NULL,
    ShiftName VARCHAR(50) NOT NULL,
    Status ENUM('Scheduled', 'Open', 'Closed', 'Cancelled') NOT NULL DEFAULT 'Scheduled',
    INDEX idx_shift_date (ShiftDate)
) ENGINE=InnoDB;

CREATE TABLE shift_assignment (
    AssignmentID INT AUTO_INCREMENT PRIMARY KEY,
    ShiftID INT NOT NULL,
    EmployeeID INT NOT NULL,
    PumpID INT NOT NULL,
    OpeningReading DECIMAL(12,2) NOT NULL DEFAULT 0,
    ClosingReading DECIMAL(12,2),
    VarianceLitres DECIMAL(12,2) DEFAULT 0,
    CONSTRAINT fk_assignment_shift
        FOREIGN KEY (ShiftID) REFERENCES shift(ShiftID),
    CONSTRAINT fk_assignment_employee
        FOREIGN KEY (EmployeeID) REFERENCES employee(EmployeeID),
    CONSTRAINT fk_assignment_pump
        FOREIGN KEY (PumpID) REFERENCES pump(PumpID),
    CONSTRAINT chk_assignment_opening CHECK (OpeningReading >= 0),
    CONSTRAINT chk_assignment_closing CHECK (ClosingReading IS NULL OR ClosingReading >= OpeningReading),
    UNIQUE KEY uq_shift_employee_pump (ShiftID, EmployeeID, PumpID)
) ENGINE=InnoDB;

CREATE TABLE attendance (
    AttendanceID INT AUTO_INCREMENT PRIMARY KEY,
    EmployeeID INT NOT NULL,
    AttDate DATE NOT NULL,
    CheckIn TIME,
    CheckOut TIME,
    HoursWorked DECIMAL(5,2) DEFAULT 0,
    Notes VARCHAR(255),
    CONSTRAINT fk_attendance_employee
        FOREIGN KEY (EmployeeID) REFERENCES employee(EmployeeID),
    CONSTRAINT chk_attendance_hours CHECK (HoursWorked >= 0),
    UNIQUE KEY uq_employee_attendance_date (EmployeeID, AttDate)
) ENGINE=InnoDB;

CREATE TABLE payroll (
    PayrollID INT AUTO_INCREMENT PRIMARY KEY,
    EmployeeID INT NOT NULL,
    Month TINYINT NOT NULL,
    Year SMALLINT NOT NULL,
    GrossSalary DECIMAL(12,2) NOT NULL DEFAULT 0,
    Deductions DECIMAL(12,2) NOT NULL DEFAULT 0,
    NetSalary DECIMAL(12,2) NOT NULL DEFAULT 0,
    PaymentDate DATE,
    PaymentStatus ENUM('Pending', 'Paid', 'Failed', 'Cancelled') NOT NULL DEFAULT 'Pending',
    CONSTRAINT fk_payroll_employee
        FOREIGN KEY (EmployeeID) REFERENCES employee(EmployeeID),
    CONSTRAINT chk_payroll_month CHECK (Month BETWEEN 1 AND 12),
    CONSTRAINT chk_payroll_amounts CHECK (GrossSalary >= 0 AND Deductions >= 0 AND NetSalary >= 0),
    UNIQUE KEY uq_employee_payroll_period (EmployeeID, Month, Year)
) ENGINE=InnoDB;

CREATE TABLE leave_request (
    LeaveID INT AUTO_INCREMENT PRIMARY KEY,
    EmployeeID INT NOT NULL,
    LeaveType ENUM('Annual', 'Sick', 'Maternity', 'Paternity', 'Emergency', 'Unpaid', 'Other') NOT NULL,
    StartDate DATE NOT NULL,
    EndDate DATE NOT NULL,
    DaysCount INT NOT NULL,
    Status ENUM('Pending', 'Approved', 'Rejected', 'Cancelled') NOT NULL DEFAULT 'Pending',
    ApprovedBy INT,
    CONSTRAINT fk_leave_employee
        FOREIGN KEY (EmployeeID) REFERENCES employee(EmployeeID),
    CONSTRAINT fk_leave_approved_by
        FOREIGN KEY (ApprovedBy) REFERENCES employee(EmployeeID),
    CONSTRAINT chk_leave_dates CHECK (EndDate >= StartDate),
    CONSTRAINT chk_leave_days CHECK (DaysCount > 0)
) ENGINE=InnoDB;

CREATE TABLE customer (
    CustomerID INT AUTO_INCREMENT PRIMARY KEY,
    CustomerName VARCHAR(120) NOT NULL,
    CustomerType ENUM('Walk-in', 'Fleet', 'Corporate', 'Individual') NOT NULL DEFAULT 'Walk-in',
    PhoneNumber VARCHAR(30),
    Email VARCHAR(120),
    Address VARCHAR(255),
    LoyaltyPoints DECIMAL(12,2) NOT NULL DEFAULT 0,
    RegisteredDate DATE NOT NULL,
    CONSTRAINT chk_customer_loyalty CHECK (LoyaltyPoints >= 0)
) ENGINE=InnoDB;

CREATE TABLE fleet_account (
    AccountID INT AUTO_INCREMENT PRIMARY KEY,
    CustomerID INT NOT NULL UNIQUE,
    CreditLimit DECIMAL(12,2) NOT NULL DEFAULT 0,
    OutstandingBalance DECIMAL(12,2) NOT NULL DEFAULT 0,
    LastStatementDate DATE,
    AccountStatus ENUM('Active', 'Suspended', 'Closed') NOT NULL DEFAULT 'Active',
    CONSTRAINT fk_fleet_customer
        FOREIGN KEY (CustomerID) REFERENCES customer(CustomerID),
    CONSTRAINT chk_fleet_credit CHECK (CreditLimit >= 0),
    CONSTRAINT chk_fleet_balance CHECK (OutstandingBalance >= 0)
) ENGINE=InnoDB;

CREATE TABLE sale_transaction (
    TransactionID INT AUTO_INCREMENT PRIMARY KEY,
    PumpID INT NOT NULL,
    ShiftID INT NOT NULL,
    EmployeeID INT NOT NULL,
    CustomerID INT,
    TransactionDateTime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    QuantityDispensed DECIMAL(12,3) NOT NULL,
    UnitPrice DECIMAL(12,2) NOT NULL,
    TotalAmount DECIMAL(12,2) NOT NULL,
    PaymentMode ENUM('Cash', 'Card', 'Mobile Money', 'Fleet Credit', 'Bank Transfer') NOT NULL,
    ReceiptNumber VARCHAR(80) NOT NULL UNIQUE,
    CONSTRAINT fk_sale_pump
        FOREIGN KEY (PumpID) REFERENCES pump(PumpID),
    CONSTRAINT fk_sale_shift
        FOREIGN KEY (ShiftID) REFERENCES shift(ShiftID),
    CONSTRAINT fk_sale_employee
        FOREIGN KEY (EmployeeID) REFERENCES employee(EmployeeID),
    CONSTRAINT fk_sale_customer
        FOREIGN KEY (CustomerID) REFERENCES customer(CustomerID),
    CONSTRAINT chk_sale_quantity CHECK (QuantityDispensed > 0),
    CONSTRAINT chk_sale_unit_price CHECK (UnitPrice >= 0),
    CONSTRAINT chk_sale_total CHECK (TotalAmount >= 0),
    INDEX idx_sale_datetime (TransactionDateTime),
    INDEX idx_sale_shift (ShiftID),
    INDEX idx_sale_employee (EmployeeID)
) ENGINE=InnoDB;

CREATE TABLE supplier (
    SupplierID INT AUTO_INCREMENT PRIMARY KEY,
    SupplierName VARCHAR(120) NOT NULL,
    ContactPerson VARCHAR(120),
    PhoneNumber VARCHAR(30),
    Email VARCHAR(120),
    Address VARCHAR(255),
    PerformanceRating DECIMAL(3,2) DEFAULT 0,
    Status ENUM('Active', 'Inactive', 'Blacklisted') NOT NULL DEFAULT 'Active',
    CONSTRAINT chk_supplier_rating CHECK (PerformanceRating BETWEEN 0 AND 5)
) ENGINE=InnoDB;

CREATE TABLE purchase_order (
    POID INT AUTO_INCREMENT PRIMARY KEY,
    SupplierID INT NOT NULL,
    EmployeeID INT NOT NULL,
    FuelTypeID INT NOT NULL,
    OrderDate DATE NOT NULL,
    ExpectedDelivery DATE,
    QuantityOrdered DECIMAL(12,2) NOT NULL,
    AgreedUnitPrice DECIMAL(12,2) NOT NULL,
    Status ENUM('Draft', 'Submitted', 'Approved', 'Delivered', 'Cancelled') NOT NULL DEFAULT 'Draft',
    Notes VARCHAR(255),
    CONSTRAINT fk_po_supplier
        FOREIGN KEY (SupplierID) REFERENCES supplier(SupplierID),
    CONSTRAINT fk_po_employee
        FOREIGN KEY (EmployeeID) REFERENCES employee(EmployeeID),
    CONSTRAINT fk_po_fuel_type
        FOREIGN KEY (FuelTypeID) REFERENCES fuel_type(FuelTypeID),
    CONSTRAINT chk_po_quantity CHECK (QuantityOrdered > 0),
    CONSTRAINT chk_po_price CHECK (AgreedUnitPrice >= 0),
    INDEX idx_po_status (Status)
) ENGINE=InnoDB;

CREATE TABLE delivery (
    DeliveryID INT AUTO_INCREMENT PRIMARY KEY,
    POID INT NOT NULL,
    TankID INT NOT NULL,
    DeliveryDate DATE NOT NULL,
    QuantityDelivered DECIMAL(12,2) NOT NULL,
    VarianceLitres DECIMAL(12,2) DEFAULT 0,
    DriverName VARCHAR(120),
    VehicleReg VARCHAR(50),
    DeliveryNoteNumber VARCHAR(80) NOT NULL UNIQUE,
    CONSTRAINT fk_delivery_po
        FOREIGN KEY (POID) REFERENCES purchase_order(POID),
    CONSTRAINT fk_delivery_tank
        FOREIGN KEY (TankID) REFERENCES tank(TankID),
    CONSTRAINT chk_delivery_quantity CHECK (QuantityDelivered > 0)
) ENGINE=InnoDB;

CREATE TABLE invoice (
    InvoiceID INT AUTO_INCREMENT PRIMARY KEY,
    DeliveryID INT NOT NULL UNIQUE,
    SupplierID INT NOT NULL,
    InvoiceDate DATE NOT NULL,
    DueDate DATE NOT NULL,
    Amount DECIMAL(12,2) NOT NULL,
    PaymentStatus ENUM('Pending', 'Partially Paid', 'Paid', 'Overdue', 'Cancelled') NOT NULL DEFAULT 'Pending',
    PaymentDate DATE,
    CONSTRAINT fk_invoice_delivery
        FOREIGN KEY (DeliveryID) REFERENCES delivery(DeliveryID),
    CONSTRAINT fk_invoice_supplier
        FOREIGN KEY (SupplierID) REFERENCES supplier(SupplierID),
    CONSTRAINT chk_invoice_amount CHECK (Amount >= 0),
    CONSTRAINT chk_invoice_dates CHECK (DueDate >= InvoiceDate)
) ENGINE=InnoDB;

CREATE TABLE asset (
    AssetID INT AUTO_INCREMENT PRIMARY KEY,
    PumpID INT UNIQUE,
    AssetName VARCHAR(120) NOT NULL,
    AssetType VARCHAR(80) NOT NULL,
    SerialNumber VARCHAR(120) UNIQUE,
    PurchaseDate DATE,
    PurchaseValue DECIMAL(12,2) DEFAULT 0,
    Status ENUM('Active', 'Inactive', 'Maintenance', 'Disposed') NOT NULL DEFAULT 'Active',
    CONSTRAINT fk_asset_pump
        FOREIGN KEY (PumpID) REFERENCES pump(PumpID),
    CONSTRAINT chk_asset_value CHECK (PurchaseValue >= 0)
) ENGINE=InnoDB;

CREATE TABLE maintenance_log (
    LogID INT AUTO_INCREMENT PRIMARY KEY,
    AssetID INT NOT NULL,
    TechnicianID INT NOT NULL,
    MaintenanceDate DATE NOT NULL,
    MaintenanceType ENUM('Routine', 'Repair', 'Calibration', 'Inspection', 'Emergency') NOT NULL,
    Description TEXT,
    FaultCode VARCHAR(50),
    RepairCost DECIMAL(12,2) DEFAULT 0,
    NextServiceDate DATE,
    ComplianceStatus ENUM('Compliant', 'Non-Compliant', 'Pending Review') NOT NULL DEFAULT 'Pending Review',
    CONSTRAINT fk_maintenance_asset
        FOREIGN KEY (AssetID) REFERENCES asset(AssetID),
    CONSTRAINT fk_maintenance_technician
        FOREIGN KEY (TechnicianID) REFERENCES employee(EmployeeID),
    CONSTRAINT chk_maintenance_cost CHECK (RepairCost >= 0),
    INDEX idx_maintenance_date (MaintenanceDate)
) ENGINE=InnoDB;

INSERT INTO user_role (RoleName, Description) VALUES
('Station Manager', 'Full read/write access to all modules and dashboards'),
('Pump Attendant', 'Limited transaction and shift-entry permissions'),
('Cashier / Accounts Clerk', 'Finance and customer module access'),
('Procurement Officer', 'Supplier, purchase order, and inventory access'),
('Owner / Director', 'Read-only executive reports and dashboards');
