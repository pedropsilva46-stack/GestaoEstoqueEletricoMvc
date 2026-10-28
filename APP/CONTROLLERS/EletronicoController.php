<?php
namespace App\Controllers;

use App\Core\Request;
use App\Models\Eletronico;
use App\Views\JsonView;

class EletronicoController {

    // GET /eletronico
    public function listar(): void {
        $dados = Eletronico::listar();
        JsonView::render(["erro" => false, "dados" => $dados], 200);
    }

    // POST /eletronico
    public function criar(): void {
        $input = Request::input();

        $nome = trim($input['nome'] ?? '');
        $lote = trim($input['lote'] ?? $input['numero_lote'] ?? '');
        $preco = filter_var($input['preco'] ?? null, FILTER_VALIDATE_FLOAT);
        $qtdInicial = filter_var($input['quantidade_inicial'] ?? 0, FILTER_VALIDATE_INT);
        $estMinimo = filter_var($input['estoque_minimo'] ?? 5, FILTER_VALIDATE_INT);

        if (empty($nome) || empty($lote) || $preco === false || $preco <= 0 || $qtdInicial === false || $qtdInicial < 0) {
            JsonView::render([
                "erro" => true, 
                "mensagem" => "Campos obrigatórios inválidos"
            ], 422);
        }

        $loteExistente = Eletronico::buscarPorLote($lote);
        if ($loteExistente) {
            JsonView::render(["erro" => true, "mensagem" => "Lote já cadastrado no sistema"], 409);
        }

        $dados = array_merge($input, [
            'nome' => $nome,
            'lote' => $lote,
            'preco' => $preco,
            'quantidade_inicial' => $qtdInicial,
            'estoque_minimo' => $estMinimo
        ]);

        $idGerado = Eletronico::criar($dados);

        if ($idGerado) {
            JsonView::render([
                "erro" => false, 
                "mensagem" => "Eletrônico cadastrado com sucesso", 
                "id_eletronico" => $idGerado
            ], 201);
        } else {
            JsonView::render(["erro" => true, "mensagem" => "Erro ao persistir registo na base de dados."], 500);
        }
    }

    // PUT /eletronico
    public function atualizar(): void {
        $input = Request::input();

        $id = filter_var($input['id_eletronico'] ?? null, FILTER_VALIDATE_INT);
        $nome = trim($input['nome'] ?? '');
        $lote = trim($input['lote'] ?? $input['numero_lote'] ?? '');
        $preco = filter_var($input['preco'] ?? null, FILTER_VALIDATE_FLOAT);
        $estMinimo = filter_var($input['estoque_minimo'] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id <= 0 || empty($nome) || empty($lote) || $preco === false || $preco <= 0 || $estMinimo === false || $estMinimo < 0) {
            JsonView::render([
                "erro" => true, 
                "mensagem" => "Dados de entrada inválidos para atualização"
            ], 422);
        }

        $produto = Eletronico::buscarPorId($id);
        if (!$produto) {
            JsonView::render(["erro" => true, "mensagem" => "Eletrônico não encontrado"], 404);
        }

        $loteExistente = Eletronico::buscarPorLote($lote);
        if ($loteExistente && (int)$loteExistente['id_eletronico'] !== $id) {
            JsonView::render(["erro" => true, "mensagem" => "Lote pertencente a outro produto"], 409);
        }

        $dados = [
            'nome' => $nome,
            'descricao' => $input['descricao'] ?? '',
            'lote' => $lote,
            'preco' => $preco,
            'estoque_minimo' => $estMinimo
        ];

        $sucesso = Eletronico::atualizar($id, $dados);

        if ($sucesso) {
            JsonView::render(["erro" => false, "mensagem" => "Eletrônico atualizado com sucesso"], 200);
        } else {
            JsonView::render(["erro" => true, "mensagem" => "Falha ao atualizar eletrônico."], 500);
        }
    }

    // DELETE /eletronico
    public function excluir(): void {
        $idInput = Request::query('id') ?? Request::input()['id_eletronico'] ?? null;
        $id = filter_var($idInput, FILTER_VALIDATE_INT);

        if (!$id || $id <= 0) {
            JsonView::render(["erro" => true, "mensagem" => "ID do produto inválido"], 422);
        }

        $produto = Eletronico::buscarPorId($id);
        if (!$produto) {
            JsonView::render(["erro" => true, "mensagem" => "Eletrônico não encontrado no sistema"], 404);
        }

        $sucesso = Eletronico::excluirLogicamente($id);

        if ($sucesso) {
            JsonView::render(["erro" => false, "mensagem" => "Eletrônico desativado com sucesso"], 200);
        } else {
            JsonView::render(["erro" => true, "mensagem" => "Erro ao desativar eletrônico."], 500);
        }
    }

    // GET /eletronicoId
    public function porId(): void {
        $idParam = Request::query('id');
        $id = filter_var($idParam, FILTER_VALIDATE_INT);

        if (!$id || $id <= 0) {
            JsonView::render(["erro" => true, "mensagem" => "Parâmetro ID é obrigatório e deve ser numérico"], 422);
        }

        $produto = Eletronico::buscarPorId($id);

        if (!$produto) {
            JsonView::render(["erro" => true, "mensagem" => "Eletrônico não encontrado"], 404);
        }

        JsonView::render(["erro" => false, "dados" => $produto], 200);
    }

    // GET /eletronicoNome
    public function porNome(): void {
        $nome = trim(Request::query('nome', ''));

        if (empty($nome)) {
            JsonView::render(["erro" => true, "mensagem" => "O parâmetro 'nome' é obrigatório para pesquisa"], 422);
        }

        $dados = Eletronico::buscarPorNome($nome);
        JsonView::render(["erro" => false, "dados" => $dados], 200);
    }

    // GET /eletronicoDescricao
    public function porDescricao(): void {
        $descricao = trim(Request::query('descricao', ''));

        if (empty($descricao)) {
            JsonView::render(["erro" => true, "mensagem" => "Parâmetro 'descricao' não informado"], 422);
        }

        $dados = Eletronico::buscarPorDescricao($descricao);
        JsonView::render(["erro" => false, "dados" => $dados], 200);
    }

    // GET /eletronicoLote
    public function porLote(): void {
        $lote = trim(Request::query('lote', ''));

        if (empty($lote)) {
            JsonView::render(["erro" => true, "mensagem" => "O código do lote é obrigatório"], 422);
        }

        $produto = Eletronico::buscarPorLote($lote);

        if (!$produto) {
            JsonView::render(["erro" => true, "mensagem" => "Lote não localizado"], 404);
        }

        JsonView::render(["erro" => false, "dados" => $produto], 200);
    }

    // GET /resumo
    public function resumo(): void {
        try {
            $dados = Eletronico::resumo();
            JsonView::render(["erro" => false, "dados" => $dados], 200);
        } catch (\Exception $e) {
            JsonView::render(["erro" => true, "mensagem" => "Falha ao gerar resumo do estoque"], 500);
        }
    }
}