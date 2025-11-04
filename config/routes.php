<?php

declare(strict_types=1);

/**
 * Route configuration for contenir-asset
 *
 * Add this to your application's route configuration:
 *
 * // In config/routes.php or similar:
 * require __DIR__ . '/../vendor/contenir/asset/config/routes.php';
 *
 * Or manually add the route in your routing configuration.
 */

use Contenir\Asset\Handler\ImageResizeHandler;
use Mezzio\Application;

return static function (Application $app): void {
    /**
     * Dynamic image resize route
     *
     * Matches: /cache/images/{dimensions}/{image_path}.{format}
     * Example: /cache/images/800x600/asset/project/image.jpg
     */
    $app->get(
        '/cache/images/{dimensions:[0-9]+x[0-9]*}/{image_path:.+}\.{format:jpg|jpeg|png|webp|avif}',
        ImageResizeHandler::class,
        'asset.image.resize'
    );
};