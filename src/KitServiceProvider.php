<?php

declare(strict_types=1);

namespace Shopwise\Kit;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Shopwise\Kit\Auth\VerifyJwt;
use Shopwise\Kit\Console\ConsumeCommand;
use Shopwise\Kit\Console\RelayOutboxCommand;
use Shopwise\Kit\Messaging\KafkaPublisher;
use Shopwise\Kit\Messaging\Publisher;

final class KitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/messaging.php', 'messaging');
        $this->mergeConfigFrom(__DIR__.'/../config/shopwise-auth.php', 'shopwise-auth');
        $this->mergeConfigFrom(__DIR__.'/../config/kafka.php', 'kafka');

        $this->app->bindIf(Publisher::class, KafkaPublisher::class);
    }

    public function boot(Router $router): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $router->aliasMiddleware('auth.jwt', VerifyJwt::class);

        if ($this->app->runningInConsole()) {
            $this->commands([RelayOutboxCommand::class, ConsumeCommand::class]);

            $this->publishes([__DIR__.'/../config/messaging.php' => config_path('messaging.php')], 'shopwise-config');

            $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
                $schedule->command('shopwise:relay-outbox')->everySecond()->withoutOverlapping();
            });
        }
    }
}
