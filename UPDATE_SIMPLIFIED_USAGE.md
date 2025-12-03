# Update: Simplified Command Usage

## Change Summary

The `codegen:combine-migrations` command now **defaults to processing all tables** when no options are specified, making it even easier to use!

## Before (Required Flag)

```bash
# Had to specify --all
php artisan codegen:combine-migrations --all

# Or specify a table
php artisan codegen:combine-migrations --table=users
```

## After (Simpler Default)

```bash
# Processes all tables by default - no flag needed! 🎉
php artisan codegen:combine-migrations

# Or specify a table for focused processing
php artisan codegen:combine-migrations --table=users

# Dry run is just as easy
php artisan codegen:combine-migrations --dry-run
```

## Usage Examples

### Quick Cleanup (Most Common Use Case)

```bash
# Simply run the command - it does everything!
php artisan codegen:combine-migrations
```

### Safe Preview First

```bash
# Check what will happen first
php artisan codegen:combine-migrations --dry-run

# Then apply if you're happy
php artisan codegen:combine-migrations
```

### Targeted Processing

```bash
# Focus on one table
php artisan codegen:combine-migrations --table=users

# Preview just that table
php artisan codegen:combine-migrations --table=users --dry-run
```

## Why This Change?

**User Feedback**: "if I don't specify the table then just do for all"

This change makes the most common use case (combining all migrations) the simplest to execute. No need to remember flags - just run the command!

## Backward Compatibility

✅ The `--all` flag still works (for existing scripts)
✅ The `--table` flag works exactly the same
✅ The `--dry-run` flag works exactly the same

## Command Options Summary

| Command | Description |
|---------|-------------|
| `php artisan codegen:combine-migrations` | **Combine all tables (default)** |
| `php artisan codegen:combine-migrations --table=users` | Combine specific table |
| `php artisan codegen:combine-migrations --dry-run` | Preview all changes |
| `php artisan codegen:combine-migrations --table=users --dry-run` | Preview specific table |

## Updated Documentation

The following files have been updated to reflect this change:

- ✅ `CombineMigrationsCommand.php` - Updated handle() method and description
- ✅ `QUICK_START.md` - Simplified all examples
- ✅ `README.md` - Already shows both syntaxes (update shows simpler default)

## Benefits

1. **Faster workflow** - Type less, do more
2. **More intuitive** - Default behavior matches most common use case
3. **Easier to remember** - Just type the command, no flags needed
4. **Still flexible** - Can target specific tables when needed

## Migration Path

Users can immediately start using the simpler syntax:

```bash
# Old way (still works)
php artisan codegen:combine-migrations --all

# New way (simpler!)
php artisan codegen:combine-migrations
```

Both produce the exact same result!

---

**Date**: December 3, 2025
**Reason**: User feedback requesting simpler default behavior
**Impact**: Positive - Makes the tool easier to use while maintaining full backward compatibility
