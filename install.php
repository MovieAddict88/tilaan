<?php
require_once 'db_config.php';

try {
    $sql = file_get_contents('setup.sql');
    $pdo->exec($sql);
    echo "Database tables created successfully<br>";

    $tables = [
        'troubleshooting_guides' => "
            CREATE TABLE `troubleshooting_guides` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `title` varchar(255) NOT NULL,
              `content` text NOT NULL,
              `category` varchar(100) NOT NULL,
              `is_active` tinyint(1) NOT NULL DEFAULT 1,
              `created_at` timestamp NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
        ",
        'settings' => "
            CREATE TABLE `settings` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(255) NOT NULL,
              `value` text DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
        ",
        'resellers' => "
            CREATE TABLE `resellers` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `company_name` varchar(255) DEFAULT NULL,
              `logo_path` varchar(255) DEFAULT NULL,
              `primary_color` varchar(7) DEFAULT NULL,
              `secondary_color` varchar(7) DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT current_timestamp(),
              `commission_rate` decimal(5,2) NOT NULL DEFAULT 0.10,
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
        ",
        'commissions' => "
            CREATE TABLE `commissions` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `reseller_id` int(11) NOT NULL,
              `client_id` int(11) NOT NULL,
              `amount` decimal(10,2) NOT NULL,
              `commission_rate` decimal(5,2) NOT NULL,
              `commission_earned` decimal(10,2) NOT NULL,
              `created_at` timestamp NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `reseller_id` (`reseller_id`),
              KEY `client_id` (`client_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
        ",
        'promos' => "
            CREATE TABLE `promos` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `promo_name` varchar(255) NOT NULL,
              `icon_promo_path` varchar(255) NOT NULL,
              `carrier` varchar(255) DEFAULT NULL,
              `config_text` text DEFAULT NULL,
              `is_active` tinyint(1) DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
        ",
        'admob_ads' => "
            CREATE TABLE `admob_ads` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(255) NOT NULL,
              `ad_unit_id` varchar(255) NOT NULL,
              `ad_type` varchar(255) NOT NULL DEFAULT 'banner',
              `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
              `description` text DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
        ",
        'configurations' => "
            CREATE TABLE `configurations` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(255) NOT NULL,
              `type` varchar(255) NOT NULL,
              `value` text NOT NULL,
              `created_at` timestamp NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        "
    ];

    foreach ($tables as $table => $createQuery) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE :table");
        $stmt->execute(['table' => $table]);
        if ($stmt->rowCount() == 0) {
            $pdo->exec($createQuery);
            echo "Table `$table` created.<br>";

            if ($table === 'settings') {
                $pdo->exec("
                    INSERT INTO `settings` (`name`, `value`) VALUES
                    ('site_name', 'CS-MOBILE DATA'),
                    ('site_icon', 'assets/icon_6948daa99e56a.png'),
                    ('language', 'en');
                ");
                echo "Default settings inserted.<br>";
            }
        }
    }

    $columns = [
        'management_ip' => 'VARCHAR(255) DEFAULT NULL',
        'management_port' => 'INT(11) DEFAULT NULL'
    ];

    foreach ($columns as $column => $definition) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `vpn_profiles` LIKE :column");
        $stmt->execute(['column' => $column]);
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `vpn_profiles` ADD COLUMN `$column` $definition");
            echo "Column `$column` added to `vpn_profiles` table.<br>";
        }
    }

    $user_columns = [
        'data_usage' => 'BIGINT(20) UNSIGNED NOT NULL DEFAULT 0'
    ];

    foreach ($user_columns as $column => $definition) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `users` LIKE :column");
        $stmt->execute(['column' => $column]);
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `$column` $definition");
            echo "Column `$column` added to `users` table.<br>";
        }
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
    $stmt->execute(['username' => 'admin']);
    
    if ($stmt->rowCount() === 0) {
        $admin_user = 'admin';
        $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
        $insert_admin_sql = 'INSERT INTO users (username, password, role, first_name, last_name, address, contact_number) VALUES (:username, :password, "admin", "Admin", "User", "N/A", "N/A")';
        $stmt = $pdo->prepare($insert_admin_sql);
        $stmt->execute([
            'username' => $admin_user,
            'password' => $admin_pass
        ]);
        echo 'Default admin user created successfully (username: admin, password: admin123).<br>';
    } else {
        echo 'Admin user already exists.<br>';
    }

    echo "<br><strong>Database setup completed successfully!</strong><br>";
    echo '<a href="login.php" style="display: inline-block; padding: 10px 20px; background: #4361ee; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px;">Go to Login</a>';

} catch (PDOException $e) {
    // Suppress errors if the table already exists
    if (strpos($e->getMessage(), 'already exists') === false) {
        die('ERROR: Could not execute sql statement. ' . $e->getMessage());
    }
}
?>