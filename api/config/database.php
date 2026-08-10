<?php
// Configuração da base de dados
// As credenciais NÃO ficam no código - vêm de variáveis de ambiente.
// Configure isso no seu servidor (painel de hospedagem, .env, ou vhost).

// Configuração da base de dados
define('DB_HOST', 'myshared2003');
define('DB_NAME', 'luna_home');
define('DB_USER', 'luna_home');
define('DB_PASS', 'Akiles1539@@##');

function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        // Em produção, não expor detalhes do erro
        error_log("Erro na conexão com o banco: " . $e->getMessage());
        die("Erro na conexão com o banco de dados.");
    }
}
?>
