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
    'version'       => '9.0.2.1',
    'versionstring' => '9.0.2',
    'edition'       => ''
);

echo(json_encode($values));
