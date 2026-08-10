<?php
require_once __DIR__ . '/../config/database.php';

class Modalidade {
    private $pdo;

    public function __construct() {
        $this->pdo = getDBConnection();
    }

    public function getAll($apenasAtivas = false) {
        $sql = "SELECT * FROM cong_modalidades";
        if ($apenasAtivas) {
            $sql .= " WHERE status = 'ativo'";
        }
        $sql .= " ORDER BY nome ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM cong_modalidades WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($dados) {
        $sql = "INSERT INTO cong_modalidades (nome, descricao, icone, status)
                VALUES (:nome, :descricao, :icone, :status)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?? null,
            'icone' => $dados['icone'] ?? null,
            'status' => $dados['status'] ?? 'ativo'
        ]);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $dados) {
        $sql = "UPDATE cong_modalidades SET nome = :nome, descricao = :descricao,
                icone = :icone, status = :status WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?? null,
            'icone' => $dados['icone'] ?? null,
            'status' => $dados['status'] ?? 'ativo'
        ]);
    }

    public function toggleStatus($id) {
        $modalidade = $this->getById($id);
        if (!$modalidade) return false;
        $novoStatus = $modalidade['status'] === 'ativo' ? 'inativo' : 'ativo';
        $stmt = $this->pdo->prepare("UPDATE cong_modalidades SET status = :status WHERE id = :id");
        return $stmt->execute(['status' => $novoStatus, 'id' => $id]);
    }
}
?>
