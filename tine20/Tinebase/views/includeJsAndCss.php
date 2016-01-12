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

echo "<!-- Tine 2.0 static files -->\n";

// this variable gets replaced by the buildscript
$tineBuildPath = '';

$locale = (Tinebase_Core::isRegistered(Tinebase_Core::LOCALE)) ? Tinebase_Core::getLocale() : 'en';

switch(TINE20_BUILDTYPE) {
    case 'DEVELOPMENT':
        echo $this->jsb2tk->getHTML();
        echo '    <script type="text/javascript" src="index.php?method=Tinebase.getJsTranslations&' . time() . '"></script>';
        $customJSFiles = Tinebase_Config::getInstance()->get(Tinebase_Config::FAT_CLIENT_CUSTOM_JS);
        if (! empty($customJSFiles)) {
            echo "\n    <!-- HEADS UP! CUSTOMJS IN PLACE! -->";
            echo "\n    <script type=\"text/javascript\" src=\"index.php?method=Tinebase.getCustomJsFiles\"></script>";
        }
        break;

    case 'DEBUG':
    case 'RELEASE':
        echo "\n    <link rel='stylesheet' type='text/css' href='index.php?method=Tinebase.getCssFiles' />";
        echo "\n    <script type=\"text/javascript\" src=\"index.php?method=Tinebase.getJsFiles\"></script>";
        echo "\n    <script type=\"text/javascript\" src=\"index.php?method=Tinebase.getJsTranslations\"></script>";
        break;
}

if (Tinebase_Core::getConfig()->customMainscreenHeaders) {echo "\n" . Tinebase_Core::getConfig()->customMainscreenHeaders;}
