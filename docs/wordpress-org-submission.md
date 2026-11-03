# WordPress.org submission

## Candidate identity

- Plugin name: **WP Snippet Studio**
- Slug and text domain: **wp-snippet-studio**
- Author: **Glowing Coders**, https://glowingcoders.com/
- Contributor account: **glowingcoders**
- Version: **1.0.0**
- License: **GPL-2.0-or-later**
- Scope: reusable filtered HTML and escaped plain text; single-site WordPress 6.4+, PHP 8.2+.

The earlier full execution build remains archived. The content edition is the continuing directory product; executable features must not be reintroduced as a post-approval workaround.

## Exact upload file

Use **wp-snippet-studio-1.0.0.zip**, produced by the content-edition workflow. The artifact named **wp-snippet-studio-submission-candidate** contains the installation ZIP, its checksum and the complete Plugin Check report.

Download the artifact from the latest successful matching-commit run. Extract the outer GitHub artifact archive once, then select the inner plugin ZIP for submission. Do not upload the outer report bundle or GitHub's source-code archive.

Expected plugin ZIP SHA-256:

```text
96982a1f27043ad17755cbbdc4762a7db98cddcfe4f911806ec98c12a7f725f1  wp-snippet-studio-1.0.0.zip
```

The installation archive has one `wp-snippet-studio/` directory and 15 runtime/readme/license files. [Validation evidence](validation.md) documents its tests and known naming warnings.

## Remaining submission step

1. Sign in to WordPress.org as **glowingcoders** and open https://wordpress.org/plugins/developers/add/.
2. Check for an existing pending submission for this same plugin. Update that submission if one exists; do not create duplicates.
3. Upload the exact installation ZIP. Keep **WP Snippet Studio** and the requested slug.
4. Read the actual submission terms and attestations before accepting them. Resolve any name warning with the review process; do not silently rename the plugin or bypass the checks.
5. Record the real confirmation, review status and date in the root README and validation log. Until confirmation exists, the status remains **not submitted**.

The official scan reports **0 errors and 3 naming warnings** for the WP prefix. This package does not claim approval. The official [developer FAQ](https://developer.wordpress.org/plugins/wordpress-org/plugin-developer-faq/) describes review and upload requirements.

## After approval

Use the assigned WordPress.org SVN repository for the reviewed runtime package, with consistent main-file version, readme stable tag and release tag. Verify the directory download before announcing customer availability. GitHub changes alone do not publish customer updates. The existing production 0.1.1 installation is not replaced as part of submission preparation.
