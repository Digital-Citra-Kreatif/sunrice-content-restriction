<?php

declare(strict_types=1);

namespace Sunrice\ContentRestriction\Site;

use Illuminate\Http\Exceptions\HttpResponseException;
use Sunrice\Account\Account;
use Sunrice\Cms\Site\Page;
use Sunrice\Cms\Support\Locales;
use Sunrice\ContentRestriction\Restrictions;
use Sunrice\ContentRestriction\Rule;
use Sunrice\ContentRestriction\Settings\RestrictionSettings;

/**
 * Restricted entry and term pages, for visitors who may not open them
 * (a Cms::resolveTemplateUsing() hook): a guest goes to sign in (and
 * back), or to the page set, or sees the message; a signed-in account
 * without one of the roles sees the message, or goes to the page set.
 * The message is the page's view: `content-restriction::restricted`, which
 * the app replaces with resources/views/vendor/content-restriction/restricted.blade.php.
 * Previews show everything.
 *
 * @internal
 */
final class PageGate
{
    public const VIEW = 'content-restriction::restricted';

    public function __invoke(Page $page): ?string
    {
        $rule = $this->rule($page);
        $restrictions = app(Restrictions::class);
        if ($rule === null || $restrictions->allows($rule)) {
            return null;
        }
        $settings = $restrictions->settings();
        $guest = $restrictions->visitorRoles() === null;
        if ($guest && $settings->unauthorized === RestrictionSettings::SIGN_IN) {
            throw new HttpResponseException(app(Account::class)->redirectToSignIn());
        }
        $path = $settings->redirectPath();
        if ($path !== '' && trim(Locales::split(request()->path())[1], '/') !== $path) {
            throw new HttpResponseException(redirect(url(Locales::prefix($page->locale).'/'.$path)));
        }

        return self::VIEW;
    }

    private function rule(Page $page): ?Rule
    {
        if ($page->preview || $page->draft) {
            return null;
        }
        $restrictions = app(Restrictions::class);
        if ($page->type === Page::ENTRY && $page->entry !== null) {
            return $restrictions->forEntry($page->entry->entry);
        }
        if ($page->type === Page::TERM && $page->term !== null) {
            return $restrictions->forTerm($page->term->term);
        }

        return null;
    }
}
