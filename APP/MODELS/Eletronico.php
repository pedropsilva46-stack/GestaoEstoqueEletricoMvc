<?php
namespace App\Models;
use App\Core\Database;
use PDO;
use Exception;
class Eletronico {
    
    public static function listar(): array {
        $db = Database::connection();
        $sql = "SELECT 
                    e.id_eletronico,
                    e.nome,
                    e.descricao,
                    e.numero_lote,
                    e.fabricante,
                    e.categoria,
                    e.preco,
                    e.estoque_minimo,
                    e.ativo,
                    COALESCE(es.quantidade_atual, 0) AS quantidade_atual,
                    CASE 
                        WHEN COALESCE(es.quantidade_atual, 0) <= e.estoque_minimo THEN 1 
                        ELSE 0 
                    END AS estoque_baixo
                FROM eletronicos e
                LEFT JOIN estoque es ON e.id_eletronico = es.id_eletronico
                WHERE e.ativo = 1
                ORDER BY e.nome ASC";
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }
    public static function buscarPorId(int $id): ?array {
        $db = Database::connection();
        $sql = "SELECT 
                    e.*, 
                    COALESCE(es.quantidade_atual, 0) AS quantidade_atual,
                    CASE 
                        WHEN COALESCE(es.quantidade_atual, 0) <= e.estoque_minimo THEN 1 
                        ELSE 0 
                    END AS estoque_baixo
                FROM eletronicos e 
                LEFT JOIN estoque es ON e.id_eletronico = es.id_eletronico 
                WHERE e.id_eletronico = :id AND e.ativo = 1";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result ?: null;
    }
    public static function buscarPorNome(string $nome): array {
        $db = Database::connection();
        $sql = "SELECT 
                    e.*, 
                    COALESCE(es.quantidade_atual, 0) AS quantidade_atual,
                    CASE 
                        WHEN COALESCE(es.quantidade_atual, 0) <= e.estoque_minimo THEN 1 
                        ELSE 0 
                    END AS estoque_baixo
                FROM eletronicos e
                LEFT JOIN estoque es ON e.id_eletronico = es.id_eletronico
                WHERE e.nome LIKE :nome AND e.ativo = 1
                ORDER BY e.nome ASC";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':nome', '%' . $nome . '%');
        $stmt->execute();
        return $stmt->fetchAll();
    }
    public static function buscarPorDescricao(string $descricao): array {
        $db = Database::connection();
        $sql = "SELECT 
                    e.*, 
                    COALESCE(es.quantidade_atual, 0) AS quantidade_atual,
                    CASE 
                        WHEN COALESCE(es.quantidade_atual, 0) <= e.estoque_minimo THEN 1 
                        ELSE 0 
                    END AS estoque_baixo
                FROM eletronicos e
                LEFT JOIN estoque es ON e.id_eletronico = es.id_eletronico
                WHERE e.descricao LIKE :descricao AND e.ativo = 1
                ORDER BY e.nome ASC";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':descricao', '%' . $descricao . '%');
        $stmt->execute();
        return $stmt->fetchAll();
    }
    public static function buscarPorLote(string $lote): ?array {
        $db = Database::connection();
        $sql = "SELECT 
                    e.*, 
                    COALESCE(es.quantidade_atual, 0) AS quantidade_atual,
                    CASE 
                        WHEN COALESCE(es.quantidade_atual, 0) <= e.estoque_minimo THEN 1 
                        ELSE 0 
                    END AS estoque_baixo
                FROM eletronicos e
                LEFT JOIN estoque es ON e.id_eletronico = es.id_eletronico
                WHERE e.numero_lote = :lote AND e.ativo = 1";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':lote', $lote);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result ?: null;
    }
    public static function criar(array $dados): ?int {
        $db = Database::connection();
        try {
            $db->beginTransaction();
            $sqlEletronico = "INSERT INTO eletronicos 
                (nome, descricao, numero_lote, fabricante, categoria, tensao, dimensoes, resolucao, capacidade, conectividade, preco, processador, memoria, camera, sistema_operacional, cor, estoque_minimo, ativo) 
                VALUES 
                (:nome, :descricao, :numero_lote, :fabricante, :categoria, :tensao, :dimensoes, :resolucao, :capacidade, :conectividade, :preco, :processador, :memoria, :camera, :sistema_operacional, :cor, :estoque_minimo, 1)";
            
            $stmt = $db->prepare($sqlEletronico);
            $stmt->bindValue(':nome', $dados['nome']);
            $stmt->bindValue(':descricao', $dados['descricao'] ?? '');
            $stmt->bindValue(':numero_lote', $dados['lote']);
            $stmt->bindValue(':fabricante', $dados['fabricante'] ?? 'Geral');
            $stmt->bindValue(':categoria', $dados['categoria'] ?? 'Geral');
            $stmt->bindValue(':tensao', $dados['tensao'] ?? null);
            $stmt->bindValue(':dimensoes', $dados['dimensoes'] ?? null);
            $stmt->bindValue(':resolucao', $dados['resolucao'] ?? null);
            $stmt->bindValue(':capacidade', $dados['capacidade'] ?? null);
            $stmt->bindValue(':conectividade', $dados['conectividade'] ?? null);
            $stmt->bindValue(':preco', $dados['preco']);
            $stmt->bindValue(':processador', $dados['processador'] ?? null);
            $stmt->bindValue(':memoria', $dados['memoria'] ?? null);
            $stmt->bindValue(':camera', $dados['camera'] ?? null);
            $stmt->bindValue(':sistema_operacional', $dados['sistema_operacional'] ?? null);
            $stmt->bindValue(':cor', $dados['cor'] ?? null);
            $stmt->bindValue(':estoque_minimo', $dados['estoque_minimo'] ?? 5, PDO::PARAM_INT);
            $stmt->execute();
            $idEletronico = (int) $db->lastInsertId();
            $quantidadeInicial = (int) ($dados['quantidade_inicial'] ?? 0);
            $sqlEstoque = "INSERT INTO estoque (id_eletronico, quantidade_atual) VALUES (:id_eletronico, :quantidade)";
            $stmtEstoque = $db->prepare($sqlEstoque);
            $stmtEstoque->bindValue(':id_eletronico', $idEletronico, PDO::PARAM_INT);
            $stmtEstoque->bindValue(':quantidade', $quantidadeInicial, PDO::PARAM_INT);
            $stmtEstoque->execute();
            if ($quantidadeInicial > 0) {
                $sqlMov = "INSERT INTO movimentacoes 
                    (id_eletronico, id_usuario, tipo, quantidade, saldo_anterior, saldo_posterior, observacao) 
                    VALUES 
                    (:id_eletronico, :id_usuario, 'ENTRADA', :quantidade, 0, :quantidade, 'Carga inicial do produto')";
                $stmtMov = $db->prepare($sqlMov);
                $stmtMov->bindValue(':id_eletronico', $idEletronico, PDO::PARAM_INT);
                $stmtMov->bindValue(':id_usuario', DEFAULT_USER_ID, PDO::PARAM_INT);
                $stmtMov->bindValue(':quantidade', $quantidadeInicial, PDO::PARAM_INT);
                $stmtMov->execute();
            }
            $db->commit();
            return $idEletronico;
        } catch (Exception $e) {
            $db->rollBack();
            return null;
        }
    }
    public static function atualizar(int $id, array $dados): bool {
        $db = Database::connection();
        try {
            $db->beginTransaction();
            $sql = "UPDATE eletronicos SET 
                        nome = :nome, 
                        descricao = :descricao, 
                        numero_lote = :lote, 
                        preco = :preco, 
                        estoque_minimo = :estoque_minimo 
                    WHERE id_eletronico = :id AND ativo = 1";
            
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':nome', $dados['nome']);
            $stmt->bindValue(':descricao', $dados['descricao'] ?? '');
            $stmt->bindValue(':lote', $dados['lote']);
            $stmt->bindValue(':preco', $dados['preco']);
            $stmt->bindValue(':estoque_minimo', $dados['estoque_minimo'], PDO::PARAM_INT);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }
    public static function excluirLogicamente(int $id): bool {
        $db = Database::connection();
        $sql = "UPDATE eletronicos SET ativo = 0 WHERE id_eletronico = :id AND ativo = 1";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    public static function resumo(): array {
        $db = Database::connection();
        
        $sqlTotalProdutos = "SELECT COUNT(*) AS total_produtos FROM eletronicos WHERE ativo = 1";
        $totalProdutos = (int) $db->query($sqlTotalProdutos)->fetchColumn();
        $sqlTotalEstoque = "SELECT COALESCE(SUM(es.quantidade_atual), 0) FROM estoque es INNER JOIN eletronicos e ON es.id_eletronico = e.id_eletronico WHERE e.ativo = 1";
        $totalItensEstoque = (int) $db->query($sqlTotalEstoque)->fetchColumn();
        $sqlValorTotal = "SELECT COALESCE(SUM(es.quantidade_atual * e.preco), 0) FROM estoque es INNER JOIN eletronicos e ON es.id_eletronico = e.id_eletronico WHERE e.ativo = 1";
        $valorTotalEstoque = (float) $db->query($sqlValorTotal)->fetchColumn();
        $sqlEstoqueBaixo = "SELECT COUNT(*) FROM eletronicos e LEFT JOIN estoque es ON e.id_eletronico = es.id_eletronico WHERE e.ativo = 1 AND COALESCE(es.quantidade_atual, 0) <= e.estoque_minimo";
        $totalEstoqueBaixo = (int) $db->query($sqlEstoqueBaixo)->fetchColumn();
        return [
            "total_produtos_ativos" => $totalProdutos,
            "total_itens_estoque" => $totalItensEstoque,
            "valor_total_estoque" => $valorTotalEstoque,
            "total_produtos_estoque_baixo" => $totalEstoqueBaixo
        ];
    }
}