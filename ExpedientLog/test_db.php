<?php
require_once 'templates/config.php';

echo "<h2>Database Connection Test</h2>";

// Test 1: Connection
echo "<h3>1. Database Connection</h3>";
try {
    $result = $pdo->query("SELECT 1");
    echo "<p style='color: green;'><strong>✓ Connection successful</strong></p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>✗ Connection failed:</strong> " . $e->getMessage() . "</p>";
}

// Test 2: Users table
echo "<h3>2. Users Table</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch();
    echo "<p><strong>Total users:</strong> " . $result['total'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>✗ Error:</strong> " . $e->getMessage() . "</p>";
}

// Test 3: Tickets table
echo "<h3>3. Tickets Table</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets");
    $result = $stmt->fetch();
    echo "<p><strong>Total tickets:</strong> " . $result['total'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>✗ Error:</strong> " . $e->getMessage() . "</p>";
}

// Test 4: Today's tickets
echo "<h3>4. Today's Tickets</h3>";
try {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $result = $stmt->fetch();
    echo "<p><strong>Today's tickets:</strong> " . $result['total'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>✗ Error:</strong> " . $e->getMessage() . "</p>";
}

// Test 5: Sample tickets
echo "<h3>5. Sample Tickets</h3>";
try {
    $stmt = $pdo->query("SELECT id, user_id, task, project, is_knowledge, created_at FROM tickets LIMIT 5");
    $tickets = $stmt->fetchAll();
    if (empty($tickets)) {
        echo "<p style='color: orange;'><strong>No tickets found</strong></p>";
    } else {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Task</th><th>Project</th><th>KB</th><th>Created</th></tr>";
        foreach ($tickets as $t) {
            echo "<tr>";
            echo "<td>" . $t['id'] . "</td>";
            echo "<td>" . $t['user_id'] . "</td>";
            echo "<td>" . substr($t['task'], 0, 30) . "...</td>";
            echo "<td>" . ($t['project'] ?: 'N/A') . "</td>";
            echo "<td>" . ($t['is_knowledge'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . $t['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>✗ Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr><p><a href='templates/admin_dashboard.php'>Back to Dashboard</a> | <a href='templates/dashboard.php'>Employee Dashboard</a></p>";
?>