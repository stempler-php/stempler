<?php

declare(strict_types=1);

namespace Stempler\Parser\Syntax;

use Stempler\Lexer\Token;
use Stempler\Node\Raw;
use Stempler\Parser;
use Stempler\Parser\Assembler;
use Stempler\Parser\SyntaxInterface;

/**
 * Register simple text inclusions.
 */
final class RawSyntax implements SyntaxInterface
{
    public function handle(Parser $parser, Assembler $asm, Token $token): void
    {
        $asm->push(new Raw(
            $token->content,
            new Parser\Context($token, $parser->getPath()),
        ));
    }
}
