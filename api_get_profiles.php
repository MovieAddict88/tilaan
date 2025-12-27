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

    // Fetch all active profiles and their associated promos
    $sql = "
        SELECT
            p.id AS profile_id,
            p.name AS profile_name,
            p.ovpn_config,
            p.type AS profile_type,
            p.icon_path,
            pr.promo_name,
            pr.config_text
        FROM
            vpn_profiles p
        JOIN
            profile_promos pp ON p.id = pp.profile_id
        JOIN
            promos pr ON pp.promo_id = pr.id
        WHERE
            pr.is_active = 1
        ORDER BY
            p.id ASC, pr.promo_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $profiles = [];
    $base_url = get_base_url();

    foreach ($results as $row) {
        $profile_id = $row['profile_id'];

        // If this is the first time we've seen this profile, create its main entry
        if (!isset($profiles[$profile_id])) {
            $profiles[$profile_id] = [
                'id' => $profile_id,
                'profile_name' => $row['profile_name'],
                'ovpn_config' => $row['ovpn_config'],
                'profile_type' => $row['profile_type'],
                'icon_path' => !empty($row['icon_path']) ? $base_url . $row['icon_path'] : null,
                'ping' => rand(20, 200),
                'signal_strength' => rand(30, 100),
                'promos' => []
            ];
        }

        // Add the current promo to this profile's list of promos
        $profiles[$profile_id]['promos'][] = [
            'promo_name' => $row['promo_name'],
            'profile_content' => $row['ovpn_config'] . "\n" . $row['config_text']
        ];
    }

    // Clean up the base ovpn_config from the main profile object
    foreach ($profiles as &$profile) {
        unset($profile['ovpn_config']);
    }

    // Convert the associative array to a simple indexed array for the final JSON output
    $final_profiles = array_values($profiles);

    // Set the content type header to application/json
    header('Content-Type: application/json');

    // Output the profiles as a JSON encoded string
    echo json_encode(['status' => 'success', 'profiles' => $final_profiles]);

} catch (PDOException $e) {
    // Handle potential database errors
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
