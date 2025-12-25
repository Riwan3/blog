<?php
/**
 * Database Seeder Example
 *
 * CARA PAKAI:
 * 1. Copy file ini menjadi seed.php
 * 2. Edit password sesuai kebutuhan
 * 3. Jangan commit seed.php ke repository!
 */

require_once 'src/config.php';

// GANTI PASSWORD INI!
$admin_password = 'your-secure-password-here';
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Insert admin user
$sql = "INSERT INTO `users` (`username`, `password`, `role`) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "sss", $username, $hashed_password, $role);

$username = 'admin';
$role = 'admin';

if (mysqli_stmt_execute($stmt)) {
    echo "✓ Admin user created successfully!\n";
    echo "  Username: admin\n";
    echo "  Password: (hidden for security)\n";
} else {
    echo "✗ Error: " . mysqli_error($link) . "\n";
}

mysqli_stmt_close($stmt);
mysqli_close($link);
?>
