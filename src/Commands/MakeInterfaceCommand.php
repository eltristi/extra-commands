<?php

namespace eltristi\ExtraCommands\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class MakeInterfaceCommand extends GeneratorCommand
{
    protected $name = 'make:interface';
    protected $description = 'Create a new interface';
    protected $type = 'Interface';

    protected function getStub()
    {
        return __DIR__.'/../Stubs/interface.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        $interfaceType = strtolower($this->option('type'));

        switch ($interfaceType) {
            case 'repository':
                return $rootNamespace . '\\' . config('generator.namespace.repositoryInterface');
            case 'service':
                return $rootNamespace . '\\' . config('generator.namespace.serviceInterface');
            default:
                return $rootNamespace . '\\Contracts';
        }
    }

    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        $replace = [
            'DummyNamespace' => $this->getDefaultNamespace(trim($this->rootNamespace(), '\\')),
            'DummyInterface' => $this->argument('name'),
        ];
    
        return str_replace(
            array_keys($replace), array_values($replace), $stub
        );
    }

    protected function getOptions()
    {
        return [
            ['type', 't', InputOption::VALUE_OPTIONAL, 'The type of the interface (repository, service)'],
        ];
    }
}
