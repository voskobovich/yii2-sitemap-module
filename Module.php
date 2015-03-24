<?php

namespace voskobovich\sitemap;

use voskobovich\sitemap\behaviors\SitemapBehavior;
use Yii;
use yii\helpers\ArrayHelper;


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
     * Prepared model
     * @var \yii\db\ActiveRecord[]
     */
    public $prepareModels = [];

    /**
     * Configuration static urls for data collection
     * @var array
     */
    public $urls = [];

    /**
     * The number of links on the page
     * @var int
     */
    public $perPage = 10000;

    /**
     * Prepared models
     * @var array
     */
    private $_models = [];

    /**
     * Configuration models for data collection
     * @param $config
     * @return array
     */
    public function setModels($config)
    {
        foreach ($config as $configItem) {
            if (is_array($configItem)) {
                /** @var \yii\db\ActiveRecord $model */
                $model = new $configItem['class'];
                $model->attachBehaviors([
                    'sitemap' => ArrayHelper::merge(
                        $configItem['config'],
                        [
                            'class' => SitemapBehavior::className(),
                            'module' => $this,
                        ]
                    )
                ]);

                $this->_models[] = $model;
            }
        }
    }

    /**
     * Get prepared models
     * @return \yii\db\ActiveRecord[]
     */
    public function getModels()
    {
        return $this->_models;
    }

    /**
     * Get base path for sitemap files
     * @return mixed
     */
    public function getBasePath()
    {
        return Yii::getAlias('@app/../web');
    }

    /**
     * Get base path for sitemap files
     * @return mixed
     */
    public function getPartsPath()
    {
        return $this->getBasePath().'/sitemap_files';
    }
}
