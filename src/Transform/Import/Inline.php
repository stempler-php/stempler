<?php

declare(strict_types=1);

namespace Stempler\Transform\Import;

use Stempler\Builder;
use Stempler\Node\Template;
use Stempler\Node\Traits\ContextTrait;
use Stempler\Parser\Context;

/**
 * Provides the ability to import block defined in the same template.
 */
final class Inline implements ImportInterface
{
    use ContextTrait;

    public function __construct(
        private readonly string $name,
        private readonly array $nodes,
        ?Context $context = null,
    ) {
        $this->context = $context;
    }

    public function resolve(Builder $builder, string $name): ?Template
    {
        if ($name !== $this->name) {
            return null;
        }

        $tpl = new Template($this->nodes);
        $tpl->setContext($this->context);

        return $tpl;
    }
}
