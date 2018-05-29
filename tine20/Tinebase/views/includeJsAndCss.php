<?php
/**
 * Tine 2.0 main view
 * 
 * @package     Tinebase
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        check if build script puts the translation files in build dir $tineBuildPath
 */

echo "\n    <!-- Tine 2.0 static files -->";

// this variable gets replaced by the buildscript
$tineBuildPath = '';

$locale = (Tinebase_Core::isRegistered(Tinebase_Core::LOCALE)) ? Tinebase_Core::getLocale() : 'en';
$eTag = Tinebase_Frontend_Http::getAssetHash();

switch(TINE20_BUILDTYPE) {
    case 'DEVELOPMENT':
        echo "\n    <!-- webpack-dev-server includes -->";
        foreach($this->devIncludes as $devInclude) {
            echo "\n    <script src='{$devInclude}'></script>";
        }
        echo "\n    <script src='webpack-dev-server.js'></script>";

        echo "\n    <!-- translations -->";
        echo "\n    <script type=\"text/javascript\" src=\"index.php?method=Tinebase.getJsTranslations&locale={$locale}&app=all&version={$eTag}\"></script>";
        $customJSFiles = Tinebase_Config::getInstance()->get(Tinebase_Config::FAT_CLIENT_CUSTOM_JS);
        if (! empty($customJSFiles)) {
            echo "\n    <!-- HEADS UP! CUSTOMJS IN PLACE! -->";
            echo "\n    <script type=\"text/javascript\" src=\"index.php?method=Tinebase.getCustomJsFiles\"></script>";
        }
        break;

    case 'DEBUG':
    case 'RELEASE':
        echo "\n    <script type=\"text/javascript\" src=\"index.php?method=Tinebase.getJsFiles&$eTag\"></script>";
        echo "\n    <script type=\"text/javascript\" src=\"index.php?method=Tinebase.getJsTranslations&locale={$locale}&app=all&version={$eTag}\"></script>";
        break;
}

if (Tinebase_Core::getConfig()->customMainscreenHeaders) {echo "\n" . Tinebase_Core::getConfig()->customMainscreenHeaders;}
