<?php
require_once __DIR__ . '/../Model/DAO/conexao.php';
require_once __DIR__ . '/src/ApiException.php';
require_once __DIR__ . '/src/JsonResponse.php';
require_once __DIR__ . '/src/Request.php';
require_once __DIR__ . '/src/AuthRepository.php';
require_once __DIR__ . '/src/GameRepository.php';
require_once __DIR__ . '/src/RawgClient.php';
require_once __DIR__ . '/src/AuthController.php';
require_once __DIR__ . '/src/GameController.php';

function apiPdo() {
    return Conexao::getInstance();
}

function apiDispatch() {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (isset($_GET['route'])) {
        $path = (string) $_GET['route'];
    } else {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/api', PHP_URL_PATH);
        $path = preg_replace('#^.*?/api(?:/|$)#', '', (string) $path);
    }
    $path = preg_replace('#^index\.php/?#', '', $path);
    $path = trim($path, '/');
    $segments = $path === '' ? [] : explode('/', $path);

    if ($method === 'OPTIONS') {
        JsonResponse::send(null, 204);
    }

    if ($segments === ['auth', 'register'] && $method === 'POST') {
        (new AuthController(new AuthRepository(apiPdo())))->register(Request::json());
    }
    if ($segments === ['auth', 'login'] && $method === 'POST') {
        (new AuthController(new AuthRepository(apiPdo())))->login(Request::json());
    }
    if ($segments === ['auth', 'logout'] && $method === 'POST') {
        (new AuthController(new AuthRepository(apiPdo())))->logout();
    }
    if ($segments === ['games'] && $method === 'GET') {
        (new GameController(null, new RawgClient(getenv('RAWG_API_KEY') ?: '')))
            ->search($_GET);
    }
    if (count($segments) === 2 && $segments[0] === 'games' && ctype_digit($segments[1])
        && $method === 'GET') {
        $gameId = (int) $segments[1];
        (new GameController(null, new RawgClient(getenv('RAWG_API_KEY') ?: '')))
            ->show($gameId);
    }
    if (count($segments) === 3 && $segments[0] === 'games'
        && ctype_digit($segments[1]) && $segments[2] === 'reviews' && $method === 'GET') {
        (new GameController(new GameRepository(apiPdo()), null))
            ->reviews((int) $segments[1], $_GET);
    }
    if ($segments === ['library'] && $method === 'GET') {
        (new GameController(new GameRepository(apiPdo()), null))
            ->libraryList($_GET);
    }
    if ($segments === ['library'] && $method === 'POST') {
        (new GameController(
            new GameRepository(apiPdo()),
            new RawgClient(getenv('RAWG_API_KEY') ?: '')
        ))->libraryAdd(Request::json());
    }
    if (count($segments) === 2 && $segments[0] === 'library' && ctype_digit($segments[1])) {
        $gameId = (int) $segments[1];
        if ($method === 'PATCH') {
            (new GameController(new GameRepository(apiPdo()), null))
                ->libraryUpdate($gameId, Request::json());
        }
        if ($method === 'DELETE') {
            (new GameController(new GameRepository(apiPdo()), null))
                ->libraryRemove($gameId);
        }
    }
    if (count($segments) === 3 && $segments[0] === 'games'
        && ctype_digit($segments[1]) && $segments[2] === 'review') {
        if ($method === 'PUT') {
            (new GameController(new GameRepository(apiPdo()), null))
                ->reviewSave((int) $segments[1], Request::json());
        }
        if ($method === 'DELETE') {
            (new GameController(new GameRepository(apiPdo()), null))
                ->reviewRemove((int) $segments[1]);
        }
    }

    throw new ApiException('Endpoint não encontrado.', 404);
}
