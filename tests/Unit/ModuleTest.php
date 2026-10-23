<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Unit;

use Contenir\Asset\ConfigProvider;
use Contenir\Asset\Module;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Module::class)]
#[Group('unit')]
final class ModuleTest extends TestCase
{
    #[Test]
    public function handsTheConfigProvidersWiringToLaminasMvc(): void
    {
        $provider = new ConfigProvider();

        static::assertSame(
            [
                'asset'              => $provider->getAssetDefaults(),
                'service_manager'    => $provider->getDependencies(),
                'view_helpers'       => $provider->getViewHelperConfig(),
                'view_helper_config' => $provider->getViewHelperDefaults(),
            ],
            (new Module())->getConfig(),
        );
    }
}
