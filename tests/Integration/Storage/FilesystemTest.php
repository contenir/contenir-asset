<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Integration\Storage;

use Contenir\Asset\Exception\RuntimeException;
use Contenir\Asset\Storage\Filesystem;
use ContenirTest\Asset\Trait\TempDirectoryTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function is_dir;
use function is_file;

#[CoversClass(Filesystem::class)]
#[CoversClass(RuntimeException::class)]
#[Group('integration')]
final class FilesystemTest extends TestCase
{
    use TempDirectoryTrait;

    #[Test]
    public function copiesAndRemovesFiles(): void
    {
        $this->createFile('/a.txt', 'a');

        Filesystem::copy("{$this->publicPath}/a.txt", "{$this->publicPath}/b.txt");
        Filesystem::remove("{$this->publicPath}/a.txt");

        static::assertSame([false, true], [is_file("{$this->publicPath}/a.txt"), is_file("{$this->publicPath}/b.txt")]);
    }

    #[Test]
    public function copyIntoMissingDirectoryFailsWithReason(): void
    {
        $this->createFile('/a.txt', 'a');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/^Cannot copy .*a\.txt to .*missing\/b\.txt \(.+\)$/');

        Filesystem::copy("{$this->publicPath}/a.txt", "{$this->publicPath}/missing/b.txt");
    }

    #[Test]
    public function createsNestedDirectoriesAndAcceptsExistingOnes(): void
    {
        Filesystem::ensureDirectory("{$this->publicPath}/a/b/c");
        Filesystem::ensureDirectory("{$this->publicPath}/a/b/c");

        static::assertTrue(is_dir("{$this->publicPath}/a/b/c"));
    }

    #[Test]
    public function directoryBlockedByAFileFailsWithReason(): void
    {
        $this->createFile('/blocked', 'x');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot create asset directory');

        Filesystem::ensureDirectory("{$this->publicPath}/blocked/child");
    }

    #[Test]
    public function removingMissingFileFailsWithReason(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/^Cannot remove .*gone\.txt \(.+\)$/');

        Filesystem::remove("{$this->publicPath}/gone.txt");
    }

    protected function setUp(): void
    {
        $this->setUpTempDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
    }
}
