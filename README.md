#  Gestão de Estoque de Eletrónicos — API MVC em PHP

API RESTful desenvolvida em PHP utilizando a arquitetura Model-View-Controller (MVC) e MySQL para a gestão, controlo de stock e movimentação de produtos eletrónicos.

---

##  Requisitos do Sistema

Para executar este projeto localmente, certifica-te de que tens instalado:

- **PHP**: Versão 8.0 ou superior (com extensões `pdo_mysql` e `json` ativas)
- **Base de Dados**: MySQL Server 8.0+ ou MariaDB
- **Servidor Web**: Apache (XAMPP, WAMP ou servidor embutido do PHP) com suporte a `mod_rewrite`
- **Cliente HTTP**: Thunder Client (extensão do VS Code), Postman ou Insomnia para testes de API

---

##  Configuração Local

Siga os passos abaixo para configurar e rodar o projeto na tua máquina:

### 1. Clonar ou Mover o Projeto
Coloca a pasta `GestaoEstoqueEletroMVC` no diretório raiz do teu servidor web (exemplo: `htdocs` no XAMPP ou `www` no WAMP).

### 2. Configurar a Base de Dados
1. Inicia o servidor **MySQL**.
2. Executa o ficheiro de script SQL `banco.sql` (disponível no projeto) no MySQL Workbench, phpMyAdmin ou via CLI.
3. O script criará a base de dados `gestao_estoque_eletro` com as tabelas:
   - `usuarios`
   - `eletronicos`
   - `estoque`
   - `movimentacoes`
4. O script insere automaticamente os utilizadores padrão (`id_usuario = 1` para Administrador) e dados iniciais de teste.

### 3. Ajustar Conexão (`config.php`)
Verifica se as tuas credenciais de acesso ao MySQL no ficheiro de configuração correspondem ao ambiente local:
- **Host**: `localhost`
- **Porta**: `3306`
- **Base de Dados**: `gestao_estoque_eletro`
- **Utilizador**: `root`
- **Palavra-passe**: `""` (ou a tua senha local)

---

##  URL Base da API

A URL base para acesso a todas as rotas da API é:

```text
http://localhost/GestaoEstoqueEletroMVC/PUBLIC/eletronico