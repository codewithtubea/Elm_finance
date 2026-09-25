CREATE TABLE IF NOT EXISTS `elm_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(50) NOT NULL DEFAULT '',
    `last_name` VARCHAR(50) NOT NULL DEFAULT '',
    `university` VARCHAR(100) NOT NULL DEFAULT 'Ashesi University',
    `verification_token` VARCHAR(64) NULL DEFAULT NULL,
    `is_verified` TINYINT(1) NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_elm_users_username` (`username`),
    UNIQUE KEY `uq_elm_users_email` (`email`),
    UNIQUE KEY `uq_elm_users_verification_token` (`verification_token`),
    CONSTRAINT `chk_elm_users_verified` CHECK (`is_verified` IN (0, 1)),
    CONSTRAINT `chk_elm_users_active` CHECK (`is_active` IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `elm_expenses` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `category` ENUM('food', 'transport', 'essentials', 'entertainment', 'other') NOT NULL,
    `description` VARCHAR(255) NOT NULL DEFAULT '',
    `expense_date` DATE NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_elm_expenses_user_date` (`user_id`, `expense_date`, `created_at`),
    CONSTRAINT `fk_elm_expenses_user` FOREIGN KEY (`user_id`) REFERENCES `elm_users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_elm_expenses_amount` CHECK (`amount` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `elm_budgets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `category` ENUM('food', 'transport', 'essentials', 'entertainment', 'other') NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `month_year` DATE NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_elm_budgets_user_category_month` (`user_id`, `category`, `month_year`),
    KEY `idx_elm_budgets_user_month` (`user_id`, `month_year`),
    CONSTRAINT `fk_elm_budgets_user` FOREIGN KEY (`user_id`) REFERENCES `elm_users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_elm_budgets_amount` CHECK (`amount` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `elm_campus_costs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `university` VARCHAR(100) NOT NULL,
    `category` ENUM('food', 'transport', 'essentials', 'entertainment', 'other') NOT NULL,
    `average_amount` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_elm_campus_university_category` (`university`, `category`),
    CONSTRAINT `chk_elm_campus_costs_amount` CHECK (`average_amount` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `elm_campus_costs` (`university`, `category`, `average_amount`) VALUES
    ('Ashesi University', 'food', 220.00),
    ('Ashesi University', 'transport', 120.00),
    ('Ashesi University', 'essentials', 200.00),
    ('Ashesi University', 'entertainment', 80.00),
    ('Ashesi University', 'other', 100.00)
ON DUPLICATE KEY UPDATE `average_amount` = VALUES(`average_amount`);
