<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Model/Category.php';
require_once __DIR__ . '/../src/Model/Product.php';
require_once __DIR__ . '/../src/Service/CatalogService.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// 1. Проверка некорректных конструкторов
$cases = [
    'Product: id = 0'              => fn() => new Product(0, 'Мышь', 100.0, 1),
    'Product: пустое имя'          => fn() => new Product(1, '   ', 100.0, 1),
    'Product: отрицательная цена'  => fn() => new Product(1, 'Мышь', -5.0, 1),
    'Product: categoryId = 0'      => fn() => new Product(1, 'Мышь', 100.0, 0),
    'Category: пустое название'    => fn() => new Category(1, ''),
    'Product: корректные данные'   => fn() => new Product(10, 'Клавиатура', 3500.0, 1),
];

$results = [];
foreach ($cases as $title => $factory) {
    try {
        $factory();
        $results[] = [$title, true, 'Объект создан'];
    } catch (InvalidArgumentException $ex) {
        $results[] = [$title, false, $ex->getMessage()];
    }
}

// 2. Сверка классов с class_requirements.csv
$csvRows = [];
$csvError = null;
$handle = fopen(__DIR__ . '/../data/class_requirements.csv', 'r');
if ($handle === false) {
    $csvError = 'Не удалось открыть class_requirements.csv';
} else {
    fgetcsv($handle, 0, ';', '"', ''); // строка заголовков
    while (($row = fgetcsv($handle, 0, ';', '"', '')) !== false) {
        if (count($row) < 4) {
            continue;
        }
        [$class, $property, $type, $required] = $row;
        $ok = false;
        if (class_exists($class)) {
            $ref = new ReflectionClass($class);
            if ($ref->hasProperty($property)) {
                $propType = $ref->getProperty($property)->getType();
                $ok = $propType !== null && $propType->getName() === $type;
            }
        }
        $csvRows[] = [$class, $property, $type, $required, $ok];
    }
    fclose($handle);
}

// 3. Проверка всех строк products.json
$datasetInfo = '';
try {
    $service = new CatalogService(__DIR__ . '/../data/products.json', __DIR__ . '/../data/categories.json');
    $datasetInfo = 'Товаров загружено: ' . count($service->getProducts())
        . ', категорий: ' . count($service->getCategories())
        . ', пропущенных записей: ' . count($service->getErrors());
} catch (RuntimeException $ex) {
    $datasetInfo = 'Ошибка: ' . $ex->getMessage();
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Проверка — Практическая работа 5</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="site-header"><div class="brand">Проверка</div><a href="index.php" style="color:#fff">← К каталогу</a></header>
<main class="wrap">
    <h2>1. Проверка конструкторов</h2>
    <table class="checks">
        <tr><th>Сценарий</th><th>Результат</th><th>Сообщение</th></tr>
        <?php foreach ($results as [$title, $created, $message]): ?>
            <tr>
                <td><?= e($title) ?></td>
                <td class="<?= $created ? 'ok' : 'fail' ?>"><?= $created ? 'создан' : 'InvalidArgumentException' ?></td>
                <td><?= e($message) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>2. Соответствие классов class_requirements.csv</h2>
    <?php if ($csvError !== null): ?>
        <div class="alert alert-error"><?= e($csvError) ?></div>
    <?php else: ?>
        <table class="checks">
            <tr><th>Класс</th><th>Свойство</th><th>Тип</th><th>Обязательное</th><th>Есть в классе</th></tr>
            <?php foreach ($csvRows as [$class, $property, $type, $required, $ok]): ?>
                <tr>
                    <td><?= e($class) ?></td><td><?= e($property) ?></td><td><?= e($type) ?></td><td><?= e($required) ?></td>
                    <td class="<?= $ok ? 'ok' : 'fail' ?>"><?= $ok ? 'да' : 'НЕТ' ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <h2>3. Загрузка dataset</h2>
    <p><?= e($datasetInfo) ?></p>
</main>
</body>
</html>