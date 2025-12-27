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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_ovpn'])) {
    $profile_name = trim($_POST['profile_name']);
    $profile_type = trim($_POST['profile_type']);
    $promo_ids = isset($_POST['promo_ids']) ? $_POST['promo_ids'] : [];

    $file = $_FILES['profile_ovpn'];
    $file_name = $file['name'];
    $file_tmp_name = $file['tmp_name'];
    $file_size = $file['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Basic validation
    if (empty($profile_name)) {
        $upload_message = '<div class="alert alert-danger">Please enter a profile name.</div>';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_message = '<div class="alert alert-danger">An error occurred during file upload.</div>';
    } elseif ($file_ext != 'ovpn') {
        $upload_message = '<div class="alert alert-danger">Invalid file type. Only .ovpn files are allowed.</div>';
    } elseif ($file_size > 1000000) { // 1MB limit
        $upload_message = '<div class="alert alert-danger">File is too large. Maximum size is 1MB.</div>';
    } else {
        $ovpn_config = file_get_contents($file_tmp_name);

        try {
            $pdo->beginTransaction();

            $sql_profile = 'INSERT INTO vpn_profiles (name, ovpn_config, type) VALUES (:name, :ovpn_config, :type)';
            $stmt_profile = $pdo->prepare($sql_profile);
            $stmt_profile->bindParam(':name', $profile_name, PDO::PARAM_STR);
            $stmt_profile->bindParam(':ovpn_config', $ovpn_config, PDO::PARAM_STR);
            $stmt_profile->bindParam(':type', $profile_type, PDO::PARAM_STR);

            if ($stmt_profile->execute()) {
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

                $pdo->commit();
                $upload_message = '<div class="alert alert-success">Profile "' . htmlspecialchars($profile_name) . '" uploaded successfully.</div>';
            } else {
                $pdo->rollBack();
                $upload_message = '<div class="alert alert-danger">Failed to insert profile into database.</div>';
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Error in upload_profile.php: ' . $e->getMessage());
            $upload_message = '<div class="alert alert-danger">An unexpected error occurred. Please try again later.</div>';
        }
    }
}

include 'header.php';
?>

<div class="page-header">
    <h1>Upload Single Profile</h1>
</div>

<div class="card">
    <div class="card-header">
        <h3>Upload a new .ovpn profile</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($upload_message)) { echo $upload_message; } ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="profile_name">Profile Name</label>
                <input type="text" name="profile_name" id="profile_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="profile_ovpn">Select .ovpn File</label>
                <input type="file" name="profile_ovpn" id="profile_ovpn" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Profile Type</label>
                <select name="profile_type" class="form-control" required>
                    <option value="Premium">Premium</option>
                    <option value="Freemium">Freemium</option>
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
                <button type="submit" class="btn btn-primary">Upload Profile</button>
                <a href="profiles.php" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
