# Migration Combination Examples

This directory contains example migrations that demonstrate how the `codegen:combine-migrations` command works.

## Example Migrations

### Initial State

**File:** `2024_01_01_000000_create_users_table.php`

Creates the base `users` table with:
- `id` (primary key)
- `full_name` (will be renamed to `name`)
- `email_address` (will be renamed to `email`)
- `legacy_field` (will be dropped)
- `email_verified_at`
- `password`
- `rememberToken`
- `timestamps`

### Modifier Migrations

1. **`2024_01_02_000000_add_address_to_users_table.php`**
   - Adds: `address`, `city`, `country`

2. **`2024_01_03_000000_add_phone_to_users_table.php`**
   - Adds: `phone`, `mobile`

3. **`2024_01_04_000000_drop_legacy_from_users_table.php`**
   - Removes: `legacy_field`

4. **`2024_01_05_000000_rename_columns_in_users_table.php`**
   - Renames: `full_name` → `name`
   - Renames: `email_address` → `email`

## How to Use These Examples

### Step 1: Copy to Your Laravel Project

```bash
# Copy these example migrations to your Laravel project
cp examples/migrations/*.php /path/to/your/laravel-app/database/migrations/
```

### Step 2: Preview the Combination (Dry Run)

```bash
cd /path/to/your/laravel-app
php artisan codegen:combine-migrations --table=users --dry-run
```

**Expected Output:**
```
🔍 Running in DRY RUN mode - no changes will be made
🔍 Scanning migrations...

📋 Processing table: users
  Found 4 modifier migration(s)
  📝 Processing: 2024_01_02_000000_add_address_to_users_table.php
    ✅ Would merge and delete 2024_01_02_000000_add_address_to_users_table.php
  📝 Processing: 2024_01_03_000000_add_phone_to_users_table.php
    ✅ Would merge and delete 2024_01_03_000000_add_phone_to_users_table.php
  📝 Processing: 2024_01_04_000000_drop_legacy_from_users_table.php
    ✅ Would merge and delete 2024_01_04_000000_drop_legacy_from_users_table.php
  📝 Processing: 2024_01_05_000000_rename_columns_in_users_table.php
    ✅ Would merge and delete 2024_01_05_000000_rename_columns_in_users_table.php
  ✅ Would update create_users_table migration with 4 change(s)

✅ Migration combination complete!
```

### Step 3: Apply the Combination

```bash
php artisan codegen:combine-migrations --table=users
```

### Step 4: Verify the Result

After running the command, only `2024_01_01_000000_create_users_table.php` will remain, and it will contain:

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');  // ← renamed from full_name
    $table->string('email')->unique();  // ← renamed from email_address
    // legacy_field removed completely ✓
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->string('address')->nullable();  // ← added
    $table->string('city')->nullable();  // ← added
    $table->string('country')->default('Zimbabwe');  // ← added
    $table->string('phone')->nullable();  // ← added
    $table->string('mobile')->nullable();  // ← added
    $table->timestamps();
});
```

## Expected Results

### Before Running Command

```
database/migrations/
├── 2024_01_01_000000_create_users_table.php
├── 2024_01_02_000000_add_address_to_users_table.php
├── 2024_01_03_000000_add_phone_to_users_table.php
├── 2024_01_04_000000_drop_legacy_from_users_table.php
└── 2024_01_05_000000_rename_columns_in_users_table.php
```

**Total:** 5 migration files

### After Running Command

```
database/migrations/
└── 2024_01_01_000000_create_users_table.php (containing all changes)
```

**Total:** 1 migration file

## Testing the Feature

To test that everything works correctly:

```bash
# 1. Run migrations
php artisan migrate:fresh

# 2. Verify the users table structure
php artisan tinker
# Then run: Schema::getColumnListing('users')

# Expected columns:
# - id
# - name (not full_name)
# - email (not email_address)
# - email_verified_at
# - password
# - remember_token
# - address
# - city
# - country
# - phone
# - mobile
# - created_at
# - updated_at
# (notice: NO legacy_field)
```

## What This Demonstrates

- ✅ **Adding columns** from multiple migrations
- ✅ **Dropping columns** that were in the original migration
- ✅ **Renaming columns** directly in the create migration
- ✅ **Chronological processing** - changes are applied in order
- ✅ **Clean migration history** - from 5 files down to 1

## Tips

1. Always use `--dry-run` first to preview changes
2. These examples work best in a fresh Laravel installation
3. The command preserves all column modifiers (nullable, default, unique, etc.)
4. All modifier migrations are deleted after successful merge
