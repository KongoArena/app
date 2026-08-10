<?php
require_once __DIR__ . '/../config/database.php';

class Temporada {
    private $pdo;

    public function __construct() {
        $this->pdo = getDBConnection();
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM cong_temporadas ORDER BY data_inicio DESC");
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM cong_temporadas WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($dados) {
        $sql = "INSERT INTO cong_temporadas (nome, data_inicio, data_fim, status)
                VALUES (:nome, :data_inicio, :data_fim, :status)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nome' => $dados['nome'],
            'data_inicio' => $dados['data_inicio'] ?? null,
            'data_fim' => $dados['data_fim'] ?? null,
            'status' => $dados['status'] ?? 'ativa'
        ]);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $dados) {
        $sql = "UPDATE cong_temporadas SET nome = :nome, data_inicio = :data_inicio,
                data_fim = :data_fim, status = :status WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nome' => $dados['nome'],
            'data_inicio' => $dados['data_inicio'] ?? null,
            'data_fim' => $dados['data_fim'] ?? null,
            'status' => $dados['status'] ?? 'ativa'
        ]);
    }
}
?>
