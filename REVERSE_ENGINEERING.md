# Reverse Engineering Feature

This feature allows you to analyze existing database tables and generate Laravel models and migrations from them. This is particularly useful when working with legacy databases or when you need to recreate migrations from an existing schema.

## Installation Requirements

This feature works out of the box with your Laravel installation. No additional dependencies are required.

The feature supports MySQL, PostgreSQL, and SQLite databases using native Laravel database connections.

## Usage

### Basic Usage

To reverse engineer all tables in your database:

```bash
php artisan codegen:reverse-engineer --all
```

### Generate Only Models

```bash
php artisan codegen:reverse-engineer --models
```

### Generate Only Migrations

```bash
php artisan codegen:reverse-engineer --migrations
```

### Specify Specific Tables

```bash
php artisan codegen:reverse-engineer --all --tables=users,posts,comments
```

### Use Different Database Connection

```bash
php artisan codegen:reverse-engineer --all --connection=mysql2
```

### Force Overwrite Existing Files

```bash
php artisan codegen:reverse-engineer --all --force
```

## Features

### Model Generation

The command analyzes your database tables and generates Laravel models with:

- **Proper table name mapping**: Automatically sets the `$table` property if needed
- **Mass assignment protection**: Uses `$guarded = []` for easier management of models with many fields
- **Type casting**: Automatically detects and sets appropriate casts for columns
- **Timestamps detection**: Automatically detects if the table has `created_at` and `updated_at` columns
- **Relationships**: Generates `belongsTo` and `hasMany` relationships based on foreign key constraints

#### Example Generated Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $table = 'posts';

    public $timestamps = true;

    /**
     * Using fillable is good when you have 2–10 fields, but what if you have 20–50 fields in your model? I have experienced creating a table
     * with 18 fields to be exact, and none of those fields are needed to be protected. Trust me, it's quite of a work. "I'm not
     * saying that it's not good" using fillable in this situation, you may, but if you want an easier way to secure it from
     * mass-assignment, then guarded will be more preferable.
     */
    protected $guarded = [];

    protected $casts = [
        'published_at' => 'datetime',
        'user_id' => 'integer',
        'category_id' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id', 'id');
    }
}
```

### Migration Generation

The command generates Laravel migrations that recreate your existing table structure:

- **Column types**: Maps database column types to Laravel migration methods
- **Column modifiers**: Includes nullable, default values, unsigned, etc.
- **Foreign key constraints**: Recreates foreign key relationships
- **Proper naming**: Uses Laravel migration naming conventions

#### Example Generated Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id('id');
            $table->string('title', 255);
            $table->text('content');
            $table->integer('user_id')->unsigned();
            $table->integer('category_id')->unsigned()->nullable();
            $table->dateTime('published_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('category_id')->references('id')->on('categories');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

## Supported Database Types

The reverse engineering feature supports the following database column types:

- **Integers**: `integer`, `bigint`, `smallint`, `tinyint`
- **Decimals**: `decimal`, `float`, `double`
- **Strings**: `string`, `text`, `longtext`, `mediumtext`
- **Dates**: `date`, `datetime`, `timestamp`, `time`
- **Booleans**: `boolean`
- **JSON**: `json`
- **Binary**: `binary`

## Exclusions

The following Laravel system tables are automatically excluded from reverse engineering:

- `migrations`
- `password_resets`
- `password_reset_tokens` 
- `failed_jobs`
- `personal_access_tokens`
- `jobs`
- `job_batches`

## Relationship Detection

The feature automatically detects relationships based on foreign key constraints:

- **BelongsTo**: Generated for tables with foreign keys
- **HasMany**: Generated for reverse relationships
- **Naming**: Relationship methods use camelCase naming (e.g., `userId` becomes `user()`)

## Limitations

1. **Complex relationships**: Many-to-many relationships require manual setup
2. **Polymorphic relationships**: Not automatically detected
3. **Custom accessors/mutators**: Must be added manually
4. **Validation rules**: Must be added separately
5. **Database-specific types**: Some database-specific column types may map to generic Laravel types

## Best Practices

1. **Review generated files**: Always review and adjust the generated models and migrations
2. **Test relationships**: Verify that detected relationships work as expected  
3. **Add validation**: Implement form requests and validation rules separately
4. **Backup first**: Always backup your existing files before using `--force`
5. **Incremental approach**: Start with specific tables using `--tables` option

## Troubleshooting

### Foreign Key Detection Issues

If foreign keys are not detected properly:

1. Ensure your database actually has foreign key constraints defined
2. Check that the foreign key constraints follow standard naming conventions
3. Some databases may require specific privileges to read constraint information

### Column Type Mapping Issues

If column types are not mapped correctly:

1. Check the `mapDatabaseTypeToMigration()` method in the command class
2. You may need to extend the mapping for your specific database type
3. Manual adjustment of the generated migration may be required

### Performance with Large Databases

For databases with many tables:

1. Use the `--tables` option to process specific tables
2. Consider running the command in smaller batches
3. The analysis phase may take time for databases with complex schemas