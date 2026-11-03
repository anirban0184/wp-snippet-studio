# Security and recovery

## Content boundary

Only HTML and text are accepted. HTML is filtered on save and again on output using a fixed allow-list for formatting, headings, lists, links, images and tables. Script/style tags, inline style attributes, forms, iframes, SVG, event handlers and unsafe URL protocols are excluded. Plain text is escaped with line breaks preserved. Neither type interprets PHP or expands nested shortcodes.

Active content is public wherever its shortcode is placed. Do not store secrets or treat shortcodes as an access-control mechanism. Administrators may intentionally include external image URLs; visitors' browsers then request those images like ordinary website content. The plugin itself has no remote service, telemetry or executable-code download.

## Management and approvals

Management requires `manage_options` on single-site WordPress. Every mutation uses authenticated POST with an action-specific, record-specific WordPress nonce. A nonce is not the capability check. HTML/text authoring does not depend on file-editing permissions and cannot change executable files.

New content and all edits save inactive. Separate activation requires valid, installation-bound HMAC approval over the record's identity, content, type, status and revision. A tampered, unsupported, inactive or unverified record produces no shortcode output. Deactivating tampered content does not approve it. Source review and an inactive save are required before activation.

Revision checks and compare-and-swap updates prevent stale edits, status changes and deletions. Signatures do not encrypt content, protect a fully compromised server or prevent replay of an older database backup. Salt changes require review and new inactive saves. Content-bearing database write failures and read failures are suppressed in this plugin's query layer to avoid public SQL diagnostics and content leakage through database logging.

## Pause and recovery

Use **Pause all output** from the dashboard to suppress all shortcode output while preserving active settings. Existing cached pages and already-running requests cannot be revoked by a toggle. Purge caches when needed.

If dashboard access is unavailable, hosting/file access can set this above the stop-editing line in `wp-config.php`:

```php
define( 'WPSSTUDIO_SAFE_MODE', true );
```

The constant overrides the dashboard. Remove it before resuming output. Deactivation also stops shortcode rendering without deleting data. Keep ordinary production backups and an appropriate hosting recovery route.

## Development-build migration

Schema version 2 preserves the existing `{prefix}wpsstudio_snippets` table and compatibility columns. All older records become inactive, lose their old approval signatures and receive a newer revision. No content or manual-attempt receipt is deleted.

HTML/text records require review and a save under the new allow-list. Other types remain stored but are excluded from management and shortcode queries. There is no flag, dormant execution engine or automatic import that can enable them. Unknown future schema versions fail closed rather than being downgraded. The legacy development runtime is archived in GitHub, not shipped in the installation ZIP.

## Retention

Deactivation, replacement and ordinary uninstall preserve data. Individual Delete is permanent; there is no Trash UI for snippets. Removing or disabling a snippet does not edit posts that contain its shortcode; inactive or missing content returns an empty string while the plugin is active.

Permanent data removal during uninstall requires an explicit configuration opt-in:

```php
define( 'WPSSTUDIO_DELETE_DATA', true );
```

Back up first. This drops the entire plugin table, including preserved legacy records, and removes its options. Remove the constant afterward. Multisite is not supported. Keep public PHP error display disabled and private logs outside the document root.
