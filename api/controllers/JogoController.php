<?php
require_once __DIR__ . '/../models/Jogo.php';
require_once __DIR__ . '/../middleware/Auth.php';

class JogoController {
    private $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    private function requerGestao() {
        $usuario = $this->auth->verify();
        if (!$usuario || !in_array($usuario['tipo'], ['admin', 'gestor'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Sem permissão']);
            return false;
        }
        return true;
    }

    public function listar() {
        $jogo = new Jogo();
        echo json_encode($jogo->getAll($_GET));
    }

    public function detalhes($id) {
        $jogo = new Jogo();
        $resultado = $jogo->getById($id);
        if ($resultado) {
            echo json_encode($resultado);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Jogo não encontrado']);
        }
    }

    public function criar() {
        if (!$this->requerGestao()) return;
        $dados = json_decode(file_get_contents('php://input'), true);
        $obrigatorios = ['competicao_id', 'temporada_id', 'equipa_casa_id', 'equipa_fora_id', 'data_hora'];
        foreach ($obrigatorios as $campo) {
            if (empty($dados[$campo])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Campo $campo é obrigatório"]);
                return;
            }
        }
        $jogo = new Jogo();
        $id = $jogo->create($dados);
        echo json_encode(['success' => true, 'id' => $id]);
    }

    public function registarResultado($id) {
        if (!$this->requerGestao()) return;
        $dados = json_decode(file_get_contents('php://input'), true);
        if (empty($dados['resultado'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Campo resultado é obrigatório (objeto JSON)']);
            return;
        }
        $jogo = new Jogo();
        $ok = $jogo->registarResultado($id, $dados['resultado'], $dados['status'] ?? 'finalizado');

        if (!empty($dados['estatisticas']) && is_array($dados['estatisticas'])) {
            foreach ($dados['estatisticas'] as $est) {
                if (isset($est['atleta_id'], $est['equipa_id'], $est['tipo'], $est['valor'])) {
                    $jogo->adicionarEstatistica($id, $est['atleta_id'], $est['equipa_id'], $est['tipo'], $est['valor']);
                }
            }
        }

        echo json_encode(['success' => $ok]);
    }

    public function apagar($id) {
        if (!$this->requerGestao()) return;
        $jogo = new Jogo();
        $ok = $jogo->delete($id);
        echo json_encode(['success' => $ok]);
    }
}
?>
