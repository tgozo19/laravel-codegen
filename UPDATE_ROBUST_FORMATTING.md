# Update: Robust Code Formatting

## Change Summary

I've implemented a **robust code formatter** within the `codegen:combine-migrations` command to ensure that generated migration files are always perfectly formatted.

## The Problem

Previously, merging migrations could result in:
- Inconsistent indentation (mixed spaces/tabs or wrong levels)
- Multiple statements on a single line (e.g., `$table->string('email'); $table->string('phone');`)
- Messy whitespace

## The Solution

I added a custom `formatCodeByIndentation` method that:
1. **Splits code into lines** and trims whitespace
2. **Calculates indentation levels** based on braces `{` and `}`
3. **Fixes multi-statement lines** by splitting them
4. **Reconstructs the file** with consistent 4-space indentation

## How It Works

The formatter runs automatically after all changes are applied but before the file is saved. It handles:
- Opening/closing braces `{}`
- Array definitions `[]`
- Schema closures
- Blank lines and spacing

## Example Result

**Before (Messy):**
```php
        Schema::create('users', function (Blueprint $table) {
            $table->id();
$table->string('name'); $table->string('email');
                        $table->string('address');
        });
```

**After (Clean):**
```php
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('address');
        });
```

## Benefits

- **Professional Output**: Generated code looks hand-written
- **PSR Compliance**: Follows standard PHP formatting rules
- **Readability**: Easier to review and maintain
- **Reliability**: Works even if external tools like Pint aren't installed

## Files Updated

- `src/Console/Commands/Migrations/CombineMigrationsCommand.php`

---

**Date**: December 3, 2025
**Reason**: User feedback regarding poor indentation in generated files
**Impact**: Significantly improved code quality of generated migrations
