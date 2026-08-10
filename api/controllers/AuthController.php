<?php
require_once __DIR__ . '/../models/Utilizador.php';

header('Content-Type: application/json');

class AuthController {
    public function login() {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if (empty($dados['email']) || empty($dados['senha'])) {
            echo json_encode(['success' => false, 'message' => 'Email e senha são obrigatórios']);
            return;
        }
        
        $utilizador = new Utilizador();
        $resultado = $utilizador->login($dados['email'], $dados['senha']);
        
        echo json_encode($resultado);
    }
    
    public function register() {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        $camposObrigatorios = ['nome_completo', 'email', 'senha'];
        foreach ($camposObrigatorios as $campo) {
            if (empty($dados[$campo])) {
                echo json_encode(['success' => false, 'message' => "Campo $campo é obrigatório"]);
                return;
            }
        }
        
        $utilizador = new Utilizador();
        $resultado = $utilizador->create($dados);
        
        echo json_encode([
            'success' => $resultado,
            'message' => $resultado ? 'Utilizador criado com sucesso' : 'Erro ao criar utilizador'
        ]);
    }
}

// Roteamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new AuthController();
    
    if (strpos($_SERVER['REQUEST_URI'], '/login') !== false) {
        $controller->login();
    } elseif (strpos($_SERVER['REQUEST_URI'], '/register') !== false) {
        $controller->register();
    }
}
?>