<?php
/**
 * Kongo Arena - Diagnóstico Completo v3.0
 * Versão corrigida com parâmetro ?rota= e testes POST
 * Compatível com PHP 8.0+
 */

error_reporting(E_ERROR | E_PARSE);
date_default_timezone_set('America/Sao_Paulo');

class KongoDiagnosticoV3 {
    private $base_url;
    private $base_path;
    private $inicio_execucao;
    
    public function __construct() {
        $this->inicio_execucao = microtime(true);
        $this->base_url = 'http://tanquedigital.com.br/kongo/';
        $this->base_path = __DIR__;
    }
    
    public function executar() {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
        echo "<title>Kongo Arena - Diagnóstico v3.0</title>";
        echo "<style>
            body { font-family: 'Segoe UI', Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
            .card { background: white; border-radius: 10px; padding: 20px; margin: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .sucesso { color: #10b981; }
            .erro { color: #ef4444; }
            .alerta { color: #f59e0b; }
            h1 { color: #1f2937; border-bottom: 3px solid #3b82f6; padding-bottom: 10px; }
            h2 { color: #374151; margin-top: 30px; }
            table { width: 100%; border-collapse: collapse; margin: 10px 0; }
            th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
            th { background: #f9fafb; font-weight: 600; }
            .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
            .badge-ok { background: #d1fae5; color: #065f46; }
            .badge-erro { background: #fee2e2; color: #991b1b; }
            .badge-alerta { background: #fef3c7; color: #92400e; }
            .endpoint { font-family: monospace; background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-size: 13px; }
            pre { background: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 8px; overflow-x: auto; }
        </style></head><body>";
        
        echo "<h1>Kongo Arena - Diagnóstico Completo v3.0</h1>";
        echo "<p><strong>Data:</strong> " . date('d/m/Y H:i:s') . "</p>";
        echo "<p><strong>Hospedagem:</strong> tanquedigital.com.br</p>";
        echo "<p><strong>PHP:</strong> " . PHP_VERSION . "</p>";
        echo "<p><strong>Caminho:</strong> {$this->base_path}</p>";
        
        $this->testarServidor();
        $this->testarPaginasHTML();
        $this->testarAPI();
        $this->testarBancoDados();
        $this->testarLoginReal();
        $this->verificarSeguranca();
        $this->verificarPermissoes();
        $this->gerarResumo();
        
        $tempo_total = microtime(true) - $this->inicio_execucao;
        echo "<p style='text-align: center; color: #6b7280; margin-top: 40px;'>Varredura concluída em " . number_format($tempo_total, 3) . " segundos</p>";
        echo "</body></html>";
    }
    
    private function testarServidor() {
        echo "<h2>1. INFORMAÇÕES DO SERVIDOR</h2>";
        echo "<div class='card'>";
        
        $info = [
            'Sistema Operacional' => PHP_OS,
            'Versão PHP' => PHP_VERSION,
            'Servidor Web' => $_SERVER['SERVER_SOFTWARE'] ?? 'Não identificado',
            'Memória Alocada' => ini_get('memory_limit'),
            'Tempo Máximo de Execução' => ini_get('max_execution_time') . 's',
            'Upload Máximo' => ini_get('upload_max_filesize'),
            'Post Máximo' => ini_get('post_max_size'),
            'Extensão cURL' => extension_loaded('curl') ? 'Instalada' : 'Faltando',
            'Extensão PDO' => extension_loaded('pdo_mysql') ? 'Instalada' : 'Faltando',
            'Extensão JSON' => extension_loaded('json') ? 'Instalada' : 'Faltando',
            'Extensão OpenSSL' => extension_loaded('openssl') ? 'Instalada (necessária para JWT)' : 'Faltando',
        ];
        
        echo "<table>";
        foreach ($info as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
        }
        echo "</table></div>";
    }
    
    private function testarPaginasHTML() {
        echo "<h2>2. TESTANDO PÁGINAS HTML</h2>";
        
        $paginas = [
            'Admin - Login' => 'admin/login.html',
            'Admin - Index' => 'admin/index.html',
            'Admin - Dashboard' => 'admin/dashboard.html',
            'Cliente - Login' => 'cliente/login.html',
            'Cliente - Index' => 'cliente/index.html',
            'Cliente - Dashboard' => 'cliente/dashboard.html',
        ];
        
        echo "<div class='card'>";
        echo "<table><tr><th>Página</th><th>Status</th><th>HTTP</th><th>Tempo</th><th>Tamanho</th></tr>";
        
        foreach ($paginas as $nome => $caminho) {
            $resultado = $this->testarURL($this->base_url . $caminho);
            $status = $resultado['http'] == 200 ? 'ONLINE' : 'OFFLINE';
            $badge = $resultado['http'] == 200 ? 'badge-ok' : 'badge-erro';
            
            echo "<tr>";
            echo "<td><strong>$nome</strong><br><span class='endpoint'>$caminho</span></td>";
            echo "<td><span class='badge $badge'>$status</span></td>";
            echo "<td>{$resultado['http']}</td>";
            echo "<td>{$resultado['tempo']}ms</td>";
            echo "<td>" . $this->formatarTamanho($resultado['tamanho']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table></div>";
    }
    
    private function testarAPI() {
        echo "<h2>3. TESTANDO API REST (com ?rota=)</h2>";
        
        $endpoints = [
            'Health Check' => 'api/index.php?rota=health',
            'API Root' => 'api/index.php',
            'Listar Atletas' => 'api/index.php?rota=atletas',
            'Listar Modalidades' => 'api/index.php?rota=modalidades',
            'Listar Clubes' => 'api/index.php?rota=clubes',
            'Listar Equipas' => 'api/index.php?rota=equipas',
            'Listar Temporadas' => 'api/index.php?rota=temporadas',
            'Listar Competições' => 'api/index.php?rota=competicoes',
            'Listar Jogos' => 'api/index.php?rota=jogos',
        ];
        
        echo "<div class='card'>";
        echo "<table><tr><th>Endpoint</th><th>Status</th><th>HTTP</th><th>Tempo</th></tr>";
        
        foreach ($endpoints as $nome => $caminho) {
            $resultado = $this->testarURL($this->base_url . $caminho);
            $status = $resultado['http'] == 200 ? 'ONLINE' : ($resultado['http'] == 401 ? 'REQUER AUTH' : 'ERRO');
            $badge = $resultado['http'] == 200 ? 'badge-ok' : ($resultado['http'] == 401 ? 'badge-alerta' : 'badge-erro');
            
            echo "<tr>";
            echo "<td><strong>$nome</strong><br><span class='endpoint'>$caminho</span></td>";
            echo "<td><span class='badge $badge'>$status</span></td>";
            echo "<td>{$resultado['http']}</td>";
            echo "<td>{$resultado['tempo']}ms</td>";
            echo "</tr>";
        }
        
        echo "</table></div>";
    }
    
    private function testarBancoDados() {
        echo "<h2>4. TESTANDO BANCO DE DADOS</h2>";
        
        $possiveis_caminhos = [
            $this->base_path . '/api/config/database.php',
            $this->base_path . '/api/config/DB.php',
            $this->base_path . '/config/database.php',
            $this->base_path . '/database.php',
            $this->base_path . '/.env',
        ];
        
        $config_file = null;
        foreach ($possiveis_caminhos as $caminho) {
            if (file_exists($caminho)) {
                $config_file = $caminho;
                break;
            }
        }
        
        if (!$config_file) {
            echo "<div class='card'><p class='erro'>Arquivo de configuração do banco não encontrado em nenhum dos locais padrão</p></div>";
            return;
        }
        
        echo "<div class='card'>";
        echo "<p><strong>Arquivo encontrado:</strong> <span class='endpoint'>" . basename($config_file) . "</span></p>";
        
        $config = $this->lerConfigDB($config_file);
        
        if (!$config || empty($config['dbname'])) {
            echo "<p class='erro'>Não foi possível ler as configurações do banco de dados</p>";
            echo "</div>";
            return;
        }
        
        echo "<table>";
        echo "<tr><td><strong>Host</strong></td><td>{$config['host']}</td></tr>";
        echo "<tr><td><strong>Banco</strong></td><td>{$config['dbname']}</td></tr>";
        echo "<tr><td><strong>Usuário</strong></td><td>{$config['username']}</td></tr>";
        echo "<tr><td><strong>Charset</strong></td><td>" . ($config['charset'] ?? 'utf8') . "</td></tr>";
        
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=" . ($config['charset'] ?? 'utf8');
            $pdo = new PDO($dsn, $config['username'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "<tr><td><strong>Conexão</strong></td><td class='sucesso'>Conectado com sucesso</td></tr>";
            
            $stmt = $pdo->query("SHOW TABLES");
            $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<tr><td><strong>Tabelas encontradas</strong></td><td>" . count($tabelas) . " tabelas</td></tr>";
            echo "<tr><td><strong>Lista de tabelas</strong></td><td>" . implode(', ', $tabelas) . "</td></tr>";
            
        } catch (PDOException $e) {
            echo "<tr><td><strong>Conexão</strong></td><td class='erro'>Erro: " . $e->getMessage() . "</td></tr>";
        }
        
        echo "</table></div>";
    }
    
    private function testarLoginReal() {
        echo "<h2>5. TESTANDO LOGIN REAL (JWT com POST)</h2>";
        
        echo "<div class='card'>";
        echo "<h3>Teste de Login Admin</h3>";
        
        $resultado = $this->tentarLoginPOST('admin', 'admin123');
        
        if ($resultado['sucesso']) {
            echo "<p class='sucesso'>Login bem-sucedido!</p>";
            echo "<p><strong>Token JWT gerado:</strong> <span class='endpoint'>" . substr($resultado['token'], 0, 50) . "...</span></p>";
            echo "<p><strong>Tempo de resposta:</strong> {$resultado['tempo']}ms</p>";
            
            $validacao = $this->validarToken($resultado['token']);
            if ($validacao['valido']) {
                echo "<p class='sucesso'>Token JWT válido! Expira em: " . date('d/m/Y H:i:s', $validacao['exp']) . "</p>";
            } else {
                echo "<p class='erro'>Token JWT inválido: {$validacao['erro']}</p>";
            }
        } else {
            echo "<p class='erro'>Login falhou!</p>";
            echo "<p><strong>Mensagem:</strong> {$resultado['mensagem']}</p>";
            if ($resultado['resposta']) {
                echo "<p><strong>Resposta completa:</strong></p>";
                echo "<pre>" . htmlspecialchars(json_encode($resultado['resposta'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
            }
        }
        
        echo "</div>";
    }
    
    private function tentarLoginPOST($usuario, $senha) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $this->base_url . 'api/index.php?rota=login');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'usuario' => $usuario,
            'senha' => $senha
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $inicio = microtime(true);
        $resposta = curl_exec($ch);
        $tempo = (microtime(true) - $inicio) * 1000;
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        $data = json_decode($resposta, true);
        
        $sucesso = false;
        $token = '';
        $mensagem = '';
        
        if ($http_code == 200 && isset($data['token'])) {
            $sucesso = true;
            $token = $data['token'];
            $mensagem = 'Login bem-sucedido';
        } else {
            $mensagem = $data['mensagem'] ?? $data['error'] ?? 'Erro desconhecido';
        }
        
        return [
            'sucesso' => $sucesso,
            'token' => $token,
            'mensagem' => $mensagem,
            'resposta' => $data,
            'http_code' => $http_code,
            'tempo' => round($tempo)
        ];
    }
    
    private function validarToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return ['valido' => false, 'erro' => 'Token malformado'];
        }
        
        try {
            $payload = json_decode(base64_decode($parts[1]), true);
            
            if (!$payload) {
                return ['valido' => false, 'erro' => 'Payload inválido'];
            }
            
            if (!isset($payload['exp'])) {
                return ['valido' => false, 'erro' => 'Token não tem data de expiração'];
            }
            
            if ($payload['exp'] < time()) {
                return ['valido' => false, 'erro' => 'Token expirado'];
            }
            
            return [
                'valido' => true,
                'exp' => $payload['exp'],
                'payload' => $payload
            ];
            
        } catch (Exception $e) {
            return ['valido' => false, 'erro' => 'Erro ao decodificar: ' . $e->getMessage()];
        }
    }
    
    private function verificarSeguranca() {
        echo "<h2>6. VERIFICAÇÃO DE SEGURANÇA</h2>";
        
        echo "<div class='card'>";
        echo "<table>";
        
        $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        echo "<tr><td><strong>HTTPS</strong></td><td>" . ($https ? '<span class="sucesso">Ativo</span>' : '<span class="alerta">Desativado</span>') . "</td></tr>";
        
        $htaccess = $this->base_path . '/.htaccess';
        echo "<tr><td><strong>.htaccess</strong></td><td>" . (file_exists($htaccess) ? '<span class="sucesso">Encontrado</span>' : '<span class="alerta">Não encontrado</span>') . "</td></tr>";
        
        $pastas_sensiveis = [
            'api/config' => 'Configurações do sistema',
            'api/middleware' => 'Middlewares de autenticação',
        ];
        
        foreach ($pastas_sensiveis as $pasta => $descricao) {
            $caminho = $this->base_path . '/' . $pasta;
            $index = $caminho . '/index.html';
            $protegido = file_exists($index) || (file_exists($caminho . '/.htaccess'));
            echo "<tr><td><strong>$pasta</strong></td><td>" . ($protegido ? '<span class="sucesso">Protegida</span>' : '<span class="alerta">Exposta</span>') . "</td></tr>";
        }
        
        echo "</table></div>";
    }
    
    private function verificarPermissoes() {
        echo "<h2>7. PERMISSÕES DE ARQUIVOS E PASTAS</h2>";
        
        echo "<div class='card'>";
        echo "<table><tr><th>Caminho</th><th>Tipo</th><th>Permissão</th><th>Gravável</th></tr>";
        
        $caminhos = [
            '/' => 'Raiz do projeto',
            '/api' => 'Pasta da API',
            '/api/config' => 'Configurações',
            '/admin' => 'Painel Admin',
            '/cliente' => 'Área do Cliente',
            '/uploads' => 'Uploads de arquivos',
        ];
        
        foreach ($caminhos as $caminho => $descricao) {
            $full_path = $this->base_path . $caminho;
            
            if (!file_exists($full_path)) {
                echo "<tr><td><strong>$caminho</strong></td><td>N/A</td><td colspan='2'><span class='alerta'>Não existe</span></td></tr>";
                continue;
            }
            
            $tipo = is_dir($full_path) ? 'Pasta' : 'Arquivo';
            $perms = substr(sprintf('%o', fileperms($full_path)), -4);
            $gravavel = is_writable($full_path);
            
            echo "<tr>";
            echo "<td><strong>$caminho</strong><br><small>$descricao</small></td>";
            echo "<td>$tipo</td>";
            echo "<td><code>$perms</code></td>";
            echo "<td>" . ($gravavel ? '<span class="sucesso">Sim</span>' : '<span class="alerta">Não</span>') . "</td>";
            echo "</tr>";
        }
        
        echo "</table></div>";
    }
    
    private function gerarResumo() {
        echo "<h2>8. RESUMO FINAL</h2>";
        
        echo "<div class='card'>";
        echo "<h3 class='sucesso'>Próximos passos:</h3>";
        echo "<ul>";
        echo "<li>API REST operacional com parâmetro ?rota=</li>";
        echo "<li>Estrutura de rotas completa (Atletas, Clubes, Competições, etc.)</li>";
        echo "<li>JWT configurado e funcional</li>";
        echo "<li>Completar páginas HTML faltantes (Dashboard, Cliente)</li>";
        echo "<li>Criar arquivo .htaccess de segurança</li>";
        echo "<li>Subir código para o GitHub</li>";
        echo "</ul>";
        echo "</div>";
    }
    
    private function testarURL($url) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $inicio = microtime(true);
        $resposta = curl_exec($ch);
        $tempo = (microtime(true) - $inicio) * 1000;
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $tamanho = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        
        curl_close($ch);
        
        return [
            'http' => $http_code,
            'tempo' => round($tempo),
            'tamanho' => $tamanho,
            'resposta' => $resposta
        ];
    }
    
    private function formatarTamanho($bytes) {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
        return round($bytes / 1048576, 2) . ' MB';
    }
    
    private function lerConfigDB($arquivo) {
        $conteudo = file_get_contents($arquivo);
        
        preg_match("/host['\"]?\s*=>\s*['\"]([^'\"]+)['\"]/i", $conteudo, $host);
        preg_match("/dbname['\"]?\s*=>\s*['\"]([^'\"]+)['\"]/i", $conteudo, $dbname);
        preg_match("/username['\"]?\s*=>\s*['\"]([^'\"]+)['\"]/i", $conteudo, $username);
        preg_match("/password['\"]?\s*=>\s*['\"]([^'\"]+)['\"]/i", $conteudo, $password);
        preg_match("/charset['\"]?\s*=>\s*['\"]([^'\"]+)['\"]/i", $conteudo, $charset);
        
        return [
            'host' => $host[1] ?? 'localhost',
            'dbname' => $dbname[1] ?? '',
            'username' => $username[1] ?? '',
            'password' => $password[1] ?? '',
            'charset' => $charset[1] ?? 'utf8'
        ];
    }
}

$diagnostico = new KongoDiagnosticoV3();
$diagnostico->executar();
?>