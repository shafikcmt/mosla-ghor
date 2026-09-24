# Product Editor recovery — 2026-09-24

> **Latest local database follow-up:** The database is reachable and the Product Editor migration was already applied in batch 5 at the start of the follow-up. There are no pending migrations. The missing-tags error is no longer reproducible through authenticated local Laravel HTTP-kernel requests. See the follow-up below; it supersedes the earlier connection-refused limitation. No migration was executed by this follow-up.

Commit preparation rechecked the source changes, additive migration, tests, routes, and assets. The full test suite still has only the documented pre-existing customer OTP redirect failure (41 passed, 1 failed, 276 assertions). PHP syntax and JavaScript syntax checks passed, all 19 product routes registered, and the Vite build passed. Generated `public/build/manifest.json` and compiled build assets are intentionally excluded from the source commit and remain local; deployment must provide the complete build output. No credentials, debug statements, runtime files, dependencies, or seeders are included in the Product Editor commit scope.

Recovered the interrupted working tree without resetting, committing, or pushing it. No development or production database migrations were executed. Automated tests use an isolated SQLite in-memory database.

## Cause and asset repair

The new shared editor called `filemtime()` for `public/css/product-editor.css` and `public/js/product-editor.js`, but neither file existed. Both admin and vendor Create/Edit pages included that partial. The admin/vendor layouts use direct assets and CDN Tailwind rather than Vite entry points.

Added the actual CSS and JavaScript under `public/`, kept the existing layout conventions, and replaced filesystem timestamp calls with an explicit asset version. These source assets must be included in deployment; they require no Vite compilation. There are no remaining `filemtime()` calls in the shared editor. Admin Create/Edit, including `/admin/products/1/edit`, were rendered through Laravel HTTP tests before further feature completion. This is not a claim of interactive browser verification.

## Interrupted files reviewed

All 19 files present in the initial dirty worktree were reviewed:

- `app/Http/Controllers/Admin/ProductController.php`
- `app/Http/Controllers/Vendor/ProductController.php`
- `app/Http/Controllers/Concerns/ManagesProductVariants.php`
- `app/Models/Product.php`
- `app/Models/ProductVariant.php`
- `app/Models/Tag.php`
- `app/Services/ProductEditor.php`
- `app/Support/ProductMedia.php`
- `database/migrations/2026_09_23_000001_add_product_tags_and_variant_attributes.php`
- `resources/views/admin/products/_form.blade.php`
- `resources/views/admin/products/create.blade.php`
- `resources/views/admin/products/edit.blade.php`
- `resources/views/vendor/products/_form.blade.php`
- `resources/views/vendor/products/create.blade.php`
- `resources/views/vendor/products/edit.blade.php`
- `resources/views/partials/products/editor.blade.php`
- `resources/views/partials/products/fields.blade.php`
- `resources/views/partials/products/media.blade.php`
- `resources/views/partials/products/retail-packs.blade.php`

The shared transactional service, staged media handling, gallery ownership tokens, pack preservation, tag model/relationship, variant attribute cast, and additive migration already existed. They were retained and tested. Missing assets, an old variant form without attribute controls or reliable unchecked values, disconnected public tags, corrupted Bengali messages, and incomplete sales-channel enforcement needed completion. Existing routes resolve to controller methods; both roles use the same editor and persistence flow.

## Files changed in this continuation

Existing interrupted files edited:

- `app/Http/Controllers/Admin/ProductController.php`
- `app/Http/Controllers/Vendor/ProductController.php`
- `app/Http/Controllers/Concerns/ManagesProductVariants.php`
- `app/Services/ProductEditor.php`
- `resources/views/admin/products/create.blade.php`
- `resources/views/admin/products/edit.blade.php`
- `resources/views/vendor/products/create.blade.php`
- `resources/views/vendor/products/edit.blade.php`
- `resources/views/partials/products/editor.blade.php`
- `resources/views/partials/products/fields.blade.php`
- `resources/views/partials/products/media.blade.php`

Additional existing files edited:

- `app/Http/Controllers/ProductController.php`
- `resources/views/home.blade.php` — one wholesale visibility condition; no page redesign.
- `resources/views/storefront/product-detail.blade.php`
- `public/build/manifest.json` — generated CSS hash from the successful build.

New continuation files:

