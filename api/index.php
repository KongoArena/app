<?php
// Ponte simples: redireciona para a API real
require __DIR__ . '/../../api/index.php';

// api/index.php - Roteamento central via parâmetro GET "rota" (?rota=...)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Autoload simples
spl_autoload_register(function ($class) {
    $paths = ['models/', 'controllers/', 'middleware/'];
    foreach ($paths as $path) {
        $file = __DIR__ . '/' . $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

$rota = $_GET['rota'] ?? '';
$metodo = $_SERVER['REQUEST_METHOD'];
$partes = array_values(array_filter(explode('/', $rota), fn($p) => $p !== ''));
$recurso = $partes[0] ?? '';
$id1 = $partes[1] ?? null;
$sub = $partes[2] ?? null;
$id2 = $partes[3] ?? null;

// Rota padrão de teste / documentação
if (empty($rota)) {
    echo json_encode([
        'status' => 'ok',
        'message' => 'API Kongo Arena funcionando!',
        'rotas_disponiveis' => [
            'AUTENTICAÇÃO' => [
                'POST ?rota=login', 'POST ?rota=register'
            ],
            'ATLETAS' => [
                'GET ?rota=atletas', 'POST ?rota=atletas', 'GET ?rota=atletas/ID',
                'PUT ?rota=atletas/ID', 'POST ?rota=atletas/ID/modalidades'
            ],
            'MODALIDADES' => [
                'GET ?rota=modalidades', 'POST ?rota=modalidades', 'GET ?rota=modalidades/ID',
                'PUT ?rota=modalidades/ID', 'POST ?rota=modalidades/ID/status'
            ],
            'CLUBES' => [
                'GET ?rota=clubes', 'POST ?rota=clubes', 'GET ?rota=clubes/ID',
                'PUT ?rota=clubes/ID', 'DELETE ?rota=clubes/ID'
            ],
            'EQUIPAS' => [
                'GET ?rota=equipas', 'POST ?rota=equipas', 'GET ?rota=equipas/ID',
                'PUT ?rota=equipas/ID', 'DELETE ?rota=equipas/ID',
                'POST ?rota=equipas/ID/atletas', 'DELETE ?rota=equipas/ID/atletas/ATLETA_ID'
            ],
            'TEMPORADAS' => [
                'GET ?rota=temporadas', 'POST ?rota=temporadas', 'PUT ?rota=temporadas/ID'
            ],
            'COMPETIÇÕES' => [
                'GET ?rota=competicoes', 'POST ?rota=competicoes', 'GET ?rota=competicoes/ID',
                'PUT ?rota=competicoes/ID', 'POST ?rota=competicoes/ID/equipas',
                'DELETE ?rota=competicoes/ID/equipas/EQUIPA_ID', 'GET ?rota=competicoes/ID/classificacao'
            ],
            'JOGOS' => [
                'GET ?rota=jogos', 'POST ?rota=jogos', 'GET ?rota=jogos/ID',
                'PUT ?rota=jogos/ID/resultado', 'DELETE ?rota=jogos/ID'
            ],
            'RANKINGS' => [
                'GET ?rota=rankings/competicao/ID', 'GET ?rota=rankings/modalidade/ID',
                'POST ?rota=rankings/competicao/ID/recalcular'
            ],
            'LICENÇAS' => [
                'GET ?rota=licencas', 'GET ?rota=licencas/ID_ATLETA',
                'POST ?rota=licencas/ID_ATLETA/renovar', 'POST ?rota=licencas/atualizar-expiradas'
            ],
            'DASHBOARD' => [
                'GET ?rota=dashboard/estatisticas'
            ]
        ]
    ]);
    exit;
}

// =============================================
// AUTENTICAÇÃO
// =============================================
if ($metodo === 'POST' && $rota === 'login') {
    (new AuthController())->login(); exit;
}
if ($metodo === 'POST' && $rota === 'register') {
    (new AuthController())->register(); exit;
}

// =============================================
// ATLETAS
// =============================================
if ($recurso === 'atletas') {
    $c = new AtletaController();
    if ($metodo === 'GET' && !$id1) { $c->listar(); exit; }
    if ($metodo === 'POST' && !$id1) { $c->criar(); exit; }
    if ($metodo === 'GET' && $id1 && !$sub) { $c->detalhes($id1); exit; }
    if ($metodo === 'PUT' && $id1 && !$sub) { $c->atualizar($id1); exit; }
    if ($metodo === 'POST' && $id1 && $sub === 'modalidades') { $c->adicionarModalidade($id1); exit; }
}

// =============================================
// MODALIDADES
// =============================================
if ($recurso === 'modalidades') {
    $c = new ModalidadeController();
    if ($metodo === 'GET' && !$id1) { $c->listar(); exit; }
    if ($metodo === 'POST' && !$id1) { $c->criar(); exit; }
    if ($metodo === 'GET' && $id1 && !$sub) { $c->detalhes($id1); exit; }
    if ($metodo === 'PUT' && $id1 && !$sub) { $c->atualizar($id1); exit; }
    if ($metodo === 'POST' && $id1 && $sub === 'status') { $c->alternarStatus($id1); exit; }
}

// =============================================
// CLUBES
// =============================================
if ($recurso === 'clubes') {
    $c = new ClubeController();
    if ($metodo === 'GET' && !$id1) { $c->listar(); exit; }
    if ($metodo === 'POST' && !$id1) { $c->criar(); exit; }
    if ($metodo === 'GET' && $id1) { $c->detalhes($id1); exit; }
    if ($metodo === 'PUT' && $id1) { $c->atualizar($id1); exit; }
    if ($metodo === 'DELETE' && $id1) { $c->apagar($id1); exit; }
}

// =============================================
// EQUIPAS
// =============================================
if ($recurso === 'equipas') {
    $c = new EquipaController();
    if ($metodo === 'GET' && !$id1) { $c->listar(); exit; }
    if ($metodo === 'POST' && !$id1) { $c->criar(); exit; }
    if ($metodo === 'GET' && $id1 && !$sub) { $c->detalhes($id1); exit; }
    if ($metodo === 'PUT' && $id1 && !$sub) { $c->atualizar($id1); exit; }
    if ($metodo === 'DELETE' && $id1 && !$sub) { $c->apagar($id1); exit; }
    if ($metodo === 'POST' && $id1 && $sub === 'atletas') { $c->adicionarAtleta($id1); exit; }
    if ($metodo === 'DELETE' && $id1 && $sub === 'atletas' && $id2) { $c->removerAtleta($id1, $id2); exit; }
}

// =============================================
// TEMPORADAS
// =============================================
if ($recurso === 'temporadas') {
    $c = new TemporadaController();
    if ($metodo === 'GET' && !$id1) { $c->listar(); exit; }
    if ($metodo === 'POST' && !$id1) { $c->criar(); exit; }
    if ($metodo === 'PUT' && $id1) { $c->atualizar($id1); exit; }
}

// =============================================
// COMPETIÇÕES
// =============================================
if ($recurso === 'competicoes') {
    $c = new CompeticaoController();
    if ($metodo === 'GET' && !$id1) { $c->listar(); exit; }
    if ($metodo === 'POST' && !$id1) { $c->criar(); exit; }
    if ($metodo === 'GET' && $id1 && !$sub) { $c->detalhes($id1); exit; }
    if ($metodo === 'PUT' && $id1 && !$sub) { $c->atualizar($id1); exit; }
    if ($metodo === 'POST' && $id1 && $sub === 'equipas') { $c->adicionarEquipa($id1); exit; }
    if ($metodo === 'DELETE' && $id1 && $sub === 'equipas' && $id2) { $c->removerEquipa($id1, $id2); exit; }
    if ($metodo === 'GET' && $id1 && $sub === 'classificacao') { $c->classificacao($id1); exit; }
}

// =============================================
// JOGOS
// =============================================
if ($recurso === 'jogos') {
    $c = new JogoController();
    if ($metodo === 'GET' && !$id1) { $c->listar(); exit; }
    if ($metodo === 'POST' && !$id1) { $c->criar(); exit; }
    if ($metodo === 'GET' && $id1 && !$sub) { $c->detalhes($id1); exit; }
    if ($metodo === 'PUT' && $id1 && $sub === 'resultado') { $c->registarResultado($id1); exit; }
    if ($metodo === 'DELETE' && $id1 && !$sub) { $c->apagar($id1); exit; }
}

// =============================================
// RANKINGS
// =============================================
if ($recurso === 'rankings') {
    $c = new RankingController();
    if ($metodo === 'GET' && $id1 === 'competicao' && $sub) { $c->porCompeticao($sub); exit; }
    if ($metodo === 'GET' && $id1 === 'modalidade' && $sub) { $c->porModalidade($sub); exit; }
    if ($metodo === 'POST' && $id1 === 'competicao' && $sub && $id2 === 'recalcular') { $c->recalcular($sub); exit; }
}

// =============================================
// LICENÇAS
// =============================================
if ($recurso === 'licencas') {
    $c = new LicencaController();
    if ($metodo === 'GET' && !$id1) { $c->listar(); exit; }
    if ($metodo === 'POST' && $id1 === 'atualizar-expiradas') { $c->atualizarExpiradas(); exit; }
    if ($metodo === 'GET' && $id1 && !$sub) { $c->porAtleta($id1); exit; }
    if ($metodo === 'POST' && $id1 && $sub === 'renovar') { $c->renovar($id1); exit; }
}

// =============================================
// DASHBOARD
// =============================================
if ($recurso === 'dashboard' && $id1 === 'estatisticas' && $metodo === 'GET') {
    (new DashboardController())->estatisticas(); exit;
}

// Se nenhuma rota for encontrada
http_response_code(404);
echo json_encode([
    'error' => 'Rota não encontrada',
    'rota_solicitada' => $rota,
    'metodo' => $metodo
]);
?>
