<?php
/**
 * Tine 2.0 main view
 * 
 * @package     Tinebase
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

$faviconSVG = Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_FAVICON_SVG);
$maskiconColor = Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_MASKICON_COLOR);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title><?php echo Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_TITLE); ?></title>
    
    <style type="text/css"> @keyframes loading-animation { from { transform: rotate(0deg); } to { transform: rotate(360deg); } } .tine-viewport-waitcycle { animation-name: loading-animation; animation-duration: 1000ms; animation-iteration-count: infinite; animation-timing-function: cubic-bezier(.17, .67, .52, .71); position: absolute; top: 50%; left: 50%; margin-top: -16px; margin-left: -16px; width: 32px; height: 32px; box-sizing: border-box; border: 2px solid #ccc !important; border-top: 2px solid #2196f3 !important; border-radius: 100%; } .tine-viewport-poweredby { position: absolute; bottom: 10px; right: 10px; font:normal 12px arial, helvetica,tahoma,sans-serif; } </style>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-Tine20-Version" content="<?php echo TINE20_PACKAGESTRING ?>" />
    <meta name="google" content="notranslate">

    <link rel="icon" type="image/png" href="favicon/16" sizes="16x16">
    <link rel="icon" type="image/png" href="favicon/32" sizes="32x32">
    <link rel="icon" type="image/png" href="favicon/96" sizes="96x96">

    <link rel="apple-touch-icon" href="favicon/120">
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/180">
    <link rel="apple-touch-icon" sizes="152x152" href="favicon/152">
    <link rel="apple-touch-icon" sizes="167x167" href="favicon/167">

    <link rel="mask-icon" href="favicon/svg" color="<?php echo $maskiconColor;?>">

    <meta name="apple-mobile-web-app-title" content="<?php echo Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_TITLE); ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">

    <link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/ext-all.css" />
<!--    <link rel="stylesheet" type="text/css" href="Tinebase/css/extjs-theme-tine20-flat.css" />-->
</head>
<body>
    <!-- Loading Indicator -->
    <div class="tine-viewport-waitcycle">&#160;</div>
    <div class="tine-viewport-poweredby" style="">Powered by: <a target="_blank" href="<?php echo Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_WEBURL); ?>" title="<?php echo Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_DESCRIPTION); ?>"><?php echo Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_TITLE); ?></a></div>
    <?php
    if(isset(Tinebase_Core::getConfig()->captcha->count) && Tinebase_Core::getConfig()->captcha->count != 0)
    {
        echo "\n".' <span id="useCaptcha" />'."\n";
    }
    ?>
    <!-- EXT JS -->
    <script type="text/javascript" src="library/ExtJS/adapter/ext/ext-base<?php echo TINE20_BUILDTYPE != 'RELEASE' ? '-debug' : '' ?>.js"></script>
    <script type="text/javascript" src="library/ExtJS/ext-all<?php echo TINE20_BUILDTYPE != 'RELEASE' ? '-debug' : '' ?>.js"></script>

    <?php require 'Tinebase/views/includeJsAndCss.php'; ?>

    <noscript><p>You need to enable javascript to use <a target="_blank" href="<?php echo Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_WEBURL); ?>" title="<?php echo Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_DESCRIPTION); ?>"><?php echo Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_TITLE); ?></a></p></noscript>
</body>
</html>
