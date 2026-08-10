<?php
require_once __DIR__ . '/../models/Licenca.php';
require_once __DIR__ . '/../middleware/Auth.php';

class LicencaController {
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
        if (!$this->requerGestao()) return;
        $licenca = new Licenca();
        echo json_encode($licenca->getAll($_GET['status'] ?? null));
    }

    public function porAtleta($atletaId) {
        $licenca = new Licenca();
        $resultado = $licenca->getByAtleta($atletaId);
        if ($resultado) {
            echo json_encode($resultado);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Licença não encontrada']);
        }
    }

    public function renovar($atletaId) {
        if (!$this->requerGestao()) return;
        $dados = json_decode(file_get_contents('php://input'), true);
        $licenca = new Licenca();
        $ok = $licenca->renovar($atletaId, $dados['meses'] ?? 12);
        echo json_encode(['success' => $ok]);
    }

    public function atualizarExpiradas() {
        if (!$this->requerGestao()) return;
        $licenca = new Licenca();
        $licenca->atualizarExpiradas();
        echo json_encode(['success' => true]);
    }
}
?>
