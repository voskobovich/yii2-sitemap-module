<?php

use \yii\helpers\Url;


/**
 * @var array[] $urls
 */

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php foreach($urls as $url): ?>
        <sitemap>
            <loc><?= Url::to($url['loc'], true) ?></loc>
            <?php if (isset($url['lastmod'])): ?>
                <lastmod><?= is_string($url['lastmod']) ?
                        $url['lastmod'] : date(DATE_W3C, $url['lastmod']) ?></lastmod>
            <?php endif; ?>
        </sitemap>
    <?php endforeach; ?>
</sitemapindex>