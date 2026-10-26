<?php

declare(strict_types=1);

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Shopwise\Kit\Money\MoneyCast;

#[Unguarded]
final class PricedThing extends Model
{
    protected function casts(): array
    {
        return ['price' => MoneyCast::class.':price'];
    }
}

it('maps Money to amount and currency columns and back', function (): void {
    $thing = new PricedThing(['price' => Money::ofMinor(1999, 'EUR')]);

    expect($thing->getAttributes())->toBe(['price_amount' => 1999, 'price_currency' => 'EUR'])
        ->and($thing->price->isEqualTo(Money::ofMinor(1999, 'EUR')))->toBeTrue();
});

it('rejects plain numbers', function (): void {
    new PricedThing(['price' => 19.99]);
})->throws(InvalidArgumentException::class);
