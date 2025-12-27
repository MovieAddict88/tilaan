<?php
// api_get_promos.php

// Include the database configuration
require_once 'db_config.php';
require_once 'utils.php';

// Authenticate user
if (!isset($_POST['login_code'])) {
    header('Content-Type: application/json');
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Login code is required.']);
    exit;
}

// Check for profile_id
if (!isset($_POST['profile_id']) || empty($_POST['profile_id'])) {
    header('Content-Type: application/json');
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Profile ID is required.']);
    exit;
}

try {
    $login_code = $_POST['login_code'];
    $profile_id = $_POST['profile_id'];

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

    // Fetch the base profile config first
    $profile_stmt = $pdo->prepare('SELECT ovpn_config FROM vpn_profiles WHERE id = :profile_id');
    $profile_stmt->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
    $profile_stmt->execute();
    $base_config = $profile_stmt->fetchColumn();

    if ($base_config === false) {
        header('Content-Type: application/json');
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'error', 'message' => 'Profile not found.']);
        exit;
    }

    // Fetch active promos associated with the given profile_id
    $sql = "
        SELECT
            pr.id,
            pr.promo_name,
            pr.icon_promo_path,
            pr.config_text
        FROM
            promos pr
        JOIN
            profile_promos pp ON pr.id = pp.promo_id
        WHERE
            pp.profile_id = :profile_id AND pr.is_active = 1
        ORDER BY
            pr.promo_name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $promos = [];
    $base_url = get_base_url();

    foreach ($results as $row) {
        $promos[] = [
            'id' => $row['id'],
            'promo_name' => $row['promo_name'],
            'icon_promo_path' => !empty($row['icon_promo_path']) ? $base_url . $row['icon_promo_path'] : null,
            // Combine the base profile OVPN config with the promo-specific config
            'profile_content' => $base_config . "\n" . $row['config_text']
        ];
    }

    // Set the content type header to application/json
    header('Content-Type: application/json');

    // Output the promos as a JSON encoded string
    echo json_encode(['status' => 'success', 'promos' => $promos]);

} catch (PDOException $e) {
    // Handle potential database errors
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
