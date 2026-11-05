# Coder Snippet Studio

Reusable filtered HTML and escaped plain text for single-site WordPress, by [Glowing Coders](https://glowingcoders.com/).

Version 1.0.0 requires WordPress 6.4+ and PHP 8.2+. Licensed GPL-2.0-or-later.

Create HTML or plain-text snippets, save inactive, review and activate them, then place `[wpsstudio id="12"]` in a Shortcode block. Includes type filters, pagination, copyable shortcodes, administrator permissions, nonce checks, revision protection and a global output pause. Editing deactivates a snippet until reviewed again.

Build with `php scripts/build.php`; validate with `php tests/package.php`. The installation ZIP is `build/coder-snippet-studio-1.0.0.zip`. See [validation](docs/validation.md) for actual results.

WordPress.org submission succeeded on November 6, 2026 (Asia/Dhaka). Automated scanning: Pass. Initial slug: `coder-snippet-studio`. Status: Awaiting Review; approval is pending. The former full execution build remains in Git history and on `legacy/full-snippets-0.1.1`. This content edition supports HTML/text only. When moving an existing installation, deactivate the old plugin before activating this edition; its database and shortcode identifiers are retained.
