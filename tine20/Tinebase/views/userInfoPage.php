<?php
/**
 * user info page
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
    
        <link rel="icon" href="../../images/favicon.ico" type="image/x-icon" />
    
        <link rel="openid2.provider" href="<?php echo $this->escape($this->openIdUrl) ?>"/>
        <link rel="openid.server"    href="<?php echo $this->escape($this->openIdUrl) ?>"/>        
    </head>
    <body>
        This is the info page for user <?php echo $this->escape($this->username) ?>.
    </body>
</html>
