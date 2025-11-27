<?php
// FILE: config.php (Revised for PostgreSQL/Supabase)

// --- Supabase PostgreSQL Credentials ---
// NOTE: Use the "Connection String" details from your Supabase Project Settings
$host = 'db.yourprojectref.supabase.co'; // Your unique host (e.g., db.xyz123abc.supabase.co)
$dbname = 'postgres';                    // Default Supabase DB name is usually 'postgres'
$username = 'postgres';                  // Supabase default user
$password = 'YOUR_DB_PASSWORD';          // IMPORTANT: Your actual database password
$port = '5432';                          // Standard PostgreSQL port

try {
    // 1. Change the DSN prefix from 'mysql' to 'pgsql'
    // 2. Add the 'port' parameter
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$username;password=$password";
    
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Optional: Set timezone for consistency, though Supabase often handles this.
    $pdo->exec("SET TIMEZONE TO 'Africa/Lusaka';"); // Example Timezone
    
} catch (PDOException $e) {
    // In a production environment, avoid showing the detailed error
    error_log("PostgreSQL Connection failed: " . $e->getMessage());
    die("Database connection failed. Check config.php and credentials.");
}

// Start session
session_start();
?>