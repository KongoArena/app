<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';

class Utilizador {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    public function login($email, $senha) {
        $sql = "SELECT * FROM cong_utilizadores WHERE email = :email AND status = 'ativo'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($senha, $user['senha_hash'])) {
            // Atualizar último acesso
            $sqlUpdate = "UPDATE cong_utilizadores SET ultimo_acesso = NOW() WHERE id = :id";
            $stmtUpdate = $this->pdo->prepare($sqlUpdate);
            $stmtUpdate->execute(['id' => $user['id']]);
            
            // Gerar JWT
            $token = generateJWT($user['id'], $user['tipo']);
            
            return [
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'nome' => $user['nome_completo'],
                    'email' => $user['email'],
                    'tipo' => $user['tipo']
                ]
            ];
        }
        
        return ['success' => false, 'message' => 'Credenciais inválidas'];
    }
    
    public function create($dados) {
        $senhaHash = password_hash($dados['senha'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO cong_utilizadores (nome_completo, email, telefone, senha_hash, tipo, status) 
                VALUES (:nome_completo, :email, :telefone, :senha_hash, :tipo, 'ativo')";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'nome_completo' => $dados['nome_completo'],
            'email' => $dados['email'],
            'telefone' => $dados['telefone'] ?? null,
            'senha_hash' => $senhaHash,
            'tipo' => $dados['tipo'] ?? 'atleta'
        ]);
    }
}
?>