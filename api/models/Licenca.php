<?php
require_once __DIR__ . '/../config/database.php';

class Licenca {
    private $pdo;

    public function __construct() {
        $this->pdo = getDBConnection();
    }

    public function getByAtleta($atletaId) {
        $stmt = $this->pdo->prepare("SELECT * FROM cong_licencas WHERE atleta_id = :atleta_id");
        $stmt->execute(['atleta_id' => $atletaId]);
        return $stmt->fetch();
    }

    public function renovar($atletaId, $meses = 12) {
        $sql = "UPDATE cong_licencas SET
                data_emissao = CURDATE(),
                data_expiracao = DATE_ADD(CURDATE(), INTERVAL :meses MONTH),
                status = 'ativa'
                WHERE atleta_id = :atleta_id";
        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute(['atleta_id' => $atletaId, 'meses' => $meses]);

        $stmtAtleta = $this->pdo->prepare("UPDATE cong_atletas SET status_licenca = 'ativa' WHERE id = :id");
        $stmtAtleta->execute(['id' => $atletaId]);

        return $ok;
    }

    public function marcarEmRenovacao($atletaId) {
        $stmt = $this->pdo->prepare("UPDATE cong_licencas SET status = 'renovacao' WHERE atleta_id = :atleta_id");
        $stmt->execute(['atleta_id' => $atletaId]);
        $stmtAtleta = $this->pdo->prepare("UPDATE cong_atletas SET status_licenca = 'renovacao' WHERE id = :id");
        return $stmtAtleta->execute(['id' => $atletaId]);
    }

    // Marca como expiradas todas as licenças cuja data já passou.
    // Deve ser chamado periodicamente (ex: cron diário) ou sob pedido no admin.
    public function atualizarExpiradas() {
        $sql = "UPDATE cong_licencas SET status = 'expirada'
                WHERE data_expiracao < CURDATE() AND status != 'expirada'";
        $stmt = $this->pdo->query($sql);

        $sqlAtleta = "UPDATE cong_atletas a
                JOIN cong_licencas l ON a.id = l.atleta_id
                SET a.status_licenca = 'expirada'
                WHERE l.status = 'expirada' AND a.status_licenca != 'expirada'";
        $this->pdo->query($sqlAtleta);

        return true;
    }

    public function getAll($filtroStatus = null) {
        $sql = "SELECT l.*, a.nome_completo, a.kongo_id
                FROM cong_licencas l
                JOIN cong_atletas a ON l.atleta_id = a.id";
        $params = [];
        if ($filtroStatus) {
            $sql .= " WHERE l.status = :status";
            $params['status'] = $filtroStatus;
        }
        $sql .= " ORDER BY l.data_expiracao ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
?>
