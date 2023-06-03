<?php

namespace eltristi\CommandGenerator\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class MakeRepositoryCommand extends GeneratorCommand
{
    protected $name = 'make:repository';
    protected $description = 'Create a new repository';
    protected $type = 'Repository';

    protected function getStub()
    {
        if ($this->option('interface') && $this->option('model')) {
            return __DIR__ . '/../Stubs/repository-model.stub';
        }

        if ($this->option('model') && !$this->option('interface')) {
            return __DIR__ . '/../Stubs/repository-model-no-interface.stub';
        }

        if ($this->option('interface') && !$this->option('model')) {
            return __DIR__ . '/../Stubs/repository.stub';
        }

        return __DIR__ . '/../Stubs/repository-no-interface.stub';
    }

    protected function getInterfaceStub()
    {
        return __DIR__ . '/../Stubs/interface.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\\' . config('generator.namespace.repository');
    }

    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        $replace = [];

        if ($this->option('interface')) {
            $replace["DummyNamespace\\DummyInterface"] =  trim($this->rootNamespace(), '\\')
                . '\\' . config('generator.namespace.repositoryInterface')
                . '\\' . $this->argument('name') . "Interface";
        }

        if ($this->option('model')) {
            $replace["DummyModel"] =  $this->option('model');
        }

        $replace = [
            ...$replace,
            'DummyNamespace' => $this->getDefaultNamespace(trim($this->rootNamespace(), '\\')),
            'DummyClass' => $this->argument('name'),
            'DummyInterface' => $this->argument('name') . 'Interface',
        ];

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $stub
        );
    }

    protected function getOptions()
    {
        return [
            ['interface', 'i', InputOption::VALUE_NONE, 'Indicates if an interface should be generated'],
            ['model', 'm', InputOption::VALUE_REQUIRED, 'Indicates if the repository should be bound to a model']
        ];
    }

    public function handle()
    {
        parent::handle();

        if ($this->option('interface')) {
            $this->createInterface();
            $this->createProviderIfNeeded();
            $this->addBindingToProvider();
        }
    }

    protected function createProviderIfNeeded()
    {
        // Define the name of the provider
        $providerName = 'RepositoryServiceProvider';

        // Check if provider exists, if not create it
        $providerPath = app_path('Providers/' . $providerName . '.php');

        if (!file_exists($providerPath)) {
            // Create the provider using Artisan
            \Artisan::call('make:provider', ['name' => $providerName]);

            // Add provider to config/app.php
            $configApp = file_get_contents(config_path('app.php'));
            $newProvider = "App\\Providers\\" . $providerName . "::class,";
            $configApp = str_replace("'providers' => [", "'providers' => [\n        " . $newProvider, $configApp);
            file_put_contents(config_path('app.php'), $configApp);
        }
    }

    protected function addBindingToProvider()
    {
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');
        $providerContents = file_get_contents($providerPath);

        $repositoryInterfaceNamespace = trim($this->rootNamespace(), '\\') . '\\' . config('generator.namespace.repositoryInterface') . '\\' . $this->argument('name') . "Interface";
        $repositoryClassNamespace = $this->getDefaultNamespace(trim($this->rootNamespace(), '\\')) . '\\' . $this->argument('name');

        $newBinding = "\n        \$this->app->bind(\\{$repositoryInterfaceNamespace}::class, \\{$repositoryClassNamespace}::class);";

        $providerContents = str_replace(
            'public function register()\n    {',
            'public function register(): void\n    {' . $newBinding,
            $providerContents
        );
        $providerContents = str_replace(
            'public function register(): void\n    {',
            'public function register(): void\n    {' . $newBinding,
            $providerContents
        );
        file_put_contents($providerPath, $providerContents);
    }


    protected function createInterface()
    {
        // Define the interface name and path
        $interfaceName = $this->argument('name') . 'Interface';
        $interfaceNamespace = trim($this->rootNamespace(), '\\') . '\\' . config('generator.namespace.repositoryInterface');
        $interfaceClass = $interfaceNamespace . '\\' . $interfaceName;

        // Check if interface already exists
        if (class_exists($interfaceClass)) {
            $this->error('Interface already exists!');
            return false;
        }

        // Define the path to the interface stub
        $interfaceStubPath = $this->getInterfaceStub();

        // Get the stub content
        $interfaceStubContent = file_get_contents($interfaceStubPath);

        // Replace the DummyNamespace and DummyInterface placeholders
        $interfaceContent = str_replace(['DummyNamespace', 'DummyInterface'], [$interfaceNamespace, $interfaceName], $interfaceStubContent);

        // Define the path to the interface file
        $interfacePath = app_path(str_replace('\\', '/', config('generator.namespace.repositoryInterface')));

        // Check if the directory exists, if not create it
        if (!is_dir($interfacePath)) {
            mkdir($interfacePath, 0777, true);
        }

        // Write the interface content to the file
        file_put_contents($interfacePath . '/' . $interfaceName . '.php', $interfaceContent);

        // Return the fully qualified interface name
        return $interfaceClass;
    }
}
