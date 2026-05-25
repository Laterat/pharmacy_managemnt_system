CREATE DATABASE IF NOT EXISTS pharmacy_inventory;
USE pharmacy_inventory;

DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS medicines;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(120) NOT NULL,
    phone VARCHAR(30),
    address VARCHAR(180),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_name VARCHAR(120) NOT NULL,
    generic_name VARCHAR(120),
    category_id INT NOT NULL,
    batch_no VARCHAR(60) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    buying_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    selling_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    supplier_id INT,
    manufacture_date DATE,
    expiry_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON UPDATE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT NOT NULL,
    quantity_sold INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    sold_by INT NOT NULL,
    sold_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON UPDATE CASCADE,
    FOREIGN KEY (sold_by) REFERENCES users(id) ON UPDATE CASCADE
);

INSERT INTO users (fullname, username, password, role) VALUES
('System Administrator', 'admin', '$2y$12$wU5QDAf7U4.ridfHzwTcouTaYife0BxMJzHOp.hQEaWlLd/Ta311q', 'admin'),
('Pharmacy Staff', 'staff', '$2y$12$wU5QDAf7U4.ridfHzwTcouTaYife0BxMJzHOp.hQEaWlLd/Ta311q', 'staff');

INSERT INTO categories (category_name) VALUES
('Tablets'), ('Syrups'), ('Capsules'), ('Injections'), ('Creams');

INSERT INTO suppliers (supplier_name, phone, address) VALUES
('MediCare Suppliers', '+254700111222', 'Nairobi'),
('HealthPlus Distributors', '+254711222333', 'Mombasa'),
('Wellness Pharma', '+254722333444', 'Kisumu');

INSERT INTO medicines
(medicine_name, generic_name, category_id, batch_no, quantity, buying_price, selling_price, supplier_id, manufacture_date, expiry_date)
VALUES
('Panadol Extra', 'Paracetamol + Caffeine', 1, 'TAB-1001', 45, 4.50, 8.00, 1, '2025-01-01', DATE_ADD(CURDATE(), INTERVAL 180 DAY)),
('Amoxil 500mg', 'Amoxicillin', 3, 'CAP-2001', 8, 12.00, 20.00, 2, '2025-03-12', DATE_ADD(CURDATE(), INTERVAL 20 DAY)),
('Benylin Cough Syrup', 'Diphenhydramine', 2, 'SYR-5001', 18, 180.00, 260.00, 1, '2024-10-01', DATE_ADD(CURDATE(), INTERVAL 45 DAY)),
('Hydrocortisone Cream', 'Hydrocortisone', 5, 'CRM-7001', 6, 90.00, 140.00, 3, '2024-08-05', DATE_SUB(CURDATE(), INTERVAL 7 DAY)),
('Ceftriaxone Injection', 'Ceftriaxone', 4, 'INJ-9001', 24, 150.00, 230.00, 2, '2025-02-01', DATE_ADD(CURDATE(), INTERVAL 90 DAY));
