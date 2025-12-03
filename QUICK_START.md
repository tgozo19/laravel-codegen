# Quick Start Guide - Migration Combination

Get started with the migration combination feature in under 5 minutes!

## Prerequisites

- Laravel CodeGen package installed (`tgozo/laravel-codegen`)
- Existing Laravel project with migrations

## Quick Start (3 Steps)

### Step 1: Check Your Migrations

```bash
# List your current migrations
ls -la database/migrations/
```

Look for patterns like:
- `create_*_table.php` (base migrations)
- `add_*_to_*_table.php` (adding columns)
- `drop_*_from_*_table.php` (removing columns)
- `rename_*_in_*_table.php` (renaming columns)

### Step 2: Preview the Combination (SAFE!)

```bash
# Preview what will happen WITHOUT making changes (processes all tables by default)
php artisan codegen:combine-migrations --dry-run

# Or preview just one specific table
php artisan codegen:combine-migrations --table=users --dry-run
```

**What you'll see:**
```
🔍 Running in DRY RUN mode - no changes will be made
🔍 Scanning migrations...
Found 3 create table migrations

📋 Processing table: users
  Found 3 modifier migration(s)
  ✅ Would merge and delete 3 migration(s)
  
✅ Migration combination complete!
```

### Step 3: Apply the Combination

If the preview looks good, run it for real:

```bash
# Combine all migrations (default behavior)
php artisan codegen:combine-migrations

# OR combine just one table
php artisan codegen:combine-migrations --table=users
```

**Done!** 🎉 Your migrations are now combined!

---

## Real Example

### Your Current Migrations
```
database/migrations/
├── 2024_01_01_create_users_table.php
├── 2024_01_02_add_social_to_users_table.php
├── 2024_01_03_add_profile_to_users_table.php
└── 2024_01_04_drop_unused_from_users_table.php
```

### Run the Command
```bash
php artisan codegen:combine-migrations --table=users
```

### Result
```
database/migrations/
└── 2024_01_01_create_users_table.php  ← All changes merged here!
```

The other 3 files are **deleted** after being merged.

---

## Common Scenarios

### Scenario 1: Development Cleanup

You've been developing a feature and created many migrations:

```bash
# Clean up before committing (combines all tables by default)
php artisan codegen:combine-migrations
git add database/migrations/
git commit -m "Consolidate migrations"
```

### Scenario 2: Single Table Focus

Working on just the users table:

```bash
# Preview first
php artisan codegen:combine-migrations --table=users --dry-run

# Apply if it looks good
php artisan codegen:combine-migrations --table=users
```

### Scenario 3: Before Deployment

Clean up migrations before deploying:

```bash
# Combine everything
php artisan codegen:combine-migrations

# Run tests to ensure nothing broke
php artisan test

# Deploy with confidence
git push
```

---

## What Gets Combined?

| Migration Type | Pattern | Action |
|---------------|---------|--------|
| Add columns | `add_*_to_users_table` | Adds columns to create migration |
| Drop columns | `drop_*_from_users_table` | Removes columns from create migration |
| Rename columns | `rename_*_in_users_table` | Renames columns in create migration |
| Modify table | `modify_users_table` | Applies all operations found |

---

## Safety Tips

✅ **Always use `--dry-run` first**
```bash
php artisan codegen:combine-migrations --dry-run
```

✅ **Commit before combining**
```bash
git add .
git commit -m "Before migration combination"
php artisan codegen:combine-migrations
```

✅ **Test after combining**
```bash
php artisan migrate:fresh
php artisan test
```

✅ **Start with one table if unsure**
```bash
php artisan codegen:combine-migrations --table=users
```

---

## Troubleshooting

### "Could not find create_*_table migration"

**Problem:** The table name doesn't match.

**Solution:** Check that your create migration follows the pattern:
```
*_create_users_table.php  ✓ Correct
*_create_user_table.php   ✗ Won't match for --table=users
```

### "Could not parse migration operations"

**Problem:** Complex or non-standard migration syntax.

**Solution:** These migrations need manual review. The command will skip them and continue with others.

### Nothing happens

**Problem:** No modifier migrations found.

**Solution:** The command only works if you have both:
1. A create table migration
2. At least one modifier migration for that table

---

## Next Steps

- 📖 Read `MIGRATION_COMBINATION.md` for detailed documentation
- 🧪 Try the examples in `examples/migrations/`
- 📊 Check `WORKFLOW_DIAGRAM.txt` for visual reference
- 🔍 Review `IMPLEMENTATION_SUMMARY.md` for technical details

---

## Need Help?

- Check the [Laravel CodeGen README](README.md)
- Report issues on GitHub
- Review example migrations in `examples/`

---

## That's It! 

You're ready to keep your migrations clean and organized. Happy coding! 🚀
