<?php

namespace eltristi\CommandGenerator\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class MakeServiceCommand extends GeneratorCommand
{
    protected $name = 'make:service';
    protected $description = 'Create a new service class';
    protected $type = 'Service';

    protected function getStub()
    {
        return $this->option('interface')
                ? __DIR__.'/../Stubs/service.stub'
                : __DIR__.'/../Stubs/service-no-interface.stub';
    }

    protected function getInterfaceStub()
    {
        return __DIR__ . '/../Stubs/interface.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\\' . config('generator.namespace.service');
    }

    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());
    
        $replace = [];
    
        if ($this->option('interface')) {
            $replace["DummyNamespace\\DummyInterface"] =  trim($this->rootNamespace(), '\\') 
            . '\\' . config('generator.namespace.serviceInterface')
            . '\\' . $this->argument('name') . "Interface";
        }

        $replace = [
            ...$replace,
            'DummyNamespace' => $this->getDefaultNamespace(trim($this->rootNamespace(), '\\')),
            'DummyClass' => $this->argument('name'),
            'DummyInterface' => $this->argument('name') . 'Interface',
        ];
    
        return str_replace(
            array_keys($replace), array_values($replace), $stub
        );
    }

    protected function getOptions()
    {
        return [
            ['interface', 'i', InputOption::VALUE_NONE, 'Indicates if an interface should be generated']
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
        $providerName = 'ServicesServiceProvider';
        
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
        $providerPath = app_path('Providers/ServicesServiceProvider.php');
        $providerContents = file_get_contents($providerPath);

        $serviceInterface = $this->getDefaultNamespace(trim($this->rootNamespace(), '\\'))
            . '\\' . config('generator.namespace.serviceInterface')
            . '\\' . $this->argument('name') . "Interface";

        $serviceClass = $this->qualifyClass($this->getNameInput());

        $newBinding = "\n        \$this->app->singleton({$serviceInterface}::class, {$serviceClass}::class);";

        $providerContents = str_replace('public function register()\n    {', 'public function register()\n    {' . $newBinding, $providerContents);
        file_put_contents($providerPath, $providerContents);
    }

    protected function createInterface()
    {
        // Define the interface name and path
        $interfaceName = $this->argument('name') . 'Interface';
        $interfaceNamespace = trim($this->rootNamespace(), '\\') . '\\' . config('generator.namespace.serviceInterface');
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
        $interfacePath = app_path(str_replace('\\', '/', config('generator.namespace.serviceInterface')));

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
