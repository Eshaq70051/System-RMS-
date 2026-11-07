-- ==========================================================
-- Smart Restaurant Management System (RMS)
-- Complete Database Schema (SQLite & MySQL compatible)
-- ==========================================================

PRAGMA foreign_keys = ON;

-- 1. Branches (الفروع)
CREATE TABLE IF NOT EXISTS branches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    phone VARCHAR(50),
    email VARCHAR(100),
    address TEXT,
    tax_number VARCHAR(50),
    commercial_registration VARCHAR(50),
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. Roles (الأدوار والصلاحيات)
CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. Users (المستخدمين)
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER,
    role_id INTEGER NOT NULL,
    name VARCHAR(150) NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    avatar VARCHAR(255),
    is_active INTEGER DEFAULT 1,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- 4. Permissions (الصلاحيات التفصيلية)
CREATE TABLE IF NOT EXISTS permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    module VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- 5. Categories (أقسام وتصنيفات المأكولات)
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    icon VARCHAR(50) DEFAULT 'utensils',
    image VARCHAR(255),
    color VARCHAR(20) DEFAULT '#4f46e5',
    sort_order INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 6. Products (الأصناف والوجبات)
CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER NOT NULL,
    name VARCHAR(150) NOT NULL,
    barcode VARCHAR(100) UNIQUE,
    sku VARCHAR(100) UNIQUE,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    cost_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    unit VARCHAR(50) DEFAULT 'قطعة',
    image VARCHAR(255),
    is_available INTEGER DEFAULT 1,
    is_active INTEGER DEFAULT 1,
    has_recipe INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

-- Product Variations (الأحجام والخيارات)
CREATE TABLE IF NOT EXISTS product_variants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    name VARCHAR(100) NOT NULL,
    additional_price DECIMAL(10, 2) DEFAULT 0.00,
    sku VARCHAR(100),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Product Addons (الإضافات الاختيارية)
CREATE TABLE IF NOT EXISTS product_addons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 7. Dining Tables (الطاولات)
CREATE TABLE IF NOT EXISTS dining_tables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    table_number VARCHAR(50) NOT NULL,
    section VARCHAR(100) DEFAULT 'الصالة الرئيسية',
    capacity INTEGER DEFAULT 4,
    status VARCHAR(50) DEFAULT 'available', -- available, occupied, reserved
    current_order_id INTEGER,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
);

-- 8. Inventory Items (المواد الخام والمخزون)
CREATE TABLE IF NOT EXISTS inventory_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    name VARCHAR(150) NOT NULL,
    sku VARCHAR(100),
    unit VARCHAR(50) NOT NULL, -- كجم, لتر, جرام, حبة, علبة
    min_stock_alert DECIMAL(10, 3) DEFAULT 5.000,
    current_stock DECIMAL(10, 3) DEFAULT 0.000,
    cost_per_unit DECIMAL(10, 2) DEFAULT 0.00,
    expiry_date DATE,
    batch_number VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
);

-- 9. Recipes & Ingredients (الوصفات وتكلفة الوجبات)
CREATE TABLE IF NOT EXISTS recipes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER UNIQUE NOT NULL,
    name VARCHAR(150) NOT NULL,
    prep_time_minutes INTEGER DEFAULT 15,
    instructions TEXT,
    calculated_cost DECIMAL(10, 2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS recipe_ingredients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    recipe_id INTEGER NOT NULL,
    inventory_item_id INTEGER NOT NULL,
    quantity_required DECIMAL(10, 3) NOT NULL,
    unit VARCHAR(50) NOT NULL,
    waste_percentage DECIMAL(5, 2) DEFAULT 0.00,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
);

