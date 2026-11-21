<?php

declare(strict_types=1);

namespace Sunrice\ContentRestriction\Site;

use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Sunrice\Account\Account;
use Sunrice\Cms\Site\PageCache;
use Sunrice\ContentRestriction\Restrictions;

/**
 * What the restricted page's view gets besides the page's own variables
 * (`$page`, `$entry` or `$term`, `$title`…): `$signedIn`, and for a guest
 * `$signInUrl` (they come back here once signed in) and `$registerUrl`
 * (null when visitors can't create an account).
 *
 * @internal
 */
final class RestrictedView
{
    public function compose(View $view): void
    {
        $signedIn = app(Restrictions::class)->visitorRoles() !== null;
        $account = app(Account::class);
        $signInUrl = $registerUrl = null;
        if (! $signedIn) {
            // The sign-in link brings them back to this page, which the page cache can't know: not kept.
            app(PageCache::class)->dontKeep();
            $signInUrl = $account->redirectToSignIn()->getTargetUrl();
            $registerUrl = $account->settings()->registration && Route::has('account.register') ? $account->url('account.register') : null;
        }
        $view->with(['signedIn' => $signedIn, 'signInUrl' => $signInUrl, 'registerUrl' => $registerUrl]);
    }
}
