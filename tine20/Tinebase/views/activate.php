<?php
/**
 * user account activation view
 * 
 * @package     Tinebase
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        style it some more ... ;)
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo $this->escape($this->title) ?></title>
</head>
<body>
    <h1>Welcome to Tine 2.0</h1>
    <p>You are now able to login <a href="http://<?php echo $this->loginUrl ?>">here</a> with your activated account (username <?php echo $this->username ?>).</p>
</body>
</html>
