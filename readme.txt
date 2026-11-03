=== WP Snippet Studio ===
Contributors: glowingcoders
Tags: snippets, custom code, css, javascript, php
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: trunk
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage trusted PHP, CSS, JavaScript, HTML and reusable text in a compact dashboard.

== Description ==

Development build 0.1.1. Test on staging with a database backup and file access before considering production use. No WordPress.org listing or automatic update service is configured.

* No artificial limit on snippet count. Paginated, compact management table with type navigation.
* PHP, CSS, JavaScript, filtered HTML and escaped reusable text.
* Titles, descriptions, priority, individual activation and deactivation.
* PHP scopes: everywhere, frontend, admin, or one manual attempt per changed source.
* Frontend CSS in the head and JavaScript in the footer.
* Active HTML/text shortcodes: [wpsstudio id="12"].
* New snippets and edits save inactive. Syntax validation before PHP save/activation.
* Safe Mode, installation-bound integrity signatures and stale-edit protection.
* Single-site WordPress only. All included features are free.

PHP and JavaScript are executable code. Trusted users need manage_options and edit_plugins; WordPress file-edit/modification restrictions are respected. PHP is not sandboxed. Login and this plugin's management pages bypass automatic PHP for recovery.

Snippets are stored in the existing site's database table {prefix}wpsstudio_snippets. Deactivation, upgrades and ordinary uninstall preserve data. See docs/security-and-recovery.md before using executable code.

== Installation ==

1. Use the generated wp-snippet-studio-0.1.1.zip on a single-site staging installation with WordPress 6.4+ and PHP 8.2+.
2. Upload using Plugins > Add Plugin > Upload Plugin and activate using a trusted administrator account.
3. Open Snippet Studio > Add New, save inactive, review and activate from All Snippets.
4. Use only code you trust. For PHP, omit opening/closing PHP tags. For CSS/JS, omit style/script tags.

== Frequently Asked Questions ==

= How can I recover? =
Open /wp-admin/admin.php?page=wpsstudio directly. Login and the management pages bypass automatic PHP. Enable Safe Mode before reviewing code. If needed, add define( 'WPSSTUDIO_SAFE_MODE', true ); above the stop-editing line in wp-config.php. File access is the fallback if another component blocks the dashboard.

= Does saving an active snippet update it live? =
Saving deactivates it. Activate it separately after review. Changes cannot reverse database writes or other effects of code that already ran.

= Can a PHP snippet run exactly once with rollback? =
No. A manual attempt is claimed atomically before execution. Repeated/concurrent submissions are blocked for the same attempted source. A fatal or interrupted attempt is still recorded; side effects are not rolled back. Changing source allows a new attempt.

= Can I update from GitHub automatically? =
No updater is included in 0.1.1. GitHub source changes do not automatically update installed plugins. Distribution and customer updates require a separate release decision and implementation.

== Changelog ==

= 0.1.1 — development =
* Fix frontend-only PHP on normal home/index.php paths when WordPress uses query-based REST URLs, and prevent early-loading failures with pretty permalinks.
* Extend acceptance, browser, pagination and concurrent manual-run regression checks.

= 0.1.0 — development =
* Initial local snippet management, compact dashboard, scopes, priority, reusable content and recovery protections.
