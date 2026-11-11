<?php
declare(strict_types=1);

final class CatalogService
{
    /** @var array<int, Category> */
    private array $categories = [];
    /** @var Product[] */
    private array $products = [];
    /** @var string[] */
    private array $errors = [];

    public function __construct(
        private string $productsFile,
        private string $categoriesFile
    ) {
        $this->loadCategories();
        $this->loadProducts();
    }

    /** @return Product[] */
    public function getProducts(): array
    {
        return $this->products;
    }

    /** @return Category[] */
    public function getCategories(): array
    {
        return array_values($this->categories);
    }

    /** Сообщения о строках dataset, которые не удалось превратить в объекты. */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function findCategory(int $id): ?Category
    {
        return $this->categories[$id] ?? null;
    }

    public function categoryNameFor(Product $product): string
    {
        $category = $this->findCategory($product->getCategoryId());
        return $category !== null ? $category->getName() : 'Без категории';
    }

    /** @return Product[] */
    public function filterByCategory(?int $categoryId, bool $onlyActive = false): array
    {
        $result = array_filter(
            $this->products,
            fn(Product $p) => ($categoryId === null || $p->belongsToCategory($categoryId))
                && (!$onlyActive || $p->isActive())
        );
        return array_values($result);
    }

    /** Средняя цена переданных товаров (0 для пустого списка). */
    public function averagePrice(array $products): float
    {
        if (count($products) === 0) {
            return 0.0;
        }
        $sum = array_reduce($products, fn(float $s, Product $p) => $s + $p->getPrice(), 0.0);
        return $sum / count($products);
    }

    public function countActive(array $products): int
    {
        return count(array_filter($products, fn(Product $p) => $p->isActive()));
    }

    // ---- чтение dataset ----

    private function loadCategories(): void
    {
        foreach ($this->readJson($this->categoriesFile) as $i => $row) {
            try {
                if (!is_array($row) || !isset($row['id'], $row['name'])) {
                    throw new InvalidArgumentException('нет обязательных полей id и name');
                }
                $category = new Category((int) $row['id'], (string) $row['name']);
                $this->categories[$category->getId()] = $category;
            } catch (InvalidArgumentException $e) {
                $this->errors[] = 'categories.json, запись №' . ($i + 1) . ': ' . $e->getMessage();
            }
        }
    }

    private function loadProducts(): void
    {
        foreach ($this->readJson($this->productsFile) as $i => $row) {
            try {
                if (!is_array($row) || !isset($row['id'], $row['name'], $row['price'], $row['category_id'])) {
                    throw new InvalidArgumentException('нет обязательных полей id, name, price, category_id');
                }
                if (!is_numeric($row['price'])) {
                    throw new InvalidArgumentException('цена не является числом');
                }
                $this->products[] = new Product(
                    (int) $row['id'],
                    (string) $row['name'],
                    (float) $row['price'],
                    (int) $row['category_id'],
                    (bool) ($row['active'] ?? true) // необязательное поле
                );
            } catch (InvalidArgumentException $e) {
                $this->errors[] = 'products.json, запись №' . ($i + 1) . ': ' . $e->getMessage();
            }
        }
    }

    private function readJson(string $file): array
    {
        if (!is_file($file)) {
            throw new RuntimeException('Файл не найден: ' . basename($file));
        }
        $content = file_get_contents($file);
        if ($content === false) {
            throw new RuntimeException('Не удалось прочитать файл: ' . basename($file));
        }
        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('Некорректный JSON в файле ' . basename($file) . ': ' . $e->getMessage());
        }
        if (!is_array($data)) {
            throw new RuntimeException('Файл ' . basename($file) . ' должен содержать список записей');
        }
        return array_values($data);
    }
}