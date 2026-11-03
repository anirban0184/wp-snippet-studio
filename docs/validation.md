# Validation log

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
| Production check | Read-only Site Health inspection on 2026-11-03 confirmed glowingcoders.com is production / WordPress 7.1.4 / PHP 8.1.34. No plugin installation or activation was performed there. |

The local packaged tests used PHP 8.3.6/MariaDB 10.11.14; CI supplies the PHP 8.2/8.3/8.4 matrix. The report/checks do not establish compatibility with the site's specific Elementor/theme/cache stack. A PHP 8.2+ staging copy with verified backup/recovery is still needed before that site-specific test. No independent audit, stable release, directory submission or customer updater is claimed.

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
- Verify a real backup/restore and hosting/file recovery route before any production installation.
- Decide the distribution channel and implement/test customer update delivery separately. There is no updater, stable release, WordPress.org submission or approval in this build.
- Recheck source, dependencies and security findings before a stable release. PHP remains privileged, fatal recovery remains best effort and manual execution has no rollback guarantee.
