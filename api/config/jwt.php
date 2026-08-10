<?php
// Configuração JWT
define('JWT_SECRET', 'KongoArena2026SecretKey@Strong');
define('JWT_EXPIRY', 86400 * 7); // 7 dias

function generateJWT($userId, $tipo) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $userId,
        'tipo' => $tipo,
        'iat' => time(),
        'exp' => time() + JWT_EXPIRY
    ]);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

function verifyJWT($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    
    $header = $parts[0];
    $payload = $parts[1];
    $signature = $parts[2];
    
    $expectedSignature = hash_hmac('sha256', $header . "." . $payload, JWT_SECRET, true);
    $base64UrlExpectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expectedSignature));
    
    if ($signature !== $base64UrlExpectedSignature) return null;
    
    $decodedPayload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
    
    if ($decodedPayload['exp'] < time()) return null;
    
    return $decodedPayload;
}
?>