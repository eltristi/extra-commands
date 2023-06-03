<?php

namespace eltristi\CommandGenerator\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class MakeEnumCommand extends GeneratorCommand
{
    protected $name = 'make:enum';
    protected $description = 'Create a new enum';
    protected $type = 'Enum';

    protected function getStub()
    {
        return $this->option('type')
                ? __DIR__.'/../Stubs/enum-typed.stub'
                : __DIR__.'/../Stubs/enum.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\\' . config('generator.namespace.enum');
    }

    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());
        $replace = [
            'DummyNamespace' => $this->getDefaultNamespace(trim($this->rootNamespace(), '\\')),
            'DummyClass' => $this->argument('name'),
        ];

        if ($this->option('type')) {
            $replace["DummyType"] = $this->option('type');
        }


        return str_replace(
            array_keys($replace), array_values($replace), $stub
        );
    }

    protected function getOptions()
    {
        return [
            ['type', 't', InputOption::VALUE_OPTIONAL, 'The type of the enum values']
        ];
    }
}
