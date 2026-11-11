<?php
declare(strict_types=1);

final class Product
{
    public function __construct(
        private int $id,
        private string $name,
        private float $price,
        private int $categoryId,
        private bool $active = true
    ) {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID товара должен быть положительным числом');
        }
        $this->name = trim($name);
        if ($this->name === '') {
            throw new InvalidArgumentException('Название товара не может быть пустым');
        }
        if ($price < 0) {
            throw new InvalidArgumentException('Цена товара не может быть отрицательной');
        }
        if ($categoryId <= 0) {
            throw new InvalidArgumentException('ID категории товара должен быть положительным числом');
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    // методы предметной области

    /** Принадлежит ли товар категории с указанным id */
    public function belongsToCategory(int $categoryId): bool
    {
        return $this->categoryId === $categoryId;
    }

    /** Цена с учётом скидки в процентах (0..100) */
    public function priceWithDiscount(float $percent): float
    {
        if ($percent < 0 || $percent > 100) {
            throw new InvalidArgumentException('Скидка должна быть в диапазоне от 0 до 100');
        }
        return round($this->price * (1 - $percent / 100), 2);
    }

    /** Цена в виде строки: 1 124,00 ₽ */
    public function formattedPrice(): string
    {
        return self::formatMoney($this->price);
    }

    /** Текстовый статус для интерфейса */
    public function statusLabel(): string
    {
        return $this->active ? 'В наличии' : 'Недоступен';
    }

    public static function formatMoney(float $value): string
    {
        return number_format($value, 2, ',', ' ') . ' ₽';
    }
}