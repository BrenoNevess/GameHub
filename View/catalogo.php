<?php
session_start();

require_once __DIR__ . '/../Model/CatalogoJogos.php';

$basePath = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/View/catalogo.php')));
$basePath = rtrim($basePath, '/');
if ($basePath === '/') {
    $basePath = '';
}
$viewPath = $basePath . '/View';

$userId = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;
$isLoggedIn = (is_int($userId) || is_string($userId) && ctype_digit($userId))
    && (int) $userId > 0;

$jogos = gamehub_catalogo();

$generos = [];
foreach ($jogos as $jogo) {
    if (!in_array($jogo['genre'], $generos, true)) {
        $generos[] = $jogo['genre'];
    }
}

$q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$genero = isset($_GET['genero']) && is_string($_GET['genero']) ? trim($_GET['genero']) : 'Todos';

function gamehub_normalizar_busca(string $texto): string {
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($texto, 'UTF-8');
    }

    if (function_exists('iconv')) {
        $textoTransliterado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        if ($textoTransliterado !== false) {
            $texto = $textoTransliterado;
        }
    }

    return strtolower($texto);
}

$qNormalizado = gamehub_normalizar_busca($q);
$jogosVisiveis = array_values(array_filter($jogos, function ($jogo) use ($qNormalizado, $genero) {
    $nomeCombina = $qNormalizado === '' || strpos(gamehub_normalizar_busca($jogo['name']), $qNormalizado) !== false;
    $generoCombina = $genero === 'Todos' || $jogo['genre'] === $genero;
    return $nomeCombina && $generoCombina;
}));

$jogosPorPagina = 12;
$totalJogos = count($jogosVisiveis);
$totalPaginas = max(1, (int) ceil($totalJogos / $jogosPorPagina));
$pagina = isset($_GET['pagina']) && ctype_digit((string) $_GET['pagina'])
    ? max(1, (int) $_GET['pagina'])
    : 1;
$pagina = min($pagina, $totalPaginas);
$inicio = ($pagina - 1) * $jogosPorPagina;
$jogosPagina = array_slice($jogosVisiveis, $inicio, $jogosPorPagina);

function gamehub_query_catalogo(string $baseUrl, string $q, string $genero, int $pagina): string {
    $parametros = [];
    if ($q !== '') {
        $parametros['q'] = $q;
    }
    if ($genero !== 'Todos') {
        $parametros['genero'] = $genero;
    }
    $parametros['pagina'] = $pagina;
    return $baseUrl . '?' . http_build_query($parametros);
}

