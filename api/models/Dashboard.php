<?php
require_once __DIR__ . '/../config/database.php';

class Dashboard {
    private $pdo;

    public function __construct() {
        $this->pdo = getDBConnection();
    }

    public function getEstatisticas() {
        $stats = [];

        $stats['total_atletas'] = $this->contar('cong_atletas');
        $stats['total_clubes'] = $this->contar('cong_clubes');
        $stats['total_equipas'] = $this->contar('cong_equipas');
        $stats['total_competicoes'] = $this->contar('cong_competicoes');
        $stats['competicoes_ativas'] = $this->contar('cong_competicoes', "status = 'ativa'");
        $stats['licencas_ativas'] = $this->contar('cong_licencas', "status = 'ativa'");
        $stats['licencas_renovacao'] = $this->contar('cong_licencas', "status = 'renovacao'");
        $stats['licencas_expiradas'] = $this->contar('cong_licencas', "status = 'expirada'");
        $stats['total_jogos'] = $this->contar('cong_jogos');
        $stats['jogos_finalizados'] = $this->contar('cong_jogos', "status = 'finalizado'");

        $stmt = $this->pdo->query("SELECT COUNT(DISTINCT cidade) as total FROM cong_atletas WHERE cidade IS NOT NULL AND cidade != ''");
        $stats['provincias_representadas'] = (int) $stmt->fetch()['total'];

        $stmtMod = $this->pdo->query("SELECT m.nome, COUNT(am.atleta_id) as total
                FROM cong_modalidades m
                LEFT JOIN cong_atleta_modalidades am ON m.id = am.modalidade_id
                GROUP BY m.id ORDER BY total DESC");
        $stats['atletas_por_modalidade'] = $stmtMod->fetchAll();

        return $stats;
    }

    private function contar($tabela, $where = null) {
        $sql = "SELECT COUNT(*) as total FROM $tabela";
        if ($where) $sql .= " WHERE $where";
        $stmt = $this->pdo->query($sql);
        return (int) $stmt->fetch()['total'];
    }
}
?>
