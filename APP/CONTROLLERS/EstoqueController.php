<?php
namespace App\Controllers;

use App\Core\Request;
use App\Models\Eletronico;
use App\Models\Estoque;
use App\Views\JsonView;

class EstoqueController {

    // PUT /estoque
    public function movimentar(): void {
        $input = Request::input();

        $idEletronico = filter_var($input['id_eletronico'] ?? null, FILTER_VALIDATE_INT);
        $tipoRaw = $input['tipo_movimentacao'] ?? $input['tipo'] ?? '';
        $tipo = strtoupper(trim($tipoRaw));
        $quantidade = filter_var($input['quantidade'] ?? null, FILTER_VALIDATE_INT);
        $motivo = trim($input['motivo'] ?? $input['observacao'] ?? '');

        if (!$idEletronico || $idEletronico <= 0 || !in_array($tipo, ['ENTRADA', 'SAIDA']) || $quantidade === false || $quantidade <= 0) {
            JsonView::render([
                "erro" => true, 
                "mensagem" => "Parâmetros de movimentação inválidos. Envie id_eletronico (>0), tipo ('ENTRADA' ou 'SAIDA') e quantidade (>0)."
            ], 422);
        }

        $produto = Eletronico::buscarPorId($idEletronico);
        if (!$produto) {
            JsonView::render(["erro" => true, "mensagem" => "Eletrônico não encontrado"], 404);
        }

        $resultado = Estoque::movimentar($idEletronico, $tipo, $quantidade, $motivo);

        if ($resultado['sucesso']) {
            JsonView::render([
                "erro" => false, 
                "mensagem" => $resultado['mensagem'], 
                "novo_saldo" => $resultado['novo_saldo']
            ], $resultado['codigo']);
        } else {
            JsonView::render(["erro" => true, "mensagem" => $resultado['mensagem']], $resultado['codigo']);
        }
    }

    // GET /movimentacoes
    public function historico(): void {
        $idParam = Request::query('id_eletronico');
        $idEletronico = null;

        if ($idParam !== null) {
            $idEletronico = filter_var($idParam, FILTER_VALIDATE_INT);
            if (!$idEletronico || $idEletronico <= 0) {
                JsonView::render(["erro" => true, "mensagem" => "Identificador de produto inválido"], 422);
            }
        }

        $dados = Estoque::historico($idEletronico);
        JsonView::render(["erro" => false, "dados" => $dados], 200);
    }
}