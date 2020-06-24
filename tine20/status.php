<?php
/**
 * ownCloud status page
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2013-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

$values = array(
    'installed'     => true,
    'version'       => '10.0.10.4',
    'versionstring' => '10.0.10',
    'maintenance'   => false,
    'edition'       => '',
    'productname'   => 'Tine 2.0' // lets try it ;-)
);

header('Content-Type: application/json');
echo(json_encode($values));
