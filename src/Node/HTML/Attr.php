<?php

declare(strict_types=1);

namespace Stempler\Node\HTML;

use Stempler\Node\Mixin;
use Stempler\Node\NodeInterface;
use Stempler\Node\Traits\ContextTrait;
use Stempler\Parser\Context;

/**
 * Represents single node/tag attribute and it's value.
 *
 * @implements NodeInterface<Attr>
 */
final class Attr implements NodeInterface
{
    use ContextTrait;

    public function __construct(
        public Mixin|string $name,
        public mixed $value,
        ?Context $context = null,
    ) {
        $this->context = $context;
    }

    public function getIterator(): \Generator
    {
        yield 'name' => $this->name;
        yield 'value' => $this->value;
    }
}
