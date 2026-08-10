<?php
require_once __DIR__ . '/../models/Ranking.php';
require_once __DIR__ . '/../middleware/Auth.php';

class RankingController {
    private $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function porCompeticao($competicaoId) {
        $ranking = new Ranking();
        echo json_encode($ranking->getPorCompeticao($competicaoId));
    }

    public function porModalidade($modalidadeId) {
        $ranking = new Ranking();
        echo json_encode($ranking->getPorModalidade($modalidadeId));
    }

    public function recalcular($competicaoId) {
        $usuario = $this->auth->verify();
        if (!$usuario || !in_array($usuario['tipo'], ['admin', 'gestor'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Sem permissão']);
            return;
        }
        $dados = json_decode(file_get_contents('php://input'), true);
        if (empty($dados['modalidade_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Campo modalidade_id é obrigatório']);
            return;
        }
        $ranking = new Ranking();
        $total = $ranking->recalcular($competicaoId, $dados['modalidade_id']);
        echo json_encode(['success' => true, 'atletas_atualizados' => $total]);
    }
}
?>
