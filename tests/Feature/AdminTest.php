<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Sunrice\Admin\Panel;
use Sunrice\ContentRestriction\Models\Restriction;
use Sunrice\ContentRestriction\Settings\RestrictionSettings;
use Workbench\App\Models\User;

beforeEach(function () {
    site();
    $this->club = post('Coffee club');
    Role::findOrCreate('premium', 'web');
});

function staff(string ...$permissions): User
{
    $user = siteUser(['name' => 'Rina', 'email' => uniqid().'@staff.test']);
    foreach ([Panel::ACCESS, ...$permissions] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $user->givePermissionTo([Panel::ACCESS, ...$permissions]);

    return $user;
}

it('adds Settings → Restricted content behind its permission', function () {
    expect(app(Panel::class)->getPermissions())->toHaveKey('content-restriction.settings.restrictions');

    $this->actingAs(staff())->get('/admin/settings/restrictions')->assertForbidden();
    $this->actingAs(staff('content-restriction.settings.restrictions'))->get('/admin/settings/restrictions')->assertOk();
});

it('saves the settings, checking the page to send visitors to', function () {
    $this->actingAs(staff('content-restriction.settings.restrictions'));

    $this->put('/admin/settings/restrictions', ['collections' => ['blog'], 'roles' => ['premium'], 'unauthorized' => 'page', 'redirectTo' => ''])
        ->assertSessionHasErrors('redirectTo');
    $this->put('/admin/settings/restrictions', ['collections' => ['blog'], 'roles' => ['premium'], 'unauthorized' => 'page', 'redirectTo' => 'https://example.com'])
        ->assertSessionHasErrors('redirectTo');
    $this->put('/admin/settings/restrictions', ['collections' => ['nope'], 'unauthorized' => 'login'])->assertSessionHasErrors('collections.0');

    $this->put('/admin/settings/restrictions', ['collections' => ['blog'], 'roles' => ['premium'], 'unauthorized' => 'page', 'redirectTo' => '/join'])
        ->assertSessionHasNoErrors();
    $settings = app(RestrictionSettings::class);
    expect($settings->list('collections'))->toBe(['blog'])->and($settings->redirectPath())->toBe('join');
    $this->get('/blog/coffee-club')->assertRedirect('http://localhost/join');
});

it('shows the Access card in the entry editor, saying what the collection gives', function () {
    restrictions(['collections' => ['blog'], 'roles' => ['premium']]);
    $this->actingAs(staff('cms.entries.view', 'cms.entries.update'));

    $this->get("/admin/cms/collections/blog/entries/{$this->club->entry_id}/edit")->assertInertia(fn (Assert $page) => $page
        ->where('cards.0.key', 'access')
        ->where('cards.0.values', ['access' => 'default', 'roles' => []])
        ->where('cards.0.sections.0.fields.0.options.0.label', 'As Blog is: signed-in accounts (premium)'));
});

it('saves an entry\'s own access from its card, and back to the collection\'s', function () {
    $this->actingAs(staff('cms.entries.view', 'cms.entries.update'));
    $id = $this->club->entry_id;

    $this->put("/admin/cms/entries/{$id}/cards/access", ['values' => ['access' => 'restricted', 'roles' => ['premium']]])->assertSessionHasNoErrors();
    expect(Restriction::query()->sole()->only('restrictable_type', 'restrictable_id', 'access', 'roles'))
        ->toBe(['restrictable_type' => 'entry', 'restrictable_id' => $id, 'access' => 'restricted', 'roles' => ['premium']]);
    $this->get('/blog/coffee-club')->assertRedirect();

    $this->put("/admin/cms/entries/{$id}/cards/access", ['values' => ['access' => 'restricted', 'roles' => ['nobody']]])->assertSessionHasErrors();
    $this->put("/admin/cms/entries/{$id}/cards/access", ['values' => ['access' => 'default']])->assertSessionHasNoErrors();
    expect(Restriction::query()->count())->toBe(0);
});

it('keeps the card from those who may not update the entry', function () {
    $this->actingAs(staff('cms.entries.view'));

    $this->put("/admin/cms/entries/{$this->club->entry_id}/cards/access", ['values' => ['access' => 'everyone']])->assertForbidden();
});

it('saves a term\'s own access from its card', function () {
    $news = topic('News');
    $this->actingAs(staff('cms.terms.view', 'cms.terms.update'));

    $this->put("/admin/cms/terms/{$news->id}/cards/access", ['values' => ['access' => 'restricted']])->assertSessionHasNoErrors();
    $this->get('/topics/news')->assertRedirect();
});
