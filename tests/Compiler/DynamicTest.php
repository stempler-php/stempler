<?php

declare(strict_types=1);

namespace Stempler\Tests\Compiler;

use Stempler\Compiler\Renderer\CoreRenderer;
use Stempler\Compiler\Renderer\DynamicRenderer;
use Stempler\Compiler\Renderer\HTMLRenderer;
use Stempler\Lexer\Grammar\DynamicGrammar;
use Stempler\Lexer\Grammar\HTMLGrammar;
use Stempler\Parser\Syntax\DynamicSyntax;
use Stempler\Parser\Syntax\HTMLSyntax;

class DynamicTest extends BaseTestCase
{
    protected const RENDERS = [
        CoreRenderer::class,
        HTMLRenderer::class,
        DynamicRenderer::class,
    ];
    protected const GRAMMARS = [
        DynamicGrammar::class => DynamicSyntax::class,
        HTMLGrammar::class    => HTMLSyntax::class,
    ];

    public function testOutput(): void
    {
        $doc = $this->parse('{{ $name }}');

        self::assertSame("<?php echo htmlspecialchars((string) (\$name), ENT_QUOTES | ENT_SUBSTITUTE, 'utf-8'); ?>", $this->compile($doc));
    }

    public function testOutputEscapeOptions(): void
    {
        $doc = $this->parse('{{ $name }}');

        $doc->nodes[0]->filter = 'e(%s)';

        self::assertSame('<?php echo e($name); ?>', $this->compile($doc));
    }
}
