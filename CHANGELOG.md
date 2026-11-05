# Changelog

## 1.0.0 — HTML/text submission candidate

- Rename to Coder Snippet Studio with the coder-snippet-studio folder, main file and text domain; retain existing database identifiers and shortcode for content compatibility.
- Replace the full development runtime with reusable HTML and plain text only. Remove the executable runner, PHP/JavaScript/CSS types, run locations, priority controls and manual execution actions.
- Filter HTML using an explicit allow-list and escape text with preserved line breaks. Never expand nested shortcodes.
- Keep inactive saves, separate activation, compact table controls, type filters, pagination and the WordPress editor fallback. Add directly copyable shortcodes.
- Add a site-wide output pause that preserves active settings.
- Enforce administrator permissions, action-specific nonces, signed content approvals and stale revision checks.
- Preserve all development-build database records, disable old approvals, and make unsupported types inaccessible and inert.
- Build a minimal installation archive with no development tools, credentials, logs or screenshots.

This is a new submission candidate, not a claim of WordPress.org approval. The previous 0.1.1 build and its history are preserved on the repository's `legacy/full-snippets-0.1.1` branch. The live website has not been upgraded to this edition during development.
