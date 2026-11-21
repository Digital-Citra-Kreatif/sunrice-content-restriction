# Restricted content

`sunrice/content-restriction` makes some of a Sunrice CMS site's pages members-only: the entries
of some collections and the term pages of some taxonomies open only for visitors signed in to an
account (from `sunrice/account`), with one of some roles or any. Each entry and term can differ.

```php
$panel->plugin(CmsPlugin::make())
    ->plugin(AccountPlugin::make())
    ->plugin(ContentRestrictionPlugin::make());
```

Run the migrations (`php artisan migrate`): the package adds the `content_restrictions` table.

## Settings → Restricted content

Behind the permission `content-restriction.settings.restrictions`.

| Setting | |
| --- | --- |
| Collections | Their entries' pages open for signed-in accounts only. Their listing pages stay open |
| Taxonomies | Their terms' pages open for signed-in accounts only |
| Roles that may open them | One of these roles opens restricted content. None: any signed-in account |
| Still list their entries | Collections whose restricted entries stay in lists as a teaser, leading to their page. Other collections' restricted entries are left out of lists for visitors who can't open them |
| A guest goes to | The sign-in page (and back to the page once signed in), a page of the site, or a message on the page with a link to sign in |
| Page | With "a page of the site": its path (`/members`), opened in the visitor's language (`/en/members`) |

A signed-in account without one of the roles sees the message, or goes to the page set.

## The Access card

The entry and term editors get an **Access** card in their sidebar, for those who may update the
entry or term. It says what its collection or taxonomy gives ("As Blog is: signed-in accounts
(premium)") and can open the page to everyone, or to signed-in accounts with one of some roles (or
any). It saves at once, apart from the draft, and the site's caches start over.

## What visitors who can't open a page get

| Visitor | Page restricted for them |
| --- | --- |
| Guest, "the sign-in page" | Sent to sign in, and back to the page afterwards |
| Guest, "a page of the site" | Sent to that page |
| Guest, "a message" | The message, with **Sign in** (back to the page afterwards) and **Create an account** while visitors can |
| Signed-in, without the role | The message, or the page set |

Editors' previews always show the page.

The message is the view `content-restriction::restricted`. It extends the site layout the account
pages use (`Sunrice\Account\View\Layout::site()`). Replace it with your own copy in
`resources/views/vendor/content-restriction/restricted.blade.php`. It gets the page's own
variables (`$page`, `$entry` or `$term`, `$title`…) and:

| Variable | |
| --- | --- |
| `$signedIn` | Whether the visitor is signed in to an account (without one of the roles) |
| `$signInUrl` | For a guest: the sign-in page, which brings them back here |
| `$registerUrl` | For a guest: the registration page, or null when visitors can't create an account |

So a teaser can show the entry's intro:

```blade
@extends(\Sunrice\Account\View\Layout::site())

@section('content')
    <h1>{{ $title }}</h1>
    <p>{{ $values['intro'] ?? '' }}</p>
    @unless ($signedIn)
        <a href="{{ $signInUrl }}">Sign in to read on</a>
    @endunless
@endsection
```

Its lines are in `content-restriction::restriction` (English and Indonesian). Publish them with
`php artisan vendor:publish --tag=content-restriction-translations`.

## Lists, search and the sitemap

Entries a visitor can't open are left out of what the site lists: listing and term pages,
`Cms::entries()` and `<x-sunrice-cms::entries>`, search, menus, entries fields and term counts.
Collections set to "Still list their entries" keep them in lists as teasers. The sitemap never
lists restricted entries, since search engines can't sign in. Cached lists are kept per kind of
visitor (guests, and each set of roles), so a member's list is never shown to a guest.

## The page cache

The CMS's whole-page cache only ever keeps pages for guests: a visitor signed in to an account
always gets fresh pages. A restricted page sends guests elsewhere or shows them the message, and
neither is kept. Saving the settings or an Access card starts the site's caches over.

## For plugins

`Sunrice\ContentRestriction\Restrictions` (one per request) answers who may open what:

```php
use Sunrice\ContentRestriction\Restrictions;

$restrictions = app(Restrictions::class);
$rule = $restrictions->forEntry($entry);   // or forTerm($term): a Rule
$restrictions->allows($rule);              // for the visitor
$rule->restricted;                         // false: open to everyone
$rule->roles;                              // [] with restricted: any signed-in account
```
