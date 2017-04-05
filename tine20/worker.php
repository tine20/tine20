<?php
/**
 * this is action queue worker daemon
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2013-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

if (PHP_SAPI != 'cli') {
    die('Not allowed: wrong sapi name!');
}

require_once 'bootstrap.php';

$daemon = new Tinebase_ActionQueue_Worker();
$daemon->run();

