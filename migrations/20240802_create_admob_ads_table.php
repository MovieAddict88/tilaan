<?php
// migrations/20240802_create_admob_ads_table.php
require_once __DIR__ . '/../db_config.php';

try {
    // SQL to create table
    $sql = "CREATE TABLE IF NOT EXISTS admob_ads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        ad_unit_id VARCHAR(255) NOT NULL,
        ad_type VARCHAR(255) NOT NULL DEFAULT 'banner',
        is_enabled BOOLEAN NOT NULL DEFAULT 1,
        description TEXT
    )";

    // Execute the query
    $pdo->exec($sql);

    // Check if there is any data in the table. If not, insert the default ads.
    $stmt = $pdo->query("SELECT COUNT(*) FROM admob_ads");
    $rowCount = $stmt->fetchColumn();

    if ($rowCount == 0) {
        // SQL to insert data
        $insert_sql = "INSERT INTO admob_ads (name, ad_unit_id, ad_type, description) VALUES
            ('App Open', 'ca-app-pub-3940256099942544/9257395921', 'app_open', 'Displayed when the app is opened.'),
            ('Adaptive Banner', 'ca-app-pub-3940256099942544/9214589741', 'banner', 'Standard banner ad.'),
            ('Fixed Size Banner', 'ca-app-pub-3940256099942544/6300978111', 'banner', 'A banner with a fixed size.'),
            ('Interstitial', 'ca-app-pub-3940256099942544/1033173712', 'interstitial', 'Full-screen ad shown between activities.'),
            ('Rewarded Ads', 'ca-app-pub-3940256099942544/5224354917', 'rewarded', 'Ad that users can watch to earn a reward.'),
            ('Rewarded Interstitial', 'ca-app-pub-3940256099942544/5354046379', 'rewarded_interstitial', 'A rewarded ad that appears as an interstitial.'),
            ('Native', 'ca-app-pub-3940256099942544/2247696110', 'native', 'Customizable ad format.'),
            ('Native Video', 'ca-app-pub-3940256099942544/1044960115', 'native', 'A native ad with video content.')";

        // Execute the query
        $pdo->exec($insert_sql);
    }

} catch (PDOException $e) {
    // Do not output error directly to the screen in a production environment
    error_log("Admob Ads migration failed: " . $e->getMessage());
}
?>
