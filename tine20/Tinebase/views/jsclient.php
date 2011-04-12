<?php
/**
 * Tine 2.0 main view
 * 
 * @package     Tinebase
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title>Tine 2.0</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=8; IE=7" >

    <link rel="icon" href="images/favicon.ico" type="image/x-icon" />
    <link rel="chrome-application-definition" href="chrome_web_app.json">

    <!-- EXT JS -->
     <?php
        if(isset(Tinebase_Core::getConfig()->themes->default)) {
            $path = Tinebase_Core::getConfig()->themes->themelist->get(Tinebase_Core::getConfig()->themes->default)->path;
            if(1 || Tinebase_Core::getConfig()->themes->themelist->get(Tinebase_Core::getConfig()->themes->default)->useBlueAsBase==1) {
                echo '<link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/ext-all.css" />';
            } else {
                echo '<link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/ext-all-notheme.css" />';
            }
            echo "\n".'<link rel="stylesheet" type="text/css" href="'.($path).'" />';
            echo "\n";
        } else {
            echo '<link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/ext-all.css" />';
            echo '<link rel="stylesheet" type="text/css" href="styles/tine20.css" />';
        }
     ?>
    
    <script type="text/javascript" src="library/ExtJS/adapter/ext/ext-base<?php echo TINE20_BUILDTYPE != 'RELEASE' ? '-debug' : '' ?>.js"></script>
    <script type="text/javascript" src="library/ExtJS/ext-all<?php echo TINE20_BUILDTYPE != 'RELEASE' ? '-debug' : '' ?>.js"></script>
    
    <?php require 'Tinebase/views/includeJsAndCss.php'; ?>
        
</head>
<body>
    <noscript><p>You need to enable javascript to use <a href="http://www.tine20.org/" title="online open source groupware and crm">Tine 2.0</a></p></noscript>
</body>
</html>
