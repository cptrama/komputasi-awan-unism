<?php
require_once __DIR__ . '/../config/database.php';

$db = get_db();
$cols = $db->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_ASSOC);
$colNames = array_column($cols, 'name');

echo "Existing columns: " . implode(', ', $colNames) . PHP_EOL;

if (!in_array('foto', $colNames, true)) {
    $db->exec("ALTER TABLE users ADD COLUMN foto TEXT NULL");
    echo "Column 'foto' successfully added to 'users' table." . PHP_EOL;
} else {
    echo "Column 'foto' already exists." . PHP_EOL;
}
