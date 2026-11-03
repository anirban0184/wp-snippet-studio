# Validation log

## 1.0.0 HTML/text submission candidate — 2026-11-03

The original PHP/JavaScript execution build is archived on `legacy/full-snippets-0.1.1`. This edition permanently supports filtered HTML and escaped plain text only. Its name, slug, text domain and shortcode stay unchanged.

Initial clean-environment [run 41609726193](https://github.com/anirban0184/wp-snippet-studio/actions/runs/41609726193) verified the real 0.1.1 ZIP upgrade and 68 integration checks on PHP 8.2/WordPress 6.4, PHP 8.3/latest and PHP 8.4/latest. PHP 8.3 also passed 46 browser/HTTP/responsive checks and package replacement, deactivation/reactivation and ordinary-uninstall retention. The initial run exposed an uninstall coding-standard warning, document overflow at 320 px on WordPress 6.4, and official Plugin Check warnings.

Follow-up changes sanitize and unslash read-only request values, prefix template variables, anchor screen-reader feedback to its page container, and explain direct custom-table storage operations in narrowly named source annotations. Installer schema reads and migrations cannot be cached; content reads stay fresh for revocation and revision checks; table deletion requires explicit owner opt-in during uninstall. The broad repository PHPCS storage exclusions were removed.

### Naming issue requiring reviewer resolution

The official Plugin Check 2.1.0 reports `trademarked_term` warnings for the `WP` prefix in **WP Snippet Studio** and **wp-snippet-studio**. The owner explicitly requested retaining this name. The warnings are preserved in the full report, not represented as a clean naming check. WordPress.org must decide name eligibility. No directory submission or approval is claimed.

The report-validation script fails on every other unreviewed warning or error. A successful CI job may still contain these explicitly documented naming warnings; it does not mean approval readiness.

### Final rerun

Pending the follow-up commit's clean-environment run. This document will record the exact result after all checks complete.

The production site remains on 0.1.1. No new-edition installation or production test was performed in this follow-up. No live-site screenshots, credentials, browser fixtures or private logs are included in the submission artifact.
