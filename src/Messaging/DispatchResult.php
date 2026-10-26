<?php

declare(strict_types=1);

namespace Shopwise\Kit\Messaging;

enum DispatchResult
{
    case Handled;
    case Duplicate;
    case Ignored;
    case DeadLettered;
}
