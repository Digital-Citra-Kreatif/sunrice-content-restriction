<?php

declare(strict_types=1);

use Sunrice\ContentRestriction\Settings\RestrictionSettings;

beforeEach(function () {
    config(['sunrice-cms.cache.pages' => true, 'sunrice-cms.cache.enabled' => true, 'responsecache.debug.enabled' => true]);
    site();
    $this->club = post('Coffee club');
});

it('stops giving a kept page once it\'s restricted', function () {
    $this->get('/blog/coffee-club')->assertOk()->assertHeader('X-Cache-Status', 'MISS');
    $this->get('/blog/coffee-club')->assertOk()->assertHeader('X-Cache-Status', 'HIT');

    entryAccess($this->club, 'restricted');
    $this->get('/blog/coffee-club')->assertRedirect();

    entryAccess($this->club, 'default');
    restrictions(['collections' => ['blog']]);
    $this->get('/blog/coffee-club')->assertRedirect();
});

it('never keeps the message a guest gets, which brings them back once signed in', function () {
    restrictions(['collections' => ['blog'], 'unauthorized' => RestrictionSettings::MESSAGE]);

    $this->get('/blog/coffee-club')->assertOk()->assertSee('Halaman ini khusus anggota.');
    expect($this->get('/blog/coffee-club')->headers->get('X-Cache-Status'))->not->toBe('HIT');
});

it('gives signed-in accounts fresh pages, never a guest\'s', function () {
    restrictions(['collections' => ['blog'], 'teasers' => ['blog'], 'roles' => ['premium']]);
    $this->get('/blog')->assertOk()->assertHeader('X-Cache-Status', 'MISS');
    $this->get('/blog')->assertHeader('X-Cache-Status', 'HIT');

    $this->actingAs(siteUser([], 'premium'), 'account')->get('/blog/coffee-club')->assertOk()->assertSee('About Coffee club.');
    expect($this->get('/blog')->assertSee('Coffee club')->headers->get('X-Cache-Status'))->not->toBe('HIT');
});
