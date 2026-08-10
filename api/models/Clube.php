<?php
require_once __DIR__ . '/../config/database.php';

class Clube {
    private $pdo;

    public function __construct() {
        $this->pdo = getDBConnection();
    }

    public function getAll() {
        $sql = "SELECT c.*,
                (SELECT COUNT(*) FROM cong_equipas e WHERE e.clube_id = c.id) as total_equipas
                FROM cong_clubes c ORDER BY c.nome ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM cong_clubes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $clube = $stmt->fetch();
        if ($clube) {
            $stmtEquipas = $this->pdo->prepare("SELECT * FROM cong_equipas WHERE clube_id = :id");
            $stmtEquipas->execute(['id' => $id]);
            $clube['equipas'] = $stmtEquipas->fetchAll();
        }
        return $clube;
    }

    public function create($dados) {
        $sql = "INSERT INTO cong_clubes (nome, logotipo, cidade, responsavel, contacto, email)
                VALUES (:nome, :logotipo, :cidade, :responsavel, :contacto, :email)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nome' => $dados['nome'],
            'logotipo' => $dados['logotipo'] ?? null,
            'cidade' => $dados['cidade'] ?? null,
            'responsavel' => $dados['responsavel'] ?? null,
            'contacto' => $dados['contacto'] ?? null,
            'email' => $dados['email'] ?? null
        ]);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $dados) {
        $sql = "UPDATE cong_clubes SET nome = :nome, logotipo = :logotipo, cidade = :cidade,
                responsavel = :responsavel, contacto = :contacto, email = :email WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nome' => $dados['nome'],
            'logotipo' => $dados['logotipo'] ?? null,
            'cidade' => $dados['cidade'] ?? null,
            'responsavel' => $dados['responsavel'] ?? null,
            'contacto' => $dados['contacto'] ?? null,
            'email' => $dados['email'] ?? null
        ]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM cong_clubes WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>
