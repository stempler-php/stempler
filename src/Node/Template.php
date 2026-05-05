<?php

declare(strict_types=1);

namespace Stempler\Node;

use Stempler\Node\Traits\AttributeTrait;
use Stempler\Node\Traits\ContextTrait;
use Stempler\Parser\Context;

/**
 * Top level template node.
 *
 * @implements NodeInterface<Template>
 * @template TNode of NodeInterface
 */
final class Template implements NodeInterface, AttributedInterface
{
    use AttributeTrait;
    use ContextTrait;

    /**
     * @param list<TNode> $nodes
     */
    public function __construct(
        public array $nodes = [],
    ) {}

    public function setContext(?Context $context = null): void
    {
        $this->context = $context;
    }

    public function getIterator(): \Generator
    {
        yield 'nodes' => $this->nodes;
    }
}
