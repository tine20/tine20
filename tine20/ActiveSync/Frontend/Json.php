<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSyncActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Json.php 5883 2008-12-11 15:02:29Z b.mueller@metaways.de $
 *
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the ActiveSync application
 *
 * @package     ActiveSyncActiveSync
 * @subpackage  ActiveSync
 */
class ActiveSync_Frontend_Json extends Tinebase_Application_Frontend_Json_Abstract
{
    protected $_applicationName = 'ActiveSync';
    
}