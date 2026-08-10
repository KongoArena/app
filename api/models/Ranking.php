<?php
require_once __DIR__ . '/../config/database.php';

class Ranking {
    private $pdo;

    public function __construct() {
        $this->pdo = getDBConnection();
    }

    public function getPorCompeticao($competicaoId) {
        $sql = "SELECT r.*, a.nome_completo, a.kongo_id, a.fotografia
                FROM cong_ranking_atletas r
                JOIN cong_atletas a ON r.atleta_id = a.id
                WHERE r.competicao_id = :competicao_id
                ORDER BY r.pontos DESC, r.posicao ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['competicao_id' => $competicaoId]);
        return $stmt->fetchAll();
    }

    public function getPorModalidade($modalidadeId) {
        $sql = "SELECT a.id as atleta_id, a.nome_completo, a.kongo_id, a.fotografia,
                SUM(r.pontos) as pontos_totais
                FROM cong_ranking_atletas r
                JOIN cong_atletas a ON r.atleta_id = a.id
                WHERE r.modalidade_id = :modalidade_id
                GROUP BY a.id
                ORDER BY pontos_totais DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['modalidade_id' => $modalidadeId]);
        return $stmt->fetchAll();
    }

    // Recalcula o ranking de uma competição com base nas estatísticas dos jogos.
    // Regra simples e configurável: cada tipo_estatistica soma o seu valor numérico
    // como pontos (ex: golos, pontos, assistencias). Pode evoluir depois com
    // pesos diferentes por modalidade.
    public function recalcular($competicaoId, $modalidadeId) {
        $sql = "SELECT es.atleta_id, SUM(CAST(es.valor AS DECIMAL(10,2))) as pontos
                FROM cong_estatisticas_jogo es
                JOIN cong_jogos j ON es.jogo_id = j.id
                WHERE j.competicao_id = :competicao_id
                AND es.tipo_estatistica IN ('golos', 'pontos', 'assistencias')
                GROUP BY es.atleta_id
                ORDER BY pontos DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['competicao_id' => $competicaoId]);
        $resultados = $stmt->fetchAll();

        $posicao = 1;
        foreach ($resultados as $r) {
            $sqlUpsert = "INSERT INTO cong_ranking_atletas (atleta_id, competicao_id, modalidade_id, pontos, posicao)
                    VALUES (:atleta_id, :competicao_id, :modalidade_id, :pontos, :posicao)
                    ON DUPLICATE KEY UPDATE pontos = :pontos2, posicao = :posicao2";
            $stmtUpsert = $this->pdo->prepare($sqlUpsert);
            $stmtUpsert->execute([
                'atleta_id' => $r['atleta_id'],
                'competicao_id' => $competicaoId,
                'modalidade_id' => $modalidadeId,
                'pontos' => $r['pontos'],
                'posicao' => $posicao,
                'pontos2' => $r['pontos'],
                'posicao2' => $posicao
            ]);
            $posicao++;
        }

        return count($resultados);
    }
}
?>
