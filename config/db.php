<?php
// config/db.php

// 1. HOST: This is usually sql300.infinityfree.com or similar. 
// Check your "MySQL Host Name" in the panel to be sure.
$host = 'sql305.infinityfree.com'; 

// 2. DB NAME: Your database name
$dbname = 'if0_40841695_studyquest'; 

// 3. USERNAME: I found this in your error log!
$username = 'if0_40841695'; 

// 4. PASSWORD: You must copy this from the InfinityFree "Account Details" section
$password = 'QSN4668merah'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>