<?php

declare(strict_types=1);

namespace Stempler\Exception;

use Stempler\Exception\Traits\ContextTrait;

class ParserException extends \RuntimeException implements ContextExceptionInterface
{
    use ContextTrait;
}
