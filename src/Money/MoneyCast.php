<?php

declare(strict_types=1);

namespace Shopwise\Kit\Money;

use Brick\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * `'price' => MoneyCast::class.':price'` maps `price_amount` (int minor units) + `price_currency` (ISO 4217).
 *
 * @implements CastsAttributes<Money|null, mixed>
 */
final readonly class MoneyCast implements CastsAttributes
{
    public function __construct(private ?string $prefix = null) {}

    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        $prefix = $this->prefix ?? $key;
        $amount = $attributes["{$prefix}_amount"] ?? null;
        $currency = $attributes["{$prefix}_currency"] ?? null;

        if (! is_numeric($amount) || ! is_string($currency)) {
            return null;
        }

        return Money::ofMinor((int) $amount, $currency);
    }

    /**
     * @return array<string, int|string|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        $prefix = $this->prefix ?? $key;

        if ($value === null) {
            return ["{$prefix}_amount" => null, "{$prefix}_currency" => null];
        }

        if (! $value instanceof Money) {
            throw new InvalidArgumentException("{$key} must be a Brick\\Money\\Money instance.");
        }

        return [
            "{$prefix}_amount" => $value->getMinorAmount()->toInt(),
            "{$prefix}_currency" => $value->getCurrency()->getCurrencyCode(),
        ];
    }
}
