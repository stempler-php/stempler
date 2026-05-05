<?php

declare(strict_types=1);

namespace Stempler\Compiler\Renderer;

use Stempler\Compiler;
use Stempler\Directive\DirectiveRendererInterface;
use Stempler\Exception\DirectiveException;
use Stempler\Node\Dynamic\Directive;
use Stempler\Node\Dynamic\Output;
use Stempler\Node\NodeInterface;

final class DynamicRenderer implements Compiler\RendererInterface
{
    // default output filter
    public const DEFAULT_FILTER = "htmlspecialchars((string) (%s), ENT_QUOTES | ENT_SUBSTITUTE, 'utf-8')";

    public function __construct(
        private readonly ?DirectiveRendererInterface $directiveRenderer = null,
        private readonly string $defaultFilter = self::DEFAULT_FILTER,
    ) {}

    public function render(Compiler $compiler, Compiler\Result $result, NodeInterface $node): bool
    {
        switch (true) {
            case $node instanceof Output:
                $this->output($result, $node);
                return true;
            case $node instanceof Directive:
                $this->directive($result, $node);
                return true;
            default:
                return false;
        }
    }

    /**
     * @throws DirectiveException
     */
    private function directive(Compiler\Result $source, Directive $directive): void
    {
        if ($this->directiveRenderer !== null) {
            $result = $this->directiveRenderer->render($directive);
            if ($result !== null) {
                $source->push($result, $directive->getContext());
                return;
            }
        }

        throw new DirectiveException(
            \sprintf('Undefined directive `%s`', $directive->name),
            $directive->getContext(),
        );
    }

    private function output(Compiler\Result $source, Output $output): void
    {
        if ($output->rawOutput) {
            $source->push(\sprintf('<?php echo %s; ?>', \trim((string) $output->body)), $output->getContext());
            return;
        }

        $filter = $output->filter ?? $this->defaultFilter;

        $source->push(
            \sprintf(\sprintf('<?php echo %s; ?>', $filter), \trim((string) $output->body)),
            $output->getContext(),
        );
    }
}
