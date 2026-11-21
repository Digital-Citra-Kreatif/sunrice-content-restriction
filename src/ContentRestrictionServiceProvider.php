<?php

declare(strict_types=1);

namespace Sunrice\ContentRestriction;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Sunrice\Admin\Events\SettingsSaved;
use Sunrice\Cms\Content;
use Sunrice\Cms\Models\EntryTranslation;
use Sunrice\ContentRestriction\Editor\AccessCard;
use Sunrice\ContentRestriction\Listeners\ForgetRestrictions;
use Sunrice\ContentRestriction\Site\PageGate;
use Sunrice\ContentRestriction\Site\RestrictedView;

/**
 * Restricted pages on the site: the gate on entry and term pages, the
 * lists that leave out what the visitor can't open, the Access card and
 * the overrides' table. What it adds to the admin is in ContentRestrictionPlugin.
 */
final class ContentRestrictionServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('content-restriction')
            ->hasViews()
            ->hasTranslations()
            ->discoversMigrations()
            ->runsMigrations();
    }

    public function packageRegistered(): void
    {
        // Per request: the visitor's roles and the entries' own access are read once.
        $this->app->scoped(Restrictions::class);
    }

    public function packageBooted(): void
    {
        Content::editorCard(AccessCard::make());
        Content::resolveTemplateUsing((new PageGate)(...));
        Content::hideEntriesUsing(
            /** @param  Builder<EntryTranslation>  $query */
            fn (Builder $query, bool $everyone) => app(Restrictions::class)->hide($query, $everyone),
            fn (): string => app(Restrictions::class)->viewer(),
        );
        View::composer(PageGate::VIEW, RestrictedView::class);
        Event::listen(SettingsSaved::class, ForgetRestrictions::class);
    }
}
