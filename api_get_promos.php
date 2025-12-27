<?php
// api_get_promos.php

require_once 'db_config.php';
require_once 'utils.php';

// Check if profile_id is set in the POST request
if (!isset($_POST['profile_id']) || empty($_POST['profile_id'])) {
    header('Content-Type: application/json');
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Profile ID is required.']);
    exit;
}

try {
    $profile_id = $_POST['profile_id'];

    // Prepare a select statement to retrieve promos for the given profile_id
    $sql = "
        SELECT
            pr.id,
            pr.promo_name,
            pr.icon_promo_path,
            vp.ovpn_config,
            pr.config_text
        FROM
            promos pr
        JOIN
            profile_promos pp ON pr.id = pp.promo_id
        JOIN
            vpn_profiles vp ON pp.profile_id = vp.id
        WHERE
            pp.profile_id = :profile_id
        ORDER BY
            pr.promo_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
    $stmt->execute();
    $promos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $base_url = get_base_url();
    foreach ($promos as &$promo) {
        if (!empty($promo['icon_promo_path'])) {
            $promo['icon_promo_path'] = $base_url . $promo['icon_promo_path'];
        }
        // Combine the base ovpn config with the promo's config text
        $promo['profile_content'] = $promo['ovpn_config'] . "\n" . $promo['config_text'];

        // Unset the original config fields to keep the response clean
        unset($promo['ovpn_config']);
        unset($promo['config_text']);
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