<?php
namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class Estoque {
    public static function movimentar(int $idEletronico, string $tipo, int $quantidade, string $observacao = ''): array {
        $db = Database::connection();
        try {
            $db->beginTransaction();

            // Bloqueio pessimista (SELECT ... FOR UPDATE) para prevenir concorrência
            $sqlSelect = "SELECT quantidade_atual FROM estoque WHERE id_eletronico = :id FOR UPDATE";
            $stmtSelect = $db->prepare($sqlSelect);
            $stmtSelect->bindValue(':id', $idEletronico, PDO::PARAM_INT);
            $stmtSelect->execute();
            $estoqueAtual = $stmtSelect->fetch();

            if (!$estoqueAtual) {
                $saldoAnterior = 0;
                $sqlInsertEstoque = "INSERT INTO estoque (id_eletronico, quantidade_atual) VALUES (:id, 0)";
                $stmtIns = $db->prepare($sqlInsertEstoque);
                $stmtIns->bindValue(':id', $idEletronico, PDO::PARAM_INT);
                $stmtIns->execute();
            } else {
                $saldoAnterior = (int) $estoqueAtual['quantidade_atual'];
            }

            if ($tipo === 'SAIDA' && $saldoAnterior < $quantidade) {
                $db->rollBack();
                return [
                    "sucesso" => false, 
                    "codigo" => 409, 
                    "mensagem" => "Saldo insuficiente em estoque. Saldo disponível: {$saldoAnterior}"
                ];
            }

            $saldoPosterior = ($tipo === 'ENTRADA') ? ($saldoAnterior + $quantidade) : ($saldoAnterior - $quantidade);

            $sqlUpdate = "UPDATE estoque SET quantidade_atual = :saldo_posterior WHERE id_eletronico = :id";
            $stmtUpdate = $db->prepare($sqlUpdate);
            $stmtUpdate->bindValue(':saldo_posterior', $saldoPosterior, PDO::PARAM_INT);
            $stmtUpdate->bindValue(':id', $idEletronico, PDO::PARAM_INT);
            $stmtUpdate->execute();

            $sqlHistorico = "INSERT INTO movimentacoes 
                (id_eletronico, id_usuario, tipo, quantidade, saldo_anterior, saldo_posterior, observacao) 
                VALUES 
                (:id_eletronico, :id_usuario, :tipo, :quantidade, :saldo_anterior, :saldo_posterior, :observacao)";
            
            $stmtHist = $db->prepare($sqlHistorico);
            $stmtHist->bindValue(':id_eletronico', $idEletronico, PDO::PARAM_INT);
            $stmtHist->bindValue(':id_usuario', DEFAULT_USER_ID, PDO::PARAM_INT);
            $stmtHist->bindValue(':tipo', $tipo);
            $stmtHist->bindValue(':quantidade', $quantidade, PDO::PARAM_INT);
            $stmtHist->bindValue(':saldo_anterior', $saldoAnterior, PDO::PARAM_INT);
            $stmtHist->bindValue(':saldo_posterior', $saldoPosterior, PDO::PARAM_INT);
            $stmtHist->bindValue(':observacao', $observacao ?: 'Movimentação realizada via API');
            $stmtHist->execute();

            $db->commit();
            return [
                "sucesso" => true, 
                "codigo" => 200, 
                "mensagem" => "Estoque movimentado com sucesso", 
                "novo_saldo" => $saldoPosterior
            ];

        } catch (Exception $e) {
            $db->rollBack();
            return ["sucesso" => false, "codigo" => 500, "mensagem" => "Erro interno ao processar movimentação."];
        }
    }

    public static function historico(?int $idEletronico = null): array {
        $db = Database::connection();
        $sql = "SELECT 
                    m.id_movimentacao,
                    m.id_eletronico,
                    e.nome AS nome_eletronico,
                    m.tipo,
                    m.quantidade,
                    m.saldo_anterior,
                    m.saldo_posterior,
                    m.observacao,
                    m.data_movimentacao,
                    u.nome AS nome_usuario
                FROM movimentacoes m
                INNER JOIN eletronicos e ON m.id_eletronico = e.id_eletronico
                INNER JOIN usuarios u ON m.id_usuario = u.id_usuario";

        if ($idEletronico !== null) {
            $sql .= " WHERE m.id_eletronico = :id_eletronico";
        }

        $sql .= " ORDER BY m.data_movimentacao DESC";

        $stmt = $db->prepare($sql);
        if ($idEletronico !== null) {
            $stmt->bindValue(':id_eletronico', $idEletronico, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }
}