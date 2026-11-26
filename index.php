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
    <title>GameHub — Descubra seu próximo jogo</title>
    <style>
        :root {
            color-scheme: dark;
            --page: #111522;
            --surface: #1b2233;
            --surface-raised: #20283a;
            --border: rgba(190, 199, 224, .13);
            --text: #f5f6fb;
            --muted: #a4adc1;
            --purple: #8062f4;
            --purple-light: #a391ff;
            --green: #83d6af;
            --content-width: 1180px;
        }

        * { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            background:
                radial-gradient(ellipse at 50% 5%, rgba(84, 67, 157, .2), transparent 34rem),
                var(--page);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.5;
        }

        a { color: inherit; text-decoration: none; }
        button, input { font: inherit; }
        button, a { -webkit-tap-highlight-color: transparent; }

        .site-header {
            position: sticky;
            z-index: 5;
            top: 0;
            border-bottom: 1px solid var(--border);
            background: rgba(17, 21, 34, .92);
            backdrop-filter: blur(18px);
        }

        .header-inner {
            width: min(var(--content-width), calc(100% - 48px));
            min-height: 78px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            flex: 0 0 auto;
            font-size: 1.18rem;
            font-weight: 750;
            letter-spacing: -.04em;
        }

        .brand-mark {
            display: grid;
            width: 40px;
            height: 40px;
            place-items: center;
            border-radius: 13px;
            background: linear-gradient(145deg, #9278ff, #6546d5);
            box-shadow: 0 7px 22px rgba(111, 82, 228, .24);
            font-size: 1.2rem;
        }

        .header-search {
            display: flex;
            min-width: 180px;
            max-width: 520px;
            height: 44px;
            margin: 0 auto;
            padding: 4px;
            flex: 1;
            border: 1px solid var(--border);
            border-radius: 13px;
            background: var(--surface);
        }

        .header-search input, .hero-search input {
            width: 100%;
            min-width: 0;
            padding: 0 13px;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--text);
        }

        input::placeholder { color: #929bb0; }

        .search-button, .login-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 40px;
            padding: 0 17px;
            border: 0;
            border-radius: 10px;
            background: var(--purple);
            color: #fff;
            font-size: .9rem;
            font-weight: 650;
            white-space: nowrap;
            cursor: pointer;
            transition: background .18s ease, transform .18s ease;
        }

        .search-button:hover, .login-link:hover { background: #9279ff; transform: translateY(-1px); }

        .login-link {
            min-height: 42px;
            border: 1px solid var(--border);
            background: rgba(255,255,255,.025);
        }

        .login-link:hover { background: var(--surface-raised); }

        .menu-link {
            display: inline-grid;
            width: 42px;
            height: 42px;
            place-items: center;
            border: 1px solid var(--border);
            border-radius: 11px;
            color: #c4cada;
            font-size: 1.25rem;
        }

        .hero {
            width: min(900px, calc(100% - 40px));
            margin: 0 auto;
            padding: 104px 0 84px;
            text-align: center;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 13px;
            border: 1px solid rgba(163, 145, 255, .2);
            border-radius: 999px;
            background: rgba(126, 96, 238, .09);
            color: #c3b7ff;
            font-size: .85rem;
        }

        h1 {
            margin: 25px 0 18px;
            font-size: clamp(2.5rem, 6vw, 4.65rem);
            line-height: 1.08;
            letter-spacing: -.065em;
        }

        h1 span {
            display: block;
            margin-top: 6px;
            background: linear-gradient(90deg, #a18dff, #7958ec);
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-copy {
            max-width: 700px;
            margin: 0 auto;
            color: #b2bbce;
            font-size: 1.05rem;
        }

        .hero-search {
            display: flex;
            height: 62px;
            margin: 39px auto 0;
            padding: 7px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(31, 39, 57, .92);
            box-shadow: 0 18px 45px rgba(0, 0, 0, .13);
            text-align: left;
        }

        .hero-search input { padding-left: 17px; }
        .hero-search .search-button { min-width: 118px; min-height: 46px; }

        .search-hint {
            margin: 13px 0 0;
            color: #8993a9;
            font-size: .83rem;
        }

        .search-hint a { color: #b4a7ff; }
        .search-hint a:hover { text-decoration: underline; }

        .content {
            width: min(var(--content-width), calc(100% - 48px));
            margin: 0 auto;
            padding-bottom: 75px;
        }

        .game-section { margin: 0 0 65px; }

        .section-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 20px;
        }

        .section-heading h2 {
            margin: 0 0 5px;
            font-size: clamp(1.35rem, 2.4vw, 1.75rem);
            letter-spacing: -.035em;
        }

        .section-heading p {
            margin: 0;
            color: var(--muted);
            font-size: .92rem;
        }

        .section-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 4px;
            color: #b4a5ff;
            font-size: .9rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .section-link:hover { color: #d1c8ff; }

        .game-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 19px;
        }

        .game-card {
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 15px;
            background: linear-gradient(160deg, rgba(33, 42, 61, .94), rgba(25, 31, 46, .98));
            transition: border-color .2s ease, transform .2s ease;
        }

        .game-card:hover {
            transform: translateY(-4px);
            border-color: rgba(163, 145, 255, .42);
        }

        .game-image-wrap {
            position: relative;
            height: 185px;
            overflow: hidden;
            background: linear-gradient(135deg, #343252, #20283a);
        }

        .game-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .35s ease;
        }

        .game-card:hover .game-image { transform: scale(1.04); }

        .image-fallback {
            display: grid;
            height: 100%;
            place-items: center;
            color: rgba(255,255,255,.68);
            font-size: 2rem;
        }

        .rating-badge {
            position: absolute;
            right: 12px;
            bottom: 12px;
            padding: 5px 9px;
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 8px;
            background: rgba(17, 21, 34, .88);
            color: #f3d37a;
            font-size: .82rem;
            font-weight: 700;
            backdrop-filter: blur(8px);
        }

        .game-info { padding: 15px 16px 16px; }

        .game-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            color: var(--muted);
            font-size: .78rem;
        }

        .critic-score { color: var(--green); font-weight: 700; }

        .game-info h3 {
            overflow: hidden;
            margin: 8px 0 13px;
            font-size: 1.05rem;
            letter-spacing: -.02em;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .game-action {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #b7aaff;
            font-size: .86rem;
            font-weight: 650;
        }

        .game-action:hover { color: #d5ccff; }

        .section-status {
            grid-column: 1 / -1;
            min-height: 160px;
            display: grid;
            place-items: center;
            padding: 24px;
            border: 1px dashed var(--border);
            border-radius: 15px;
            color: var(--muted);
            text-align: center;
        }

        .section-status.error { border-style: solid; color: #e0b0b0; }

        .site-footer {
            border-top: 1px solid var(--border);
            background: rgba(10, 13, 22, .42);
        }

        .footer-inner {
            width: min(var(--content-width), calc(100% - 48px));
            min-height: 116px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 28px;
        }

        .footer-brand { font-size: .97rem; }
        .footer-note { margin: 5px 0 0; color: #8e98ad; font-size: .84rem; }
        .footer-links { display: flex; align-items: center; gap: 24px; color: #b4bdd0; font-size: .9rem; }
        .footer-links a:hover { color: #c7baff; }
        .footer-support { color: #c0b4ff; font-weight: 650; }

        @media (max-width: 760px) {
            .header-inner, .content, .footer-inner { width: min(100% - 32px, var(--content-width)); }
            .header-inner { min-height: 68px; gap: 12px; }
            .brand { font-size: 1rem; gap: 8px; }
            .brand-mark { width: 36px; height: 36px; }
            .header-search { height: 40px; }
            .header-search .search-button { width: 40px; min-height: 32px; padding: 0; font-size: 0; }
            .header-search .search-button::before { content: "⌕"; font-size: 1.2rem; }
            .login-link { min-height: 40px; padding: 0 11px; font-size: .82rem; }
            .menu-link { display: none; }
            .hero { padding: 78px 0 63px; }
            .content { padding-bottom: 48px; }
            .game-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
            .game-image-wrap { height: 160px; }
            .footer-inner { min-height: 125px; align-items: flex-start; justify-content: center; flex-direction: column; gap: 13px; padding: 23px 0; }
        }

        @media (max-width: 480px) {
            .header-inner { width: calc(100% - 24px); gap: 9px; }
            .brand { font-size: .92rem; }
            .brand-mark { width: 32px; height: 32px; border-radius: 10px; }
            .header-search { padding: 3px; }
            .header-search input { padding: 0 8px; font-size: .8rem; }
            .login-link { padding: 0 9px; }
            .hero { width: calc(100% - 32px); padding-top: 66px; }
            .hero-search { height: 56px; }
            .hero-search input { padding-left: 10px; font-size: .86rem; }
            .hero-search .search-button { min-width: 90px; padding: 0 10px; font-size: .82rem; }
            .section-heading { align-items: flex-start; flex-direction: column; gap: 8px; }
            .section-link { padding-bottom: 0; }
            .game-grid { gap: 10px; }
            .game-image-wrap { height: 120px; }
            .game-info { padding: 11px; }
            .game-meta { font-size: .7rem; }
            .game-info h3 { font-size: .92rem; }
            .game-action { font-size: .78rem; }
            .footer-links { flex-wrap: wrap; gap: 18px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="<?= htmlspecialchars($basePath . '/index.php', ENT_QUOTES, 'UTF-8') ?>" aria-label="GameHub - início">
                <span class="brand-mark" aria-hidden="true">🎮</span>
                <span>GameHub</span>
            </a>
            <form class="header-search" action="<?= htmlspecialchars($viewPath . '/catalogo.php', ENT_QUOTES, 'UTF-8') ?>" method="get">
                <input type="search" name="q" placeholder="Pesquise pelo nome do jogo..." aria-label="Pesquisar jogos" minlength="2">
                <button class="search-button" type="submit">Pesquisar</button>
            </form>
            <a class="login-link" href="<?= htmlspecialchars($viewPath . ($isLoggedIn ? '/favoritos.php' : '/login.php'), ENT_QUOTES, 'UTF-8') ?>">
                <span aria-hidden="true"><?= $isLoggedIn ? '★' : '↪' ?></span>
                <?= $isLoggedIn ? 'Minha conta' : 'Entrar' ?>
            </a>
            <a class="menu-link" href="<?= htmlspecialchars($viewPath . '/catalogo.php', ENT_QUOTES, 'UTF-8') ?>" aria-label="Abrir catálogo">☰</a>
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
                        <p>Descubra títulos que chegaram ao catálogo da RAWG.</p>
                    </div>
                    <a class="section-link" href="<?= htmlspecialchars($viewPath . '/catalogo.php', ENT_QUOTES, 'UTF-8') ?>">Ver catálogo <span aria-hidden="true">→</span></a>
                </div>
                <div class="game-grid" id="recent-games" aria-live="polite">
                    <div class="section-status">Carregando lançamentos recentes…</div>
                </div>
            </section>

            <section class="game-section" aria-labelledby="critics-title">
                <div class="section-heading">
                    <div>
                        <h2 id="critics-title"><span aria-hidden="true">★ </span>Destaques da crítica</h2>
                        <p>Jogos em destaque pelas avaliações do Metacritic.</p>
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

        async function loadGames(containerId, filters) {
            const container = document.getElementById(containerId);
            try {
                const response = await fetch(`${gamesEndpoint}?${filters}`, {
                    headers: { Accept: 'application/json' }
                });
                const payload = await response.json();
                if (!response.ok) {
                    throw new Error(payload.error || 'Não foi possível carregar os jogos.');
                }
                if (!Array.isArray(payload.data) || payload.data.length === 0) {
                    showSectionMessage(container, 'Nenhum jogo encontrado nesta seleção.');
                    return;
                }
                container.replaceChildren(...payload.data.map(createGameCard));
            } catch (error) {
                console.error('Falha ao carregar jogos da RAWG:', error);
                showSectionMessage(container, 'Não foi possível carregar os jogos agora. Tente novamente mais tarde ou contate o suporte.', true);
            }
        }

        loadGames('popular-games', 'ordering=-rating&page_size=6');
        loadGames('recent-games', 'ordering=-added&page_size=6');
        loadGames('critic-games', 'ordering=-metacritic&page_size=6');
    </script>
</body>
</html>
