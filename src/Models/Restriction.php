<?php

declare(strict_types=1);

namespace Sunrice\ContentRestriction\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An entry's or term's own access, set in its editor's Access card: open
 * to everyone, or restricted (to some roles, or to any signed-in account
 * when `roles` is empty). Without a row, its collection's or taxonomy's
 * setting applies.
 *
 * @property int $id
 * @property string $restrictable_type 'entry' or 'term'
 * @property int $restrictable_id
 * @property string $access 'everyone' or 'restricted'
 * @property array<mixed>|null $roles role names (as stored: read them with roleNames())
 */
final class Restriction extends Model
{
    public const ENTRY = 'entry';

    public const TERM = 'term';

    public const EVERYONE = 'everyone';

    public const RESTRICTED = 'restricted';

    protected $table = 'content_restrictions';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['restrictable_id' => 'integer', 'roles' => 'array'];
    }

    /** @return list<string> */
    public function roleNames(): array
    {
        return array_values(array_filter($this->roles ?? [], fn (mixed $role): bool => is_string($role) && $role !== ''));
    }
}
