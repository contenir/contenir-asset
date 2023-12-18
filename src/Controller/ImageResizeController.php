<?php

/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Contenir\Asset\Controller;

use Laminas\Mvc\Controller\AbstractActionController;

class ImageResizeController extends AbstractActionController
{
    public function indexAction()
    {
        $helper = $this->container()->get('config')['asset']['srcset']['helper'] ?? null;

        $folder     = urldecode($this->params('folder'));
        $dimensions = urldecode($this->params('dimensions'));
        $filename   = urldecode($this->params('filename'));

        $rootPath = realpath('./public/');
        $srcPath  = $rootPath . '/asset/' . $folder . '/' . $filename;
        $destPath = $rootPath . '/asset/' . $folder . '/.' . $dimensions;

        $resizeDimensions = explode('x', $dimensions);

        if (! file_exists($srcPath)) {
            die('File not found');
        }

        if (! is_dir($destPath)) {
            if (file_exists($destPath)) {
                exit;
            }
            $result = @mkdir($destPath, 0777, true);
        }

        switch (basename($helper)) {
            case 'convert':
                $command = sprintf(
                    '%s %s -colorspace sRGB -strip -resize %sx%s -unsharp 0x0.75 -quality 85%% %s',
                    $helper,
                    escapeshellarg($srcPath),
                    $resizeDimensions[0] ?? 32,
                    $resizeDimensions[1] ?? null,
                    escapeshellarg($destPath . '/' . $filename)
                );
                exec($command);
                break;

            default:
                $image = new ImageResize($rootPath . $filepath);
                if (count($resizeDimensions) > 1) {
                    $image->resizeToBestFit(round($resizeDimensions[0]), round($resizeDimensions[1]));
                } else {
                    $image->resizeToWidth(round($resizeDimensions[0]));
                }
                $image->save($rootPath . $destFile);
                break;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        header('Content-Type:' . finfo_file($finfo, $destPath . '/' . $filename));
        header('Content-Length: ' . filesize($destPath . '/' . $filename));
        header('X-Contenir-Generated: ' . date('r'));
        readfile($destPath . '/' . $filename);

        exit;
    }
}
