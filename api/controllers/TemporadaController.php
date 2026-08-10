<?php
require_once __DIR__ . '/../models/Temporada.php';
require_once __DIR__ . '/../middleware/Auth.php';

class TemporadaController {
    private $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function listar() {
        $temporada = new Temporada();
        echo json_encode($temporada->getAll());
    }

    public function criar() {
        $usuario = $this->auth->verify();
        if (!$usuario || $usuario['tipo'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Apenas administradores podem criar temporadas']);
            return;
        }
        $dados = json_decode(file_get_contents('php://input'), true);
        if (empty($dados['nome'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Campo nome é obrigatório']);
            return;
        }
        $temporada = new Temporada();
        $id = $temporada->create($dados);
        echo json_encode(['success' => true, 'id' => $id]);
    }

    public function atualizar($id) {
        $usuario = $this->auth->verify();
        if (!$usuario || $usuario['tipo'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Apenas administradores podem editar temporadas']);
            return;
        }
        $dados = json_decode(file_get_contents('php://input'), true);
        $temporada = new Temporada();
        $ok = $temporada->update($id, $dados);
        echo json_encode(['success' => $ok]);
    }
}
?>
