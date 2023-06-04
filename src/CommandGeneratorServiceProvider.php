<?php

namespace eltristi\CommandGenerator;

use Illuminate\Support\ServiceProvider;
use eltristi\CommandGenerator\Commands\MakeRepositoryCommand;
use eltristi\CommandGenerator\Commands\MakeServiceCommand;
use eltristi\CommandGenerator\Commands\MakeTraitCommand;
use eltristi\CommandGenerator\Commands\MakeEnumCommand;
use eltristi\CommandGenerator\Commands\MakeInterfaceCommand;

class CommandGeneratorServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeRepositoryCommand::class,
                MakeServiceCommand::class,
                MakeInterfaceCommand::class,
                MakeTraitCommand::class,
                MakeEnumCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../config/generator.php' => config_path('generator.php'),
        ], 'config');
    }
}
