<?php
// includes/db_connect.php
$host = "localhost";
$dbname = "internship_system"; // ชื่อ Database ที่ปรากฏใน DBeaver
$username = "root";            // ค่ามาตรฐานของ XAMPP
$password = "";                // ค่ามาตรฐานของ XAMPP (ปกติจะว่างไว้)

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("เชื่อมต่อฐานข้อมูลไม่ได้: " . $e->getMessage());
}
?>