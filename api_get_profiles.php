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

    // Prepare a select statement to retrieve all unique profiles
    $sql = "
        SELECT DISTINCT
            p.id,
            p.name AS profile_name,
            p.ovpn_config,
            p.type as profile_type,
            p.icon_path
        FROM
            vpn_profiles p
        ORDER BY
            p.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $base_url = get_base_url();
    foreach ($profiles as &$profile) {
        // In the new flow, the config text from promos will be handled by the client
        // after fetching promos for a selected profile.
        $profile['profile_content'] = $profile['ovpn_config'];
        unset($profile['ovpn_config']);

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
