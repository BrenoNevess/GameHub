<?php
class Request {
    public static function json() {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            throw new ApiException('Envie um corpo JSON válido.', 400);
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException('O corpo da requisição deve ser um objeto JSON válido.', 400);
        }
        return $data;
    }

    public static function authenticatedUserId(AuthRepository $repository) {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authorization = $headers['Authorization'] ?? $headers['authorization']
            ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

        if (!preg_match('/^Bearer\s+([A-Za-z0-9_-]{43})$/i', $authorization, $matches)) {
            throw new ApiException('Autenticação Bearer obrigatória.', 401);
        }

        $userId = $repository->userIdForToken($matches[1]);
        if ($userId === null) {
            throw new ApiException('Token inválido ou expirado.', 401);
        }
        return $userId;
    }
}
