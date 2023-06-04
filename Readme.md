# Laravel Extra Commands

This package provides additional artisan make commands for Laravel to speed up your development process.

## Installation

You can install the package via composer:

```bash
composer require eltristi/extra-commands
```

## Available Commands

### make:repository
Creates a new repository class and optionally an interface for it.

Usage

```bash
php artisan make:repository UserRepository --model=User --interface
```

Options
- --model (-m): The model that the repository should be bound to.
- --interface (-i): Indicates if an interface should be generated for the repository.

### make:service
Creates a new service class and optionally an interface for it.

Usage

```bash
php artisan make:service UserService --interface
```

Options
- --interface (-i): Indicates if an interface should be generated for the repository.

### make:enum
Creates a new enum class.
Usage

```bash
php artisan make:enum UserTypeEnum --type=string
```
Options
- --type (-t): Indicates the return type of the enum.

### make:trait
Creates a new trait.
Usage

```bash
php artisan make:trait MyTrait
```

### make:interface
Creates a new interface and allows you to specify its type.
Usage

```bash
php artisan make:interface UserRepositoryInterface --type=repository
```
Options
- --type (-t): The type of the interface (repository, service). If no type is specified, the interface will be placed under App\Contracts.

## Configuration
You can configure the namespaces for the generated classes in the generator config file.

## License
This project is licensed under the MIT License - see the LICENSE.md file for details.