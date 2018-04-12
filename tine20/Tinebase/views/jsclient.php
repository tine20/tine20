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

$title = Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_TITLE);
$faviconSVG = Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_FAVICON_SVG);
$maskiconColor = Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_MASKICON_COLOR);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title><?php echo $title; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-Tine20-Version" content="<?php echo TINE20_PACKAGESTRING ?>" />

    <link rel="icon" type="image/png" href="favicon/16" sizes="16x16">
    <link rel="icon" type="image/png" href="favicon/32" sizes="32x32">
    <link rel="icon" type="image/png" href="favicon/96" sizes="96x96">

    <link rel="apple-touch-icon" href="favicon/120">
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/180">
    <link rel="apple-touch-icon" sizes="152x152" href="favicon/152">
    <link rel="apple-touch-icon" sizes="167x167" href="favicon/167">

    <link rel="mask-icon" href="favicon/svg" color="<?php echo $maskiconColor;?>">

    <meta name="apple-mobile-web-app-title" content="<?php echo $title; ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">

    <link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/ext-all.css" />
</head>
<body>
    <!-- Loading Indicator -->
    <div class="tine-viewport-waitcycle" style="position: absolute; top: 50%; left: 50%; background-image: url(data:image/gif;base64,R0lGODlhEAAQALMMAKqooJGOhp2bk7e1rZ2bkre1rJCPhqqon8PBudDOxXd1bISCef///wAAAAAAAAAAACH/C05FVFNDQVBFMi4wAwEAAAAh+QQFAAAMACwAAAAAEAAQAAAET5DJyYyhmAZ7sxQEs1nMsmACGJKmSaVEOLXnK1PuBADepCiMg/DQ+/2GRI8RKOxJfpTCIJNIYArS6aRajWYZCASDa41Ow+Fx2YMWOyfpTAQAIfkEBQAADAAsAAAAABAAEAAABE6QyckEoZgKe7MEQMUxhoEd6FFdQWlOqTq15SlT9VQM3rQsjMKO5/n9hANixgjc9SQ/CgKRUSgw0ynFapVmGYkEg3v1gsPibg8tfk7CnggAIfkEBQAADAAsAAAAABAAEAAABE2QycnOoZjaA/IsRWV1goCBoMiUJTW8A0XMBPZmM4Ug3hQEjN2uZygahDyP0RBMEpmTRCKzWGCkUkq1SsFOFQrG1tr9gsPc3jnco4A9EQAh+QQFAAAMACwAAAAAEAAQAAAETpDJyUqhmFqbJ0LMIA7McWDfF5LmAVApOLUvLFMmlSTdJAiM3a73+wl5HYKSEET2lBSFIhMIYKRSimFriGIZiwWD2/WCw+Jt7xxeU9qZCAAh+QQFAAAMACwAAAAAEAAQAAAETZDJyRCimFqbZ0rVxgwF9n3hSJbeSQ2rCWIkpSjddBzMfee7nQ/XCfJ+OQYAQFksMgQBxumkEKLSCfVpMDCugqyW2w18xZmuwZycdDsRACH5BAUAAAwALAAAAAAQABAAAARNkMnJUqKYWpunUtXGIAj2feFIlt5JrWybkdSydNNQMLaND7pC79YBFnY+HENHMRgyhwPGaQhQotGm00oQMLBSLYPQ9QIASrLAq5x0OxEAIfkEBQAADAAsAAAAABAAEAAABE2QycmUopham+da1cYkCfZ94UiW3kmtbJuRlGF0E4Iwto3rut6tA9wFAjiJjkIgZAYDTLNJgUIpgqyAcTgwCuACJssAdL3gpLmbpLAzEQA7); width: 16px; height: 16px">&#160;</div><div class="tine-viewport-poweredby" style="position: absolute; bottom: 10px; right: 10px; font:normal 12px arial, helvetica,tahoma,sans-serif;">Powered by: <a target="_blank" href="http://www.tine20.com/info/community.html" title="online open source groupware and crm"><?php echo $title; ?></a></div>
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

    <noscript><p>You need to enable javascript to use <a target="_blank" href="http://www.tine20.com/info/community.html" title="online open source groupware and crm">Tine 2.0</a></p></noscript>
</body>
</html>
