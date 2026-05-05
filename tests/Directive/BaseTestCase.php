<?php

declare(strict_types=1);

namespace Stempler\Tests\Directive;

use Stempler\Compiler;
use Stempler\Compiler\Renderer\CoreRenderer;
use Stempler\Compiler\Renderer\HTMLRenderer;
use Stempler\Directive\DirectiveGroup;
use Stempler\Lexer\Grammar\DynamicGrammar;
use Stempler\Lexer\Grammar\HTMLGrammar;
use Stempler\Node\Template;
use Stempler\Parser\Syntax\DynamicSyntax;
use Stempler\Parser\Syntax\HTMLSyntax;

abstract class BaseTestCase extends \Stempler\Tests\Compiler\BaseTestCase
{
    protected const RENDERS = [
        CoreRenderer::class,
        HTMLRenderer::class,
    ];
    protected const GRAMMARS = [
        DynamicGrammar::class => DynamicSyntax::class,
        HTMLGrammar::class    => HTMLSyntax::class,
    ];
    protected const DIRECTIVES = [];

    protected function compile(Template $document): string
    {
        $compiler = new Compiler();
        foreach (static::RENDERS as $renderer) {
            $compiler->addRenderer(new $renderer());
        }

        $directiveGroup = new DirectiveGroup();
        foreach (static::DIRECTIVES as $directive) {
            $directiveGroup->addDirective(new $directive());
        }

        $compiler->addRenderer(new Compiler\Renderer\DynamicRenderer($directiveGroup));

        return $compiler->compile($document)->getContent();
    }
}
