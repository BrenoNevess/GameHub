<?php
class GameRepository {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function saveGame(array $game) {
        $statement = $this->pdo->prepare(
            'INSERT INTO games
                (rawg_id, name, slug, released, background_image, rating, metacritic,
                 publishers, genres, platforms, updated_at)
             VALUES
                (:rawg_id, :name, :slug, :released, :background_image, :rating, :metacritic,
                 :publishers, :genres, :platforms, CURRENT_TIMESTAMP)
             ON DUPLICATE KEY UPDATE
                name = VALUES(name), slug = VALUES(slug), released = VALUES(released),
                background_image = VALUES(background_image), rating = VALUES(rating),
                metacritic = VALUES(metacritic), publishers = VALUES(publishers),
                genres = VALUES(genres), platforms = VALUES(platforms),
                updated_at = CURRENT_TIMESTAMP'
        );
        $name = (string) ($game['name'] ?? '');
        if ($name === '' || !isset($game['id']) || !is_numeric($game['id'])) {
            throw new ApiException('A RAWG retornou dados incompletos para o jogo.', 502);
        }
        $statement->execute([
            ':rawg_id' => (int) $game['id'],
            ':name' => $name,
            ':slug' => (string) ($game['slug'] ?? ''),
            ':released' => $this->dateOrNull($game['released'] ?? null),
            ':background_image' => $game['background_image'] ?? null,
            ':rating' => is_numeric($game['rating'] ?? null) ? $game['rating'] : null,
            ':metacritic' => is_numeric($game['metacritic'] ?? null) ? (int) $game['metacritic'] : null,
            ':publishers' => $this->names($game['publishers'] ?? []),
            ':genres' => $this->names($game['genres'] ?? []),
            ':platforms' => $this->platformNames($game['platforms'] ?? []),
        ]);
    }

    public function addToLibrary($userId, $gameId, array $entry) {
        $statement = $this->pdo->prepare(
            'INSERT INTO user_games (user_id, rawg_id, status, hours_played, progress_percent)
             VALUES (:user_id, :rawg_id, :status, :hours_played, :progress_percent)'
        );
        try {
            $statement->execute([
                ':user_id' => $userId,
                ':rawg_id' => $gameId,
                ':status' => $entry['status'] ?? 'backlog',
                ':hours_played' => $entry['hours_played'] ?? 0,
                ':progress_percent' => $entry['progress_percent'] ?? 0,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new ApiException('Este jogo já está na sua biblioteca.', 409);
            }
            throw $e;
        }
    }

    public function listLibrary($userId, array $filters) {
        $where = ['ug.user_id = :user_id'];
        $parameters = [':user_id' => $userId];
        if (isset($filters['status'])) {
            $where[] = 'ug.status = :status';
            $parameters[':status'] = $filters['status'];
        }
        if (isset($filters['search']) && $filters['search'] !== '') {
            $where[] = 'g.name LIKE :search';
            $parameters[':search'] = '%' . $filters['search'] . '%';
        }

        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(50, max(1, (int) ($filters['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $statement = $this->pdo->prepare(
            'SELECT g.rawg_id, g.name, g.slug, g.released, g.background_image, g.rating,
                    g.metacritic, g.publishers, g.genres, g.platforms, ug.status,
                    ug.hours_played, ug.progress_percent, ug.added_at,
                    r.rating AS user_rating, r.review AS user_review
             FROM user_games ug
             JOIN games g ON g.rawg_id = ug.rawg_id
             LEFT JOIN game_reviews r ON r.user_id = ug.user_id AND r.rawg_id = ug.rawg_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY ug.added_at DESC
             LIMIT :limit OFFSET :offset'
        );
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function updateLibrary($userId, $gameId, array $changes) {
        $sets = [];
        $parameters = [':user_id' => $userId, ':rawg_id' => $gameId];
        foreach (['status', 'hours_played', 'progress_percent'] as $field) {
            if (array_key_exists($field, $changes)) {
                $sets[] = $field . ' = :' . $field;
                $parameters[':' . $field] = $changes[$field];
            }
        }
        if (!$sets) {
            throw new ApiException('Informe ao menos um campo para atualizar.', 422);
        }
        $statement = $this->pdo->prepare(
            'UPDATE user_games SET ' . implode(', ', $sets) . '
             WHERE user_id = :user_id AND rawg_id = :rawg_id'
        );
        $statement->execute($parameters);
        if ($statement->rowCount() === 0 && !$this->isInLibrary($userId, $gameId)) {
            throw new ApiException('Jogo não encontrado na biblioteca.', 404);
        }
        return $this->isInLibrary($userId, $gameId);
    }

    public function removeFromLibrary($userId, $gameId) {
        $statement = $this->pdo->prepare(
            'DELETE FROM user_games WHERE user_id = :user_id AND rawg_id = :rawg_id'
        );
        $statement->execute([':user_id' => $userId, ':rawg_id' => $gameId]);
        return $statement->rowCount() > 0;
    }

    public function listReviews($gameId, $page, $limit) {
        $offset = ($page - 1) * $limit;
        $statement = $this->pdo->prepare(
            'SELECT r.rating, r.review, r.created_at, r.updated_at,
                    u.id AS user_id, u.name AS user_name
             FROM game_reviews r
             JOIN api_users u ON u.id = r.user_id
             WHERE r.rawg_id = :rawg_id
             ORDER BY r.updated_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue(':rawg_id', $gameId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function saveReview($userId, $gameId, $rating, $review) {
        if (!$this->isInLibrary($userId, $gameId)) {
            throw new ApiException('Adicione o jogo à sua biblioteca antes de avaliá-lo.', 409);
        }
        $statement = $this->pdo->prepare(
            'INSERT INTO game_reviews (user_id, rawg_id, rating, review)
             VALUES (:user_id, :rawg_id, :rating, :review)
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), review = VALUES(review),
                                     updated_at = CURRENT_TIMESTAMP'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':rawg_id' => $gameId,
            ':rating' => $rating,
            ':review' => $review,
        ]);
    }

    public function removeReview($userId, $gameId) {
        $statement = $this->pdo->prepare(
            'DELETE FROM game_reviews WHERE user_id = :user_id AND rawg_id = :rawg_id'
        );
        $statement->execute([':user_id' => $userId, ':rawg_id' => $gameId]);
        return $statement->rowCount() > 0;
    }

    public function gameExists($gameId) {
        $statement = $this->pdo->prepare('SELECT 1 FROM games WHERE rawg_id = :rawg_id');
        $statement->execute([':rawg_id' => $gameId]);
        return (bool) $statement->fetchColumn();
    }

    private function isInLibrary($userId, $gameId) {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM user_games WHERE user_id = :user_id AND rawg_id = :rawg_id'
        );
        $statement->execute([':user_id' => $userId, ':rawg_id' => $gameId]);
        return (bool) $statement->fetchColumn();
    }

    private function dateOrNull($value) {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }

    private function names(array $items) {
        $names = [];
        foreach ($items as $item) {
            if (is_array($item) && isset($item['name']) && is_string($item['name'])) {
                $names[] = $item['name'];
            }
        }
        return json_encode($names, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function platformNames(array $items) {
        $platforms = [];
        foreach ($items as $item) {
            if (isset($item['platform']['name']) && is_string($item['platform']['name'])) {
                $platforms[] = $item['platform']['name'];
            }
        }
        return json_encode($platforms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
