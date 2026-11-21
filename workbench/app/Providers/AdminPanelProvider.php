<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Sunrice\Account\AccountPlugin;
use Sunrice\Admin\Panel;
use Sunrice\Admin\PanelProvider;
use Sunrice\Cms\CmsPlugin;
use Sunrice\ContentRestriction\ContentRestrictionPlugin;

/** The panel of the tests and of the workbench site: the CMS, accounts, and restricted content. */
final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->path('admin')->plugin(CmsPlugin::make())->plugin(AccountPlugin::make())->plugin(ContentRestrictionPlugin::make());
    }
}
