<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Unit\Storage;

use Contenir\Asset\Exception\InvalidArgumentException;
use Contenir\Asset\Storage\AssetSource;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssetSource::class)]
#[CoversClass(InvalidArgumentException::class)]
#[Group('unit')]
final class AssetSourceTest extends TestCase
{
    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidSourceProvider(): array
    {
        return [
            'empty path'       => [''],
            'missing tmp_name' => [['name' => 'a.jpg']],
            'missing name'     => [['tmp_name' => '/tmp/x']],
            'empty tmp_name'   => [['name' => 'a.jpg', 'tmp_name' => '']],
            'integer'          => [42],
        ];
    }

    #[Test]
    public function pathUsesItsBasenameAsFilename(): void
    {
        $source = AssetSource::from('/tmp/upload/photo.jpg');

        static::assertSame(['/tmp/upload/photo.jpg', 'photo.jpg'], [$source->path, $source->filename]);
    }

    #[DataProvider('invalidSourceProvider')]
    #[Test]
    public function rejectsAnythingElse(mixed $source): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('An asset source must be a file path or an upload array');

        AssetSource::from($source);
    }

    #[Test]
    public function uploadArrayKeepsTheOriginalName(): void
    {
        $source = AssetSource::from(['name' => 'Holiday.JPG', 'tmp_name' => '/tmp/php123', 'error' => 0]);

        static::assertSame(['/tmp/php123', 'Holiday.JPG'], [$source->path, $source->filename]);
    }
}
