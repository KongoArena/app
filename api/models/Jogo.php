<?php
require_once __DIR__ . '/../config/database.php';

class Jogo {
    private $pdo;

    public function __construct() {
        $this->pdo = getDBConnection();
    }

    public function getAll($filtros = []) {
        $sql = "SELECT j.*, ec.nome as casa_nome, ef.nome as fora_nome, c.nome as competicao_nome
                FROM cong_jogos j
                JOIN cong_equipas ec ON j.equipa_casa_id = ec.id
                JOIN cong_equipas ef ON j.equipa_fora_id = ef.id
                JOIN cong_competicoes c ON j.competicao_id = c.id";
        $condicoes = [];
        $params = [];
        if (!empty($filtros['competicao_id'])) {
            $condicoes[] = "j.competicao_id = :competicao_id";
            $params['competicao_id'] = $filtros['competicao_id'];
        }
        if (!empty($filtros['status'])) {
            $condicoes[] = "j.status = :status";
            $params['status'] = $filtros['status'];
        }
        if (!empty($filtros['equipa_id'])) {
            $condicoes[] = "(j.equipa_casa_id = :equipa_id OR j.equipa_fora_id = :equipa_id2)";
            $params['equipa_id'] = $filtros['equipa_id'];
            $params['equipa_id2'] = $filtros['equipa_id'];
        }
        if (!empty($condicoes)) {
            $sql .= " WHERE " . implode(" AND ", $condicoes);
        }
        $sql .= " ORDER BY j.data_hora DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $sql = "SELECT j.*, ec.nome as casa_nome, ef.nome as fora_nome, c.nome as competicao_nome
                FROM cong_jogos j
                JOIN cong_equipas ec ON j.equipa_casa_id = ec.id
                JOIN cong_equipas ef ON j.equipa_fora_id = ef.id
                JOIN cong_competicoes c ON j.competicao_id = c.id
                WHERE j.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $jogo = $stmt->fetch();
        if ($jogo) {
            $jogo['estatisticas'] = $this->getEstatisticas($id);
        }
        return $jogo;
    }

    public function create($dados) {
        $sql = "INSERT INTO cong_jogos
                (competicao_id, temporada_id, equipa_casa_id, equipa_fora_id, data_hora, local, status)
                VALUES (:competicao_id, :temporada_id, :equipa_casa_id, :equipa_fora_id, :data_hora, :local, 'agendado')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'competicao_id' => $dados['competicao_id'],
            'temporada_id' => $dados['temporada_id'],
            'equipa_casa_id' => $dados['equipa_casa_id'],
            'equipa_fora_id' => $dados['equipa_fora_id'],
            'data_hora' => $dados['data_hora'],
            'local' => $dados['local'] ?? null
        ]);
        return $this->pdo->lastInsertId();
    }

    public function registarResultado($id, $resultado, $status = 'finalizado') {
        $sql = "UPDATE cong_jogos SET resultado = :resultado, status = :status WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'resultado' => json_encode($resultado),
            'status' => $status
        ]);
    }

    public function adicionarEstatistica($jogoId, $atletaId, $equipaId, $tipo, $valor) {
        $sql = "INSERT INTO cong_estatisticas_jogo (jogo_id, atleta_id, equipa_id, tipo_estatistica, valor)
                VALUES (:jogo_id, :atleta_id, :equipa_id, :tipo, :valor)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'jogo_id' => $jogoId,
            'atleta_id' => $atletaId,
            'equipa_id' => $equipaId,
            'tipo' => $tipo,
            'valor' => $valor
        ]);
    }

    public function getEstatisticas($jogoId) {
        $sql = "SELECT es.*, a.nome_completo as atleta_nome, a.kongo_id
                FROM cong_estatisticas_jogo es
                JOIN cong_atletas a ON es.atleta_id = a.id
                WHERE es.jogo_id = :jogo_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['jogo_id' => $jogoId]);
        return $stmt->fetchAll();
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM cong_jogos WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>
