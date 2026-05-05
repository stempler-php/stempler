<?php

declare(strict_types=1);

namespace Stempler\Transform\Finalizer;

use Stempler\Node\Block;
use Stempler\VisitorContext;
use Stempler\VisitorInterface;

/**
 * Isolate blocks defined by specific path.
 */
final class IsolateBlocks implements VisitorInterface
{
    public function __construct(
        private readonly string $path,
    ) {}

    public function enterNode(mixed $node, VisitorContext $ctx): mixed
    {
        if ($node instanceof Block && $node->getContext()->getPath() === $this->path) {
            $node->name = null;
        }

        return null;
    }

    public function leaveNode(mixed $node, VisitorContext $ctx): mixed
    {
        return null;
    }
}
