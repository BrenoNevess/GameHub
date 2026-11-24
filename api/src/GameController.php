<?php
class GameController {
    private $repository;
    private $rawg;

    private const STATUSES = [
        'wishlist',
        'playing',
        'up_next',
        'backlog',
        'paused',
        'completed',
        'retired',
    ];

    public function __construct($repository, $rawg) {
        $this->repository = $repository;
        $this->rawg = $rawg;
    }

    public function search(array $query) {
        $filters = [];
        if (isset($query['search'])) {
            if (!is_string($query['search']) || strlen(trim($query['search'])) < 2
                || strlen($query['search']) > 100) {
                throw new ApiException('A busca deve ter entre 2 e 100 caracteres.', 422);
            }
            $filters['search'] = trim($query['search']);
        }

        $page = $this->positiveInteger($query['page'] ?? 1, 'page', 10000);
        $pageSize = $this->positiveInteger($query['page_size'] ?? 20, 'page_size', 40);
        $filters['page'] = $page;
        $filters['page_size'] = $pageSize;

        foreach (['genres', 'parent_platforms', 'ordering'] as $key) {
            if (isset($query[$key])) {
                if (!is_string($query[$key]) || strlen($query[$key]) > 100) {
                    throw new ApiException('Filtro inválido: ' . $key . '.', 422);
                }
                $filters[$key] = $query[$key];
            }
        }

        $response = $this->rawg->search($filters);
        $results = [];
        foreach (($response['results'] ?? []) as $game) {
            $results[] = $this->formatGame($game);
        }
        JsonResponse::send([
            'data' => $results,
            'pagination' => [
                'count' => (int) ($response['count'] ?? count($results)),
                'page' => $page,
                'page_size' => $pageSize,
                'has_more' => !empty($response['next']),
            ],
        ]);
    }

    public function show($gameId) {
        $game = $this->rawg->game($gameId);
        JsonResponse::send(['data' => $this->formatGame($game)]);
    }

    public function libraryList(array $query) {
        $userId = $this->authenticatedUserId();
        $filters = [];
        if (isset($query['status'])) {
            if (!in_array($query['status'], self::STATUSES, true)) {
                throw new ApiException('Status de biblioteca inválido.', 422);
            }
            $filters['status'] = $query['status'];
        }
        if (isset($query['search'])) {
            if (!is_string($query['search']) || strlen($query['search']) > 100) {
                throw new ApiException('Busca inválida.', 422);
            }
            $filters['search'] = trim($query['search']);
        }
        $filters['page'] = $this->positiveInteger($query['page'] ?? 1, 'page', 100000);
        $filters['limit'] = $this->positiveInteger($query['limit'] ?? 20, 'limit', 50);

        JsonResponse::send([
            'data' => $this->decodeLibraryRows(
                $this->repository->listLibrary($userId, $filters)
            ),
            'pagination' => [
                'page' => $filters['page'],
                'limit' => $filters['limit'],
            ],
        ]);
    }

    public function libraryAdd(array $input) {
        $userId = $this->authenticatedUserId();
        $gameId = $this->positiveInteger($input['rawg_id'] ?? null, 'rawg_id', PHP_INT_MAX);
        $entry = $this->validateLibraryChanges($input, true);

        $game = $this->rawg->game($gameId);
        $this->repository->saveGame($game);
        $this->repository->addToLibrary($userId, $gameId, $entry);
        JsonResponse::send([
            'data' => [
                'rawg_id' => $gameId,
                'name' => $game['name'] ?? '',
                'status' => $entry['status'],
                'hours_played' => $entry['hours_played'],
                'progress_percent' => $entry['progress_percent'],
            ],
        ], 201);
    }

    public function libraryUpdate($gameId, array $input) {
        $userId = $this->authenticatedUserId();
        $changes = $this->validateLibraryChanges($input, false);
        $found = $this->repository->updateLibrary($userId, $gameId, $changes);
        JsonResponse::send(['data' => ['rawg_id' => $gameId, 'updated' => $found]]);
    }

    public function libraryRemove($gameId) {
        $userId = $this->authenticatedUserId();
        if (!$this->repository->removeFromLibrary($userId, $gameId)) {
            throw new ApiException('Jogo não encontrado na sua biblioteca.', 404);
        }
        JsonResponse::send(null, 204);
    }

