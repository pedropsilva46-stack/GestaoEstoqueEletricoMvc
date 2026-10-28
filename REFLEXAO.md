# Registos de Reflexão e Decisões Técnicas (Fase 3)

## 21/10/2026 — Fundação e Infraestrutura Base
* **Integrantes e papéis:** Driver: Estudante A | Navigator: Estudante B
* **Problema:** Mapear a base de dados real `gestao_estoque_eletro` e ajustar o autoloader para a estrutura de pastas em maiúsculas (`APP/CONTROLLERS`, `APP/MODELS`, etc.).
* **Nossa tentativa:** O autoloader padrão procurava diretórios em minúsculas, falhando em sistemas de ficheiros case-sensitive.
* **Prompt enviado à IA:** "Como implementar um spl_autoload_register robusto em PHP para estruturas de diretórios em maiúsculas APP/CONTROLLERS?"
* **Sugestão da IA:** Mapear os segmentos de namespaces para maiúsculas e manter fallback para compatibilidade.
* **Decisão da dupla:** Adotar o autoloader dinâmico e criar a constante `DEFAULT_USER_ID = 1` para suprir a chave estrangeira da tabela `movimentacoes` na ausência de módulo de autenticação.
* **Teste realizado:** GET `http://localhost/GestaoEstoqueEletroMVC/PUBLIC/health`
* **Resultado obtido:** HTTP 200 | `{"status": "ok", "mensagem": "API Gestão de Estoque operacional."}`
* **Evidência:** Registo efetuado no repositório.
* **O que aprendemos:** O isolamento das configurações e um autoloader flexível previnem falhas em diferentes ambientes de hospedagem.

---

## 21/10/2026 — POST /eletronico (Cadastro de Eletrónico e Estoque Inicial)
* **Integrantes e papéis:** Driver: Estudante B | Navigator: Estudante A
* **Problema:** Garantir a inserção na tabela `eletronicos`, a criação do saldo na tabela `estoque` e a auditoria em `movimentacoes` sem criar dados órfãos se algo falhar.
* **Nossa tentativa:** Inserções diretas individuais.
* **Prompt enviado à IA:** "Como fazer 3 inserções encadeadas no MySQL usando PDO com transação e commit/rollback?"
* **Sugestão da IA:** Encapsular as três operações entre `$db->beginTransaction()` e `$db->commit()`, tratando exceções no bloco `catch`.
* **Decisão da dupla:** Aceitar e implementar no método `Eletronico::criar()`, adicionando a verificação do lote existente antes de abrir a transação.
* **Teste realizado:** POST `http://localhost/GestaoEstoqueEletroMVC/PUBLIC/eletronico`
* **Resultado obtido:** HTTP 201 | `{"erro": false, "mensagem": "Eletrônico cadastrado com sucesso", "id_eletronico": 4}`
* **Evidência:** `EVIDÊNCIAS-POSTMAN/POST eletronico.png`
* **O que aprendemos:** A atomicidade garantida por transações garante a consistência relacional da base de dados.

---

## 21/10/2026 — PUT /eletronico (Atualização Cadastral)
* **Integrantes e papéis:** Driver: Estudante A | Navigator: Estudante B
* **Problema:** Tratar atualizações validando se o lote informado já pertence a um produto distinto.
* **Nossa tentativa:** Executar o UPDATE diretamente e tratar erros de chave única (Unique Key).
* **Prompt enviado à IA:** "Como verificar no PUT se um número de lote pertence a outro ID de produto?"
* **Sugestão da IA:** Consultar o lote com `Eletronico::buscarPorLote()` e verificar se o `id_eletronico` retornado é diferente do ID a ser atualizado.
* **Decisão da dupla:** Aplicar a verificação prévia no controller e retornar status HTTP 409 em caso de colisão.
* **Teste realizado:** PUT `http://localhost/GestaoEstoqueEletroMVC/PUBLIC/eletronico`
* **Resultado obtido:** HTTP 200 | `{"erro": false, "mensagem": "Eletrônico atualizado com sucesso"}`
* **Evidência:** `EVIDÊNCIAS-POSTMAN/PUT eletronico.png`
* **O que aprendemos:** A validação antecipada de regras de negócio evita exceções genéricas de base de dados.

