<?php
/**
 * Tine 2.0 PHP HTTP Client
 * 
 * @package     Tinebase
 * @license     New BSD License
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * simple loader
 */
set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__),
    get_include_path(),
)));

require_once 'Zend/Loader.php';
Zend_Loader::registerAutoload();