    public function reviews($gameId, array $query) {
        if (!$this->repository->gameExists($gameId)) {
            throw new ApiException('Jogo não encontrado no catálogo local.', 404);
        }
        $page = $this->positiveInteger($query['page'] ?? 1, 'page', 100000);
        $limit = $this->positiveInteger($query['limit'] ?? 20, 'limit', 50);
        JsonResponse::send([
            'data' => $this->repository->listReviews($gameId, $page, $limit),
            'pagination' => ['page' => $page, 'limit' => $limit],
        ]);
    }

    public function reviewSave($gameId, array $input) {
        $userId = $this->authenticatedUserId();
        $rating = filter_var($input['rating'] ?? null, FILTER_VALIDATE_INT);
        $review = $input['review'] ?? '';
        if ($rating === false || $rating < 1 || $rating > 5
            || !is_string($review) || strlen($review) > 4000) {
            throw new ApiException('Informe uma nota de 1 a 5 e uma avaliação de até 4000 caracteres.', 422);
        }
        $this->repository->saveReview($userId, $gameId, $rating, trim($review));
        JsonResponse::send(['message' => 'Avaliação salva.']);
    }

    public function reviewRemove($gameId) {
        $userId = $this->authenticatedUserId();
        if (!$this->repository->removeReview($userId, $gameId)) {
            throw new ApiException('Avaliação não encontrada.', 404);
        }
        JsonResponse::send(null, 204);
    }

    private function authenticatedUserId() {
        return Request::authenticatedUserId(new AuthRepository(apiPdo()));
    }

    private function validateLibraryChanges(array $input, $includeDefaults) {
        $allowed = ['status', 'hours_played', 'progress_percent'];
        foreach (array_keys($input) as $key) {
            if (!in_array($key, $allowed, true) && !($includeDefaults && $key === 'rawg_id')) {
                throw new ApiException('Campo não permitido: ' . $key . '.', 422);
            }
        }

        $changes = [];
        if ($includeDefaults || array_key_exists('status', $input)) {
            $status = $input['status'] ?? 'backlog';
            if (!is_string($status) || !in_array($status, self::STATUSES, true)) {
                throw new ApiException('Status de biblioteca inválido.', 422);
            }
            $changes['status'] = $status;
        }
        if ($includeDefaults || array_key_exists('hours_played', $input)) {
            $hours = $input['hours_played'] ?? 0;
            if (!is_numeric($hours) || (float) $hours < 0 || (float) $hours > 999999) {
                throw new ApiException('hours_played deve ser um número não negativo.', 422);
            }
            $changes['hours_played'] = (float) $hours;
        }
        if ($includeDefaults || array_key_exists('progress_percent', $input)) {
            $progress = filter_var($input['progress_percent'] ?? 0, FILTER_VALIDATE_INT);
            if ($progress === false || $progress < 0 || $progress > 100) {
                throw new ApiException('progress_percent deve ser um inteiro entre 0 e 100.', 422);
            }
            $changes['progress_percent'] = $progress;
        }
        return $changes;
    }

    private function positiveInteger($value, $field, $maximum) {
        $integer = filter_var($value, FILTER_VALIDATE_INT);
        if ($integer === false || $integer < 1 || $integer > $maximum) {
            throw new ApiException($field . ' deve ser um inteiro positivo dentro do limite.', 422);
        }
        return $integer;
    }

    private function formatGame(array $game) {
        $publishers = [];
        foreach (($game['publishers'] ?? []) as $publisher) {
            if (isset($publisher['name'])) {
                $publishers[] = $publisher['name'];
            }
        }
        $genres = [];
        foreach (($game['genres'] ?? []) as $genre) {
            if (isset($genre['name'])) {
                $genres[] = $genre['name'];
            }
        }
        $platforms = [];
        foreach (($game['platforms'] ?? []) as $item) {
            if (isset($item['platform']['name'])) {
                $platforms[] = $item['platform']['name'];
            }
        }

        return [
            'rawg_id' => isset($game['id']) ? (int) $game['id'] : null,
            'name' => $game['name'] ?? null,
            'slug' => $game['slug'] ?? null,
            'released' => $game['released'] ?? null,
            'background_image' => $game['background_image'] ?? null,
            'rating' => $game['rating'] ?? null,
            'metacritic' => $game['metacritic'] ?? null,
            'publishers' => $publishers,
            'genres' => $genres,
            'platforms' => $platforms,
            'description' => $game['description_raw'] ?? null,
        ];
    }

    private function decodeLibraryRows(array $rows) {
        foreach ($rows as &$row) {
            foreach (['publishers', 'genres', 'platforms'] as $field) {
                $decoded = json_decode($row[$field] ?? '[]', true);
                $row[$field] = is_array($decoded) ? $decoded : [];
            }
        }
        unset($row);
        return $rows;
    }
}
