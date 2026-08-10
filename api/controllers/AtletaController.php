<?php
require_once __DIR__ . '/../models/Atleta.php';
require_once __DIR__ . '/../middleware/Auth.php';

header('Content-Type: application/json');

class AtletaController {
    private $auth;
    
    public function __construct() {
        $this->auth = new Auth();
    }
    
    public function listar() {
        // Verificar autenticação
        $usuario = $this->auth->verify();
        if (!$usuario) {
            http_response_code(401);
            echo json_encode(['error' => 'Não autenticado']);
            return;
        }
        
        $filtros = $_GET;
        $atleta = new Atleta();
        $resultado = $atleta->getAll($filtros);
        
        echo json_encode($resultado);
    }
    
    public function criar() {
        $usuario = $this->auth->verify();
        if (!$usuario) {
            http_response_code(401);
            echo json_encode(['error' => 'Não autenticado']);
            return;
        }
        
        // Verificar permissão (admin ou gestor)
        if (!in_array($usuario['tipo'], ['admin', 'gestor'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Sem permissão']);
            return;
        }
        
        $dados = json_decode(file_get_contents('php://input'), true);
        
        $camposObrigatorios = ['nome_completo', 'data_nascimento'];
        foreach ($camposObrigatorios as $campo) {
            if (empty($dados[$campo])) {
                echo json_encode(['success' => false, 'message' => "Campo $campo é obrigatório"]);
                return;
            }
        }
        
        $atleta = new Atleta();
        $resultado = $atleta->create($dados);
        
        echo json_encode([
            'success' => true,
            'data' => $resultado,
            'message' => 'Atleta criado com sucesso'
        ]);
    }
    
    public function detalhes($id) {
        $usuario = $this->auth->verify();
        if (!$usuario) {
            http_response_code(401);
            echo json_encode(['error' => 'Não autenticado']);
            return;
        }
        
        $atleta = new Atleta();
        $resultado = $atleta->getById($id);
        
        if ($resultado) {
            echo json_encode($resultado);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Atleta não encontrado']);
        }
    }

    public function atualizar($id) {
        $usuario = $this->auth->verify();
        if (!$usuario) {
            http_response_code(401);
            echo json_encode(['error' => 'Não autenticado']);
            return;
        }

        // Admin/gestor podem editar qualquer atleta; o próprio atleta só edita o seu perfil
        $atleta = new Atleta();
        $existente = $atleta->getById($id);
        if (!$existente) {
            http_response_code(404);
            echo json_encode(['error' => 'Atleta não encontrado']);
            return;
        }
        $podeEditar = in_array($usuario['tipo'], ['admin', 'gestor'])
            || ($usuario['tipo'] === 'atleta' && $existente['utilizador_id'] == $usuario['user_id']);
        if (!$podeEditar) {
            http_response_code(403);
            echo json_encode(['error' => 'Sem permissão para editar este atleta']);
            return;
        }

        $dados = json_decode(file_get_contents('php://input'), true);
        $ok = $atleta->update($id, $dados);
        echo json_encode(['success' => $ok]);
    }

    public function adicionarModalidade($id) {
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
        $atleta = new Atleta();
        $ok = $atleta->addModalidade($id, $dados['modalidade_id'], $dados['posicao'] ?? null);
        echo json_encode(['success' => $ok]);
    }
}
?>
