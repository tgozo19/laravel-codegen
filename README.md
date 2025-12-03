# 🚀 Laravel CodeGen - The Ultimate Laravel Development Accelerator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tgozo/laravel-codegen.svg?style=flat-square)](https://packagist.org/packages/tgozo/laravel-codegen)
[![Total Downloads](https://img.shields.io/packagist/dt/tgozo/laravel-codegen.svg?style=flat-square)](https://packagist.org/packages/tgozo/laravel-codegen)
[![License](https://img.shields.io/packagist/l/tgozo/laravel-codegen.svg?style=flat-square)](https://packagist.org/packages/tgozo/laravel-codegen)

**Stop writing boilerplate code. Start building features.**

Laravel CodeGen is a powerful code generation package that transforms your development workflow by automatically generating complete, production-ready Laravel components with a single command. Say goodbye to repetitive coding and hello to blazing-fast development!

## ✨ Why Laravel CodeGen?

- **🏃‍♂️ 10x Faster Development**: Generate complete CRUD operations in seconds, not hours
- **🎯 Zero Configuration**: Works out of the box with sensible defaults
- **🔧 Highly Configurable**: Customize every aspect of generated code to match your standards
- **🧪 Test-Driven**: Automatically generates comprehensive Pest PHP tests
- **📱 Modern Stack Support**: Built-in Livewire components with Alpine.js integration
- **🔄 Reverse Engineering**: Convert existing databases to Laravel code instantly
- **🎨 Consistent Code Quality**: Follows Laravel best practices and coding standards

## 🔥 Features That Will Blow Your Mind

### 🎪 Complete Application Scaffolding
Generate entire application modules with a single command:

- **📋 Database Migrations** - With intelligent field types and relationships
- **📦 Eloquent Models** - Including relationships, casts, and factory integration
- **🎮 Controllers** - Full CRUD operations with proper validation
- **🏭 Model Factories** - Smart fake data generation using AI-powered field detection
- **🌱 Database Seeders** - Pre-populated with realistic test data
- **🚏 Routes** - RESTful routes with proper naming conventions
- **👀 Blade Views** - Beautiful, responsive UI components
- **⚡ Livewire Components** - Modern, reactive components (Create, Read, Update, Delete)
- **🧪 Pest PHP Tests** - Comprehensive test coverage for all endpoints
- **📝 Form Requests** - Robust validation with custom rules

### 🔄 Reverse Engineering Magic
Already have a database? No problem!

```bash
# Convert your entire database to Laravel code
php artisan codegen:reverse-engineer --all

# Generate models only
php artisan codegen:reverse-engineer --models

# Target specific tables
php artisan codegen:reverse-engineer --tables=users,posts --all
```

**Supports multiple databases**: MySQL, PostgreSQL, SQLite

### 🎨 Smart Code Generation
- **Intelligent Field Detection**: Automatically detects field types and generates appropriate form inputs
- **Relationship Inference**: Discovers and generates model relationships automatically
- **Faker Integration**: Uses advanced algorithms to generate realistic fake data
- **Validation Rules**: Auto-generates validation rules based on database constraints
- **Consistent Naming**: Follows Laravel naming conventions perfectly

## 📦 Requirements

- **PHP** >= 8.1
- **Laravel** >= 10.0
- **Composer** 2.0+

## ⚡ Quick Installation

```bash
composer require tgozo/laravel-codegen
```

**That's it!** The package auto-registers itself. No additional setup required.

### Optional: Publish Configuration
```bash
php artisan vendor:publish --provider="Tgozo\LaravelCodegen\CodeGenServiceProvider"
```

## 🚀 Usage Examples

### 🎯 Quick Start - Complete CRUD in 30 Seconds

```bash
# Generate everything for a blog post system
php artisan make:codegen-migration create_posts_table --all
```

**This single command creates:**
- ✅ Migration with intelligent field prompts
- ✅ Post model with relationships and casts
- ✅ PostController with full CRUD operations
- ✅ PostFactory with realistic fake data
- ✅ Database seeder
- ✅ 4 Livewire components (View, Show, Create, Edit)
- ✅ RESTful routes
- ✅ Responsive Blade views
- ✅ Complete Pest PHP test suite (12+ tests)
- ✅ Form request validation

### 🎪 Selective Generation

```bash
# Just migration, model, and factory
php artisan make:codegen-migration create_users_table -mf

# Migration, model, controller, and seeder
php artisan make:codegen-migration create_products_table -mcs

# Everything with Livewire components
php artisan make:codegen-migration create_orders_table -mcsfp --livewire
```

### 🔄 Reverse Engineer Existing Database

```bash
# Convert entire database
php artisan codegen:reverse-engineer --all

# Only specific tables
php artisan codegen:reverse-engineer --tables=users,posts,comments --models

# With custom connection
php artisan codegen:reverse-engineer --connection=legacy --all
```

### 🎨 Advanced Field Configuration

During migration creation, you can specify complex field types:

```
Field name: title
Field type: string:nullable:unique:index

Field name: price
Field type: decimal:precision:10,2:default:0.00

Field name: user_id
Field type: foreignId:constrained:cascadeOnDelete
```

## 🛠️ Command Options

| Option | Description |
|--------|-------------|
| `-m, --model` | Generate Eloquent model |
| `-c, --controller` | Generate controller with CRUD operations |
| `-f, --factory` | Generate model factory |
| `-s, --seeder` | Generate database seeder |
| `-p, --pest` | Generate Pest PHP tests |
| `-l, --livewire` | Generate Livewire components |
| `--all` | Generate everything (equivalent to -mcsfpl) |
| `--force` | Overwrite existing files |
| `--relates` | Specify model relationships |
| `--except` | Exclude specific generations |

## 🎭 Generated Code Examples

### 📦 Eloquent Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Using $guarded = [] instead of $fillable for models with many fields
     * This approach is more maintainable for models with 18+ fields
     * and provides better security against mass-assignment vulnerabilities.
     */
    protected $guarded = [];

    protected $casts = [
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'metadata' => 'array',
    ];

    // Relationships are auto-generated
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
```

### 🎮 Controller with CRUD
```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use Illuminate\Http\Controller;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with('user')->paginate(15);
        return view('posts.index', compact('posts'));
    }

    public function store(StorePostRequest $request)
    {
        $post = Post::create($request->validated());
        return redirect()->route('posts.show', $post)
            ->with('success', 'Post created successfully!');
    }

    // ... other CRUD methods
}
```

### ⚡ Livewire Component
```php
<?php

namespace App\Livewire\Post;

use App\Models\Post;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

class Create extends Component
{
    public string $title = '';
    public string $content = '';
    public bool $is_published = false;

    protected $rules = [
        'title' => 'required|min:3|max:255',
        'content' => 'required|min:10',
        'is_published' => 'boolean',
    ];

    public function save()
    {
        $this->validate();
        
        Post::create([
            'title' => $this->title,
            'content' => $this->content,
            'is_published' => $this->is_published,
            'user_id' => auth()->id(),
        ]);

        session()->flash('success', 'Post created successfully!');
        return redirect()->route('posts.index');
    }

    #[Title('Create Post')]
    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.post.create');
    }
}
```

### 🧪 Pest PHP Tests
```php
<?php

use App\Models\Post;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can display posts index page', function () {
    Post::factory(3)->create();
    
    $this->get(route('posts.index'))
        ->assertOk()
        ->assertViewIs('posts.index')
        ->assertViewHas('posts');
});

