<?php
require_once __DIR__ . '/../models/Modalidade.php';
require_once __DIR__ . '/../middleware/Auth.php';

class ModalidadeController {
    private $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function listar() {
        $modalidade = new Modalidade();
        echo json_encode($modalidade->getAll());
    }

    public function detalhes($id) {
        $modalidade = new Modalidade();
        $resultado = $modalidade->getById($id);
        if ($resultado) {
            echo json_encode($resultado);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Modalidade não encontrada']);
        }
    }

    public function criar() {
        $usuario = $this->auth->verify();
        if (!$usuario || $usuario['tipo'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Apenas administradores podem criar modalidades']);
            return;
        }
        $dados = json_decode(file_get_contents('php://input'), true);
        if (empty($dados['nome'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Campo nome é obrigatório']);
            return;
        }
        $modalidade = new Modalidade();
        $id = $modalidade->create($dados);
        echo json_encode(['success' => true, 'id' => $id]);
    }

    public function atualizar($id) {
        $usuario = $this->auth->verify();
        if (!$usuario || $usuario['tipo'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Apenas administradores podem editar modalidades']);
            return;
        }
        $dados = json_decode(file_get_contents('php://input'), true);
        $modalidade = new Modalidade();
        $ok = $modalidade->update($id, $dados);
        echo json_encode(['success' => $ok]);
    }

    public function alternarStatus($id) {
        $usuario = $this->auth->verify();
        if (!$usuario || $usuario['tipo'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Apenas administradores podem alterar o estado']);
            return;
        }
        $modalidade = new Modalidade();
        $ok = $modalidade->toggleStatus($id);
        echo json_encode(['success' => $ok]);
    }
}
?>
