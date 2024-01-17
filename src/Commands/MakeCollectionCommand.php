<?php

namespace eltristi\ExtraCommands\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeCollectionCommand extends GeneratorCommand
{
    protected $name = 'make:collection';
    protected $description = 'Create a new collection';
    protected $type = 'Collection';

    protected function getStub()
    {
        return __DIR__.'/../Stubs/collection.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\\' . config('extra-commands.namespace.collection');
    }
}
