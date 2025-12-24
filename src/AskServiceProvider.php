<?php

namespace Marvin\Ask;

use Marvin\Ask\Commands\AskQuestion;
use Marvin\Ask\Commands\DeleteOldCommandRuns;
use Marvin\Ask\Commands\Documents\DeleteOrphans;
use Marvin\Ask\Commands\GetEmbedVector;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AskServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('ask')
            ->hasConfigFile()
            ->hasViews()
            ->discoversMigrations()
            ->runsMigrations()
            ->hasCommands([
                DeleteOrphans::class,
                AskQuestion::class,
                DeleteOldCommandRuns::class,
                GetEmbedVector::class,
            ]);
    }
}
