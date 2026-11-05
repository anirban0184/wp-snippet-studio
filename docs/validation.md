# Coder Snippet Studio validation — 2026-11-06

Renamed the plugin, main file, archive folder and text domain to `coder-snippet-studio` at the owner’s request after finding a conflicting project name. Local PHP/JavaScript syntax and 15-file package checks passed. The renamed runtime passed standards/dependency checks and all three WordPress/PHP test combinations in run 42088469138. Its official Plugin Check reported “Success: Checks complete. No errors found.” The report parser initially rejected that clean-result wording; commit 739c70d fixes the parser and run 42088807231 validates that change. WordPress.org accepted the submitted ZIP and reported Automated Plugin Scanning: Pass; status Awaiting Review. Submitted ZIP SHA-256: `dbf743e067a1141c913d25e967a0d869fa7445d2f5a19af83845b16e4f3540c6`. Earlier results below apply to the previous identity only.

# Validation log

## 1.0.0 HTML/text submission candidate — 2026-11-03

Verified runtime commit: [51df02c](https://github.com/anirban0184/wp-snippet-studio/commit/51df02cdad194772c0710f2691d533fb37fdaef1). Documentation and workflow-only follow-ups do not change the installation ZIP.

| Check | Observed result |
| --- | --- |
| WordPress standards and PHP compatibility | Passed; shipped PHP source has no lint errors/warnings |
| PHP / JavaScript syntax | Passed |
| Composer dependency audit | No security advisories found |
| npm dependency audit | 0 vulnerabilities |
| Runtime ZIP manifest and source agreement | Passed; 15 runtime/readme/license files; no executable-code gateway |
| Actual 0.1.1 ZIP upgrade | Passed on PHP 8.2 / WordPress 6.4, PHP 8.3 / latest, PHP 8.4 / latest |
| Content and security integration | 68 checks passed on each matrix combination |
| Browser / HTTP / responsive workflows | 46 checks passed on WordPress 6.4 / PHP 8.2 and latest WordPress / PHP 8.3 |
| Deactivation/reactivation, replacement, ordinary uninstall retention | Checks passed in the recorded matrix job logs |
| Official Plugin Check 2.1.0, all default checks | 0 errors; 3 naming warnings retained for review |

The responsive suite checks 1440, 1024, 782, 736, 480, 360 and 320 px, with five table columns on one line and horizontal scrolling confined to the table. It also checks content creation, HTML filtering, text round trips, inactive-first edits, activation, deletion, shortcode output, missing/forged nonces, unauthorized roles, retired execution actions and global output pause. The header now wraps on narrow screens, and pagination waits for the destination page before asserting its row count.

Package: `wp-snippet-studio-1.0.0.zip`

SHA-256: `96982a1f27043ad17755cbbdc4762a7db98cddcfe4f911806ec98c12a7f725f1`

The same digest was produced by independent PHP 8.2 and 8.3 CI builds. Each package entry is checked byte-for-byte against its source. Development dependencies, tests, screenshots, fixtures, credentials and logs are excluded.

### Naming issue requiring reviewer resolution

The official scan reports three `trademarked_term` warnings concerning the `WP` prefix in **WP Snippet Studio** and **wp-snippet-studio**. The owner requested retaining this identity. All three messages remain in `plugin-check.json`; no check was disabled or removed from the default official scan. The report validator fails on every other warning/error.

The upstream [WP-prefix discussion](https://github.com/WordPress/plugin-check/issues/464) treats acronym prefixes separately from full trademarks. It is not approval of this specific plugin name. WordPress.org must determine name eligibility; this project claims neither name clearance nor directory approval.

Direct custom-table reads and writes use prepared identifiers/values or WordPress's typed database methods. Named source annotations explain why those storage operations cannot use a core storage API or stale persistent caches. The uninstall schema annotation applies only to explicit owner opt-in table removal. Broad repository-level database-check exclusions were removed.

### Run records and current status

- [Initial run 41609726193](https://github.com/anirban0184/wp-snippet-studio/actions/runs/41609726193) exposed the uninstall lint warning, WordPress 6.4 header overflow and official scan warnings.
- [Run 41611316863](https://github.com/anirban0184/wp-snippet-studio/actions/runs/41611316863) exposed the remaining narrow heading and pagination test synchronization issues.
- [Run 41611683498](https://github.com/anirban0184/wp-snippet-studio/actions/runs/41611683498) contains the passing steps and exact digest above. The overall run was cancelled while an older commit was being rerun in the same concurrency group; it is not represented as a green overall CI run.
- [Final validation run 41612260002](https://github.com/anirban0184/wp-snippet-studio/actions/runs/41612260002), commit `c48c71291fc768b76aa645e7f5e114e4f71250f9`: **all four jobs passed**, including the unfiltered official scan plus explicit review of its three naming warnings. Concurrency is isolated by commit. Tested latest WordPress was **7.1.4**.
- [Verified candidate artifact](https://github.com/anirban0184/wp-snippet-studio/actions/runs/41612260002/artifacts/13220250275) contains the exact installation ZIP, checksum and complete report. Extract this report bundle once before uploading the inner plugin ZIP.
- Final tracking/documentation-only changes do not alter the tested runtime or package digest. Current-head checks are also required before merge.

The production site remains on 0.1.1. No new-edition installation or production test was performed in this follow-up. The original full development build remains archived on `legacy/full-snippets-0.1.1`.

**WordPress.org submission: not uploaded. Approval: pending.** Browser access is unavailable in the resumed session. The complete candidate and submission instructions are preserved in GitHub.
