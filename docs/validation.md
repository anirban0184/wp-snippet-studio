# Validation log

## 0.1.1 — controlled live smoke test, 2026-11-03

Installed and activated the verified 0.1.1 package on **glowingcoders.com** at the owner's explicit request, after WordPress Site Health confirmed the upgrade to **PHP 8.3.36**. The site uses single-site **WordPress 7.1.4**, **LiteSpeed**, **Hello Elementor**, **Elementor 4.2.2** and **Elementor Pro 4.1.3**. The owner requested proceeding without a backup; no backup/restore or hosting/file recovery was verified.

Package provenance: runtime source at commit `97ef58d89fae02798b06e4a687a87d2a5a8da566`; uploaded `wp-snippet-studio-0.1.1.zip` SHA-256 **`97688354c02ec7bc5d98e1597ab651e2f1a765dc090be81b3f85490b7a1e4364`**. This documentation update does not change runtime source or plugin version. Later documentation-only rebuilds may produce a different archive checksum.

| Live check | Observed result |
| --- | --- |
| ZIP installation and activation | WordPress reported successful installation and activation; plugin list and dashboard showed 0.1.1. |
| Metadata and inactive saves | Saved PHP, CSS, JavaScript, HTML and text with titles/descriptions. New records were inactive. Editing the active CSS description deactivated it and removed its frontend output. |
| Individual controls and type tabs | Separate activation/deactivation worked. All/PHP/CSS/JS/HTML/Text showed the expected 8/4/1/1/1/1 records. Type, run location, priority, status and actions stayed in one desktop table row. |
| PHP run locations | Frontend-only metadata appeared on the Elementor home page and stayed off the admin dashboard. Admin-only metadata appeared on the admin dashboard and stayed off the frontend. Everywhere metadata appeared on both. The plugin management route contained none of these automatic PHP markers. |
| Priority | With both hook priorities fixed at 10, changing the everywhere snippet priority from 20 to 5 reversed its frontend metadata order relative to the priority-10 snippet. Its active status was preserved; priority was then restored to 20. |
| CSS and JavaScript | A harmless CSS custom property was present in computed frontend styles and a head style element. A footer script set the expected DOM attribute. Both were absent on the admin dashboard. |
| HTML and text shortcodes | An unpublished draft preview rendered the HTML strong element after stripping a harmless event-handler attribute. Text escaped literal HTML and preserved a line break and nested shortcode text. Direct PHP and missing-ID shortcodes produced no body output. |
| Safe Mode | Paused PHP metadata, CSS, JavaScript and both content shortcodes while preserving all seven automatic active flags. The manual button was disabled. Turning Safe Mode off restored normal output and the manual control. |
| Run once | A side-effect-free arithmetic check completed while remaining inactive. A second attempt was rejected with the expected already-attempted message. |
| Plugin reactivation | Deactivation/reactivation succeeded. All eight records, types/scopes, priorities and inactive states remained available. |
| Cleanup and final site check | Seven automatic snippets were disabled; the manual record remained inactive. Draft page 633 was never published and was moved to recoverable Trash. The visual editor was restored. Safe Mode was off, 0.1.1 remained active, and the site root, Elementor home and admin dashboard loaded with no QA metadata/CSS/JavaScript markers. |

The retained records are named **Live QA — PHP Frontend / PHP Everywhere / PHP Admin / PHP Run Once / CSS / JavaScript / HTML / Text** (IDs 1–8). They remain available for review; no individual snippet was permanently deleted. The manual run only checked arithmetic and did not write site content or options.

These were controlled browser checks through the normal administrator UI and an authenticated unpublished preview. The destructive test suite, intentional fatal errors, table-loss simulation, role changes, concurrent workers and uninstall experiments were not run on production. The earlier isolated suites remain the evidence for those cases. This is not an independent security audit, a mobile-device check on this host, a full Elementor integration certification, a backup/restore verification or a public release. No new runtime defect was reproduced during this live pass.

