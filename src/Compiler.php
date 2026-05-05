<?php

declare(strict_types=1);

namespace Stempler;

use Stempler\Compiler\RendererInterface;
use Stempler\Compiler\Result;
use Stempler\Exception\CompilerException;
use Stempler\Node\NodeInterface;

/**
 * Recursively compile node tree using set of handlers.
 */
final class Compiler
{
    /** @var RendererInterface[] */
    private array $renders = [];

    public function addRenderer(RendererInterface $renderer): void
    {
        $this->renders[] = $renderer;
    }

    public function compile(array|NodeInterface $node, ?Result $result = null): Result
    {
        $result ??= new Result();

        if (\is_array($node)) {
            foreach ($node as $child) {
                $this->compile($child, $result);
            }

            return $result;
        }

        foreach ($this->renders as $renderer) {
            if ($renderer->render($this, $result, $node)) {
                return $result;
            }
        }

        throw new CompilerException(
            \sprintf('Unable to compile %s, no renderer found', $node::class),
            $node->getContext(),
        );
    }
}
