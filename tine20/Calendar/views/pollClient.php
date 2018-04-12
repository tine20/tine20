<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  views
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <link rel="icon" type="image/png" href="/favicon/16" sizes="16x16">
    <link rel="icon" type="image/png" href="/favicon/32" sizes="32x32">
    <link rel="icon" type="image/png" href="/favicon/96" sizes="96x96">
  </head>
  <body>
    <div id="app"></div><?php
    foreach($this->jsFiles as $jsFile) {
      echo "\n    <script type=\"text/javascript\" src='{$jsFile}'></script>";
    }?>
  </body>
</html>