<?php
// _db.php - Підключення до бази даних
$host = "127.0.0.1";
$port = 3306;
$username = "root";        // Ваш логін (для XAMPP за замовчуванням "root")
$password = "";            // Ваш пароль (для XAMPP за замовчуванням "")
$database = "hotel_booking";

try {
    $db = new PDO("mysql:host=$host;port=$port;charset=utf8mb4",
                   $username,
                   $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("USE `$database`");
    
    // Також створюємо змінну $pdo для сумісності (опціонально)
    $pdo = $db;
    
} catch(PDOException $e) {
    die("Помилка підключення: " . $e->getMessage());
}
?>