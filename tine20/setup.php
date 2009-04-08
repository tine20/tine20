#!/usr/bin/env php
<?php
/**
 * Tine 2.0 - this file starts the setup process
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

set_include_path('.' . PATH_SEPARATOR . dirname(__FILE__) . '/library' . PATH_SEPARATOR . get_include_path());

require_once 'Zend/Loader.php';

Zend_Loader::registerAutoload();

Setup_Core::dispatchRequest();

exit;
