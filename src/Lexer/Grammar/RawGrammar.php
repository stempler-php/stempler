<?php

declare(strict_types=1);

namespace Stempler\Lexer\Grammar;

use Stempler\Lexer\Buffer;
use Stempler\Lexer\Byte;
use Stempler\Lexer\GrammarInterface;
use Stempler\Lexer\Token;

final class RawGrammar implements GrammarInterface
{
    /**
     * @codeCoverageIgnore
     */
    public static function tokenName(int $token): string
    {
        return 'RAW:RAW';
    }

    /**
     * @return \Generator<int, Byte|Token|null>
     */
    public function parse(Buffer $src): \Generator
    {
        /** @var string|null $buffer */
        $buffer = null;
        $bufferOffset = 0;

        foreach ($src as $n) {
            if ($n instanceof Byte) {
                if ($buffer === null) {
                    $buffer = '';
                    $bufferOffset = $n->offset;
                }

                $buffer .= $n->char;
                continue;
            }

            if ($buffer !== null) {
                yield new Token(Token::TYPE_RAW, $bufferOffset, $buffer);
                $buffer = null;
            }

            yield $n;
        }

        if ($buffer !== null) {
            yield new Token(Token::TYPE_RAW, $bufferOffset, $buffer);
        }
    }
}
