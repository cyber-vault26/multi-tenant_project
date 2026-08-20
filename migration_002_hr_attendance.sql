-- ============================================================
-- migration_002_hr_attendance.sql
--
-- 1. Fixes a real bug: the users.role ENUM only allowed
--    ('admin','staff'), but add-staff.php's dropdown offers
--    'manager' too, and platform.php checks for 'super_admin'.
--    Both inserts currently fail silently or error out.
--
-- 2. Adds the HR / Attendance module: employee profile fields
--    on users, plus a dedicated attendance table with daily
--    check-in/check-out, tenant-isolated like everything else.
--
-- Run this against your EXISTING Railway database (it does not
-- replace schema.sql — it's an incremental ALTER on top of it).
-- ============================================================

-- ---- 1. Fix the role ENUM ----------------------------------
ALTER TABLE users
    MODIFY COLUMN role ENUM('staff','manager','admin','super_admin') NOT NULL DEFAULT 'staff';

-- ---- 2. Employee profile fields (HR) -----------------------
ALTER TABLE users
    ADD COLUMN department      VARCHAR(100) DEFAULT NULL AFTER role,
    ADD COLUMN position_title  VARCHAR(100) DEFAULT NULL AFTER department,
    ADD COLUMN employment_type ENUM('full_time','part_time','contract') NOT NULL DEFAULT 'full_time' AFTER position_title,
    ADD COLUMN hire_date       DATE DEFAULT NULL AFTER employment_type,
    ADD COLUMN phone           VARCHAR(30) DEFAULT NULL AFTER hire_date;

-- ---- 3. Attendance table ------------------------------------
-- One row per user per work_date. "source" is included now so
-- a future biometric device integration can write into the
-- same table without a schema change later.
CREATE TABLE IF NOT EXISTS attendance (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    work_date   DATE NOT NULL,
    check_in    DATETIME DEFAULT NULL,
    check_out   DATETIME DEFAULT NULL,
    status      ENUM('present','late','absent','on_leave') NOT NULL DEFAULT 'present',
    source      ENUM('manual','biometric') NOT NULL DEFAULT 'manual',
    notes       VARCHAR(255) DEFAULT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_workdate (user_id, work_date),
    CONSTRAINT fk_attendance_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
