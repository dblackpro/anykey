<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache');

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function readAppMetadata(string $path): array
{
    // Каталогу нужны только метаданные из начала файла.
    $head = @file_get_contents($path, false, null, 0, 256 * 1024);
    if ($head === false || trim($head) === '' || !class_exists(DOMDocument::class)) {
        return [];
    }

    if (strncmp($head, "\xEF\xBB\xBF", 3) === 0) {
        $head = substr($head, 3);
    }

    $document = new DOMDocument();
    $headEnd = stripos($head, '</head>');
    if ($headEnd !== false) {
        $head = substr($head, 0, $headEnd + 7);
    }
    if (!$document->loadHTML('<?xml encoding="UTF-8">' . $head, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
        return [];
    }

    $metadata = [];
    $element = $document->getElementsByTagName('head')->item(0);
    if ($element === null) {
        return [];
    }

    $metadata['title'] = trim($element->getElementsByTagName('title')->item(0)->textContent ?? '');
    foreach ($element->getElementsByTagName('meta') as $tag) {
        $key = strtolower(trim($tag->getAttribute('name') ?: $tag->getAttribute('property')));
        $value = trim($tag->getAttribute('content'));
        if ($key !== '' && $value !== '' && !array_key_exists($key, $metadata)) {
            $metadata[$key] = $value;
        }
    }

    return $metadata;
}

// Описания из старого каталога. Файл появляется в списке только при наличии в папке.
$legacy = [
    'utm.html' => [
        'title' => 'UTM Builder',
        'description' => 'Генератор UTM-меток с пресетами для Яндекс Директ, VK, Telegram и встроенной сборкой QR-кодов.',
        'category' => 'Маркетинг', 'style' => 'marketing', 'icon' => '⚡',
        'keywords' => 'utm метки маркетинг ссылки qr код генератор',
    ],
    '3dcalc.html' => [
        'title' => '3D Signage Builder',
        'description' => '3D-визуализатор и калькулятор объемных букв. Расчет пластика PETG, LED-подсветки, времени печати и экспорт ТЗ/SVG.',
        'category' => '3D & Производство', 'style' => '3d', 'icon' => '👁️',
        'keywords' => '3d печать объемные буквы вывеска расчет petg калькулятор',
    ],
    'funmel.html' => [
        'title' => 'Funnel Decomposer',
        'description' => 'Калькулятор декомпозиции воронки продаж и юнит-экономики. Расчет "от цели" или "от бюджета" с метриками CPL, CAC и ROMI.',
        'category' => 'Бизнес & Финансы', 'style' => 'finance', 'icon' => '📉',
        'keywords' => 'воронка продаж декомпозиция конверсия cpl cac romi юнит экономика',
    ],
    'makersmark.html' => [
        'title' => "Maker's Mark Studio",
        'description' => 'Генератор фирменного клейма мейкера, авторских логотипов и маркировки для 3D-моделей, тиснения и физических изделий.',
        'category' => '3D & Брендинг', 'style' => '3d', 'icon' => '🏷️',
        'keywords' => 'makers mark клеймо мейкер логотип 3d печать маркировка штамп бренд',
    ],
];

$entries = @scandir(__DIR__);
$apps = [];
foreach ($entries ?: [] as $filename) {
    if ($filename[0] === '.' || preg_match('/^index\.html?$/i', $filename) || !preg_match('/\.html?$/i', $filename)) {
        continue;
    }

    $path = __DIR__ . DIRECTORY_SEPARATOR . $filename;
    if (!is_file($path) || !is_readable($path)) {
        continue;
    }

    $meta = readAppMetadata($path);
    $saved = $legacy[strtolower($filename)] ?? [];
    $nameFromFile = trim(preg_replace('/[-_]+/', ' ', pathinfo($filename, PATHINFO_FILENAME)));
    $apps[] = [
        'filename' => $filename,
        'title' => $meta['catalog:title'] ?? $saved['title'] ?? (($meta['title'] ?? '') ?: $nameFromFile ?: $filename),
        'description' => $meta['catalog:description'] ?? $saved['description'] ?? $meta['description'] ?? $meta['og:description'] ?? '',
        'category' => $meta['catalog:category'] ?? $saved['category'] ?? '',
        'style' => $saved['style'] ?? 'default',
        'icon' => $meta['catalog:icon'] ?? $saved['icon'] ?? '🧩',
        'keywords' => $meta['catalog:keywords'] ?? $saved['keywords'] ?? $meta['keywords'] ?? '',
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Any-Key.[click!] — Каталог микро-сервисов и калькуляторов</title>
    <style>
        :root {
            --bg: #0b0f19;
            --card-bg: #161e2e;
            --card-hover: #1e293b;
            --border: #27354a;
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --cyan: #06b6d4;
            --emerald: #10b981;
            --amber: #f59e0b;
            --rose: #f43f5e;
            --text: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 32px 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container {
            width: 100%;
            max-width: 1100px;
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #fff;
            text-decoration: none;
        }

        .logo span {
            color: var(--cyan);
        }

        .badge {
            font-size: 0.8rem;
            background: rgba(99, 102, 241, 0.15);
            color: #a5b4fc;
            padding: 6px 14px;
            border-radius: 9999px;
            border: 1px solid rgba(99, 102, 241, 0.3);
            font-weight: 600;
        }

        /* Hero banner */
        .hero {
            text-align: center;
            margin-bottom: 40px;
            padding: 20px 0;
        }

        .hero h1 {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #fff 0%, var(--text-muted) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            color: var(--text-muted);
            font-size: 1.05rem;
            max-width: 600px;
            margin: 0 auto 24px;
            line-height: 1.5;
        }

        .search-box {
            max-width: 480px;
            margin: 0 auto;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 14px 18px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.2s;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        /* App Grid */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr));
            gap: 24px;
            margin-bottom: 48px;
        }

        .app-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.25s ease;
            text-decoration: none;
            color: inherit;
            position: relative;
            overflow: hidden;
        }

        .app-card[hidden] {
            display: none;
        }

        .catalog-status {
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 32px;
        }

        .app-card:hover {
            background: var(--card-hover);
            border-color: var(--primary);
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.4);
        }

        .card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .tag {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .tag-default { background: rgba(99, 102, 241, 0.15); color: #a5b4fc; }
        .tag-marketing { background: rgba(6, 182, 212, 0.15); color: var(--cyan); border: 1px solid rgba(6, 182, 212, 0.3); }
        .tag-3d { background: rgba(245, 158, 11, 0.15); color: var(--amber); border: 1px solid rgba(245, 158, 11, 0.3); }
        .tag-finance { background: rgba(16, 185, 129, 0.15); color: var(--emerald); border: 1px solid rgba(16, 185, 129, 0.3); }

        .app-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: #fff;
        }

        .app-desc {
            font-size: 0.88rem;
            color: var(--text-muted);
            line-height: 1.45;
            margin-bottom: 20px;
        }

        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--primary);
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .app-card:hover .card-footer {
            color: #a5b4fc;
        }

        footer {
            margin-top: auto;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
            padding-top: 24px;
            border-top: 1px solid var(--border);
            width: 100%;
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <a href="#" class="logo">Any-Key.<span>[click!]</span></a>
        <span class="badge">Indie App Suite 2026</span>
    </header>

    <div class="hero">
        <h1>Каталог веб-инструментов</h1>
        <p>Автономные одностраничные приложения для маркетинга, 3D-печати, брендинга и юнит-экономики бизнеса.</p>
        
        <div class="search-box">
            <input type="text" id="searchInput" aria-label="Поиск инструмента" placeholder="Поиск инструмента (UTM, 3D, воронка, клеймо)...">
        </div>
    </div>

    <div class="grid" id="appsGrid">
        <?php foreach ($apps as $app): ?>
            <a href="./<?= rawurlencode($app['filename']) ?>" class="app-card"
               data-keywords="<?= escape($app['filename'] . ' ' . $app['keywords']) ?>">
                <div>
                    <div class="card-header">
                        <div class="icon-wrapper" aria-hidden="true"><?= escape($app['icon']) ?></div>
                        <?php if ($app['category'] !== ''): ?>
                            <span class="tag tag-<?= escape($app['style']) ?>"><?= escape($app['category']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="app-title"><?= escape($app['title']) ?></div>
                    <?php if ($app['description'] !== ''): ?>
                        <div class="app-desc"><?= escape($app['description']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <span>Открыть сервис</span>
                    <span aria-hidden="true">→</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <p class="catalog-status" id="catalogStatus" role="status"<?= $apps ? ' hidden' : '' ?>><?php
        if ($entries === false) {
            echo 'Не удалось прочитать папку приложений.';
        } elseif (!$apps) {
            echo 'В каталоге пока нет приложений.';
        } else {
            echo 'По вашему запросу ничего не найдено.';
        }
    ?></p>

    <footer>
        <p>© 2026 Any-Key.[click!] — Инди-разработка приложений без тяжелых фреймворков.</p>
    </footer>
</div>

<script>
    const searchInput = document.getElementById('searchInput');
    const cards = [...document.querySelectorAll('#appsGrid .app-card')];
    const status = document.getElementById('catalogStatus');
    const searchableCards = cards.map(card => ({
        card,
        text: [
            card.dataset.keywords,
            card.querySelector('.app-title').textContent,
            card.querySelector('.app-desc')?.textContent,
            card.querySelector('.tag')?.textContent
        ].join(' ').toLowerCase()
    }));

    function filterCards() {
        const query = searchInput.value.toLowerCase().trim();
        for (const {card, text} of searchableCards) {
            card.hidden = !text.includes(query);
        }
        if (cards.length) {
            status.hidden = cards.some(card => !card.hidden);
        }
    }

    searchInput.addEventListener('input', filterCards);
    filterCards();
</script>

    <!-- Yandex.Metrika counter -->
<script type="text/javascript">
    (function(m,e,t,r,i,k,a){
        m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
    })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=114366607', 'ym');

    ym(114366607, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/114366607" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->

</body>
</html>
