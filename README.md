# Sunrice Content Restriction

Members-only content for [Sunrice CMS](https://github.com/Digital-Citra-Kreatif/sunrice), built on
`sunrice/account`:

- Settings → Restricted content: the collections and taxonomies whose pages only signed-in
  accounts open, the roles that may open them, and what other visitors get (the sign-in page and
  back, a page of the site, or a message).
- An **Access** card in the entry and term editors, so each entry or term can differ.
- Restricted entries are left out of lists, search, menus and the sitemap for visitors who can't
  open them, or kept as teasers.
- An overridable Blade view for the message.

Requires `sunrice/account` 0.2.1 or later in the 0.2 series.

```bash
composer require sunrice/content-restriction
php artisan migrate
```

```php
$panel->plugin(CmsPlugin::make())->plugin(AccountPlugin::make())->plugin(ContentRestrictionPlugin::make());
```

See [docs/index.md](docs/index.md).
