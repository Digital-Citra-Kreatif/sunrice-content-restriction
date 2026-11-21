<?php

declare(strict_types=1);

namespace Sunrice\ContentRestriction;

/**
 * Who may open an entry or a term: everyone (`restricted` false), or
 * signed-in accounts with one of `roles` (any signed-in account when
 * there are none).
 *
 * @api
 */
final readonly class Rule
{
    /** @param  list<string>  $roles */
    public function __construct(public bool $restricted, public array $roles = []) {}

    public static function everyone(): self
    {
        return new self(false);
    }

    /** @param  list<string>  $accountRoles  the roles of the visitor's account (null: a guest) */
    public function allows(?array $accountRoles): bool
    {
        if (! $this->restricted) {
            return true;
        }
        if ($accountRoles === null) {
            return false;
        }

        return $this->roles === [] || array_intersect($this->roles, $accountRoles) !== [];
    }
}
