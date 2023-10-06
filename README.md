# Laravel-codegen

This package generates code for you. It is capable of creating Migrations, Models, Controllers, Factories, Routes, Views, Database Seeders and PestPhp Tests.

#### All these come with pre-populated code

For each Route, a PestPhp Test will be created to make sure it can be accessed.

### Important Note

This package is still in beta. Therefore, I recommend testing your application thoroughly before using it in production.

## Requirements
- PHP >= 8.1
- Laravel >= 10

## Installing

You can install the package via composer:

```shell
composer require tgozo/laravel-codegen
```

## Usage

To create a migration. Execute the following command from the root of your Laravel project
```
php artisan codegen:migration
```

### Notes

1. At least 1 field should be specified.
1. You can add the options `-m`, `-c`, `-s`, `-f` to the command so that a Model, a Controller, a Seeder and a Factory can be created respectively.
1. To create a Migration, Model, Controller, Seeder and a Factory. Execute the command ```php artisan codegen:migration -mcsf```
1. The option `--all` can be used so that when the Migration is created, a Model, a Controller, a Database Seeder, a Factory and Routes & Views to be used withing the controller are created
1. When a Controller is created, necessary Routes are added to the `routes/web.php` file and the necessary views are added to the `resources/views` directory.
1. For each Route, a PestPhp Test will be created to make sure it can be accessed.
1. The migration names should follow certain patterns.

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
