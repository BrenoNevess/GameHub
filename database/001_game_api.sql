CREATE TABLE IF NOT EXISTS api_users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_api_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_api_tokens_hash (token_hash),
    KEY idx_api_tokens_user (user_id),
    CONSTRAINT fk_api_tokens_user
        FOREIGN KEY (user_id) REFERENCES api_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS games (
    rawg_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    released DATE NULL,
    background_image TEXT NULL,
    rating DECIMAL(4, 2) NULL,
    metacritic SMALLINT UNSIGNED NULL,
    publishers TEXT NOT NULL,
    genres TEXT NOT NULL,
    platforms TEXT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (rawg_id),
    KEY idx_games_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_games (
    user_id BIGINT UNSIGNED NOT NULL,
    rawg_id BIGINT UNSIGNED NOT NULL,
    status ENUM(
        'wishlist',
        'playing',
        'up_next',
        'backlog',
        'paused',
        'completed',
        'retired'
    ) NOT NULL DEFAULT 'backlog',
    hours_played DECIMAL(8, 2) UNSIGNED NOT NULL DEFAULT 0,
    progress_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
    added_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, rawg_id),
    KEY idx_user_games_status (user_id, status),
    CONSTRAINT fk_user_games_user
        FOREIGN KEY (user_id) REFERENCES api_users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_games_game
        FOREIGN KEY (rawg_id) REFERENCES games (rawg_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS game_reviews (
    user_id BIGINT UNSIGNED NOT NULL,
    rawg_id BIGINT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    review TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, rawg_id),
    KEY idx_game_reviews_game (rawg_id, updated_at),
    CONSTRAINT chk_game_reviews_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_game_reviews_library
        FOREIGN KEY (user_id, rawg_id)
        REFERENCES user_games (user_id, rawg_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
