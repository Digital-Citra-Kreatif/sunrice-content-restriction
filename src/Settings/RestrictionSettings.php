<?php

declare(strict_types=1);

namespace Sunrice\ContentRestriction\Settings;

use Closure;
use Spatie\Permission\Models\Role;
use Sunrice\Admin\Panel;
use Sunrice\Admin\Resources\Forms\Form;
use Sunrice\Admin\Resources\Forms\Section;
use Sunrice\Admin\Resources\Forms\Select;
use Sunrice\Admin\Resources\Forms\TextInput;
use Sunrice\Admin\Settings\Settings;
use Sunrice\Cms\Models\Collection;
use Sunrice\Cms\Models\Taxonomy;

/**
 * Settings → Restricted content: which collections' entries and which
 * taxonomies' term pages only signed-in accounts open (with one of
 * `roles`, or any account when none is chosen), whether lists show
 * restricted entries to visitors who can't open them, and what those
 * visitors get. An entry or a term can differ in its editor's Access card.
 *
 * @api
 */
final class RestrictionSettings extends Settings
{
    public const KEY = 'restrictions';

    public const SIGN_IN = 'login';

    public const PAGE = 'page';

    public const MESSAGE = 'message';

    /** @var array<mixed> handles of the collections whose entries are restricted (read them with list()) */
    public array $collections = [];

    /** @var array<mixed> handles of the taxonomies whose term pages are restricted (read them with list()) */
    public array $taxonomies = [];

    /** @var array<mixed> roles that may open restricted content; none: any signed-in account (read them with list()) */
    public array $roles = [];

    /** @var array<mixed> handles of the collections whose lists still show restricted entries (a teaser) (read them with list()) */
    public array $teasers = [];

    /** What a guest gets on a restricted page: self::SIGN_IN, self::PAGE or self::MESSAGE. */
    public string $unauthorized = self::SIGN_IN;

    /** With self::PAGE: the site path they're sent to. */
    public string $redirectTo = '';

    public static function key(): string
    {
        return self::KEY;
    }

    public static function label(): string
    {
        return 'Restricted content';
    }

    public static function description(): string
    {
        return 'Pages only signed-in accounts open, and what other visitors get. An entry or a term can differ in its editor\'s Access card.';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Restricted')->columns(2)->schema([
                Select::make('collections')->label('Collections')->multiple()->span(2)
                    ->options(fn (): array => self::collectionOptions())
                    ->help('Their entries\' pages open for signed-in accounts only. Their listing pages stay open.'),
                Select::make('taxonomies')->label('Taxonomies')->multiple()->span(2)
                    ->options(fn (): array => self::taxonomyOptions())
                    ->help('Their terms\' pages open for signed-in accounts only.'),
                Select::make('roles')->label('Roles that may open them')->multiple()->span(2)
                    ->options(fn (): array => self::roleOptions())
                    ->help('None: any signed-in account.'),
            ]),
            Section::make('Lists')->description('Listing and term pages, search, menus and entries fields, for visitors who can\'t open an entry. The sitemap never lists restricted entries.')->columns(2)->schema([
                Select::make('teasers')->label('Still list their entries')->multiple()->span(2)
                    ->options(fn (): array => self::collectionOptions())
                    ->help('Restricted entries of these collections stay in lists, as a teaser leading to their page. Other collections\' are left out.'),
            ]),
            Section::make('Visitors who may not open a page')->columns(2)->schema([
                Select::make('unauthorized')->label('A guest goes to')->required()->span(2)
                    ->options([
                        self::SIGN_IN => 'The sign-in page, and back once signed in',
                        self::PAGE => 'A page of the site',
                        self::MESSAGE => 'A message on the page, with a link to sign in',
                    ])
                    ->help('A signed-in account without one of the roles sees the message (or goes to the page).'),
                TextInput::make('redirectTo')->label('Page')->maxLength(255)->placeholder('/members')->span(2)
                    ->showWhen('unauthorized', self::PAGE)
                    ->rules(['required_if:unauthorized,'.self::PAGE, self::rule(self::checkPath(...))])
                    ->help('A path on the site, in each language under its prefix: /members, /en/members.'),
            ]),
        ]);
    }

    /** @return list<string> */
    public function list(string $setting): array
    {
        $values = match ($setting) {
            'collections' => $this->collections,
            'taxonomies' => $this->taxonomies,
            'roles' => $this->roles,
            'teasers' => $this->teasers,
            default => [],
        };

        return array_values(array_unique(array_filter($values, fn (mixed $value): bool => is_string($value) && $value !== '')));
    }

    /** The redirect page's path without slashes around it ('' when none). */
    public function redirectPath(): string
    {
        return $this->unauthorized === self::PAGE ? trim($this->redirectTo, "/ \t") : '';
    }

    /** @return array<string, string> */
    private static function collectionOptions(): array
    {
        return self::options(Collection::query()->orderBy('title')->pluck('title', 'handle')->all());
    }

    /** @return array<string, string> */
    private static function taxonomyOptions(): array
    {
        return self::options(Taxonomy::query()->orderBy('title')->pluck('title', 'handle')->all());
    }

    /** @return array<string, string> the roles on the panel's guard, by name */
    public static function roleOptions(): array
    {
        $options = [];
        foreach (Role::query()->where('guard_name', app(Panel::class)->getGuard())->orderBy('name')->pluck('name')->all() as $name) {
            if (is_string($name)) {
                $options[$name] = $name;
            }
        }

        return $options;
    }

    /**
     * @param  array<mixed>  $pairs
     * @return array<string, string>
     */
    private static function options(array $pairs): array
    {
        $options = [];
        foreach ($pairs as $value => $label) {
            if (is_string($value) && is_string($label)) {
                $options[$value] = $label;
            }
        }

        return $options;
    }

    private static function checkPath(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        $path = trim($value);
        if (! str_starts_with($path, '/') || str_starts_with($path, '//') || preg_match('/[\s?#\\\\]/', $path) === 1) {
            return 'A path on the site, starting with a slash: /members.';
        }

        return null;
    }

    /**
     * @param  Closure(mixed): ?string  $check  the error, or null
     * @return Closure(string, mixed, Closure(string): mixed): void
     */
    private static function rule(Closure $check): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($check): void {
            $error = $check($value);
            if ($error !== null) {
                $fail($error);
            }
        };
    }
}
