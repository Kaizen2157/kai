<?php
require_once __DIR__ . '/../db.php';

echo "<h2>Database Structure Check</h2>";

// Check if templates table exists
$tables = $conn->query("SHOW TABLES");
echo "<h3>Tables in database:</h3>";
echo "<ul>";
while ($table = $tables->fetch_array()) {
    echo "<li>" . $table[0] . "</li>";
}
echo "</ul>";

// Check templates table structure
echo "<h3>templates table columns:</h3>";
$columns = $conn->query("SHOW COLUMNS FROM templates");
if ($columns) {
    echo "<ul>";
    while ($col = $columns->fetch_assoc()) {
        echo "<li><strong>" . $col['Field'] . "</strong> - " . $col['Type'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:red;'>templates table does not exist!</p>";
}

// Check template_categories table
echo "<h3>template_categories table:</h3>";
$catColumns = $conn->query("SHOW COLUMNS FROM template_categories");
if ($catColumns) {
    echo "<ul>";
    while ($col = $catColumns->fetch_assoc()) {
        echo "<li><strong>" . $col['Field'] . "</strong> - " . $col['Type'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:red;'>template_categories table does not exist!</p>";
}
?>