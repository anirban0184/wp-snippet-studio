# WP Snippet Studio

A free WordPress manager for trusted PHP, CSS, JavaScript, HTML and reusable text snippets, maintained by [Glowing Coders](https://glowingcoders.com/).

| Version | 0.1.1 development build |
| --- | --- |
| Status | Core implemented; staging and distribution review pending |
| Requirements | Single-site WordPress 6.4+, PHP 8.2+ |
| License | GPL-2.0-or-later |
| Slug / text domain | `wp-snippet-studio` |

This is a development build for isolated testing. It has not been deployed to glowingcoders.com, submitted to WordPress.org or published as a stable customer release. All implemented features are free.

## Available in 0.1.1

- Create snippets without an artificial count limit; edit and permanently delete them.
- PHP, CSS, JavaScript, filtered HTML and escaped reusable text.
- Snippet titles and descriptions; new snippets and every edit save **inactive**.
- Individual activation/deactivation with a separate trusted-code review step.
- PHP run locations: everywhere, frontend only, admin only, or one manual attempt per changed source.
- Priority defaults to 10, allows -9999 through 9999, and loads lower values first, then lower IDs.
- Active CSS loads in the frontend head; JavaScript loads in the footer through WordPress enqueue APIs.
- Active HTML/text renders through `[wpsstudio id="12"]`. PHP cannot be run through a public shortcode.
- A native WordPress code editor with plain-text fallback, separate settings and a **Save inactive** button.
- Compact type navigation: All Snippets, PHP, CSS, JS, HTML and Text.
- One horizontal table: **Name | Type | Run location | Priority | Status | Actions**. It keeps that arrangement on small screens using horizontal scrolling.
- Inline priority updates, status switches and visible Edit/Delete/Run once actions.
- Safe Mode, protected management/login recovery routes and best-effort failure recovery.
- Data retention across theme changes, plugin updates, deactivation and ordinary uninstall.

The list paginates 25 rows. HTML/text appear at their shortcode position; priority does not reposition them. Manual snippets stay inactive. Priority controls this plugin's loading order, not hook priorities inside your code. Search, tags, bulk actions and import/export are deferred.

## Quick start on staging

1. Back up the disposable/staging site's database and files, and confirm hosting/file access for recovery.
2. Run `php scripts/build.php` in a development checkout, or use the generated installation ZIP from a verified build. GitHub's source archive includes development files; prefer the packaged ZIP.
3. Upload `wp-snippet-studio-0.1.1.zip` through Plugins → Add Plugin → Upload Plugin and activate as a trusted administrator.
4. Open **Snippet Studio → Add New**, choose type/location/priority and save inactive.
5. Review the source, then activate from All Snippets. For HTML/text, place the displayed shortcode in your content.

The latest read-only check of glowingcoders.com on **2026-11-03** confirmed **production, WordPress 7.1.4 / PHP 8.1.34**. It is below this build's PHP requirement. A PHP 8.2+ staging copy and verified backup/recovery are needed before site-specific installation testing. The plugin was not installed or activated on production.

## Execution and content rules

Automatic PHP loads at `plugins_loaded` priority 20, before the theme. Use hooks for later work. Everywhere includes ordinary frontend/admin requests, REST, cron and CLI. Frontend excludes detected REST/cron/CLI requests; admin follows `is_admin()`, including admin AJAX. Login and the plugin's own management routes always bypass automatic PHP for recovery.

Enter PHP bodies without PHP tags or namespaces. CSS/JS must omit style/script tags. JavaScript receives no automatic jQuery dependency. HTML is filtered to WordPress post-safe markup; text is escaped with line breaks preserved. Nested shortcodes are not evaluated.

A manual attempt is atomically recorded before execution. Duplicate/concurrent submissions and unchanged previously attempted source are blocked. Failed/interrupted attempts still count; source changes allow a new attempt. This is not exactly-once delivery, a permanent execution history or a rollback mechanism.

## Database and architecture

Snippets live inside the existing WordPress database in **`$wpdb->prefix . 'wpsstudio_snippets'`**, e.g. `wp_wpsstudio_snippets`. This is a custom table, not a separate database.

The versioned schema stores metadata, source, type/scope/status, priority, UTC timestamps, revision, an installation-bound integrity signature and a manual-attempt receipt. Prepared queries protect data and identifiers. Revision checks reject stale writes; atomic claims protect manual attempts.

[Development and database details](docs/development.md) describe every code directory, schema field, lifecycle stage, test setup and build process.

## Security and recovery

Security is a mandatory release criterion. Known exploitable vulnerabilities block a public release.

Management checks **manage_options + edit_plugins** and WordPress file-edit/modification restrictions. Every mutation requires authenticated POST, the correct action/snippet nonce and a fresh revision. There is no public source/PHP endpoint, remote executable-code download, telemetry or account requirement.

PHP parsing validates syntax without running it and fails closed if tokenizer is unavailable. Signed approvals detect altered source/settings and fail closed on unverified records; salt changes require explicit source review and an inactive save. These signatures do not encrypt source, sandbox code or protect a fully compromised server.

PHP executes with the website's permissions, and JavaScript executes in visitors' browsers. Only trusted authors should manage it. Immediate caught exceptions quarantine the revision and enable Safe Mode. Fatal recovery is best effort; it cannot undo effects or handle every external callback, loop, memory/hosting failure or killed process.

For recovery, open `/wp-admin/admin.php?page=wpsstudio` directly and enable Safe Mode. The fallback is adding this above the stop-editing line in `wp-config.php`:

```php
define( 'WPSSTUDIO_SAFE_MODE', true );
```

Safe Mode pauses PHP, CSS/JS, shortcodes and manual execution on new requests. Keep public PHP error display disabled and logs outside the public document root.

Read [Security and recovery](docs/security-and-recovery.md) before using executable snippets. It covers exact scope rules, limitations, backups, cache behavior, file/CLI recovery and the explicit `WPSSTUDIO_DELETE_DATA` uninstall option. Ordinary uninstall preserves data. [SECURITY.md](SECURITY.md) explains private reporting.

## Validation

The 0.1.1 acceptance pass fixed frontend/REST request detection, mobile overflow under WordPress 6.4 styles and fatal-error snapshot loss during older core error rendering. The corrected source passes all GitHub CI jobs across PHP 8.2–8.4, including browser/recovery tests on WordPress 6.4 and 7.1.4. Versioned ZIP replacement preserves signed snippet records.

Real-WordPress integration, browser, policy, syntax, WordPress coding/security standards, compatibility and dependency checks are included. [Validation results](docs/validation.md) record the environments actually tested and remaining limits. A configured CI matrix is not a claim that every job has passed.

The [development guide](docs/development.md) explains how to reproduce checks. Tests are destructive to disposable fixtures and must never run on production/client databases. Neither passing tests nor this implementation is an independent security audit.

## Six-section roadmap

| Section | Focus | Status / scope |
| --- | --- | --- |
| 1. Core Snippets | First development build | Core types, metadata, scopes, priority, activation, compact dashboard and recovery implemented in 0.1.1; staging/public-release review pending |
| 2. Snippet Management | Organization | Search, tags, duplicate, bulk actions, import/export deferred |
| 3. Placement Controls | Content placement | Header/footer fields, page-level and before/after-content placement deferred |
| 4. Conditions & Integrations | Targeting | Device/user/page conditions, WooCommerce, Elementor and Gutenberg integrations deferred |
| 5. Safety & Agency Tools | Management | Advanced logs, revisions, backups, permissions and statistics deferred; basic security/recovery is already mandatory in section 1 |
| 6. Advanced Platform | Scale | Multisite, generators, performance insights, private library and optional sync deferred |

No paid features, Pro upsells, cloud library or tracking are included.

## Version control, distribution and customer updates

GitHub is the source of truth. Development uses a feature branch, checked changes, updated documentation/changelog and a pull request. The build script creates an allow-listed installation ZIP and checksum without development packages, tests or credentials. CI checks do not deploy or publish.

For each public version: finish functional/security review, verify staging and backup/recovery, record validation and changelog, choose the distribution channel, tag/release the tested package, then monitor feedback. Installation ZIP replacement must preserve snippet data.

**Customer auto-updates are not implemented.** Pushing code or creating a GitHub release does not by itself deliver updates in WordPress dashboards. An independent update service, transport/integrity policy and release workflow need their own implementation and testing.

The [WordPress.org Plugin Developer FAQ](https://developer.wordpress.org/plugins/wordpress-org/plugin-developer-faq/) currently says new arbitrary-code insertion/execution plugins, including PHP/JavaScript editors, are not accepted. The current product scope is therefore not assumed eligible for a new directory listing. Distribution or a revised product scope remains an owner decision; approval is not guaranteed.

## Project tracking

| Item | Status |
| --- | --- |
| Name, slug, text domain and GPL license | Complete |
| GitHub repository | [anirban0184/wp-snippet-studio](https://github.com/anirban0184/wp-snippet-studio) |
| 0.1.1 core code and compact dashboard | Implemented |
| Database, access controls and recovery | Implemented; checks documented |
| Build, checks and documentation | Included; results in validation log |
| glowingcoders.com deployment | Pending compatible staging and verified recovery |
| Stable 1.0.0 release | Pending staging review and distribution decision |
| WordPress.org listing / customer updater | Not configured |

See [CHANGELOG.md](CHANGELOG.md) for version changes.

## License

WP Snippet Studio is licensed under [GNU GPL version 2 or later](LICENSE).
