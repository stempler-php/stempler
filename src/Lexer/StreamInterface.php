<?php

declare(strict_types=1);

namespace Stempler\Lexer;

interface StreamInterface
{
    public function getOffset(): int;

    public function peak(): ?string;

    public function isEOI(): bool;
}
