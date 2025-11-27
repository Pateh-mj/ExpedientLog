<?php
// FILE: config.php (Final Secure Version for Supabase/PostgreSQL)

// Supabase/PostgreSQL Credentials will be read from the hosting environment
$host = getenv('dpg-d4k4mrvpm1nc73ae8ekg-a');
$dbname = getenv('schema_wi69');
$username = getenv('admin');
$password = getenv('6Tj26ci5BJWIZxeovbEY2NGbSzVO6Q9v');
$port = getenv('DB_PORT') ?: '5432'; // Default PostgreSQL port


// --- Security Check ---
if (!$host || !$dbname || !$username || !$password) {
    // Crucial check: If variables aren't set, the app must not proceed.
    http_response_code(500);
    die("Error: Database environment variables are missing. Check your Render settings.");
}

try {
    // *** CRITICAL CHANGE: Use the pgsql (PostgreSQL) driver ***
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$username;password=$password";
    
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET TIMEZONE TO 'UTC';"); // Ensure consistent time handling
    
} catch (PDOException $e) {
    // Log the error (but don't show the password!)
    error_log("PostgreSQL Connection failed: " . $e->getMessage());
    http_response_code(500);
    die("Database connection failed. Please check the connection details in Render's Environment Variables.");
}

// Start session
session_start();

?>
