# 🗄️ SME System Database Schema & Relationships

## Database Successfully Created
- **Database Name:** `sme_system`
- **Total Tables:** 5
- **Foreign Key Relationships:** 5
- **Database Engine:** InnoDB (ACID Compliant)
- **Character Set:** utf8mb4

---

## Table Structure

### 1. **USERS** Table (Customers & Admins)
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    role ENUM('admin','customer') DEFAULT 'customer',
    phone VARCHAR(50),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active','inactive') DEFAULT 'active'
);
```
**Purpose:** Store user account information  
**Relations:** Connects to Orders and Payments

---

### 2. **CATEGORIES** Table
```sql
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active','inactive') DEFAULT 'active'
);
```
**Purpose:** Organize products by category  
**Relations:** Referenced by Products table

---

### 3. **PRODUCTS** Table
```sql
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity INT NOT NULL DEFAULT 0,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active','inactive') DEFAULT 'active',
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);
```
**Purpose:** Store product information  
**Relations:** Connected to Categories and Orders

---

### 4. **ORDERS** Table
```sql
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    order_date DATE NOT NULL,
    status ENUM('pending','processing','completed','cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```
**Purpose:** Track customer orders  
**Relations:** Links users to products, references payments

---

### 5. **PAYMENTS** Table
```sql
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','credit_card','debit_card','bank_transfer','online_gateway') DEFAULT 'cash',
    payment_status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100) UNIQUE,
    notes TEXT,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
);
```
**Purpose:** Track payment records for orders  
**Relations:** Connected to Orders and Users

---

## Relationship Diagram (Text Format)

```
┌─────────────────┐
│     USERS       │
│   (id:PK)       │
└────────┬────────┘
         │
      1:∞│ (customer_id)
         │
    ┌────▼──────────────┐
    │     ORDERS        │
    │   (id:PK)         │
    │   customer_id:FK  │
    │   product_id:FK   │
    └────┬──────────┬───┘
         │           │
      1:∞│ (order_id) │ 1:∞ (product_id)
         │           │
    ┌────▼────┐    ┌─▼──────────┐
    │ PAYMENTS │    │  PRODUCTS  │
    │(id:PK)  │    │  (id:PK)   │
    └─────────┘    │category_id │
                   └─────┬──────┘
                         │ 1:∞ (category_id)
                         │
                   ┌─────▼─────────┐
                   │  CATEGORIES   │
                   │    (id:PK)    │
                   └───────────────┘
```

---

## Relationships Summary

| From Table | To Table | Type | Foreign Key | Description |
|-----------|---------|------|------------|-------------|
| **CATEGORIES** | **PRODUCTS** | 1:∞ | category_id | One category has many products |
| **PRODUCTS** | **ORDERS** | 1:∞ | product_id | One product can be in many orders |
| **USERS** | **ORDERS** | 1:∞ | customer_id | One user can place many orders |
| **ORDERS** | **PAYMENTS** | 1:∞ | order_id | One order can have many payments |
| **USERS** | **PAYMENTS** | 1:∞ | customer_id | One user can make many payments |

---

## Referential Integrity Features

**ON DELETE CASCADE:** When a user or product is deleted, associated orders are automatically deleted  
**ON DELETE SET NULL:** When a category is deleted, products retain their data but category reference is cleared  
**UNIQUE Constraints:** Prevents duplicate usernames, emails, and transaction IDs  
**ACID Compliance:** InnoDB engine ensures data consistency

---

## Default Admin Account

After setup, the following default admin account is created automatically:

```
Username: admin
Password: admin123
Role: admin
Email: admin@example.com
```

---

## Key Features

1. **Multi-tier relationships:** All tables are interconnected
2. **Referential Integrity:** Foreign keys ensure data consistency
3. **Cascading Operations:** Automatic deletion of related records
4. **Timestamps:** Track creation and updates
5. **Status Management:** Active/inactive status for categories and products
6. **Payment Tracking:** Complete payment history with multiple payment methods
7. **Order Management:** Full order lifecycle from pending to completed

---

##  Example Data Flow

1. **Customer Registration** → User created in `USERS` table
2. **Add Product** → Product added to `PRODUCTS` table with category reference
3. **Place Order** → Order created linking customer and product in `ORDERS` table
4. **Process Payment** → Payment recorded in `PAYMENTS` table referencing order and customer
5. **Track Status** → All statuses updated through enum fields

---

##  Database Files

- **Schema Setup:** `database_schema.php` - Creates all tables and relationships
- **Auto Generated:** `setup.php` - Original setup (updated with new schema)

---

## Access Your Database

### Via PHPMyAdmin
1. Go to `http://localhost/phpmyadmin`
2. Database: `sme_system`
3. All 5 tables with relationships

### Via Application
1. Visit `http://localhost/smepro/database_schema.php`
2. View complete schema and relationship diagrams
3. Returns HTML visual representation

---

**Created:** February 10, 2026  
**Status:** Active and Ready to Use
