<?php

declare(strict_types=1);

namespace Sunrice\ContentRestriction;

use Sunrice\Account\AccountPlugin;
use Sunrice\Admin\BasePlugin;
use Sunrice\Admin\Panel;
use Sunrice\Cms\CmsPlugin;
use Sunrice\ContentRestriction\Settings\RestrictionSettings;

/**
 * Members-only content, added to the panel after accounts in the app's
 * panel provider:
 *
 *     $panel->plugin(CmsPlugin::make())->plugin(AccountPlugin::make())->plugin(ContentRestrictionPlugin::make());
 *
 * Adds Settings → Restricted content (behind
 * `content-restriction.settings.restrictions`), the Access card of the
 * entry and term editors, and the developer docs.
 */
final class ContentRestrictionPlugin extends BasePlugin
{
    public const ID = 'content-restriction';

    public function id(): string
    {
        return self::ID;
    }

    /** @return list<string> */
    public function requires(): array
    {
        return [CmsPlugin::ID, AccountPlugin::ID];
    }

    public function register(Panel $panel): void
    {
        $panel->settings(RestrictionSettings::class);
        $panel->docs('Restricted content', dirname(__DIR__).'/docs');
    }
}
