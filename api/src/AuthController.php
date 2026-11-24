<?php
class AuthController {
    private $repository;

    public function __construct(AuthRepository $repository) {
        $this->repository = $repository;
    }

    public function register(array $input) {
        $name = isset($input['name']) && is_string($input['name']) ? trim($input['name']) : '';
        $email = isset($input['email']) && is_string($input['email'])
            ? strtolower(trim($input['email']))
            : '';
        $password = $input['password'] ?? null;

        if ($name === '' || strlen($name) > 120 || strlen($email) > 254
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || !is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
            throw new ApiException(
                'Informe nome, e-mail válido e senha entre 8 e 72 bytes.',
                422
            );
        }

        $userId = $this->repository->createUser(
            $name,
            $email,
            password_hash($password, PASSWORD_DEFAULT)
        );
        $this->issueToken($userId, ['id' => $userId, 'name' => $name, 'email' => $email], 201);
    }

    public function login(array $input) {
        $email = isset($input['email']) && is_string($input['email'])
            ? strtolower(trim($input['email']))
            : '';
        $password = $input['password'] ?? null;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($password)) {
            throw new ApiException('E-mail ou senha inválidos.', 401);
        }

        $user = $this->repository->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new ApiException('E-mail ou senha inválidos.', 401);
        }

        $this->issueToken((int) $user['id'], [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ]);
    }

    public function logout() {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authorization = $headers['Authorization'] ?? $headers['authorization']
            ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer\s+([A-Za-z0-9_-]{43})$/i', $authorization, $matches)) {
            throw new ApiException('Autenticação Bearer obrigatória.', 401);
        }
        $this->repository->revokeToken($matches[1]);
        JsonResponse::send(['message' => 'Sessão encerrada.']);
    }

    private function issueToken($userId, array $user, $status = 200) {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $this->repository->createToken($userId, $token);
        JsonResponse::send([
            'data' => [
                'user' => $user,
                'token_type' => 'Bearer',
                'access_token' => $token,
                'expires_in' => 2592000,
            ],
        ], $status);
    }
}
