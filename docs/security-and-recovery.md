# Security and recovery

## Before installation

Use a single-site **staging** installation, a verified database/file backup and a working hosting/file-access recovery route. WordPress 6.4+ and PHP 8.2+ are the compatibility floors; use actively maintained, patched WordPress and PHP versions in production. The previously inspected glowingcoders.com production host used PHP 8.1, so that environment needs an appropriate supported PHP upgrade and staging verification before installing this build. No live-site changes were made during development.

PHP and JavaScript authors must be trusted like plugin developers. PHP executes with the site's PHP permissions, including database and filesystem access. JavaScript executes in visitors' browsers. Syntax validation, signatures and recovery **do not sandbox code** or make malicious code safe.

## Access and source approval

- Management requires **both** `manage_options` and `edit_plugins`, single-site WordPress and permitted file modifications. `DISALLOW_FILE_EDIT`, `DISALLOW_FILE_MODS` and the WordPress file-modification policy are respected.
- Every mutation uses authenticated POST, a per-action/per-snippet WordPress nonce and a fresh revision. A nonce is not the authorization check. No public PHP-execution API, source API or remote snippet download exists.
- New snippets and edits save **inactive**. Review source before activation. Every save deactivates the snippet, including previously active ones. Inline priority updates preserve status.
- Installation-bound HMAC signatures protect approved source and execution settings against unsigned record changes. Unverified rows cannot run. After salt rotation or migration with different salts, review and save affected snippets inactive to approve them again.
- Signatures detect record changes; they do not protect a fully compromised server, replace secure WordPress salts, encrypt source at rest, or guarantee protection from database replay/backup restoration. A database backup includes snippet source. Do not put credentials in source when secure configuration can supply them.
- Database write errors from source-bearing queries are suppressed in this plugin's database layer to avoid logging SQL containing code. Only generic failure categories are stored. Hosting/PHP/WordPress logs are controlled by the site owner.

## Execution behavior

Automatic PHP loads at `plugins_loaded` priority **20**, after active plugin files and before the theme. Use WordPress hooks for work that needs later lifecycle stages; theme functions and main-query conditional tags are not yet available at snippet load time. Loading order is ascending priority, then ascending ID. It does not change priorities specified inside a snippet's own hooks. PHP output on successful automatic execution is allowed; use hooks to output at the intended location.

| PHP run location | Behavior |
| --- | --- |
| Everywhere | Ordinary frontend/admin requests, REST, cron and CLI. Recovery routes below are excluded. |
| Frontend only | Non-admin web requests, excluding detected REST routes, cron, CLI and recovery routes. It is request-level targeting, not a page-content condition engine. |
| Admin only | Requests where `is_admin()` is true, including admin AJAX. Plugin management and login recovery routes are excluded. |
| Run once manually | Explicit authorized POST only; remains inactive and never loads automatically. |

**Login (`wp-login.php`) and this plugin's management pages always bypass automatic PHP**, so a snippet cannot lock the normal recovery path through its own execution. This also means PHP snippets cannot customize login in 0.1.1. Other plugins, WordPress or infrastructure can still prevent login/admin access.

PHP source must be a code body without `<?php`, `?>`, namespace declarations or `__halt_compiler`. Tokenizer parsing checks syntax without execution and fails closed if unavailable. It does not prove logical safety, resource bounds or correctness of later callbacks.

Active CSS loads through WordPress enqueue APIs in the frontend head; active JavaScript loads in the footer. Themes must call the normal head/footer hooks. No automatic jQuery dependency is added. Enter raw CSS/JS without style/script tags; tag breakouts are rejected even inside strings/comments. Browser language syntax, CSP compatibility and code effects still need staging testing. Already cached pages/assets and already running requests cannot be revoked by a toggle; purge the site's caches when needed.

Only active, verified **HTML/text** can render through `[wpsstudio id="12"]`. HTML is filtered to WordPress post-safe markup on save and output. It is not a script/tracking-code insertion channel. Text is escaped and line breaks preserved. Neither evaluates nested shortcodes or PHP. Inactive/missing/invalid IDs and executable snippet types return an empty string. These content snippets are public wherever an author places their shortcode; they are not a private-content access system.

## Manual attempts and concurrency

Run once claims the record with a revision-and-signature compare-and-swap **before** executing. A failed/interrupted run remains attempted; duplicate or concurrent POSTs cannot repeat that claim. The last attempted source hash prevents repeating unchanged source, including after metadata edits. Changing source allows another attempt. This is not a permanent history of every prior code version and is not a rollback mechanism. Database restoration, deleting/recreating a snippet or changing its source can permit execution again. Manual direct output is discarded; effects such as data writes remain. Manual code runs during the protected admin POST, after `init` has already fired, so maintenance work should execute directly or use an appropriate later hook.

Stale editor/status/priority/delete forms are rejected. This protects against overwriting a newer save, not against the effects of an already executing request or malicious privileged code.

## Recover a broken site

1. Open `https://YOUR-SITE/wp-admin/admin.php?page=wpsstudio` directly. Log in if needed. This management route and login skip automatic PHP.
2. Enable **Safe Mode**. It pauses automatic PHP, CSS/JS output, HTML/text shortcodes and manual execution on new requests. Snippets remain editable and their active flags remain stored.
3. Disable or delete the problematic snippet. Review source and save inactive before retrying. Turn Safe Mode off only after reviewing all active code.
4. If the dashboard cannot be reached, add this before the stop-editing line in `wp-config.php` using hosting/file access:

   ```php
   define( 'WPSSTUDIO_SAFE_MODE', true );
   ```

   The constant takes precedence over the dashboard switch. Remove it after fixing code; a fatal may also have enabled the database Safe Mode option, which then needs turning off in the dashboard.
5. If needed, deactivate with `wp plugin deactivate wp-snippet-studio --skip-plugins=wp-snippet-studio` or rename the plugin directory using hosting/file access. Deactivation preserves snippets.

Immediate caught PHP exceptions quarantine that revision and enable Safe Mode. An immediate detected fatal also attempts quarantine and enables Safe Mode. A later fatal originating in a callback declared in this runner's evaluated source enables global Safe Mode for the next request. Recovery runs after the failing request has already failed.

This is **best effort**: shutdown cannot reliably attribute every error in external functions, undo side effects, stop all loops, handle a killed process, or recover from every memory/hosting failure. `exit`/`die` are not caught exceptions. File-based Safe Mode and backups remain necessary.

Disable public PHP error display on production (`WP_DEBUG_DISPLAY` false and appropriate hosting `display_errors` policy). If logging is enabled, store logs outside the document root with access restrictions. WordPress/PHP can log uncaught deferred errors; do not expose `debug.log`, database backups or source archives publicly.

## Data retention

The custom table is `$wpdb->prefix . 'wpsstudio_snippets'`. Deactivation, upgrades and ordinary deletion/uninstall preserve it. To permanently remove the table and this plugin's options during a WordPress uninstall, **back up first** and explicitly set:

```php
define( 'WPSSTUDIO_DELETE_DATA', true );
```

Remove that constant afterward. There is no trash/revision/restore UI in 0.1.1. Deleting an individual snippet is immediate and cannot undo its earlier effects. Multisite activation and execution are deferred.
