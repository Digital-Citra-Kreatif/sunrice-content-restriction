<?php

declare(strict_types=1);

arch('every file declares strict types')
    ->expect('Sunrice\ContentRestriction')
    ->toUseStrictTypes();

arch('no debugging left behind')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();
