<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Trait;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function bin2hex;
use function dirname;
use function file_put_contents;
use function imagecreatetruecolor;
use function imagepng;
use function is_dir;
use function mkdir;
use function random_bytes;
use function rmdir;
use function sys_get_temp_dir;
use function unlink;

/**
 * A fresh temporary web root per test, removed afterwards. Call
 * {@see self::setUpTempDirectory()} from setUp() and
 * {@see self::tearDownTempDirectory()} from tearDown().
 */
trait TempDirectoryTrait
{
    protected string $publicPath;

    protected function createFile(string $path, string $content): string
    {
        $directory = $this->publicPath . dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, permissions: 0o775, recursive: true);
        }

        file_put_contents($this->publicPath . $path, $content);

        return $path;
    }

    /**
     * Write a PNG of the given size at a public path and return it.
     */
    protected function createImage(string $path, int $width, int $height): string
    {
        $directory = $this->publicPath . dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, permissions: 0o775, recursive: true);
        }

        imagepng(imagecreatetruecolor($width, $height), $this->publicPath . $path);

        return $path;
    }

    protected function setUpTempDirectory(): void
    {
        $this->publicPath = sys_get_temp_dir() . '/contenir-asset-test-' . bin2hex(random_bytes(6));
        mkdir($this->publicPath, permissions: 0o775, recursive: true);
    }

    protected function tearDownTempDirectory(): void
    {
        if (! is_dir($this->publicPath)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->publicPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->publicPath);
    }
}
