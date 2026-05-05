<?php

declare(strict_types=1);

namespace Stempler\Compiler\Renderer;

use Stempler\Compiler;
use Stempler\Compiler\RendererInterface;
use Stempler\Node\Aggregate;
use Stempler\Node\Block;
use Stempler\Node\Hidden;
use Stempler\Node\Mixin;
use Stempler\Node\NodeInterface;
use Stempler\Node\Raw;
use Stempler\Node\Template;

final class CoreRenderer implements RendererInterface
{
    public function render(
        Compiler $compiler,
        Compiler\Result $result,
        NodeInterface $node,
    ): bool {
        switch (true) {
            case $node instanceof Hidden:
                return true;

            case $node instanceof Template || $node instanceof Block || $node instanceof Aggregate:
                $result->withinContext(
                    $node->getContext(),
                    static function (Compiler\Result $source) use ($node, $compiler): void {
                        foreach ($node->nodes as $child) {
                            $compiler->compile($child, $source);
                        }
                    },
                );

                return true;

            case $node instanceof Mixin:
                $result->withinContext(
                    $node->getContext(),
                    static function (Compiler\Result $source) use ($node, $compiler): void {
                        foreach ($node->nodes as $child) {
                            if (\is_string($child)) {
                                $source->push($child, null);
                                continue;
                            }

                            $compiler->compile($child, $source);
                        }
                    },
                );

                return true;

            case $node instanceof Raw:
                $result->push($node->content, $node->getContext());

                return true;

            default:
                return false;
        }
    }
}
