<?php

namespace voskobovich\sitemap\behaviors;

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
     * The number of selected models
     * for the request to the database
     */
    const BATCH_MAX_SIZE = 100;

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
     * Build data for model
     * @return array
     */
    public function sitemapData()
    {
        $result = [];
        $n = 0;

        /** @var \yii\db\ActiveRecord $owner */
        $owner = $this->owner;
        $query = $owner::find();
        if (is_callable($this->scope)) {
            call_user_func($this->scope, $query);
        }

        foreach ($query->each(self::BATCH_MAX_SIZE) as $model) {
            $urlData = call_user_func($this->dataClosure, $model);

            if (empty($urlData)) {
                continue;
            }

            $result[$n]['loc'] = $urlData['loc'];
            $result[$n]['lastmod'] = $urlData['lastmod'];

            if (isset($urlData['changefreq'])) {
                $result[$n]['changefreq'] = $urlData['changefreq'];
            } elseif ($this->defaultChangefreq !== false) {
                $result[$n]['changefreq'] = $this->defaultChangefreq;
            }

            if (isset($urlData['priority'])) {
                $result[$n]['priority'] = $urlData['priority'];
            } elseif ($this->defaultPriority !== false) {
                $result[$n]['priority'] = $this->defaultPriority;
            }

            if (isset($urlData['news'])) {
                $result[$n]['news'] = $urlData['news'];
            }
            if (isset($urlData['images'])) {
                $result[$n]['images'] = $urlData['images'];
            }

            ++$n;
        }

        return $result;
    }
}
