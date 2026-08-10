<?php
/**
 * Script de RECUPERAÇÃO DE ACESSO - Kongo Arena
 * ------------------------------------------------
 * USO: apenas via linha de comando (SSH), NUNCA pelo navegador.
 * Isso evita o mesmo problema de segurança do "login_emergencia.php"
 * antigo, que ficava acessível publicamente por qualquer pessoa.
 *
 * Como usar no servidor:
 *   php recuperar_acesso.php email@doexemplo.com NovaSenha123
 *
 * O que faz:
 *   - Localiza o utilizador pelo e-mail
 *   - Gera um novo hash seguro de senha (bcrypt, via password_hash)
 *   - Atualiza a senha no banco
 *   - Reativa a conta caso estivesse com status diferente de 'ativo'
 */

// Bloqueia qualquer tentativa de execução via navegador/HTTP
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Acesso negado. Este script só pode ser executado via linha de comando (CLI).\n");
}

require_once __DIR__ . '/api/config/database.php';

if ($argc < 3) {
    echo "Uso: php recuperar_acesso.php <email> <nova_senha>\n";
    exit(1);
}

$email = trim($argv[1]);
$novaSenha = $argv[2];

if (strlen($novaSenha) < 6) {
    echo "A nova senha deve ter pelo menos 6 caracteres.\n";
    exit(1);
}

try {
    $pdo = getDBConnection();

    // Verifica se o utilizador existe
    $stmt = $pdo->prepare("SELECT id, nome_completo, status FROM cong_utilizadores WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "Nenhum utilizador encontrado com o e-mail: $email\n";
        exit(1);
    }

    $novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);

    $update = $pdo->prepare(
        "UPDATE cong_utilizadores 
         SET senha_hash = :hash, status = 'ativo' 
         WHERE id = :id"
    );
    $update->execute([
        'hash' => $novoHash,
        'id' => $user['id']
    ]);

    echo "Senha redefinida com sucesso para: {$user['nome_completo']} ($email)\n";
    echo "Status da conta: ativo\n";
    echo "Peça à pessoa para entrar com a nova senha e trocá-la em seguida.\n";

} catch (PDOException $e) {
    echo "Erro ao aceder à base de dados: " . $e->getMessage() . "\n";
    exit(1);
}
?>
