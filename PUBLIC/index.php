<?php
require_once __DIR__ . '/../CONFIG/config.php';
require_once __DIR__ . '/../APP/CORE/Database.php';
require_once __DIR__ . '/../APP/VIEWS/JsonView.php';
require_once __DIR__ . '/../APP/MODELS/Eletronico.php';
require_once __DIR__ . '/../APP/CONTROLLERS/EletronicoController.php';

use Controllers\EletronicoController;
use Views\JsonView;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Normaliza e extrai apenas a rota final
if (false !== ($pos = strpos($uri, 'index.php'))) {
    $uri = substr($uri, $pos + strlen('index.php'));
} elseif (false !== ($pos = strpos($uri, 'PUBLIC'))) {
    $uri = substr($uri, $pos + strlen('PUBLIC'));
}

$uri = '/' . trim($uri, '/');

// Tratamento de parâmetro ?id=X via query string
$id = $_GET['id'] ?? null;

switch ($uri) {
    case '/eletronico':
        $controller = new EletronicoController();
        if ($method === 'GET') {
            if ($id) {
                $controller->buscarPorId($id);
            } else {
                $controller->listar();
            }
        } elseif ($method === 'POST') {
            $controller->cadastrar();
        } elseif ($method === 'PUT') {
            $controller->atualizar();
        } elseif ($method === 'DELETE') {
            $controller->desativar();
        } else {
            JsonView::render(["erro" => true, "mensagem" => "Método não permitido"], 405);
        }
        break;

    default:
        JsonView::render(["erro" => true, "mensagem" => "Rota não encontrada."], 404);
        break;
}