<?php

namespace Marvin\Ask\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Marvin\Ask\AskServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

    }

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn(string $modelName) => 'Marvin\\Ask\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            AskServiceProvider::class,
        ];
    }
}
