<?php
/**
 * update view
 * 
 * @package     Tinebase
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/ext-all.css"/>
    <script type="text/javascript" src="library/ExtJS/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="library/ExtJS/ext-all.js"></script>

<?php
    try {
        $i18n = Tinebase_Translation::getTranslation('Tinebase');
        $msg = $i18n->_('Tine 2.0 needs to be updated or is not installed yet.');
        $title = $i18n->_('Please wait or contact your administrator');
    } catch (Exception $e) {
        // new tine installation with empty DB
        header('Location: setup.php');
    }
    
    echo <<<EOT
    <script type="text/javascript">
        Ext.BLANK_IMAGE_URL = "library/ExtJS/resources/images/default/s.gif";
        Ext.onReady(function() {
            Ext.MessageBox.wait('$msg', '$title');
            window.setTimeout('location.href = location.href', 20000);
        });
    </script>
EOT;
?>

</head>
<body>
<div id="button"></div>
</body>
</html>
