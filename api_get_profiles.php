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

    // Prepare a select statement to retrieve all active profiles and their associated promo configurations
    $sql = "
        SELECT
            p.id,
            p.name AS profile_name,
            p.ovpn_config,
            GROUP_CONCAT(pr.promo_name SEPARATOR ', ') as promo_names,
            GROUP_CONCAT(pr.config_text SEPARATOR '\n') as config_texts,
            p.type as profile_type,
            p.icon_path
        FROM
            vpn_profiles p
        JOIN
            profile_promos pp ON p.id = pp.profile_id
        JOIN
            promos pr ON pp.promo_id = pr.id
        WHERE
            pr.is_active = 1
        GROUP BY
            p.id, p.name, p.ovpn_config, p.type, p.icon_path
        ORDER BY
            p.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $base_url = get_base_url();
    foreach ($profiles as &$profile) {
        // Append aggregated promo names to profile name
        if (!empty($profile['promo_names'])) {
            $profile['profile_name'] .= ' (' . $profile['promo_names'] . ')';
        }

        // Combine the base ovpn config with the aggregated config texts
        $profile['profile_content'] = $profile['ovpn_config'];
        if (!empty($profile['config_texts'])) {
            $profile['profile_content'] .= "\n" . $profile['config_texts'];
        }

        // Unset the original config fields to keep the response clean
        unset($profile['ovpn_config']);
        unset($profile['config_texts']);
        unset($profile['promo_names']);

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
