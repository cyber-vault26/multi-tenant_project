
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


CREATE TABLE IF NOT EXISTS tenants (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(150) NOT NULL,
    business_type    VARCHAR(100) DEFAULT NULL,
    category         VARCHAR(100) DEFAULT NULL,
    currency         VARCHAR(10)  NOT NULL DEFAULT 'TZS',
    timezone         VARCHAR(64)  DEFAULT 'Africa/Nairobi',
    address          VARCHAR(255) DEFAULT NULL,
    business_email   VARCHAR(150) DEFAULT NULL,
    phone            VARCHAR(30)  DEFAULT NULL,
    brand_color      VARCHAR(20)  DEFAULT NULL,
    modules_enabled  VARCHAR(255) DEFAULT NULL,
    logo_path        VARCHAR(255) DEFAULT NULL,
    status           ENUM('active','suspended') NOT NULL DEFAULT 'active',
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS users (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name      VARCHAR(150) NOT NULL,
    email          VARCHAR(150) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    tenant_id      INT UNSIGNED NOT NULL DEFAULT 1,
    role           ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS branches (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id    INT UNSIGNED NOT NULL,
    branch_name  VARCHAR(150) NOT NULL,
    branch_code  VARCHAR(50)  DEFAULT NULL,
    city         VARCHAR(100) DEFAULT NULL,
    region       VARCHAR(100) DEFAULT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_branches_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS accounts (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id     INT UNSIGNED NOT NULL,
    account_name  VARCHAR(100) NOT NULL,
    account_type  ENUM('Asset','Liability','Equity','Income','Expense') NOT NULL,
    balance       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_accounts_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS clients (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id     INT UNSIGNED NOT NULL,
    full_name     VARCHAR(150) NOT NULL,
    phone_number  VARCHAR(30)  DEFAULT NULL,
    id_number     VARCHAR(50)  DEFAULT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_clients_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS products (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       INT UNSIGNED NOT NULL,
    name            VARCHAR(150) NOT NULL,
    sku             VARCHAR(100) DEFAULT NULL,
    purchase_price  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    sale_price      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    stock_quantity  INT NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS sales (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id     INT UNSIGNED NOT NULL,
    product_id    INT UNSIGNED NOT NULL,
    quantity      INT NOT NULL,
    total_amount  DECIMAL(15,2) NOT NULL,
    sale_date     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_tenant  FOREIGN KEY (tenant_id)  REFERENCES tenants(id)  ON DELETE CASCADE,
    CONSTRAINT fk_sales_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS loans (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id        INT UNSIGNED NOT NULL,
    client_id        INT UNSIGNED NOT NULL,
    amount           DECIMAL(15,2) NOT NULL,
    interest_rate    DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    duration_months  INT NOT NULL DEFAULT 1,
    status           ENUM('active','completed','defaulted') NOT NULL DEFAULT 'active',
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_loans_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_loans_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS loan_schedules (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         INT UNSIGNED NOT NULL,
    loan_id           INT UNSIGNED NOT NULL,
    installment_no    INT NOT NULL,
    due_date          DATE NOT NULL,
    principal_amount  DECIMAL(15,2) NOT NULL,
    interest_amount   DECIMAL(15,2) NOT NULL,
    total_due         DECIMAL(15,2) NOT NULL,
    status            ENUM('pending','paid') NOT NULL DEFAULT 'pending',
    CONSTRAINT fk_schedule_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_schedule_loan   FOREIGN KEY (loan_id)   REFERENCES loans(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS repayments (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id      INT UNSIGNED NOT NULL,
    loan_id        INT UNSIGNED NOT NULL,
    amount_paid    DECIMAL(15,2) NOT NULL,
    collected_by   INT UNSIGNED DEFAULT NULL,
    paid_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_repay_tenant FOREIGN KEY (tenant_id)    REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_repay_loan   FOREIGN KEY (loan_id)      REFERENCES loans(id)   ON DELETE CASCADE,
    CONSTRAINT fk_repay_user   FOREIGN KEY (collected_by) REFERENCES users(id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS journal_entries (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id    INT UNSIGNED NOT NULL,
    account_id   INT UNSIGNED NOT NULL,
    description  VARCHAR(255) DEFAULT NULL,
    debit        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    credit       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_journal_tenant  FOREIGN KEY (tenant_id)  REFERENCES tenants(id)  ON DELETE CASCADE,
    CONSTRAINT fk_journal_account FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS expenses (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id     INT UNSIGNED NOT NULL,
    account_id    INT UNSIGNED NOT NULL,
    amount        DECIMAL(15,2) NOT NULL,
    description   VARCHAR(255) DEFAULT NULL,
    expense_date  DATE NOT NULL,
    created_by    INT UNSIGNED DEFAULT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_expenses_tenant  FOREIGN KEY (tenant_id)  REFERENCES tenants(id)  ON DELETE CASCADE,
    CONSTRAINT fk_expenses_account FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_expenses_user    FOREIGN KEY (created_by) REFERENCES users(id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS audit_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED DEFAULT NULL,
    action      VARCHAR(150) NOT NULL,
    details     VARCHAR(500) DEFAULT NULL,
    ip_address  VARCHAR(45)  DEFAULT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_audit_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS password_resets (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(150) NOT NULL,
    token       VARCHAR(255) NOT NULL,
    expires_at  DATETIME NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_resets_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Seed data: platform.php treats tenant id 1 as the "sysAdmin" /
-- platform-owner tenant, and new self-registrations default
-- new users to tenant_id = 1 until they run setup-org.php.
-- Without this row, first signup (auth_process.php) will fail
-- its foreign key check.
-- ------------------------------------------------------------
INSERT INTO tenants (id, name, currency, status)
VALUES (1, 'sysAdmin', 'TZS', 'active');
