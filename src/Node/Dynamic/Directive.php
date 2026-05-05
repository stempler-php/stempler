<?php

declare(strict_types=1);

namespace Stempler\Node\Dynamic;

use Stempler\Node\NodeInterface;
use Stempler\Node\Traits\ContextTrait;
use Stempler\Parser\Context;

/**
 * @implements NodeInterface<Directive>
 */
final class Directive implements NodeInterface
{
    use ContextTrait;

    public string $name;
    public ?string $body = null;
    public array $values = [];

    public function __construct(?Context $context = null)
    {
        $this->context = $context;
    }

    public function getIterator(): \Generator
    {
        yield from [];
    }
}
