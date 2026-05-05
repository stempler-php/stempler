<?php

declare(strict_types=1);

namespace Stempler\Tests\Compiler;

use PHPUnit\Framework\TestCase;
use Stempler\Compiler;
use Stempler\Lexer\StringStream;
use Stempler\Node\Template;
use Stempler\Parser;

abstract class BaseTestCase extends TestCase
{
    protected const GRAMMARS = [
        /* GRAMMAR => SYNTAX */
    ];
    protected const RENDERS = [
        /* RENDERER */
    ];

    protected function compile(Template $document): string
    {
        $compiler = new Compiler();
        foreach (static::RENDERS as $renderer) {
            $compiler->addRenderer(new $renderer());
        }

        return $compiler->compile($document)->getContent();
    }

    protected function parse(string $string): Template
    {
        $parser = new Parser();

        foreach (static::GRAMMARS as $grammar => $syntax) {
            $parser->addSyntax(new $grammar(), new $syntax());
        }

        return $parser->parse(new StringStream($string));
    }
}
