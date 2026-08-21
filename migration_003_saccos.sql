-- ============================================================
-- migration_003_saccos.sql
--
-- Adds the SACCOS module: member registration, a share-capital
-- ledger, a savings ledger, and dividend declarations/payouts.
-- This is intentionally separate from the existing microfinance
-- (loans) tables — SACCOS is member-owned savings/shares, loans
-- is client lending. They're related concepts but not the same
-- data model, so they get their own tables rather than being
-- bolted onto `clients`/`loans`.
--
-- ============================================================

-- ---- 1. Members ----------------------------------------------
CREATE TABLE IF NOT EXISTS sacco_members (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id      INT UNSIGNED NOT NULL,
    member_number  VARCHAR(50)  DEFAULT NULL,
    full_name      VARCHAR(150) NOT NULL,
    phone          VARCHAR(30)  DEFAULT NULL,
    national_id    VARCHAR(50)  DEFAULT NULL,
    join_date      DATE NOT NULL,
    status         ENUM('active','inactive','exited') NOT NULL DEFAULT 'active',
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sacco_members_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- 2. Share capital ledger -----------------------------------
-- Running share balance = SUM(purchase.shares_count) - SUM(withdrawal.shares_count)
CREATE TABLE IF NOT EXISTS share_transactions (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         INT UNSIGNED NOT NULL,
    member_id         INT UNSIGNED NOT NULL,
    type              ENUM('purchase','withdrawal') NOT NULL,
    shares_count      INT UNSIGNED NOT NULL,
    amount            DECIMAL(15,2) NOT NULL,
    transaction_date  DATE NOT NULL,
    notes             VARCHAR(255) DEFAULT NULL,
    recorded_by       INT UNSIGNED DEFAULT NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_share_tx_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_share_tx_member FOREIGN KEY (member_id) REFERENCES sacco_members(id) ON DELETE CASCADE,
    CONSTRAINT fk_share_tx_user   FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- 3. Savings ledger -------------------------------------
-- Running savings balance = SUM(deposit.amount) - SUM(withdrawal.amount)
CREATE TABLE IF NOT EXISTS savings_transactions (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         INT UNSIGNED NOT NULL,
    member_id         INT UNSIGNED NOT NULL,
    type              ENUM('deposit','withdrawal') NOT NULL,
    amount            DECIMAL(15,2) NOT NULL,
    transaction_date  DATE NOT NULL,
    notes             VARCHAR(255) DEFAULT NULL,
    recorded_by       INT UNSIGNED DEFAULT NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_savings_tx_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_savings_tx_member FOREIGN KEY (member_id) REFERENCES sacco_members(id) ON DELETE CASCADE,
    CONSTRAINT fk_savings_tx_user   FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- 4. Dividend declarations --------------------------------
-- A tenant declares one dividend per fiscal year, at a % rate
-- applied against either total shares or total savings balance
-- (SACCOS bylaws vary on which basis they use).
CREATE TABLE IF NOT EXISTS dividend_declarations (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id          INT UNSIGNED NOT NULL,
    fiscal_year        VARCHAR(20) NOT NULL,
    rate_percent       DECIMAL(5,2) NOT NULL,
    basis              ENUM('shares','savings') NOT NULL DEFAULT 'shares',
    declared_date      DATE NOT NULL,
    total_payout_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    declared_by        INT UNSIGNED DEFAULT NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_dividend_decl_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_dividend_decl_user   FOREIGN KEY (declared_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- 5. Dividend payouts (one row per member per declaration) --
CREATE TABLE IF NOT EXISTS dividend_payouts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       INT UNSIGNED NOT NULL,
    declaration_id  INT UNSIGNED NOT NULL,
    member_id       INT UNSIGNED NOT NULL,
    basis_amount    DECIMAL(15,2) NOT NULL,
    payout_amount   DECIMAL(15,2) NOT NULL,
    status          ENUM('pending','paid') NOT NULL DEFAULT 'pending',
    paid_date       DATE DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_declaration_member (declaration_id, member_id),
    CONSTRAINT fk_payout_tenant      FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_payout_declaration FOREIGN KEY (declaration_id) REFERENCES dividend_declarations(id) ON DELETE CASCADE,
    CONSTRAINT fk_payout_member      FOREIGN KEY (member_id) REFERENCES sacco_members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- 6. New chart-of-accounts entries for every existing tenant --
-- SACCOS needs a place to post share capital and savings deposits
-- distinct from Cash/Bank. Every tenant created by setup-org.php
-- only got 'Cash on Hand' + 'Bank Account' — add the three SACCOS
-- accounts to every tenant that doesn't already have them.
INSERT INTO accounts (tenant_id, account_name, account_type, balance)
SELECT t.id, 'Member Shares Capital', 'Equity', 0.00
FROM tenants t
WHERE NOT EXISTS (
    SELECT 1 FROM accounts a WHERE a.tenant_id = t.id AND a.account_name = 'Member Shares Capital'
);

INSERT INTO accounts (tenant_id, account_name, account_type, balance)
SELECT t.id, 'Member Savings Deposits', 'Liability', 0.00
FROM tenants t
WHERE NOT EXISTS (
    SELECT 1 FROM accounts a WHERE a.tenant_id = t.id AND a.account_name = 'Member Savings Deposits'
);

INSERT INTO accounts (tenant_id, account_name, account_type, balance)
SELECT t.id, 'Dividends Paid', 'Expense', 0.00
FROM tenants t
WHERE NOT EXISTS (
    SELECT 1 FROM accounts a WHERE a.tenant_id = t.id AND a.account_name = 'Dividends Paid'
);
