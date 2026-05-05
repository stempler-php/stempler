<?php

declare(strict_types=1);

namespace Stempler\Tests;

use PHPUnit\Framework\TestCase;
use Stempler\Builder;
use Stempler\Compiler\SourceMap;
use Stempler\Loader\DirectoryLoader;
use Stempler\Loader\LoaderInterface;
use Stempler\Loader\StringLoader;
use Stempler\Node\Raw;
use Stempler\Stempler;
use Stempler\StemplerCache;
use Stempler\Transform\Visitor\FlattenNodes;
use Stempler\Transform\Visitor\FormatHTML;
use Stempler\VisitorContext;
use Stempler\VisitorInterface;
use Stempler\Tests\fixtures\ImageDirective;

final class StemplerTest extends TestCase
{
    public function testRenderRawTemplate(): void
    {
        $loader = new StringLoader();
        $loader->set('root', 'hello world');

        self::assertSame('hello world', Stempler::create($loader)->render('root'));
    }

    public function testRenderTemplateWithOutputVariables(): void
    {
        $loader = new StringLoader();
        $loader->set('root', 'Hello, {{ $name }}!');

        self::assertSame('Hello, &lt;John&gt;!', Stempler::create($loader)->render('root', [
            'name' => '<John>',
        ]));
    }

    public function testDefaultConditionalDirective(): void
    {
        $loader = new StringLoader();
        $loader->set('root', '@if($ok)yes@endif');

        $stempler = Stempler::create($loader);

        self::assertSame('yes', $stempler->render('root', ['ok' => true]));
        self::assertSame('', $stempler->render('root', ['ok' => false]));
    }

    public function testDefaultLoopDirective(): void
    {
        $loader = new StringLoader();
        $loader->set('root', '@foreach($items as $item){{ $item }}@endforeach');

        self::assertSame('ab', Stempler::create($loader)->render('root', [
            'items' => ['a', 'b'],
        ]));
    }

    public function testDefaultJsonDirective(): void
    {
        $loader = new StringLoader();
        $loader->set('root', '@json($payload)');

        self::assertSame('{"x":"\u003Ctag\u003E"}', Stempler::create($loader)->render('root', [
            'payload' => ['x' => '<tag>'],
        ]));
    }

    public function testRenderImportFixture(): void
    {
        $output = $this->makeFixtureStempler()->render('bundle-import');

        self::assertSame('<a href="abc">cde</a>', \trim($output));
    }

    public function testRenderExtendsFixture(): void
    {
        $output = $this->makeFixtureStempler()->render('demo-import', [
            'url' => 'https://example.com',
        ]);

        self::assertStringContainsString('<!DOCTYPE html>', $output);
        self::assertStringContainsString('<a href="https://example.com"></a>', $output);
        self::assertStringContainsString('hello world', $output);
    }

    public function testRenderStacks(): void
    {
        $loader = new StringLoader();
        $loader->set(
            'root',
            '<stack:collect name="css"/><stack:push name="css">a</stack:push><stack:prepend name="css">b</stack:prepend>',
        );

        self::assertSame('ba', Stempler::create($loader)->render('root'));
    }

    public function testCustomDirectiveCanBeInjected(): void
    {
        $loader = new StringLoader();
        $loader->set('root', '@image("blog", "test.png", "150|250", "webp")');

        self::assertSame(
            '<img title="blog" src="test.png" size="150|250" type="webp">',
            Stempler::create($loader, [new ImageDirective()])->render('root'),
        );
    }

    public function testCustomVisitorCanBeInjected(): void
    {
        $loader = new StringLoader();
        $loader->set('root', 'hello');

        $visitor = new class implements VisitorInterface {
            public function enterNode(mixed $node, VisitorContext $ctx): mixed
            {
                return null;
            }

            public function leaveNode(mixed $node, VisitorContext $ctx): mixed
            {
                if ($node instanceof Raw) {
                    $node->content = \strtoupper((string) $node->content);
                }

                return null;
            }
        };

        $stempler = Stempler::create($loader, visitors: [
            Builder::STAGE_COMPILE => [$visitor],
        ]);

        self::assertSame('HELLO', $stempler->render('root'));
    }

    public function testMakeSourceMapReturnsFixturePaths(): void
    {
        $map = $this->makeFixtureStempler()->makeSourceMap('demo-import');

        self::assertInstanceOf(SourceMap::class, $map);
        self::assertContains($this->fixturePath('demo-import.dark.php'), $map->getPaths());
        self::assertContains($this->fixturePath('layout/base.dark.php'), $map->getPaths());
        self::assertContains($this->fixturePath('import/block.dark.php'), $map->getPaths());
        self::assertContains($this->fixturePath('import/url.dark.php'), $map->getPaths());
    }