## 0.1.1 — acceptance test pass, 2026-11-03

The acceptance pass reproduced two problems in the original frontend request detector: ordinary home/index.php paths could be mistaken for REST paths, and pretty permalinks could cause rest_url() to access WordPress's rewrite object before core initialized it. The fix checks query-based REST explicitly and matches core's pretty REST prefixes without that late-initialized object.

Regression checks now cover plain, pretty and index.php permalinks; their REST endpoints; saving/reactivating an active JavaScript snippet; pagination through 61 persisted records; Unicode and quoted source across all five types; manual receipts after metadata/source edits; unsigned activation; and pending schema migration behavior. A real two-process test uses independent database connections and an atomic counter to verify that simultaneous manual attempts produce one accepted execution and one blocked attempt.

The first expanded WordPress 6.4 browser run also detected a mobile overflow regression. Absolutely positioned accessibility labels escaped the table's scroll clipping because it did not establish their containing block. Setting the scroller to position:relative confines them to the table. The strict whole-document width assertion is retained; this is a layout fix, not a relaxed test.

The baseline fatal-error test then exposed secondary notices from WordPress 6.4's error rendering replacing error_get_last(). The runner now snapshots the original snippet fatal through wp_php_error_message before rendering. Both the output filter and shutdown recovery use that snapshot; the HTTP 500/private-output and Safe Mode assertions remain strict.

