<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Unit\View\Helper;

use Contenir\Asset\View\Helper\ImageInfo;
use ContenirTest\Asset\TestAsset\Entity\Asset;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(ImageInfo::class)]
#[Group('unit')]
final class ImageInfoTest extends TestCase
{
    #[Test]
    public function emptyPathHasNoDimensions(): void
    {
        static::assertNull(ImageInfo::dimensions('/nowhere', ''));
    }

    #[Test]
    public function readsPathFromEverySupportedShape(): void
    {
        $entity       = new Asset();
        $entity->path = '/entity.jpg';
        $object       = new stdClass();
        $object->path = '/object.jpg';

        static::assertSame(
            ['/string.jpg', '/entity.jpg', '/array.jpg', '/object.jpg', '', '', '', ''],
            [
                ImageInfo::path('/string.jpg'),
                ImageInfo::path($entity),
                ImageInfo::path(['path' => '/array.jpg']),
                ImageInfo::path($object),
                ImageInfo::path(new stdClass()),
                ImageInfo::path(['path' => 5]),
                ImageInfo::path(new Asset()),
                ImageInfo::path(42),
            ],
        );
    }
}
