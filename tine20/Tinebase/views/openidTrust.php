<?php
/**
 * OpenId trust screen
 * 
 * @package     Tinebase
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    
        <link rel="icon" href="images/favicon.ico" type="image/x-icon" />
        <link rel="stylesheet" type="text/css" href="styles/tine20.css" />
    </head>
    <body>
        <p>A site identifying as 
        <a href="<?php echo $this->escape($this->openIdConsumer);?>">
            <?php echo $this->escape($this->openIdConsumer);?>
        </a> has asked us for confirmation that
        <a href="<?php echo $this->escape($this->openIdIdentity);?>">
            <?php echo $this->escape($this->openIdIdentity);?>
        </a> is your identity URL.
        </p>
        <form method="post">
            <input type="checkbox" name="forever">
            <label for="forever">für immer</label><br>
            <input type="hidden" name="openid_action" value="trust">
            <input type="submit" name="allow" value="Allow">
            <input type="submit" name="deny" value="Deny">
        </form>    
    </body>
</html>
