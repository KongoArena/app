<?php
require_once __DIR__ . '/../config/database.php';

class Atleta {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    public function create($dados) {
        // Gerar Kongo ID automaticamente
        $stmt = $this->pdo->query("SELECT MAX(id) as max_id FROM cong_atletas");
        $row = $stmt->fetch();
        $nextId = $row['max_id'] + 1;
        $kongoId = 'KA-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO cong_atletas (
                    kongo_id, nome_completo, data_nascimento, genero, 
                    fotografia, altura, peso, pe_dominante, nacionalidade, 
                    cidade, biografia, status_licenca, utilizador_id, clube_id
                ) VALUES (
                    :kongo_id, :nome_completo, :data_nascimento, :genero,
                    :fotografia, :altura, :peso, :pe_dominante, :nacionalidade,
                    :cidade, :biografia, :status_licenca, :utilizador_id, :clube_id
                )";
        
        $stmt = $this->pdo->prepare($sql);
        $dados['kongo_id'] = $kongoId;
        $dados['status_licenca'] = 'pendente';
        
        // Garantir que apenas as colunas esperadas vão para a query
        $params = [
            'kongo_id' => $dados['kongo_id'],
            'nome_completo' => $dados['nome_completo'],
            'data_nascimento' => $dados['data_nascimento'],
            'genero' => $dados['genero'] ?? null,
            'fotografia' => $dados['fotografia'] ?? null,
            'altura' => $dados['altura'] ?? null,
            'peso' => $dados['peso'] ?? null,
            'pe_dominante' => $dados['pe_dominante'] ?? null,
            'nacionalidade' => $dados['nacionalidade'] ?? 'Angola',
            'cidade' => $dados['cidade'] ?? null,
            'biografia' => $dados['biografia'] ?? null,
            'status_licenca' => $dados['status_licenca'],
            'utilizador_id' => $dados['utilizador_id'] ?? null,
            'clube_id' => !empty($dados['clube_id']) ? $dados['clube_id'] : null
        ];
        $stmt->execute($params);
        $atletaId = $this->pdo->lastInsertId();
        
        // Criar licença
        $this->criarLicenca($atletaId);

        // Associar modalidade(s), se enviadas: modalidade_id (único) ou modalidades (array)
        if (!empty($dados['modalidade_id'])) {
            $this->addModalidade($atletaId, $dados['modalidade_id'], $dados['posicao'] ?? null);
        }
        if (!empty($dados['modalidades']) && is_array($dados['modalidades'])) {
            foreach ($dados['modalidades'] as $mod) {
                $modId = is_array($mod) ? ($mod['modalidade_id'] ?? null) : $mod;
                $posicao = is_array($mod) ? ($mod['posicao'] ?? null) : null;
                if ($modId) {
                    $this->addModalidade($atletaId, $modId, $posicao);
                }
            }
        }
        
        return [
            'id' => $atletaId,
            'kongo_id' => $kongoId
        ];
    }
    
    public function criarLicenca($atletaId) {
        $sql = "INSERT INTO cong_licencas (atleta_id, data_emissao, data_expiracao, status) 
                VALUES (:atleta_id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'ativa')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['atleta_id' => $atletaId]);
    }
    
    public function getAll($filtros = []) {
        $sql = "SELECT a.*, l.status as status_licenca_atual, c.nome as clube_nome
                FROM cong_atletas a
                LEFT JOIN cong_licencas l ON a.id = l.atleta_id
                LEFT JOIN cong_clubes c ON a.clube_id = c.id";
        
        $condicoes = [];
        if (!empty($filtros['modalidade'])) {
            $condicoes[] = "a.id IN (SELECT atleta_id FROM cong_atleta_modalidades WHERE modalidade_id = " . intval($filtros['modalidade']) . ")";
        }
        if (!empty($filtros['clube_id'])) {
            $condicoes[] = "a.clube_id = " . intval($filtros['clube_id']);
        }
        
        if (!empty($condicoes)) {
            $sql .= " WHERE " . implode(" AND ", $condicoes);
        }
        
        $sql .= " ORDER BY a.id DESC";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $sql = "SELECT a.*, l.status as status_licenca_atual, c.nome as clube_nome
                FROM cong_atletas a
                LEFT JOIN cong_licencas l ON a.id = l.atleta_id
                LEFT JOIN cong_clubes c ON a.clube_id = c.id
                WHERE a.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $atleta = $stmt->fetch();
        if ($atleta) {
            $atleta['modalidades'] = $this->getModalidades($id);

            $sqlEquipas = "SELECT e.id, e.nome, e.modalidade_id, ea.data_entrada
                    FROM cong_equipa_atletas ea
                    JOIN cong_equipas e ON ea.equipa_id = e.id
                    WHERE ea.atleta_id = :atleta_id AND ea.status = 'ativo'";
            $stmtEquipas = $this->pdo->prepare($sqlEquipas);
            $stmtEquipas->execute(['atleta_id' => $id]);
            $atleta['equipas_atuais'] = $stmtEquipas->fetchAll();
        }
        return $atleta;
    }
    
    public function getByKongoId($kongoId) {
        $sql = "SELECT * FROM cong_atletas WHERE kongo_id = :kongo_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['kongo_id' => $kongoId]);
        return $stmt->fetch();
    }
    
    public function update($id, $dados) {
        // Faz merge com os dados existentes para permitir atualizações parciais
        $atual = $this->getById($id);
        if (!$atual) return false;

        $campos = ['nome_completo', 'data_nascimento', 'genero', 'fotografia', 'altura',
            'peso', 'pe_dominante', 'nacionalidade', 'cidade', 'biografia', 'clube_id'];

        $params = ['id' => $id];
        foreach ($campos as $campo) {
            $params[$campo] = array_key_exists($campo, $dados) ? $dados[$campo] : ($atual[$campo] ?? null);
        }

        $sql = "UPDATE cong_atletas SET 
                    nome_completo = :nome_completo,
                    data_nascimento = :data_nascimento,
                    genero = :genero,
                    fotografia = :fotografia,
                    altura = :altura,
                    peso = :peso,
                    pe_dominante = :pe_dominante,
                    nacionalidade = :nacionalidade,
                    cidade = :cidade,
                    biografia = :biografia,
                    clube_id = :clube_id
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function addModalidade($atletaId, $modalidadeId, $posicao = null) {
        $sql = "INSERT INTO cong_atleta_modalidades (atleta_id, modalidade_id, posicao) 
                VALUES (:atleta_id, :modalidade_id, :posicao)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'atleta_id' => $atletaId,
            'modalidade_id' => $modalidadeId,
            'posicao' => $posicao
        ]);
    }
    
    public function getModalidades($atletaId) {
        $sql = "SELECT m.*, am.posicao 
                FROM cong_atleta_modalidades am
                JOIN cong_modalidades m ON am.modalidade_id = m.id
                WHERE am.atleta_id = :atleta_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['atleta_id' => $atletaId]);
        return $stmt->fetchAll();
    }
}
?>