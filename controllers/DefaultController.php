<?php

namespace voskobovich\sitemap\controllers;

use Yii;
use yii\web\Controller;

/**
 * @author Vitaly Voskobovich
 * @author Vladislav Platonov
 * @package voskobovich\sitemap
 */
class DefaultController extends Controller
{
    /**
     * Realtime generated sitemap
     */
    public function actionIndex()
    {
        /** @var \voskobovich\sitemap\Module $module */
        $module = $this->module;

        $sitemapData = $module->cacheProvider->get($module->cacheKey);

        if (!$sitemapData) {
            $sitemapData = $module->buildSitemap();
            $module->cacheProvider->set($module->cacheKey, $sitemapData, $module->cacheExpire);
        }

        $sitemapData = $this->renderPartial('main-template', [
            'urls' => $sitemapData
        ]);

        header('Content-type: application/xml');
        if ($module->enableGzip) {
            $sitemapData = gzencode($sitemapData);
            header('Content-Encoding: gzip');
            header('Content-Length: ' . strlen($sitemapData));
        }

        echo $sitemapData;
    }
}
