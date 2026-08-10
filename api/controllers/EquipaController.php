<?php
require_once __DIR__ . '/../models/Equipa.php';
require_once __DIR__ . '/../middleware/Auth.php';

class EquipaController {
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
        $equipa = new Equipa();
        echo json_encode($equipa->getAll($_GET));
    }

    public function detalhes($id) {
        $equipa = new Equipa();
        $resultado = $equipa->getById($id);
        if ($resultado) {
            echo json_encode($resultado);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Equipa não encontrada']);
        }
    }

    public function criar() {
        if (!$this->requerGestao()) return;
        $dados = json_decode(file_get_contents('php://input'), true);
        if (empty($dados['nome']) || empty($dados['modalidade_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Campos nome e modalidade_id são obrigatórios']);
            return;
        }
        $equipa = new Equipa();
        $id = $equipa->create($dados);
        echo json_encode(['success' => true, 'id' => $id]);
    }

    public function atualizar($id) {
        if (!$this->requerGestao()) return;
        $dados = json_decode(file_get_contents('php://input'), true);
        $equipa = new Equipa();
        $ok = $equipa->update($id, $dados);
        echo json_encode(['success' => $ok]);
    }

    public function apagar($id) {
        if (!$this->requerGestao()) return;
        $equipa = new Equipa();
        $ok = $equipa->delete($id);
        echo json_encode(['success' => $ok]);
    }

    public function adicionarAtleta($equipaId) {
        if (!$this->requerGestao()) return;
        $dados = json_decode(file_get_contents('php://input'), true);
        if (empty($dados['atleta_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Campo atleta_id é obrigatório']);
            return;
        }
        $equipa = new Equipa();
        $ok = $equipa->adicionarAtleta($equipaId, $dados['atleta_id'], $dados['data_entrada'] ?? null);
        echo json_encode(['success' => $ok]);
    }

    public function removerAtleta($equipaId, $atletaId) {
        if (!$this->requerGestao()) return;
        $equipa = new Equipa();
        $ok = $equipa->removerAtleta($equipaId, $atletaId);
        echo json_encode(['success' => $ok]);
    }
}
?>
