<?php
require_once 'db_config.php';
require_once 'utils.php';

// Check if profile_id is provided
if (!isset($_GET['profile_id']) || empty($_GET['profile_id'])) {
    header('Content-Type: application/json');
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Profile ID is required.']);
    exit;
}

$profile_id = intval($_GET['profile_id']);

try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.promo_name, p.icon_promo_path, p.config_text
        FROM promos p
        JOIN profile_promos pp ON p.id = pp.promo_id
        WHERE pp.profile_id = :profile_id
        ORDER BY p.promo_name ASC
    ");
    $stmt->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
    $stmt->execute();
    $promos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $base_url = get_base_url();
    foreach ($promos as &$promo) {
        if (!empty($promo['icon_promo_path'])) {
            $promo['icon_promo_path'] = $base_url . $promo['icon_promo_path'];
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'promos' => $promos]);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
