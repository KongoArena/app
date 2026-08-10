<?php
require_once __DIR__ . '/../config/database.php';

class Equipa {
    private $pdo;

    public function __construct() {
        $this->pdo = getDBConnection();
    }

    public function getAll($filtros = []) {
        $sql = "SELECT e.*, c.nome as clube_nome, m.nome as modalidade_nome,
                (SELECT COUNT(*) FROM cong_equipa_atletas ea WHERE ea.equipa_id = e.id AND ea.status = 'ativo') as total_atletas
                FROM cong_equipas e
                LEFT JOIN cong_clubes c ON e.clube_id = c.id
                LEFT JOIN cong_modalidades m ON e.modalidade_id = m.id";
        $condicoes = [];
        $params = [];
        if (!empty($filtros['modalidade_id'])) {
            $condicoes[] = "e.modalidade_id = :modalidade_id";
            $params['modalidade_id'] = $filtros['modalidade_id'];
        }
        if (!empty($filtros['clube_id'])) {
            $condicoes[] = "e.clube_id = :clube_id";
            $params['clube_id'] = $filtros['clube_id'];
        }
        if (!empty($condicoes)) {
            $sql .= " WHERE " . implode(" AND ", $condicoes);
        }
        $sql .= " ORDER BY e.nome ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $sql = "SELECT e.*, c.nome as clube_nome, m.nome as modalidade_nome
                FROM cong_equipas e
                LEFT JOIN cong_clubes c ON e.clube_id = c.id
                LEFT JOIN cong_modalidades m ON e.modalidade_id = m.id
                WHERE e.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $equipa = $stmt->fetch();
        if ($equipa) {
            $equipa['atletas'] = $this->getAtletas($id);
        }
        return $equipa;
    }

    public function create($dados) {
        $sql = "INSERT INTO cong_equipas (nome, clube_id, modalidade_id, categoria, treinador)
                VALUES (:nome, :clube_id, :modalidade_id, :categoria, :treinador)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nome' => $dados['nome'],
            'clube_id' => $dados['clube_id'] ?? null,
            'modalidade_id' => $dados['modalidade_id'],
            'categoria' => $dados['categoria'] ?? null,
            'treinador' => $dados['treinador'] ?? null
        ]);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $dados) {
        $sql = "UPDATE cong_equipas SET nome = :nome, clube_id = :clube_id,
                modalidade_id = :modalidade_id, categoria = :categoria, treinador = :treinador
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nome' => $dados['nome'],
            'clube_id' => $dados['clube_id'] ?? null,
            'modalidade_id' => $dados['modalidade_id'],
            'categoria' => $dados['categoria'] ?? null,
            'treinador' => $dados['treinador'] ?? null
        ]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM cong_equipas WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function adicionarAtleta($equipaId, $atletaId, $dataEntrada = null) {
        $sql = "INSERT INTO cong_equipa_atletas (equipa_id, atleta_id, data_entrada, status)
                VALUES (:equipa_id, :atleta_id, :data_entrada, 'ativo')";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'equipa_id' => $equipaId,
            'atleta_id' => $atletaId,
            'data_entrada' => $dataEntrada ?? date('Y-m-d')
        ]);
    }

    public function removerAtleta($equipaId, $atletaId) {
        $sql = "UPDATE cong_equipa_atletas SET status = 'inativo', data_saida = CURDATE()
                WHERE equipa_id = :equipa_id AND atleta_id = :atleta_id AND status = 'ativo'";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['equipa_id' => $equipaId, 'atleta_id' => $atletaId]);
    }

    public function getAtletas($equipaId) {
        $sql = "SELECT a.id, a.kongo_id, a.nome_completo, a.fotografia, a.status_licenca,
                ea.data_entrada, ea.status as status_equipa
                FROM cong_equipa_atletas ea
                JOIN cong_atletas a ON ea.atleta_id = a.id
                WHERE ea.equipa_id = :equipa_id AND ea.status = 'ativo'
                ORDER BY a.nome_completo ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['equipa_id' => $equipaId]);
        return $stmt->fetchAll();
    }
}
?>
