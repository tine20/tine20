<?php
/**
 * this is the general file any request should be routed trough
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2013-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

if (PHP_SAPI != 'cli') {
    die('Not allowed: wrong sapi name!');
}

require_once 'bootstrap.php';

$daemon = new Tinebase_ActionQueue_Worker();
$daemon->run();

