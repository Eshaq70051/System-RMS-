<?php

require_once __DIR__ . '/../app/Models/Database.php';
require_once __DIR__ . '/Seeder.php';

use App\Models\Database;
use Database\Seeder;

echo "=== Initializing Smart RMS Database ===\n";

try {
    $db = Database::getInstance();
    echo "[✓] Database connected and schema loaded successfully.\n";

    $seeder = new Seeder();
    $seeder->run();
    echo "[✓] Seeder executed successfully with initial data.\n";

    $userCount = $db->fetchValue("SELECT COUNT(*) FROM users");
    $prodCount = $db->fetchValue("SELECT COUNT(*) FROM products");
    $catCount = $db->fetchValue("SELECT COUNT(*) FROM categories");
    $invCount = $db->fetchValue("SELECT COUNT(*) FROM inventory_items");
    $tableCount = $db->fetchValue("SELECT COUNT(*) FROM dining_tables");

    echo "Status Summary:\n";
    echo " - Users: $userCount\n";
    echo " - Categories: $catCount\n";
    echo " - Products: $prodCount\n";
    echo " - Raw Inventory Items: $invCount\n";
    echo " - Dining Tables: $tableCount\n";
    echo "=== Initialization Complete ===\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
