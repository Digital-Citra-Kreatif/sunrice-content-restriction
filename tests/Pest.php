<?php

declare(strict_types=1);

use Spatie\Permission\Models\Role;
use Sunrice\Cms\Actions\Entries\CreateEntry;
use Sunrice\Cms\Actions\Entries\PublishEntry;
use Sunrice\Cms\Actions\Terms\SaveTerm;
use Sunrice\Cms\Models\Blueprint;
use Sunrice\Cms\Models\Collection;
use Sunrice\Cms\Models\EntryTranslation;
use Sunrice\Cms\Models\Taxonomy;
use Sunrice\Cms\Models\Term;
use Sunrice\ContentRestriction\Editor\AccessCard;
use Sunrice\ContentRestriction\Settings\RestrictionSettings;
use Sunrice\ContentRestriction\Tests\TestCase;
use Workbench\App\Models\User;

uses(TestCase::class)->in('Feature');

/** A site user, verified, with the roles given (on the panel's guard). */
function siteUser(array $attributes = [], string ...$roles): User
{
    $user = User::query()->create([...['name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'secret-pass', 'email_verified_at' => now()], ...$attributes]);
    foreach ($roles as $role) {
        $user->assignRole(Role::findOrCreate($role, 'web'));
    }

    return $user;
}

/** Saves Settings → Restricted content, as the settings page does: the others keep their values. */
function restrictions(array $values): void
{
    app(RestrictionSettings::class)->fill($values)->save();
}

/** The blog (/blog, /blog/{slug}) and the topics taxonomy (/topics/{slug}). */
function site(): void
{
    Blueprint::query()->create(['handle' => 'post', 'title' => 'Post', 'fields' => [['handle' => 'intro', 'type' => 'text']]]);
    Collection::query()->create(['handle' => 'blog', 'title' => 'Blog', 'blueprint' => 'post', 'dated' => true, 'route' => '/blog/{slug}', 'listing_route' => '/blog']);
    Collection::query()->create(['handle' => 'pages', 'title' => 'Pages', 'blueprint' => 'post', 'route' => '/{slug}']);
    Taxonomy::query()->create(['handle' => 'topics', 'title' => 'Topics', 'route' => '/topics/{slug}']);
}

function post(string $title, string $collection = 'blog'): EntryTranslation
{
    return app(PublishEntry::class)->handle(app(CreateEntry::class)->handle(Collection::query()->where('handle', $collection)->firstOrFail(), ['title' => $title, 'data' => ['intro' => "About {$title}."]]));
}

function topic(string $title): Term
{
    return app(SaveTerm::class)->handle(Taxonomy::query()->where('handle', 'topics')->firstOrFail(), ['title' => $title]);
}

/** Sets an entry's own access, as its Access card does. */
function entryAccess(EntryTranslation $entry, string $access, array $roles = []): void
{
    AccessCard::make()->store($entry->entry, ['access' => $access, 'roles' => $roles], siteUser(['email' => uniqid().'@staff.test']));
}

/** Sets a term's own access, as its Access card does. */
function termAccess(Term $term, string $access, array $roles = []): void
{
    AccessCard::make()->store($term, ['access' => $access, 'roles' => $roles], siteUser(['email' => uniqid().'@staff.test']));
}

/** Signs in to an account on the site, outside a request: the next question to Restrictions sees them. */
function signIn(User $user): void
{
    test()->actingAs($user, 'account');
    app()->forgetScopedInstances();
}
