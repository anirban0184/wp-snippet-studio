# WP Snippet Studio

> A free, lightweight WordPress code snippet manager for PHP, CSS, JavaScript, HTML, and reusable content.

**Status:** Early development  
**License:** GPL-2.0  
**Website:** [Glowing Coders](https://glowingcoders.com/)

WP Snippet Studio is being built as a focused, user-friendly way to add and manage custom WordPress code without editing a theme's `functions.php` file. Snippets will remain independent from theme updates and theme changes.

## Project goals

- Keep custom code organized in one dependable place.
- Make it simple to activate, deactivate, edit, and manage snippets.
- Support practical code types used in real WordPress projects.
- Follow WordPress coding, security, and WordPress.org Plugin Directory standards.
- Keep the plugin free while it is developed and released.
- Build a stable base before adding advanced features.

## Planned first public release

The first WordPress.org release will focus on the essential snippet-management experience:

- Create unlimited snippets.
- Support PHP, CSS, JavaScript, HTML, and reusable text content.
- Add a title and description to every snippet.
- Activate or deactivate each snippet individually.
- Edit and delete snippets.
- Choose where PHP snippets run:
  - Everywhere
  - Frontend only
  - WordPress admin only
  - Run once manually
- Load active CSS and JavaScript snippets sitewide on the frontend.
- Use shortcodes for reusable HTML and text content.
- Store snippets independently from the active theme.
- Apply administrator-only access, nonce validation, capability checks, sanitization, validation, and escaped output.

> **Note:** WP Snippet Studio is under active development. Features listed above are planned and will be marked as available only after they are built, tested, and released.

## Product roadmap

| Section | Focus | Planned capabilities |
| --- | --- | --- |
| 1. Core Snippets | First public release | Snippet types, title/description, active/inactive toggle, PHP execution locations, basic editor |
| 2. Snippet Management | Organization | Search, tags, filters, duplicate, bulk actions, priority, import/export |
| 3. Placement Controls | Content output | Shortcodes, page-level placement, before/after content, selected-page rules |
| 4. Conditions & Integrations | Smart targeting | Device, user-role, page-type, WooCommerce, Elementor, and Gutenberg conditions |
| 5. Safety & Agency Tools | Reliable management | Safe Mode, error logs, revisions, permissions, statistics, backups |
| 6. Advanced Platform | Scalable workflows | Multisite, generators, performance insights, private library, and optional sync tools |

## Development standards

WP Snippet Studio will follow these principles throughout development:

- GPL-compatible code and dependencies only.
- WordPress Coding Standards where applicable.
- No theme-file editing required.
- No remote code execution.
- No account requirement for core local features.
- No third-party tracking scripts.
- Security-first handling of form submissions and stored data.
- Backward-compatible updates whenever possible.
- Clear release notes and changelog entries for every public version.

## Release workflow

1. Build each feature in a dedicated, testable development task.
2. Test functionality and edge cases before merging it into the main branch.
3. Record changes in the project documentation and changelog.
4. Tag a versioned GitHub release.
5. Submit tested public releases to WordPress.org after approval.
6. Monitor feedback and fix issues through controlled updates.

GitHub is the primary source repository. The WordPress.org SVN repository will be used later for official Plugin Directory releases and customer updates.

## Current project status

| Item | Status |
| --- | --- |
| Plugin name selected | Complete |
| WordPress.org account created | Complete |
| GitHub repository created | Complete |
| GPL-2.0 license added | Complete |
| Version 0.1.0 architecture | Next |
| Core snippet management | Planned |
| WordPress.org submission | Planned |

## Contributing

The project is currently maintained by Glowing Coders. Contribution guidelines, issue templates, and development setup instructions will be added as the plugin enters active development.

## License

WP Snippet Studio is licensed under the [GNU General Public License v2.0](LICENSE).
