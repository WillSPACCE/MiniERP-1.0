-- TEST_ONLY: exercita o executor somente em tenants artificiais com ID >= 990000.
CREATE TABLE IF NOT EXISTS platform02b_test_probe (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
