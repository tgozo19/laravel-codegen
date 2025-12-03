# Update: Intelligent Deduplication

## Change Summary

I've enhanced the `codegen:combine-migrations` command with **intelligent column deduplication** and **cleanup-only mode**.

## The Problem

Users encountered issues where:
1. Running the command multiple times caused duplicate columns
2. Existing migration files had duplicates from manual edits or previous bugs
3. The command wouldn't fix existing files if no new migrations were merged

## The Solution

### 1. Prevent Duplicates on Add
Before adding a column from a modifier migration, the command now checks if that column already exists in the target file. If it does, it skips the addition, preventing duplicates at the source.

### 2. Remove Existing Duplicates
I implemented a `removeDuplicateColumns` method that scans the `Schema::create` block and removes any subsequent definitions of the same column. It preserves the first occurrence.

### 3. Cleanup-Only Mode
The command now runs the cleanup/formatting logic **even if no modifier migrations are found**. This allows you to use the command just to fix/format existing migration files!

## Example Usage

**Fixing a messy file:**
```bash
# Even if you have no new migrations to merge, this will clean up your file!
php artisan codegen:combine-migrations --table=users
```

**Result:**
- Removes duplicate columns (e.g. triple `address` definitions)
- Fixes indentation
- Fixes spacing

## Files Updated

- `src/Console/Commands/Migrations/CombineMigrationsCommand.php`

---

**Date**: December 3, 2025
**Reason**: User reported duplication issues and requested cleanup capability
**Impact**: Ensures migration files remain valid and clean regardless of how many times the command is run
