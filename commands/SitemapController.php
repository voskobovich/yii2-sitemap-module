<?php

namespace voskobovich\sitemap\commands;

use Yii;
use yii\console\Controller;
use yii\helpers\FileHelper;
use yii\helpers\ArrayHelper;


/**
 * Generate file or many files by cron command
 * Class SitemapGenerateController
 * @package voskobovich\sitemap\commands
 */
class SitemapController extends Controller
{
    /**
     * Sitemap Module instance
     * @var \voskobovich\sitemap\Module $module
     */
    private $_module = null;

    /**
     * Location sitemap files
     * @var string
     */
    private $_baseDir = null;

    /**
     * Location pages files
     * @var string
     */
    private $_pageDir = null;

    /**
     * URI to sitemap file
     * @var string
     */
    private $_pathMainFile = null;

    /**
     * Init command
     */
    public function init()
    {
        $this->_module = Yii::$app->getModule('sitemap');
        $this->_baseDir = $this->_module->getFilesPath();
        $this->_pageDir = $this->_baseDir . '/sitemap_files';
        $this->_pathMainFile = "{$this->_baseDir}/sitemap.xml";
    }

    /**
     * List commands
     */
    public function actionIndex()
    {
        echo "sitemap/generate - Generate new sitemap\n";
        echo "sitemap/delete   - Delete all files of sitemap\n";
    }

    /**
     * Delete all files
     */
    public function actionDelete()
    {
        $this->deleteFiles();
    }

    /**
     * Building sitemat
     */
    public function actionGenerate()
    {
        // Delete old files
        $this->deleteFiles();

        // Get all models
        $activeModels = $this->_module->getModels();

        $pages = [];
        foreach ($activeModels as $activeModel) {
            $pages = ArrayHelper::merge($pages, $activeModel->buildPages());
        }

        $xmlData = Yii::$app->view->renderPhpFile(
            $this->_module->viewPath . '/default/main-template.php',
            ['urls' => $pages]
        );

        if (file_put_contents($this->_pathMainFile, $xmlData)) {
            echo "{$this->_pathMainFile} - OK\n";
        }
    }

    /**
     * Delete all files of sitemap
     */
    private function deleteFiles()
    {
        // Clear old files
        FileHelper::removeDirectory($this->_pageDir);
        FileHelper::createDirectory($this->_pageDir, 0777);

        // Delete sitemap file
        if (file_exists($this->_pathMainFile)) {
            unlink($this->_pathMainFile);
        }
    }
}