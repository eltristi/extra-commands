<?php

namespace eltristi\ExtraCommands\Commands;

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
        return $rootNamespace . '\\' . config('extra-commands.namespace.repository');
    }

    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        $replace = [];

        if ($this->option('interface')) {
            $replace["DummyNamespace\\DummyInterface"] =  trim($this->rootNamespace(), '\\')
                . '\\' . config('extra-commands.namespace.repositoryInterface')
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
        $providerName = 'RepositoryServiceProvider';

        $providerPath = app_path('Providers/' . $providerName . '.php');

        if (!file_exists($providerPath)) {
            \Artisan::call('make:provider', ['name' => $providerName]);

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
    
        $repositoryInterface = $this->argument('name') . "Interface";
        $repositoryClass = $this->argument('name');
    
        $newBinding = "        \$this->app->bind({$repositoryInterface}::class, {$repositoryClass}::class);\n";
    
        $repositoryInterfaceFullNamespace = trim($this->rootNamespace(), '\\') . '\\' . config('extra-commands.namespace.repositoryInterface') . '\\' . $repositoryInterface;
        $repositoryClassFullNamespace = $this->getDefaultNamespace(trim($this->rootNamespace(), '\\')) . '\\' . $repositoryClass;
    
        if (!str_contains($providerContents, "use {$repositoryInterfaceFullNamespace};")) {
            $providerContents = str_replace("namespace App\\Providers;", "namespace App\\Providers;\nuse {$repositoryInterfaceFullNamespace};", $providerContents);
        }
    
        if (!str_contains($providerContents, "use {$repositoryClassFullNamespace};")) {
            $providerContents = str_replace("namespace App\\Providers;\nuse {$repositoryInterfaceFullNamespace};", "namespace App\\Providers;\nuse {$repositoryInterfaceFullNamespace};\nuse {$repositoryClassFullNamespace};", $providerContents);
        }
    
        if (!str_contains($providerContents, $newBinding)) {
            if (!str_contains($providerContents, "public function register(): void")) {
                $providerContents = str_replace("public function register()", "public function register(): void", $providerContents);
            }
            $providerContents = preg_replace("/public function register\(\): void[^\{]*\{(\s*\n)?/", "public function register(): void\n    {\n{$newBinding}", $providerContents);
        }
    
        file_put_contents($providerPath, $providerContents);
    }

    protected function createInterface()
    {
        $interfaceName = $this->argument('name') . 'Interface';
        $interfaceNamespace = trim($this->rootNamespace(), '\\') . '\\' . config('extra-commands.namespace.repositoryInterface');
        $interfaceClass = $interfaceNamespace . '\\' . $interfaceName;

        if (class_exists($interfaceClass)) {
            $this->error('Interface already exists!');
            return false;
        }

        $interfaceStubPath = $this->getInterfaceStub();

        $interfaceStubContent = file_get_contents($interfaceStubPath);

        $interfaceContent = str_replace(['DummyNamespace', 'DummyInterface'], [$interfaceNamespace, $interfaceName], $interfaceStubContent);

        $interfacePath = app_path(str_replace('\\', '/', config('extra-commands.namespace.repositoryInterface')));

        if (!is_dir($interfacePath)) {
            mkdir($interfacePath, 0777, true);
        }

        file_put_contents($interfacePath . '/' . $interfaceName . '.php', $interfaceContent);

        return $interfaceClass;
    }
}
