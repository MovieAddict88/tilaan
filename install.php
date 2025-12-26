<?php
// Include the database configuration file
require_once 'db_config.php';

try {
    // Read the SQL file
    $sql = file_get_contents('setup.sql');

    // Execute the SQL file
    $pdo->exec($sql);
    echo "Database tables created successfully from setup.sql<br>";

    // --- IDEMPOTENT SCHEMA UPDATES ---

    // Ensure 'promos' table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `promos` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `promo_name` varchar(255) NOT NULL,
          `icon_promo_path` varchar(255) NOT NULL,
          `carrier` varchar(255) DEFAULT NULL,
          `config_text` text DEFAULT NULL,
          `is_active` tinyint(1) DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
    ");
    echo "Checked/created `promos` table.<br>";

    // Ensure 'troubleshooting_guides' table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `troubleshooting_guides` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `title` varchar(255) NOT NULL,
          `content` text NOT NULL,
          `category` varchar(100) NOT NULL,
          `is_active` tinyint(1) NOT NULL DEFAULT 1,
          `created_at` timestamp NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
    ");
    echo "Checked/created `troubleshooting_guides` table.<br>";

    // Ensure 'resellers' table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `resellers` (
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
    ");
    echo "Checked/created `resellers` table.<br>";

    // Ensure 'commissions' table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `commissions` (
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
    ");
    echo "Checked/created `commissions` table.<br>";

    // Add columns to tables if they don't exist
    $table_columns = [
        'vpn_profiles' => [
            'management_ip' => 'VARCHAR(255) DEFAULT NULL',
            'management_port' => 'INT(11) DEFAULT NULL'
        ],
        'app_updates' => [
            'file_size' => 'BIGINT(20) DEFAULT NULL'
        ]
    ];

    foreach ($table_columns as $table => $columns) {
        // Check if table exists before altering
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            foreach ($columns as $column => $definition) {
                $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE :column");
                $stmt->execute(['column' => $column]);
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
                    echo "Column `$column` added to `$table` table.<br>";
                }
            }
        } else {
            echo "Table `$table` does not exist, skipping column additions.<br>";
        }
    }


    // Check if the admin user already exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
    $stmt->execute(['username' => 'admin']);
    
    if ($stmt->rowCount() === 0) {
        // Admin user does not exist, so create it
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
    die('ERROR: Could not execute sql statement. ' . $e->getMessage());
}
?>