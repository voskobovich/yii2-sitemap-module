<?php

namespace voskobovich\sitemap\commands;

use Yii;
use yii\console\Controller;
use yii\data\ArrayDataProvider;
use yii\helpers\FileHelper;
use yii\helpers\Url;


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
    private $_pathSitemapFile = null;

    /**
     * Init command
     */
    public function init()
    {
        $this->_module = Yii::$app->getModule('sitemap');
        $this->_baseDir = Yii::getAlias('@app/../web');
        $this->_pageDir = "{$this->_baseDir}/{$this->_module->pagesFolder}";
        $this->_pathSitemapFile = "{$this->_baseDir}/{$this->_module->sitemapFileName}.xml";
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

        // Get all links
        $siteMapData = $this->_module->buildSitemap();

        // Separated links on page
        $dataProvider = new ArrayDataProvider([
            'allModels' => $siteMapData,
            'pagination' => [
                'pageSize' => $this->_module->perPage,
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

        $dataProvider->prepare();
        $pageCount = $dataProvider->pagination->getPageCount();

        if ($pageCount > 1) {
            $pages = [];

            for ($page = 0; $page < $pageCount; $page++) {
                $dataProvider->pagination->setPage($page);
                $dataProvider->prepare(true);

                $xmlData = Yii::$app->view->renderPhpFile(
                    $this->_module->viewPath . '/default/page-template.php',
                    ['urls' => $dataProvider->getModels()]
                );

                $fileName = "{$this->_module->pageFileName}{$page}.xml";
                $filePath = "{$this->_pageDir}/{$fileName}";

                if (file_put_contents($filePath, $xmlData)) {
                    $pages[] = [
                        'loc' => "/{$this->_module->pagesFolder}/{$fileName}",
                        'lastmod' => time()
                    ];

                    echo "{$fileName} - OK\n";
                }
            }

            $this->putToFile($pages, 'main-template');
        } else {
            $this->putToFile($siteMapData, 'page-template');
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
        if (file_exists($this->_pathSitemapFile)) {
            unlink($this->_pathSitemapFile);
        }
    }

    /**
     * Writing data to a file
     * @param $urls
     * @param string $template
     */
    private function putToFile($urls, $template = 'page-template')
    {
        $xmlData = Yii::$app->view->renderPhpFile(
            $this->_module->viewPath . '/default/' . $template . '.php',
            ['urls' => $urls]
        );

        if (file_put_contents($this->_pathSitemapFile, $xmlData)) {
            echo "{$this->_pathSitemapFile} - OK\n";
        }
    }
}