- `public/css/product-editor.css`
- `public/js/product-editor.js`
- `resources/views/partials/products/variants.blade.php`
- `resources/views/partials/products/variant-row.blade.php`
- `tests/Feature/ProductEditorTest.php`
- `PRODUCT_EDITOR_RECOVERY.md`

The build also generated ignored `public/build/assets/app-DQQUru5H.css`. Deployment must run the build or include its complete output, not only the tracked manifest.

## Corrected behavior

Media: omitted/empty main-image input preserves the stored path. A validated upload is stored before the database update; retired files are deleted only after commit, only within owned upload directories, and only when no other product/gallery/variant references them. Failed storage or database updates preserve old media and clean up staged uploads. Gallery removal accepts hashes of the current product's gallery entries, not arbitrary paths. Variant images and uploaded video follow the same lifecycle. Explicit image removal now works even when the form also submits the existing external URL; replacement uploads still take precedence.

Channels: retail-only shows retail controls, wholesale-only shows wholesale controls, and selecting both shows both. JavaScript immediately hides and disables irrelevant fieldsets; the backend excludes irrelevant inputs independently. Disabling retail preserves its base price, pack IDs, manual overrides, and active flags. Re-enabling retail retains overrides. Saving a legacy retail product without base packs now generates the missing packs. Blank MOQ units and low-stock thresholds are normalized to valid database defaults.

Vendor visibility: creation uses the existing per-vendor/global auto-approval settings. Otherwise products remain pending; vendor requests cannot grant approval. Admin approval, active status, and the selected sales channel control publication. Pending products are excluded from homepage queries and detail pages. Public detail/related-product queries now enforce the requested channel. Wholesale homepage rows no longer incorrectly require retail packs or variants. Editor status text explains pending, inactive, and published states. Vendor ownership checks remain enforced.

Tags: the existing `tags`/`product_tag` design is used, with whitespace/case normalization and deduplication, limits of 20 tags and 80 characters each, escaped form/public output, and save/reload/clear support. The editor offers add/remove chips with a textarea fallback. Public product pages show tags and escaped keyword metadata; existing title, description, and canonical behavior remains.

Variants: existing records update by ID. Attribute name/value rows support Brand, Size, Weight, and other labels. Each variant has its own image, retail/sale price, stock, active flag, and optional default selection. Omitted images/stock remain intact. Existing removal requests deactivate records, preserving IDs and historical references; unsaved rows can be removed. Foreign variant/default IDs are rejected. Blank new active flags get a real default before default-variant validation. Sale-price validation checks the resulting values even when only one price is submitted. Submitted rows and attributes reload after validation errors; file inputs must be selected again.

Validation also aligns image/video URL length with the existing MySQL string columns and rejects generated variant names exceeding the name column length. Bengali page titles and success/error messages were repaired.

## Database

No additional migration was necessary. Retained the interrupted additive migration `2026_09_23_000001_add_product_tags_and_variant_attributes.php`: creates `tags`, creates the foreign-key-backed `product_tag` pivot with a composite primary key, and adds nullable JSON `product_variants.attributes`. No deployed migration was edited. Its schema constructs are compatible with MySQL's existing unsigned bigint IDs and JSON support. Actual execution against MySQL remains a server/staging check; automated migration execution was SQLite only.

The read-only `php artisan migrate:status` check could not connect to local MySQL at `127.0.0.1:3306` (connection refused). Therefore the current local database's migration status and live product ID 1 page could not be verified. The HTTP rendering results above use the migrated test database. Start the local MySQL service and check/apply the retained additive migration if pending before checking the live editor.

## Verification

