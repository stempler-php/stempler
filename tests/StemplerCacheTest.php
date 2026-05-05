<?php

declare(strict_types=1);

namespace Spiral\Tests\Stempler;

use PHPUnit\Framework\TestCase;
use Spiral\Stempler\StemplerCache;

final class StemplerCacheTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = \sys_get_temp_dir() . '/stempler-cache-' . \bin2hex(\random_bytes(8));
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->directory);
        unset($GLOBALS['__stempler_cache_test']);
    }

    public function testWriteCreatesCacheAndMapFiles(): void
    {
        $dependency = $this->directory . '/dependency.php';
        $this->writeFile($dependency, '<?php return true;');

        $cache = new StemplerCache($this->directory);
        $cache->write('demo', '<?php return 42;', [$dependency]);

        self::assertFileExists($this->directory . '/demo.php');
        self::assertFileExists($this->directory . '/demo-map.php');
        self::assertTrue($cache->isFresh('demo'));

        if (\DIRECTORY_SEPARATOR === '/') {
            self::assertSame(0666, \fileperms($this->directory . '/demo.php') & 0777);
            self::assertSame(0666, \fileperms($this->directory . '/demo-map.php') & 0777);
            self::assertSame(0777, \fileperms($this->directory) & 0777);
        }
    }

    public function testWriteCreatesCacheAndMapFilesForNestedKey(): void
    {
        $cache = new StemplerCache($this->directory);
        $cache->write('nested/demo', '<?php return 42;');

        self::assertFileExists($this->directory . '/nested/demo.php');
        self::assertFileExists($this->directory . '/nested/demo-map.php');
        self::assertTrue($cache->isFresh('nested/demo'));

        if (\DIRECTORY_SEPARATOR === '/') {
            self::assertSame(0777, \fileperms($this->directory . '/nested') & 0777);
        }
    }

    public function testWriteThrowsWhenCacheDirectoryCannotBePrepared(): void
    {
        $this->writeFile($this->directory . '/nested', '<?php return true;');

        $cache = new StemplerCache($this->directory);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Unable to prepare cache directory for `%s`.',
            $this->directory . '/nested/demo.php',
        ));

        $cache->write('nested/demo', '<?php return 42;');
    }

    public function testIsFreshReturnsFalseWhenDependencyIsNewer(): void
    {
        $dependency = $this->directory . '/dependency.php';
        $this->writeFile($dependency, '<?php return true;');

        $cache = new StemplerCache($this->directory);
        $cache->write('demo', '<?php return 42;', [$dependency]);

        \touch($dependency, \time() + 5);

        self::assertFalse($cache->isFresh('demo'));
    }

    public function testDeleteRemovesCacheFiles(): void
    {
        $cache = new StemplerCache($this->directory);
        $cache->write('demo', '<?php return 42;');

        $cache->delete('demo');

        self::assertFileDoesNotExist($this->directory . '/demo.php');
        self::assertFileDoesNotExist($this->directory . '/demo-map.php');
    }

    public function testDeleteThrowsWhenCacheFileCannotBeDeleted(): void
    {
        \mkdir($this->directory, 0777, true);
        \mkdir($this->directory . '/demo.php', 0777);

        $cache = new StemplerCache($this->directory);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Unable to delete cache file `%s`.',
            $this->directory . '/demo.php',
        ));

        $cache->delete('demo');
    }

    public function testIsFreshThrowsWhenCacheFileIsMissingButMapExists(): void
    {
        $cache = new StemplerCache($this->directory);
        $cache->write('demo', '<?php return 42;');

        \unlink($this->directory . '/demo.php');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf(
            "File '%s' not found",
            $this->directory . '/demo.php',
        ));

        $cache->isFresh('demo');
    }

    public function testLoadIncludesCachedTemplateOnce(): void
    {
        $cache = new StemplerCache($this->directory);
        $cache->write(
            'demo',
            <<<'PHP'
<?php
$GLOBALS['__stempler_cache_test'][] = 'loaded';
PHP,
        );

        $cache->load('demo');
        $cache->load('demo');

        self::assertSame(['loaded'], $GLOBALS['__stempler_cache_test']);
    }

    private function writeFile(string $filename, string $content): void
    {
        $directory = \dirname($filename);
        if (!\is_dir($directory)) {
            \mkdir($directory, 0777, true);
        }

        \file_put_contents($filename, $content);
    }

    private function deleteDirectory(string $directory): void
    {
        if (!\is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                \rmdir($item->getPathname());
                continue;
            }

            \unlink($item->getPathname());
        }

        \rmdir($directory);
    }
}
