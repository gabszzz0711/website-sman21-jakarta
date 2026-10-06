<?php
$host = "sql305.byethost3.com";
$user = "b3_43046986";
$pass = "J@karta5"; // MAMP menggunakan password 'root'
$db   = "b3_43046986_sman21jkt";
$port = 3306;   // Port default MySQL di MAMP

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    // Mencoba koneksi tanpa spesifikasi port jika port disesuaikan
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
}
?>