-- ============================================
-- Finance ERP Dashboard - Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS finance_dashboard
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE finance_dashboard;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    avatar_url VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Parties Table (Unified Customers & Suppliers)
CREATE TABLE IF NOT EXISTS parties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    gst_number VARCHAR(15) DEFAULT NULL,
    pan_number VARCHAR(10) DEFAULT NULL,
    owner_name VARCHAR(150) DEFAULT NULL,
    state VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    pincode VARCHAR(10) DEFAULT NULL,
    discount DECIMAL(5, 2) DEFAULT 0.00,
    due_days INT DEFAULT 45,
    broker_name VARCHAR(150) DEFAULT NULL,
    broker_mobile VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Products Table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    gst_percent DECIMAL(5, 2) DEFAULT 0.00,
    stock_quantity INT NOT NULL DEFAULT 0,
    item_code VARCHAR(50) DEFAULT NULL,
    hsn_code VARCHAR(20) DEFAULT NULL,
    unit VARCHAR(20) DEFAULT 'Pcs',
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Sales Bills Table
CREATE TABLE IF NOT EXISTS sales_bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    customer_id INT NOT NULL,
    bill_number VARCHAR(50) NOT NULL,
    bill_date DATE NOT NULL,
    due_days INT NOT NULL DEFAULT 0,
    due_date DATE NOT NULL,
    challan_no VARCHAR(100) DEFAULT NULL,
    challan_date DATE DEFAULT NULL,
    apply_gst TINYINT(1) DEFAULT 1,
    discount_percent DECIMAL(5, 2) DEFAULT 0.00,
    discount_amount DECIMAL(15, 2) DEFAULT 0.00,
    gst_percent DECIMAL(5, 2) DEFAULT 0.00,
    gst_amount DECIMAL(15, 2) DEFAULT 0.00,
    taxable_amount DECIMAL(15, 2) DEFAULT 0.00,
    grand_total DECIMAL(15, 2) DEFAULT 0.00,
    paid_amount DECIMAL(15, 2) DEFAULT 0.00,
    status ENUM('PAID', 'PARTIAL', 'UNPAID') DEFAULT 'UNPAID',
    remarks TEXT DEFAULT NULL,
    tds_tcs_type VARCHAR(10) DEFAULT 'NONE',
    tds_tcs_percent DECIMAL(5, 2) DEFAULT 0.00,
    tds_tcs_amount DECIMAL(15, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id, bill_number),
    FOREIGN KEY (customer_id) REFERENCES parties(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Sales Bill Items Table
CREATE TABLE IF NOT EXISTS sales_bill_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sales_bill_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    item_code VARCHAR(50) DEFAULT NULL,
    hsn_code VARCHAR(20) DEFAULT NULL,
    quantity DECIMAL(15, 2) NOT NULL DEFAULT 1.00,
    unit VARCHAR(20) DEFAULT 'Pcs',
    rate DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sales_bill_id) REFERENCES sales_bills(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Purchases Table
CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    supplier_id INT NOT NULL,
    bill_number VARCHAR(50) NOT NULL,
    bill_date DATE NOT NULL,
    due_days INT NOT NULL DEFAULT 0,
    due_date DATE NOT NULL,
    apply_gst TINYINT(1) DEFAULT 1,
    discount_percent DECIMAL(5, 2) DEFAULT 0.00,
    discount_amount DECIMAL(15, 2) DEFAULT 0.00,
    gst_percent DECIMAL(5, 2) DEFAULT 0.00,
    gst_amount DECIMAL(15, 2) DEFAULT 0.00,
    taxable_amount DECIMAL(15, 2) DEFAULT 0.00,
    amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    paid_amount DECIMAL(15, 2) DEFAULT 0.00,
    status ENUM('PAID', 'PARTIAL', 'UNPAID') DEFAULT 'UNPAID',
    remarks TEXT DEFAULT NULL,
    attachment_path VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id, bill_number),
    FOREIGN KEY (supplier_id) REFERENCES parties(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7b. Purchase Bill Items Table
CREATE TABLE IF NOT EXISTS purchase_bill_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_bill_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    item_code VARCHAR(50) DEFAULT NULL,
    hsn_code VARCHAR(20) DEFAULT NULL,
    quantity DECIMAL(15, 2) NOT NULL DEFAULT 1.00,
    unit VARCHAR(20) DEFAULT 'Pcs',
    rate DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    gst_percent DECIMAL(5, 2) DEFAULT 0.00,
    gst_amount DECIMAL(15, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (purchase_bill_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Expenses Table
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    expense_date DATE NOT NULL,
    category VARCHAR(100) NOT NULL,
    supplier_id INT DEFAULT NULL,
    amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    paid_amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    status ENUM('PAID', 'PARTIAL', 'UNPAID') NOT NULL DEFAULT 'UNPAID',
    attachment_path VARCHAR(500) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES parties(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8b. Expense Items Table
CREATE TABLE IF NOT EXISTS expense_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(15, 2) NOT NULL DEFAULT 1.00,
    rate DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Transactions Table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reference_id INT DEFAULT NULL,
    type ENUM('Sale', 'Purchase', 'Expense', 'Payment') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    description VARCHAR(255) DEFAULT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. Payments Table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sales_bill_id INT DEFAULT NULL,
    purchase_bill_id INT DEFAULT NULL,
    transaction_id INT DEFAULT NULL,
    payment_date DATE NOT NULL,
    payment_mode ENUM('Cash', 'Bank Transfer', 'UPI', 'Cheque') NOT NULL,
    reference_number VARCHAR(100) DEFAULT NULL,
    amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    tds_amount DECIMAL(15, 2) DEFAULT 0.00,
    settlement_amount DECIMAL(15, 2) DEFAULT 0.00,
    tds_percent DECIMAL(5, 2) DEFAULT 0.00,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sales_bill_id) REFERENCES sales_bills(id) ON DELETE CASCADE,
    FOREIGN KEY (purchase_bill_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 11. Audit Logs Table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Seed Default Values
-- ============================================

-- Seed Default Admin User (Password: Admin@123)
INSERT INTO users (id, full_name, email, password_hash) VALUES
(1, 'Alex Johnson', 'admin@finance.com', '$2y$10$tZ2gZ2t4Gk3h8n.3T54X2O46Fz1F7P6/x75a/O391kP58gN.aK6kK')
ON DUPLICATE KEY UPDATE id=id;

-- Seed Parties (Unified Customers & Suppliers) to Match User Image (assigned to admin user_id = 1)
INSERT INTO parties (id, user_id, name, email, phone, address, gst_number, pan_number, due_days) VALUES 
(1, 1, 'SAMBHAV TEXTILES', 'sambhav@textiles.com', '9876543210', 'Textile Market, Surat', '24SAMBH1111A1Z1', 'SAMBH1111A', 45),
(2, 1, 'SAKSHAM BOUTIQUE', 'saksham@boutique.com', '8765432109', 'Fashion Street, Mumbai', '27SAKSH2222B2Z2', 'SAKSH2222B', 45),
(3, 1, 'veera fashion', 'veera@fashion.com', '7654321098', 'Designer Hub, Delhi', '07VEERA3333C3Z3', 'VEERA3333C', 45),
(4, 1, 'PRIYANKA FASHION', 'priyanka@fashion.com', '6543210987', 'High Street, Jaipur', '08PRIYA4444D4Z4', 'PRIYA4444D', 45),
(5, 1, 'AMICHAND CREATION', 'amichand@creation.com', '5432109876', 'Craft Lane, Ahmedabad', '24AMICH5555E5Z5', 'AMICH5555E', 45),
(6, 1, 'AWARD INTERNATIONAL', 'award@international.com', '4321098765', 'Export Zone, Noida', '09AWARD6666F6Z6', 'AWARD6666F', 45),
(7, 1, 'MAA MANDIR CREATION', 'maa@mandir.com', '9988776655', 'Mandir Marg, Ahmedabad', '24ADWFM5858R1ZZ', 'ADWFM5858R', 45),
(8, 1, 'GANESH CREATION', 'ganesh@creation.com', '8877665544', 'Ganesh Market, Surat', '24DDNPS8812F1Z8', 'DDNPS8812F', 45),
(9, 1, 'Zenith Manufacturing', 'sales@zenithmfg.com', '6543210987', 'Industrial Zone B, Bangalore', '29DDDDD4444D4Z4', 'DDDDD4444D', 45),
(10, 1, 'Alpha Wholesale', 'supply@alphawholesale.com', '5432109876', 'Market Lane, Chennai', '33EEEEE5555E5Z5', 'EEEEE5555E', 45),
(11, 1, 'RADHIKA TRADERS', 'radhika@traders.com', '9911223344', 'Radhika Market, Surat', '24RADHI1111A1Z1', 'RADHI1111A', 45),
(12, 1, 'SHREE KHODIYAR CONING', 'khodiyar@coning.com', '9922334455', 'Coning Zone, Surat', '24KHODI2222B2Z2', 'KHODI2222B', 45),
(13, 1, 'MADHAV JARILON', 'madhav@jarilon.com', '9933445566', 'Jarilon Area, Mumbai', '27MADHA3333C3Z3', 'MADHA3333C', 45),
(14, 1, 'DHARMI ENTERPRISE', 'dharmi@enterprise.com', '9944556677', 'Business Park, Ahmedabad', '24DHARM4444D4Z4', 'DHARM4444D', 45),
(15, 1, 'J B ENTERPRISE', 'jb@enterprise.com', '9955667788', 'Industrial Estate, Surat', '24JBENT5555E5Z5', 'JBENT5555E', 45),
(16, 1, 'TARA TEXTILES', 'tara@textiles.com', '9966778899', 'Textile Hub, Ahmedabad', '24TARAT6666F6Z6', 'TARAT6666F', 45),
(17, 1, 'abhi kyada', 'abhi@kyada.com', '9977889900', 'Surat, Gujarat', NULL, NULL, 30),
(18, 1, 'i shree khodiyar tex', 'khodiyar@tex.com', '9988990011', 'Surat, Gujarat', NULL, NULL, 30),
(19, 1, 'ravi', 'ravi@supplier.com', '9999001122', 'Surat, Gujarat', NULL, NULL, 30),
(20, 1, 'chetanbhai', 'chetan@supplier.com', '9900112233', 'Surat, Gujarat', NULL, NULL, 30),
(21, 1, 'jigneshbhai', 'jignesh@supplier.com', '9911223344', 'Surat, Gujarat', NULL, NULL, 30),
(22, 1, 'KAVYA FASHION', 'kavya@fashion.com', '9898989898', 'Surat, Gujarat', '24KAVYA1111A1Z1', 'KAVYA1111A', 45),
(23, 1, 'ASHAPURA SAREE', 'ashapura@saree.com', '9797979797', 'Surat, Gujarat', '24ASHAP2222B2Z2', 'ASHAP2222B', 45)
ON DUPLICATE KEY UPDATE id=id;

-- Seed Products (assigned to admin user_id = 1)
INSERT INTO products (id, user_id, name, category, price, stock_quantity, item_code, hsn_code, unit) VALUES 
(1, 1, 'GERMAN', 'Textiles', 10.00, 10000, NULL, '5402', 'Pcs'),
(2, 1, 'OPPO RENO14 5G', 'Electronics', 5.00, 25000, NULL, '85171290', 'Pcs'),
(3, 1, 'YARN', 'Textiles', 12.00, 8000, NULL, '5403', 'Pcs'),
(4, 1, 'BLAWOSE', 'Apparel', 15.00, 150, NULL, NULL, 'Pcs'),
(5, 1, 'CLAIM LASS', 'Apparel', 20.00, 300, '5407', NULL, 'Pcs'),
(6, 1, 'COTTON YARN', 'Textiles', 8.00, 500, NULL, NULL, 'Pcs'),
(7, 1, 'CHORT SAREES', 'Apparel', 50.00, 80, NULL, NULL, 'Pcs'),
(8, 1, '3.00-18 ERIDE', 'Automotive', 100.00, 120, NULL, NULL, 'Pcs'),
(9, 1, 'VISCOS BOX', 'Packaging', 30.00, 150, NULL, '5403', 'Pcs'),
(10, 1, '150/48 POLYSTER YARN', 'Textiles', 18.00, 400, NULL, '5402', 'Pcs'),
(11, 1, 'MULTY EMBRODERY CONE', 'Accessories', 25.00, 250, NULL, '54024700', 'Pcs'),
(12, 1, 'JARI DORI', 'Accessories', 14.00, 180, NULL, '560500', 'Pcs'),
(13, 1, 'GARMAN', 'Textiles', 22.00, 90, NULL, '5402', 'Pcs')
ON DUPLICATE KEY UPDATE id=id;

-- Seed Sales Bills (assigned to admin user_id = 1)
INSERT INTO sales_bills (id, user_id, customer_id, bill_number, bill_date, due_days, due_date, apply_gst, discount_percent, discount_amount, gst_percent, gst_amount, taxable_amount, grand_total, paid_amount, status, remarks) VALUES 
(1, 1, 6, '1', '2026-04-04', 30, '2026-06-11' + INTERVAL 23 DAY, 1, 0.00, 0.00, 18.00, 11548.01, 64162.24, 75710.25, 0.00, 'UNPAID', 'Export shipment order.'),
(2, 1, 6, '2', '2026-04-10', 30, '2026-06-11' + INTERVAL 17 DAY, 1, 0.00, 0.00, 18.00, 4017.56, 22316.44, 26334.00, 0.00, 'UNPAID', 'Second delivery batch.'),
(3, 1, 5, '3', '2026-04-15', 30, '2026-06-11' + INTERVAL 12 DAY, 1, 0.00, 0.00, 12.00, 2433.01, 20275.08, 22708.09, 0.00, 'UNPAID', ' Ahmedabad local depot.'),
(4, 1, 4, '4', '2026-04-16', 30, '2026-06-11' + INTERVAL 11 DAY, 1, 0.00, 0.00, 18.00, 6822.90, 37905.00, 44727.90, 0.00, 'UNPAID', 'Cotton fabrics batch.'),
(5, 1, 4, '5', '2026-04-17', 30, '2026-06-11' + INTERVAL 10 DAY, 1, 0.00, 0.00, 12.00, 3631.50, 30262.50, 33894.00, 0.00, 'UNPAID', 'Embroidery sets.'),
(6, 1, 3, '6', '2026-04-18', 30, '2026-06-11' + INTERVAL 9 DAY, 1, 0.00, 0.00, 18.00, 1364.16, 7578.64, 8942.80, 0.00, 'UNPAID', 'Veera fashion design spool.'),
(7, 1, 2, '7', '2026-05-10', 30, '2026-06-11' + INTERVAL 13 DAY, 1, 0.00, 0.00, 18.00, 7175.39, 39863.29, 47038.68, 0.00, 'UNPAID', 'Saksham Boutique invoice.'),
(8, 1, 1, '8', '2026-05-11', 30, '2026-05-11' + INTERVAL 30 DAY, 1, 0.00, 0.00, 18.00, 8150.20, 45278.87, 53429.07, 53429.07, 'PAID', 'Sambhav silk threads.'),
(9, 1, 1, '9', '2026-05-13', 30, '2026-05-13' + INTERVAL 30 DAY, 1, 0.00, 0.00, 18.00, 7456.02, 41422.32, 48878.34, 48878.34, 'PAID', 'Sambhav Cotton roll.'),
(10, 1, 1, '42', '2026-05-15', 30, '2026-06-15', 1, 0.00, 0.00, 18.00, 13365.13, 74250.71, 87615.84, 87615.84, 'PAID', 'Sambhav textiles payment mockup.'),
(11, 1, 23, '48', '2026-05-16', 30, '2026-06-16', 1, 0.00, 0.00, 18.00, 2346.32, 13035.13, 15381.45, 15381.45, 'PAID', 'Ashapura Saree payment mockup.'),
(12, 1, 2, '49', '2026-05-17', 30, '2026-06-17', 1, 0.00, 0.00, 18.00, 9185.89, 51032.71, 60218.60, 60218.60, 'PAID', 'Saksham Boutique payment mockup.')
ON DUPLICATE KEY UPDATE id=id;

-- Seed Sales Bill Items
INSERT INTO sales_bill_items (sales_bill_id, product_id, product_name, item_code, hsn_code, quantity, unit, rate, amount) VALUES 
(1, 1, 'Premium Cotton Roll', 'TX-COT-01', '520811', 6416.22, 'Meters', 10.00, 64162.24),
(2, 1, 'Premium Cotton Roll', 'TX-COT-01', '520811', 2231.64, 'Meters', 10.00, 22316.44),
(3, 2, 'Designer Silk Thread', 'TX-SIL-02', '500400', 4055.01, 'Spools', 5.00, 20275.08),
(4, 1, 'Premium Cotton Roll', 'TX-COT-01', '520811', 3790.50, 'Meters', 10.00, 37905.00),
(5, 3, 'Embroidered Patch Set', 'TX-EMB-03', '581092', 2521.87, 'Sets', 12.00, 30262.50),
(6, 2, 'Designer Silk Thread', 'TX-SIL-02', '500400', 1515.73, 'Spools', 5.00, 7578.64),
(7, 1, 'Premium Cotton Roll', 'TX-COT-01', '520811', 3986.32, 'Meters', 10.00, 39863.29),
(8, 2, 'Designer Silk Thread', 'TX-SIL-02', '500400', 9055.77, 'Spools', 5.00, 45278.87),
(9, 1, 'Premium Cotton Roll', 'TX-COT-01', '520811', 4142.23, 'Meters', 10.00, 41422.32),
(10, 1, 'Premium Cotton Roll', 'TX-COT-01', '520811', 7425.07, 'Meters', 10.00, 74250.71),
(11, 1, 'Premium Cotton Roll', 'TX-COT-01', '520811', 1303.51, 'Meters', 10.00, 13035.13),
(12, 2, 'Designer Silk Thread', 'TX-SIL-02', '500400', 10206.54, 'Spools', 5.00, 51032.71)
ON DUPLICATE KEY UPDATE id=id;

-- Seed Purchase Bills matching mockup exactly! (assigned to admin user_id = 1)
INSERT INTO purchases (id, user_id, supplier_id, bill_number, bill_date, due_days, due_date, apply_gst, discount_percent, discount_amount, gst_percent, gst_amount, taxable_amount, amount, paid_amount, status, remarks) VALUES 
(1, 1, 11, 'rt388', '2025-12-31', 0, '2025-12-31', 1, 0.00, 0.00, 18.00, 317.20, 1762.20, 2079.40, 2079.40, 'PAID', 'Radhika Traders cotton yarn.'),
(2, 1, 12, '25/0301', '2025-12-25', 0, '2025-12-25', 1, 0.00, 0.00, 18.00, 1386.05, 7700.25, 9086.30, 9086.30, 'PAID', 'Khodiyar coning threads.'),
(3, 1, 13, 'mj321', '2025-12-02', 0, '2025-12-02', 1, 0.00, 0.00, 18.00, 6478.94, 35994.14, 42473.08, 42473.08, 'PAID', 'Madhav jarilon gold thread.'),
(4, 1, 14, '1000', '2025-12-02', 0, '2025-12-02', 1, 0.00, 0.00, 18.00, 805.61, 4475.63, 5281.24, 5281.24, 'PAID', 'Dharmi enterprise supplies.'),
(5, 1, 14, '995', '2025-12-01', 0, '2025-12-01', 1, 0.00, 0.00, 18.00, 784.33, 4357.41, 5141.74, 5141.74, 'PAID', 'Dharmi enterprise roll.'),
(6, 1, 11, 'rt338', '2025-11-30', 0, '2025-11-30', 1, 0.00, 0.00, 18.00, 539.01, 2994.48, 3533.49, 3533.49, 'PAID', 'Radhika Traders yarn.'),
(7, 1, 11, '0254', '2025-11-24', 0, '2025-11-24', 1, 0.00, 0.00, 18.00, 2479.08, 13772.65, 16251.73, 16251.73, 'PAID', 'Radhika Traders silk roll.'),
(8, 1, 13, 'MJ/282', '2025-11-07', 0, '2025-11-07', 1, 0.00, 0.00, 18.00, 3480.67, 19337.06, 22817.73, 22817.73, 'PAID', 'Madhav jarilon thread.'),
(9, 1, 15, '432', '2025-10-31', 0, '2025-10-31', 1, 0.00, 0.00, 18.00, 1905.70, 10587.20, 12492.90, 12492.90, 'PAID', 'JB enterprise boxes.'),
(10, 1, 11, 'RT302', '2025-10-31', 0, '2025-10-31', 1, 0.00, 0.00, 18.00, 584.16, 3245.33, 3829.49, 3829.49, 'PAID', 'Radhika Traders yarn.'),
(11, 1, 12, '25/0217', '2025-10-14', 0, '2025-10-14', 1, 0.00, 0.00, 18.00, 1293.84, 7188.01, 8481.85, 8481.85, 'PAID', 'Khodiyar coning spool.'),
(12, 1, 13, 'MJ/232', '2025-10-08', 0, '2025-10-08', 1, 0.00, 0.00, 18.00, 3562.74, 19793.01, 23355.75, 23355.75, 'PAID', 'Madhav jarilon thread.'),
(13, 1, 16, '90', '2025-10-04', 0, '2025-10-04', 1, 0.00, 0.00, 18.00, 80.08, 444.92, 525.00, 525.00, 'PAID', 'Tara textiles raw cotton.'),
(14, 1, 11, 'RT238', '2025-09-30', 0, '2025-09-30', 1, 0.00, 0.00, 18.00, 1108.74, 6159.68, 7268.42, 7268.42, 'PAID', 'Radhika Traders roll.'),
(15, 1, 22, '3', '2026-05-18', 0, '2026-05-18', 1, 0.00, 0.00, 18.00, 6911.39, 38396.61, 45308.00, 45308.00, 'PAID', 'Kavya Fashion purchase mockup.'),
(16, 1, 11, 'RT7', '2026-05-19', 0, '2026-05-19', 1, 0.00, 0.00, 18.00, 878.19, 4878.81, 5757.00, 5757.00, 'PAID', 'Radhika Traders purchase mockup.'),
(17, 1, 22, 'KAV-99', '2026-06-01', 30, '2026-07-01', 1, 0.00, 0.00, 18.00, 3813.56, 21186.44, 25000.00, 5000.00, 'PARTIAL', 'Kavya Fashion partial bill.'),
(18, 1, 11, 'RAD-101', '2026-06-02', 30, '2026-07-02', 1, 0.00, 0.00, 18.00, 1830.51, 10169.49, 12000.00, 0.00, 'UNPAID', 'Radhika Traders pending bill.')
ON DUPLICATE KEY UPDATE id=id;

-- Seed Purchase Bill Items
INSERT INTO purchase_bill_items (purchase_bill_id, product_id, product_name, item_code, hsn_code, quantity, unit, rate, amount, gst_percent, gst_amount) VALUES 
(1, 1, 'GERMAN', NULL, '5402', 176.22, 'Pcs', 10.00, 1762.20, 18.00, 317.20),
(2, 2, 'OPPO RENO14 5G', NULL, '85171290', 1540.05, 'Pcs', 5.00, 7700.25, 18.00, 1386.05),
(3, 3, 'YARN', NULL, '5403', 2999.51, 'Pcs', 12.00, 35994.14, 18.00, 6478.94),
(4, 1, 'GERMAN', NULL, '5402', 447.56, 'Pcs', 10.00, 4475.63, 18.00, 805.61),
(5, 1, 'GERMAN', NULL, '5402', 435.74, 'Pcs', 10.00, 4357.41, 18.00, 784.33),
(6, 1, 'GERMAN', NULL, '5402', 299.45, 'Pcs', 10.00, 2994.48, 18.00, 539.01),
(7, 1, 'GERMAN', NULL, '5402', 1377.27, 'Pcs', 10.00, 13772.65, 18.00, 2479.08),
(8, 3, 'YARN', NULL, '5403', 1611.42, 'Pcs', 12.00, 19337.06, 18.00, 3480.67),
(9, 1, 'GERMAN', NULL, '5402', 1058.72, 'Pcs', 10.00, 10587.20, 18.00, 1905.70),
(10, 1, 'GERMAN', NULL, '5402', 324.53, 'Pcs', 10.00, 3829.49, 18.00, 584.16),
(11, 2, 'OPPO RENO14 5G', NULL, '85171290', 1437.60, 'Pcs', 5.00, 7188.01, 18.00, 1293.84),
(12, 3, 'YARN', NULL, '5403', 1649.42, 'Pcs', 12.00, 19793.01, 18.00, 3562.74),
(13, 1, 'GERMAN', NULL, '5402', 44.49, 'Pcs', 10.00, 444.92, 18.00, 80.08),
(14, 1, 'GERMAN', NULL, '5402', 615.97, 'Pcs', 10.00, 6159.68, 18.00, 1108.74),
(15, 1, 'GERMAN', NULL, '5402', 3839.66, 'Pcs', 10.00, 38396.61, 18.00, 6911.39),
(16, 1, 'GERMAN', NULL, '5402', 487.88, 'Pcs', 10.00, 4878.81, 18.00, 878.19),
(17, 1, 'GERMAN', NULL, '5402', 2118.64, 'Pcs', 10.00, 21186.44, 18.00, 3813.56),
(18, 1, 'GERMAN', NULL, '5402', 1016.95, 'Pcs', 10.00, 10169.49, 18.00, 1830.51)
ON DUPLICATE KEY UPDATE id=id;

-- Seed Payments (assigned to admin user_id = 1)
INSERT INTO payments (sales_bill_id, purchase_bill_id, payment_date, payment_mode, reference_number, amount, settlement_amount, notes, user_id) VALUES 
(8, NULL, '2026-05-12', 'Bank Transfer', 'TXN-880921', 53429.07, 53429.07, 'Full bill clearing.', 1),
(9, NULL, '2026-05-14', 'UPI', 'UPI-990218', 48878.34, 48878.34, 'Full bill clearing.', 1),
(10, NULL, '2026-05-15', 'Cheque', '2025-26-1', 87615.84, 87615.84, 'Sambhav textiles cheque.', 1),
(11, NULL, '2026-05-16', 'Cheque', '2025-26-1', 15381.45, 15381.45, 'Ashapura Saree cheque.', 1),
(12, NULL, '2026-05-17', 'Cheque', '2025-26-1', 60218.60, 60218.60, 'Saksham Boutique cheque.', 1),
(NULL, 15, '2026-05-18', 'Cheque', '2024-25-31', 45308.00, 45308.00, 'Kavya Fashion cheque.', 1),
(NULL, 16, '2026-05-19', 'Cheque', '2025-26-4', 5757.00, 5757.00, 'Radhika Traders cheque.', 1),
(NULL, 17, '2026-06-01', 'Bank Transfer', 'TXN-KAV-99', 5000.00, 5000.00, 'Partial pay.', 1),
(NULL, 1, '2025-12-31', 'Bank Transfer', 'PUR-TXN-101', 2079.40, 2079.40, 'Radhika purchase pay.', 1),
(NULL, 2, '2025-12-25', 'UPI', 'PUR-TXN-102', 9086.30, 9086.30, 'Khodiyar purchase pay.', 1),
(NULL, 3, '2025-12-02', 'Bank Transfer', 'PUR-TXN-103', 42473.08, 42473.08, 'Madhav purchase pay.', 1),
(NULL, 4, '2025-12-02', 'Cash', 'PUR-TXN-104', 5281.24, 5281.24, 'Dharmi purchase pay.', 1),
(NULL, 5, '2025-12-01', 'Cash', 'PUR-TXN-105', 5141.74, 5141.74, 'Dharmi purchase pay.', 1),
(NULL, 6, '2025-11-30', 'Bank Transfer', 'PUR-TXN-106', 3533.49, 3533.49, 'Radhika purchase pay.', 1),
(NULL, 7, '2025-11-24', 'UPI', 'PUR-TXN-107', 16251.73, 16251.73, 'Radhika purchase pay.', 1),
(NULL, 8, '2025-11-07', 'Bank Transfer', 'PUR-TXN-108', 22817.73, 22817.73, 'Madhav purchase pay.', 1),
(NULL, 9, '2025-10-31', 'UPI', 'PUR-TXN-109', 12492.90, 12492.90, 'JB purchase pay.', 1),
(NULL, 10, '2025-10-31', 'Cash', 'PUR-TXN-110', 3829.49, 3829.49, 'Radhika purchase pay.', 1),
(NULL, 11, '2025-10-14', 'UPI', 'PUR-TXN-111', 8481.85, 8481.85, 'Khodiyar purchase pay.', 1),
(NULL, 12, '2025-10-08', 'Bank Transfer', 'PUR-TXN-112', 23355.75, 23355.75, 'Madhav purchase pay.', 1),
(NULL, 13, '2025-10-04', 'Cash', 'PUR-TXN-113', 525.00, 525.00, 'Tara purchase pay.', 1),
(NULL, 14, '2025-09-30', 'Bank Transfer', 'PUR-TXN-114', 7268.42, 7268.42, 'Radhika purchase pay.', 1)
ON DUPLICATE KEY UPDATE id=id;

-- Seed general ledger records (assigned to admin user_id = 1)
INSERT INTO transactions (reference_id, type, amount, description, date, user_id) VALUES 
(1, 'Sale', 75710.25, 'Sales Bill 1 (AWARD INTERNATIONAL)', '2026-04-04', 1),
(2, 'Sale', 26334.00, 'Sales Bill 2 (AWARD INTERNATIONAL)', '2026-04-10', 1),
(3, 'Sale', 22708.09, 'Sales Bill 3 (AMICHAND CREATION)', '2026-04-15', 1),
(4, 'Sale', 44727.90, 'Sales Bill 4 (PRIYANKA FASHION)', '2026-04-16', 1),
(5, 'Sale', 33894.00, 'Sales Bill 5 (PRIYANKA FASHION)', '2026-04-17', 1),
(6, 'Sale', 6942.60, 'Sales Bill 6 (veera fashion)', '2026-04-18', 1),
(7, 'Sale', 47038.68, 'Sales Bill 7 (SAKSHAM BOUTIQUE)', '2026-05-10', 1),
(8, 'Sale', 53429.07, 'Sales Bill 8 (SAMBHAV TEXTILES)', '2026-05-11', 1),
(9, 'Sale', 48878.34, 'Sales Bill 9 (SAMBHAV TEXTILES)', '2026-05-13', 1),
(10, 'Sale', 87615.84, 'Sales Bill 42 (SAMBHAV TEXTILES)', '2026-05-15', 1),
(11, 'Sale', 15381.45, 'Sales Bill 48 (ASHAPURA SAREE)', '2026-05-16', 1),
(12, 'Sale', 60218.60, 'Sales Bill 49 (SAKSHAM BOUTIQUE)', '2026-05-17', 1),
(1, 'Purchase', 2079.40, 'Purchase Bill rt388 (RADHIKA TRADERS)', '2025-12-31', 1),
(2, 'Purchase', 9086.30, 'Purchase Bill 25/0301 (SHREE KHODIYAR CONING)', '2025-12-25', 1),
(3, 'Purchase', 42473.08, 'Purchase Bill mj321 (MADHAV JARILON)', '2025-12-02', 1),
(4, 'Purchase', 5281.24, 'Purchase Bill 1000 (DHARMI ENTERPRISE)', '2025-12-02', 1),
(5, 'Purchase', 5141.74, 'Purchase Bill 995 (DHARMI ENTERPRISE)', '2025-12-01', 1),
(6, 'Purchase', 3533.49, 'Purchase Bill rt338 (RADHIKA TRADERS)', '2025-11-30', 1),
(7, 'Purchase', 16251.73, 'Purchase Bill 0254 (RADHIKA TRADERS)', '2025-11-24', 1),
(8, 'Purchase', 22817.73, 'Purchase Bill MJ/282 (MADHAV JARILON)', '2025-11-07', 1),
(9, 'Purchase', 12492.90, 'Purchase Bill 432 (J B ENTERPRISE)', '2025-10-31', 1),
(10, 'Purchase', 3829.49, 'Purchase Bill RT302 (RADHIKA TRADERS)', '2025-10-31', 1),
(11, 'Purchase', 8481.85, 'Purchase Bill 25/0217 (SHREE KHODIYAR CONING)', '2025-10-14', 1),
(12, 'Purchase', 23355.75, 'Purchase Bill MJ/232 (MADHAV JARILON)', '2025-10-08', 1),
(13, 'Purchase', 525.00, 'Purchase Bill 90 (TARA TEXTILES)', '2025-10-04', 1),
(14, 'Purchase', 7268.42, 'Purchase Bill RT238 (RADHIKA TRADERS)', '2025-09-30', 1),
(15, 'Purchase', 45308.00, 'Purchase Bill 3 (KAVYA FASHION)', '2026-05-18', 1),
(16, 'Purchase', 5757.00, 'Purchase Bill RT7 (RADHIKA TRADERS)', '2026-05-19', 1),
(17, 'Purchase', 25000.00, 'Purchase Bill KAV-99 (KAVYA FASHION)', '2026-06-01', 1),
(18, 'Purchase', 12000.00, 'Purchase Bill RAD-101 (RADHIKA TRADERS)', '2026-06-02', 1),
(1, 'Payment', 53429.07, 'Payment of ₹ 53,429.07 received via Bank Transfer for Invoice 8', '2026-05-12', 1),
(2, 'Payment', 48878.34, 'Payment of ₹ 48,878.34 received via UPI for Invoice 9', '2026-05-14', 1),
(3, 'Payment', 87615.84, 'Payment of ₹ 87,615.84 received via Cheque (Ref: 2025-26-1) for Invoice 42', '2026-05-15', 1),
(4, 'Payment', 15381.45, 'Payment of ₹ 15,381.45 received via Cheque (Ref: 2025-26-1) for Invoice 48', '2026-05-16', 1),
(5, 'Payment', 60218.60, 'Payment of ₹ 60,218.60 received via Cheque (Ref: 2025-26-1) for Invoice 49', '2026-05-17', 1),
(6, 'Payment', 45308.00, 'Payment of ₹ 45,308.00 paid via Cheque (Ref: 2024-25-31) for Purchase Bill 3', '2026-05-18', 1),
(7, 'Payment', 5757.00, 'Payment of ₹ 5,757.00 paid via Cheque (Ref: 2025-26-4) for Purchase Bill RT7', '2026-05-19', 1),
(8, 'Payment', 5000.00, 'Payment of ₹ 5,000.00 paid via Bank Transfer (Ref: TXN-KAV-99) for Purchase Bill KAV-99', '2026-06-01', 1)
ON DUPLICATE KEY UPDATE id=id;

-- Seed Expenses (assigned to admin user_id = 1)
INSERT INTO expenses (id, user_id, expense_date, category, supplier_id, amount, paid_amount, status, notes) VALUES
(1, 1, '2026-01-02', 'hostel fee', 17, 51000.00, 51000.00, 'PAID', 'Mockup hostel fee'),
(2, 1, '2026-01-02', 'collej fees', 17, 77500.00, 77500.00, 'PAID', 'Mockup college fees'),
(3, 1, '2025-12-01', 'Raw Material', 18, 3500.00, 3500.00, 'PAID', 'Mockup raw material expense'),
(4, 1, '2025-12-01', 'Salary', 18, 136201.00, 136201.00, 'PAID', 'Mockup salary payout'),
(5, 1, '2025-12-01', 'dhaga katig', 19, 16150.00, 16150.00, 'PAID', 'Mockup dhaga cutting charges'),
(6, 1, '2025-12-01', 'lon instolment', 20, 21260.00, 21260.00, 'PAID', 'Mockup loan installment'),
(7, 1, '2025-11-01', 'Salary', 18, 86599.00, 86599.00, 'PAID', 'Mockup salary payout'),
(8, 1, '2025-11-01', 'Rent', 21, 24000.00, 24000.00, 'PAID', 'Mockup office rent'),
(9, 1, '2025-11-01', 'Service', 18, 25500.00, 25500.00, 'PAID', 'Mockup service charges')
ON DUPLICATE KEY UPDATE id=id;

-- Seed Expense Items
INSERT INTO expense_items (id, expense_id, description, quantity, rate, amount) VALUES
(1, 1, 'Hostel accommodation fee payment', 1, 51000.00, 51000.00),
(2, 2, 'College tuition fees payment', 1, 77500.00, 77500.00),
(3, 3, 'Yarn raw materials batch', 1, 3500.00, 3500.00),
(4, 4, 'Monthly staff salaries payment', 1, 136201.00, 136201.00),
(5, 5, 'Thread cutting work charges', 1, 16150.00, 16150.00),
(6, 6, 'Monthly bank loan installment', 1, 21260.00, 21260.00),
(7, 7, 'Monthly staff salaries payment', 1, 86599.00, 86599.00),
(8, 8, 'Office building monthly rent', 1, 24000.00, 24000.00),
(9, 9, 'Maintenance and support service', 1, 25500.00, 25500.00)
ON DUPLICATE KEY UPDATE id=id;

-- Seed Expense Ledger records (assigned to admin user_id = 1)
INSERT INTO transactions (reference_id, type, amount, description, date, user_id) VALUES
(1, 'Expense', 51000.00, 'Expense: hostel fee (abhi kyada)', '2026-01-02', 1),
(2, 'Expense', 77500.00, 'Expense: collej fees (abhi kyada)', '2026-01-02', 1),
(3, 'Expense', 3500.00, 'Expense: Raw Material (i shree khodiyar tex)', '2025-12-01', 1),
(4, 'Expense', 136201.00, 'Expense: Salary (I shree khodiyar tex)', '2025-12-01', 1),
(5, 'Expense', 16150.00, 'Expense: dhaga katig (ravi)', '2025-12-01', 1),
(6, 'Expense', 21260.00, 'Expense: lon instolment (chetanbhai)', '2025-12-01', 1),
(7, 'Expense', 86599.00, 'Expense: Salary (I shree khodiyar tex)', '2025-11-01', 1),
(8, 'Expense', 24000.00, 'Expense: Rent (jigneshbhai)', '2025-11-01', 1),
(9, 'Expense', 25500.00, 'Expense: Service (i shree khodiyar tex)', '2025-11-01', 1)
ON DUPLICATE KEY UPDATE id=id;
