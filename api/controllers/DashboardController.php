<?php
require_once __DIR__ . '/../models/Dashboard.php';
require_once __DIR__ . '/../middleware/Auth.php';

class DashboardController {
    private $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function estatisticas() {
        $usuario = $this->auth->verify();
        if (!$usuario || !in_array($usuario['tipo'], ['admin', 'gestor'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Sem permissão']);
            return;
        }
        $dashboard = new Dashboard();
        echo json_encode($dashboard->getEstatisticas());
    }
}
?>
