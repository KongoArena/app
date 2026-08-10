<?php
require_once __DIR__ . '/../models/Competicao.php';
require_once __DIR__ . '/../middleware/Auth.php';

class CompeticaoController {
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
        $competicao = new Competicao();
        echo json_encode($competicao->getAll($_GET));
    }

    public function detalhes($id) {
        $competicao = new Competicao();
        $resultado = $competicao->getById($id);
        if ($resultado) {
            echo json_encode($resultado);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Competição não encontrada']);
        }
    }

    public function criar() {
        if (!$this->requerGestao()) return;
        $dados = json_decode(file_get_contents('php://input'), true);
        $obrigatorios = ['nome', 'modalidade_id', 'temporada_id'];
        foreach ($obrigatorios as $campo) {
            if (empty($dados[$campo])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Campo $campo é obrigatório"]);
                return;
            }
        }
        $competicao = new Competicao();
        $id = $competicao->create($dados);
        echo json_encode(['success' => true, 'id' => $id]);
    }

    public function atualizar($id) {
        if (!$this->requerGestao()) return;
        $dados = json_decode(file_get_contents('php://input'), true);
        $competicao = new Competicao();
        $ok = $competicao->update($id, $dados);
        echo json_encode(['success' => $ok]);
    }

    public function adicionarEquipa($competicaoId) {
        if (!$this->requerGestao()) return;
        $dados = json_decode(file_get_contents('php://input'), true);
        if (empty($dados['equipa_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Campo equipa_id é obrigatório']);
            return;
        }
        $competicao = new Competicao();
        $ok = $competicao->adicionarEquipa($competicaoId, $dados['equipa_id']);
        echo json_encode(['success' => $ok]);
    }

    public function removerEquipa($competicaoId, $equipaId) {
        if (!$this->requerGestao()) return;
        $competicao = new Competicao();
        $ok = $competicao->removerEquipa($competicaoId, $equipaId);
        echo json_encode(['success' => $ok]);
    }

    public function classificacao($competicaoId) {
        $competicao = new Competicao();
        echo json_encode($competicao->getClassificacao($competicaoId));
    }
}
?>
