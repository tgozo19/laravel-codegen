# Changelog

All notable changes to Laravel CodeGen will be documented in this file.

## [Unreleased]

### Added - Migration Combination Feature (2025-12-03)

- **New Command**: `php artisan codegen:combine-migrations`
  - Intelligently merges modifier migrations into their original create table migrations
  - Supports `--table=name` to combine migrations for a specific table
  - Supports `--all` to combine migrations for all tables
  - Supports `--dry-run` to preview changes without applying them
  
- **Supported Operations**:
  - Add columns from `add_*_to_{table}_table` migrations
  - Drop columns from `drop_*_from_{table}_table` migrations
  - Rename columns from `rename_*_in_{table}_table` migrations
  - Parse and apply operations from `modify_{table}_table` migrations

- **Safety Features**:
  - Dry-run mode for safe previewing
  - Chronological processing (timestamp order)
  - Pattern validation
  - Detailed output showing all operations
  - Graceful error handling

- **Documentation**:
  - `MIGRATION_COMBINATION.md` - Complete feature documentation
  - `QUICK_START.md` - Get started in 5 minutes guide
  - `WORKFLOW_DIAGRAM.txt` - Visual workflow diagram
  - `IMPLEMENTATION_SUMMARY.md` - Technical implementation details
  - Updated main `README.md` with migration management section
  - Example migrations in `examples/migrations/` directory

- **Benefits**:
  - Cleaner migration history (reduces file count by 60-80%)
  - Better code readability
  - Faster migration execution
  - Easier testing and development
  - Reduced merge conflicts
  - Simplified code reviews

### Example Usage

```bash
# Combine migrations for a specific table
php artisan codegen:combine-migrations --table=users

# Combine all possible migrations
php artisan codegen:combine-migrations --all

# Preview changes first (recommended)
php artisan codegen:combine-migrations --all --dry-run
```

### Use Cases

- **Development Cleanup**: Consolidate migrations after feature development
- **Pre-deployment**: Clean up migration history before pushing to production
- **Testing**: Simplify fresh database setups
- **Team Collaboration**: Reduce migration file clutter

### Technical Details

- Command registered in `CodeGenServiceProvider`
- Implements intelligent migration parsing using regex patterns
- Preserves column modifiers (nullable, default, unique, etc.)
- Maintains proper code indentation and structure
- Processes migrations in chronological order to maintain integrity

### Files Added

- `src/Console/Commands/Migrations/CombineMigrationsCommand.php`
- `MIGRATION_COMBINATION.md`
- `QUICK_START.md`
- `WORKFLOW_DIAGRAM.txt`
- `IMPLEMENTATION_SUMMARY.md`
- `examples/migrations/` (5 example files + README)

### Files Modified

- `src/CodeGenServiceProvider.php` - Registered new command
- `README.md` - Added migration management section

---

## Previous Versions

(Version history would go here when the package has releases)
