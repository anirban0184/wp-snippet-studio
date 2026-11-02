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
- Follow WordPress coding and security best practices.
- Keep the plugin free while it is developed and released.
- Build a stable base before adding advanced features.

## Planned first public release

The first public release is planned to focus on the essential snippet-management experience. The distribution method has not yet been decided.

- Create unlimited snippets.
- Support PHP, CSS, JavaScript, HTML, and reusable text content.
- Add a title and description to every snippet.
- Activate or deactivate each snippet individually.
- Set the execution priority for eligible snippets.
- Edit and delete snippets.
- Choose where PHP snippets run:
  - Everywhere
  - Frontend only
  - WordPress admin only
  - Run once manually
- Load active CSS and JavaScript snippets sitewide on the frontend.
- Use shortcodes for reusable HTML and text content.
- Store snippets independently from the active theme.
- Provide a recovery mode that bypasses snippet execution when troubleshooting.
- Apply strict capability checks, nonce validation, appropriate input validation, and context-specific output escaping.

> **Note:** WP Snippet Studio is under active development. Features listed above are planned and will be marked as available only after they are built, tested, and released.

## Planned data and execution design

Snippets will be stored in a dedicated table inside the site's existing WordPress database: `$wpdb->prefix . 'wpsstudio_snippets'`. For a site using the `wp_` prefix, its name will be `wp_wpsstudio_snippets`. This is a custom table, not a separate database.

The planned fields include an ID, title, description, code, type, status, execution scope, priority, and creation/update timestamps. A separate schema version will allow the table to be migrated when the plugin is updated. Snippets should remain intact when the plugin is deactivated or upgraded.

### Snippet priority

- Priority is planned for the first release for automatically executed PHP, CSS, and JavaScript snippets.
- The default is `10`; lower numbers are processed first among applicable snippets of the same type and execution context.
- For matching priorities, the snippet with the lower ID is processed first.
- A PHP snippet's priority controls the order in which this plugin loads it; it does not override the priority supplied to WordPress hooks inside the snippet. Theme, plugin, and WordPress hook timing also affect the result.
- HTML and text shortcodes appear where they are placed, so snippet priority does not determine their position. Manually run PHP snippets execute only on explicit request.

## Product roadmap

| Section | Focus | Planned capabilities |
| --- | --- | --- |
| 1. Core Snippets | First public release | Snippet types, title/description, active/inactive toggle, execution priority, PHP execution locations, basic editor, recovery mode |
| 2. Snippet Management | Organization | Search, tags, filters, duplicate, bulk actions, import/export |
| 3. Placement Controls | Content output | Additional page-level placement, before/after content, selected-page rules |
| 4. Conditions & Integrations | Smart targeting | Device, user-role, page-type, WooCommerce, Elementor, and Gutenberg conditions |
| 5. Safety & Agency Tools | Reliable management | Enhanced recovery, error logs, revisions, permissions, statistics, backups |
| 6. Advanced Platform | Scalable workflows | Multisite, generators, performance insights, private library, and optional sync tools |

## Development standards

WP Snippet Studio will follow these principles throughout development:

- GPL-compatible code and dependencies only.
- WordPress Coding Standards where applicable.
- No theme-file editing required.
- Do not fetch executable snippets from external sources in the first release; locally entered executable snippets require trusted access.
- No account requirement for core local features.
- No third-party tracking scripts.
- Security-first handling of form submissions and stored data.
- Backward-compatible updates whenever possible.
- Clear release notes and changelog entries for every public version.

## Security requirements for the first release

Security is a mandatory release criterion. Known exploitable vulnerabilities must be resolved before a release is published. The following requirements are planned and must be implemented and verified; they are not claims that the safeguards already exist.

- Check the necessary user capabilities on every snippet-management action and every entry point. Login or a valid nonce alone is not authorization. Raw executable snippet code must not be exposed through unauthorized requests.
- Restrict executable-code management to trusted users and respect WordPress file-editing and file-modification restrictions. Multisite support remains deferred until its separate permission model is explicitly implemented and tested.
- Use authenticated, nonce-protected state-changing requests for saving, activating, deactivating, deleting, and manually running snippets.
- Validate snippet types, execution scopes, identifiers, and numeric priorities. Sanitize metadata appropriately; preserve authorized source code and escape it when displaying it in the editor. Define and test explicit output rules for HTML and text snippets.
- Use protected WordPress database APIs and prepared queries where needed. Allow-list any dynamic query fields and sorting options.
- Save new snippets as inactive. Require PHP syntax validation before activation and do not proceed when required validation fails or cannot be completed.
- Include a documented recovery mode in the first release, and verify that it bypasses snippets even when a snippet prevents normal dashboard access.
- Keep code, credentials, and sensitive error details out of public responses. Any diagnostic storage must be access-controlled.
- Do not provide public arbitrary-code execution endpoints or fetch executable snippets from remote sources in the first release.
- Test unauthorized access, forged state-changing requests, SQL injection, stored script injection in management screens, shortcode output, invalid PHP, and recovery behavior. Check that upgrades preserve existing snippet data.
- Perform manual security review alongside automated checks before releases. Review dependencies and keep credentials out of Git; any future publishing credentials must use protected secret storage.

PHP snippets execute with the website's PHP permissions. Syntax checks, error handling, and recovery mode do not sandbox untrusted code or guarantee protection from every failure. The plugin must communicate this limitation accurately and require trusted code.

Reference: [WordPress capability checks](https://developer.wordpress.org/plugins/security/checking-user-capabilities/) and [WordPress hardening guidance](https://developer.wordpress.org/advanced-administration/security/hardening/).

## Release workflow

1. Build each feature in a dedicated, testable development task.
2. Run functional and security checks plus manual review, and resolve known exploitable vulnerabilities before publishing a release.
3. Record changes in the project documentation and changelog.
4. Tag a versioned GitHub release.
5. Publish the tested release through the distribution method chosen for the product.
6. Monitor feedback and fix issues through controlled updates.

GitHub is the primary source repository. GitHub releases alone do not provide WordPress dashboard updates: an independently distributed plugin would need an update mechanism that is separately implemented and tested.

### WordPress.org distribution status

The [WordPress.org Plugin Developer FAQ](https://developer.wordpress.org/plugins/wordpress-org/plugin-developer-faq/) currently states that new plugins allowing arbitrary code insertion or execution, including PHP and JavaScript editors, are not accepted. The proposed full feature set therefore should not be presented as eligible for a new WordPress.org listing. Independent distribution or an alternative product scope requires an owner decision; approval of any revised submission is not guaranteed.

## Current project status

| Item | Status |
| --- | --- |
| Plugin name selected | Complete |
| WordPress.org account created | Complete |
| GitHub repository created | Complete |
| GPL-2.0 license added | Complete |
| Version 0.1.0 architecture | Next |
| Core snippet management | Planned |
| Distribution and updates | Decision pending |

## Contributing

The project is currently maintained by Glowing Coders. Contribution guidelines, issue templates, and development setup instructions will be added as the plugin enters active development.

## License

WP Snippet Studio is licensed under the [GNU General Public License v2.0](LICENSE).