- Focused Product Editor suite: all 14 tests pass (181 assertions in the final full run), covering all four forms, channel markup, pack preservation/recovery, media preservation/replacement, shared-file retention, invalid uploads, gallery ownership, storage failure, transaction rollback, tags, variant attributes/images/stock/deactivation/default ownership, vendor authorization, approval, and public visibility.
- Final `php artisan test --compact`: 41 passed, 1 failed, 276 assertions. The sole failure is the pre-existing `CustomerOtpLoginTest::test_full_otp_login_flow_authenticates_customer` at line 74: expected `/account`, received `/`. It also failed in the initial run before the product feature completion. No unrelated authentication changes were made.
- `php artisan route:list --except-vendor`: passed, 339 application routes.
- PHP syntax checks: passed for all 11 relevant PHP implementation/migration/test files, with changed service/tests checked again after final edits.
- `node --check public/js/product-editor.js`: passed.
- `git diff --check`: passed; Git emitted only existing Windows line-ending conversion notices.
- `npm.cmd run build`: passed, Vite 7.3.3, 55 modules. `npm run build` initially hit PowerShell's script execution policy; invoking the standard Windows npm command shim succeeded.
- An initial test harness using migration rollback hit an unrelated older SQLite rollback/index incompatibility. The final regression harness creates fresh in-memory connections and applies additive migrations without rolling back deployed migrations.

## Remaining manual and server checks

No interactive browser checks were performed. Check admin and vendor Create/Edit on desktop and mobile: immediate channel switching, keyboard focus, tag chips, adding/removing attribute/variant rows, validation redisplay, upload previews, and actual image/video URLs after saving.

Before server deployment, back up the database and apply the retained additive migration through the normal deployment process. Verify on the target MySQL version, deploy both `public/css/product-editor.css` and `public/js/product-editor.js`, run `npm ci`/`npm run build` or deploy complete built assets, refresh compiled Blade caches, and verify the public storage link plus storage permissions and upload limits. Test a real pending vendor product, then admin approval, channel publication, media replacement, and retrieval on the Linux server. No production migration, deployment, commit, or push was performed here.

## Local database follow-up

### Migration discovery and review

The only migration introduced by Product Editor is:

`database/migrations/2026_09_23_000001_add_product_tags_and_variant_attributes.php`

Initial and final migration status both showed **Ran, batch 5**. All other migrations were also applied; there were no pending Product Editor or unrelated migrations. The local connection is `127.0.0.1`, database `spice_ecommerce`, environment `local`, MariaDB 10.4.32. No schema migration was executed during this follow-up, and the migration ledger was not changed. Who applied batch 5 before this inspection was not determined.

`Product::tags()` and `Tag::products()` both resolve to `product_tag`, using `product_id` and `tag_id`; neither expects pivot timestamps. The actual pivot matches this contract. `tags.id` and both foreign keys are unsigned bigint; the pivot primary key is `(product_id, tag_id)`, its tag foreign key has an index, and both foreign keys use `ON DELETE CASCADE`. The normalized tag key has a unique index. The existing variant model's array cast matches the nullable JSON attributes column. MariaDB represents that column as longtext with a JSON-validity constraint.

The migration is additive: it creates two tables and adds one nullable column without updating/deleting existing product, price, image, stock, or variant values. Dependencies already exist. Its `down()` drops the pivot before tags and removes only the added variant column. This preserves pre-feature data, but **rollback after feature use would discard tags, assignments, and variant attributes**. Prefer rolling back application code while retaining this additive schema. MySQL/MariaDB DDL is not transactionally rolled back; inspect schema state before retrying any partially failed deployment. No rollback was executed.

### Pretend SQL

Executed the exact-path command:

```text
php artisan migrate --path=database/migrations/2026_09_23_000001_add_product_tags_and_variant_attributes.php --pretend
```

It reported `Nothing to migrate` because batch 5 was already applied. To review the actual generated SQL without altering migration history or executing DDL, the migration's `up()` was also compiled inside Laravel's connection `pretend()` mode:

```sql
create table `tags` (`id` bigint unsigned not null auto_increment primary key, `name` varchar(80) not null, `normalized_key` char(64) not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `tags` add unique `tags_normalized_key_unique`(`normalized_key`);
create table `product_tag` (`product_id` bigint unsigned not null, `tag_id` bigint unsigned not null, primary key (`product_id`, `tag_id`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `product_tag` add constraint `product_tag_product_id_foreign` foreign key (`product_id`) references `products` (`id`) on delete cascade;
alter table `product_tag` add constraint `product_tag_tag_id_foreign` foreign key (`tag_id`) references `tags` (`id`) on delete cascade;
alter table `product_variants` add `attributes` json null;
```

These definitions match the actual InnoDB tables inspected with `SHOW CREATE TABLE`. No tables or columns were created by this follow-up because all three schema additions already existed.

### Local verification and data preservation

