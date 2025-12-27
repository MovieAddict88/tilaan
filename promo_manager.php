<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header('location: login.php');
    exit;
}

require_once 'db_config.php';
require_once 'utils.php';

$profile_id = null;
if (isset($_GET['profile_id'])) {
    $profile_id = $_GET['profile_id'];
}

// Handle Add/Edit Promo
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle saving promo associations
    if (isset($_POST['save_associations'])) {
        $profile_id = $_POST['profile_id'];
        $promo_ids = isset($_POST['promo_ids']) ? $_POST['promo_ids'] : [];

        try {
            $pdo->beginTransaction();

            $sql = 'DELETE FROM profile_promos WHERE profile_id = :profile_id';
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
            $stmt->execute();

            if (!empty($promo_ids)) {
                $sql = 'INSERT INTO profile_promos (profile_id, promo_id) VALUES (:profile_id, :promo_id)';
                $stmt = $pdo->prepare($sql);
                foreach ($promo_ids as $promo_id) {
                    $stmt->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
                    $stmt->bindParam(':promo_id', $promo_id, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
        header('location: promo_manager.php?profile_id=' . $profile_id);
        exit;
    }

    // Handle Add Promo
    if (isset($_POST['add_promo'])) {
        $carrier = trim($_POST['carrier']);
        $name = trim($_POST['name']);
        $config_text = trim($_POST['config_text']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $icon_promo_path = trim($_POST['icon_promo_path']);

        $sql = 'INSERT INTO promos (carrier, promo_name, config_text, is_active, icon_promo_path) VALUES (:carrier, :promo_name, :config_text, :is_active, :icon_promo_path)';
        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(':carrier', $carrier, PDO::PARAM_STR);
            $stmt->bindParam(':promo_name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':config_text', $config_text, PDO::PARAM_STR);
            $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
            $stmt->bindParam(':icon_promo_path', $icon_promo_path, PDO::PARAM_STR);
            $stmt->execute();
        }
        header('location: promo_manager.php' . ($profile_id ? '?profile_id=' . $profile_id : ''));
        exit;
    }
}

// Handle Delete Promo
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = 'DELETE FROM promos WHERE id = :id';
    if ($stmt = $pdo->prepare($sql)) {
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
    header('location: promo_manager.php' . ($profile_id ? '?profile_id=' . $profile_id : ''));
    exit;
}

include 'header.php';

// Fetch all profiles for the dropdown
$profiles = $pdo->query('SELECT id, name FROM vpn_profiles ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);

// Fetch all promos
$promos = $pdo->query('SELECT id, promo_name, carrier, is_active, icon_promo_path FROM promos ORDER BY promo_name ASC')->fetchAll(PDO::FETCH_ASSOC);

// Fetch current associations for the selected profile
$associated_promo_ids = [];
if ($profile_id) {
    $sql = 'SELECT promo_id FROM profile_promos WHERE profile_id = :profile_id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
    $stmt->execute();
    $associated_promo_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}
?>

<div class="page-header">
    <h2><?php echo translate('promo_manager'); ?></h2>
</div>

<div class="card">
    <div class="card-header">
        <h3><?php echo translate('assign_promos_to_profile'); ?></h3>
    </div>
    <div class="card-body">
        <form action="promo_manager.php" method="get" id="profile_selector_form">
            <div class="form-group">
                <label for="profile_id"><?php echo translate('select_profile'); ?></label>
                <select name="profile_id" id="profile_id" class="form-control" onchange="document.getElementById('profile_selector_form').submit();">
                    <option value=""><?php echo translate('please_select'); ?></option>
                    <?php foreach ($profiles as $profile): ?>
                        <option value="<?php echo $profile['id']; ?>" <?php if ($profile_id == $profile['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($profile['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if ($profile_id): ?>
            <hr>
            <form action="promo_manager.php?profile_id=<?php echo $profile_id; ?>" method="post">
                <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                <h4><?php echo translate('available_promos'); ?></h4>
                <div class="form-group">
                    <?php foreach ($promos as $promo): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="promo_ids[]" value="<?php echo $promo['id']; ?>" id="promo_<?php echo $promo['id']; ?>"
                                <?php if (in_array($promo['id'], $associated_promo_ids)) echo 'checked'; ?>>
                            <label class="form-check-label" for="promo_<?php echo $promo['id']; ?>">
                                <?php echo htmlspecialchars($promo['promo_name']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-group">
                    <input type="submit" name="save_associations" class="btn btn-primary" value="<?php echo translate('save_changes'); ?>">
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h3><?php echo translate('add_new_promo'); ?></h3>
    </div>
    <div class="card-body">
        <form action="promo_manager.php<?php echo ($profile_id ? '?profile_id=' . $profile_id : ''); ?>" method="post">
            <div class="form-group">
                <label for="carrier">Carrier</label>
                <input type="text" name="carrier" id="carrier" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="config_text">Configuration Text</label>
                <textarea name="config_text" id="config_text" class="form-control" rows="10" required></textarea>
            </div>
            <div class="form-group">
                <label for="is_active">Active</label>
                <input type="checkbox" name="is_active" id="is_active" value="1" checked>
            </div>
            <div class="form-group">
                <label class="form-label"><?php echo translate('icon'); ?></label>
                <select name="icon_promo_path" class="form-control" required>
                    <?php
                    $promo_icons = glob('assets/promo/*.png');
                    foreach ($promo_icons as $icon) {
                        echo "<option value='" . htmlspecialchars($icon) . "'>" . htmlspecialchars(basename($icon)) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <input type="submit" name="add_promo" class="btn btn-primary" value="<?php echo translate('add_promo'); ?>">
            </div>
        </form>
    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h3><?php echo translate('existing_promos'); ?></h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Carrier</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Icon</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $base_url = get_base_url();
                    foreach ($promos as $promo) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($promo['carrier']) . "</td>";
                        echo "<td>" . htmlspecialchars($promo['promo_name']) . "</td>";
                        echo "<td>" . ($promo['is_active'] ? 'Active' : 'Inactive') . "</td>";
                        echo "<td><img src='" . htmlspecialchars($base_url . $promo['icon_promo_path']) . "' alt='icon' width='30'></td>";
                        echo "<td>";
                        echo "<a href='edit_promo.php?id=" . $promo['id'] . "' class='btn btn-primary'>Edit</a>";
                        echo "<a href='promo_manager.php?delete=" . $promo['id'] . ($profile_id ? '&profile_id=' . $profile_id : '') . "' class='btn btn-danger' onclick='return confirm(\"" . htmlspecialchars(translate('are_you_sure'), ENT_QUOTES) . "\")'>" . translate('delete') . "</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
