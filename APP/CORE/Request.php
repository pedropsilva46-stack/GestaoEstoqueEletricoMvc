<?php
namespace App\Core;

class Request {
    public static function method(): string {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public static function path(): string {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        
        if (strpos($uri, $scriptName) === 0) {
            $uri = substr($uri, strlen($scriptName));
        }

        $uri = '/' . trim($uri, '/');
        return $uri === '/PUBLIC' || $uri === '/public' ? '/' : $uri;
    }

    public static function query(string $key, $default = null) {
        return $_GET[$key] ?? $default;
    }

    public static function input(): array {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        return is_array($json) ? $json : [];
    }
}