The [corrected-source CI run](https://github.com/anirban0184/wp-snippet-studio/actions/runs/41590320154) passed all four jobs. Integration, acceptance, concurrency and policy checks passed on PHP 8.2/WordPress 6.4, PHP 8.3/WordPress 7.1.4 and PHP 8.4/WordPress 7.1.4. Both browser combinations (8.2/6.4 and 8.3/7.1.4) also passed.

| Test group | Result |
| --- | --- |
| Existing integration suite | 96 checks passed per tested environment |
| Additional acceptance suite | 30 checks passed: all-type edits, Unicode/source preservation, manual receipts, unsigned activation, pagination and schema pause |
| Real concurrent execution | Two workers with separate DB connections: one accepted execution, one blocked attempt, one stored effect; passed locally and in all three CI combinations |
| Full browser suite | 56 checks passed on WordPress 6.4 and 7.1.4, including strict document-overflow assertions down to 320px and private-output/Safe Mode checks after immediate and deferred fatal errors |
| Pagination browser suite | 23 checks passed across 61 records, including all/text filters, page boundaries, stable IDs and malformed read-only parameters |
| Request-scope routes | 28 plain, 40 pretty and 40 index-permalink checks passed on both WordPress versions; REST returns JSON and excludes frontend-only PHP |
| ZIP/upgrade verification | Independently installed 0.1.1 tested on both local WordPress versions; verified 0.1.0-to-0.1.1 replacement and reactivation preserve the exact signed active record |
| Quality gates | PHP syntax, WordPress standards/security/compatibility, JavaScript syntax, Composer validation and Composer/npm advisory checks passed |
| Initial production check | Earlier read-only Site Health inspection on 2026-11-03 found production / WordPress 7.1.4 / PHP 8.1.34 and did not install the plugin. The subsequent PHP upgrade and controlled live pass are recorded above. |

The local packaged tests used PHP 8.3.6/MariaDB 10.11.14; CI supplies the PHP 8.2/8.3/8.4 matrix. The generic suites alone do not establish compatibility with a specific Elementor/theme/cache stack. The controlled site-specific pass above adds bounded evidence for the observed stack, with broader integration/caching checks and backup/recovery still unverified. No independent audit, stable release, directory submission or customer updater is claimed.

## 0.1.0 — initial development validation

Local validation performed on 2026-11-03. Use the latest GitHub Actions results to assess any later revision. This is internal implementation review and automated validation, not an independent security audit or a production-readiness certification.

The [GitHub Actions run for the implemented source](https://github.com/anirban0184/wp-snippet-studio/actions/runs/41525992712) completed successfully, including all four jobs. Its pull-request run also passed. CI verified **96 integration checks plus all three isolated policies** on WordPress 6.4 / PHP 8.2.35, WordPress 7.1.4 / PHP 8.3.36 and WordPress 7.1.4 / PHP 8.4.27. The PHP 8.3 job also passed **51 browser checks** in Chrome Headless Shell 151.0.7922.34. These results cover the core source before the final documentation/CI-trigger housekeeping commit; inspect the latest PR checks for that revision.

| Check | Environment / result |
| --- | --- |
| Core integration | WordPress 7.1.4, PHP 8.3.6, MariaDB 10.11.14: **96 checks passed** |
| Compatibility baseline | WordPress 6.4, PHP 8.3.6, MariaDB 10.11.14: **96 checks passed** |
| Browser/HTTP | WordPress 7.1.4 and Chromium 133: **51 checks passed** |
| Responsive geometry | 1440, 1024, 782, 736, 480, 360 and 320px: six columns remain on one row, no document overflow |
| Isolated policy checks | File editing disabled, file modifications disabled, tokenizer disabled: **passed**; file-based Safe Mode also verified |
| WordPress standards/security | WordPress-Extra, explicit text domain and PHPCompatibilityWP for PHP 8.2+: **zero errors/warnings** |
| Development dependencies | Locked Composer audit: no reported advisories; npm audit: zero reported vulnerabilities |
| PHP/JS parsing | Shipped PHP syntax and admin/browser JavaScript syntax checked |
| Installation package | Built ZIP installed independently: **96 integration checks passed**; reproducible archive and replacement/deactivation/reactivation/default-uninstall data retention **passed** |
| Live deployment | **Not performed**; glowingcoders.com was production/PHP 8.1 |
| Independent audit / Elementor compatibility | **Not performed** |

Integration covers permissions (anonymous/content roles/incomplete capabilities), file-modification policy, strict IDs/priorities, invalid PHP/tag breakouts, inactive saves, activation, deterministic priority order, stale update/delete rejection, migration preservation, source signatures/tampering, HTML filtering, text escaping, no public PHP shortcode, Safe Mode for every output type, manual replay/claims, exception quarantine and source-private database failure handling. A missing table is tested on the disposable database and restored afterward.

Browser checks exercise actual logged-in/anonymous/editor HTTP requests and missing nonces, real editor saves/deletes, status switches, type navigation, inline priority, frontend/admin/REST scope classification, frontend CSS/JS execution, public reusable content, Safe Mode, manual replay, the login recovery route and both immediate/deferred fatal recovery. Fatal assertions check a failed HTTP request, absence of private test error/output markers and a usable management route afterward.

Manual source review covered capability/nonces on every management path, source escaping and public-output boundaries, prepared queries/identifiers, inactive execution gates, signature approval, revision/claim updates, source-bearing database errors and recoverability. The fatal-output test uncovered WordPress flushing buffers before plugin shutdown; the final implementation filters detected-fatal output at flush time and retains a fatal snapshot for recovery. No production code, database or hosting configuration was changed.

## Remaining release work

- Keep the GitHub CI matrix passing for future source changes and inspect actual results.
- Test on a compatible staging copy with the intended theme, Elementor/WooCommerce stack, cache, security and hosting configuration. Generic WordPress tests do not establish compatibility with every plugin or persistent cache.
- Verify a real backup/restore and hosting/file recovery route for future production work; the owner-authorized live pass above did not verify these.
- Decide the distribution channel and implement/test customer update delivery separately. There is no updater, stable release, WordPress.org submission or approval in this build.
- Recheck source, dependencies and security findings before a stable release. PHP remains privileged, fatal recovery remains best effort and manual execution has no rollback guarantee.
