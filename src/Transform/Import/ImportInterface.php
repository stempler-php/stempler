<?php

declare(strict_types=1);

namespace Stempler\Transform\Import;

use Stempler\Builder;
use Stempler\Node\Template;
use Stempler\Parser\Context;

interface ImportInterface
{
    public function getContext(): ?Context;

    /**
     * Resolve template by it's name or return null if import does not work
     * for the given name.
     */
    public function resolve(Builder $builder, string $name): ?Template;
}
