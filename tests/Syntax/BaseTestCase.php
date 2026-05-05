<?php

declare(strict_types=1);

namespace Stempler\Tests\Syntax;

use PHPUnit\Framework\TestCase;
use Stempler\Lexer;
use Stempler\Node\Template;
use Stempler\Parser;

abstract class BaseTestCase extends TestCase
{
    protected const GRAMMARS = [
        /* GRAMMAR => SYNTAX */
    ];

    protected function parse(string $string): Template
    {
        $parser = new Parser();

        foreach (static::GRAMMARS as $grammar => $syntax) {
            $parser->addSyntax(new $grammar(), new $syntax());
        }

        return $parser->parse(new Lexer\StringStream($string));
    }
}
