-- ============================================================
-- SiPadu – MySQL 8 Initialization Script
-- Runs once when the MySQL container is first created.
-- ============================================================

-- Ensure UTF-8 mb4 defaults for the database
ALTER DATABASE pa_disdukcapil
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Create application user with limited privileges
-- (root already created the DB via MYSQL_DATABASE env var)
CREATE USER IF NOT EXISTS 'sipadu_app'@'%'
  IDENTIFIED WITH mysql_native_password BY 'sipadu_secret';

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER,
      CREATE TEMPORARY TABLES, LOCK TABLES, EXECUTE, CREATE VIEW,
      SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE, EVENT, TRIGGER
  ON pa_disdukcapil.*
  TO 'sipadu_app'@'%';

FLUSH PRIVILEGES;

-- ── Performance tuning hints ───────────────────────────────────────
-- These are advisory; production should configure my.cnf directly.

-- Increase sort buffer for complex ORDER BY on large tables
SET GLOBAL sort_buffer_size = 4194304;          -- 4 MB

-- InnoDB buffer pool (adjust based on available memory)
-- SET GLOBAL innodb_buffer_pool_size = 536870912;  -- 512 MB

SELECT 'SiPadu database initialized successfully.' AS status;
