<?php

namespace voskobovich\sitemap\behaviors;

use Yii;
use yii\data\ActiveDataProvider;
use yii\base\Behavior;
use yii\base\InvalidConfigException;

/**
 * Behavior for XML Sitemap Yii2 module.
 *
 * For example:
 *
 * ```php
 * public function behaviors()
 * {
 *  return [
 *       'sitemap' => [
 *           'class' => SitemapBehavior::className(),
 *           'scope' => function ($model) {
 *               $model->select(['url', 'lastmod']);
 *               $model->andWhere(['is_deleted' => 0]);
 *           },
 *           'dataClosure' => function ($model) {
 *              return [
 *                  'loc' => Url::to($model->url, true),
 *                  'lastmod' => strtotime($model->lastmod),
 *                  'changefreq' => SitemapBehavior::CHANGEFREQ_DAILY,
 *                  'priority' => 0.8
 *              ];
 *          }
 *       ],
 *  ];
 * }
 * ```
 *
 * @see http://www.sitemaps.org/protocol.html
 * @author Vitaly Voskobovich
 * @author Vladislav Platonov
 * @package voskobovich\sitemap\behaviors
 */
class SitemapBehavior extends Behavior
{
    /**
     * Change frequency variants
     */
    const CHANGEFREQ_ALWAYS = 'always';
    const CHANGEFREQ_HOURLY = 'hourly';
    const CHANGEFREQ_DAILY = 'daily';
    const CHANGEFREQ_WEEKLY = 'weekly';
    const CHANGEFREQ_MONTHLY = 'monthly';
    const CHANGEFREQ_YEARLY = 'yearly';
    const CHANGEFREQ_NEVER = 'never';

    /**
     * Data format for the construction
     * of links to the map
     * Example:
     * ```
     * return function() {
     *      'loc' => ...,
     *      'lastmod' => ...,
     *      'changefreq' => ...,
     *      'priority' => ...,
     * }
     * ```
     * @var callable
     */
    public $dataClosure;

    /**
     * Change frequency
     * Default: false
     * @var string|bool
     */
    public $defaultChangefreq = false;

    /**
     * Priority
     * Default: false
     * @var float|bool
     */
    public $defaultPriority = false;

    /**
     * Scopes for select model
     * @var callable
     */
    public $scope;

    /**
     * Sitemap module instance
     * @var \voskobovich\sitemap\Module
     */
    public $module = null;

    /**
     * Init behavior
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!is_callable($this->dataClosure)) {
            throw new InvalidConfigException('SitemapBehavior::$dataClosure isn\'t callable.');
        }
    }

    /**
     * Create sitemap files
     * @return array
     */
    public function buildPages()
    {
        /** @var \yii\db\ActiveRecord $owner */
        $owner = $this->owner;
        $query = $owner::find();

        $basePath = $this->module->getFilesPath();
        preg_match('/([A-Za-z]+)$/', get_class($owner), $matchResult);
        $fileSuffix = strtolower($matchResult[0]);

        // Apply scopes
        if (is_callable($this->scope)) {
            call_user_func($this->scope, $query);
        }

        // Build data provider for separated on pages
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $this->module->perPage,
                /*
                 * Break up a large sitemap into a set of smaller sitemaps to prevent your
                 * server from being overloaded by serving a large file to Google. A sitemap
                 * file can't contain more than 50,000 URLs and must be no larger than 50 MB
                 * uncompressed.
                 * Source: https://support.google.com/webmasters/answer/183668?hl=en
                 */
                'pageSizeLimit' => [10, 50000]
            ],
        ]);

        // Build pages
        $pages = [];

        $dataProvider->prepare();
        $pageCount = $dataProvider->pagination->getPageCount();

        for ($page = 0; $page < $pageCount; $page++) {
            $dataProvider->pagination->setPage($page);
            $dataProvider->prepare(true);

            // Processing one page
            $pageUrls = [];
            $n = 0;

            foreach ($dataProvider->getModels() as $model) {
                $urlData = call_user_func($this->dataClosure, $model);

                $pageUrls[$n]['loc'] = $urlData['loc'];
                $pageUrls[$n]['lastmod'] = $urlData['lastmod'];

                if (isset($urlData['changefreq'])) {
                    $pageUrls[$n]['changefreq'] = $urlData['changefreq'];
                } elseif ($this->defaultChangefreq !== false) {
                    $pageUrls[$n]['changefreq'] = $this->defaultChangefreq;
                }

                if (isset($urlData['priority'])) {
                    $pageUrls[$n]['priority'] = $urlData['priority'];
                } elseif ($this->defaultPriority !== false) {
                    $pageUrls[$n]['priority'] = $this->defaultPriority;
                }

                if (isset($urlData['news'])) {
                    $pageUrls[$n]['news'] = $urlData['news'];
                }
                if (isset($urlData['images'])) {
                    $pageUrls[$n]['images'] = $urlData['images'];
                }

                ++$n;
            }

            $xmlData = Yii::$app->view->renderPhpFile(
                $this->module->viewPath . '/default/page-template.php',
                ['urls' => $pageUrls]
            );

            $fileUrl = "sitemap_files/{$fileSuffix}_{$page}.xml";

            if (file_put_contents($basePath . '/' . $fileUrl, $xmlData)) {
                $pages[] = [
                    'loc' => $fileUrl,
                    'lastmod' => time()
                ];

                echo "OK: {$basePath}/{$fileUrl}\n";
            }
        }

        return $pages;
    }
}
