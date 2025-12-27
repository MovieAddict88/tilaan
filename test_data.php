<?php
require_once 'db_config.php';

try {
    // Create a test profile
    $sql = "INSERT INTO vpn_profiles (name, ovpn_config, type) VALUES (:name, :ovpn_config, :type)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => 'test_profile',
        ':ovpn_config' => 'test_config',
        ':type' => 'Premium'
    ]);
    $profile_id = $pdo->lastInsertId();
    echo "Test profile created with id: $profile_id\n";

    // Create a test promo
    $sql = "INSERT INTO promos (promo_name, icon_promo_path) VALUES (:promo_name, :icon_promo_path)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':promo_name' => 'test_promo',
        ':icon_promo_path' => 'test_icon'
    ]);
    $promo_id = $pdo->lastInsertId();
    echo "Test promo created with id: $promo_id\n";

    // Associate the profile with the promo
    $sql = "INSERT INTO vpn_profile_promos (profile_id, promo_id) VALUES (:profile_id, :promo_id)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':profile_id' => $profile_id,
        ':promo_id' => $promo_id
    ]);
    echo "Associated profile $profile_id with promo $promo_id\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
