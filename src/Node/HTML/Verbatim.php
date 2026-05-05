<?php

declare(strict_types=1);

namespace Stempler\Node\HTML;

use Stempler\Node\AttributedInterface;
use Stempler\Node\NodeInterface;
use Stempler\Node\Traits\AttributeTrait;
use Stempler\Node\Traits\ContextTrait;
use Stempler\Parser\Context;

/**
 * Non HTML codebase (JS or CSS).
 *
 * @implements NodeInterface<Verbatim>
 * @template TNode of NodeInterface
 */
final class Verbatim implements NodeInterface, AttributedInterface
{
    use ContextTrait;
    use AttributeTrait;

    /**
     * @var TNode[]|non-empty-string[]
     */
    public array $nodes = [];

    public function __construct(?Context $context = null)
    {
        $this->context = $context;
    }

    /**
     * @psalm-suppress ImplementedReturnTypeMismatch
     * @return \Generator<'nodes', array<array-key, TNode|non-empty-string>, mixed, void>
     */
    public function getIterator(): \Generator
    {
        yield 'nodes' => $this->nodes;
    }
}
