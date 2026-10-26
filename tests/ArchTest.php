<?php

declare(strict_types=1);

arch()->preset()->php();

arch('strict types everywhere')
    ->expect('Shopwise\Kit')
    ->toUseStrictTypes();

arch('messaging stays free of HTTP concerns')
    ->expect('Shopwise\Kit\Messaging')
    ->not->toUse(['Illuminate\Http', 'Shopwise\Kit\Auth']);
