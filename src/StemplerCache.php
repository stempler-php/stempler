<?php

declare(strict_types=1);

namespace Spiral\Stempler;

use RuntimeException;
use function array_reverse;
use function basename;
use function chmod;
use function clearstatcache;
use function dirname;
use function file_exists;
use function file_put_contents;
use function filemtime;
use function fileperms;
use function is_dir;
use function mkdir;
use function sprintf;
use function unlink;
use function var_export;

final class StemplerCache
{
    private const FILE_MODE = 0666;

    public function __construct(
        private readonly string $directory,
    ) {}

    /**
     * Store template into cache and write invalidation map file.
     *
     * @param list<string> $paths
     */
    public function write(string $key, string $content, array $paths = []): void
    {
        $filename = $this->filename($key);
        if (!$this->ensureDirectory(dirname($filename))) {
            throw new RuntimeException(sprintf(
                'Unable to prepare cache directory for `%s`.',
                $filename,
            ));
        }

        if (!$this->storeFile($filename, $content)) {
            throw new RuntimeException(sprintf('Unable to write cache file `%s`.', $filename));
        }

        $mapFilename = $this->mapFilename($key);
        if (!$this->storeFile($mapFilename, sprintf('<?php return %s;', var_export($paths, true)))) {
            throw new RuntimeException(sprintf('Unable to write cache map `%s`.', $mapFilename));
        }
    }

    /**
     * Check if template still fresh (no files used for generation has changed).
     */
    public function isFresh(string $key): bool
    {
        $mapFilename = $this->mapFilename($key);
        if (!$this->exists($mapFilename)) {
            return false;
        }

        $time = $this->time($this->filename($key));

        $files = (array) include $mapFilename;
        foreach ($files as $file) {
            if ($this->time($file) > $time) {
                return false;
            }
        }

        return true;
    }

    /**
     * Delete file from the cache.
     */
    public function delete(string $key): void
    {
        $filename = $this->filename($key);
        if ($this->exists($filename) && !$this->deleteFile($filename)) {
            throw new RuntimeException(sprintf('Unable to delete cache file `%s`.', $filename));
        }

        $mapFilename = $this->mapFilename($key);
        if ($this->exists($mapFilename) && !$this->deleteFile($mapFilename)) {
            throw new RuntimeException(sprintf('Unable to delete cache map `%s`.', $mapFilename));
        }
    }

    /**
     * Load template content.
     */
    public function load(string $key): void
    {
        $filename = $this->filename($key);
        if ($this->exists($filename)) {
            include_once $filename;
        }
    }

    private function filename(string $key): string
    {
        return sprintf('%s/%s.php', $this->directory, $key);
    }

    private function mapFilename(string $key): string
    {
        return sprintf('%s/%s-map.php', $this->directory, $key);
    }

    private function ensureDirectory(string $directory): bool
    {
        $directoryMode = self::FILE_MODE | 0o111;

        if (is_dir($directory)) {
            return $this->setPermissions($directory, $directoryMode);
        }

        $directoryChain = [basename($directory)];
        $baseDirectory = $directory;
        while (!is_dir($baseDirectory = dirname($baseDirectory))) {
            $directoryChain[] = basename($baseDirectory);
        }

        foreach (array_reverse($directoryChain) as $dir) {
            $baseDirectory = sprintf('%s/%s', $baseDirectory, $dir);
            if ($this->exists($baseDirectory)) {
                return false;
            }

            /** @noinspection MkdirRaceConditionInspection */
            if (!mkdir($baseDirectory)) {
                return false;
            }

            if (!chmod($baseDirectory, $directoryMode)) {
                return false;
            }
        }

        return true;
    }

    private function storeFile(string $filename, string $data): bool
    {
        if (is_dir($filename)) {
            return false;
        }

        if ($this->exists($filename) && !$this->setPermissions($filename, self::FILE_MODE)) {
            return false;
        }

        $result = file_put_contents($filename, $data, LOCK_EX);
        if ($result === false) {
            clearstatcache(false, $filename);
            return false;
        }

        $permissionsUpdated = $this->setPermissions($filename, self::FILE_MODE);
        clearstatcache(false, $filename);

        return $permissionsUpdated;
    }

    private function deleteFile(string $filename): bool
    {
        if (is_dir($filename)) {
            return false;
        }

        $result = unlink($filename);
        clearstatcache(false, $filename);

        return $result;
    }

    private function exists(string $filename): bool
    {
        return file_exists($filename);
    }

    private function time(string $filename): int
    {
        if (!$this->exists($filename)) {
            throw new RuntimeException(sprintf("File '%s' not found", $filename));
        }

        return filemtime($filename) ?: throw new RuntimeException(
            sprintf('Unable to read modification time for `%s`.', $filename),
        );
    }

    private function getPermissions(string $filename): int
    {
        if (!$this->exists($filename)) {
            throw new RuntimeException(sprintf("File '%s' not found", $filename));
        }

        $permission = fileperms($filename);
        if ($permission === false) {
            throw new RuntimeException(sprintf('Unable to read permissions for `%s`.', $filename));
        }

        return $permission & 0777;
    }

    private function setPermissions(string $filename, int $mode): bool
    {
        if (is_dir($filename)) {
            $mode |= 0o111;
        }

        return $this->getPermissions($filename) === $mode || chmod($filename, $mode);
    }
}
