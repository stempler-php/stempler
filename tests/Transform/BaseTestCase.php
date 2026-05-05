<?php

declare(strict_types=1);

namespace Stempler\Tests\Transform;

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
use Stempler\Loader\DirectoryLoader;
use Stempler\Loader\LoaderInterface;
use Stempler\Loader\StringLoader;
use Stempler\Parser\Syntax\DynamicSyntax;
use Stempler\Parser\Syntax\HTMLSyntax;
use Stempler\Parser\Syntax\InlineSyntax;
use Stempler\Parser\Syntax\PHPSyntax;

abstract class BaseTestCase extends TestCase
{
    protected function compile(string $source, array $visitors = [], ?LoaderInterface $loader = null)
    {
        if ($loader === null) {
            $loader = new StringLoader();
            $loader->set('root', $source);
        }

        $builder = $this->getBuilder($loader, $visitors);

        return $builder->compile('root');
    }

    protected function parse(string $source, array $visitors = [], ?LoaderInterface $loader = null)
    {
        $loader ??= new StringLoader();
        $loader->set('root', $source);

        $builder = $this->getBuilder($loader, $visitors);

        return $builder->load('root');
    }

    protected function getBuilder(LoaderInterface $loader, array $visitors): Builder
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

        foreach ($this->getVisitors() as $visitor) {
            $builder->addVisitor($visitor);
        }

        foreach ($visitors as $visitor) {
            $builder->addVisitor($visitor);
        }

        return $builder;
    }

    protected function getVisitors(): array
    {
        return [];
    }

    protected function getFixtureLoader(): LoaderInterface
    {
        return new DirectoryLoader(__DIR__ . '/../fixtures');
    }
}
