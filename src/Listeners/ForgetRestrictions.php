<?php

declare(strict_types=1);

namespace Sunrice\ContentRestriction\Listeners;

use Sunrice\Admin\Events\SettingsSaved;
use Sunrice\ContentRestriction\Restrictions;
use Sunrice\ContentRestriction\Settings\RestrictionSettings;

/** After Settings → Restricted content is saved: the site's caches start over. */
final class ForgetRestrictions
{
    public function handle(SettingsSaved $event): void
    {
        if ($event->section === RestrictionSettings::KEY) {
            app(Restrictions::class)->changed();
        }
    }
}
