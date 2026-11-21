<?php

declare(strict_types=1);

namespace Sunrice\ContentRestriction\Editor;

use Sunrice\Admin\Resources\Forms\Form;
use Sunrice\Admin\Resources\Forms\Select;
use Sunrice\Cms\Editor\EditorCard;
use Sunrice\Cms\Models\Entry;
use Sunrice\Cms\Models\Term;
use Sunrice\ContentRestriction\Models\Restriction;
use Sunrice\ContentRestriction\Restrictions;
use Sunrice\ContentRestriction\Rule;
use Sunrice\ContentRestriction\Settings\RestrictionSettings;

/**
 * The Access card of the entry and term editors: as its collection or
 * taxonomy says (Settings → Restricted content), open to everyone, or
 * for signed-in accounts (with one of some roles, or any).
 *
 * @internal
 */
final class AccessCard
{
    public const KEY = 'access';

    public const DEFAULT = 'default';

    public static function make(): EditorCard
    {
        return EditorCard::make(self::KEY, 'Access')
            ->description('Who may open its page.')
            ->entries()
            ->terms()
            ->form(fn (Entry|Term $record, Form $form): Form => $form->schema([
                Select::make('access')->label('Open to')->required()
                    ->options([
                        self::DEFAULT => self::defaultLabel($record),
                        Restriction::EVERYONE => 'Everyone',
                        Restriction::RESTRICTED => 'Signed-in accounts',
                    ]),
                Select::make('roles')->label('With one of the roles')->multiple()
                    ->options(fn (): array => RestrictionSettings::roleOptions())
                    ->showWhen('access', Restriction::RESTRICTED)
                    ->help('None: any signed-in account.'),
            ]))
            ->load(function (Entry|Term $record): array {
                $row = self::row($record);

                return ['access' => $row->access ?? self::DEFAULT, 'roles' => $row?->roleNames() ?? []];
            })
            ->save(function (Entry|Term $record, array $values): void {
                $access = $values['access'] ?? self::DEFAULT;
                if ($access === self::DEFAULT) {
                    self::row($record)?->delete();
                } else {
                    $roles = $access === Restriction::RESTRICTED && is_array($values['roles'] ?? null) ? array_values(array_filter($values['roles'], is_string(...))) : [];
                    Restriction::query()->updateOrCreate(
                        ['restrictable_type' => self::type($record), 'restrictable_id' => $record->id],
                        ['access' => $access, 'roles' => $roles],
                    );
                }
                app(Restrictions::class)->changed();
            });
    }

    private static function row(Entry|Term $record): ?Restriction
    {
        return Restriction::query()->where('restrictable_type', self::type($record))->where('restrictable_id', $record->id)->first();
    }

    private static function type(Entry|Term $record): string
    {
        return $record instanceof Entry ? Restriction::ENTRY : Restriction::TERM;
    }

    /** "As Blog is: everyone", "As Blog is: signed-in accounts (member, premium)". */
    private static function defaultLabel(Entry|Term $record): string
    {
        $restrictions = app(Restrictions::class);
        $settings = $restrictions->settings();
        if ($record instanceof Entry) {
            $name = $record->collection->title;
            $default = in_array($record->collection->handle, $settings->list('collections'), true);
        } else {
            $name = $record->taxonomy->title;
            $default = in_array($record->taxonomy->handle, $settings->list('taxonomies'), true);
        }
        $rule = $default ? new Rule(true, $settings->list('roles')) : Rule::everyone();
        $who = match (true) {
            ! $rule->restricted => __('everyone'),
            $rule->roles === [] => __('signed-in accounts'),
            default => __('signed-in accounts (:roles)', ['roles' => implode(', ', $rule->roles)]),
        };

        return __('As :name is: :who', ['name' => $name, 'who' => $who]);
    }
}
