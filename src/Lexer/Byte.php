<?php

declare(strict_types=1);

namespace Stempler\Lexer;

final class Byte
{
    public function __construct(
        public int $offset,
        public string $char,
    ) {}
}
