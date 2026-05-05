<?php

declare(strict_types=1);

namespace Stempler\Compiler;

use Stempler\Compiler;
use Stempler\Node\NodeInterface;

interface RendererInterface
{
    public function render(Compiler $compiler, Result $result, NodeInterface $node): bool;
}
