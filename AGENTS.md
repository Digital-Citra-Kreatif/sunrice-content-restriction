# Working on Sunrice Content Restriction

`sunrice/content-restriction` (namespace `Sunrice\ContentRestriction\`, plugin id
`content-restriction`) makes a Sunrice CMS site's entries and term pages members-only, built on
`sunrice/account`'s site accounts and roles.

## Rules

- One task per branch and pull request, titled with what it does.
- Use only the public API (classes and methods marked `@api`) of `sunrice/admin`, `sunrice/cms`
  and `sunrice/account`. Missing something? Propose it there in the PR instead of reaching in.
- No feature detection (`method_exists`, reflection) between packages: state versions in
  `composer.json`.
- Security by default: every route and page declares what guards it; validate all input, escape
  all output. A restricted page never shows its content to a visitor who can't open it, and no
  cache gives one visitor's page to another.
- Every task ships Pest tests (Orchestra Testbench). Before opening a PR run `composer test`,
  `composer lint` and `composer analyse` (Larastan at `level: max`; no ignores without a comment).
- Until 1.0 there is no backwards compatibility to keep, but record every change in
  `CHANGELOG.md` and update `docs/` when adding developer-facing API.
- Do not commit `vendor/`, `node_modules/`, `composer.lock` or `.env`.

## Conventions

- PHP 8.4+, Laravel 13. Every PHP file starts `declare(strict_types=1);`.
- Views and translations `content-restriction::`; tables prefixed `content_`.
- Permissions `content-restriction.<area>.<action>`.
- Settings live in `RestrictionSettings` (Settings → Restricted content); an entry's or term's own
  access in `content_restrictions`.

## Commands

```bash
composer test      # Pest
composer lint      # Pint (test mode)
composer format    # Pint (fix)
composer analyse   # Larastan
vendor/bin/testbench serve   # the workbench site
```

## Releasing

Move the changelog's Unreleased section to `## <version> (<date>)` in a pull request, merge it, then
run the **release** workflow (Actions → release → Run workflow) with the version: it tags
`v<version>` on `main` and makes the GitHub release from that changelog section.
