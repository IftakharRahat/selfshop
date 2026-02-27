<?php
$host = '127.0.0.1';
$db   = 'selfshop';
$user = 'root';
$pass = '';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $stmt = $pdo->query("SELECT id, ProductName, ProductRegularPrice, ProductResellerPrice, selling_type FROM products WHERE ProductName LIKE '%Bird%' OR ProductName LIKE '%Car Toy%'");
    $results = $stmt->fetchAll();
    
    $out = "";
    foreach($results as $r) {
        $out .= "ID: {$r['id']} | Name: {$r['ProductName']} | Type: {$r['selling_type']} | Reg: {$r['ProductRegularPrice']} | Res: {$r['ProductResellerPrice']}\n";
    }
    file_put_contents('prices_pdo.txt', $out);
    echo "Saved to prices_pdo.txt\n";
} catch (\PDOException $e) {
    file_put_contents('prices_pdo.txt', "Error: " . $e->getMessage());
    echo "Error caught\n";
}
