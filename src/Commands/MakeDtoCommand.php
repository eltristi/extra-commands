<?php

namespace eltristi\ExtraCommands\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeDtoCommand extends GeneratorCommand
{
    protected $name = 'make:dto';
    protected $description = 'Create a new DTO';
    protected $type = 'Dto';

    protected function getStub()
    {
        return __DIR__.'/../Stubs/dto.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\\' . config('extra-commands.namespace.dto');
    }
}
