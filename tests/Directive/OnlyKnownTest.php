<?php

declare(strict_types=1);

namespace Stempler\Tests\Directive;

use Stempler\Directive\DirectiveGroup;
use Stempler\Directive\LoopDirective;
use Stempler\Lexer\Grammar\DynamicGrammar;
use Stempler\Lexer\Grammar\HTMLGrammar;
use Stempler\Lexer\StringStream;
use Stempler\Node\Template;
use Stempler\Parser;
use Stempler\Parser\Syntax\HTMLSyntax;

class OnlyKnownTest extends BaseTestCase
{
    protected const DIRECTIVES = [
        LoopDirective::class,
    ];

    public function testForeachEndForeach(): void
    {
        $doc = $this->parse('@foreach($users as $u) {{ $u->name }} @endforeach @hello after');

        self::assertSame('<?php foreach($users as $u): ?> <?php echo htmlspecialchars'
        . "((string) (\$u->name), ENT_QUOTES | ENT_SUBSTITUTE, 'utf-8'); ?> <?php endforeach; ?> @hello after", $this->compile($doc));
    }

    protected function parse(string $string): Template
    {
        $parser = new Parser();

        $directives = new DirectiveGroup();
        $directives->addDirective(new LoopDirective());

        $parser->addSyntax(new DynamicGrammar($directives), new Parser\Syntax\DynamicSyntax());
        $parser->addSyntax(new HTMLGrammar(), new HTMLSyntax());

        return $parser->parse(new StringStream($string));
    }
}
