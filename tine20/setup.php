<?php
/**
 * Tine 2.0 - this file starts the setup process
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * magic_quotes_gpc Hack!!!
 * @author Florian Blasel
 * 
 * If you are on a shared host you may not able to change the php setting for magic_quotes_gpc
 * this hack will solve this BUT this takes performance (speed)!
 */

require_once 'bootstrap.php';
Setup_Core::dispatchRequest();

exit;
