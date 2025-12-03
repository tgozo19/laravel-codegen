# Migration Combination Feature

## Overview

The `codegen:combine-migrations` command allows you to merge modifier migrations (add/drop/rename columns) into their original create table migrations. This keeps your migration folder clean and your table structures consolidated.

## Usage

### Combine migrations for a specific table
```bash
php artisan codegen:combine-migrations --table=users
```

### Combine all possible migrations
```bash
php artisan codegen:combine-migrations --all
```

### Preview changes (dry run)
```bash
php artisan codegen:combine-migrations --table=users --dry-run
php artisan codegen:combine-migrations --all --dry-run
```

## Supported Migration Patterns

The command recognizes and processes the following migration patterns:

### 1. Add Columns Pattern
**Migration naming:** `add_{column_names}_to_{table}_table`

**Example:** `2024_01_02_000000_add_address_to_users_table.php`

```php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('address')->nullable();
        $table->string('city')->nullable();
    });
}
```

**Result:** These columns will be added to the `create_users_table` migration before `timestamps()`.

---

### 2. Drop Columns Pattern
**Migration naming:** `drop_{column_names}_from_{table}_table`

**Example:** `2024_01_03_000000_drop_legacy_from_users_table.php`

```php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['legacy_field', 'old_field']);
    });
}
```

**Result:** The specified columns will be removed from the `create_users_table` migration.

---

### 3. Rename Columns Pattern
**Migration naming:** `rename_{description}_in_{table}_table`

**Example:** `2024_01_04_000000_rename_columns_in_users_table.php`

```php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->renameColumn('full_name', 'name');
        $table->renameColumn('email_address', 'email');
    });
}
```

**Result:** The column names will be updated in the `create_users_table` migration.

---

### 4. Modify Table Pattern
**Migration naming:** `modify_{table}_table`

**Example:** `2024_01_05_000000_modify_users_table.php`

```php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('phone')->nullable();
        $table->dropColumn('fax');
    });
}
```

**Result:** All operations are parsed and applied (add phone, remove fax).

---

## How It Works

1. **Scans** your migrations folder for create table migrations
2. **Identifies** all modifier migrations that target the same table
3. **Parses** each modifier migration to extract the operations
4. **Applies** operations to the create migration in chronological order:
   - **Add operations**: Columns are inserted before `timestamps()` or at the end
   - **Drop operations**: Matching column definitions are removed
   - **Rename operations**: Column names are updated in-place
5. **Deletes** the modifier migrations after successful merge
6. **Updates** the create migration file

---

## Complete Example

### Before Running Command

```
database/migrations/
├── 2024_01_01_000000_create_users_table.php
├── 2024_01_02_000000_add_address_to_users_table.php
├── 2024_01_03_000000_add_phone_to_users_table.php
├── 2024_01_04_000000_drop_legacy_from_users_table.php
└── 2024_01_05_000000_rename_columns_in_users_table.php
```

**Original `create_users_table.php`:**
```php
public function up()
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('full_name');
        $table->string('email_address')->unique();
        $table->string('legacy_field');
        $table->timestamps();
    });
}
```

**Run the command:**
```bash
php artisan codegen:combine-migrations --table=users
```

### After Running Command

```
database/migrations/
└── 2024_01_01_000000_create_users_table.php
```

**Updated `create_users_table.php`:**
```php
public function up()
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');  // renamed from full_name
        $table->string('email')->unique();  // renamed from email_address
        // legacy_field removed
        $table->string('address')->nullable();  // added
        $table->string('phone')->nullable();  // added
        $table->timestamps();
    });
}
```

---

## Best Practices

1. **Always backup** your migrations before running this command
2. **Use `--dry-run`** first to preview changes
3. **Run on development** environments, not production
4. **Commit changes** to version control separately
5. **Run before deployment** to keep production migrations clean
6. **Test thoroughly** after combining migrations

---

## Safety Features

- **Dry Run Mode**: Preview changes without modifying files
- **Chronological Processing**: Migrations are processed in timestamp order
- **Pattern Validation**: Only recognized migration patterns are processed
- **Detailed Output**: Shows exactly what's being merged and deleted

---

## Limitations

- Only works with standard Laravel migration syntax
- Custom Schema builder methods may not be recognized
- Complex migrations with conditionals may need manual review
- Table renames are not automatically processed (would require manual intervention)

---

## Table Rename Handling

For table renames, the command currently doesn't automatically process them since it's complex to determine intent. Here are recommendations:

**Option 1: Update Manually**
- Rename the create migration file
- Update the table name in the migration  
- Update all modifier migrations to use the new name
- Then run combine-migrations

**Option 2: Create Fresh Migration**
- If table was never deployed to production
- Delete old migrations
- Create a new create migration with the correct table name

---

## Troubleshooting

### "Could not find create_{table}_table migration"
- Ensure the create migration follows the naming pattern: `*_create_{table}_table.php`
- Check that the table name matches exactly

### "Could not parse migration operations"
- The migration may use custom syntax
- Check that it follows standard Laravel migration patterns
- Try manual merge for complex migrations

### Changes not applied
- Ensure migrations follow standard Laravel patterns
- Check file permissions
- Review the output for specific error messages

---

## Command Output

The command provides detailed feedback:

```
🔍 Scanning migrations...
Found 3 create table migrations

📋 Processing table: users
  Found 3 modifier migration(s)
  📝 Processing: 2024_01_02_000000_add_address_to_users_table.php
    ✅ Merged and deleted 2024_01_02_000000_add_address_to_users_table.php
  📝 Processing: 2024_01_03_000000_drop_legacy_from_users_table.php
    ✅ Merged and deleted 2024_01_03_000000_drop_legacy_from_users_table.php
  ✅ Updated create_users_table migration with 2 change(s)

✅ Migration combination complete!
```

---

## Integration with Laravel CodeGen

This command works seamlessly with migrations generated by:

```bash
php artisan make:codegen-migration create_posts_table --all
php artisan make:codegen-migration add_featured_to_posts_table
```

After development iterations, clean up with:

```bash
php artisan codegen:combine-migrations --all
```
