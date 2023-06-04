<?php

namespace eltristi\ExtraCommands\Commands;

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
        $providerName = 'ServicesServiceProvider';
        
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
        $providerPath = app_path('Providers/ServicesServiceProvider.php');
        $providerContents = file_get_contents($providerPath);
    
        $serviceInterface = $this->argument('name') . "Interface";
        $serviceClass = $this->argument('name');
    
        $newBinding = "        \$this->app->bind({$serviceInterface}::class, {$serviceClass}::class);\n";
    
        $serviceInterfaceFullNamespace = trim($this->rootNamespace(), '\\') . '\\' . config('generator.namespace.serviceInterface') . '\\' . $serviceInterface;
        $serviceClassFullNamespace = $this->getDefaultNamespace(trim($this->rootNamespace(), '\\')) . '\\' . $serviceClass;
    
        if (!str_contains($providerContents, "use {$serviceInterfaceFullNamespace};")) {
            $providerContents = str_replace("namespace App\\Providers;", "namespace App\\Providers;\nuse {$serviceInterfaceFullNamespace};", $providerContents);
        }
    
        if (!str_contains($providerContents, "use {$serviceClassFullNamespace};")) {
            $providerContents = str_replace("namespace App\\Providers;\nuse {$serviceInterfaceFullNamespace};", "namespace App\\Providers;\nuse {$serviceInterfaceFullNamespace};\nuse {$serviceClassFullNamespace};", $providerContents);
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
        $interfaceNamespace = trim($this->rootNamespace(), '\\') . '\\' . config('generator.namespace.serviceInterface');
        $interfaceClass = $interfaceNamespace . '\\' . $interfaceName;

        if (class_exists($interfaceClass)) {
            $this->error('Interface already exists!');
            return false;
        }

        $interfaceStubPath = $this->getInterfaceStub();

        $interfaceStubContent = file_get_contents($interfaceStubPath);

        $interfaceContent = str_replace(['DummyNamespace', 'DummyInterface'], [$interfaceNamespace, $interfaceName], $interfaceStubContent);

        $interfacePath = app_path(str_replace('\\', '/', config('generator.namespace.serviceInterface')));

        if (!is_dir($interfacePath)) {
            mkdir($interfacePath, 0777, true);
        }

        file_put_contents($interfacePath . '/' . $interfaceName . '.php', $interfaceContent);

        return $interfaceClass;
    }
}
