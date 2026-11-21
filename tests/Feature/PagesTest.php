<?php

declare(strict_types=1);

use Spatie\Permission\Models\Role;
use Sunrice\Account\Facades\Account;
use Sunrice\Cms\Site\Page;
use Sunrice\ContentRestriction\Settings\RestrictionSettings;
use Sunrice\ContentRestriction\Site\PageGate;

beforeEach(function () {
    site();
    $this->club = post('Coffee club');
    restrictions(['collections' => ['blog']]);
});

it('sends a guest to sign in, and back to the page once signed in', function () {
    siteUser();

    $this->get('/blog/coffee-club')->assertRedirect(Account::url('account.login'));
    $this->post('/account/login', ['email' => 'ana@example.com', 'password' => 'secret-pass'])->assertRedirect('http://localhost/blog/coffee-club');
    $this->get('/blog/coffee-club')->assertOk()->assertSee('About Coffee club.');
});

it('opens for any signed-in account when no role is chosen', function () {
    $this->actingAs(siteUser(), 'account')->get('/blog/coffee-club')->assertOk()->assertSee('About Coffee club.');
});

it('opens for accounts with one of the roles, and shows the others a message', function () {
    restrictions(['roles' => ['premium', 'gold']]);

    $this->actingAs(siteUser([], 'member'), 'account')->get('/blog/coffee-club')
        ->assertOk()->assertSee('Akun Anda tidak dapat membuka halaman ini.')->assertDontSee('About Coffee club.');
    $this->actingAs(siteUser(['email' => 'bo@example.com'], 'gold'), 'account')->get('/blog/coffee-club')->assertOk()->assertSee('About Coffee club.');
});

it('shows a guest the message with a link to sign in, in the page\'s language', function () {
    restrictions(['unauthorized' => RestrictionSettings::MESSAGE]);

    $this->get('/blog/coffee-club')->assertOk()
        ->assertSee('Coffee club')->assertDontSee('About Coffee club.')
        ->assertSee('Halaman ini khusus anggota.')
        ->assertSee(Account::url('account.login'))->assertSee(Account::url('account.register'))
        ->assertSee('<meta name="robots" content="noindex">', false);
    $this->get('/en/blog/coffee-club')->assertOk()->assertSee('This page is for members.');

    // The sign-in link brings them back.
    siteUser();
    $this->post('/account/login', ['email' => 'ana@example.com', 'password' => 'secret-pass'])->assertRedirect('http://localhost/en/blog/coffee-club');
});

it('sends visitors who may not open it to a page of the site, in their language', function () {
    $join = post('Join', 'pages');
    restrictions(['unauthorized' => RestrictionSettings::PAGE, 'redirectTo' => '/join', 'roles' => ['premium']]);

    $this->get('/blog/coffee-club')->assertRedirect('http://localhost/join');
    $this->get('/en/blog/coffee-club')->assertRedirect('http://localhost/en/join');
    $this->actingAs(siteUser([], 'member'), 'account')->get('/blog/coffee-club')->assertRedirect('http://localhost/join');
    $this->get('/join')->assertOk();
});

it('shows the message instead of sending the redirect page to itself', function () {
    $join = post('Join', 'pages');
    restrictions(['collections' => ['blog', 'pages'], 'unauthorized' => RestrictionSettings::PAGE, 'redirectTo' => '/join']);

    $this->get('/join')->assertOk()->assertSee('Halaman ini khusus anggota.');
});

it('leaves other collections open', function () {
    post('About us', 'pages');

    $this->get('/about-us')->assertOk()->assertSee('About About us.');
});

it('lets an entry differ from its collection', function () {
    $open = post('Open day');
    $this->get('/blog/open-day')->assertRedirect();

    entryAccess($open, 'everyone');
    $this->get('/blog/open-day')->assertOk();

    $about = post('About us', 'pages');
    Role::findOrCreate('premium', 'web');
    entryAccess($about, 'restricted', ['premium']);
    $this->actingAs(siteUser([], 'member'), 'account')->get('/about-us')->assertSee('Akun Anda tidak dapat membuka halaman ini.');
});

it('restricts the term pages of a taxonomy, and a term on its own', function () {
    $news = topic('News');
    $this->get('/topics/news')->assertOk();

    restrictions(['taxonomies' => ['topics']]);
    $this->get('/topics/news')->assertRedirect(Account::url('account.login'));

    termAccess($news, 'everyone');
    $this->get('/topics/news')->assertOk();
});

it('shows editors a preview', function () {
    expect(PageGate::VIEW)->toBe('content-restriction::restricted');
    $page = new Page(Page::ENTRY, 'id', 'cms.article', entry: $this->club, preview: true);

    expect((new PageGate)($page))->toBeNull();
});
