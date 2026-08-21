-- ============================================================
-- migration_004_ecommerce.sql
--
-- Adds the E-commerce module: a public storefront per tenant
-- (no login required for customers), a session-based cart,
-- guest checkout, and admin order management.
--
-- ============================================================

-- ---- 1. Storefront settings on tenants -----------------------
ALTER TABLE tenants
    ADD COLUMN store_slug    VARCHAR(100) DEFAULT NULL AFTER modules_enabled,
    ADD COLUMN store_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER store_slug;

ALTER TABLE tenants ADD UNIQUE KEY uniq_store_slug (store_slug);

-- Auto-generate a slug for existing tenants from their name so
-- the storefront is immediately reachable without a manual step.
-- (Basic slugify: lowercase, spaces/apostrophes stripped to dashes.
-- Admins can override this later in Settings.)
UPDATE tenants
SET store_slug = LOWER(
    REPLACE(REPLACE(REPLACE(TRIM(name), ' ', '-'), '''', ''), '.', '')
)
WHERE store_slug IS NULL;

-- ---- 2. Storefront-facing product fields ----------------------
ALTER TABLE products
    ADD COLUMN description   TEXT DEFAULT NULL AFTER name,
    ADD COLUMN image_url     VARCHAR(500) DEFAULT NULL AFTER description,
    ADD COLUMN is_published  TINYINT(1) NOT NULL DEFAULT 0 AFTER stock_quantity;

-- ---- 3. Online orders ------------------------------------------
CREATE TABLE IF NOT EXISTS online_orders (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         INT UNSIGNED NOT NULL,
    order_number      VARCHAR(30) NOT NULL,
    customer_name     VARCHAR(150) NOT NULL,
    customer_phone    VARCHAR(30) NOT NULL,
    customer_email    VARCHAR(150) DEFAULT NULL,
    delivery_address  VARCHAR(255) DEFAULT NULL,
    status            ENUM('pending','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    payment_status    ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
    total_amount      DECIMAL(15,2) NOT NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_order_number (order_number),
    CONSTRAINT fk_online_orders_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- 4. Order line items ----------------------------------------
-- product_name_snapshot preserves what the item was called at
-- purchase time, since products can be renamed/deleted later.
CREATE TABLE IF NOT EXISTS online_order_items (
    id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id               INT UNSIGNED NOT NULL,
    product_id             INT UNSIGNED DEFAULT NULL,
    product_name_snapshot  VARCHAR(150) NOT NULL,
    quantity               INT UNSIGNED NOT NULL,
    unit_price             DECIMAL(15,2) NOT NULL,
    subtotal               DECIMAL(15,2) NOT NULL,
    CONSTRAINT fk_order_items_order   FOREIGN KEY (order_id) REFERENCES online_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- 5. New chart-of-accounts entry for every existing tenant --
-- Online sales get their own Income account so paid orders are
-- posted as real double-entry (Debit Cash, Credit Online Sales),
-- rather than reusing POS's simpler single-sided posting.
INSERT INTO accounts (tenant_id, account_name, account_type, balance)
SELECT t.id, 'Online Sales', 'Income', 0.00
FROM tenants t
WHERE NOT EXISTS (
    SELECT 1 FROM accounts a WHERE a.tenant_id = t.id AND a.account_name = 'Online Sales'
);
