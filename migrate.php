<?php
/**
 * Database Migration Script
 * Usage: php migrate.php [fresh]
 *
 * Commands:
 *   php migrate.php        - Run migration
 *   php migrate.php fresh  - Drop all tables and run fresh migration
 */

require_once 'src/config.php';

// Fungsi untuk drop semua tabel
function dropAllTables($connection) {
    echo "Dropping all tables...\n";

    // Disable foreign key checks
    mysqli_query($connection, "SET FOREIGN_KEY_CHECKS = 0");

    // Get all tables
    $result = mysqli_query($connection, "SHOW TABLES");

    if ($result) {
        while ($row = mysqli_fetch_array($result)) {
            $table = $row[0];
            echo "  - Dropping table: {$table}\n";
            mysqli_query($connection, "DROP TABLE IF EXISTS `{$table}`");
        }
    }

    // Re-enable foreign key checks
    mysqli_query($connection, "SET FOREIGN_KEY_CHECKS = 1");

    echo "All tables dropped successfully!\n\n";
}

// Fungsi untuk menjalankan file SQL
function runMigration($connection, $sqlFile) {
    echo "Running migration from {$sqlFile}...\n";

    if (!file_exists($sqlFile)) {
        die("ERROR: File {$sqlFile} tidak ditemukan!\n");
    }

    // Baca file SQL
    $sql = file_get_contents($sqlFile);

    if ($sql === false) {
        die("ERROR: Tidak dapat membaca file {$sqlFile}!\n");
    }

    // Remove comments
    $sql = preg_replace('/^--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    // Split SQL statements by semicolon
    $statements = explode(';', $sql);

    $success = 0;
    $errors = 0;

    foreach ($statements as $statement) {
        $statement = trim($statement);

        // Skip empty statements
        if (empty($statement)) {
            continue;
        }

        if (mysqli_query($connection, $statement)) {
            $success++;
            // Extract table name for better feedback
            if (preg_match('/CREATE TABLE\s+`?(\w+)`?/i', $statement, $matches)) {
                echo "  ✓ Created table: {$matches[1]}\n";
            }
        } else {
            $errors++;
            echo "  ✗ Error: " . mysqli_error($connection) . "\n";
            echo "  Statement: " . substr($statement, 0, 100) . "...\n";
        }
    }

    echo "\nMigration completed!\n";
    echo "Success: {$success} statements\n";

    if ($errors > 0) {
        echo "Errors: {$errors} statements\n";
    }
}

// Main execution
echo "=================================\n";
echo "Database Migration Tool\n";
echo "=================================\n";
echo "Database: " . DB_NAME . "\n";
echo "Server: " . DB_SERVER . "\n";
echo "=================================\n\n";

// Check command line arguments
$command = isset($argv[1]) ? $argv[1] : '';
$seedFlag = isset($argv[2]) && $argv[2] === '--seed';

if ($command === 'fresh') {
    echo "Running FRESH migration (dropping all tables)...\n\n";

    $confirm = readline("Are you sure you want to drop all tables? This cannot be undone! (yes/no): ");

    if (strtolower(trim($confirm)) !== 'yes') {
        echo "Migration cancelled.\n";
        exit(0);
    }

    dropAllTables($link);
}

// Run migration only if not seed-only command
if ($command !== 'seed') {
    runMigration($link, 'setup.sql');
}

// Run seeder if --seed flag is provided or command is seed
if ($seedFlag || $command === 'seed') {
    echo "\n=================================\n";
    echo "Running Database Seeder...\n";
    echo "=================================\n\n";

    if (file_exists('seed.php')) {
        mysqli_close($link);
        include 'seed.php';
    } else {
        echo "⚠ Warning: seed.php not found. Skipping seeder.\n";
    }
} else {
    // Close connection
    mysqli_close($link);
}

echo "\n=================================\n";
echo "Migration finished!\n";
echo "=================================\n";
?>
