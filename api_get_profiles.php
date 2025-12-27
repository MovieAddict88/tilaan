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

    // Base SQL query parts
    $select_sql = "
        SELECT DISTINCT
            p.id,
            p.name AS profile_name,
            p.ovpn_config,
            p.type as profile_type,
            p.icon_path
    ";
    $from_sql = " FROM vpn_profiles p ";
    $join_sql = "";
    $where_sql = "";
    $order_by_sql = " ORDER BY p.name ASC";

    $params = [];

    // Check if promo_id is provided for filtering
    if (isset($_POST['promo_id']) && !empty($_POST['promo_id'])) {
        $select_sql .= ", pr.config_text ";
        $join_sql = "
            JOIN profile_promos pp ON p.id = pp.profile_id
            JOIN promos pr ON pp.promo_id = pr.id
        ";
        $where_sql = " WHERE pp.promo_id = :promo_id ";
        $params[':promo_id'] = $_POST['promo_id'];
    }

    $sql = $select_sql . $from_sql . $join_sql . $where_sql . $order_by_sql;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $base_url = get_base_url();
    foreach ($profiles as &$profile) {
        $profile_content = $profile['ovpn_config'];
        if (isset($profile['config_text']) && !empty($profile['config_text'])) {
            $profile_content .= "\n" . $profile['config_text'];
        }
        $profile['profile_content'] = $profile_content;

        unset($profile['ovpn_config']);
        if (isset($profile['config_text'])) {
            unset($profile['config_text']);
        }

        if (!empty($profile['icon_path'])) {
            $profile['icon_path'] = $base_url . $profile['icon_path'];
        }
        $profile['ping'] = rand(20, 200);
        $profile['signal_strength'] = rand(30, 100);
    }

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'profiles' => $profiles]);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
