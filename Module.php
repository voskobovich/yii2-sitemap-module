<?php

namespace voskobovich\sitemap;

use Yii;
use yii\base\InvalidConfigException;
use yii\caching\Cache;
use yii\db\ActiveRecord;


/**
 * Yii2 module for automatically generating XML Sitemap.
 *
 * @author Vitaly Voskobovich
 * @author Vladislav Platonov
 * @package voskobovich\sitemap
 */
class Module extends \yii\base\Module
{
    /**
     * Namespace for controllers
     * @var string
     */
    public $controllerNamespace = 'voskobovich\sitemap\controllers';

    /**
     * Cache lifetime in seconds
     * @var int
     */
    public $cacheExpire = 86400;

    /**
     * Identifier cache provider
     * @var Cache|string
     */
    public $cacheProvider = 'cache';

    /**
     * Key data in the cache storage
     * @var string
     */
    public $cacheKey = 'sitemap';

    /**
     * Use php's gzip compressing.
     * @var boolean
     */
    public $enableGzip = false;

    /**
     * Configuration models for data collection
     * @var array
     */
    public $models = [];

    /**
     * Configuration static urls for data collection
     * @var array
     */
    public $urls = [];

    /**
     * Folder name of parts sitemap
     * @var string
     */
    public $pagesFolder = 'sitemap_pages';

    /**
     * File name template page sitemap
     * @var string
     */
    public $pageFileName = 'page_';

    /**
     * File name sitemap
     * @var string
     */
    public $sitemapFileName = 'sitemap';

    /**
     * The number of links on the page
     * @var int
     */
    public $perPage = 10000;

    /**
     * Init module
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if (is_string($this->cacheProvider)) {
            $this->cacheProvider = Yii::$app->{$this->cacheProvider};
        }

        if (!$this->cacheProvider instanceof Cache) {
            throw new InvalidConfigException('Invalid `cacheKey` parameter was specified.');
        }
    }

    /**
     * Build a site map.
     * @return array
     */
    public function buildSitemap()
    {
        $urls = $this->urls;

        foreach ($this->models as $modelName) {
            if (is_array($modelName)) {
                /** @var ActiveRecord $model */
                $model = new $modelName['class'];
                if (isset($modelName['behaviors'])) {
                    $model->attachBehaviors($modelName['behaviors']);
                }
            } else {
                $model = new $modelName;
            }

            $urls = array_merge($urls, $model->sitemapData());
        }

        return $urls;
    }
}
