<?php
require_once __DIR__ . '/bootstrap.php';

try {
    apiDispatch();
} catch (ApiException $e) {
    JsonResponse::send(['error' => $e->getMessage()], $e->getStatus());
} catch (PDOException $e) {
    error_log('API database error: ' . $e->getMessage());
    JsonResponse::send(['error' => 'Erro ao acessar o banco de dados.'], 500);
} catch (Throwable $e) {
    error_log('API error: ' . $e->getMessage());
    JsonResponse::send(['error' => 'Erro interno do servidor.'], 500);
}
