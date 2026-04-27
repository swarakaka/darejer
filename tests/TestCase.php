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
    }
}
