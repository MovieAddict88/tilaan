<?php
// Include the database configuration file
require_once 'db_config.php';

try {
    // Read the SQL file
    $sql = file_get_contents('setup.sql');

    // Execute the SQL file
    $pdo->exec($sql);
    echo "Database tables created successfully<br>";

    // Check if the admin user already exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
    $stmt->execute(['username' => 'admin']);
    
    if ($stmt->rowCount() === 0) {
        // Admin user does not exist, so create it
        $admin_user = 'admin';
        $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);

        $insert_admin_sql = 'INSERT INTO users (username, password, role, first_name, last_name, address, contact_number) VALUES (:username, :password, "admin", "Admin", "User", "N/A", "N/A")';
        $stmt = $pdo->prepare($insert_admin_sql);
        $stmt->execute([
            'username' => $admin_user,
            'password' => $admin_pass
        ]);
        echo 'Default admin user created successfully (username: admin, password: admin123).<br>';
    } else {
        echo 'Admin user already exists.<br>';
    }

    echo "<br><strong>Database setup completed successfully!</strong><br>";
    echo '<a href="login.php" style="display: inline-block; padding: 10px 20px; background: #4361ee; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px;">Go to Login</a>';

} catch (PDOException $e) {
    die('ERROR: Could not execute sql statement. ' . $e->getMessage());
}
?>