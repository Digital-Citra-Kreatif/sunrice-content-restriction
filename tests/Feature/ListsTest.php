<?php

declare(strict_types=1);

use Sunrice\Cms\Content;
use Sunrice\Cms\Site\Sitemap;
use Sunrice\ContentRestriction\Restrictions;

beforeEach(function () {
    site();
    $this->open = post('Open day');
    $this->club = post('Coffee club');
    $this->about = post('About us', 'pages');
    restrictions(['collections' => ['blog'], 'roles' => ['premium']]);
    entryAccess($this->open, 'everyone');
});

/** @return list<string> */
function listed(): array
{
    $titles = array_map(fn ($entry) => $entry->title, Content::entries('blog', 'pages')->get()->all());
    sort($titles);

    return $titles;
}

it('leaves out of lists what the visitor may not open', function () {
    expect(listed())->toBe(['About us', 'Open day']);
    $this->get('/blog')->assertOk()->assertSee('Open day')->assertDontSee('Coffee club');

    signIn(siteUser([], 'member'));
    expect(listed())->toBe(['About us', 'Open day']);

    signIn(siteUser(['email' => 'bo@example.com'], 'premium'));
    expect(listed())->toBe(['About us', 'Coffee club', 'Open day']);
    $this->get('/blog')->assertOk()->assertSee('Coffee club');
});

it('leaves out an entry restricted on its own, in an open collection', function () {
    entryAccess($this->about, 'restricted');

    expect(listed())->toBe(['Open day']);
    signIn(siteUser());
    expect(listed())->toBe(['About us', 'Open day']);
});

it('still lists the restricted entries of the collections set to, as teasers', function () {
    restrictions(['teasers' => ['blog']]);

    expect(listed())->toBe(['About us', 'Coffee club', 'Open day']);
    $this->get('/blog')->assertSee('Coffee club');
    $this->get('/blog/coffee-club')->assertRedirect();
});

it('never puts restricted entries in the sitemap', function () {
    restrictions(['teasers' => ['blog']]);
    signIn(siteUser([], 'premium'));

    $urls = array_column(iterator_to_array(app(Sitemap::class)->pages(), false), 'url');
    expect($urls)->toContain(url('/blog/open-day'))->toContain(url('/about-us'))->not->toContain(url('/blog/coffee-club'));
});

it('keeps lists apart per kind of visitor', function () {
    expect(app(Restrictions::class)->viewer())->toBe('guest');

    signIn(siteUser([], 'premium', 'member'));
    expect(app(Restrictions::class)->viewer())->toBe('account:member,premium');
});

it('has nothing to keep apart while nothing is restricted', function () {
    restrictions(['collections' => []]);
    entryAccess($this->open, 'default');
    app()->forgetScopedInstances();

    expect(app(Restrictions::class)->viewer())->toBe('')
        ->and(listed())->toBe(['About us', 'Coffee club', 'Open day']);
});
