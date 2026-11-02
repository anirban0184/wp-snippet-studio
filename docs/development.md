# Development

## Layout and lifecycle

| Path | Responsibility |
| --- | --- |
| `wp-snippet-studio.php` | Metadata, explicit class loading, activation and service registration |
| `includes/class-installer.php` | Additive dbDelta schema, schema-version checks, activation guard |
| `includes/class-permissions.php` | Trusted-author capabilities and WordPress modification policy |
| `includes/class-validator.php` | Field allow-lists, IDs/priorities, non-executing PHP syntax validation |
| `includes/class-repository.php` | Prepared database access, signatures, inactive saves, atomic revision/claim updates |
| `includes/class-runner.php` | Lifecycle/scopes, enqueue APIs, shortcodes, output handling and recovery |
| `admin/class-admin.php` | Menu, capability-gated reads, nonced POST dispatch, redirects |
| `admin/views/` | Escaped server-rendered list/editor views |
| `assets/admin.*` | Compact WordPress dashboard and native code-editor enhancement |
| `uninstall.php` | Preserve data by default; explicit configuration opt-in for removal |
| `tests/` | Disposable real-WordPress integration, policy and Playwright checks |
| `scripts/build.php` | Allow-listed install ZIP and SHA-256 checksum; no dev packages or test credentials shipped |

The table is `{prefix}wpsstudio_snippets`, inside the existing WordPress database. Columns: `id`, `title`, `description`, `code`, `type`, `scope`, `status`, `priority`, `revision`, `signature`, `last_run_hash`, `last_run_at`, `last_error`, `created_at`, `updated_at`. Times are UTC. There is a compound execution index on status/type/priority/id. List queries omit source and paginate 25 rows. The snippet count has no artificial cap; resource limits still apply.

`wpsstudio_schema_version` is separate from plugin version. Activation installs/migrates the table. Management visits apply pending migrations after plugin updates; execution pauses while the schema version is pending. A missing/unreadable table yields no executable rows. Do not change the signed field representation without a migration/reapproval plan.

## Tools

PHP 8.2+, Composer 2, Node 22+, MySQL/MariaDB, WP-CLI and a disposable **single-site** WordPress installation are needed for development checks. Production code has no Composer/npm dependencies and no build step at runtime.

```sh
composer install
composer validate --strict
composer lint
composer audit --locked
npm ci --ignore-scripts
npm audit --audit-level=moderate
```

Use `composer fix` for formatting; recheck `git diff --check` afterward. Review each suppression: SQL data/identifiers use placeholders, deliberate trusted PHP execution is explicitly documented, and database reads avoid stale caches for immediate revocation/recovery.

## Real WordPress checks

Never run tests against glowingcoders.com or another production/client database. Tests create users, posts and snippets, exercise deliberately broken PHP and temporarily rename the test table to simulate database loss. Use an empty disposable database, working file access and `WP_ENVIRONMENT_TYPE` set to `local` or `development`. Disable cron for deterministic fatal tests and keep debug logs outside the document root.

Install/link the repository as `wp-content/plugins/wp-snippet-studio`, then:

```sh
wp plugin activate wp-snippet-studio --user=YOUR_TEST_ADMIN
WPSSTUDIO_TESTS=1 wp eval-file tests/integration.php --user=YOUR_TEST_ADMIN

for policy in file_edit file_mods; do
  WPSSTUDIO_TESTS=1 WPSSTUDIO_TEST_POLICY="$policy" wp --require=tests/policy-bootstrap.php eval-file tests/policy.php --user=YOUR_TEST_ADMIN
done
WPSSTUDIO_TESTS=1 WPSSTUDIO_TEST_POLICY=tokenizer php -d disable_functions=token_get_all /path/to/wp-cli.phar --require=tests/policy-bootstrap.php eval-file tests/policy.php --user=YOUR_TEST_ADMIN
```

Run from the repository; supply `--path=/path/to/disposable-wordpress` to WP-CLI if it is outside the installed site. PHP extensions disabled in policy tests are disabled only for that process.

For browser tests, use a localhost URL, e.g. `http://127.0.0.1:8090`, and serve the disposable site there:

```sh
mkdir -p build/qa
export WPSSTUDIO_TESTS=1
export WPSSTUDIO_QA_FIXTURE="$PWD/build/qa/fixture.json"
npx playwright install chromium
wp eval-file tests/browser-fixtures.php --user=YOUR_TEST_ADMIN
php -S 127.0.0.1:8090 -t /path/to/disposable-wordpress
# In another terminal, export the same fixture variable and run:
npm run test:browser
wp eval-file tests/browser-cleanup.php --user=YOUR_TEST_ADMIN
```

Fixtures write randomly generated test credentials to a private ignored JSON file. Never commit or upload it. Cleanup removes tracked fixture data. On an interrupted test, enable file-based Safe Mode before cleanup if necessary. Screenshots go to ignored `build/qa/`. Browser cases verify real HTTP permissions/CSRF, responsive geometry, CRUD/toggles/priority, execution scopes, assets/content and immediate/deferred fatal recovery. The suite intentionally allows native confirmation dialogs and accepts them.

The GitHub workflow uses read-only repository permissions, locked development dependencies, pinned action commits and disposable databases. It never deploys, creates a release or handles production credentials. It checks PHP 8.2/WordPress 6.4, PHP 8.3/latest WordPress and PHP 8.4/latest WordPress. A configured job is not evidence of a successful run; see actual results in Actions and [validation](validation.md).

## Package and version control

```sh
php scripts/build.php
```

Requires the PHP zip extension for development only. It writes `build/wp-snippet-studio-0.1.0.zip` and its checksum with a single `wp-snippet-studio/` install directory. The allow-list excludes tests, credentials, dependencies, CI and development config. Repeated builds with the same source/toolchain produce the same archive.

For a version change, update the plugin header, `WPSSTUDIO_VERSION`, npm manifest/lock, changelog, readme and build documentation together. Validate installation of the built ZIP on an isolated site as well as the source checkout. Preserve data across replacement/deactivation/reactivation. Do not deploy to production or tag a stable customer release on the strength of lint alone.

Development uses a feature branch, a reviewed diff, automated checks and a pull request. GitHub is source control; no automatic customer updater exists in 0.1.0. Distribution and update signing/transport/version policy require a separate product decision.
