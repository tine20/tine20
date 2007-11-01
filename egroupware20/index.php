<?php
/**
 * this is the general file any request should be routed trough
 *
 * @package     Egwbase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

require_once 'Zend/Loader.php';

Zend_Loader::registerAutoload();

$egwBase = new Egwbase_Controller();

$egwBase->handle();
