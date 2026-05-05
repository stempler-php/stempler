<?php

declare(strict_types=1);

namespace Stempler\Tests\Directive;

use Stempler\Directive\PHPDirective;

class PHPTest extends BaseTestCase
{
    protected const DIRECTIVES = [
        PHPDirective::class,
    ];

    public function testPHP(): void
    {
        $doc = $this->parse('@php echo 1; @endphp');

        self::assertSame('<?php echo 1; ?>', $this->compile($doc));
    }
}
