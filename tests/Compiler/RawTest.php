<?php

declare(strict_types=1);

namespace Stempler\Tests\Compiler;

use Stempler\Compiler\Renderer\CoreRenderer;

class RawTest extends BaseTestCase
{
    protected const RENDERS = [
        CoreRenderer::class,
    ];

    public function testCompileRaw(): void
    {
        $doc = $this->parse('hello world');

        self::assertSame('hello world', $this->compile($doc));
    }
}