it('can create a new post', function () {
    $postData = [
        'title' => 'Test Post',
        'content' => 'This is a test post content.',
        'is_published' => true,
    ];
    
    $this->post(route('posts.store'), $postData)
        ->assertRedirect()
        ->assertSessionHas('success');
    
    $this->assertDatabaseHas('posts', $postData);
});

// ... 10+ more tests covering all scenarios
```

## 🎨 Configuration

### Publishing Configuration
```bash
php artisan vendor:publish --provider="Tgozo\LaravelCodegen\CodeGenServiceProvider"
```

### Configuration Options
```php
<?php

return [
    // Choose between 'controller' or 'livewire' for generated logic
    'logic_preference' => 'livewire',
    
    // Default namespace for generated classes
    'default_namespace' => 'App',
    
    // Custom stub paths
    'stub_paths' => [
        // Override default stubs with your custom ones
    ],
    
    // Test framework preference
    'test_framework' => 'pest', // or 'phpunit'
    
    // View framework preference
    'view_framework' => 'livewire', // or 'blade'
];
```

## 🎭 Migration Patterns

The package supports various migration patterns:

| Pattern | Description | Example |
|---------|-------------|----------|
| `create_{table}_table` | Create new table | `create_posts_table` |
| `add_{columns}_to_{table}_table` | Add columns | `add_featured_to_posts_table` |
| `drop_{columns}_from_{table}_table` | Remove columns | `drop_legacy_from_users_table` |
| `modify_{table}_table` | Modify existing table | `modify_posts_table` |

## 🔧 Advanced Features

### 🤖 AI-Powered Field Detection
The package includes intelligent field type detection:

```php
// Automatically detects appropriate faker methods
'email' => fake()->unique()->safeEmail(),
'phone' => fake()->phoneNumber(),
'address' => fake()->address(),
'birth_date' => fake()->dateTimeBetween('-50 years', '-18 years'),
'avatar' => fake()->imageUrl(200, 200, 'people'),
```

### 🔗 Relationship Management
```bash
# Specify relationships during generation
php artisan make:codegen-migration create_posts_table --relates="user:belongsTo,comments:hasMany"
```

### 📱 Livewire Integration
Generated Livewire components include:
- Real-time validation
- Loading states
- Error handling
- Success messages
- Responsive design
- Alpine.js integration

## 🧪 Testing

The package generates comprehensive test suites:

```bash
# Run generated tests
php artisan test

