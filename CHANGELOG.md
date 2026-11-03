# Changelog

## 0.1.1 — development build

- Fix frontend-only PHP being skipped on ordinary home/index.php paths when WordPress uses query-based REST URLs. Avoid calling rest_url() before WordPress initializes its rewrite object, which could crash requests with pretty permalinks. Actual REST requests remain excluded.
- Add regression coverage for index.php requests and editing/reactivating active JavaScript snippets.
- Extend acceptance checks for Unicode/source round trips, all-type edits, manual source changes, unsigned activation, pagination and pending schema migrations.
- Add a real two-process manual-run race check with independent database connections.
- Keep absolutely positioned accessibility labels inside the table scroller, fixing mobile document overflow under WordPress 6.4 styles.
- Preserve the original fatal-error snapshot before WordPress error rendering can replace it with secondary notices, keeping output suppression and Safe Mode recovery effective on WordPress 6.4.
- Record controlled live checks on glowingcoders.com after its PHP 8.3.36 upgrade, including all snippet types/scopes, priority, Safe Mode, manual replay protection and reactivation retention. QA snippets were left inactive; no runtime changes were needed for this pass.

## 0.1.0 — development build

- Add PHP, CSS, JavaScript, filtered HTML and escaped text snippet management in a dedicated, versioned database table.
- Add compact type tabs and a horizontal Name / Type / Run location / Priority / Status / Actions table, including on smaller screens.
- Add native WordPress code editor, titles, descriptions and inactive saves for all new/edited snippets.
- Add activation/deactivation, priority ordering, four PHP scopes and atomically claimed manual attempts.
- Add frontend CSS/head and JavaScript/footer loading and active HTML/text shortcodes.
- Add capability and nonce checks, source integrity signatures, optimistic concurrency and generic failure records.
- Add Safe Mode, protected login/management recovery routes, runtime exception quarantine and best-effort fatal recovery.
- Preserve data on deactivation, upgrade and ordinary uninstall; require an explicit configuration constant for deletion during uninstall.
- Add functional/security integration checks, browser checks, code standards checks and an installation ZIP builder.

Version 0.1.1 is installed on glowingcoders.com for owner-authorized testing. This development build has not been submitted to WordPress.org or published as a stable customer release. There is no automatic updater yet.
