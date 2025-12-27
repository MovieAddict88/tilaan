<?php
// api_get_profiles.php

// Include the database configuration
require_once 'db_config.php';
require_once 'utils.php';

// Check if the login_code is set
if (!isset($_POST['login_code'])) {
    header('Content-Type: application/json');
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Login code is required.']);
    exit;
}

try {
    $login_code = $_POST['login_code'];

    // Validate the login_code
    $sql = 'SELECT id, banned FROM users WHERE login_code = :login_code';
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':login_code', $login_code, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        header('Content-Type: application/json');
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'error', 'message' => 'Invalid login code.']);
        exit;
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user['banned']) {
        header('Content-Type: application/json');
        http_response_code(403); // Forbidden
        echo json_encode(['status' => 'error', 'message' => 'User is banned.']);
        exit;
    }

    // Check if promo_id is set in the POST request
    if (isset($_POST['promo_id']) && !empty($_POST['promo_id'])) {
        $promo_id = $_POST['promo_id'];

        // This query will return one row per profile-promo combination for profiles that have the selected promo
        $sql = "
            SELECT
                p.id,
                p.name AS profile_name,
                p.ovpn_config,
                p.type as profile_type,
                p.icon_path,
                pr.id as promo_id,
                pr.promo_name,
                pr.config_text
            FROM
                vpn_profiles p
            JOIN
                vpn_profile_promos vpp ON p.id = vpp.profile_id
            JOIN
                promos pr ON vpp.promo_id = pr.id
            WHERE p.id IN (SELECT profile_id FROM vpn_profile_promos WHERE promo_id = :promo_id)
            ORDER BY
                p.id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':promo_id', $promo_id, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process the flat list into a structured array
        $profiles_assoc = [];
        foreach ($results as $row) {
            $profile_id = $row['id'];
            if (!isset($profiles_assoc[$profile_id])) {
                $profiles_assoc[$profile_id] = [
                    'id' => $row['id'],
                    'profile_name' => $row['profile_name'],
                    'ovpn_config' => $row['ovpn_config'],
                    'profile_type' => $row['profile_type'],
                    'icon_path' => $row['icon_path'],
                    'promos' => [],
                ];
            }
            if ($row['promo_id']) {
                $profiles_assoc[$profile_id]['promos'][] = [
                    'id' => $row['promo_id'],
                    'promo_name' => $row['promo_name'],
                    'config_text' => $row['config_text'],
                ];
            }
        }
        $profiles = array_values($profiles_assoc);

    } else {
        // If no promo_id is provided, return an empty list of profiles.
        $profiles = [];
    }

    $base_url = get_base_url();
    foreach ($profiles as &$profile) {
        if (!empty($profile['icon_path'])) {
            $profile['icon_path'] = $base_url . $profile['icon_path'];
        }
        // Simulate ping for each profile
        $profile['ping'] = rand(20, 200);
        $profile['signal_strength'] = rand(30, 100);
    }

    // Set the content type header to application/json
    header('Content-Type: application/json');

    // Output the profiles as a JSON encoded string
    echo json_encode(['status' => 'success', 'profiles' => $profiles]);

} catch (PDOException $e) {
    // Handle potential database errors
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
