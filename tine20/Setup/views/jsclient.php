<?php
/**
 * main view
 * 
 * @package     Tinebase
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        check if build script puts the translation files in build dir $tineBuildPath
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo $this->escape($this->title) ?></title>

    <!-- EXT JS -->
    <link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/ext-all.css" />
    <link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/xtheme-blue.css" />

    <script type="text/javascript" src="library/ExtJS/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="library/ExtJS/ext-all<?php echo TINE20_BUILDTYPE != 'RELEASE' ? '-debug' : '' ?>.js"></script>

    <!-- Tine 2.0 setup static files --><?php
        /**
         * this variable gets replaced by the buildscript
         */
        $tineBuildPath = '';
        
        $locale = Zend_Registry::get('locale');
        $fileMap = Tinebase_Frontend_Http::getAssetsMap();
        $tinebaseJS = $fileMap["Tinebase/js/Tinebase"]['js'];
        $setupJS = $fileMap["Setup/js/Setup"]['js'];

        switch(TINE20_BUILDTYPE) {
            case 'RELEASE':
                echo "\n    <script type='text/javascript' language='javascript' src='$tinebaseJS'></script>";
                echo "\n    <script type='text/javascript' language='javascript' src='$setupJS'></script>";
                echo "\n    <script type='text/javascript' language='javascript' src='Tinebase/js/Tinebase-lang-" . (string)$locale . ".js'></script>";
                echo "\n    <script type='text/javascript' language='javascript' src='Setup/js/Setup-lang-" . (string)$locale . ".js'></script>";
                break;
                
            case 'DEBUG':
                $tinebaseJS = substr($tinebaseJS, 0, -3) . '.debug.js';
                $setupJS = substr($setupJS, 0, -3) . '.debug.js';
                echo "\n    <script type='text/javascript' language='javascript' src='$tinebaseJS'></script>";
                echo "\n    <script type='text/javascript' language='javascript' src='$setupJS'></script>";
                echo "\n    <script type='text/javascript' language='javascript' src='Tinebase/js/Tinebase-lang-" . (string)$locale . "-debug.js'></script>";
                echo "\n    <script type='text/javascript' language='javascript' src='Setup/js/Setup-lang-" . (string)$locale . "-debug.js'></script>";
                break;
                
            case 'DEVELOPMENT':
            default:
                echo "\n    <!-- amd/commonjs loader dependencies -->";
                echo "\n    <script src='$tinebaseJS'></script>";
                echo "\n    <script src='$setupJS'></script>";
                echo "\n    <script src='webpack-dev-server.js'></script>";

                echo "\n    <!-- translations -->";
                echo "\n    <script type=\"text/javascript\" src=\"setup.php?method=Tinebase.getJsTranslations&locale={$locale}&app=all&" . time() . "\"></script>";
                break;
        }?>
</head>
<body>
    <noscript><p>You need to enable javascript to use <a target="_blank" href="<?php echo Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_WEBURL); ?>" title="<?php echo Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_DESCRIPTION); ?>"><?php echo Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_TITLE); ?></a> or use the CLI setup</p></noscript>
</body>
</html>
