<?php

declare(strict_types=1);

namespace Sunrice\ContentRestriction;

use Illuminate\Database\Eloquent\Builder;
use Sunrice\Account\Account;
use Sunrice\Cms\Models\Collection;
use Sunrice\Cms\Models\Entry;
use Sunrice\Cms\Models\EntryTranslation;
use Sunrice\Cms\Models\Taxonomy;
use Sunrice\Cms\Models\Term;
use Sunrice\Cms\Support\SiteCache;
use Sunrice\ContentRestriction\Models\Restriction;
use Sunrice\ContentRestriction\Settings\RestrictionSettings;

/**
 * Who may open what: an entry's or term's own access (its editor's Access
 * card), else its collection's or taxonomy's (Settings → Restricted
 * content). Kept for one request; plugins ask it too:
 *
 *     app(Restrictions::class)->allows(app(Restrictions::class)->forEntry($entry));
 *
 * @api
 */
final class Restrictions
{
    /** @var array<string, array<int, Rule>>|null type => id => its own rule */
    private ?array $own = null;

    /** @var list<string>|null|false the visitor's roles (null: a guest); false: not read yet */
    private array|null|false $roles = false;

    /** @var array<string, list<int>> */
    private array $ids = [];

    public function settings(): RestrictionSettings
    {
        return app(RestrictionSettings::class);
    }

    /** Who may open an entry's page. */
    public function forEntry(Entry $entry): Rule
    {
        return $this->own()[Restriction::ENTRY][$entry->id]
            ?? $this->defaultFor(in_array($entry->collection_id, $this->collectionIds('collections'), true));
    }

    /** Who may open a term's page. */
    public function forTerm(Term $term): Rule
    {
        return $this->own()[Restriction::TERM][$term->id]
            ?? $this->defaultFor(in_array($term->taxonomy_id, $this->taxonomyIds(), true));
    }

    /** Whether the visitor (signed in to an account or not) may open what the rule guards. */
    public function allows(Rule $rule): bool
    {
        return $rule->allows($this->visitorRoles());
    }

    /**
     * The visitor's account roles, sorted; null for a guest.
     *
     * @return list<string>|null
     */
    public function visitorRoles(): ?array
    {
        if ($this->roles === false) {
            $user = app(Account::class)->user();
            $this->roles = $user === null ? null : app(Account::class)->roles($user);
        }

        return $this->roles;
    }

    /** Whether anything is restricted: a collection, a taxonomy, or an entry's or term's own. */
    public function active(): bool
    {
        if ($this->collectionIds('collections') !== [] || $this->taxonomyIds() !== []) {
            return true;
        }
        foreach ($this->own() as $rules) {
            foreach ($rules as $rule) {
                if ($rule->restricted) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Leaves out of lists the entries the visitor (with `$everyone`: any
     * visitor, for the sitemap) may not open, but for the collections set
     * to still list them (not in the sitemap).
     *
     * @param  Builder<EntryTranslation>  $query
     */
    public function hide(Builder $query, bool $everyone): void
    {
        $roles = $everyone ? null : $this->visitorRoles();
        $teasers = $everyone ? [] : $this->collectionIds('teasers');
        $collection = $query->getModel()->qualifyColumn('collection_id');
        $entry = $query->getModel()->qualifyColumn('entry_id');

        $allowed = $denied = [];
        foreach ($this->own()[Restriction::ENTRY] ?? [] as $id => $rule) {
            if ($rule->allows($roles)) {
                $allowed[] = $id;
            } else {
                $denied[] = $id;
            }
        }
        $hidden = $this->defaultFor(true)->allows($roles) ? [] : array_values(array_diff($this->collectionIds('collections'), $teasers));
        if ($hidden !== []) {
            $query->where(fn (Builder $q) => $q->whereNotIn($collection, $hidden)->orWhereIn($entry, $allowed));
        }
        if ($denied !== []) {
            $query->where(fn (Builder $q) => $q->whereNotIn($entry, $denied)->orWhereIn($collection, $teasers));
        }
    }

    /** Who's looking, for caches of lists: the same for visitors who see the same entries. */
    public function viewer(): string
    {
        if (! $this->active()) {
            return '';
        }
        $roles = $this->visitorRoles();

        return $roles === null ? 'guest' : 'account:'.implode(',', $roles);
    }

    /** After an entry's or term's access, or the settings, changed: read again, and the site's caches start over. */
    public function changed(): void
    {
        $this->own = null;
        $this->ids = [];
        app(SiteCache::class)->changed('content-restriction');
    }

    private function defaultFor(bool $restricted): Rule
    {
        return $restricted ? new Rule(true, $this->settings()->list('roles')) : Rule::everyone();
    }

    /** @return array<string, array<int, Rule>> */
    private function own(): array
    {
        if ($this->own === null) {
            $this->own = [];
            foreach (Restriction::query()->get() as $row) {
                $this->own[$row->restrictable_type][$row->restrictable_id] = $row->access === Restriction::RESTRICTED
                    ? new Rule(true, $row->roleNames())
                    : Rule::everyone();
            }
        }

        return $this->own;
    }

    /**
     * @param  'collections'|'teasers'  $setting
     * @return list<int>
     */
    private function collectionIds(string $setting): array
    {
        $handles = $this->settings()->list($setting);

        return $this->ids[$setting] ??= $handles === [] ? [] : self::ids(Collection::query()->whereIn('handle', $handles)->pluck('id')->all());
    }

    /** @return list<int> */
    private function taxonomyIds(): array
    {
        $handles = $this->settings()->list('taxonomies');

        return $this->ids['taxonomies'] ??= $handles === [] ? [] : self::ids(Taxonomy::query()->whereIn('handle', $handles)->pluck('id')->all());
    }

    /**
     * @param  array<mixed>  $values
     * @return list<int>
     */
    private static function ids(array $values): array
    {
        $ids = [];
        foreach ($values as $value) {
            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                $ids[] = (int) $value;
            }
        }

        return $ids;
    }
}
