<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Model/Category.php';
require_once __DIR__ . '/../src/Model/Product.php';
require_once __DIR__ . '/../src/Service/CatalogService.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$fatalError = null;
$filterError = null;
$service = null;
$products = [];
$selectedCategory = null;
$onlyActive = false;

try {
    $service = new CatalogService(
        __DIR__ . '/../data/products.json',
        __DIR__ . '/../data/categories.json'
    );

    // Обработка формы фильтра (GET) — отдельно от моделей
    $rawCategory = is_string($_GET['category'] ?? null) ? trim($_GET['category']) : '';
    if ($rawCategory !== '') {
        $id = filter_var($rawCategory, FILTER_VALIDATE_INT);
        if ($id === false || $service->findCategory($id) === null) {
            $filterError = 'Выбрана несуществующая категория. Показан весь каталог.';
        } else {
            $selectedCategory = $id;
        }
    }
    $onlyActive = ($_GET['only_active'] ?? '') === '1';

    $products = $service->filterByCategory($selectedCategory, $onlyActive);
} catch (RuntimeException $e) {
    $fatalError = $e->getMessage();
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Каталог товаров — Практическая работа 5</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="site-header">
    <div class="brand">Практическая работа 5</div>
    <span>ТРПО</span>
</header>

<main class="wrap">
    <section class="hero">
        <span class="pill">PHP ООП</span>
        <h1>Каталог товаров</h1>
        <p class="muted">Классы Product, Category и CatalogService. Данные читаются из data/products.json и data/categories.json.</p>
        <p><a href="check.php">Проверка конструкторов и dataset</a></p>
    </section>

    <?php if ($fatalError !== null): ?>
        <div class="alert alert-error">Ошибка загрузки данных: <?= e($fatalError) ?></div>
    <?php else: ?>

        <?php foreach ($service->getErrors() as $message): ?>
            <div class="alert alert-warn">Пропущена запись. <?= e($message) ?></div>
        <?php endforeach; ?>

        <?php if ($filterError !== null): ?>
            <div class="alert alert-warn"><?= e($filterError) ?></div>
        <?php endif; ?>

        <section>
            <form method="get" class="filter">
                <label for="category">Категория</label>
                <select name="category" id="category">
                    <option value="">Все категории</option>
                    <?php foreach ($service->getCategories() as $category): ?>
                        <option value="<?= $category->getId() ?>"
                            <?= $selectedCategory === $category->getId() ? 'selected' : '' ?>>
                            <?= e($category->getName()) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label class="check">
                    <input type="checkbox" name="only_active" value="1" <?= $onlyActive ? 'checked' : '' ?>>
                    Только доступные
                </label>

                <button type="submit" class="btn">Применить</button>
                <a href="index.php" class="reset">Сбросить</a>
            </form>
        </section>

        <section class="stats">
            <div class="card"><b>Найдено товаров</b><span><?= count($products) ?></span></div>
            <div class="card"><b>Из них доступно</b><span><?= $service->countActive($products) ?></span></div>
            <div class="card"><b>Средняя цена</b><span><?= e(Product::formatMoney($service->averagePrice($products))) ?></span></div>
        </section>

        <section>
            <h2>Товары</h2>
            <?php if (count($products) === 0): ?>
                <div class="alert alert-info">По выбранным условиям товары не найдены.</div>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($products as $product): ?>
                        <article class="card product <?= $product->isActive() ? '' : 'inactive' ?>">
                            <span class="pill"><?= e($service->categoryNameFor($product)) ?></span>
                            <h3><?= e($product->getName()) ?></h3>
                            <p class="price"><?= e($product->formattedPrice()) ?></p>
                            <p class="muted">Со скидкой 10%: <?= e(Product::formatMoney($product->priceWithDiscount(10))) ?></p>
                            <p class="status"><?= e($product->statusLabel()) ?> · ID <?= $product->getId() ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>