-- 10. Stock Movements (سجل حركة المواد والمخزون)
CREATE TABLE IF NOT EXISTS stock_movements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    inventory_item_id INTEGER NOT NULL,
    branch_id INTEGER NOT NULL,
    type VARCHAR(50) NOT NULL, -- purchase, sale_deduction, adjustment_in, adjustment_out, return
    quantity DECIMAL(10, 3) NOT NULL,
    unit_cost DECIMAL(10, 2) DEFAULT 0.00,
    total_cost DECIMAL(10, 2) DEFAULT 0.00,
    reference_type VARCHAR(50), -- order, purchase, manual
    reference_id INTEGER,
    notes TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 11. Suppliers (الموردين)
CREATE TABLE IF NOT EXISTS suppliers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(150) NOT NULL,
    company_name VARCHAR(150),
    phone VARCHAR(50),
    email VARCHAR(100),
    address TEXT,
    tax_number VARCHAR(50),
    current_balance DECIMAL(10, 2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 12. Purchase Orders (المشتريات وفواتير الموردين)
CREATE TABLE IF NOT EXISTS purchase_orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    supplier_id INTEGER NOT NULL,
    invoice_number VARCHAR(100) NOT NULL,
    purchase_date DATE NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(10, 2) DEFAULT 0.00,
    discount_amount DECIMAL(10, 2) DEFAULT 0.00,
    grand_total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    paid_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    remaining_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    payment_status VARCHAR(50) DEFAULT 'unpaid', -- paid, partial, unpaid
    status VARCHAR(50) DEFAULT 'received', -- received, pending, cancelled
    notes TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    purchase_order_id INTEGER NOT NULL,
    inventory_item_id INTEGER NOT NULL,
    quantity DECIMAL(10, 3) NOT NULL,
    unit_cost DECIMAL(10, 2) NOT NULL,
    total_cost DECIMAL(10, 2) NOT NULL,
    expiry_date DATE,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS supplier_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier_id INTEGER NOT NULL,
    purchase_order_id INTEGER,
    payment_date DATE NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'cash', -- cash, bank_transfer, cheque
    reference_number VARCHAR(100),
    notes TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 13. Cashier Shifts (ورديات الكاشير)
CREATE TABLE IF NOT EXISTS cashier_shifts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    opened_at DATETIME NOT NULL,
    closed_at DATETIME,
    opening_cash DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    closing_cash_actual DECIMAL(10, 2) DEFAULT 0.00,
    expected_cash DECIMAL(10, 2) DEFAULT 0.00,
    cash_difference DECIMAL(10, 2) DEFAULT 0.00,
    total_sales DECIMAL(10, 2) DEFAULT 0.00,
    total_returns DECIMAL(10, 2) DEFAULT 0.00,
    total_cash_sales DECIMAL(10, 2) DEFAULT 0.00,
    total_card_sales DECIMAL(10, 2) DEFAULT 0.00,
    status VARCHAR(50) DEFAULT 'open', -- open, closed
    notes TEXT,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- 14. Orders (الطلبات والمبيعات)
CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    branch_id INTEGER NOT NULL,
    shift_id INTEGER,
    user_id INTEGER NOT NULL,
    table_id INTEGER,
    order_type VARCHAR(50) DEFAULT 'dine_in', -- dine_in, takeaway, delivery, phone, online
    customer_name VARCHAR(150),
    customer_phone VARCHAR(50),
    delivery_address TEXT,
    status VARCHAR(50) DEFAULT 'pending', -- pending, in_kitchen, ready, completed, cancelled
    kitchen_status VARCHAR(50) DEFAULT 'waiting', -- waiting, cooking, done
    kitchen_notes TEXT,
    subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    discount_type VARCHAR(20) DEFAULT 'fixed', -- fixed, percentage
    discount_value DECIMAL(10, 2) DEFAULT 0.00,
    discount_amount DECIMAL(10, 2) DEFAULT 0.00,
    tax_amount DECIMAL(10, 2) DEFAULT 0.00,
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    paid_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    change_amount DECIMAL(10, 2) DEFAULT 0.00,
    payment_method VARCHAR(50) DEFAULT 'cash', -- cash, card, split, credit
    cash_amount DECIMAL(10, 2) DEFAULT 0.00,
    card_amount DECIMAL(10, 2) DEFAULT 0.00,
    is_paid INTEGER DEFAULT 1,
    kitchen_started_at DATETIME,
    kitchen_ready_at DATETIME,
    completed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES cashier_shifts(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (table_id) REFERENCES dining_tables(id) ON DELETE SET NULL
);

-- 15. Order Items (تفاصيل الأصناف بالطلب)
CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    cost_price DECIMAL(10, 2) DEFAULT 0.00,
    quantity INTEGER NOT NULL DEFAULT 1,
    total_price DECIMAL(10, 2) NOT NULL,
    notes TEXT,
    addons_json TEXT, -- JSON array of selected addons
    status VARCHAR(50) DEFAULT 'pending', -- pending, cooking, ready, served
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- 16. Order Returns & Refunds (المرتجعات)
CREATE TABLE IF NOT EXISTS order_returns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    branch_id INTEGER NOT NULL,
    return_number VARCHAR(50) UNIQUE NOT NULL,
    refund_amount DECIMAL(10, 2) NOT NULL,
    refund_method VARCHAR(50) DEFAULT 'cash',
    reason TEXT,
    returned_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (returned_by) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS order_return_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_return_id INTEGER NOT NULL,
    order_item_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_refund DECIMAL(10, 2) NOT NULL,
    restore_stock INTEGER DEFAULT 1,
    FOREIGN KEY (order_return_id) REFERENCES order_returns(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE RESTRICT,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- 17. Employees & HR (الموظفين والموارد البشرية)
CREATE TABLE IF NOT EXISTS employees (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    user_id INTEGER,
    full_name VARCHAR(150) NOT NULL,
    national_id VARCHAR(50),
    phone VARCHAR(50) NOT NULL,
    job_title VARCHAR(100) NOT NULL,
    department VARCHAR(100) DEFAULT 'الخدمة والصالة',
    salary DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    hire_date DATE,
    emergency_phone VARCHAR(50),
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS employee_attendances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    date DATE NOT NULL,
    clock_in TIME,
    clock_out TIME,
    working_hours DECIMAL(5, 2) DEFAULT 0.00,
    status VARCHAR(50) DEFAULT 'present', -- present, absent, late, leave
    notes TEXT,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS employee_payrolls (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    month INTEGER NOT NULL,
    year INTEGER NOT NULL,
    basic_salary DECIMAL(10, 2) NOT NULL,
    bonuses DECIMAL(10, 2) DEFAULT 0.00,
    deductions DECIMAL(10, 2) DEFAULT 0.00,
    advances DECIMAL(10, 2) DEFAULT 0.00,
    net_salary DECIMAL(10, 2) NOT NULL,
    payment_status VARCHAR(50) DEFAULT 'pending', -- paid, pending
    payment_date DATE,
    payment_method VARCHAR(50) DEFAULT 'bank_transfer',
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- 18. Expenses & Financials (المصروفات والإدارة المالية)
CREATE TABLE IF NOT EXISTS expense_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE IF NOT EXISTS expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    expense_category_id INTEGER NOT NULL,
    title VARCHAR(150) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    date DATE NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'cash', -- cash, bank
    paid_to VARCHAR(150),
    receipt_image VARCHAR(255),
    notes TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (expense_category_id) REFERENCES expense_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 19. Cash Drawer Transactions (حركة الصندوق والخزينة)
CREATE TABLE IF NOT EXISTS cash_drawer_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    shift_id INTEGER,
    type VARCHAR(20) NOT NULL, -- in, out
    amount DECIMAL(10, 2) NOT NULL,
    category VARCHAR(50) NOT NULL, -- sale, return, expense, deposit, withdrawal, opening_balance
    reference_type VARCHAR(50), -- order, expense, shift, manual
    reference_id INTEGER,
    description TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES cashier_shifts(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 20. Audit Logs (سجل التدقيق والعمليات)
CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    details TEXT,
    ip_address VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 21. General Settings (الإعدادات العامة)
CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