    public function testPrettyPrintVisitorsCanBeUsedFromCore(): void
    {
        $loader = new StringLoader();
        $loader->set(
            'root',
            "<div>\n    <block:name>\n        hello\n    </block:name>\n</div>",
        );

        $stempler = Stempler::create($loader, visitors: [
            Builder::STAGE_COMPILE => [
                new FlattenNodes(),
                new FormatHTML(),
            ],
        ]);

        self::assertSame("<div>\n  hello\n</div>", $stempler->render('root'));
    }

    public function testRenderUsesCacheForFilesystemTemplates(): void
    {
        $templates = $this->makeTempDirectory();
        $cache = $this->makeTempDirectory();
        \file_put_contents($templates . '/root.dark.php', 'Hello, {{ $name }}!');

        $counter = new class implements VisitorInterface {
            public int $count = 0;

            public function enterNode(mixed $node, VisitorContext $ctx): mixed
            {
                return null;
            }

            public function leaveNode(mixed $node, VisitorContext $ctx): mixed
            {
                if ($node instanceof \Stempler\Node\Template) {
                    $this->count++;
                }

                return null;
            }
        };

        try {
            $stempler = Stempler::create(
                new DirectoryLoader($templates),
                visitors: [Builder::STAGE_COMPILE => [$counter]],
                cache: new StemplerCache($cache),
            );

            self::assertSame('Hello, John!', $stempler->render('root', ['name' => 'John']));
            self::assertSame('Hello, Jane!', $stempler->render('root', ['name' => 'Jane']));

            self::assertSame(1, $counter->count);
            self::assertCount(2, \glob($cache . '/*.php') ?: []);
        } finally {
            $this->removeDirectory($templates);
            $this->removeDirectory($cache);
        }
    }

    public function testRenderSkipsCacheForNonFilesystemTemplates(): void
    {
        $cache = $this->makeTempDirectory();
        $loader = new StringLoader();
        $loader->set('root', 'Hello, {{ $name }}!');

        $counter = new class implements VisitorInterface {
            public int $count = 0;

            public function enterNode(mixed $node, VisitorContext $ctx): mixed
            {
                return null;
            }

            public function leaveNode(mixed $node, VisitorContext $ctx): mixed
            {
                if ($node instanceof \Stempler\Node\Template) {
                    $this->count++;
                }

                return null;
            }
        };

        try {
            $stempler = Stempler::create(
                $loader,
                visitors: [Builder::STAGE_COMPILE => [$counter]],
                cache: new StemplerCache($cache),
            );

            self::assertSame('Hello, John!', $stempler->render('root', ['name' => 'John']));
            self::assertSame('Hello, Jane!', $stempler->render('root', ['name' => 'Jane']));

            self::assertSame(2, $counter->count);
            self::assertCount(0, \glob($cache . '/*.php') ?: []);
        } finally {
            $this->removeDirectory($cache);
        }
    }

    public function testResetDeletesFilesystemTemplateCache(): void
    {
        $templates = $this->makeTempDirectory();
        $cache = $this->makeTempDirectory();
        \file_put_contents($templates . '/root.dark.php', 'Hello, {{ $name }}!');

        try {
            $stempler = Stempler::create(
                new DirectoryLoader($templates),
                cache: new StemplerCache($cache),
            );

            self::assertSame('Hello, John!', $stempler->render('root', ['name' => 'John']));
            self::assertCount(2, \glob($cache . '/*.php') ?: []);

            $stempler->reset('root');

            self::assertCount(0, \glob($cache . '/*.php') ?: []);
        } finally {
            $this->removeDirectory($templates);
            $this->removeDirectory($cache);
        }
    }

    private function makeFixtureStempler(): Stempler
    {
        return Stempler::create($this->getFixtureLoader());
    }

    private function getFixtureLoader(): LoaderInterface
    {
        return new DirectoryLoader(__DIR__ . '/fixtures');
    }

    private function fixturePath(string $path): string
    {
        return __DIR__ . '/fixtures/' . $path;
    }

    private function makeTempDirectory(): string
    {
        $directory = \sys_get_temp_dir() . '/stempler-test-' . \bin2hex(\random_bytes(8));

        if (!\mkdir($directory) && !\is_dir($directory)) {
            throw new \RuntimeException(\sprintf('Unable to create temp directory `%s`.', $directory));
        }

        return $directory;
    }

    private function removeDirectory(string $directory): void
    {
        if (!\is_dir($directory)) {
            return;
        }

        $entries = \scandir($directory);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . '/' . $entry;
            if (\is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            \unlink($path);
        }

        \rmdir($directory);
    }
}
