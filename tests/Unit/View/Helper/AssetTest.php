<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Unit\View\Helper;

use Contenir\Asset\AssetManagerInterface;
use Contenir\Asset\View\Helper\Asset;
use ContenirTest\Asset\TestAsset\Entity\Asset as AssetEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Asset::class)]
#[Group('unit')]
final class AssetTest extends TestCase
{
    #[Test]
    public function emptyIdLoadsNothing(): void
    {
        $manager = $this->createMock(AssetManagerInterface::class);
        $manager->expects(static::never())->method('findOneById');
        $helper = new Asset($manager);

        static::assertSame([null, null], [$helper(null), $helper('')]);
    }

    #[Test]
    public function loadsAssetById(): void
    {
        $entity  = new AssetEntity();
        $manager = $this->createMock(AssetManagerInterface::class);
        $manager->expects(static::once())->method('findOneById')->with(5)->willReturn($entity);

        static::assertSame($entity, (new Asset($manager))(5));
    }
}
