<?php

declare(strict_types=1);

namespace Stempler\Transform\Visitor;

use Stempler\Node\Hidden;
use Stempler\Node\HTML\Tag;
use Stempler\VisitorContext;
use Stempler\VisitorInterface;

final class DefineHidden implements VisitorInterface
{
    private string $hiddenKeyword = 'hidden';

    public function enterNode(mixed $node, VisitorContext $ctx): mixed
    {
        return null;
    }

    public function leaveNode(mixed $node, VisitorContext $ctx): ?Hidden
    {
        if ($node instanceof Tag && \str_starts_with($node->name, $this->hiddenKeyword)) {
            return new Hidden([$node]);
        }

        return null;
    }
}
