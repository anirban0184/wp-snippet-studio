=== Coder Snippet Studio ===
Contributors: glowingcoders
Tags: reusable content, html, text, shortcodes, snippets
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create reusable HTML and plain text with inactive-first saves, separate activation, shortcodes and a site-wide output pause.

== Description ==

Coder Snippet Studio helps administrators maintain repeated content in one place. Use it for contact details, opening hours, notices, calls to action and other reusable HTML or plain text.

Each snippet is saved inactive. Review its filtered content, activate it, and place its shortcode wherever WordPress processes shortcodes. Editing an active snippet makes it inactive again until you review and reactivate it.

* HTML and plain text with titles and descriptions.
* No artificial limit on the number of snippets. Each snippet supports up to 1 MB of content.
* Individual activation and deactivation.
* A compact Name / Type / Shortcode / Status / Actions table with HTML/text filters and pagination.
* Copyable shortcodes such as [wpsstudio id="12"].
* Basic HTML filtered on save and output using an explicit allow-list.
* Plain text escaped on output with line breaks preserved.
* Pause all output without losing active settings.
* Administrator permissions, action-specific nonces, content approval signatures and stale-edit protection.
* Data retained across deactivation, replacement and ordinary uninstall.

The editor accepts content only. It does not execute PHP or user-written JavaScript, inject CSS, expand nested shortcodes, download executable code or install other software. All included features are free. There are no accounts, subscriptions, remote services or telemetry.

HTML supports paragraphs, basic formatting, headings, lists, links, images and tables. Scripts, style tags/attributes, forms, iframes, SVG and event handlers are removed. Review the saved result before activation. Content can include administrator-selected external image URLs; browsers load those images like ordinary website content.

Active content is public wherever its shortcode is placed. This is not a private-content access system. The plugin currently supports single-site WordPress.

Source and development tools: https://github.com/anirban0184/wp-snippet-studio

== Installation ==

1. Install Coder Snippet Studio and activate it on a single-site installation using WordPress 6.4+ and PHP 8.2+.
2. Open Snippet Studio > Add New.
3. Add a title, choose HTML or plain text, enter content and save inactive.
4. Review the saved content, then activate it from All Snippets.
5. Copy its shortcode into a Shortcode block or another location where WordPress processes shortcodes.

== Frequently Asked Questions ==

= Can this run PHP, JavaScript or CSS snippets? =
No. This edition manages HTML and plain text only. Content that looks like PHP or a nested shortcode is never interpreted as code.

= What happens when I edit active content? =
Saving deactivates it. Review the saved result and activate it again from All Snippets. An inactive or missing snippet renders an empty string.

= How does Pause all output work? =
It suppresses all snippet shortcodes on new requests while preserving active settings. Resume output when ready. Previously cached pages may still contain earlier output until the site's caches are cleared.

= What happens to development-build records? =
Upgrading from the earlier development build preserves its database rows and content, but disables all existing approvals. HTML/text records require review and an inactive save before activation. Unsupported types remain stored but cannot be edited, activated or rendered by this edition.

= Will uninstalling remove content? =
Ordinary uninstall preserves the custom table and settings. To opt into permanent removal, back up first and define WPSSTUDIO_DELETE_DATA as true in wp-config.php before uninstalling. This also removes preserved development-build records. Remove that constant afterward.

= Where is content stored? =
In the existing WordPress database, in the table {prefix}wpsstudio_snippets. There is no separate database or external storage service.

== Changelog ==

= 1.0.0 =
* Initial HTML/plain-text edition with inactive saves, individual activation, reusable shortcodes and output pause.
* Add explicit HTML filtering, escaped text, administrator-only management, content approval signatures and revision checks.
* Preserve legacy development records without executing unsupported types.
