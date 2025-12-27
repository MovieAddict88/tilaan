<?php
require_once '../db_config.php';

try {
    $pdo->exec("
        CREATE TABLE `vpn_profile_promos` (
          `profile_id` int(11) NOT NULL,
          `promo_id` int(11) NOT NULL,
          PRIMARY KEY (`profile_id`,`promo_id`),
          KEY `promo_id` (`promo_id`),
          CONSTRAINT `vpn_profile_promos_ibfk_1` FOREIGN KEY (`profile_id`) REFERENCES `vpn_profiles` (`id`) ON DELETE CASCADE,
          CONSTRAINT `vpn_profile_promos_ibfk_2` FOREIGN KEY (`promo_id`) REFERENCES `promos` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $pdo->exec("
        ALTER TABLE `vpn_profiles` DROP COLUMN `promo_id`;
    ");

    $pdo->exec("
        INSERT INTO `migrations` (`migration`) VALUES ('20240804_create_profile_promos_table.php');
    ");

    echo "Migration successful!\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
