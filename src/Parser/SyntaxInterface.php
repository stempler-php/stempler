<?php

declare(strict_types=1);

namespace Stempler\Parser;

use Stempler\Exception\SyntaxException;
use Stempler\Lexer\Token;
use Stempler\Parser;

interface SyntaxInterface
{
    /**
     * @throws SyntaxException
     */
    public function handle(Parser $parser, Assembler $asm, Token $token): void;
}
