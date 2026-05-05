<?php

declare(strict_types=1);

namespace Stempler\Tests\Syntax;

use Stempler\Lexer\Grammar\InlineGrammar;
use Stempler\Node\Inline;
use Stempler\Node\Raw;
use Stempler\Parser\Syntax\InlineSyntax;

class InlineTest extends BaseTestCase
{
    protected const GRAMMARS = [
        InlineGrammar::class => InlineSyntax::class,
    ];

    public function testRaw(): void
    {
        $doc = $this->parse('raw');

        self::assertInstanceOf(Raw::class, $doc->nodes[0]);
        self::assertSame('raw', $doc->nodes[0]->content);
    }

    public function testInline(): void
    {
        $doc = $this->parse('${name}');

        self::assertInstanceOf(Inline::class, $doc->nodes[0]);
        self::assertSame('name', $doc->nodes[0]->name);
        self::assertNull($doc->nodes[0]->value);
    }

    public function testInlineDefault(): void
    {
        $doc = $this->parse('${name|default}');

        self::assertInstanceOf(Inline::class, $doc->nodes[0]);
        self::assertSame('name', $doc->nodes[0]->name);
        self::assertSame('default', $doc->nodes[0]->value);
    }
}
