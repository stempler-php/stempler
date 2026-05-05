<?php

declare(strict_types=1);

namespace Stempler\Tests;

use PHPUnit\Framework\TestCase;
use Stempler\Builder;
use Stempler\Compiler\Renderer\CoreRenderer;
use Stempler\Compiler\Renderer\DynamicRenderer;
use Stempler\Compiler\Renderer\HTMLRenderer;
use Stempler\Compiler\Renderer\PHPRenderer;
use Stempler\Directive\DirectiveGroup;
use Stempler\Lexer\Grammar\DynamicGrammar;
use Stempler\Lexer\Grammar\HTMLGrammar;
use Stempler\Lexer\Grammar\InlineGrammar;
use Stempler\Lexer\Grammar\PHPGrammar;
use Stempler\Loader\LoaderInterface;
use Stempler\Loader\StringLoader;
use Stempler\Parser\Syntax\DynamicSyntax;
use Stempler\Parser\Syntax\HTMLSyntax;
use Stempler\Parser\Syntax\InlineSyntax;
use Stempler\Parser\Syntax\PHPSyntax;

class BuilderTest extends TestCase
{
    public function testRaw(): void
    {
        $builder = $this->getBuilder(new StringLoader());
        $builder->getLoader()->set('home', 'hello world');

        self::assertSame('hello world', $builder->compile('home')->getContent());
    }

    public function testInvalidPath(): void
    {
        $this->expectException(\Stempler\Exception\LoaderException::class);
        $builder = $this->getBuilder(new StringLoader());
        $builder->compile('missing');
    }

    protected function getBuilder(LoaderInterface $loader): Builder
    {
        $builder = new Builder($loader);

        // Grammars
        $builder->getParser()->addSyntax(new PHPGrammar(), new PHPSyntax());
        $builder->getParser()->addSyntax(new InlineGrammar(), new InlineSyntax());
        $builder->getParser()->addSyntax(new DynamicGrammar(), new DynamicSyntax());
        $builder->getParser()->addSyntax(new HTMLGrammar(), new HTMLSyntax());

        $builder->getCompiler()->addRenderer(new CoreRenderer());
        $builder->getCompiler()->addRenderer(new PHPRenderer());
        $builder->getCompiler()->addRenderer(new DynamicRenderer(new DirectiveGroup()));
        $builder->getCompiler()->addRenderer(new HTMLRenderer());

        return $builder;
    }
}
