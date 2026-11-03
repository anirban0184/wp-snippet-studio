# WP Snippet Studio

Reusable **HTML and plain text** for single-site WordPress, maintained by [Glowing Coders](https://glowingcoders.com/).

| Item | Value |
| --- | --- |
| Name / slug / text domain | WP Snippet Studio / `wp-snippet-studio` |
| Version | 1.0.0 submission candidate |
| Requirements | WordPress 6.4+, PHP 8.2+; single site |
| License | GPL-2.0-or-later |
| Directory status | Not submitted or approved yet |

The main product is now the HTML/text edition requested for WordPress.org submission. The earlier full development build is preserved on [`legacy/full-snippets-0.1.1`](https://github.com/anirban0184/wp-snippet-studio/tree/legacy/full-snippets-0.1.1). Its old test results do not certify this new implementation. The existing live 0.1.1 installation has not been replaced during this work.

## Included features

- Unlimited snippet count, titles and descriptions; 1 MB maximum per content body.
- HTML filtered against an explicit allow-list; plain text escaped with line breaks preserved.
- New snippets and edits save inactive. Activation is a separate review step.
- `[wpsstudio id="12"]` displays approved active content; nested shortcodes are not evaluated.
- One horizontal **Name | Type | Shortcode | Status | Actions** table, with copyable shortcodes, type filters, pagination and individual controls.
- Native WordPress HTML/text editor, with a plain textarea fallback when syntax highlighting is disabled.
- Pause all output while keeping active settings, plus the `WPSSTUDIO_SAFE_MODE` configuration override.
- Administrator capability checks, authenticated POSTs, per-action/per-record nonces, content approval signatures and stale revision protection.
- Data retention across plugin deactivation, replacement and ordinary uninstall.

There is no PHP or user-JavaScript execution, CSS injection, manual code runner, arbitrary file editor, remote code loader, external updater, telemetry, paid gate or software installer. This is a lasting content-only scope, not a temporary feature switch for review. Priority and run-location controls are absent because content appears where its shortcode is placed.

## Upgrade behavior and database

The table remains **`$wpdb->prefix . 'wpsstudio_snippets'`**. Compatible legacy columns stay in the additive schema so development records are not destroyed. They do not activate any executable functionality.

Schema version 2 deactivates prior records, clears their approvals and advances revisions without changing original content. Existing HTML/text can be reviewed, saved inactive and activated. Other types are excluded from management and shortcode lookups and remain stored as inert legacy data. No live upgrade is performed automatically by GitHub.

Only administrators with `manage_options` can manage content. HTML/text editing remains available when WordPress file editing is disabled because this edition cannot modify executable files or run user code. See [security and recovery](docs/security-and-recovery.md).

## Build and validation

Run `php scripts/build.php` to produce `build/wp-snippet-studio-1.0.0.zip` and its SHA-256 checksum. The allow-list includes runtime files, `readme.txt` and the license only; development tests, tools, logs, screenshots and credentials are excluded.

The [development guide](docs/development.md) describes isolated test commands and actual package upgrade/uninstall checks. Never run the destructive fixtures on production. The [validation log](docs/validation.md) records actual results; checks being configured is not evidence that they passed.

The [WordPress.org FAQ](https://developer.wordpress.org/plugins/wordpress-org/plugin-developer-faq/) excludes new arbitrary-code execution plugins and permits securely handled HTML. The new edition removes executable features rather than hiding them. WordPress.org still decides eligibility, differentiation, review and approval. This repository does not claim directory approval or configured customer updates before publication.

## Project tracking

| Task | Status |
| --- | --- |
| Same plugin name, slug and text domain | Preserved |
| Earlier full build | Archived on the legacy branch |
| HTML/text edition | Implemented; validation in progress |
| Production installation | Earlier 0.1.1 build; new edition not installed |
| Submission package | Build and verification in progress |
| WordPress.org submission / approval | Pending |

All included features are free. Future development must stay within the directory's rules. See [CHANGELOG.md](CHANGELOG.md) and [SECURITY.md](SECURITY.md).
