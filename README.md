# Laravel-codegen

This package generates code for you. It takes to another level how migrations, controllers and models are created in Laravel

### Note

As of now the package only accept the creation of a migration. More features will be rolled out soon.

## Requirements
- PHP >=8.1
- Laravel >= 10

## Installing

You can install the package via composer:

```shell
composer require tgozo/laravel-codegen
```

## Usage

To create a migration. Execute the following command from the root of your Laravel project
```
php artisan codegen:migration --with-fields
```
The migration names should follow certain patterns.

```
create_posts_table
```
As we can see from the above migration name, it starts with `create_` and ends with `_table`. More patterns will be shared as features are rolled out.

## Contributing

Please see [CONTRIBUTING](contributing.md) for details.

## Security Vulnerabilities

Please review [our security policy](security.md) on how to report security vulnerabilities.

## License

The MIT License (MIT). Please see [License File](license.md) for more information.
