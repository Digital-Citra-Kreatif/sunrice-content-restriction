# Changelog

## Unreleased

- `sunrice/account` comes from Packagist: `composer.json` no longer lists its GitHub repository.

## 0.1.0 (2026-11-21)

- Needs `sunrice/account` 0.1 (was its `main`). Follows its prefixed route names (`sunrice.account.login`, `sunrice.account.register`),
  and pushes its styles to the layout as the account pages do (`partials.push-styles`).
- The package: `sunrice/content-restriction`, a Sunrice plugin (`ContentRestrictionPlugin`, id
  `content-restriction`, after accounts).
  - Settings → Restricted content (`RestrictionSettings`, permission
    `content-restriction.settings.restrictions`): restricted collections and taxonomies, the roles
    that may open them (none: any signed-in account), the collections whose lists keep restricted
    entries as teasers, and what a guest gets: the sign-in page and back, a page of the site, or a
    message.
  - The Access card of the entry and term editors: as the collection or taxonomy says, everyone,
    or signed-in accounts with one of some roles. Kept in `content_restrictions`.
  - Restricted entry and term pages send visitors who can't open them to sign in or to the page
    set, or show the message (`content-restriction::restricted`, overridable). Previews show
    everything.
  - Lists, search, menus, entries fields and term counts leave out what the visitor can't open,
    but for teaser collections; the sitemap leaves out every restricted entry.
  - `Restrictions` and `Rule` for plugins.