---

## 21/10/2026 — DELETE /eletronico (Exclusão Lógica)
* **Integrantes e papéis:** Driver: Estudante B | Navigator: Estudante A
* **Problema:** Remover o acesso ao produto sem violar as restrições de Foreign Key na tabela `movimentacoes`.
* **Nossa tentativa:** Executar `DELETE FROM eletronicos`, o que gerava um erro de restrição de chave estrangeira.
* **Prompt enviado à IA:** "Como implementar exclusão lógica alterando o campo ativo para 0 mantendo a integridade referencial?"
* **Sugestão da IA:** Substituir a remoção por `UPDATE eletronicos SET ativo = 0 WHERE id_eletronico = :id` e filtrar `ativo = 1` em todas as consultas de leitura.
* **Decisão da dupla:** Implementar `Eletronico::excluirLogicamente()` e atualizar todos os SELECTs para incluir `WHERE ativo = 1`.
* **Teste realizado:** DELETE `http://localhost/GestaoEstoqueEletroMVC/PUBLIC/eletronico?id=2`
* **Resultado obtido:** HTTP 200 | `{"erro": false, "mensagem": "Eletrônico desativado com sucesso"}`
* **Evidência:** `EVIDÊNCIAS-POSTMAN/DELETE eletronicoid=2.png`
* **O que aprendemos:** A exclusão lógica (soft delete) preserva o histórico auditável do sistema e a integridade dos dados.

---

## 21/10/2026 — PUT /estoque (Movimentação de Entrada e Saída)
* **Integrantes e papéis:** Driver: Estudante A | Navigator: Estudante B
* **Problema:** Atualizar o saldo de forma segura prevenindo condições de corrida e calculando `saldo_anterior` e `saldo_posterior`.
* **Nossa tentativa:** Fazer a leitura do saldo, calcular no PHP e gravar sem bloqueio.
* **Prompt enviado à IA:** "Como utilizar SELECT ... FOR UPDATE em PDO para bloquear o registo durante a atualização de estoque?"
* **Sugestão da IA:** Abrir transação e efetuar a consulta com a cláusula `FOR UPDATE`, impedindo que outras requisições alterem a mesma linha até a conclusão do commit.
* **Decisão da dupla:** Implementar o bloqueio pessimista no método `Estoque::movimentar()`.
* **Teste realizado:** PUT `http://localhost/GestaoEstoqueEletroMVC/PUBLIC/estoque`
* **Resultado obtido:** HTTP 200 | `{"erro": false, "mensagem": "Estoque movimentado com sucesso", "novo_saldo": 15}`
* **Evidência:** `EVIDÊNCIAS-POSTMAN/PUT estoque.png`
* **O que aprendemos:** O bloqueio pessimista é indispensável para evitar divergências em operações financeiras e de inventário.

---

## 21/10/2026 — GET /movimentacoes e GET /eletronico
* **Integrantes e papéis:** Driver: Estudante B | Navigator: Estudante A
* **Problema:** Exibir o histórico ordenado e a lista de produtos trazendo o nome do utilizador e o nome do produto via JOIN.
* **Nossa tentativa:** Consultar apenas os IDs sem relacionar com as tabelas principais.
* **Prompt enviado à IA:** "Como estruturar a consulta SQL com INNER JOIN para trazer nomes de utilizador e produto no histórico?"
* **Sugestão da IA:** Unir `movimentacoes` com `eletronicos` e `usuarios` selecionando as colunas descritivas necessárias.
* **Decisão da dupla:** Aplicar os JOINs nas consultas dos Models `Estoque` e `Eletronico`.
* **Teste realizado:** GET `http://localhost/GestaoEstoqueEletroMVC/PUBLIC/eletronico` e GET `http://localhost/GestaoEstoqueEletroMVC/PUBLIC/movimentacoes`
* **Resultado obtido:** HTTP 200 | Dados retornados com informações completas e legíveis.
* **Evidências:** `EVIDÊNCIAS-POSTMAN/GET eletronico.png` e `EVIDÊNCIAS-POSTMAN/GET movimentacoes.png`
* **O que aprendemos:** A realização de JOINs otimiza o número de pedidos ao servidor, entregando objetos JSON ricos para o cliente.