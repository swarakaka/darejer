<?php

namespace Darejer\Tests;

use Darejer\DarejerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            DarejerServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('darejer.languages', ['en']);
        $app['config']->set('darejer.default_language', 'en');

        // Run queued jobs (including BroadcastEvent dispatched by
        // ShouldBroadcast events) inline, and route broadcasts to the null
        // driver so tests don't need a queue table or websocket server.
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('broadcasting.default', 'null');
    }
}
