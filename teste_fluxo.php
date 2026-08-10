<?php
// Ajusta o caminho para o teu database.php seguro
require_once 'database.php'; 
$pdo = getDBConnection();

echo "<h2>⚙️ Motor Kongo Arena - Teste de Fluxo</h2>";

try {
    // 1. Criar Atleta de Teste
    $stmt = $pdo->prepare("INSERT INTO athletes (first_name, last_name, birth_date) VALUES (?, ?, ?)");
    $stmt->execute(['André', 'Silva', '2008-05-12']);
    $athleteId = $pdo->lastInsertId();
    echo "<p>✅ <b>Atleta criado</b> (ID Interno: $athleteId)</p>";

    // 2. Gerar Kongo ID Sequencial (Lógica Premium)
    $stmt = $pdo->query("SELECT kongo_number FROM kongo_ids ORDER BY id DESC LIMIT 1");
    $lastKongo = $stmt->fetchColumn();

    if (!$lastKongo) {
        $newKongoNumber = 'KA-000001';
    } else {
        $num = (int)substr($lastKongo, 3);
        $newKongoNumber = 'KA-' . str_pad($num + 1, 6, '0', STR_PAD_LEFT);
    }

    // Gerar Token Criptográfico para o QR Code (Não expõe dados pessoais)
    $qrToken = bin2hex(random_bytes(16)); 
    
    $stmt = $pdo->prepare("INSERT INTO kongo_ids (athlete_id, kongo_number, qr_token, status) VALUES (?, ?, ?, 'active')");
    $stmt->execute([$athleteId, $newKongoNumber, $qrToken]);
    
    echo "<p>🏆 <b>Kongo ID Gerado:</b> <span style='color: #d4af37; font-weight:bold;'>$newKongoNumber</span></p>";
    echo "<p>🔗 <b>Link do QR Code:</b> <code>kongoarena.com/validar/" . $qrToken . "</code></p>";
    echo "<hr>";

    // 3. Simular Leitura do QR Code (O que o App/Leitor faz)
    echo "<h3>📱 Simulando Leitura do QR Code...</h3>";
    $stmt = $pdo->prepare("
        SELECT k.kongo_number, k.status, a.first_name, a.last_name 
        FROM kongo_ids k
        JOIN athletes a ON k.athlete_id = a.id
        WHERE k.qr_token = ?
    ");
    $stmt->execute([$qrToken]);
    $result = $stmt->fetch();

    if ($result) {
        echo "<div style='background:#212121; color:#fff; padding:15px; border-left: 5px solid #d4af37; font-family: Arial, sans-serif;'>";
        echo "<p style='color:#4CAF50; font-weight:bold;'>🟢 KONGO ID VÁLIDO</p>";
        echo "<p><b>Atleta:</b> " . $result['first_name'] . " " . $result['last_name'] . "</p>";
        echo "<p><b>Número:</b> " . $result['kongo_number'] . "</p>";
        echo "<p><b>Estado:</b> " . strtoupper($result['status']) . "</p>";
        echo "</div>";
    } else {
        echo "<p style='color:red;'>🔴 QR Code Inválido ou Expirado.</p>";
    }

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>