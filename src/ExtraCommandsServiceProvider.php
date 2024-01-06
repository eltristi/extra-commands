<?php

namespace eltristi\ExtraCommands;

use Illuminate\Support\ServiceProvider;
use eltristi\ExtraCommands\Commands\MakeRepositoryCommand;
use eltristi\ExtraCommands\Commands\MakeServiceCommand;
use eltristi\ExtraCommands\Commands\MakeTraitCommand;
use eltristi\ExtraCommands\Commands\MakeEnumCommand;
use eltristi\ExtraCommands\Commands\MakeInterfaceCommand;

class ExtraCommandsServiceProvider extends ServiceProvider
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
            __DIR__.'/../config/extra-commands.php' => config_path('extra-commands.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/extra-commands.php', 'extra-commands');
    }
}
