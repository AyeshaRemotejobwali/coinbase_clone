<?php
$host = 'localhost';
$dbname = 'dbs2xhu1dvnw7h';
$username = 'uxgukysg8xcbd';
$password = '6imcip8yfmic';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>