function gamehub_formatar_preco($valor) {
    if ($valor === null) {
        return null;
    }
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111522">
    <meta name="description" content="Pesquise por nome, filtre por gênero e compare preços de todos os jogos do GameHub.">
    <link rel="stylesheet" type="text/css" href="<?= htmlspecialchars($basePath . '/Styles/index.css', ENT_QUOTES, 'UTF-8') ?>">
    <title>Catálogo de jogos — GameHub</title>
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="<?= htmlspecialchars($basePath . '/index.php', ENT_QUOTES, 'UTF-8') ?>" aria-label="GameHub - início">
                <span class="brand-mark" aria-hidden="true">🎮</span>
                <span>GameHub</span>
            </a>
            <nav class="primary-nav" aria-label="Navegação principal">
                <a class="nav-link" href="<?= htmlspecialchars($basePath . '/index.php', ENT_QUOTES, 'UTF-8') ?>">Início</a>
                <a class="nav-link active" href="<?= htmlspecialchars($viewPath . '/catalogo.php', ENT_QUOTES, 'UTF-8') ?>" aria-current="page">Catálogo</a>
                <a class="nav-link" href="<?= htmlspecialchars($viewPath . '/favoritos.php', ENT_QUOTES, 'UTF-8') ?>">Favoritos</a>
                <a class="nav-link" href="<?= htmlspecialchars($viewPath . '/suporte.php', ENT_QUOTES, 'UTF-8') ?>">Suporte</a>
            </nav>
            <form class="header-search" action="<?= htmlspecialchars($viewPath . '/catalogo.php', ENT_QUOTES, 'UTF-8') ?>" method="get">
                <input type="search" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Pesquise pelo nome do jogo..." aria-label="Pesquisar jogos" minlength="2">
                <input type="hidden" name="genero" value="<?= htmlspecialchars($genero, ENT_QUOTES, 'UTF-8') ?>">
                <button class="search-button" type="submit">Pesquisar</button>
            </form>
            <a class="login-link" href="<?= htmlspecialchars($viewPath . ($isLoggedIn ? '/favoritos.php' : '/login.php'), ENT_QUOTES, 'UTF-8') ?>">
                <span aria-hidden="true"><?= $isLoggedIn ? '★' : '↪' ?></span>
                <?= $isLoggedIn ? 'Minha conta' : 'Entrar' ?>
            </a>
        </div>
    </header>

    <main>
        <div class="content">
            <section class="game-section" aria-labelledby="catalogo-title">
                <div class="section-heading">
                    <div>
                        <h2 id="catalogo-title"><span aria-hidden="true">✦ </span>Catálogo de jogos</h2>
                        <p>Pesquise por nome, filtre por gênero e compare preços de todos os jogos do GameHub.</p>
                    </div>
                </div>

                <form class="header-search" action="<?= htmlspecialchars($viewPath . '/catalogo.php', ENT_QUOTES, 'UTF-8') ?>" method="get" style="margin-bottom: 1.5rem; max-width: none;">
                    <input type="search" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Digite o nome do jogo" aria-label="Pesquisar jogos" minlength="2">
                    <select name="genero" aria-label="Filtrar por gênero" onchange="this.form.submit()">
                        <option value="Todos"<?= $genero === 'Todos' ? ' selected' : '' ?>>Todos os gêneros</option>
                        <?php foreach ($generos as $g): ?>
                            <option value="<?= htmlspecialchars($g, ENT_QUOTES, 'UTF-8') ?>"<?= $genero === $g ? ' selected' : '' ?>><?= htmlspecialchars($g, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="search-button" type="submit">Pesquisar</button>
                </form>

                <p class="section-status" style="padding: 0 0 1rem;">
                    <?= count($jogosVisiveis) ?> <?= count($jogosVisiveis) === 1 ? 'jogo encontrado' : 'jogos encontrados' ?>
                </p>

                <?php if (count($jogosVisiveis) > 0): ?>
                    <div class="game-grid" id="catalogo-games">
                        <?php foreach ($jogosPagina as $jogo): ?>
                            <article class="game-card" tabindex="0">
                                <div class="game-image-wrap">
                                    <img class="game-image" src="<?= htmlspecialchars($jogo['cover_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" referrerpolicy="no-referrer"
                                        onerror="this.remove(); this.parentElement.insertAdjacentHTML('afterbegin', '&lt;div class=&quot;image-fallback&quot; aria-hidden=&quot;true&quot;&gt;🎮&lt;/div&gt;');">
                                    <span class="rating-badge"><?= $jogo['catalog_rating'] !== null ? '★ ' . number_format((float) $jogo['catalog_rating'], 1, ',', '.') : 'Sem nota' ?></span>
                                    <div class="game-preview" aria-label="Informações do jogo">
                                        <dl>
                                            <div><dt>Gênero</dt><dd><?= htmlspecialchars($jogo['genre'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                            <div><dt>Ano</dt><dd><?= htmlspecialchars((string) $jogo['year'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                            <div><dt>Desenvolvedora</dt><dd><?= htmlspecialchars($jogo['developer'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                            <div><dt>História</dt><dd><?= htmlspecialchars($jogo['main_story_label'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                            <div><dt>Conclusão</dt><dd><?= htmlspecialchars($jogo['completionist_label'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                            <div><dt>Plataformas</dt><dd><?= htmlspecialchars($jogo['platform'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                        </dl>
                                    </div>
                                </div>
                                <div class="game-info">
                                    <div class="game-meta">
                                        <span><?= htmlspecialchars((string) $jogo['year'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="critic-score"><?= htmlspecialchars($jogo['genre'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <h3><?= htmlspecialchars($jogo['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <?php if ($jogo['best_price'] !== null): ?>
                                        <span class="game-action">A partir de <?= htmlspecialchars(gamehub_formatar_preco($jogo['best_price']), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php else: ?>
                                        <span class="game-action">Preço indisponível</span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($totalPaginas > 1): ?>
                        <nav class="pagination" aria-label="Paginação do catálogo">
                            <?php if ($pagina > 1): ?>
                                <a class="pagination-link pagination-direction" href="<?= htmlspecialchars(gamehub_query_catalogo($viewPath . '/catalogo.php', $q, $genero, $pagina - 1), ENT_QUOTES, 'UTF-8') ?>" aria-label="Página anterior">← Anterior</a>
                            <?php else: ?>
                                <span class="pagination-link pagination-direction disabled" aria-disabled="true">← Anterior</span>
                            <?php endif; ?>

                            <?php for ($numeroPagina = 1; $numeroPagina <= $totalPaginas; $numeroPagina++): ?>
                                <?php if ($numeroPagina === $pagina): ?>
                                    <span class="pagination-link active" aria-current="page"><?= $numeroPagina ?></span>
                                <?php else: ?>
                                    <a class="pagination-link" href="<?= htmlspecialchars(gamehub_query_catalogo($viewPath . '/catalogo.php', $q, $genero, $numeroPagina), ENT_QUOTES, 'UTF-8') ?>"><?= $numeroPagina ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($pagina < $totalPaginas): ?>
                                <a class="pagination-link pagination-direction" href="<?= htmlspecialchars(gamehub_query_catalogo($viewPath . '/catalogo.php', $q, $genero, $pagina + 1), ENT_QUOTES, 'UTF-8') ?>" aria-label="Próxima página">Próxima →</a>
                            <?php else: ?>
                                <span class="pagination-link pagination-direction disabled" aria-disabled="true">Próxima →</span>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="section-status">Nenhum jogo encontrado. Tente outro nome ou remova o filtro de gênero.</div>
                <?php endif; ?>
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
</body>
</html>
