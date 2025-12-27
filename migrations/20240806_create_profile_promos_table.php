<?php
require_once __DIR__ . '/../db_config.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `profile_promos` (
            `profile_id` INT NOT NULL,
            `promo_id` INT NOT NULL,
            PRIMARY KEY (`profile_id`, `promo_id`),
            FOREIGN KEY (`profile_id`) REFERENCES `vpn_profiles`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`promo_id`) REFERENCES `promos`(`id`) ON DELETE CASCADE
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
    ");

    $pdo->exec("
        ALTER TABLE `vpn_profiles` DROP COLUMN `promo_id`;
    ");

    echo "Migration executed successfully.";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage());
}
?>