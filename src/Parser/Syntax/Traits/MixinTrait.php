<?php

declare(strict_types=1);

namespace Stempler\Parser\Syntax\Traits;

use Stempler\Lexer\Token;
use Stempler\Node\Mixin;
use Stempler\Node\Raw;
use Stempler\Parser;
use Stempler\Parser\Assembler;

trait MixinTrait
{
    private function parseToken(Parser $parser, Token $token): Mixin|Raw|string
    {
        if ($token->tokens === []) {
            if ($token->type === Token::TYPE_RAW) {
                return new Raw($token->content);
            }

            return $token->content;
        }

        $mixin = new Mixin([], new Parser\Context($token, $parser->getPath()));
        /**
         * TODO issue #767
         * @link https://github.com/spiral/framework/issues/767
         * @psalm-suppress InvalidArgument
         */
        $parser->parseTokens(
            new Assembler($mixin, 'nodes'),
            $token->tokens,
        );

        return $mixin;
    }
}
