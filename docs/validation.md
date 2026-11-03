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
- A workflow follow-up isolates concurrency by commit. Final overall CI status must be checked on the current pull-request head before merge.

The production site remains on 0.1.1. No new-edition installation or production test was performed in this follow-up. The original full development build remains archived on `legacy/full-snippets-0.1.1`.

**WordPress.org submission: not uploaded. Approval: pending.** Browser access is unavailable in the resumed session. The complete candidate and submission instructions are preserved in GitHub.
