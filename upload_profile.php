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

// Check if a file was uploaded and a name was provided
if (isset($_FILES['profile_ovpn']) && isset($_POST['profile_name'])) {
    $profile_name = trim($_POST['profile_name']);
    $file = $_FILES['profile_ovpn'];
    $file_name = $file['name'];
    $file_tmp_name = $file['tmp_name'];
    $file_size = $file['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if ($file_ext == 'ovpn' && $file_size <= 1000000) {
        $ovpn_config = file_get_contents($file_tmp_name);

        try {
            $pdo->beginTransaction();
            $sql = 'INSERT INTO vpn_profiles (name, ovpn_config) VALUES (:name, :ovpn_config)';
            if ($stmt = $pdo->prepare($sql)) {
                $stmt->bindParam(':name', $profile_name, PDO::PARAM_STR);
                $stmt->bindParam(':ovpn_config', $ovpn_config, PDO::PARAM_STR);
                if ($stmt->execute()) {
                    $profile_id = $pdo->lastInsertId();

                    if (!empty($_POST['promo_ids']) && is_array($_POST['promo_ids'])) {
                        $sql_insert_promo = 'INSERT INTO vpn_profile_promos (profile_id, promo_id) VALUES (:profile_id, :promo_id)';
                        $stmt_insert_promo = $pdo->prepare($sql_insert_promo);

                        foreach ($_POST['promo_ids'] as $promo_id) {
                            $stmt_insert_promo->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
                            $stmt_insert_promo->bindParam(':promo_id', $promo_id, PDO::PARAM_INT);
                            $stmt_insert_promo->execute();
                        }
                    }
                    $pdo->commit();
                    $upload_message = 'Profile uploaded successfully.';
                } else {
                    $pdo->rollBack();
                    $upload_message = 'Error uploading profile.';
                }
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Error in upload_profile.php: ' . $e->getMessage());
            $upload_message = 'An error occurred. Please try again later.';
        }
    } else {
        $upload_message = 'Invalid file type or size.';
    }
}

include 'header.php';
?>

<div class="page-header">
    <h1>Upload Profile</h1>
</div>

<div class="card">
    <div class="card-header">
        <h3>Upload new .ovpn profile</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($upload_message)) : ?>
            <div class="alert alert-info"><?php echo $upload_message; ?></div>
        <?php endif; ?>
        <form action="upload_profile.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="profile_name">Profile Name:</label>
                <input type="text" name="profile_name" id="profile_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="profile_ovpn">Select .ovpn file to upload:</label>
                <input type="file" name="profile_ovpn" id="profile_ovpn" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Promos</label>
                <div class="checkbox-group">
                    <?php
                    $sql_promos = 'SELECT id, promo_name FROM promos';
                    $promos = $pdo->query($sql_promos)->fetchAll();
                    foreach ($promos as $promo) {
                        echo '<div class="form-check">';
                        echo '<input class="form-check-input" type="checkbox" name="promo_ids[]" value="' . $promo['id'] . '" id="promo_' . $promo['id'] . '">';
                        echo '<label class="form-check-label" for="promo_' . $promo['id'] . '">' . htmlspecialchars($promo['promo_name']) . '</label>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Upload</button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
