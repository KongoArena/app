<?php
// kongo/api/index.php
// ============================================
// 1. CABEÇALHOS CORS (permite GitHub falar com Locaweb)
// ============================================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// ============================================
// 2. CARREGAR DEPENDÊNCIAS
// ============================================
require_once __DIR__ . '/config/database.php';
// Se tiveres ficheiro jwt.php:
// require_once __DIR__ . '/config/jwt.php';

// ============================================
// 3. ROUTER DA API
// ============================================
$action = $_GET['action'] ?? 'root';

try {
    $pdo = getDBConnection();

    switch ($action) {

        // ---------- RAIZ ----------
        case 'root':
            echo json_encode([
                'success' => true,
                'message' => 'API Kongo Arena funcionando!',
                'version' => '1.0',
                'timestamp' => date('c')
            ]);
            break;

        // ---------- HEALTH CHECK ----------
        case 'health':
            // Testa a ligação real à BD
            $stmt = $pdo->query("SELECT 1");
            $stmt->execute();
            echo json_encode([
                'success' => true,
                'status' => 'healthy',
                'database' => 'connected',
                'php_version' => phpversion()
            ]);
            break;

        // ---------- LOGIN (POST) ----------
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método não permitido. Use POST.']);
                break;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $password = $input['password'] ?? '';

            if (empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'E-mail e palavra-passe obrigatórios.']);
                break;
            }

            $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'Credenciais inválidas.']);
                break;
            }

            // Verifica a palavra-passe encriptada
            if (!password_verify($password, $user['password'])) {
                echo json_encode(['success' => false, 'message' => 'Credenciais inválidas.']);
                break;
            }

            // Gera um token simples (JWT seria o ideal, mas isto já funciona)
            $token = base64_encode(json_encode([
                'user_id' => $user['id'],
                'role' => $user['role'],
                'exp' => time() + (60 * 60 * 24) // 24 horas
            ]));

            echo json_encode([
                'success' => true,
                'message' => 'Login bem-sucedido!',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]);
            break;

        // ---------- GERAR KONGO ID ----------
        case 'generate_kongo_id':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Use POST.']);
                break;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $athleteId = $input['athlete_id'] ?? null;
            $orgId = $input['org_id'] ?? null;

            if (!$athleteId) {
                echo json_encode(['success' => false, 'message' => 'ID do atleta obrigatório.']);
                break;
            }

            // Próximo número sequencial
            $stmt = $pdo->query("SELECT kongo_number FROM kongo_ids ORDER BY id DESC LIMIT 1");
            $last = $stmt->fetchColumn();
            $newNumber = 'KA-000001';
            if ($last) {
                $num = (int)substr($last, 3);
                $newNumber = 'KA-' . str_pad($num + 1, 6, '0', STR_PAD_LEFT);
            }

            $qrToken = bin2hex(random_bytes(16));

            $stmt = $pdo->prepare("
                INSERT INTO kongo_ids (athlete_id, kongo_number, qr_token, current_organization_id, status) 
                VALUES (?, ?, ?, ?, 'active')
            ");
            $stmt->execute([$athleteId, $newNumber, $qrToken, $orgId]);

            echo json_encode([
                'success' => true,
                'message' => 'Kongo ID gerado com sucesso!',
                'data' => [
                    'kongo_number' => $newNumber,
                    'qr_token' => $qrToken,
                    'validation_url' => "https://tanquedigital.com.br/kongo/api/index.php?action=validate_qr&token=" . $qrToken
                ]
            ]);
            break;

        // ---------- VALIDAR QR CODE (público) ----------
        case 'validate_qr':
            $token = $_GET['token'] ?? '';
            if (empty($token)) {
                echo json_encode(['valid' => false, 'message' => 'Token em falta.']);
                break;
            }

            $stmt = $pdo->prepare("
                SELECT k.kongo_number, k.status, a.first_name, a.last_name, o.name as org_name
                FROM kongo_ids k
                JOIN athletes a ON k.athlete_id = a.id
                LEFT JOIN organizations o ON k.current_organization_id = o.id
                WHERE k.qr_token = ?
            ");
            $stmt->execute([$token]);
            $row = $stmt->fetch();

            if ($row) {
                echo json_encode([
                    'valid' => true,
                    'status' => $row['status'],
                    'athlete' => $row['first_name'] . ' ' . $row['last_name'],
                    'kongo_id' => $row['kongo_number'],
                    'organization' => $row['org_name']
                ]);
            } else {
                echo json_encode(['valid' => false, 'message' => 'Kongo ID inválido ou expirado.']);
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Ação não encontrada.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Kongo API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno no servidor.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
