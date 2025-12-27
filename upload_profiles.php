<?php
// Start session
session_start();

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('location: login.php');
    exit;
}

// Include the database connection file
require_once 'db_config.php';

$upload_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profiles_ovpn'])) {
    $profile_type = trim($_POST['profile_type']);
    $promo_ids = isset($_POST['promo_ids']) ? $_POST['promo_ids'] : [];
    $icon_path = trim($_POST['icon_path']);

    $icons = glob('assets/*.png');
    if (!in_array($icon_path, $icons)) {
        $error_message = 'Invalid icon selected.';
    }

    if (empty($error_message)) {
        $files = $_FILES['profiles_ovpn'];
        $file_count = count($files['name']);
        $upload_count = 0;

        try {
            $pdo->beginTransaction();

            for ($i = 0; $i < $file_count; $i++) {
                $file_name = $files['name'][$i];
                $file_tmp_name = $files['tmp_name'][$i];
                $file_size = $files['size'][$i];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if ($file_ext == 'ovpn' && $file_size <= 1000000) {
                    $profile_name = pathinfo($file_name, PATHINFO_FILENAME);
                    $ovpn_config = file_get_contents($file_tmp_name);

                    $sql_profile = 'INSERT INTO vpn_profiles (name, ovpn_config, type, icon_path) VALUES (:name, :ovpn_config, :type, :icon_path)';
                    $stmt_profile = $pdo->prepare($sql_profile);
                    $stmt_profile->bindParam(':name', $profile_name, PDO::PARAM_STR);
                    $stmt_profile->bindParam(':ovpn_config', $ovpn_config, PDO::PARAM_STR);
                    $stmt_profile->bindParam(':type', $profile_type, PDO::PARAM_STR);
                    $stmt_profile->bindParam(':icon_path', $icon_path, PDO::PARAM_STR);

                    if ($stmt_profile->execute()) {
                        $upload_count++;
                        $profile_id = $pdo->lastInsertId();

                        if (!empty($promo_ids) && is_array($promo_ids)) {
                            $sql_promo = 'INSERT INTO profile_promos (profile_id, promo_id) VALUES (:profile_id, :promo_id)';
                            $stmt_promo = $pdo->prepare($sql_promo);

                            foreach ($promo_ids as $promo_id) {
                                $stmt_promo->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
                                $stmt_promo->bindParam(':promo_id', $promo_id, PDO::PARAM_INT);
                                $stmt_promo->execute();
                            }
                        }
                    }
                }
            }

            $pdo->commit();

            if ($upload_count > 0) {
                $upload_message = $upload_count . ' of ' . $file_count . ' profiles uploaded successfully.';
            } else {
                $error_message = 'No valid .ovpn profiles were uploaded. Please check file types and sizes.';
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Error in upload_profiles.php: ' . $e->getMessage());
            $error_message = 'An unexpected error occurred during the upload. Please try again.';
        }
    }
}

include 'header.php';
?>

<div class="page-header">
    <h1>Bulk Upload .ovpn Profiles</h1>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h3>Bulk Upload .ovpn Profiles</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($upload_message)): ?>
            <div class="alert alert-success"><?php echo $upload_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="profile_type">Profile Type</label>
                <select name="profile_type" id="profile_type" class="form-control" required>
                    <option value="Premium">Premium</option>
                    <option value="Freemium">Freemium</option>
                </select>
            </div>
            <div class="form-group">
                <label for="icon_path">Icon</label>
                <select name="icon_path" id="icon_path" class="form-control" required>
                    <?php
                    $icons = glob('assets/*.png');
                    foreach ($icons as $icon) {
                        echo '<option value="' . htmlspecialchars($icon) . '">' . htmlspecialchars(basename($icon)) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Promos</label>
                <div class="checkbox-group" style="height: 150px; overflow-y: auto; border: 1px solid #ced4da; padding: 10px; border-radius: .25rem;">
                    <?php
                    $sql_promos = 'SELECT id, promo_name FROM promos ORDER BY promo_name';
                    $promos = $pdo->query($sql_promos)->fetchAll();
                    if ($promos) {
                        foreach ($promos as $promo) {
                            echo '<div class="form-check">';
                            echo '<input class="form-check-input" type="checkbox" name="promo_ids[]" value="' . $promo['id'] . '" id="promo_' . $promo['id'] . '">';
                            echo '<label class="form-check-label" for="promo_' . $promo['id'] . '">' . htmlspecialchars($promo['promo_name']) . '</label>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p>No promos available.</p>';
                    }
                    ?>
                </div>
            </div>
            <div class="form-group">
                <label for="profiles_ovpn">Select .ovpn Files</label>
                <input type="file" name="profiles_ovpn[]" id="profiles_ovpn" class="form-control" multiple required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Upload Profiles</button>
                <a href="profiles.php" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
