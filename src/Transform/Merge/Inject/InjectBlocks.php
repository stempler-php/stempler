<?php

declare(strict_types=1);

namespace Stempler\Transform\Merge\Inject;

use Stempler\Node\Block;
use Stempler\Transform\BlockClaims;
use Stempler\Transform\QuotedValue;
use Stempler\Traverser;
use Stempler\VisitorContext;
use Stempler\VisitorInterface;

/**
 * Replaces blocks by name.
 */
final class InjectBlocks implements VisitorInterface
{
    public function __construct(
        private readonly BlockClaims $blocks,
    ) {}

    public function enterNode(mixed $node, VisitorContext $ctx): mixed
    {
        return null;
    }

    public function leaveNode(mixed $node, VisitorContext $ctx): mixed
    {
        if (!$node instanceof Block || $node->name === null || !$this->blocks->has($node->name)) {
            return null;
        }

        $inject = $this->blocks->claim($node->name);

        if ($inject instanceof QuotedValue) {
            // exclude quotes
            $inject = $inject->trimValue();
        }

        // mount block:parent content
        if ($node->name !== 'parent') {
            $traverser = new Traverser();
            $traverser->addVisitor(new InjectBlocks(new BlockClaims([
                'parent' => $node->nodes,
            ])));

            $inject = $traverser->traverse($inject);
        }

        $node->nodes = $inject;

        return null;
    }
}
