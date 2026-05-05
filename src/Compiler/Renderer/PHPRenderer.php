<?php

declare(strict_types=1);

namespace Stempler\Compiler\Renderer;

use Stempler\Compiler;
use Stempler\Compiler\RendererInterface;
use Stempler\Node\NodeInterface;
use Stempler\Node\PHP;

final class PHPRenderer implements RendererInterface
{
    public function render(Compiler $compiler, Compiler\Result $result, NodeInterface $node): bool
    {
        if ($node instanceof PHP) {
            $result->push($node->content, $node->getContext());
            return true;
        }

        return false;
    }
}
