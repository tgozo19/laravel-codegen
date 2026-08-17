# 🚀 Laravel CodeGen - The Ultimate Laravel Development Accelerator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tgozo/laravel-codegen.svg?style=flat-square)](https://packagist.org/packages/tgozo/laravel-codegen)
[![Total Downloads](https://img.shields.io/packagist/dt/tgozo/laravel-codegen.svg?style=flat-square)](https://packagist.org/packages/tgozo/laravel-codegen)
[![License](https://img.shields.io/packagist/l/tgozo/laravel-codegen.svg?style=flat-square)](https://packagist.org/packages/tgozo/laravel-codegen)

**Stop writing boilerplate code. Start building features.**

Laravel CodeGen is a production-grade code generation suite that transforms your development workflow by automatically scaffolding complete, tested, production-ready Laravel components with terminal prompts, interactive wizards, modern frontend templates (Vue 3, React TSX, Inertia, Blade, Livewire), and domain-driven design support.

---

## ✨ Key Capabilities

- **🏃‍♂️ 10x Faster Scaffolding**: Scaffolds complete CRUD modules in seconds with zero configuration.
- **🪄 Interactive Scaffolding Wizard**: Run `php artisan codegen:wizard` for interactive prompt-guided generation.
- **🎨 Modern Frontend Support**: Built-in support for **Inertia.js Vue 3**, **Inertia.js React (TSX)**, **Blade**, **Livewire**, and **TypeScript definitions**.
- **🏰 Domain-Driven Design (DDD)**: Use `--domain=DomainName` to structure modules cleanly into domain namespaces.
- **📝 Form Requests & Backed Enums**: Auto-infer validation rules (`--requests`) and scaffold PHP 8.1+ String Enums (`--enum`).
- **🛡️ Repository Pattern**: Scaffold decoupled repository interfaces and Eloquent implementations (`--repository`).
- **⚡ Domain Events & Listeners**: Scaffold model lifecycle events and listener classes (`--events`).
- **📄 OpenAPI 3.0 Spec Generator**: Generate `openapi.json` specs automatically (`php artisan codegen:openapi`).
- **🔄 Reverse Engineering Magic**: Convert existing database tables into models, migrations, and controllers (`php artisan codegen:reverse-engineer`).
- **🧹 Cleanup & Rollback**: Safely remove all generated module artifacts with `php artisan codegen:clean {Model}`.
- **📑 Publishable Stubs**: Customize generated code templates using `php artisan codegen:publish-stubs`.
- **🧪 Pest PHP Test Suite**: Auto-generates comprehensive Pest PHP feature tests out of the box.

---

## 📦 Installation

Install via Composer:

```bash
composer require tgozo/laravel-codegen --dev
```

The package auto-registers its service provider. No extra setup required!

### Optional: Publish Configuration & Custom Stubs
```bash
# Publish config file
php artisan vendor:publish --tag="laravelcodegen-config"

# Publish customizable stubs to resources/stubs/vendor/laravelcodegen
php artisan codegen:publish-stubs
```

---

## 🚀 Quick Start & CLI Usage

### 🧙‍♂️ Interactive Terminal Wizard
Run the interactive wizard powered by Laravel Prompts:

```bash
php artisan codegen:wizard
```

---

### ⚡ Scaffolding Command Flags

```bash
# Complete CRUD module with Vue 3 Inertia, Form Requests, and Pest Tests
php artisan make:codegen-migration create_articles_table --all --inertia --requests -p

# Complete DDD module with React TSX, TypeScript definitions, and Enums
php artisan make:codegen-migration create_products_table --all --react --types --enum=ProductStatus:draft,published --domain=Catalog

# Dry Run / Preview mode (simulates generation without creating files)
php artisan make:codegen-migration create_orders_table --all --dry-run
```

#### Command Flag Reference

| Option | Description |
|--------|-------------|
| `-m, --model` | Generate Eloquent Model with `casts()` and relationships |
| `-c, --controller` | Generate RESTful Controller |
| `-f, --factory` | Generate Model Factory with smart faker matching |
| `-s, --seeder` | Generate Database Seeder |
| `-p, --pest` | Generate Pest PHP feature test suite |
| `-l, --livewire` | Generate Livewire components |
| `--inertia` | Generate Vue 3 Inertia pages (`Index.vue`, `Create.vue`) |
| `--react` | Generate React TSX Inertia pages (`Index.tsx`, `Create.tsx`) |
| `--types` | Generate TypeScript definition (`resources/js/types/{Model}.d.ts`) |
| `--requests` | Generate `Store{Model}Request.php` & `Update{Model}Request.php` |
| `--enum=Name:case1,case2` | Generate PHP 8.1+ Backed String Enum |
| `--repository` | Generate Repository contract and Eloquent implementation |
| `--events` | Generate `App\Events\{Model}Created.php` and `Handle{Model}Created.php` |
| `--soft-deletes-actions` | Include `restore()` and `forceDelete()` controller methods |
| `--domain=Name` | Scope generated files to domain path (DDD) |
| `--dry-run` | Preview generated files without writing to disk |
| `--all` | Generate complete module suite |
| `--force` | Overwrite existing files |

---

## 🛠️ Specialized Commands

### 🔄 Reverse Engineer Existing Database
Convert an existing database table or schema into models and migrations:

```bash
# Reverse engineer all database tables
php artisan codegen:reverse-engineer --all

# Target specific tables
php artisan codegen:reverse-engineer --tables=users,orders --all
```

---

### 📄 OpenAPI 3.0 Spec Generator
Generate OpenAPI 3.0 JSON specification for frontend clients and Postman:

```bash
php artisan codegen:openapi --output=openapi.json
```

---

### 🧹 Module Rollback & Cleanup
Safely inspect and delete all generated files for a specific module:

```bash
php artisan codegen:clean Article
```

---

### 📦 Migration Squashing & Combining
Merge multiple incremental migrations into a single clean base migration file:

```bash
# Squash migrations for a single table
php artisan make:codegen-squash products

# Combine modifier migrations
php artisan codegen:combine-migrations --table=users
```

---

## 🧪 Running Package Tests

The package features a complete Pest PHP test suite built on Orchestra Testbench:

```bash
cd the-package
./vendor/bin/pest tests
```

---

## 📈 Roadmap & Completed Features

- [x] **API Documentation** - Auto-generate OpenAPI/Swagger 3.0 spec (`codegen:openapi`)
- [x] **Event/Listener System** - Auto-generate domain events and listeners (`--events`)
- [x] **TypeScript Integration** - Auto-generate TypeScript interface definitions (`--types`)
- [x] **React & Vue Support** - Auto-generate Inertia Vue 3 & React TSX pages (`--inertia`, `--react`)
- [x] **Repository Pattern** - Auto-generate repository contracts & Eloquent implementations (`--repository`)
- [x] **Interactive Terminal Wizard** - Laravel Prompts guided workflow (`codegen:wizard`)
- [x] **Custom Stubs Loader** - Publish & customize package stubs (`codegen:publish-stubs`)
- [ ] **Docker Integration** - Scaffolding custom Docker configurations
- [ ] **Multi-tenancy Support** - Scaffolding tenant-aware models and migrations

---

## 🛡️ Security

If you discover any security-related issues, please email [dev@tgozo.co.zw](mailto:dev@tgozo.co.zw) instead of using the issue tracker.

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

## 🙏 Credits

- **Takudzwa Gozo** - [GitHub](https://github.com/tgozo19)
- **All Contributors** - Thank you for making this package better!

---

<div align="center">

**⭐ If this package saved you time, please consider giving it a star on GitHub! ⭐**

[Report Bug](https://github.com/tgozo19/laravel-codegen/issues) • [Request Feature](https://github.com/tgozo19/laravel-codegen/issues)

</div>
