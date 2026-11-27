<?php
session_start();

$basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$basePath = rtrim($basePath, '/');
if ($basePath === '/') {
    $basePath = '';
}
$viewPath = $basePath . '/View';

$userId = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;
$isLoggedIn = (is_int($userId) || (is_string($userId) && ctype_digit($userId)))
    && (int) $userId > 0;
$gamesEndpoint = $basePath . '/api/games';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111522">
    <meta name="description" content="Descubra jogos, confira avaliações e encontre seu próximo favorito no GameHub.">
    <link rel="stylesheet" type="text/css" href="<?= htmlspecialchars($basePath . '/Styles/index.css', ENT_QUOTES, 'UTF-8') ?>">
    <title>GameHub — Descubra seu próximo jogo</title>
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="<?= htmlspecialchars($basePath . '/index.php', ENT_QUOTES, 'UTF-8') ?>" aria-label="GameHub - início">
                <span class="brand-mark" aria-hidden="true">🎮</span>
                <span>GameHub</span>
            </a>
            <nav class="primary-nav" aria-label="Navegação principal">
                <a class="nav-link active" href="<?= htmlspecialchars($basePath . '/index.php', ENT_QUOTES, 'UTF-8') ?>" aria-current="page">Início</a>
                <a class="nav-link" href="<?= htmlspecialchars($viewPath . '/catalogo.php', ENT_QUOTES, 'UTF-8') ?>">Catálogo</a>
                <a class="nav-link" href="<?= htmlspecialchars($viewPath . '/favoritos.php', ENT_QUOTES, 'UTF-8') ?>">Favoritos</a>
                <a class="nav-link" href="<?= htmlspecialchars($viewPath . '/suporte.php', ENT_QUOTES, 'UTF-8') ?>">Suporte</a>
            </nav>
            <form class="header-search" action="<?= htmlspecialchars($viewPath . '/catalogo.php', ENT_QUOTES, 'UTF-8') ?>" method="get">
                <input type="search" name="q" placeholder="Pesquise pelo nome do jogo..." aria-label="Pesquisar jogos" minlength="2">
                <button class="search-button" type="submit">Pesquisar</button>
            </form>
            <a class="login-link" href="<?= htmlspecialchars($viewPath . ($isLoggedIn ? '/favoritos.php' : '/login.php'), ENT_QUOTES, 'UTF-8') ?>">
                <span aria-hidden="true"><?= $isLoggedIn ? '★' : '↪' ?></span>
                <?= $isLoggedIn ? 'Minha conta' : 'Entrar' ?>
            </a>
        </div>
    </header>

    <main>
        <section class="hero" aria-labelledby="hero-title">
            <div class="eyebrow"><span aria-hidden="true">✦</span> Descubra seu próximo jogo</div>
            <h1 id="hero-title">Encontre seu próximo jogo.<span>Compare. Avalie. Decida.</span></h1>
            <p class="hero-copy">Explore jogos, confira avaliações e detalhes em um só lugar. Encontre a aventura que combina com você.</p>
            <form class="hero-search" action="<?= htmlspecialchars($viewPath . '/catalogo.php', ENT_QUOTES, 'UTF-8') ?>" method="get">
                <input type="search" name="q" placeholder="Pesquise pelo nome do jogo..." aria-label="Pesquisar jogos no catálogo" minlength="2">
                <button class="search-button" type="submit">Pesquisar <span aria-hidden="true">→</span></button>
            </form>
            <p class="search-hint">Experimente buscar por
                <a href="<?= htmlspecialchars($viewPath . '/catalogo.php?q=Elden+Ring', ENT_QUOTES, 'UTF-8') ?>">Elden Ring</a>,
                <a href="<?= htmlspecialchars($viewPath . '/catalogo.php?q=Hollow+Knight', ENT_QUOTES, 'UTF-8') ?>">Hollow Knight</a>
                ou <a href="<?= htmlspecialchars($viewPath . '/catalogo.php?q=God+of+War', ENT_QUOTES, 'UTF-8') ?>">God of War</a>
            </p>
        </section>

        <div class="content">
            <section class="game-section" aria-labelledby="popular-title">
                <div class="section-heading">
                    <div>
                        <h2 id="popular-title"><span aria-hidden="true">✦ </span>Jogos populares</h2>
                        <p>Os títulos mais bem avaliados no catálogo.</p>
                    </div>
                    <a class="section-link" href="<?= htmlspecialchars($viewPath . '/catalogo.php', ENT_QUOTES, 'UTF-8') ?>">Ver catálogo <span aria-hidden="true">→</span></a>
                </div>
                <div class="game-grid" id="popular-games" aria-live="polite">
                    <div class="section-status">Carregando jogos populares…</div>
                </div>
            </section>

            <section class="game-section" aria-labelledby="recent-title">
                <div class="section-heading">
                    <div>
                        <h2 id="recent-title"><span aria-hidden="true">◷ </span>Adicionados recentemente</h2>
                        <p>Descubra títulos que chegaram ao nosso catálogo.</p>
                    </div>
                    <a class="section-link" href="<?= htmlspecialchars($viewPath . '/catalogo.php', ENT_QUOTES, 'UTF-8') ?>">Ver catálogo <span aria-hidden="true">→</span></a>
                </div>
                <div class="game-grid" id="recent-games" aria-live="polite">
                    <div class="section-status">Carregando lançamentos recentes…</div>
                </div>
            </section>

            <section class="game-section" aria-labelledby="featured-title">
                <div class="section-heading">
                    <div>
                        <h2 id="featured-title"><span aria-hidden="true">★ </span>Jogos em destaque</h2>
                        <p>Confira os jogos mais bem avaliados e descubra seu próximo favorito.</p>
                    </div>
                    <a class="section-link" href="<?= htmlspecialchars($viewPath . '/catalogo.php', ENT_QUOTES, 'UTF-8') ?>">Ver catálogo <span aria-hidden="true">→</span></a>
                </div>
                <div class="game-grid" id="critic-games" aria-live="polite">
                    <div class="section-status">Carregando destaques…</div>
                </div>
            </section>
        </div>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <div>
                <a class="brand footer-brand" href="<?= htmlspecialchars($basePath . '/index.php', ENT_QUOTES, 'UTF-8') ?>">
                    <span class="brand-mark" aria-hidden="true">🎮</span>
                    <span>GameHub</span>
                </a>
                <p class="footer-note">Descubra, compare e escolha seu próximo jogo.</p>
            </div>
            <nav class="footer-links" aria-label="Links institucionais">
                <span>© <?= date('Y') ?> GameHub</span>
                <a href="<?= htmlspecialchars($viewPath . '/catalogo.php', ENT_QUOTES, 'UTF-8') ?>">Catálogo</a>
                <a class="footer-support" href="<?= htmlspecialchars($viewPath . '/suporte.php', ENT_QUOTES, 'UTF-8') ?>">Precisa de ajuda? Fale com o suporte</a>
            </nav>
        </div>
    </footer>

    <script>
        const gamesEndpoint = <?= json_encode($gamesEndpoint, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const catalogUrl = <?= json_encode($viewPath . '/catalogo.php', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        function showSectionMessage(container, message, isError = false) {
            container.replaceChildren();
            const status = document.createElement('div');
            status.className = `section-status${isError ? ' error' : ''}`;
            status.textContent = message;
            container.append(status);
        }

        function createGameCard(game) {
            const card = document.createElement('article');
            card.className = 'game-card';

            const imageWrap = document.createElement('div');
            imageWrap.className = 'game-image-wrap';
            if (game.background_image) {
                const image = document.createElement('img');
                image.className = 'game-image';
                image.src = game.background_image;
                image.alt = '';
                image.loading = 'lazy';
                image.referrerPolicy = 'no-referrer';
                image.addEventListener('error', () => {
                    image.remove();
                    const fallback = document.createElement('div');
                    fallback.className = 'image-fallback';
                    fallback.setAttribute('aria-hidden', 'true');
                    fallback.textContent = '🎮';
                    imageWrap.prepend(fallback);
                }, { once: true });
                imageWrap.append(image);
            } else {
                const fallback = document.createElement('div');
                fallback.className = 'image-fallback';
                fallback.setAttribute('aria-hidden', 'true');
                fallback.textContent = '🎮';
                imageWrap.append(fallback);
            }

            const rating = document.createElement('span');
            rating.className = 'rating-badge';
            rating.textContent = game.rating ? `★ ${Number(game.rating).toLocaleString('pt-BR', { maximumFractionDigits: 1 })}` : 'Sem nota';
            imageWrap.append(rating);

            const info = document.createElement('div');
            info.className = 'game-info';
            const meta = document.createElement('div');
            meta.className = 'game-meta';
            const year = document.createElement('span');
            year.textContent = game.released ? game.released.slice(0, 4) : 'Data não informada';
            meta.append(year);
            if (game.metacritic) {
                const score = document.createElement('span');
                score.className = 'critic-score';
                score.textContent = `Metacritic ${game.metacritic}`;
                meta.append(score);
            }

            const title = document.createElement('h3');
            title.textContent = game.name || 'Jogo sem título';
            const action = document.createElement('a');
            action.className = 'game-action';
            action.href = `${catalogUrl}?q=${encodeURIComponent(game.name || '')}`;
            action.textContent = 'Ver no catálogo →';
            info.append(meta, title, action);
            card.append(imageWrap, info);
            return card;
        }

        function excludeFromPopular(games) {
            return games.filter(game => (
                typeof game.name !== 'string'
                || !game.name.toLowerCase().includes('hentai')
            ));
        }

        async function loadGames(containerId, filters) {
            const container = document.getElementById(containerId);
            try {
                const response = await fetch(`${gamesEndpoint}?${filters}`, {
                    headers: { Accept: 'application/json' }
                });
                let payload;
                try {
                    payload = await response.json();
                } catch {
                    throw new Error(`O servidor respondeu com um formato inesperado (HTTP ${response.status}).`);
                }
                if (!response.ok) {
                    throw new Error(
                        typeof payload.error === 'string'
                            ? payload.error
                            : `A consulta à API falhou (HTTP ${response.status}).`
                    );
                }
                if (!Array.isArray(payload.data) || payload.data.length === 0) {
                    showSectionMessage(container, 'Nenhum jogo encontrado nesta seleção.');
                    return;
                }
                const games = containerId === 'popular-games'
                    ? excludeFromPopular(payload.data)
                    : payload.data;
                if (games.length === 0) {
                    showSectionMessage(container, 'Nenhum jogo encontrado nesta seleção.');
                    return;
                }
                container.replaceChildren(...games.map(createGameCard));
            } catch (error) {
                console.error('Falha ao carregar jogos:', error);
                showSectionMessage(
                    container,
                    'Não foi possível carregar os jogos no momento. Tente novamente mais tarde.',
                    true
                );
            }
        }

        loadGames('popular-games', 'ordering=-rating&page_size=7');
        loadGames('recent-games', 'ordering=-added&page_size=6');
        loadGames('critic-games', 'ordering=-metacritic&page_size=6');
    </script>
</body>
</html>
