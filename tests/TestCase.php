<?php

declare(strict_types=1);

namespace Shopwise\Kit\Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Orchestra\Testbench\TestCase as Orchestra;
use Shopwise\Kit\KitServiceProvider;
use Shopwise\Kit\Messaging\Publisher;
use Shopwise\Kit\Testing\FakePublisher;

abstract class TestCase extends Orchestra
{
    // Not RefreshDatabase: its wrapping transaction would hide Outbox's "must be in a transaction" guard.
    use DatabaseMigrations;

    protected FakePublisher $publisher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publisher = new FakePublisher;
        $this->app->instance(Publisher::class, $this->publisher);
    }

    protected function getPackageProviders($app): array
    {
        return [KitServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('messaging.source', 'kit-test');
        $app['config']->set('messaging.consumer_group', 'kit-test');
    }
}
