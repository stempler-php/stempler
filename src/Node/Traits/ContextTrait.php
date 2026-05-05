<?php

declare(strict_types=1);

namespace Stempler\Node\Traits;

use Stempler\Parser\Context;

trait ContextTrait
{
    private ?Context $context = null;

    public function getContext(): ?Context
    {
        return $this->context;
    }
}
