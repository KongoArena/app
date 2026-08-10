<?php
require_once __DIR__ . '/../config/database.php';

class Competicao {
    private $pdo;

    public function __construct() {
        $this->pdo = getDBConnection();
    }

    public function getAll($filtros = []) {
        $sql = "SELECT c.*, m.nome as modalidade_nome, t.nome as temporada_nome,
                (SELECT COUNT(*) FROM cong_competicao_equipas ce WHERE ce.competicao_id = c.id) as total_equipas
                FROM cong_competicoes c
                LEFT JOIN cong_modalidades m ON c.modalidade_id = m.id
                LEFT JOIN cong_temporadas t ON c.temporada_id = t.id";
        $condicoes = [];
        $params = [];
        if (!empty($filtros['modalidade_id'])) {
            $condicoes[] = "c.modalidade_id = :modalidade_id";
            $params['modalidade_id'] = $filtros['modalidade_id'];
        }
        if (!empty($filtros['temporada_id'])) {
            $condicoes[] = "c.temporada_id = :temporada_id";
            $params['temporada_id'] = $filtros['temporada_id'];
        }
        if (!empty($filtros['status'])) {
            $condicoes[] = "c.status = :status";
            $params['status'] = $filtros['status'];
        }
        if (!empty($condicoes)) {
            $sql .= " WHERE " . implode(" AND ", $condicoes);
        }
        $sql .= " ORDER BY c.data_inicio DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $sql = "SELECT c.*, m.nome as modalidade_nome, t.nome as temporada_nome
                FROM cong_competicoes c
                LEFT JOIN cong_modalidades m ON c.modalidade_id = m.id
                LEFT JOIN cong_temporadas t ON c.temporada_id = t.id
                WHERE c.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $comp = $stmt->fetch();
        if ($comp) {
            $comp['equipas'] = $this->getEquipas($id);
        }
        return $comp;
    }

    public function create($dados) {
        $sql = "INSERT INTO cong_competicoes
                (nome, modalidade_id, temporada_id, formato, descricao, data_inicio, data_fim, imagem_capa, status)
                VALUES (:nome, :modalidade_id, :temporada_id, :formato, :descricao, :data_inicio, :data_fim, :imagem_capa, :status)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nome' => $dados['nome'],
            'modalidade_id' => $dados['modalidade_id'],
            'temporada_id' => $dados['temporada_id'],
            'formato' => $dados['formato'] ?? 'Liga',
            'descricao' => $dados['descricao'] ?? null,
            'data_inicio' => $dados['data_inicio'] ?? null,
            'data_fim' => $dados['data_fim'] ?? null,
            'imagem_capa' => $dados['imagem_capa'] ?? null,
            'status' => $dados['status'] ?? 'breve'
        ]);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $dados) {
        $sql = "UPDATE cong_competicoes SET nome = :nome, modalidade_id = :modalidade_id,
                temporada_id = :temporada_id, formato = :formato, descricao = :descricao,
                data_inicio = :data_inicio, data_fim = :data_fim, imagem_capa = :imagem_capa, status = :status
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nome' => $dados['nome'],
            'modalidade_id' => $dados['modalidade_id'],
            'temporada_id' => $dados['temporada_id'],
            'formato' => $dados['formato'] ?? 'Liga',
            'descricao' => $dados['descricao'] ?? null,
            'data_inicio' => $dados['data_inicio'] ?? null,
            'data_fim' => $dados['data_fim'] ?? null,
            'imagem_capa' => $dados['imagem_capa'] ?? null,
            'status' => $dados['status'] ?? 'breve'
        ]);
    }

    public function adicionarEquipa($competicaoId, $equipaId) {
        $sql = "INSERT IGNORE INTO cong_competicao_equipas (competicao_id, equipa_id)
                VALUES (:competicao_id, :equipa_id)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['competicao_id' => $competicaoId, 'equipa_id' => $equipaId]);
    }

    public function removerEquipa($competicaoId, $equipaId) {
        $sql = "DELETE FROM cong_competicao_equipas WHERE competicao_id = :competicao_id AND equipa_id = :equipa_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['competicao_id' => $competicaoId, 'equipa_id' => $equipaId]);
    }

    public function getEquipas($competicaoId) {
        $sql = "SELECT e.* FROM cong_competicao_equipas ce
                JOIN cong_equipas e ON ce.equipa_id = e.id
                WHERE ce.competicao_id = :competicao_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['competicao_id' => $competicaoId]);
        return $stmt->fetchAll();
    }

    public function getClassificacao($competicaoId) {
        // Classificação genérica baseada em jogos finalizados (3 pontos vitória, 1 empate)
        // Assume resultado JSON no formato {"placar_casa": N, "placar_fora": N}
        $sql = "SELECT j.*, ec.nome as casa_nome, ef.nome as fora_nome
                FROM cong_jogos j
                JOIN cong_equipas ec ON j.equipa_casa_id = ec.id
                JOIN cong_equipas ef ON j.equipa_fora_id = ef.id
                WHERE j.competicao_id = :competicao_id AND j.status = 'finalizado'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['competicao_id' => $competicaoId]);
        $jogos = $stmt->fetchAll();

        $tabela = [];
        foreach ($jogos as $jogo) {
            $resultado = json_decode($jogo['resultado'], true);
            if (!$resultado || !isset($resultado['placar_casa']) || !isset($resultado['placar_fora'])) continue;

            $casaId = $jogo['equipa_casa_id'];
            $foraId = $jogo['equipa_fora_id'];
            foreach ([$casaId => $jogo['casa_nome'], $foraId => $jogo['fora_nome']] as $id => $nome) {
                if (!isset($tabela[$id])) {
                    $tabela[$id] = ['equipa_id' => $id, 'equipa' => $nome, 'pontos' => 0,
                        'jogos' => 0, 'vitorias' => 0, 'empates' => 0, 'derrotas' => 0,
                        'marcados' => 0, 'sofridos' => 0];
                }
            }

            $pc = (int)$resultado['placar_casa'];
            $pf = (int)$resultado['placar_fora'];

            $tabela[$casaId]['jogos']++;
            $tabela[$foraId]['jogos']++;
            $tabela[$casaId]['marcados'] += $pc;
            $tabela[$casaId]['sofridos'] += $pf;
            $tabela[$foraId]['marcados'] += $pf;
            $tabela[$foraId]['sofridos'] += $pc;

            if ($pc > $pf) {
                $tabela[$casaId]['vitorias']++; $tabela[$casaId]['pontos'] += 3;
                $tabela[$foraId]['derrotas']++;
            } elseif ($pf > $pc) {
                $tabela[$foraId]['vitorias']++; $tabela[$foraId]['pontos'] += 3;
                $tabela[$casaId]['derrotas']++;
            } else {
                $tabela[$casaId]['empates']++; $tabela[$casaId]['pontos'] += 1;
                $tabela[$foraId]['empates']++; $tabela[$foraId]['pontos'] += 1;
            }
        }

        $tabela = array_values($tabela);
        usort($tabela, function($a, $b) {
            if ($a['pontos'] !== $b['pontos']) return $b['pontos'] - $a['pontos'];
            $saldoA = $a['marcados'] - $a['sofridos'];
            $saldoB = $b['marcados'] - $b['sofridos'];
            return $saldoB - $saldoA;
        });

        return $tabela;
    }
}
?>