- `php artisan migrate:status`: no pending migrations; exact-path status reconfirmed batch 5.
- `php artisan optimize:clear`: succeeded for config, cache, compiled files, events, routes, and views.
- Authenticated requests through the full Laravel HTTP kernel against the actual local database: `/admin/products/create` and `/admin/products/1/edit` through `/admin/products/7/edit` all returned **200** with the shared editor rendered. These are application-kernel checks, not browser/network-server checks.
- Saved a unique tag on product 1 through `ProductEditor`, reloaded its relationship, and rendered the Edit page containing that tag. This ran inside an outer transaction and was rolled back, leaving no test tag or assignment behind. It verifies persistence/reload within a transaction, not a retained committed tag.
- The local database has **zero existing variants**. Created a temporary variant inside the same transaction, with attributes, stock, and a reference to the product's image; updated it through `ProductEditor` and rendered it successfully. Its ID, image path, attributes, and stock survived the update. The probe was rolled back. Existing variant image files cannot be visually checked when there are no existing variant records; automated tests cover uploaded images.
- Full ordered-row SHA-256 snapshots matched before/after for `products` (7), `product_prices` (42), `product_variants` (0), `tags` (0), `product_tag` (0), and `order_items` (0). Thus no existing row values, pack records, or image paths changed during verification. No upload or deletion of media files was performed. Rolled-back tag/variant inserts may advance AUTO_INCREMENT counters; those counters were not reset.
- `php artisan test --compact --filter=ProductEditorTest`: **14 passed, 181 assertions** (15.96 seconds), using the isolated SQLite in-memory test database.
- No frontend files changed, and no asset rebuild was run in this follow-up. No destructive migration, production action, commit, or push was performed.

### Production deployment order — instructions only, not executed

Do not expose the new application code to requests before its schema exists. Stage the complete new release (including these migrations, dependencies, and the already-built assets) separately from the live release. For an in-place deployment, use maintenance mode before copying code. Pause/drain background workers that could execute the new code. A zero-downtime release can instead apply the reviewed additive migration from a staged release while old code serves requests, then switch only after schema verification; assess DDL locking before choosing that approach.

The following is a **maintenance deployment example**. Replace paths, database name, and backup configuration for the actual server. These commands have not been run on production:

```sh
# 1. From the currently live release, before exposing new code:
cd /srv/moslamart/current
php artisan down

# 2. Take and verify a database backup using the site's backup procedure.
# Example: credentials belong in a protected option file, not the command line.
mysqldump --defaults-extra-file=/secure/mysql-backup.cnf --single-transaction --routines --triggers --events spice_ecommerce > /secure/backups/spice_ecommerce-before-product-editor.sql

# 3. Stage/deploy the complete release through the normal deployment process.
# Keep public traffic in maintenance until schema and application checks pass.
cd /srv/moslamart/releases/RELEASE_ID
php artisan migrate:status
php artisan migrate --path=database/migrations/2026_09_23_000001_add_product_tags_and_variant_attributes.php --pretend --force

# 4. Inspect the SQL and run ONLY this path if it is pending and the schema agrees.
php artisan migrate --path=database/migrations/2026_09_23_000001_add_product_tags_and_variant_attributes.php --force
php artisan migrate:status --path=database/migrations/2026_09_23_000001_add_product_tags_and_variant_attributes.php
php artisan optimize:clear

# 5. Activate the staged release using the normal release switch procedure.
# Ensure maintenance state remains shared/preserved across the switch.
cd /srv/moslamart/current
php artisan queue:restart

# 6. Verify through the site's maintenance bypass/internal access:
# admin Edit/Create; tag save/reload; packs/variants/media;
# pending vendor hidden, approved active product visible in its selected channels.
# Reopen traffic only after verification, then repeat public smoke checks.
php artisan up
```

If migration status says `Ran` but tables are missing, stop and investigate the database/connection or partial schema state; do not delete migration history or blindly retry this non-idempotent migration. On application rollback, keep the additive schema instead of dropping newly entered feature data.

Remaining manual verification: use the actual browser/server session to open product 1 and Create, add/save/reload a tag, exercise channel controls, and check variant upload previews and stored image URLs. If the original missing-table error persists in that browser while these checks pass, verify that the web process uses the same database as CLI and restart long-running application processes after clearing their configuration caches.
