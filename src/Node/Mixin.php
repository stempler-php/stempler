<?php

declare(strict_types=1);

namespace Stempler\Node;

use Stempler\Node\Traits\ContextTrait;
use Stempler\Parser\Context;

/**
 * Combines
 *
 * @implements NodeInterface<Mixin>
 * @template TNode of NodeInterface
 */
final class Mixin implements NodeInterface
{
    use ContextTrait;

    /**
     * @param array<array-key, TNode|string> $nodes
     */
    public function __construct(
        public array $nodes = [],
        ?Context $context = null,
    ) {
        $this->context = $context;
    }

    /**
     * @psalm-suppress ImplementedReturnTypeMismatch
     * @return \Generator<'nodes', array<array-key, TNode|string>, mixed, void>
     */
    public function getIterator(): \Generator
    {
        yield 'nodes' => $this->nodes;
    }
}
