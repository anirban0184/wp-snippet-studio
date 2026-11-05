# Development

The 1.0.0 product is the HTML/text edition. The full development source and old execution/fatal-error tests live on `legacy/full-snippets-0.1.1`; they are not part of this package.

| Path | Responsibility |
| --- | --- |
| `coder-snippet-studio.php` | Metadata, explicit class loading and registration |
| `includes/class-installer.php` | Additive schema version 2 and conservative legacy migration |
| `includes/class-permissions.php` | Administrator and single-site boundary |
| `includes/class-validator.php` | Typed fields, strict IDs, content limits and HTML allow-list |
| `includes/class-repository.php` | Prepared database queries, approvals, inactive saves and revision checks |
| `includes/class-renderer.php` | Active approved HTML/text shortcodes and output pause |
| `admin/`, `assets/` | Protected content UI and local/core editor assets |
| `uninstall.php` | Preserve data unless permanent removal is explicitly configured |
| `tests/` | Guarded disposable integration, browser, package-upgrade and retention checks |
| `scripts/build.php` | Deterministic runtime-only ZIP and SHA-256 checksum |

The existing table is `{prefix}wpsstudio_snippets`. Legacy scope, priority and manual-receipt columns remain only for data compatibility. New records use the `shortcode` scope; there is no execution ordering or manual runner. Lists omit content bodies and paginate 25 records ordered by ID.

## Quality checks

Use PHP 8.2+, Composer, Node 22+, WP-CLI, MariaDB/MySQL and disposable single-site WordPress.

```sh
composer install
composer validate --strict
composer lint
composer audit --locked
npm ci --ignore-scripts
npm audit --audit-level=moderate
node --check assets/admin.js
node --check tests/browser.mjs
php scripts/build.php
```

## Disposable checks

Never use production/client databases. The guard requires WP-CLI, `WPSSTUDIO_TESTS=1`, local/development environment and a localhost site URL. Fixtures create and remove test users, pages and content. The integration suite temporarily renames the table to check fail-closed behavior.

```sh
export WPSSTUDIO_TESTS=1
wp eval-file tests/integration.php --user=YOUR_TEST_ADMIN
export WPSSTUDIO_QA_FIXTURE="$PWD/build/content/fixture.json"
mkdir -p build/content
wp eval-file tests/browser-fixtures.php --user=YOUR_TEST_ADMIN
# Serve the disposable installation at its configured localhost URL.
npm run test:browser
wp eval-file tests/browser-cleanup.php --user=YOUR_TEST_ADMIN
```

The private fixture file contains randomly generated test credentials and is excluded from git and packages. Cleanup removes only tracked fixture records and users. Browser screenshots are local test artifacts, not directory assets or public production screenshots.

For package upgrade checks: install the archived 0.1.1 ZIP, deactivate it without loading it, run `tests/upgrade-fixture.php` with that plugin skipped, then replace it with the 1.0.0 ZIP and activate. Run `tests/upgrade-assert.php` to check preservation and inactive approvals. For replacement/deactivation/ordinary-uninstall checks, use the seed/check/remove actions in `tests/retention.php`. The CI workflow records the exact commands.

Run the official WordPress Plugin Check against the installed minimal ZIP with all default checks and no excluded checks or ignored-warning flags. Preserve its raw output, then run `php tests/plugin-check-report.php` to retain every finding in a structured report and reject unreviewed findings. The retained WP-prefix naming warnings require directory review; a successful CI job is not a clean naming scan or directory approval. Source annotations name and explain the necessary custom-table operations. Keep actual results in [validation](validation.md).

Development uses a feature branch, reviewed diff, checks and a pull request. GitHub does not deploy to production or deliver WordPress customer updates. Directory submission, review, SVN publication and update delivery are separate steps.
