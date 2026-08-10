<?php
require_once __DIR__ . '/../models/Clube.php';
require_once __DIR__ . '/../middleware/Auth.php';

class ClubeController {
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
        $clube = new Clube();
        echo json_encode($clube->getAll());
    }

    public function detalhes($id) {
        $clube = new Clube();
        $resultado = $clube->getById($id);
        if ($resultado) {
            echo json_encode($resultado);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Clube não encontrado']);
        }
    }

    public function criar() {
        if (!$this->requerGestao()) return;
        $dados = json_decode(file_get_contents('php://input'), true);
        if (empty($dados['nome'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Campo nome é obrigatório']);
            return;
        }
        $clube = new Clube();
        $id = $clube->create($dados);
        echo json_encode(['success' => true, 'id' => $id]);
    }

    public function atualizar($id) {
        if (!$this->requerGestao()) return;
        $dados = json_decode(file_get_contents('php://input'), true);
        $clube = new Clube();
        $ok = $clube->update($id, $dados);
        echo json_encode(['success' => $ok]);
    }

    public function apagar($id) {
        if (!$this->requerGestao()) return;
        $clube = new Clube();
        $ok = $clube->delete($id);
        echo json_encode(['success' => $ok]);
    }
}
?>
