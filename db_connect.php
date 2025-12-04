<?php
// Konfigurasi koneksi database
 $host = 'localhost';
 $dbname = 'project_management';
 $username = 'root';
 $password = 'root';

try {
    // Membuat koneksi PDO ke database MySQL
    // Menggunakan host, nama database, username, dan password yang telah ditentukan
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Mengatur mode error PDO ke Exception untuk penanganan kesalahan yang lebih baik
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Mengatur mode fetch default ke Associative Array agar hasil query mudah diakses
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Jika koneksi gagal, hentikan eksekusi dan tampilkan pesan error
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>