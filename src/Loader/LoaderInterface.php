<?php

declare(strict_types=1);

namespace Stempler\Loader;

use Stempler\Exception\LoaderException;

interface LoaderInterface
{
    /**
     * Load document content by it's path.
     *
     * @throws LoaderException
     */
    public function load(string $path): Source;
}
