<?php

declare(strict_types=1);

namespace Contenir\Asset;

/**
 * laminas-mvc module: the {@see ConfigProvider} wiring with its services
 * under `service_manager`, where laminas-mvc reads them.
 *
 * @api
 */
final readonly class Module
{
    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $provider = new ConfigProvider();

        return [
            'asset'              => $provider->getAssetDefaults(),
            'service_manager'    => $provider->getDependencies(),
            'view_helpers'       => $provider->getViewHelperConfig(),
            'view_helper_config' => $provider->getViewHelperDefaults(),
        ];
    }
}
