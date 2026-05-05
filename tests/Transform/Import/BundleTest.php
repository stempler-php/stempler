<?php

declare(strict_types=1);

namespace Stempler\Tests\Transform\Import;

use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use Stempler\Builder;
use Stempler\Loader\LoaderInterface;
use Stempler\Loader\Source;
use Stempler\Node\Template;
use Stempler\Transform\Context\ImportContext;
use Stempler\Transform\Import\Bundle;
use Stempler\Transform\Import\ImportInterface;
use Stempler\VisitorContext;
use Stempler\VisitorInterface;
use Stempler\Tests\Transform\BaseTestCase;

final class BundleTest extends BaseTestCase
{
    use m\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public static function wrongNamespaceProvider(): iterable
    {
        yield ['span'];
        yield ['abcd:span'];
        yield ['test1:span'];
        yield ['abc:span'];
        yield ['tes:span'];
    }

    public static function correctNamespaceProvider(): iterable
    {
        yield ['test.span'];
        yield ['test:span'];
        yield ['test/span'];
    }

    #[DataProvider('wrongNamespaceProvider')]
    public function testResolveTagWithWrongNamespace(string $tag): void
    {
        $bundle = new Bundle('path/to/dir', 'test');
        $loader = m::mock(LoaderInterface::class);

        $loader
            ->shouldReceive('load')
            ->once()
            ->with('path/to/dir')
            ->andReturn(new Source('<span></span>'));

        $builder = new Builder($loader);

        $builder->addVisitor(
            new class implements VisitorInterface {
                public function enterNode(mixed $node, VisitorContext $ctx): mixed
                {
                    $n = $ctx->getCurrentNode();
                    if ($n instanceof Template) {
                        $import = m::mock(ImportInterface::class);
                        $import->shouldNotReceive('resolve');
                        $n->setAttribute(ImportContext::class, [$import]);
                    }

                    return $node;
                }

                public function leaveNode(mixed $node, VisitorContext $ctx): mixed
                {
                    return $node;
                }
            },
        );

        self::assertNull(
            $bundle->resolve($builder, $tag),
        );
    }

    #[DataProvider('correctNamespaceProvider')]
    public function testResolveTagWithCorrectNamespace(string $tag): void
    {
        $bundle = new Bundle('path/to/dir', 'test');
        $loader = m::mock(LoaderInterface::class);

        $loader
            ->shouldReceive('load')
            ->once()
            ->with('path/to/dir')
            ->andReturn(new Source('<span></span>'));

        $builder = new Builder($loader);
        $template = new Template();

        $builder->addVisitor(
            new class($builder, $template) implements VisitorInterface {
                public function __construct(
                    private readonly Builder $builder,
                    private readonly Template $template,
                ) {}

                public function enterNode(mixed $node, VisitorContext $ctx): mixed
                {
                    $n = $ctx->getCurrentNode();
                    if ($n instanceof Template) {
                        $import = m::mock(ImportInterface::class);
                        $import
                            ->shouldReceive('resolve')
                            ->once()
                            ->with($this->builder, 'span')
                            ->andReturn($this->template);
                        $n->setAttribute(ImportContext::class, [$import]);
                    }

                    return $node;
                }

                public function leaveNode(mixed $node, VisitorContext $ctx): mixed
                {
                    return $node;
                }
            },
        );

        self::assertSame(
            $template,
            $bundle->resolve($builder, $tag),
        );
    }
}