# Or with Pest
vendor/bin/pest
```

Generated tests cover:
- ✅ Route accessibility
- ✅ CRUD operations
- ✅ Validation rules
- ✅ Authorization policies
- ✅ Database constraints
- ✅ Model relationships
- ✅ Factory generation
- ✅ Seeder execution

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Development Setup
```bash
git clone https://github.com/tgozo/laravel-codegen.git
cd laravel-codegen
composer install
vendor/bin/pest
```

## 🔗 Migration Management

### 📦 Combine Migrations

Keep your migrations clean by combining modifier migrations into their original create table migrations!

```bash
# Combine all migrations (default - simplest!)
php artisan codegen:combine-migrations

# Combine migrations for a specific table
php artisan codegen:combine-migrations --table=users

# Preview changes without applying them (recommended first!)
php artisan codegen:combine-migrations --dry-run
```

**What it does:**

This command intelligently identifies and merges modifier migrations (like `add_address_to_users_table`) back into their original create table migrations (like `create_users_table`).

**Supported Operations:**

- ✅ **Add Columns**: Columns from `add_*_to_{table}_table` migrations are added to the create migration
- ✅ **Drop Columns**: Columns marked for dropping in `drop_*_from_{table}_table` are removed from the create migration
- ✅ **Rename Columns**: Column renames from `rename_*_in_{table}_table` are applied directly in the create migration
- ✅ **Modify Table**: General table modifications are parsed and applied appropriately

**Example:**

Before:
```
2024_01_01_000000_create_users_table.php
2024_01_02_000000_add_address_to_users_table.php
2024_01_03_000000_add_phone_to_users_table.php
2024_01_04_000000_drop_legacy_from_users_table.php
```

After running `php artisan codegen:combine-migrations --table=users`:
```
2024_01_01_000000_create_users_table.php  (now includes address, phone, and legacy removed)
```

**Benefits:**

- 🧹 **Cleaner Migration History**: Fewer files to manage
- 📚 **Better Readability**: Complete table structure in one file
- ⚡ **Faster Migrations**: Single migration instead of multiple sequential ones
- 🎯 **Easier Testing**: Fresh database setup is simpler with combined migrations

## 📈 Roadmap

- [ ] **GraphQL Integration** - Generate GraphQL schemas and resolvers
- [ ] **API Documentation** - Auto-generate OpenAPI/Swagger docs
- [ ] **Docker Integration** - Generate Docker configurations
- [ ] **Queue Jobs** - Generate background job classes
- [ ] **Event/Listener System** - Generate event-driven architectures
- [ ] **Multi-tenancy Support** - Generate tenant-aware models and migrations
- [ ] **Custom Stubs Manager** - GUI for managing custom stubs

## 🛡️ Security

If you discover any security-related issues, please email [dev@tgozo.co.zw](mailto:dev@tgozo.co.zw) instead of using the issue tracker.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## 🙏 Credits

- **Takudzwa Gozo** - [GitHub](https://github.com/tgozo19)
- **All Contributors** - Thank you for making this package better!

---

<div align="center">

**⭐ If this package saved you time, please consider giving it a star! ⭐**

[Report Bug](https://github.com/tgozo19/laravel-codegen/issues) • [Request Feature](https://github.com/tgozo19/laravel-codegen/issues) • [Documentation](https://github.com/tgozo19/laravel-codegen/wiki)

</div>
