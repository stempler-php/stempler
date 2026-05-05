<?php

declare(strict_types=1);

namespace Stempler\Parser\Syntax;

use Stempler\Lexer\Token;
use Stempler\Node\PHP;
use Stempler\Parser;
use Stempler\Parser\Assembler;
use Stempler\Parser\SyntaxInterface;

/**
 * Registers PHP blocks.
 */
final class PHPSyntax implements SyntaxInterface
{
    public function handle(Parser $parser, Assembler $asm, Token $token): void
    {
        $asm->push(new PHP(
            $token->content,
            $token->tokens,
            new Parser\Context($token, $parser->getPath()),
        ));
    }
}
