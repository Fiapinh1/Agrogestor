<?php
$host = "localhost";        // Servidor do MySQL
$db   = "agrogestor";       // Nome do banco de dados
$user = "root";             // Usuário do MySQL (XAMPP padrão = root)
$pass = "";                 // Senha do MySQL (XAMPP padrão = vazio)

try {
    // Cria a conexão PDO
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,       // Mostra erros de SQL
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // Retorna arrays associativos
            PDO::ATTR_EMULATE_PREPARES => false                // Usa prepares reais
        ]
    );
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
