<?php
/* ============================================================
   config/database.php
   ONE JOB: connect PHP to MySQL via PDO.
   Every api/*.php file includes this first.
   ============================================================ */

$DB_HOST = "localhost";
$DB_NAME = "aidflow_db";
$DB_USER = "root";   // default XAMPP username
$DB_PASS = "";        // default XAMPP password (empty)

$pdo = new PDO(
    "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
